<?php

namespace Kama;

/**
 * Возможность загружать изображения для терминов (элементов таксономий: категории, метки).
 *
 * Пример использвоания смотрите здесь: https://github.com/doiftrue/Term_Meta_Image
 *
 * @author Kama (wp-kama.ru)
 *
 * @version 3.5
 */

class WP_Term_Image {

	/**
	 * Параметры по умолчанию, которые можно изменить при инициализации класса.
	 *
	 * @var array
	 */
	private static $args = [

		// Для каких таксономий включить код. По умолчанию для всех публичных.
		'taxonomies' => [],

		// URL пустой картинки
		'noimage_src' => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 1000 1000'%3E%3Cpath fill='%23bbb' d='M430 660 l0 -90 -90 0 -90 0 0 -70 0 -70 90 0 90 0 0 -85 0 -85 70 0 70 0 0 85 0 85 90 0 90 0 0 70 0 70 -90 0 -90 0 0 90 0 90 -70 0 -70 0 0 -90z'%3E%3C/path%3E%3C/svg%3E",
	];

	/**
	 * Название мета ключа у термина где храниться ID картинки-вложения.
	 *
	 * @var string
	 */
	private static $meta_key = '_thumbnail_id';

	/**
	 * Название мета ключа для поста-вложения (метка к какому термину относится картинка).
	 *
	 * @var string
	 */
	private static $attach_meta_key = 'image_of_term';

	/**
	 * @param array|string $args String can be passed when call it from WP hook directly.
	 *                           See: self::$args.
	 *
	 * @return WP_Term_Image
	 */
	public static function init( $args = [] ){
		static $inst;

		$inst || $inst = new self( (array) $args );

		return $inst;
	}

	private function __construct( array $args = [] ){

		self::$args = $args + self::$args;

		$taxes = self::$args['taxonomies'] ?: get_taxonomies( [ 'public' => true ], 'names' );

		foreach( $taxes as $taxname ){

			add_action( "{$taxname}_add_form_fields", [ $this, '_add_term__image_fields' ], 10 );
			add_action( "{$taxname}_edit_form_fields", [ $this, '_update_term__image_fields' ], 10, 2 );
			add_action( "created_{$taxname}", [ $this, '_create_term__handler' ], 10, 2 );
			add_action( "edited_{$taxname}", [ $this, '_updated_term__handler' ], 10 );

			// table columns
			add_filter( "manage_edit-{$taxname}_columns", [ $this, '_add_image_column' ] );
			add_filter( "manage_{$taxname}_custom_column", [ $this, '_fill_image_column' ], 10, 3 );
		}

	}

	/**
	 * @param int|WP_Term $term Термин картинку которого нужно получить.
	 *
	 * @return int 0 if no image.
	 */
	public static function get_image_id( $term ){

		return (int) get_term_meta( is_object( $term ) ? $term->term_id : $term, self::$meta_key, true );
	}

	/**
	 * Поля при создании термина.
	 *
	 * @param $taxonomy
	 *
	 * @return void
	 */
	public function _add_term__image_fields( $taxonomy ){

		// подключим стили медиа, если их нет
		wp_enqueue_media();

		add_action( 'admin_print_footer_scripts', [ $this, '_add_script' ], 99 );
		$this->_css();
		?>
		<div class="form-field term-group">
			<label><?php _e( 'Image', 'default' ) ?></label>

			<div class="term__image__wrapper">
				<a class="termeta_img_button" href="#">
					<img width="100" height="100" alt="" src="<?= self::$args['noimage_src'] ?>">
				</a>
				<input type="button" class="button button-secondary termeta_img_remove_js"
				       value="<?php _e( 'Remove', 'default' ) ?>"/>
			</div>

			<input type="hidden" id="term_imgid" name="term_imgid" value="">
		</div>
		<?php
	}

	## поля при редактировании термина
	public function _update_term__image_fields( $term, $taxonomy ){

		wp_enqueue_media(); // подключим стили медиа, если их нет

		add_action( 'admin_print_footer_scripts', [ $this, '_add_script' ], 99 );

		$image_id = self::get_image_id( $term );

		$image_url = $image_id
			? wp_get_attachment_image_url( $image_id, 'thumbnail' )
			: self::$args['noimage_src'];

		$this->_css();
		?>
		<tr class="form-field term-group-wrap">
			<th scope="row"><?php _e( 'Image', 'default' ) ?></th>
			<td>
				<div class="term__image__wrapper">
					<a class="termeta_img_button" href="#">
						<?= '<img src="' . $image_url . '" alt="">' ?>
					</a>
					<input type="button" class="button button-secondary termeta_img_remove_js"
					       value="<?php _e( 'Remove', 'default' ) ?>"/>
				</div>

				<input type="hidden" id="term_imgid" name="term_imgid" value="<?= $image_id ?>">
			</td>
		</tr>
		<?php
	}

