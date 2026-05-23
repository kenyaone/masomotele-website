<?php
/**
 * Plugin Name: Finachub Lipa na Mpesa Checkout for WooCommerce
 * Plugin URI:  https://finachub.com/product-category/plugins/mpesa/  // Updated URL
 * Description: Accept M-Pesa STK Push payments in WooCommerce. A simple and reliable way to integrate Kenya's most popular payment method.
 * Version:     1.3.2 
 * Author:      Finacc
 * Author URI:  https://finachub.com
 * Text Domain: finachub-checkout-for-m-pesa
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Define constants */
define( 'FINACHUB_MPESA_VERSION', '1.2.0' );
define( 'FINACHUB_MPESA_PLUGIN_FILE', __FILE__ );
define( 'FINACHUB_MPESA_PLUGIN_PATH', plugin_dir_path( FINACHUB_MPESA_PLUGIN_FILE ) );
define( 'FINACHUB_MPESA_PLUGIN_URL', plugin_dir_url( FINACHUB_MPESA_PLUGIN_FILE ) );

/**
 * Include the installer file.
 */
require_once FINACHUB_MPESA_PLUGIN_PATH . 'includes/class-finachub-mpesa-checkout-install.php';

/**
 * Activation/Deactivation Hooks.
 */
register_activation_hook( __FILE__, [ 'Finachub_Mpesa_Checkout_Install', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Finachub_Mpesa_Checkout_Install', 'deactivate' ] );

/**
 * Autoloader for plugin classes.
 */
spl_autoload_register( function ( $class_name ) {
    $prefix   = 'Finachub_Mpesa_Checkout_';
    $base_dir = FINACHUB_MPESA_PLUGIN_PATH . 'includes/';
    if ( 0 !== strpos( $class_name, $prefix ) ) {
        return;
    }
    $relative_class = substr( $class_name, strlen( $prefix ) );
    $file_name      = 'class-finachub-mpesa-checkout-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
    $file_path      = $base_dir . $file_name;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
} );

/**
 * Initialize the plugin after WooCommerce loads.
 */
add_action( 'plugins_loaded', 'finachub_mpesa_checkout_plugin_init', 20 );
function finachub_mpesa_checkout_plugin_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . sprintf( esc_html__( 'Finachub Lipa na Mpesa Checkout requires %sWooCommerce%s to be installed and activated.', 'finachub-checkout-for-m-pesa' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">', '</a>' ) . '</p></div>';
        });
        return;
    }

    // Load text domain for localization
    load_plugin_textdomain( 'finachub-checkout-for-m-pesa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    // Register the Payment Gateway.
    add_filter( 'woocommerce_payment_gateways', function ( $methods ) {
        $methods[] = 'Finachub_Mpesa_Checkout_Gateway';
        return $methods;
    } );

    // Initialize the Admin Dashboard.
    if ( is_admin() && class_exists( 'Finachub_Mpesa_Checkout_Admin_Dashboard' ) ) {
        new Finachub_Mpesa_Checkout_Admin_Dashboard();
    }
}

/**
 * Enqueue front-end CSS & JS.
 */
add_action( 'wp_enqueue_scripts', function() {
    // Enqueue the Jost font for front-end.
    wp_enqueue_style(
        'finachub_mpesa_jost_font_frontend',
        'https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap',
        [],
        '1.0' // Font version doesn't need to match plugin version
    );

    // Styles for checkout page and waiting page
    if ( is_checkout() || finachub_mpesa_is_waiting_page() ) {
        wp_enqueue_style(
            'finachub_mpesa_frontend_styles',
            FINACHUB_MPESA_PLUGIN_URL . 'assets/css/mpesa-frontend-styles.css',
            ['finachub_mpesa_jost_font_frontend'],
            FINACHUB_MPESA_VERSION
        );
    }

     // JS for waiting page (remains inactive in free version)
     if ( finachub_mpesa_is_waiting_page() ) {
         wp_enqueue_script(
            'finachub_mpesa_waiting_script',
            FINACHUB_MPESA_PLUGIN_URL . 'assets/js/mpesa-waiting.js',
            [ 'jquery' ],
            FINACHUB_MPESA_VERSION,
            true
        );
     }
} );

/**
 * Helper function to check if we are on the M-Pesa waiting page.
 */
function finachub_mpesa_is_waiting_page() {
    // Check for waiting flag, order ID, and a valid nonce
    return ( isset( $_GET['mpesa_waiting'], $_GET['order_id'], $_GET['_wpnonce'] ) &&
             'yes' === $_GET['mpesa_waiting'] &&
             wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'finachub_waiting' )
           );
}


