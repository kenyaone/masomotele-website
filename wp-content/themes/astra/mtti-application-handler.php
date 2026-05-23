<?php
/**
 * MTTI Application Form Handler
 * Processes form submissions via AJAX
 */

// Allow direct access for AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_mtti_application') {
    // Process MTTI application
    process_mtti_application();
    exit;
}

function process_mtti_application() {
    header('Content-Type: application/json');

    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    try {
        // Validate request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid request method');
        }

        // Get and sanitize input
        $firstName = isset($_POST['firstName']) ? sanitize_text_field($_POST['firstName']) : '';
        $lastName = isset($_POST['lastName']) ? sanitize_text_field($_POST['lastName']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $startDate = isset($_POST['startDate']) ? sanitize_text_field($_POST['startDate']) : '';
        $paymentMethod = isset($_POST['paymentMethod']) ? sanitize_text_field($_POST['paymentMethod']) : '';

        // Get courses - WordPress adds slashes, so we need to remove them
        $coursesJson = isset($_POST['courses']) ? stripslashes($_POST['courses']) : '';
        $courses = json_decode($coursesJson, true);

        if (!is_array($courses)) {
            $courses = array();
        }

        // Validate required fields
        if (empty($firstName) || empty($lastName)) {
            throw new Exception('Full name is required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
        if (empty($phone) || strlen(preg_replace('/[^\d]/', '', $phone)) < 10) {
            throw new Exception('Invalid phone number format');
        }
        if (empty($courses) || !is_array($courses) || count($courses) === 0) {
            throw new Exception('Please select at least one course');
        }
        if (empty($startDate)) {
            throw new Exception('Preferred start date is required');
        }
        if (empty($paymentMethod)) {
            throw new Exception('Payment method is required');
        }

        // Generate unique application ID
        $applicationId = 'MTTI' . date('YmdHis') . rand(1000, 9999);

        // Create applications directory
        $appDir = WP_CONTENT_DIR . '/mtti-applications';
        if (!is_dir($appDir)) {
            mkdir($appDir, 0755, true);
        }

        // Prepare application data
        $applicationData = array(
            'applicationId' => $applicationId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'courses' => $courses,
            'startDate' => $startDate,
            'paymentMethod' => $paymentMethod,
            'submittedAt' => date('Y-m-d H:i:s'),
            'status' => 'Pending Review'
        );

        // Save application data
        $jsonFile = $appDir . '/' . $applicationId . '.json';
        file_put_contents($jsonFile, json_encode($applicationData, JSON_PRETTY_PRINT));

        // Generate admission letter
        $admissionUrl = site_url() . '/admission/' . $applicationId . '/';

        // Send confirmation email and WhatsApp notification
        send_mtti_confirmation_email($email, $applicationData, $applicationId);
        log_whatsapp_notification($phone, $applicationId, $firstName, $appDir);

        echo json_encode(array(
            'success' => true,
            'message' => 'Application submitted successfully!',
            'applicationId' => $applicationId,
            'admissionUrl' => $admissionUrl
        ));

    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
        http_response_code(400);
    }
}

function send_mtti_confirmation_email($email, $data, $applicationId) {
    $coursesText = implode(', ', $data['courses']);
    $admissionUrl = site_url() . '/admission/' . $applicationId . '/';

    $subject = 'MTTI Admission Confirmation - ' . $applicationId;

    $emailBody = <<<HTML
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
            .button { background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { color: #999; font-size: 12px; text-align: center; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>MTTI Eldoret</h1>
                <p>Masomotele Technical Training Institute</p>
            </div>
            <div class="content">
                <h2>Welcome, {$data['firstName']}!</h2>
                <p>Thank you for applying to MTTI Eldoret. Your application has been received and registered.</p>

                <p><strong>Admission ID:</strong> {$applicationId}</p>
                <p><strong>Selected Courses:</strong> {$coursesText}</p>
                <p><strong>Preferred Start Date:</strong> {$data['startDate']}</p>

                <p>
                    <a href="{$admissionUrl}" class="button">View Your Admission Letter</a>
                </p>

                <h3>What happens next?</h3>
                <ol>
                    <li>You'll receive a WhatsApp message shortly with additional details</li>
                    <li>Confirm your enrollment by replying to the WhatsApp message</li>
                    <li>Complete the course fee payment</li>
                    <li>Report on your start date with the required documents</li>
                </ol>

                <p>For questions, contact us via WhatsApp: <a href="https://wa.me/254712464936">+254 712 464 936</a></p>
            </div>
            <div class="footer">
                <p>MTTI Eldoret - Real Skills, Real Jobs</p>
                <p>Sagaas Center, 4th Floor, Eldoret, Kenya</p>
            </div>
        </div>
    </body>
    </html>
    HTML;

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: MTTI Eldoret <musilwabonface@gmail.com>\r\n";
    $headers .= "Reply-To: musilwabonface@gmail.com\r\n";

    wp_mail($email, $subject, $emailBody, $headers);
}

function log_whatsapp_notification($phone, $applicationId, $firstName, $appDir) {
    $phone = preg_replace('/[^\d\+]/', '', $phone);
    if (substr($phone, 0, 1) !== '+') {
        $phone = '+' . $phone;
    }

    $message = "Hi {$firstName}! 👋\n\n";
    $message .= "Your application to MTTI Eldoret has been received! ✅\n\n";
    $message .= "Admission ID: {$applicationId}\n\n";
    $message .= "📋 View your admission letter: " . site_url() . "/admission/{$applicationId}/\n\n";
    $message .= "📞 Next steps:\n";
    $message .= "1. Reply 'YES' to confirm enrollment\n";
    $message .= "2. Complete payment\n";
    $message .= "3. Bring required documents\n\n";
    $message .= "Questions? Just reply to this message or call us.\n\n";
    $message .= "Real Skills, Real Jobs - MTTI Eldoret 💼";

    $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

    $logFile = $appDir . '/whatsapp-logs.txt';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Phone: {$phone}, AppID: {$applicationId}, URL: {$whatsappUrl}\n";
    error_log($logMessage, 3, $logFile);
}

// Register AJAX handler
add_action('wp_ajax_nopriv_process_mtti_application', 'process_mtti_application');
add_action('wp_ajax_process_mtti_application', 'process_mtti_application');

/**
 * Register custom rewrite rules for admission letters
 */
add_action('init', function() {
    // Rewrite rule for /admission/{application-id}/
    add_rewrite_rule(
        '^admission/([MTTI0-9]+)/?$',
        'index.php?mtti_admission_id=$matches[1]',
        'top'
    );

    // Flush rewrite rules if not already done
    $rewrite_rules = get_option('rewrite_rules');
    if (empty($rewrite_rules) || !isset($rewrite_rules['^admission/([MTTI0-9]+)/?$'])) {
        flush_rewrite_rules();
    }
});

/**
 * Add query var for admission ID
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'mtti_admission_id';
    return $vars;
});

/**
 * Flush rewrite rules on theme activation
 */
add_action('after_switch_theme', function() {
    flush_rewrite_rules();
});

/**
 * Handle admission letter display via clean URL
 */
add_action('template_include', function($template) {
    if (get_query_var('mtti_admission_id')) {
        // Load the admission letter template for clean URL access
        return get_template_directory() . '/admission-letter.php';
    }
    return $template;
}, 99);
