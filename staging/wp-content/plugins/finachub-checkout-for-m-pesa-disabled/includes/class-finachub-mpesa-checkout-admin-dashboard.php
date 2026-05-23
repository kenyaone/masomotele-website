<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Finachub_Mpesa_Checkout_Admin_Dashboard
 *
 * Creates the admin dashboard pages with improved UI, persuasive upsells, updated menu title,
 * and adds a warning notice if PHP mail() function is disabled. Handles notice display correctly.
 */
class Finachub_Mpesa_Checkout_Admin_Dashboard {

    /**
     * The hook suffix for the main admin menu page.
     * @var string|false
     */
    private $main_hook_suffix = false;

    /**
     * The hook suffix for the settings submenu page.
     * @var string|false
     */
    private $settings_hook_suffix = false;

    /**
     * The hook suffix for the help submenu page.
     * @var string|false
     */
    private $help_hook_suffix = false;


    /**
     * Constructor. Hooks into WordPress admin actions.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        // Note: Enqueue is now hooked specifically in add_admin_menu

        // Add the check for the mail() function notice - hooked to the standard action
        add_action( 'admin_notices', [ $this, 'check_mail_function_notice' ] );
    }

    /**
     * Adds the admin menu items.
     */
    public function add_admin_menu() {
        // Store the hook suffix returned by add_menu_page
        $this->main_hook_suffix = add_menu_page(
            esc_html__( 'Finachub Lipa na Mpesa Dashboard', 'finachub-checkout-for-m-pesa' ), // Browser Title / Page Title
            esc_html__( 'Lipa na Mpesa', 'finachub-checkout-for-m-pesa' ), // Menu Title (Concise)
            'manage_options', // Capability required
            'finachub_mpesa_main', // Main slug
            [ $this, 'render_dashboard' ], // Callback for the main page
            'dashicons-money-alt', // Menu Icon
            54.5 // Position (below WooCommerce)
        );
        
        $this->settings_hook_suffix = add_submenu_page(
            'finachub_mpesa_main', // Parent slug
            esc_html__( 'Lipa na Mpesa Settings Guide', 'finachub-checkout-for-m-pesa' ), // Page title
            esc_html__( 'Settings Guide', 'finachub-checkout-for-m-pesa' ), // Menu title
            'manage_options', // Capability
            'finachub_mpesa_settings', // Menu slug
            [ $this, 'render_settings_page' ] // Callback
        );
        
        $this->help_hook_suffix = add_submenu_page(
            'finachub_mpesa_main', // Parent slug
            esc_html__( 'Lipa na Mpesa Help & Upgrade', 'finachub-checkout-for-m-pesa' ), // Page title
            esc_html__( 'Help & Upgrade', 'finachub-checkout-for-m-pesa' ), // Menu title
            'manage_options', // Capability
            'finachub_mpesa_help', // Menu slug
            [ $this, 'render_help_support_page' ] // Callback
        );

        // Hook asset enqueue specific to the pages where they are needed
        add_action( 'admin_print_styles-' . $this->main_hook_suffix, [ $this, 'enqueue_dashboard_assets' ] );
        add_action( 'admin_print_styles-' . $this->settings_hook_suffix, [ $this, 'enqueue_dashboard_assets' ] );
        add_action( 'admin_print_styles-' . $this->help_hook_suffix, [ $this, 'enqueue_dashboard_assets' ] );
    }

    /**
     * Checks if mail() function exists and displays an admin notice if not.
     * This function is now simplified and more reliable.
     */
    public function check_mail_function_notice() {
        // Exit if mail() exists
        if ( function_exists( 'mail' ) ) {
            return;
        }

        $show_notice = false;
        $screen      = get_current_screen();

        if ( ! $screen ) {
            return; // Cannot determine screen
        }

        // Create an array of our plugin's known screen IDs (hook suffixes) from the class properties.
        $plugin_pages = array_filter([
            $this->main_hook_suffix,
            $this->settings_hook_suffix,
            $this->help_hook_suffix,
        ]);

        // Check if the current screen ID is one of our plugin pages.
        if ( in_array( $screen->id, $plugin_pages, true ) ) {
            $show_notice = true;
        }

        // Also check if on the WooCommerce Payment Settings page, specifically the M-Pesa section.
        if ( ! $show_notice && $screen->id === 'woocommerce_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] === 'checkout' ) {
            // Show on the main checkout tab or specifically if our section is selected.
             if ( ! isset( $_GET['section'] ) || ( isset( $_GET['section'] ) && $_GET['section'] === 'finachub_mpesa_checkout' ) ) {
                 $show_notice = true;
             }
        }

        // Only add the notice content if we are on a relevant screen.
        if ( $show_notice ) {
            $smtp_plugin_url = esc_url( admin_url( 'plugin-install.php?s=smtp&tab=search&type=term' ) );
            $message         = sprintf(
                /* translators: 1: Opening strong tag, 2: Closing strong tag, 3: Opening link tag to SMTP search, 4: Closing link tag */
                esc_html__( '%1$sWarning:%2$s The PHP mail() function is disabled on your server. Finachub M-Pesa Checkout (and WooCommerce) relies on email functionality. This may cause errors during checkout completion or settings changes. Please install and configure an %3$sSMTP plugin%4$s to ensure emails are sent correctly.', 'finachub-checkout-for-m-pesa' ),
                '<strong>',
                '</strong>',
                '<a href="' . $smtp_plugin_url . '" target="_blank">',
                '</a>'
            );

            ?>
             <div class="notice notice-warning is-dismissible finachub-mail-notice">
                 <p><?php echo wp_kses_post( $message ); ?></p>
             </div>
             <?php
        }
    }