/**
 * Redirect user to custom waiting page if needed.
 */
add_action( 'template_redirect', 'finachub_mpesa_checkout_maybe_show_waiting_screen' );
function finachub_mpesa_checkout_maybe_show_waiting_screen() {
    if ( finachub_mpesa_is_waiting_page() ) { // Use helper function which includes nonce check

        $order_id = absint( $_GET['order_id'] );
        $order = wc_get_order( $order_id );

        // Validate order and user access
        $is_valid_user = is_user_logged_in() && ( $order && $order->get_user_id() === get_current_user_id() || current_user_can( 'manage_woocommerce' ) );
        $guest_order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
        $is_valid_guest = ! is_user_logged_in() && $order && method_exists($order, 'key_is_valid') && $order->key_is_valid( $guest_order_key );

        if ( ! $order || ( ! $is_valid_user && ! $is_valid_guest ) ) {
             wp_safe_redirect( home_url('/') ); // Redirect silently for invalid access
             exit;
        }

        finachub_mpesa_checkout_render_waiting_page( $order_id );
        exit;
    }
}

/**
 * Render the M-Pesa waiting page.
 */
function finachub_mpesa_checkout_render_waiting_page( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_die( esc_html__( 'Invalid order specified.', 'finachub-checkout-for-m-pesa' ) );
    }

    // Upsell notice with updated URL
    $upgrade_notice_html = sprintf(
        '<div class="mpesa-waiting-upgrade-notice promo-box">
             <span class="promo-icon dashicons dashicons-info-outline"></span>
            <h4>%1$s</h4>
            <p>%2$s</p>
            <a href="%3$s" target="_blank" class="button mpesa-upgrade-cta pulse-button">%4$s <span class="dashicons dashicons-arrow-right-alt2"></span></a>
        </div>',
        esc_html__( 'This is the free version.', 'finachub-checkout-for-m-pesa' ),
        esc_html__( 'Automatic payment completion requires the Pro version. Please manually check your order status in your account later.', 'finachub-checkout-for-m-pesa' ),
        esc_url( 'https://finachub.com/product-category/plugins/mpesa/' ), // Updated Purchase URL
        esc_html__( 'Upgrade Now', 'finachub-checkout-for-m-pesa' )
    );


    $logo_url = FINACHUB_MPESA_PLUGIN_URL . 'assets/img/mpesa-logo.png';

    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <?php wp_head(); // Enqueues styles and scripts ?>
        <title><?php esc_html_e( 'Awaiting M-Pesa Payment', 'finachub-checkout-for-m-pesa' ); ?></title>
    </head>
    <body <?php body_class( 'mpesa-waiting-body' ); ?>>
        <div class="mpesa-waiting-container">
            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'M-Pesa Logo', 'finachub-checkout-for-m-pesa' ); ?>" class="mpesa-waiting-logo">

            <div class="mpesa-waiting-spinner">
                <svg viewBox="25 25 50 50">
                    <circle cx="50" cy="50" r="20"></circle>
                </svg>
            </div>

            <h2><?php esc_html_e( 'Please Confirm Payment on Your Phone', 'finachub-checkout-for-m-pesa' ); ?></h2>
            <p class="mpesa-instruction"><?php esc_html_e( 'An M-Pesa payment request has been sent. Please enter your M-Pesa PIN on your phone to authorize the payment.', 'finachub-checkout-for-m-pesa' ); ?></p>

            <?php echo wp_kses_post( $upgrade_notice_html ); // Output the notice with updated URL ?>

            <div class="mpesa-waiting-info">
                 <p><strong><?php esc_html_e( 'Order Number:', 'finachub-checkout-for-m-pesa' ); ?></strong> #<?php echo esc_html( $order->get_order_number() ); ?></p>
                 <p><strong><?php esc_html_e( 'Amount:', 'finachub-checkout-for-m-pesa' ); ?></strong> <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></p>
                 <p><small><em><?php esc_html_e( 'Note: Automatic payment completion requires the Pro version.', 'finachub-checkout-for-m-pesa' ); ?></em></small></p>
            </div>

    
        </div>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}
?>