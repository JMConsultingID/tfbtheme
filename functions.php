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
 * Redirects to the thank you page after logout
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
        // Store the thank you page URL before logging out
        $thank_you_url = $order->get_checkout_order_received_url();
        
        // Log the user out
        wp_logout();
        
        // Redirect to thank you page if not doing AJAX
        if ( !wp_doing_ajax() ) {
            wp_safe_redirect( $thank_you_url );
            exit;
        } else {
            // For AJAX requests, store the redirect URL in a transient
            // with a unique key based on the user ID
            set_transient( 'logout_redirect_' . $current_user_id, $thank_you_url, 60 ); // Expires in 60 seconds
        }
    }
}
add_action( 'woocommerce_order_status_changed', 'tfbTheme_logout_user_on_order_status_change', 10, 3 );

/**
 * Handle AJAX redirects by checking for the transient on the next page load
 */
function tfbTheme_check_for_redirect() {
    if ( !is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $redirect_url = get_transient( 'logout_redirect_' . $user_id );
        
        if ( $redirect_url ) {
            // Delete the transient to prevent redirect loops
            delete_transient( 'logout_redirect_' . $user_id );
            
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}
add_action( 'template_redirect', 'tfbTheme_check_for_redirect', 10 );

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
        // Store the thank you page URL before logging out
        $thank_you_url = $order->get_checkout_order_received_url();
        
        // Log the user out
        wp_logout();
        
        // Redirect to thank you page if not doing AJAX
        if ( !wp_doing_ajax() ) {
            wp_safe_redirect( $thank_you_url );
            exit;
        }
    }
}

// Uncomment these lines if you prefer using specific status hooks instead of the general status change hook
// add_action( 'woocommerce_order_status_on-hold', 'tfbTheme_logout_on_specific_status', 10, 1 );
// add_action( 'woocommerce_order_status_processing', 'tfbTheme_logout_on_specific_status', 10, 1 );
// add_action( 'woocommerce_order_status_completed', 'tfbTheme_logout_on_specific_status', 10, 1 );

add_action('template_redirect', 'force_guest_checkout_on_order_pay');

function force_guest_checkout_on_order_pay() {
    // Check if we're on the order-pay endpoint
    if (is_wc_endpoint_url('order-pay')) {
        // Get the order ID from the URL
        global $wp;
        $order_id = absint($wp->query_vars['order-pay']);
        
        // Make sure we have a valid order
        if ($order_id > 0) {
            // If user is logged in but we want to process as guest
            if (is_user_logged_in()) {
                // Store the order key
                $order = wc_get_order($order_id);
                if ($order) {
                    $order_key = $order->get_order_key();
                    // Log the user out
                    wp_logout();
                    // Redirect back to the order-pay page with the key
                    wp_redirect(wc_get_endpoint_url('order-pay', $order_id, wc_get_checkout_url()) . '?pay_for_order=true&key=' . $order_key);
                    exit;
                }
            }
        }
    }
}

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