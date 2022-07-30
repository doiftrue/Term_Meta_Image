<?php

namespace Kama;

use WP_Term;

/**
 * Ability to upload images for terms (elements of taxonomies: categories, tags).
 *
 * Examples of usage: https://github.com/doiftrue/Term_Meta_Image
 *
 * @author Kama (wp-kama.com)
 *
 * @version 3.5.2
 */

interface WP_Term_Image_Interface {

	/**
	 * @param array|string $args String can be passed when call it from WP hook directly.
	 *                           {@see: static::$defautl_args}.
	 *
	 * @return WP_Term_Image Instance of class by specified parameters.
	 */
	public static function init( $args = [] );

	/**
	 * @param int|WP_Term $term  The term which image you want to get.
	 *
	 * @return int WP attachment image ID or 0 if no image.
	 */
	public static function get_image_id( $term );
}

class WP_Term_Image implements WP_Term_Image_Interface {

	private $args;

	/**
	 * Default parameters that can be changed during class initialization.
	 *
	 * @var array
	 */
	private static $default_args = [

		// For which taxonomies to include code. The default is for all public ones.
		'taxonomies' => [],

		// URL of the empty image
		'noimage_src' => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 1000 1000'%3E%3Cpath fill='%23bbb' d='M430 660 l0 -90 -90 0 -90 0 0 -70 0 -70 90 0 90 0 0 -85 0 -85 70 0 70 0 0 85 0 85 90 0 90 0 0 70 0 70 -90 0 -90 0 0 90 0 90 -70 0 -70 0 0 -90z'%3E%3C/path%3E%3C/svg%3E",
	];

	/**
	 * The name of the meta key for the term where the ID of the attachment is stored.
	 *
	 * @var string
	 */
	private static $meta_key = '_thumbnail_id';

	/**
	 * The name of the meta key for the attachment post (label which term the picture belongs to).
	 *
	 * @var string
	 */
	private static $attach_meta_key = 'image_of_term';

	private function __construct(){}

	public static function init( $args = [] ){
		static $inst;

		$args = array_filter( (array) $args ); // may be null if called directly from wp hook
		$args = array_intersect_key( $args, self::$default_args ); // leave allowed only

		$inst_key = md5( serialize( $args ) );

		if( empty( $inst[ $inst_key ] ) ){
			$inst[ $inst_key ] = new self();

			$inst[ $inst_key ]->set_args( $args );
			$inst[ $inst_key ]->register_hooks();
		}

		return $inst[ $inst_key ];
	}

	private function set_args( array $args = [] ){

		$this->args = $args + self::$default_args;

		if( ! $this->args['taxonomies'] ){
			$this->args['taxonomies'] = get_taxonomies( [ 'public' => true ], 'names' );
		}
	}

	private function register_hooks(){

		foreach( $this->args['taxonomies'] as $taxname ){

			add_action( "{$taxname}_add_form_fields", [ $this, '_add_term__image_fields' ], 10 );
			add_action( "{$taxname}_edit_form_fields", [ $this, '_update_term__image_fields' ], 10, 2 );
			add_action( "created_{$taxname}", [ $this, '_create_term__handler' ], 10, 2 );
			add_action( "edited_{$taxname}", [ $this, '_updated_term__handler' ], 10 );

			// table columns
			add_filter( "manage_edit-{$taxname}_columns", [ WP_Term_Image_Table_Columns::class, '_add_image_column' ] );
			add_filter( "manage_{$taxname}_custom_column", [ WP_Term_Image_Table_Columns::class, '_fill_image_column' ], 10, 3 );
		}
	}

	/**
	 * @param int|WP_Term $term  The term which image you want to get.
	 *
	 * @return int 0 if no image.
	 */
	public static function get_image_id( $term ){

		return (int) get_term_meta( is_object( $term ) ? $term->term_id : $term, self::$meta_key, true );
	}

	/**
	 * Fields at term creation.
	 *
	 * @param string $taxonomy
	 *
	 * @return void
	 */
	public function _add_term__image_fields( $taxonomy ){

		// add the media styles, if they are not present
		wp_enqueue_media();

		add_action( 'admin_print_footer_scripts', [ $this, '_add_script' ], 99 );
		$this->_css();
		?>
		<div class="form-field term-group term_image_wrapper_js">
			<label><?php _e( 'Image', 'default' ) ?></label>

			<div class="term__image__wrapper">
				<button type="button" class="termeta_img_button termeta_img_button_js">
					<img width="100" height="100" alt="" src="<?= $this->args['noimage_src'] ?>">
				</button>
				<button type="button" class="button button-secondary termeta_img_remove_js"><?php _e( 'Remove', 'default' ) ?></button>
			</div>

			<input type="hidden" id="term_imgid" name="term_imgid" value="">
		</div>
		<?php
	}