    /**
     * Enqueues admin dashboard assets (CSS & JS).
     * Hooked specifically to the plugin's admin pages.
     */
    public function enqueue_dashboard_assets() {
        // Enqueue Jost font
        wp_enqueue_style(
            'finachub_mpesa_jost_font_admin',
            'https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap',
            [],
            '1.0' // Font version
        );

        // Enqueue jQuery UI CSS (if needed)
        wp_enqueue_style(
            'finachub_mpesa_jquery_ui',
            FINACHUB_MPESA_PLUGIN_URL . 'assets/css/vendor/jquery-ui.css',
            [],
            '1.13.2'
        );

        // Enqueue the dedicated admin styles
        wp_enqueue_style(
            'finachub_mpesa_admin_styles',
            FINACHUB_MPESA_PLUGIN_URL . 'assets/css/admin-styles.css',
            [ 'dashicons', 'finachub_mpesa_jost_font_admin', 'finachub_mpesa_jquery_ui' ],
            FINACHUB_MPESA_VERSION
        );
    }


    /**
     * Renders the common admin page structure (header, content wrapper, footer).
     * Captures and displays admin notices *before* the custom wrapper.
     *
     * @param string $title      The title of the page.
     * @param string $content    The HTML content specific to the page being rendered.
     * @param string $page_class Optional CSS class for the main wrap div.
     */
    private function render_admin_page( $title, $content, $page_class = '' ) {

        // --- Start Output Buffering to Capture Notices ---
        ob_start();
        // Trigger the action hook where notices are usually displayed.
        do_action( 'all_admin_notices' );
        // Get the captured notices content
        $admin_notices = ob_get_clean();
        // --- End Output Buffering ---

        ?>
        <div class="wrap"> <?php // Use standard WP wrap for notices, then our custom one inside ?>
            <?php
            // --- Print Captured Admin Notices Here ---
            // This ensures notices appear *above* our custom header/wrapper.
            echo $admin_notices;
            ?>

            <?php // --- Start Custom Plugin Wrapper --- ?>
            <div class="finachub-admin-wrap <?php echo esc_attr( $page_class ); ?>">
                 <div class="finachub-admin-header">
                     <img src="<?php echo esc_url( FINACHUB_MPESA_PLUGIN_URL . 'assets/img/mpesa-logo.png' ); ?>" alt="M-Pesa Logo" class="finachub-header-logo">
                     <h1><?php echo esc_html( $title ); ?></h1>
                     <span class="finachub-version-tag"><?php esc_html_e( 'Free Version', 'finachub-checkout-for-m-pesa' ); ?></span>
                 </div>
                <div class="finachub-admin-content">
                    <?php echo wp_kses_post( $content ); // Output the specific page content passed in ?>
                </div>
                 <div class="finachub-admin-footer">
                     <?php printf(
                         /* translators: 1: Opening anchor tag for Pro upgrade, 2: Closing anchor tag */
                         esc_html__( 'Thank you for using Finachub M-Pesa Checkout. %1$sUpgrade to Pro%2$s for the full experience!', 'finachub-checkout-for-m-pesa' ),
                         '<a href="' . esc_url('https://finachub.com/product-category/plugins/mpesa/') . '" target="_blank" rel="noopener noreferrer">', // Updated Purchase URL
                         '</a>'
                     ); ?>
                 </div>
            </div> <?php // --- End Custom Plugin Wrapper --- ?>

        </div> <?php // --- End Standard WP Wrap --- ?>
        <?php
    }


