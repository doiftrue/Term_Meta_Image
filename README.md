
Миниатюры для элементов таксономий WordPress
------------------

Добавляет возможность задавать миниатюры для элементов таксономий WordPress, как встроенных (метки, рубрики), так и произвольных.

![](https://wp-kama.ru/wp-content/uploads/2016/12/miniatyura-dlya-termina-sozdanie.png)

Подробнее читайте по этой ссылке: https://wp-kama.ru/7686


Пример использования 
--------------------

### Подключение

Подключите php файл ``Term_Image.php``:

```php
require_once __DIR__ . '/Term_Image.php';
```

Или используйте Composer:

```bash
composer require doiftrue/wp_term_image
```

### Инициализация

```php
add_action( 'admin_init', 'kama_wp_term_image_init' );

function kama_wp_term_image_init(){

	if( is_admin() ){
	
		// Укажем для какой таксономии нужна возможность устанавливать картинки.
		// Можно не указывать, тогда картинки возможность будет автоматом добавлена для всех публичных таксономий
		\wp_term_image\Term_Image::$taxes = [ 'post_tag' ];

		\wp_term_image\Term_Image::instance();
	}
}
```

Пример получения ID и URL картинки термина:

```php
$image_id = get_term_meta( $term_id, '_thumbnail_id', 1 );
$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
```
