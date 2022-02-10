
Миниатюры для элементов таксономий WordPress
------------------

Добавляет возможность задавать миниатюры для элементов таксономий WordPress, как встроенных (метки, рубрики), так и произвольных.

Подробнее читайте по этой ссылке: https://wp-kama.ru/7686

![](https://wp-kama.ru/wp-content/uploads/2016/12/miniatyura-dlya-termina-sozdanie.png)



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

Базовая без передачи параметров:

```php
add_action( 'admin_init', '\\Kama\\WP_Term_Image::init' );
```

С передачей параметров:

```php
add_action( 'admin_init', 'kama_wp_term_image' );

function kama_wp_term_image(){
	
	// Укажем для какой таксономии нужна возможность устанавливать картинки.
	// Можно не указывать, тогда возможность будет автоматом добавлена для всех публичных таксономий.

	\Kama\WP_Term_Image::init( [
		'taxonomies' => [ 'post_tag' ],
	] );
}
```

### Получение данных в шаблоне темы

Пример получения ID картинки-вложения термина:

```php
$image_id = \Kama\WP_Term_Image::get_image_id( $term_id );
// OR
$image_id = get_term_meta( $term_id, '_thumbnail_id', 1 );
```

Теперь по ID мы пожем получить URL вложения:
```php
$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
```
