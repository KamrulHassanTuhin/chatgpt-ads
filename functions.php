<?php

function chatgpt_pseo_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'chatgpt-pseo'),
    ));
}
add_action('after_setup_theme', 'chatgpt_pseo_setup');

function chatgpt_pseo_scripts() {
    wp_enqueue_style('chatgpt-pseo-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'chatgpt_pseo_scripts');
