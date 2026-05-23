<?php
/**
 * MTTI SMTP Configuration
 *
 * Email configuration for Masomotele Technical Training Institute
 * Handles WhatsApp notifications and admission letter emails
 */

// Hook into PHPMailer initialization
add_action('phpmailer_init', function($phpmailer) {
    // Gmail/SMTP Configuration
    // You have two options:

    // OPTION 1: Use Gmail (Recommended for your account)
    $phpmailer->Host = 'smtp.gmail.com';
    $phpmailer->Port = 587;
    $phpmailer->SMTPAuth = true;
    $phpmailer->SMTPSecure = 'tls';

    // IMPORTANT: Use an App Password, not your regular Gmail password
    // Steps:
    // 1. Go to https://myaccount.google.com/apppasswords
    // 2. Select "Mail" and "Windows Computer"
    // 3. Copy the 16-character password
    // 4. Replace the password below
    $phpmailer->Username = 'musilwabonface@gmail.com';
    $phpmailer->Password = 'YOUR_GMAIL_APP_PASSWORD_HERE'; // Replace with your app password

    // Set from address
    $phpmailer->From = 'musilwabonface@gmail.com';
    $phpmailer->FromName = 'MTTI Eldoret';

    // OPTION 2: Use Hosting Provider's SMTP (cPanel)
    // Uncomment below and comment out Gmail config above
    /*
    $phpmailer->Host = 'mail.masomoteletraining.co.ke'; // or smtp.masomoteletraining.co.ke
    $phpmailer->Port = 465;
    $phpmailer->SMTPAuth = true;
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->Username = 'info@masomoteletraining.co.ke';
    $phpmailer->Password = 'YOUR_EMAIL_PASSWORD_HERE';
    $phpmailer->From = 'info@masomoteletraining.co.ke';
    $phpmailer->FromName = 'MTTI Eldoret';
    */
});

/**
 * Log all email sends for debugging
 */
add_filter('wp_mail_from', function($from) {
    $logDir = WP_CONTENT_DIR . '/mtti-applications';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/email-logs.txt';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Email sent from: {$from}\n";
    error_log($logMessage, 3, $logFile);

    return $from;
});

/**
 * Test email function - Call this to verify SMTP is working
 * Usage in browser: https://masomoteletraining.co.ke/?mtti_test_email=1
 */
add_action('init', function() {
    if (isset($_GET['mtti_test_email']) && current_user_can('manage_options')) {
        $to = 'musilwabonface@gmail.com';
        $subject = 'MTTI Email Configuration Test';
        $message = "This is a test email to verify SMTP configuration is working correctly.\n\n";
        $message .= "If you received this, your email setup is complete!\n\n";
        $message .= "Sent at: " . date('Y-m-d H:i:s') . "\n";
        $message .= "From: " . get_option('admin_email');

        $result = wp_mail($to, $subject, $message);

        wp_die($result ? '✅ Test email sent! Check your inbox.' : '❌ Failed to send test email. Check email logs.');
    }
});

?>
