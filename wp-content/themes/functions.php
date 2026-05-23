<?php
/**
 * MTTI Theme Functions - Updated Version
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme Setup
function mtti_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'mtti'),
    ));
}
add_action('after_setup_theme', 'mtti_theme_setup');

// Enqueue scripts and styles
function mtti_theme_scripts() {
    wp_enqueue_style('mtti-style', get_stylesheet_uri(), array(), '2.0');
}
add_action('wp_enqueue_scripts', 'mtti_theme_scripts');
