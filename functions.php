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
 * Pay for Order or Subscription if Logged Out - WooCommerce Checkout
 */
function tfb_log_in_user_from_payment_link(){
    global $wp;
    if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) && isset( $wp->query_vars['order-pay'] ) && ! is_user_logged_in() ) {
        $order_key = $_GET['key'];
        $order_id  = isset( $wp->query_vars['order-pay'] ) ? $wp->query_vars['order-pay'] : absint( $_GET['order_id'] );
        $order     = wc_get_order( $order_id );
        $user_id   = $order->get_user_id();
        $user      = get_user_by('id', $user_id);

        // Check if order key corresponds with order and the status of the payment is pending or failed
        if ( wc_get_objects_property( $order, 'order_key' ) === $order_key && $order->has_status( array( 'pending', 'failed' ) ) ) {
            // For security reasons we want to limit this function to customers and subscribers
            if ( in_array( 'customer', $user->roles ) || in_array( 'subscriber', $user->roles ) ) {
                // Log the user in
                wp_set_auth_cookie($user_id);
                wp_set_current_user($user_id);
            }
        }
        
    }
}
add_action( 'send_headers', 'tfb_log_in_user_from_payment_link', 0 );

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