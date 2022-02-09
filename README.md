
Миниатюры для элементов таксономий WordPress
------------------

Добавляет возможность задавать миниатюры для элементов таксономий WordPress, как встроенных (метки, рубрики), так и произвольных.

![](https://wp-kama.ru/wp-content/uploads/2016/12/miniatyura-dlya-termina-sozdanie.png)

Подробнее читайте по этой ссылке: https://wp-kama.ru/7686

Пример использования 
--------------------

Подключите php файл ``Term_Image.php``:

```php
require_once __DIR__ . '/Term_Image.php';
```

Инициализация:

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


Подключение через Composer
--------

```bash
composer require doiftrue/wp_term_image
```
