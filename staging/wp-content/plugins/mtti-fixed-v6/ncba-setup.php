<?php
/**
 * NCBA Payments Table Setup — Run ONCE
 * 
 * Access via browser or CLI:
 *   php ncba-setup.php
 *   OR visit: https://masomoteletraining.co.ke/wp-content/plugins/mtti-fixed-v5/ncba-setup.php
 * 
 * Then DELETE this file for security.
 */

// Load WordPress for dbDelta
$wp_load = dirname(__FILE__);
for ($i = 0; $i < 6; $i++) {
    $wp_load = dirname($wp_load);
    if (file_exists($wp_load . '/wp-load.php')) {
        require_once $wp_load . '/wp-load.php';
        break;
    }
}

global $wpdb;
$table   = $wpdb->prefix . 'ncba_payments';
$charset = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    trans_id        VARCHAR(50)  NOT NULL,
    ft_ref          VARCHAR(50)  DEFAULT NULL,
    trans_type      VARCHAR(30)  DEFAULT NULL,
    trans_time      VARCHAR(20)  DEFAULT NULL,
    trans_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    short_code      VARCHAR(20)  DEFAULT NULL,
    bill_ref        VARCHAR(100) DEFAULT NULL,
    narrative       VARCHAR(200) DEFAULT NULL,
    mobile          VARCHAR(30)  DEFAULT NULL,
    customer_name   VARCHAR(100) DEFAULT NULL,
    student_id      BIGINT UNSIGNED DEFAULT NULL,
    admission_number VARCHAR(50) DEFAULT NULL,
    format          VARCHAR(10)  DEFAULT 'JSON',
    raw_payload     LONGTEXT     DEFAULT NULL,
    received_at     DATETIME     DEFAULT NULL,
    status          VARCHAR(20)  DEFAULT 'received',
    PRIMARY KEY (id),
    UNIQUE KEY trans_id (trans_id),
    KEY student_id (student_id),
    KEY received_at (received_at)
) $charset;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql);

// Verify
if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
    echo "✅ Table '$table' is ready.\n";
    echo "⚠️  DELETE this file now for security: rm ncba-setup.php\n";
} else {
    echo "❌ Table creation failed. Check DB permissions.\n";
}
