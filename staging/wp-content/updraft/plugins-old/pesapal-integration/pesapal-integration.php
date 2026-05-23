<?php
/**
 * Plugin Name: Pesapal Payment Gateway
 * Plugin URI: https://masomoteletraining.co.ke
 * Description: Complete Pesapal payment integration for WordPress
 * Version: 2.1.0
 * Author: Masomo Teletraining
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

// ==================== CONSTANTS ====================
define('PESAPAL_VERSION', '2.1.0');
define('PESAPAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PESAPAL_PLUGIN_URL', plugin_dir_url(__FILE__));

// ==================== ENQUEUE SCRIPTS ====================
add_action('wp_enqueue_scripts', 'pesapal_enqueue_scripts');

function pesapal_enqueue_scripts() {
    wp_enqueue_script('jquery');
}

// ==================== ACTIVATION HOOK ====================
register_activation_hook(__FILE__, 'pesapal_activate_plugin');

function pesapal_activate_plugin() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pesapal_transactions';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_reference varchar(100) NOT NULL,
        tracking_id varchar(100) DEFAULT NULL,
        amount decimal(10,2) NOT NULL,
        currency varchar(3) DEFAULT 'KES',
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        customer_phone varchar(50) NOT NULL,
        description text,
        payment_status varchar(20) DEFAULT 'PENDING',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY order_reference (order_reference),
        KEY tracking_id (tracking_id),
        KEY payment_status (payment_status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Create logs directory
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/pesapal-logs/';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        
        // Protect log directory
        $htaccess = $log_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'Deny from all');
        }
    }
    
    // Set default options
    if (!get_option('pesapal_environment')) {
        add_option('pesapal_environment', 'demo');
    }
}

// ==================== ADMIN MENU ====================
add_action('admin_menu', 'pesapal_create_menu');

function pesapal_create_menu() {
    add_menu_page(
        'Pesapal Settings',
        'Pesapal',
        'manage_options',
        'pesapal-settings',
        'pesapal_settings_page',
        'dashicons-money-alt',
        58
    );
    
    add_submenu_page(
        'pesapal-settings',
        'Transactions',
        'Transactions',
        'manage_options',
        'pesapal-transactions',
        'pesapal_transactions_page'
    );
    
    add_submenu_page(
        'pesapal-settings',
        'Logs',
        'Logs',
        'manage_options',
        'pesapal-logs',
        'pesapal_logs_page'
    );
}

// ==================== SETTINGS PAGE ====================
function pesapal_settings_page() {
    // Handle form submission
    if (isset($_POST['pesapal_settings_submit'])) {
        if (!isset($_POST['pesapal_settings_nonce']) || 
            !wp_verify_nonce($_POST['pesapal_settings_nonce'], 'pesapal_save_settings')) {
            wp_die('Security check failed');
        }
        
        $consumer_key = isset($_POST['pesapal_consumer_key']) ? sanitize_text_field($_POST['pesapal_consumer_key']) : '';
        $consumer_secret = isset($_POST['pesapal_consumer_secret']) ? sanitize_text_field($_POST['pesapal_consumer_secret']) : '';
        $environment = isset($_POST['pesapal_environment']) ? sanitize_text_field($_POST['pesapal_environment']) : 'demo';
        
        update_option('pesapal_consumer_key', $consumer_key);
        update_option('pesapal_consumer_secret', $consumer_secret);
        update_option('pesapal_environment', $environment);
        
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully!</strong></p></div>';
    }
    
    // Get current values
    $consumer_key = get_option('pesapal_consumer_key', '');
    $consumer_secret = get_option('pesapal_consumer_secret', '');
    $environment = get_option('pesapal_environment', 'demo');
    ?>
    
    <div class="wrap">
        <h1>🔐 Pesapal Payment Settings</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
            <h3>📋 Setup Instructions:</h3>
            <ol>
                <li>Get your credentials from <a href="https://demo.pesapal.com" target="_blank">Pesapal Demo</a> (for testing) or <a href="https://www.pesapal.com" target="_blank">Pesapal Live</a></li>
                <li>Enter your Consumer Key and Consumer Secret below</li>
                <li>Select the appropriate environment (Demo for testing, Live for production)</li>
                <li>Click "Save Settings"</li>
                <li>Use the shortcode <code>[pesapal_form amount="1000"]</code> on any page</li>
            </ol>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('pesapal_save_settings', 'pesapal_settings_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="pesapal_consumer_key">Consumer Key *</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="pesapal_consumer_key" 
                                   name="pesapal_consumer_key" 
                                   value="<?php echo esc_attr($consumer_key); ?>" 
                                   class="regular-text" 
                                   placeholder="INqHwbeaZtJVpT8Si2tEsh0gey918pPu"
                                   required>
                            <p class="description">Get this from your Pesapal dashboard</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pesapal_consumer_secret">Consumer Secret *</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="pesapal_consumer_secret" 
                                   name="pesapal_consumer_secret" 
                                   value="<?php echo esc_attr($consumer_secret); ?>" 
                                   class="regular-text" 
                                   placeholder="9ehHexx0U5KurBiQLpsXTXhfg9M="
                                   required>
                            <p class="description">Get this from your Pesapal dashboard</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pesapal_environment">Environment</label>
                        </th>
                        <td>
                            <select id="pesapal_environment" name="pesapal_environment">
                                <option value="demo" <?php selected($environment, 'demo'); ?>>🧪 Demo/Testing (Sandbox)</option>
                                <option value="live" <?php selected($environment, 'live'); ?>>🚀 Live (Production)</option>
                            </select>
                            <p class="description">Use Demo for testing, Live for real payments</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="pesapal_settings_submit" 
                       id="submit" 
                       class="button button-primary" 
                       value="Save Settings">
            </p>
        </form>
        
        <hr>
        
        <h2>🔗 Integration URLs</h2>
        <table class="form-table">
            <tr>
                <th>IPN URL:</th>
                <td>
                    <code style="background: #f0f0f0; padding: 5px 10px;"><?php echo home_url('/?pesapal_ipn=1'); ?></code>
                    <p class="description">Register this in your Pesapal dashboard for instant notifications</p>
                </td>
            </tr>
            <tr>
                <th>Callback URL:</th>
                <td>
                    <code style="background: #f0f0f0; padding: 5px 10px;"><?php echo home_url('/?pesapal_callback=1'); ?></code>
                    <p class="description">Where customers return after payment</p>
                </td>
            </tr>
        </table>
        
        <hr>
        
        <h2>📝 Shortcode Usage</h2>
        <p>Add this shortcode to any page or post:</p>
        <code style="background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;">
            [pesapal_form amount="1000" description="Product Purchase"]
        </code>
        
        <h3>Available Parameters:</h3>
        <ul>
            <li><code>amount</code> - Payment amount (default: 1000)</li>
            <li><code>description</code> - Payment description (default: "Payment")</li>
            <li><code>currency</code> - Currency code (default: "KES")</li>
        </ul>
    </div>
    <?php
}

// ==================== TRANSACTIONS PAGE ====================
function pesapal_transactions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pesapal_transactions';
    
    // Handle status update
    if (isset($_GET['action']) && $_GET['action'] === 'check_status' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        if ($transaction && $transaction->tracking_id) {
            $status = pesapal_query_payment_status($transaction->order_reference, $transaction->tracking_id);
            $wpdb->update(
                $table_name,
                array('payment_status' => $status),
                array('id' => $id)
            );
            echo '<div class="notice notice-success"><p>Status updated to: ' . esc_html($status) . '</p></div>';
        }
    }
    
    $transactions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50");
    ?>
    
    <div class="wrap">
        <h1>💳 Payment Transactions</h1>
        
        <?php if (empty($transactions)): ?>
            <div style="background: #fff; padding: 40px; text-align: center; margin: 20px 0;">
                <p style="font-size: 18px; color: #666;">📭 No transactions yet</p>
                <p>Payments will appear here once customers make transactions</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td><?php echo esc_html($txn->id); ?></td>
                        <td>
                            <strong><?php echo esc_html($txn->order_reference); ?></strong>
                            <?php if ($txn->tracking_id): ?>
                                <br><small style="color: #666;">Tracking: <?php echo esc_html($txn->tracking_id); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($txn->customer_name); ?></strong><br>
                            <small><?php echo esc_html($txn->customer_email); ?></small><br>
                            <small><?php echo esc_html($txn->customer_phone); ?></small>
                        </td>
                        <td>
                            <strong><?php echo esc_html($txn->currency . ' ' . number_format($txn->amount, 2)); ?></strong>
                        </td>
                        <td>
                            <?php
                            $status_colors = array(
                                'COMPLETED' => '#28a745',
                                'PENDING' => '#ffc107',
                                'FAILED' => '#dc3545',
                                'INVALID' => '#dc3545'
                            );
                            $color = isset($status_colors[$txn->payment_status]) ? $status_colors[$txn->payment_status] : '#6c757d';
                            ?>
                            <span style="background: <?php echo $color; ?>; color: white; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                                <?php echo esc_html($txn->payment_status); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('M d, Y H:i', strtotime($txn->created_at))); ?></td>
                        <td>
                            <?php if ($txn->tracking_id): ?>
                                <a href="?page=pesapal-transactions&action=check_status&id=<?php echo $txn->id; ?>" 
                                   class="button button-small">🔄 Check Status</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// ==================== LOGS PAGE ====================
function pesapal_logs_page() {
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/pesapal-logs/';
    $log_file = $log_dir . 'pesapal-' . date('Y-m-d') . '.log';
    
    $logs = '';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $logs = esc_html($logs);
    }
    ?>
    
    <div class="wrap">
        <h1>📄 Pesapal Logs</h1>
        <p>Today's log file: <code><?php echo basename($log_file); ?></code></p>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0;">
            <?php if (empty($logs)): ?>
                <p>No logs for today</p>
            <?php else: ?>
                <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; max-height: 600px;"><?php echo $logs; ?></pre>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ==================== PAYMENT FORM SHORTCODE ====================
add_shortcode('pesapal_form', 'pesapal_form_shortcode');

function pesapal_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'amount' => '1000',
        'description' => 'Payment',
        'currency' => 'KES'
    ), $atts);
    
    // Ensure jQuery is loaded
    wp_enqueue_script('jquery');
    
    // Get AJAX URL
    $ajax_url = admin_url('admin-ajax.php');
    
    ob_start();
    ?>
    
    <div class="pesapal-form-container">
        <form id="pesapal-payment-form" method="post">
            <?php wp_nonce_field('pesapal_payment_nonce', 'pesapal_nonce'); ?>
            
            <div class="pesapal-form-group">
               <div class="form-group">
    <label>Amount (KES) *</label>
    <input type="number" name="amount" 
           value="<?php echo esc_attr($atts['amount']); ?>" 
           min="1" 
           step="0.01" 
           required 
           placeholder="Enter amount">
    </div>
            </div>
            
            <div class="pesapal-form-group">
                <label>Full Name *</label>
                <input type="text" 
                       name="customer_name" 
                       placeholder="John Doe" 
                       required>
            </div>
            
            <div class="pesapal-form-group">
                <label>Email Address *</label>
                <input type="email" 
                       name="customer_email" 
                       placeholder="john@example.com" 
                       required>
            </div>
            
            <div class="pesapal-form-group">
                <label>Phone Number *</label>
                <input type="tel" 
                       name="customer_phone" 
                       placeholder="254712345678" 
                       pattern="[0-9]{10,15}"
                       required>
            </div>
            
            <input type="hidden" name="description" value="<?php echo esc_attr($atts['description']); ?>">
            <input type="hidden" name="currency" value="<?php echo esc_attr($atts['currency']); ?>">
            <input type="hidden" name="action" value="pesapal_process_payment">
            
            <button type="submit" class="pesapal-submit-btn">
                <span class="pesapal-btn-text">💳 Pay with Pesapal</span>
                <span class="pesapal-btn-loading" style="display: none;">⏳ Processing...</span>
            </button>
        </form>
        
        <div id="pesapal-message" style="display: none;"></div>
    </div>
    
    <style>
        .pesapal-form-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .pesapal-form-group {
            margin-bottom: 20px;
        }
        .pesapal-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .pesapal-form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .pesapal-form-group input:focus {
            outline: none;
            border-color: #2271b1;
        }
        .pesapal-submit-btn {
            width: 100%;
            padding: 15px;
            background: #2271b1;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .pesapal-submit-btn:hover {
            background: #135e96;
        }
        .pesapal-submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        #pesapal-message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
        }
        #pesapal-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        #pesapal-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    
    <script type="text/javascript">
    (function($) {
        'use strict';
        
        $(document).ready(function() {
            console.log('✅ Pesapal form script loaded');
            console.log('🔗 AJAX URL:', '<?php echo $ajax_url; ?>');
            console.log('🔧 jQuery version:', $.fn.jquery);
            
            $('#pesapal-payment-form').on('submit', function(e) {
                e.preventDefault();
                console.log('📝 Form submitted');
                
                var $form = $(this);
                var $btn = $form.find('.pesapal-submit-btn');
                var $btnText = $form.find('.pesapal-btn-text');
                var $btnLoading = $form.find('.pesapal-btn-loading');
                var $message = $('#pesapal-message');
                
                var formData = $form.serialize();
                console.log('📦 Form data:', formData);
                
                // Show loading state
                $btn.prop('disabled', true);
                $btnText.hide();
                $btnLoading.show();
                $message.hide();
                
                $.ajax({
                    url: '<?php echo $ajax_url; ?>',
                    type: 'POST',
                    data: formData,
                    timeout: 30000,
                    success: function(response) {
                        console.log('✅ AJAX Success:', response);
                        
                        if (response.success && response.data && response.data.redirect_url) {
                            console.log('🔄 Redirecting to:', response.data.redirect_url);
                            $message.removeClass('error').addClass('success')
                                   .html('✅ Redirecting to payment gateway...')
                                   .show();
                            
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 500);
                        } else {
                            console.error('❌ Payment failed:', response);
                            var errorMsg = 'Payment initialization failed';
                            if (response.data && response.data.message) {
                                errorMsg = response.data.message;
                            }
                            
                            $message.removeClass('success').addClass('error')
                                   .html('❌ ' + errorMsg)
                                   .show();
                            
                            $btn.prop('disabled', false);
                            $btnText.show();
                            $btnLoading.hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ AJAX Error:', status, error);
                        console.error('📄 Response:', xhr.responseText);
                        console.error('📊 Status:', xhr.status);
                        
                        var errorMsg = 'Connection error';
                        if (xhr.status === 404) {
                            errorMsg = 'Server endpoint not found (404). Please check plugin settings.';
                        } else if (xhr.status === 500) {
                            errorMsg = 'Server error (500). Please check error logs.';
                        } else if (status === 'timeout') {
                            errorMsg = 'Request timed out. Please try again.';
                        } else if (error) {
                            errorMsg = error;
                        }
                        
                        $message.removeClass('success').addClass('error')
                               .html('❌ ' + errorMsg)
                               .show();
                        
                        $btn.prop('disabled', false);
                        $btnText.show();
                        $btnLoading.hide();
                    }
                });
            });
        });
    })(jQuery);
    </script>
    
    <?php
    return ob_get_clean();
}

// ==================== AJAX PAYMENT PROCESSOR ====================
add_action('wp_ajax_pesapal_process_payment', 'pesapal_ajax_process_payment');
add_action('wp_ajax_nopriv_pesapal_process_payment', 'pesapal_ajax_process_payment');

function pesapal_ajax_process_payment() {
    pesapal_log('🔔 AJAX handler called');
    
    // Verify nonce
    if (!isset($_POST['pesapal_nonce']) || !wp_verify_nonce($_POST['pesapal_nonce'], 'pesapal_payment_nonce')) {
        pesapal_log('❌ Nonce verification failed');
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    pesapal_log('✅ Nonce verified');
    
    // Validate inputs
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
    $email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
    $phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : 'Payment';
    $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'KES';
    
    pesapal_log("📝 Received: Name=$name, Email=$email, Amount=$amount");
    
    if ($amount <= 0) {
        pesapal_log('❌ Invalid amount');
        wp_send_json_error(array('message' => 'Invalid amount'));
    }
    
    if (empty($name) || empty($email) || empty($phone)) {
        pesapal_log('❌ Missing required fields');
        wp_send_json_error(array('message' => 'All fields are required'));
    }
    
    if (!is_email($email)) {
        pesapal_log('❌ Invalid email');
        wp_send_json_error(array('message' => 'Invalid email address'));
    }
    
    // Generate unique reference
    $reference = 'ORD_' . time() . '_' . wp_rand(1000, 9999);
    pesapal_log("🔢 Generated reference: $reference");
    
    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'pesapal_transactions';
    
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'order_reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'description' => $description,
            'payment_status' => 'PENDING'
        ),
        array('%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if (!$inserted) {
        pesapal_log('❌ Database error: ' . $wpdb->last_error);
        wp_send_json_error(array('message' => 'Failed to create transaction record'));
    }
    
    pesapal_log('✅ Transaction saved to database');
    
    // Generate Pesapal payment URL
    $payment_url = pesapal_generate_payment_url($reference, $amount, $currency, $description, $name, $email, $phone);
    
    if (!$payment_url) {
        pesapal_log('❌ Failed to generate payment URL');
        wp_send_json_error(array('message' => 'Failed to generate payment URL. Check your Pesapal credentials.'));
    }
    
    pesapal_log("✅ Payment URL generated: $payment_url");
    pesapal_log("🚀 Payment initiated: $reference - $name - $email - $currency $amount");
    
    wp_send_json_success(array(
        'redirect_url' => $payment_url,
        'reference' => $reference
    ));
}

// ==================== GENERATE PESAPAL PAYMENT URL ====================
function pesapal_generate_payment_url($reference, $amount, $currency, $description, $name, $email, $phone) {
    $consumer_key = get_option('pesapal_consumer_key', '');
    $consumer_secret = get_option('pesapal_consumer_secret', '');
    $environment = get_option('pesapal_environment', 'demo');
    
    if (empty($consumer_key) || empty($consumer_secret)) {
        pesapal_log('❌ Error: Pesapal credentials not configured');
        return false;
    }
    
    pesapal_log("🔑 Using environment: $environment");
    
    // Set API endpoint
    $api_url = ($environment === 'live') 
        ? 'https://www.pesapal.com/API/PostPesapalDirectOrderV4'
        : 'https://demo.pesapal.com/API/PostPesapalDirectOrderV4';
    
    $callback_url = home_url('/?pesapal_callback=1');
    
    pesapal_log("🔗 API URL: $api_url");
    pesapal_log("🔗 Callback URL: $callback_url");
    
    // Split name
    $name_parts = explode(' ', trim($name), 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Build XML request
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<PesapalDirectOrderInfo ';
    $xml .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
    $xml .= 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ';
    $xml .= 'Amount="' . esc_attr($amount) . '" ';
    $xml .= 'Description="' . esc_attr($description) . '" ';
    $xml .= 'Type="MERCHANT" ';
    $xml .= 'Reference="' . esc_attr($reference) . '" ';
    $xml .= 'FirstName="' . esc_attr($first_name) . '" ';
    $xml .= 'LastName="' . esc_attr($last_name) . '" ';
    $xml .= 'Email="' . esc_attr($email) . '" ';
    $xml .= 'PhoneNumber="' . esc_attr($phone) . '" ';
    $xml .= 'xmlns="http://www.pesapal.com" />';
    
    // OAuth parameters
    $oauth_params = array(
        'oauth_callback' => $callback_url,
        'oauth_consumer_key' => $consumer_key,
        'oauth_nonce' => md5(uniqid(wp_rand(), true)),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => time(),
        'oauth_version' => '1.0',
        'pesapal_request_data' => $xml
    );
    
    // Generate signature
    $signature = pesapal_generate_signature('GET', $api_url, $oauth_params, $consumer_secret);
    $oauth_params['oauth_signature'] = $signature;
    
    // Build final URL
    $payment_url = $api_url . '?' . http_build_query($oauth_params);
    
    return $payment_url;
}

// ==================== GENERATE OAUTH SIGNATURE ====================
function pesapal_generate_signature($method, $url, $params, $consumer_secret) {
    // Sort parameters
    ksort($params);
    
    // Build parameter string
    $pairs = array();
    foreach ($params as $key => $value) {
        $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
    }
    $param_string = implode('&', $pairs);
    
    // Build signature base string
    $base_string = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($param_string);
    
    // Build signing key
    $signing_key = rawurlencode($consumer_secret) . '&';
    
    // Generate signature
    $signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
    
    return $signature;
}

// ==================== IPN HANDLER ====================
add_action('init', 'pesapal_handle_ipn');

function pesapal_handle_ipn() {
    if (!isset($_GET['pesapal_ipn']) || $_GET['pesapal_ipn'] != '1') {
        return;
    }
    
    $tracking_id = isset($_GET['pesapal_transaction_tracking_id']) ? sanitize_text_field($_GET['pesapal_transaction_tracking_id']) : '';
    $reference = isset($_GET['pesapal_merchant_reference']) ? sanitize_text_field($_GET['pesapal_merchant_reference']) : '';
    
    pesapal_log("📥 IPN received: Reference=$reference, Tracking=$tracking_id");
    
    if (empty($tracking_id) || empty($reference)) {
        pesapal_log('❌ IPN error: Missing parameters');
        exit('Invalid IPN');
    }
    
    // Query payment status
    $status = pesapal_query_payment_status($reference, $tracking_id);
    
    // Update database
    global $wpdb;
    $table_name = $wpdb->prefix . 'pesapal_transactions';
    
    $updated = $wpdb->update(
        $table_name,
        array(
            'payment_status' => $status,
            'tracking_id' => $tracking_id
        ),
        array('order_reference' => $reference),
        array('%s', '%s'),
        array('%s')
    );
    
    pesapal_log("✅ IPN processed: Status=$status, Updated=$updated");
    
    // Send notification if completed
    if ($status === 'COMPLETED') {
        pesapal_send_notification_email($reference);
    }
    
    // Respond to Pesapal
    $response = "pesapal_notification_type=CHANGE";
    $response .= "&pesapal_transaction_tracking_id=" . urlencode($tracking_id);
    $response .= "&pesapal_merchant_reference=" . urlencode($reference);
    
    echo $response;
    exit;
}

// ==================== CALLBACK HANDLER ====================
add_action('init', 'pesapal_handle_callback');

function pesapal_handle_callback() {
    if (!isset($_GET['pesapal_callback']) || $_GET['pesapal_callback'] != '1') {
        return;
    }
    
    $tracking_id = isset($_GET['pesapal_transaction_tracking_id']) ? sanitize_text_field($_GET['pesapal_transaction_tracking_id']) : '';
    $reference = isset($_GET['pesapal_merchant_reference']) ? sanitize_text_field($_GET['pesapal_merchant_reference']) : '';
    
    pesapal_log("🔙 Callback received: Reference=$reference, Tracking=$tracking_id");
    
    if (empty($reference)) {
        wp_redirect(home_url('/?payment=error&message=invalid'));
        exit;
    }
    
    // Query payment status
    if (!empty($tracking_id)) {
        $status = pesapal_query_payment_status($reference, $tracking_id);
        
        // Update database
        global $wpdb;
        $table_name = $wpdb->prefix . 'pesapal_transactions';
        
        $wpdb->update(
            $table_name,
            array(
                'payment_status' => $status,
                'tracking_id' => $tracking_id
            ),
            array('order_reference' => $reference),
            array('%s', '%s'),
            array('%s')
        );
        
        pesapal_log("✅ Callback processed: Status=$status");
        
        // Redirect based on status
        if ($status === 'COMPLETED') {
            wp_redirect(home_url('/?payment=success&ref=' . urlencode($reference)));
        } elseif ($status === 'PENDING') {
            wp_redirect(home_url('/?payment=pending&ref=' . urlencode($reference)));
        } else {
            wp_redirect(home_url('/?payment=failed&ref=' . urlencode($reference)));
        }
    } else {
        wp_redirect(home_url('/?payment=pending&ref=' . urlencode($reference)));
    }
    
    exit;
}

// ==================== QUERY PAYMENT STATUS ====================
function pesapal_query_payment_status($reference, $tracking_id) {
    $consumer_key = get_option('pesapal_consumer_key', '');
    $consumer_secret = get_option('pesapal_consumer_secret', '');
    $environment = get_option('pesapal_environment', 'demo');
    
    $api_url = ($environment === 'live')
        ? 'https://www.pesapal.com/API/QueryPaymentStatus'
        : 'https://demo.pesapal.com/API/QueryPaymentStatus';
    
    $query_url = $api_url . '?' . http_build_query(array(
        'pesapal_merchant_reference' => $reference,
        'pesapal_transaction_tracking_id' => $tracking_id
    ));
    
    $response = wp_remote_get($query_url, array('timeout' => 30));
    
    if (is_wp_error($response)) {
        pesapal_log('❌ Query error: ' . $response->get_error_message());
        return 'PENDING';
    }
    
    $body = wp_remote_retrieve_body($response);
    parse_str($body, $data);
    
    $status = isset($data['pesapal_response_data']) ? strtoupper($data['pesapal_response_data']) : 'PENDING';
    
    pesapal_log("📊 Status query: $reference = $status");
    
    return $status;
}

// ==================== SEND EMAIL NOTIFICATION ====================
function pesapal_send_notification_email($reference) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pesapal_transactions';
    
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE order_reference = %s",
        $reference
    ));
    
    if (!$transaction) {
        return false;
    }
    
    $to = $transaction->customer_email;
    $subject = 'Payment Confirmation - ' . $reference;
    
    $message = "Dear " . $transaction->customer_name . ",\n\n";
    $message .= "Your payment has been received successfully!\n\n";
    $message .= "Transaction Details:\n";
    $message .= "Reference: " . $reference . "\n";
    $message .= "Amount: " . $transaction->currency . ' ' . number_format($transaction->amount, 2) . "\n";
    $message .= "Status: Completed\n";
    $message .= "Date: " . date('F j, Y, g:i a') . "\n\n";
    $message .= "Thank you for your payment!\n\n";
    $message .= "Best regards,\n";
    $message .= get_bloginfo('name');
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    $sent = wp_mail($to, $subject, $message, $headers);
    
    pesapal_log("📧 Email sent to $to: " . ($sent ? 'Success' : 'Failed'));
    
    return $sent;
}

// ==================== LOGGING FUNCTION ====================
function pesapal_log($message) {
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/pesapal-logs/';
    
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    $log_file = $log_dir . 'pesapal-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// ==================== DISPLAY PAYMENT STATUS ====================
add_action('wp_footer', 'pesapal_display_payment_status');

function pesapal_display_payment_status() {
    if (!isset($_GET['payment'])) {
        return;
    }
    
    $status = sanitize_text_field($_GET['payment']);
    $reference = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
    
    $messages = array(
        'success' => array(
            'icon' => '✅',
            'title' => 'Payment Successful!',
            'message' => 'Your payment has been completed successfully.',
            'color' => '#28a745'
        ),
        'pending' => array(
            'icon' => '⏳',
            'title' => 'Payment Pending',
            'message' => 'Your payment is being processed. Please check back shortly.',
            'color' => '#ffc107'
        ),
        'failed' => array(
            'icon' => '❌',
            'title' => 'Payment Failed',
            'message' => 'Your payment could not be completed. Please try again.',
            'color' => '#dc3545'
        ),
        'error' => array(
            'icon' => '⚠️',
            'title' => 'Error',
            'message' => 'An error occurred during payment processing.',
            'color' => '#dc3545'
        )
    );
    
    if (!isset($messages[$status])) {
        return;
    }
    
    $msg = $messages[$status];
    ?>
    <div id="pesapal-status-modal" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
    ">
        <div style="
            background: white;
            padding: 40px;
            border-radius: 10px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        ">
            <div style="font-size: 60px; margin-bottom: 20px;">
                <?php echo $msg['icon']; ?>
            </div>
            <h2 style="color: <?php echo $msg['color']; ?>; margin: 0 0 15px 0;">
                <?php echo $msg['title']; ?>
            </h2>
            <p style="font-size: 16px; color: #666; margin: 0 0 10px 0;">
                <?php echo $msg['message']; ?>
            </p>
            <?php if ($reference): ?>
                <p style="font-size: 14px; color: #999; margin: 0 0 25px 0;">
                    Reference: <strong><?php echo esc_html($reference); ?></strong>
                </p>
            <?php endif; ?>
            <button onclick="document.getElementById('pesapal-status-modal').style.display='none'" style="
                background: <?php echo $msg['color']; ?>;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
            ">Close</button>
        </div>
    </div>
    <?php
}