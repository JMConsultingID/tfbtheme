<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/tfb-elementor-theme/
 *
 * @package tfbTheme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('TFB_THEME_VERSION', '1.1.1');
add_filter('woocommerce_order_pay_need_login', '__return_false');

/**
 * Load tfb theme scripts & styles.
 *
 * @return void
 */

function tfb_theme_scripts_styles()
{
    wp_enqueue_style('tfb-theme-style', get_stylesheet_directory_uri() . '/style.css', [], TFB_THEME_VERSION);
    wp_enqueue_style('tfb-theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/tfb-theme.css', [], TFB_THEME_VERSION);
    wp_enqueue_script('tfb-theme-custom-script', get_stylesheet_directory_uri() . '/assets/js/tfb-theme.js', [], TFB_THEME_VERSION, true);
}
add_action('wp_enqueue_scripts', 'tfb_theme_scripts_styles', 20);