<?php

namespace Kama;

/**
 * Возможность загружать изображения для терминов (элементов таксономий: категории, метки).
 *
 * Пример инициализации всего функционала:
 *
 *     \Kama\WP_Term_Image::instance();
 *
 * Пример получения ID и URL картинки термина:
 *
 *     $image_id = get_term_meta( $term_id, '_thumbnail_id', 1 );
 *     $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
 *
 * @author Kama (wp-kama.ru)
 *
 * @version 3.2
 */

class WP_Term_Image {

	// Для каких таксономий включить код. По умолчанию для всех публичных.
	public static $taxes = []; // [ 'category', 'post_tag' ];

	// URL пустой картинки
	public static $add_img_url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkAQMAAABKLAcXAAAABlBMVEUAAAC7u7s37rVJAAAAAXRSTlMAQObYZgAAACJJREFUOMtjGAV0BvL/G0YMr/4/CDwY0rzBFJ704o0CWgMAvyaRh+c6m54AAAAASUVORK5CYII=';

	// название мета ключа у термина
	private static $meta_key = '_thumbnail_id';

	// название мета ключа вложения (метка что это вложение относится к термину)
	private static $attach_term_meta_key = 'image_of_term';

	public static function instance(){
		static $inst;

		$inst || $inst = new self();

		return $inst;
	}

	private function __construct(){

		$taxes = self::$taxes ?: get_taxonomies( [ 'public' => true ], 'names' );

		foreach( $taxes as $taxname ){

			add_action( "{$taxname}_add_form_fields", [ $this, '_add_term_image' ], 10, 2 );
			add_action( "{$taxname}_edit_form_fields", [ $this, '_update_term_image' ], 10, 2 );
			add_action( "created_{$taxname}", [ $this, '_save_term_image' ], 10, 2 );
			add_action( "edited_{$taxname}", [ $this, '_updated_term_image' ], 10, 2 );

			add_filter( "manage_edit-{$taxname}_columns", [ $this, '_add_image_column' ] );
			add_filter( "manage_{$taxname}_custom_column", [ $this, '_fill_image_column' ], 10, 3 );
		}

	}

	## поля при создании термина
	public function _add_term_image( $taxonomy ){

		// подключим стили медиа, если их нет
		wp_enqueue_media();

		add_action( 'admin_print_footer_scripts', [ $this, '_add_script' ], 99 );
		$this->css();
		?>
		<div class="form-field term-group">
			<label><?php _e( 'Image', 'default' ) ?></label>
			<div class="term__image__wrapper">
				<a class="termeta_img_button" href="#">
					<img src="<?= self::$add_img_url ?>" alt="">
				</a>
				<input type="button" class="button button-secondary termeta_img_remove"
				       value="<?php _e( 'Remove', 'default' ); ?>"/>
			</div>

			<input type="hidden" id="term_imgid" name="term_imgid" value="">
		</div>
		<?php
	}

	## поля при редактировании термина
	public function _update_term_image( $term, $taxonomy ){

		wp_enqueue_media(); // подключим стили медиа, если их нет

		add_action( 'admin_print_footer_scripts', [ $this, '_add_script' ], 99 );

		$image_id = get_term_meta( $term->term_id, self::$meta_key, true );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : self::$add_img_url;
		$this->css();
		?>
		<tr class="form-field term-group-wrap">
			<th scope="row"><?php _e( 'Image', 'default' ); ?></th>
			<td>
				<div class="term__image__wrapper">
					<a class="termeta_img_button" href="#">
						<?php echo '<img src="' . $image_url . '" alt="">'; ?>
					</a>
					<input type="button" class="button button-secondary termeta_img_remove"
					       value="<?php _e( 'Remove', 'default' ); ?>"/>
				</div>

				<input type="hidden" id="term_imgid" name="term_imgid" value="<?php echo $image_id; ?>">
			</td>
		</tr>
		<?php
	}

