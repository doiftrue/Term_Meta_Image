<?php

// подключаем лоадер, если еще этого не сделали
require_once __DIR__ . '/vendor/autoload.php';

// init
add_action( 'admin_init', 'kama_wp_term_image_init' );

function kama_wp_term_image_init(){

	if( is_admin() ){
		// Укажем для какой таксономии нужна возможность устанавливать картинки.
		// Можно не указывать, тогда картинки возможность будет автоматом добавлена для всех публичных таксономий
		\wp_term_image\Term_Image::$taxes = [ 'post_tag' ];

		\wp_term_image\Term_Image::instance();
	}
}