	private function _css(){
		?>
		<style>
			.termeta_img_button{ display:inline-block; margin-right:1em; }
			.termeta_img_button img{ display:block; float:left; margin:0; padding:0;
				width:100px; height:100px;
				background:rgba(0, 0, 0, .07);
			}
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
				let frame
				let $imgwrap = $( '.term__image__wrapper' )
				let $imgid = $( '#term_imgid' )

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
								title   : '<?= $title ?>',
								library : wp.media.query( { type: 'image' } ),
								multiple: false
								//date:   false
							} )
						],
						button: {
							text: '<?= $button_txt ?>' // Set the text of the button.
						}
					} );

					// выбор
					frame.on( 'select', function(){
						let selected = frame.state().get( 'selection' ).first().toJSON();
						if( selected ){
							$imgid.val( selected.id );
							let src = selected.sizes.thumbnail ? selected.sizes.thumbnail.url : selected.url
							$imgwrap.find( 'img' ).attr( 'src', src );
						}
					} );

					// открываем
					frame.on( 'open', function(){
						if( $imgid.val() ) frame.state().get( 'selection' ).add( wp.media.attachment( $imgid.val() ) );
					} );

					frame.open();
				} );

				// удаление
				$( '.termeta_img_remove_js' ).click( function(){
					$imgid.val( '' );
					$imgwrap.find( 'img' ).attr( 'src', '<?= str_replace( "'", "\'", self::$args['noimage_src'] ) ?>' );
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

		if( 'image' === $column_name ){
			$image_id = self::get_image_id( $term_id );

			$string = $image_id
				? '<img src="' . wp_get_attachment_image_url( $image_id, 'thumbnail' ) . '" width="50" height="50" alt="" style="border-radius:4px;" />'
				: '';
		}

		return $string;
	}

	## Save the form field
	public function _create_term__handler( $term_id, $tt_id ){

		if( isset( $_POST['term_imgid'] ) && $attach_id = (int) $_POST['term_imgid'] ){
			update_term_meta( $term_id, self::$meta_key, $attach_id );

			self::up_attach_term_id( $attach_id, 'add', $term_id );
		}
	}

	## Update the form field value
	public function _updated_term__handler( $term_id ){

		if( ! isset( $_POST['term_imgid'] ) ){
			return;
		}

		$old_attach_id = self::get_image_id( $term_id );

		$attach_id = (int) $_POST['term_imgid'];

		// update metas
		if( $attach_id ){
			update_term_meta( $term_id, self::$meta_key, $attach_id );

			self::up_attach_term_id( $attach_id, 'add', $term_id );
		}
		else{
			delete_term_meta( $term_id, self::$meta_key );
		}

		// delete attachment
		$old_attach = get_post( $old_attach_id );
		if( $old_attach && (int) $old_attach_id !== (int) $attach_id ){

			$old_attach_term_ids = self::up_attach_term_id( $old_attach_id, 'remove', $term_id );

			// Вложение не прикреплено к посту или оно прикреплено к другому термину
			if( ! $old_attach_term_ids && ! $old_attach->post_parent ){
				wp_delete_attachment( $old_attach_id );
			}
		}

	}

	/**
	 * Updates post-meta field of specified attachment (post) - adds or removes term id from the field.
	 *
	 * @param int    $attach_id
	 * @param string $action    add|remove.
	 * @param int    $term_id   Term id to add/remove to attachment.
	 *
	 * @return array term ids updated fo attachment.
	 */
	private static function up_attach_term_id( $attach_id, $action, $term_id ){

		$term_ids = wp_parse_list( get_post_meta( $attach_id, self::$attach_meta_key, true ) );

		// add
		if( 'add' === $action ){
			$term_ids[] = $term_id;
		}
		// remove
		else {
			$term_ids = array_diff( $term_ids, [ $term_id ] );
		}

		// join - save as: ,12,34,54,
		$term_ids = array_unique( (array) $term_ids );
		$term_ids = array_map( 'sanitize_text_field', $term_ids );
		$joined_term_ids = $term_ids ? ','. implode( ',', $term_ids ) .',' : '';

		update_post_meta( $attach_id, self::$attach_meta_key, $joined_term_ids );

		return $term_ids;
	}

}