	private function css(){
		?>
		<style>
			.termeta_img_button{ display:inline-block; margin-right:1em; }

			.termeta_img_button img{ display:block; float:left; margin:0; padding:0; min-width:100px; max-width:150px; height:auto; background:rgba(0, 0, 0, .07); }

			.termeta_img_button:hover img{ opacity:.8; }

			.termeta_img_button:after{ content:''; display:table; clear:both; }
		</style>
		<?php
	}

	## Add script
	public function _add_script(){
		// выходим если не на нужной странице таксономии
		//$cs = get_current_screen();
		//if( ! in_array($cs->base, array('edit-tags','term')) || ! in_array($cs->taxonomy, (array) $this->for_taxes) )
		//  return;

		$title = __( 'Featured Image', 'default' );
		$button_txt = __( 'Set featured image', 'default' );
		?>
		<script>
			jQuery( document ).ready( function( $ ){
				var frame
				var $imgwrap = $( '.term__image__wrapper' )
				var $imgid = $( '#term_imgid' )

				// добавление
				$( '.termeta_img_button' ).click( function( ev ){
					ev.preventDefault();

					if( frame ){
						frame.open();
						return;
					}

					// задаем media frame
					frame = wp.media.frames.questImgAdd = wp.media( {
						states: [
							new wp.media.controller.Library( {
								title   : '<?php echo $title ?>',
								library : wp.media.query( { type: 'image' } ),
								multiple: false
								//date:   false
							} )
						],
						button: {
							text: '<?php echo $button_txt ?>' // Set the text of the button.
						}
					} );

					// выбор
					frame.on( 'select', function(){
						var selected = frame.state().get( 'selection' ).first().toJSON();
						if( selected ){
							$imgid.val( selected.id );
							$imgwrap.find( 'img' ).attr( 'src', selected.sizes.thumbnail.url );
						}
					} );

					// открываем
					frame.on( 'open', function(){
						if( $imgid.val() ) frame.state().get( 'selection' ).add( wp.media.attachment( $imgid.val() ) );
					} );

					frame.open();
				} );

				// удаление
				$( '.termeta_img_remove' ).click( function(){
					$imgid.val( '' );
					$imgwrap.find( 'img' ).attr( 'src', '<?php echo self::$add_img_url ?>' );
				} );
			} );
		</script>

		<?php
	}

	## Добавляет колонку картинки в таблицу терминов
	public function _add_image_column( $columns ){
		// fix column width
		add_action( 'admin_notices', function(){
			echo '<style>.column-image{ width:50px; text-align:center; }</style>';
		} );

		// column without name
		return array_slice( $columns, 0, 1 ) + [ 'image' => '' ] + $columns;
	}

	public function _fill_image_column( $string, $column_name, $term_id ){

		if( 'image' === $column_name && $image_id = get_term_meta( $term_id, self::$meta_key, 1 ) ){
			$string = '<img src="' . wp_get_attachment_image_url( $image_id, 'thumbnail' ) . '" width="50" height="50" alt="" style="border-radius:4px;" />';
		}

		return $string;
	}

	## Save the form field
	public function _save_term_image( $term_id, $tt_id ){

		if( isset( $_POST['term_imgid'] ) && $attach_id = (int) $_POST['term_imgid'] ){
			update_term_meta( $term_id, self::$meta_key, $attach_id );
			update_post_meta( $attach_id, self::$attach_term_meta_key, $term_id );
		}
	}

	## Update the form field value
	public function _updated_term_image( $term_id, $tt_id ){

		if( ! isset( $_POST['term_imgid'] ) ){
			return;
		}

		$term_attach_id = (int) get_term_meta( $term_id, self::$meta_key, 1 );

		$attach_id = (int) $_POST['term_imgid'];

		// update metas
		if( $attach_id ){
			update_term_meta( $term_id, self::$meta_key, $attach_id );
			update_post_meta( $attach_id, self::$attach_term_meta_key, $term_id );
		}
		else{
			delete_term_meta( $term_id, self::$meta_key );
		}

		// delete attachment
		if( $term_attach_id && $term_attach_id != $attach_id ){

			$term_attach = get_post( $term_attach_id );

			if( $term_attach && ! $term_attach->post_parent ){
				wp_delete_attachment( $term_attach_id );
			}
		}

	}

}