	/**
	 * Fields when editing a term.
	 *
	 * @param WP_Term $term
	 * @param string  $taxonomy
	 *
	 * @return void
	 */
	public function _update_term__image_fields( $term, $taxonomy ){

		wp_enqueue_media();

		add_action( 'admin_print_footer_scripts', [ $this, '_add_script' ], 99 );

		$image_id = self::get_image_id( $term );

		$image_url = $image_id
			? wp_get_attachment_image_url( $image_id, 'thumbnail' )
			: $this->args['noimage_src'];

		$this->_css();
		?>
		<tr class="form-field term-group-wrap term_image_wrapper_js">
			<th scope="row"><?php _e( 'Image', 'default' ) ?></th>
			<td>
				<div class="term__image__wrapper">
					<button type="button" class="termeta_img_button termeta_img_button_js"><img src="<?= $image_url ?>" alt=""></button>
					<button type="button" class="button button-secondary termeta_img_remove_js"><?php _e( 'Remove', 'default' ) ?></button>
				</div>

				<input type="hidden" id="term_imgid" name="term_imgid" value="<?= $image_id ?>">
			</td>
		</tr>
		<?php
	}

	private function _css(){
		?>
		<style>
			.termeta_img_button{ display:inline-block; margin-right:1em;
				border:0; padding:0; cursor:pointer; /* reset */
			}
			.termeta_img_button img{ display:block; float:left; margin:0; padding:0;
				width:100px; height:100px;
				background:rgba(0, 0, 0, .07);
			}
			.termeta_img_button:hover img{ opacity:.8; }
			.termeta_img_button:after{ content:''; display:table; clear:both; }
		</style>
		<?php
	}

	public function _add_script(){

		// выходим если не на нужной странице таксономии
		//$cs = get_current_screen();
		//if( ! in_array($cs->base, array('edit-tags','term')) || ! in_array($cs->taxonomy, (array) $this->for_taxes) )
		//  return;

		$title = __( 'Featured Image', 'default' );
		$button_txt = __( 'Set featured image', 'default' );
		?>
		<script>
		document.addEventListener( 'DOMContentLoaded', function(){

			const imgwrap = document.querySelector( '.term_image_wrapper_js' )

			if( ! imgwrap ){
				return;
			}

			const addButton = imgwrap.querySelector( '.termeta_img_button_js' )
			const delButton = imgwrap.querySelector( '.termeta_img_remove_js' )
			const imgInput = imgwrap.querySelector( '#term_imgid' )
			let frame

			// add / edit
			addButton.addEventListener( 'click', function( ev ){
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

				// select
				frame.on( 'select', function(){
					let selected = frame.state().get( 'selection' ).first().toJSON();
					if( selected ){
						imgInput.value = selected.id;
						let src = selected.sizes.thumbnail ? selected.sizes.thumbnail.url : selected.url
						imgwrap.querySelector( 'img' ).setAttribute( 'src', src );
					}
				} );

				// open media-popup
				frame.on( 'open', function(){
					if( imgInput.value )
						frame.state().get( 'selection' ).add( wp.media.attachment( imgInput.value ) );
				} );

				frame.open();
			} );

			// remove
			delButton.addEventListener( 'click', function(){
				imgInput.value = '';
				imgwrap.querySelector( 'img' ).setAttribute( 'src', '<?= str_replace( "'", "\'", $this->args['noimage_src'] ) ?>' );
			} );

		} );
		</script>
		<?php
	}

	// Save the form field
	public function _create_term__handler( $term_id, $tt_id ){

		$attach_id = isset( $_POST['term_imgid'] ) ? (int) $_POST['term_imgid'] : 0;

		if( ! $attach_id ){
			return;
		}

		update_term_meta( $term_id, self::$meta_key, $attach_id );

		self::up_attach_term_id( $attach_id, 'add', $term_id );
	}

	// Update the form field value
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

class WP_Term_Image_Table_Columns {

	/**
	 * Adds an image column to the term table. Method for WP hook.
	 *
	 * @param array $columns
	 *
	 * @return array|string[]
	 */
	public static function _add_image_column( $columns ){

		// fix column width
		add_action( 'admin_notices', function(){
			echo '<style>.column-image{ width:50px; text-align:center; }</style>';
		} );

		// column has no name
		return array_slice( $columns, 0, 1 ) + [ 'image' => '' ] + $columns;
	}

	public static function _fill_image_column( $string, $column_name, $term_id ){

		if( 'image' === $column_name ){
			$image_id = WP_Term_Image::get_image_id( $term_id );

			$string = $image_id
				? sprintf( '<img src="%s" width="50" height="50" alt="" style="border-radius:4px;" />',
					wp_get_attachment_image_url( $image_id, 'thumbnail' ) )
				: '';
		}

		return $string;
	}

}