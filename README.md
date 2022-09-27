
Thumbnails for WordPress taxonomy elements
------------------

Adds the ability to set thumbnails for WordPress terms (elements of taxonomies), both built-in (tags, categories) and custom ones.

Read more at this link: https://wp-kama.ru/7686

![](https://wp-kama.ru/wp-content/uploads/2016/12/miniatyura-dlya-termina-sozdanie.png)



Usage Example
--------------------

### Connecting

Include ``WP_Term_Image.php`` php file:

```php
require_once __DIR__ . '/WP_Term_Image.php';
```

Or use the Composer:

```bash
composer require doiftrue/wp_term_image
```

### Initialization

Basic without parameters passing:

```php
add_action( 'admin_init', [ \Kama\WP_Term_Image::class, 'init' ] );
```

With parameters passing:

```php
add_action( 'admin_init', 'kama_wp_term_image' );

function kama_wp_term_image(){
	
	// Let's specify for which taxonomy it is necessary to set images.
	// It is possible not to specify, then the possibility will be automatically added for all public taxonomies.

	\Kama\WP_Term_Image::init( [
		'taxonomies' => [ 'post_tag' ],
	] );
}
```

### Get data in the theme template

An example of getting the ID of an attachment image of a term:

```php
$image_id = \Kama\WP_Term_Image::get_image_id( $term_id );

// OR
$image_id = get_term_meta( $term_id, '_thumbnail_id', 1 );
```

Now we can use the ID to get the URL of the attachment:

```php
$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
```
