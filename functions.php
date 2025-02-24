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
 * Auto-login user from WooCommerce payment link for pending/failed orders
 * 
 * This function automatically logs in a user when they access a payment link
 * for their pending or failed order, preventing the need to log in manually.
 */
function tfbTheme_log_in_user_from_payment_link() {
    // Only proceed if not logged in and accessing a payment page
    if ( is_user_logged_in() || !isset( $_GET['pay_for_order'] ) || !isset( $_GET['key'] ) ) {
        return;
    }
    
    global $wp;
    
    // Check if order-pay query var exists
    if ( !isset( $wp->query_vars['order-pay'] ) ) {
        return;
    }
    
    $order_key = wc_clean( $_GET['key'] );
    $order_id = absint( $wp->query_vars['order-pay'] );
    
    // Get order object
    $order = wc_get_order( $order_id );
    
    // Verify order exists
    if ( !$order ) {
        return;
    }
    
    $user_id = $order->get_user_id();
    
    // Only proceed if order belongs to a registered user
    if ( !$user_id ) {
        return;
    }
    
    // Get user object
    $user = get_user_by( 'id', $user_id );
    
    // Verify user exists
    if ( !$user ) {
        return;
    }
    
    // Check if order key matches and status is pending or failed
    if ( $order->get_order_key() === $order_key && 
         $order->has_status( array( 'pending', 'failed' ) ) ) {
        
        // Only allow customers or subscribers to be auto-logged in
        if ( in_array( 'customer', (array) $user->roles ) || 
             in_array( 'subscriber', (array) $user->roles ) ) {
             
            // Log the user in
            wp_set_auth_cookie( $user_id );
            wp_set_current_user( $user_id );
            
            // You can add a session notice here if desired
            // wc_add_notice( __( 'You have been automatically logged in to complete your payment.', 'your-text-domain' ), 'notice' );
        }
    }
}
add_action( 'template_redirect', 'tfbTheme_log_in_user_from_payment_link', 10 );

/**
 * Auto-logout user when their order status changes to on-hold, completed, or processing
 * 
 * This function hooks into the WooCommerce order status change system and logs out
 * the current user if the order belongs to them and the status changes to one of
 * the specified statuses.
 */
function tfbTheme_logout_user_on_order_status_change( $order_id, $old_status, $new_status ) {
    // Only process specific status changes
    $logout_statuses = array( 'on-hold', 'completed', 'processing' );
    
    if ( !in_array( $new_status, $logout_statuses ) ) {
        return;
    }
    
    // Check if a user is logged in
    if ( !is_user_logged_in() ) {
        return;
    }
    
    // Get the current user ID
    $current_user_id = get_current_user_id();
    
    // Get the order
    $order = wc_get_order( $order_id );
    
    // Verify order exists
    if ( !$order ) {
        return;
    }
    
    // Get the order's user ID
    $order_user_id = $order->get_user_id();
    
    // Only log out if the order belongs to the current user
    if ( $current_user_id === $order_user_id ) {
        // You can add a session notice before logout if using AJAX
        // wc_add_notice( __( 'Your payment has been processed. You have been logged out for security.', 'your-text-domain' ), 'notice' );
        
        // Log the user out
        wp_logout();
        
        // Optional: Redirect to homepage or thank you page
        if ( !wp_doing_ajax() ) {
            wp_safe_redirect( home_url() );
            exit;
        }
    }
}
//add_action( 'woocommerce_order_status_changed', 'tfbTheme_logout_user_on_order_status_change', 10, 3 );

/**
 * Alternative approach using specific status hooks
 * This can be used instead of the function above if you prefer dedicated hooks
 */
function tfbTheme_logout_on_specific_status( $order_id ) {
    // Check if a user is logged in
    if ( !is_user_logged_in() ) {
        return;
    }
    
    // Get the current user ID
    $current_user_id = get_current_user_id();
    
    // Get the order
    $order = wc_get_order( $order_id );
    
    // Verify order exists
    if ( !$order ) {
        return;
    }
    
    // Get the order's user ID
    $order_user_id = $order->get_user_id();
    
    // Only log out if the order belongs to the current user
    if ( $current_user_id === $order_user_id ) {
        // Log the user out
        wp_logout();
        
        // Optional: Redirect to homepage or thank you page
        if ( !wp_doing_ajax() ) {
            wp_safe_redirect( home_url() );
            exit;
        }
    }
}

// Uncomment these lines if you prefer using specific status hooks instead of the general status change hook
// add_action( 'woocommerce_order_status_on-hold', 'tfbTheme_logout_on_specific_status', 10, 1 );
// add_action( 'woocommerce_order_status_processing', 'tfbTheme_logout_on_specific_status', 10, 1 );
// add_action( 'woocommerce_order_status_completed', 'tfbTheme_logout_on_specific_status', 10, 1 );

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