    /**
     * Renders the main dashboard page, focusing on the Pro upsell.
     */
     public function render_dashboard() {
        ob_start();
        $pro_url = 'https://finachub.com/product-category/plugins/mpesa/';
        $demo_url = 'https://finachub.com/live-demo/';
        ?>
        <div class="finachub-dashboard-container-minimal">
            <div class="finachub-dashboard-header-minimal">
                <h2><?php esc_html_e( 'Unlock the Full Power of M-Pesa', 'finachub-checkout-for-m-pesa' ); ?></h2>
                <p><?php esc_html_e( 'You are using the free version. Upgrade to Pro to automate your workflow and access powerful features.', 'finachub-checkout-for-m-pesa' ); ?></p>
            </div>

            <div class="finachub-features-grid-minimal">
                <!-- Free Version Card -->
                <div class="finachub-feature-card-minimal free">
                    <h3><?php esc_html_e( 'Free Version', 'finachub-checkout-for-m-pesa' ); ?></h3>
                    <ul class="finachub-features-list-minimal">
                        <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'M-Pesa STK Push', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Automatic Order Completion', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Transaction Dashboard', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'CSV Data Export', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Priority Support', 'finachub-checkout-for-m-pesa' ); ?></li>
                    </ul>
                </div>

                <!-- Pro Version Card -->
                <div class="finachub-feature-card-minimal pro">
                    <h3><?php esc_html_e( 'Pro Version', 'finachub-checkout-for-m-pesa' ); ?></h3>
                    <ul class="finachub-features-list-minimal">
                        <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'M-Pesa STK Push', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Automatic Order Completion', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Transaction Dashboard', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'CSV Data Export', 'finachub-checkout-for-m-pesa' ); ?></li>
                        <li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Priority Support', 'finachub-checkout-for-m-pesa' ); ?></li>
                    </ul>
                    <div class="finachub-button-group-minimal">
                        <a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" class="finachub-button-minimal finachub-upgrade-button-minimal"><?php esc_html_e( 'Upgrade to Pro', 'finachub-checkout-for-m-pesa' ); ?></a>
                        <a href="<?php echo esc_url( $demo_url ); ?>" target="_blank" class="finachub-button-minimal finachub-demo-button-minimal"><?php esc_html_e( 'View Pro Demo', 'finachub-checkout-for-m-pesa' ); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        $this->render_admin_page( esc_html__( 'Lipa na Mpesa Dashboard', 'finachub-checkout-for-m-pesa' ), $content, 'finachub-dashboard-page' );
    }

    /**
     * Renders the settings guide page with improved clarity and style.
     */
    public function render_settings_page() {
        ob_start();
        $wc_settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=finachub_mpesa_checkout' );
        $safaricom_dev_portal_url = 'https://developer.safaricom.co.ke/user/me/apps';
        ?>
        <div class="finachub-settings-container-minimal">
            <div class="finachub-settings-header-minimal">
                <h2><?php esc_html_e( 'M-Pesa Configuration Guide', 'finachub-checkout-for-m-pesa' ); ?></h2>
                <p><?php esc_html_e( 'A clear, step-by-step guide to get you accepting M-Pesa payments quickly.', 'finachub-checkout-for-m-pesa' ); ?></p>
            </div>

            <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Step 1: Go to M-Pesa Settings', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php printf( esc_html__( 'All settings are located in the WooCommerce payments section. You can navigate there via the menu or use this direct link: %1$sGo to Settings Page%2$s.', 'finachub-checkout-for-m-pesa' ), '<a href="' . esc_url( $wc_settings_url ) . '" target="_blank">', '</a>' ); ?></p>
            </div>

            <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-toggle-on"></span>
                    <?php esc_html_e( 'Step 2: Enable the Gateway & Choose Environment', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php esc_html_e( 'First, check the "Enable M-Pesa Payment" box. Second, choose your "Environment".', 'finachub-checkout-for-m-pesa' ); ?></p>
                <div class="finachub-credentials-minimal">
                    <div class="finachub-credential-item-minimal">
                        <strong><?php esc_html_e( 'Sandbox vs. Live Environment', 'finachub-checkout-for-m-pesa' ); ?></strong>
                        <p><?php esc_html_e( 'Sandbox is a testing environment. Use it with test credentials to ensure everything works before accepting real payments. Select Live when you are ready to start accepting real payments with your official credentials.', 'finachub-checkout-for-m-pesa' ); ?></p>
                        <p class="finachub-sandbox-code" style="margin-top: 1rem !important;"><strong><?php esc_html_e( 'Important:', 'finachub-checkout-for-m-pesa' ); ?></strong> <?php esc_html_e( 'The Environment setting MUST match the type of credentials you enter in the next step.', 'finachub-checkout-for-m-pesa' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php esc_html_e( 'Step 3: Enter API Credentials', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php printf( esc_html__( 'Copy and paste these from the %1$sSafaricom Developer Portal%2$s. After logging in, go to the "My Apps" section to view your app and its credentials.', 'finachub-checkout-for-m-pesa' ), '<a href="' . esc_url( $safaricom_dev_portal_url ) . '" target="_blank">', '</a>' ); ?></p>
                
                <div class="finachub-credentials-minimal">
                    <div class="finachub-credential-item-minimal">
                        <strong><?php esc_html_e( 'Consumer Key & Secret', 'finachub-checkout-for-m-pesa' ); ?></strong>
                        <p><?php esc_html_e( 'These are the primary identifiers for your application.', 'finachub-checkout-for-m-pesa' ); ?></p>
                    </div>
                    <div class="finachub-credential-item-minimal">
                        <strong><?php esc_html_e( 'Short Code', 'finachub-checkout-for-m-pesa' ); ?></strong>
                        <p><?php esc_html_e( 'This is your business\'s PayBill or Till Number.', 'finachub-checkout-for-m-pesa' ); ?></p>
                        <p class="finachub-sandbox-code"><strong><?php esc_html_e( 'Sandbox Test Value:', 'finachub-checkout-for-m-pesa' ); ?></strong> <code>174379</code></p>
                    </div>
                    <div class="finachub-credential-item-minimal">
                        <strong><?php esc_html_e( 'Passkey', 'finachub-checkout-for-m-pesa' ); ?></strong>
                        <p><?php esc_html_e( 'This is the Lipa Na M-Pesa Online Passkey associated with your Short Code.', 'finachub-checkout-for-m-pesa' ); ?></p>
                        <p class="finachub-sandbox-code"><strong><?php esc_html_e( 'Sandbox Test Value:', 'finachub-checkout-for-m-pesa' ); ?></strong> <code>bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919</code></p>
                    </div>
                </div>
            </div>

            <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php esc_html_e( 'Step 4: Confirm Callback URL', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php esc_html_e( 'This is the URL that M-Pesa uses to send a success or failure status back to your website after a customer pays. The plugin fills this for you automatically. Your site must be using HTTPS for this to work.', 'finachub-checkout-for-m-pesa' ); ?></p>
                <p><small><em><?php esc_html_e( 'Note: Automatic order status updates based on this callback require the Pro version.', 'finachub-checkout-for-m-pesa' ); ?></em></small></p>
            </div>

             <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e( 'Step 5: Save and Test!', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php esc_html_e( 'Click the "Save changes" button. We strongly recommend doing a test payment using the Sandbox credentials and a test phone number to ensure everything is configured correctly before going live.', 'finachub-checkout-for-m-pesa' ); ?></p>
            </div>

        </div>
        <?php
        $content = ob_get_clean();
        $this->render_admin_page( esc_html__( 'Lipa na Mpesa - Settings Guide', 'finachub-checkout-for-m-pesa' ), $content, 'finachub-settings-page' );
    }
     public function render_help_support_page() {
        ob_start();
        $pro_url = 'https://finachub.com/product-category/plugins/mpesa/';
        $docs_url = 'https://finachub.com/mpesa-checkout-docs/';
        $contact_url = 'https://finachub.com/contact-us/';
        ?>
        <div class="finachub-settings-container-minimal">
            <div class="finachub-settings-header-minimal">
                <h2><?php esc_html_e( 'Help & Support', 'finachub-checkout-for-m-pesa' ); ?></h2>
                <p><?php esc_html_e( 'Find answers to common questions and learn how to get the most out of the plugin.', 'finachub-checkout-for-m-pesa' ); ?></p>
            </div>

            <!-- Troubleshooting Card -->
            <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e( 'Troubleshooting Guide', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <div class="finachub-help-section">
                    <strong><?php esc_html_e( 'Problem: STK Push Not Received by Customer', 'finachub-checkout-for-m-pesa' ); ?></strong>
                    <p><?php esc_html_e( 'This is the most common issue and is almost always caused by a configuration problem. Follow this checklist:', 'finachub-checkout-for-m-pesa' ); ?></p>
                    <ul class="finachub-help-checklist">
                        <li><strong>Credential Mismatch:</strong> Ensure your Consumer Key, Secret, Short Code, and Passkey are copied correctly from the Safaricom portal.</li>
                        <li><strong>Incorrect Environment:</strong> The "Environment" setting (Sandbox or Live) in the plugin MUST match the credentials you are using. Sandbox keys will not work in a Live environment, and vice-versa.</li>
                        <li><strong>No SSL Certificate:</strong> Your website MUST be using HTTPS. M-Pesa's servers will not communicate with a non-secure (HTTP) site.</li>
                        <li><strong>Plugin or Theme Conflict:</strong> Temporarily switch to a default WordPress theme (like Twenty Twenty-Four) and disable other plugins to see if the issue persists.</li>
                    </ul>
                </div>
                <div class="finachub-help-section">
                    <strong><?php esc_html_e( 'Problem: Orders Are Stuck on "Pending Payment"', 'finachub-checkout-for-m-pesa' ); ?></strong>
                    <p><?php esc_html_e( 'This is the intended behavior for the free version. The payment is processed, but the plugin does not automatically update the order status in WooCommerce. You must manually verify that you received the payment in your M-Pesa account and then change the order status from "Pending payment" to "Processing" or "Completed".', 'finachub-checkout-for-m-pesa' ); ?></p>
                    <p><em><?php esc_html_e( 'The Pro version fully automates this by using the M-Pesa callback to update the order status instantly.', 'finachub-checkout-for-m-pesa' ); ?></em></p>
                </div>
            </div>

            <!-- Upgrade to Pro Card -->
            <div class="finachub-settings-card-minimal finachub-pro-card-minimal">
                <h3>
                    <span class="dashicons dashicons-unlock"></span>
                    <?php esc_html_e( 'Automate Your Business with M-Pesa Pro', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php esc_html_e( 'Stop wasting time on manual tasks. The Pro version provides powerful features to enhance efficiency, gain insights, and provide a smoother customer experience.', 'finachub-checkout-for-m-pesa' ); ?></p>
                <ul class="finachub-pro-features-list">
                    <li><span class="dashicons dashicons-yes"></span> <strong>Automatic Order Completion:</strong> Save time and reduce errors by letting the plugin update order statuses for you.</li>
                    <li><span class="dashicons dashicons-yes"></span> <strong>Live Transaction Dashboard:</strong> View and search all your M-Pesa transactions directly within WordPress.</li>
                    <li><span class="dashicons dashicons-yes"></span> <strong>CSV Data Export:</strong> Easily export transaction data for your accounting and analysis needs.</li>
                    <li><span class="dashicons dashicons-yes"></span> <strong>Priority Email Support:</strong> Get faster, dedicated help from our support team.</li>
                </ul>
                <div class="finachub-button-group-minimal">
                    <a href="<?php echo esc_url( $pro_url ); ?>" class="finachub-button-minimal finachub-upgrade-button-minimal" target="_blank"><?php esc_html_e( 'Learn More & Upgrade to Pro', 'finachub-checkout-for-m-pesa' ); ?></a>
                    <a href="<?php echo esc_url( $demo_url ); ?>" class="finachub-button-minimal finachub-demo-button-minimal" target="_blank"><?php esc_html_e( 'View Pro Demo', 'finachub-checkout-for-m-pesa' ); ?></a>
                </div>
            </div>

            <!-- Contact & Docs Card -->
            <div class="finachub-settings-card-minimal">
                <h3>
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php esc_html_e( 'Documentation & Direct Support', 'finachub-checkout-for-m-pesa' ); ?>
                </h3>
                <p><?php esc_html_e( 'If you can\'t find the answer in the guide above, these resources are here to help.', 'finachub-checkout-for-m-pesa' ); ?></p>
                <div class="finachub-help-links">
                    <a href="<?php echo esc_url( $docs_url ); ?>" class="finachub-help-link-button" target="_blank">
                        <span class="dashicons dashicons-book-alt"></span>
                        <?php esc_html_e( 'Read the Full Documentation', 'finachub-checkout-for-m-pesa' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $contact_url ); ?>" class="finachub-help-link-button" target="_blank">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e( 'Contact Our Support Team', 'finachub-checkout-for-m-pesa' ); ?>
                    </a>
                </div>
            </div>

        </div>
        <?php
        $content = ob_get_clean();
        $this->render_admin_page( esc_html__( 'Lipa na Mpesa - Help & Upgrade', 'finachub-checkout-for-m-pesa' ), $content, 'finachub-help-page' );
    }

} // End Class