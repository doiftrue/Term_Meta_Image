
Миниатюры для элементов таксономий WordPress
------------------

Добавляет возможность задавать миниатюры для элементов таксономий WordPress, как встроенных (метки, рубрики), так и произвольных.

![](https://wp-kama.ru/wp-content/uploads/2016/12/miniatyura-dlya-termina-sozdanie.png)

Подробнее читайте по этой ссылке: https://wp-kama.ru/7686


Пример использования 
--------------------

### Подключение

Подключите php файл ``WP_Term_Image.php``:

```php
require_once __DIR__ . '/WP_Term_Image.php';
```

Или используйте Composer:

```bash
composer require doiftrue/wp_term_image
```

### Инициализация

```php
add_action( 'admin_init', 'kama_wp_term_image' );

function kama_wp_term_image(){
	
	// Укажем для какой таксономии нужна возможность устанавливать картинки.
	// Можно не указывать, тогда возможность будет автоматом добавлена для всех публичных таксономий.
	\Kama\WP_Term_Image::$taxes = [ 'post_tag' ];

	\Kama\WP_Term_Image::instance();
}
```

Пример получения ID и URL картинки термина:

```php
$image_id = get_term_meta( $term_id, '_thumbnail_id', 1 );

$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
```
