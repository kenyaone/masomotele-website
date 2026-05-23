<?php
/**
 * MTTI Application Form Processor
 * Handles form submission, PDF generation, and notifications
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Simple validation function
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    // Accept international format with +, numbers, and hyphens
    return preg_match('/^[\d\s\-\+\(\)]{10,}$/', $phone);
}

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input
    $firstName = isset($_POST['firstName']) ? sanitize_text_field($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? sanitize_text_field($_POST['lastName']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $dob = isset($_POST['dob']) ? sanitize_text_field($_POST['dob']) : '';
    $idNumber = isset($_POST['idNumber']) ? sanitize_text_field($_POST['idNumber']) : '';
    $qualification = isset($_POST['highestQualification']) ? sanitize_text_field($_POST['highestQualification']) : '';
    $experience = isset($_POST['experience']) ? sanitize_textarea_field($_POST['experience']) : '';
    $startDate = isset($_POST['startDate']) ? sanitize_text_field($_POST['startDate']) : '';
    $paymentMethod = isset($_POST['paymentMethod']) ? sanitize_text_field($_POST['paymentMethod']) : '';
    $courses = isset($_POST['courses']) ? json_decode($_POST['courses'], true) : array();

    // Validate required fields
    if (empty($firstName) || empty($lastName)) {
        throw new Exception('Full name is required');
    }
    if (!validateEmail($email)) {
        throw new Exception('Invalid email address');
    }
    if (!validatePhone($phone)) {
        throw new Exception('Invalid phone number format');
    }
    if (empty($dob)) {
        throw new Exception('Date of birth is required');
    }
    if (empty($qualification)) {
        throw new Exception('Education qualification is required');
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

    // Create applications directory if it doesn't exist
    $appDir = dirname(__FILE__) . '/../../mtti-applications';
    if (!is_dir($appDir)) {
        mkdir($appDir, 0755, true);
    }

    // Store application data as JSON
    $applicationData = array(
        'applicationId' => $applicationId,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'dob' => $dob,
        'idNumber' => $idNumber,
        'qualification' => $qualification,
        'experience' => $experience,
        'courses' => $courses,
        'startDate' => $startDate,
        'paymentMethod' => $paymentMethod,
        'submittedAt' => date('Y-m-d H:i:s'),
        'status' => 'Pending Review'
    );

    // Save application data
    $jsonFile = $appDir . '/' . $applicationId . '.json';
    file_put_contents($jsonFile, json_encode($applicationData, JSON_PRETTY_PRINT));

    // Generate PDF admission letter
    $pdfFile = generateAdmissionPDF($applicationData, $appDir);

    // Send WhatsApp notification
    sendWhatsAppNotification($phone, $applicationId, $firstName);

    // Send confirmation email
    sendConfirmationEmail($email, $applicationData, $applicationId);

    // Return success response with admission link
    $admissionUrl = get_site_url() . '/mtti-admission/' . $applicationId;

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

/**
 * Sanitize text field
 */
function sanitize_text_field($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitize_email($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitize textarea
 */
function sanitize_textarea_field($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate PDF admission letter
 */
function generateAdmissionPDF($data, $appDir) {
    // Create simple HTML to PDF conversion
    $html = generateAdmissionHTML($data);

    // Use a simple library approach - create printable HTML
    // For production, consider using TCPDF or similar

    $pdfFile = $appDir . '/' . $data['applicationId'] . '_admission.html';
    file_put_contents($pdfFile, $html);

    return $pdfFile;
}

/**
 * Generate admission letter HTML
 */
function generateAdmissionHTML($data) {
    $coursesText = implode(', ', $data['courses']);
    $admissionDate = date('d/m/Y');

    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MTTI Admission Letter - {$data['applicationId']}</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                line-height: 1.6;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #1b5e20;
                padding-bottom: 20px;
            }
            .header h1 {
                color: #1b5e20;
                margin: 10px 0;
                font-size: 28px;
            }
            .header p {
                margin: 5px 0;
                color: #666;
            }
            .content {
                margin: 30px 0;
            }
            .admission-id {
                background: #e8f5e9;
                padding: 15px;
                border-left: 4px solid #1b5e20;
                margin-bottom: 20px;
                font-weight: bold;
            }
            .student-info {
                margin: 20px 0;
            }
            .info-row {
                margin: 10px 0;
                display: flex;
            }
            .info-label {
                font-weight: bold;
                width: 200px;
                color: #1b5e20;
            }
            .info-value {
                flex: 1;
            }
            .courses {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .next-steps {
                background: #e3f2fd;
                padding: 15px;
                border-left: 4px solid #667eea;
                margin: 20px 0;
            }
            .next-steps h3 {
                color: #667eea;
                margin-top: 0;
            }
            .next-steps ol {
                margin-left: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                color: #999;
                font-size: 12px;
            }
            .contact-info {
                text-align: center;
                margin: 20px 0;
                background: #fff3e0;
                padding: 15px;
                border-radius: 5px;
            }
            @media print {
                body {
                    margin: 0;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>MTTI Eldoret</h1>
            <p><strong>Masomotele Technical Training Institute</strong></p>
            <p>TVETA-Accredited Technical Training Center</p>
        </div>

        <div class="content">
            <h2 style="color: #1b5e20; text-align: center;">ADMISSION LETTER</h2>

            <div class="admission-id">
                📋 Admission ID: <strong>{$data['applicationId']}</strong>
                <br>Date Issued: <strong>{$admissionDate}</strong>
            </div>

            <p>Dear <strong>{$data['firstName']} {$data['lastName']}</strong>,</p>

            <p>Congratulations! We are pleased to inform you that your application to Masomotele Technical Training Institute (MTTI) has been <strong>RECEIVED AND REGISTERED</strong>.</p>

            <div class="student-info">
                <h3 style="color: #1b5e20;">Your Information:</h3>
                <div class="info-row">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value">{$data['firstName']} {$data['lastName']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{$data['email']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">WhatsApp:</div>
                    <div class="info-value">{$data['phone']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value">{$data['dob']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Education Level:</div>
                    <div class="info-value">{$data['qualification']}</div>
                </div>
            </div>

            <div class="courses">
                <h3 style="color: #1b5e20; margin-top: 0;">Selected Courses:</h3>
                <p style="margin: 0;">
                    <strong>{$coursesText}</strong>
                </p>
                <p style="font-size: 13px; margin: 10px 0 0 0; color: #666;">
                    <strong>Preferred Start Date:</strong> {$data['startDate']}
                </p>
            </div>

            <div class="next-steps">
                <h3>📌 Next Steps:</h3>
                <ol>
                    <li><strong>Confirm Your Enrollment:</strong> Reply to the WhatsApp message we just sent you to confirm your attendance</li>
                    <li><strong>Complete Payment:</strong> Pay the course fees via {$data['paymentMethod']}</li>
                    <li><strong>Bring Required Documents:</strong>
                        <ul>
                            <li>Original ID or Passport</li>
                            <li>Birth Certificate (photocopy)</li>
                            <li>Proof of latest education qualification</li>
                            <li>Two passport-size photos</li>
                        </ul>
                    </li>
                    <li><strong>Attend Orientation:</strong> Report on your start date for course orientation</li>
                </ol>
            </div>

            <div class="contact-info">
                <h3 style="margin-top: 0; color: #1b5e20;">📞 Need Help? Contact Us:</h3>
                <p style="margin: 10px 0;">
                    <strong>WhatsApp:</strong> <a href="https://wa.me/254712464936">+254 712 464 936</a><br>
                    <strong>Email:</strong> <a href="mailto:info@masomotele.ac.ke">info@masomotele.ac.ke</a><br>
                    <strong>Location:</strong> Sagaas Center, 4th Floor, Eldoret, Kenya
                </p>
            </div>

            <p style="margin-top: 30px; color: #666;">
                <em>This is an automated admission letter issued upon successful application submission.
                For course updates and confirmation, you will receive WhatsApp messages from our team shortly.</em>
            </p>
        </div>

        <div class="footer">
            <p>MTTI Eldoret - Real Skills, Real Jobs | www.masomoteletraining.co.ke</p>
            <p>© 2026 Masomotele Technical Training Institute. All Rights Reserved.</p>
        </div>

        <div class="no-print" style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">
            <button onclick="window.print()" style="background: #1b5e20; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                🖨️ Print or Save as PDF
            </button>
        </div>
    </body>
    </html>
    HTML;
}

/**
 * Send WhatsApp notification
 */
function sendWhatsAppNotification($phone, $applicationId, $firstName) {
    // Format phone number for WhatsApp
    $phone = preg_replace('/[^\d\+]/', '', $phone);
    if (substr($phone, 0, 1) !== '+') {
        $phone = '+' . $phone;
    }

    // Message content
    $message = "Hi {$firstName}! 👋\n\n";
    $message .= "Your application to MTTI Eldoret has been received! ✅\n\n";
    $message .= "Admission ID: {$applicationId}\n\n";
    $message .= "📋 View your admission letter: " . get_site_url() . "/mtti-admission/{$applicationId}\n\n";
    $message .= "📞 Next steps:\n";
    $message .= "1. Reply 'YES' to confirm enrollment\n";
    $message .= "2. Complete payment\n";
    $message .= "3. Bring required documents\n\n";
    $message .= "Questions? Just reply to this message or call us.\n\n";
    $message .= "Real Skills, Real Jobs - MTTI Eldoret 💼";

    // For WhatsApp integration, you can use:
    // Option 1: WhatsApp API via Twilio
    // Option 2: WhatsApp Business API
    // Option 3: Create WhatsApp Web link (manual)

    // For now, log the message and create a WhatsApp link
    $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($message);

    // Log for admin review
    $logFile = dirname(__FILE__) . '/../../mtti-applications/whatsapp-logs.txt';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Phone: {$phone}, AppID: {$applicationId}, URL: {$whatsappUrl}\n";
    error_log($logMessage, 3, $logFile);

    return true;
}

/**
 * Send confirmation email
 */
function sendConfirmationEmail($email, $data, $applicationId) {
    $coursesText = implode(', ', $data['courses']);
    $admissionUrl = get_site_url() . '/mtti-admission/' . $applicationId;

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
    $headers .= "From: MTTI Eldoret <info@masomotele.ac.ke>\r\n";

    mail($email, $subject, $emailBody, $headers);
}

/**
 * Get WordPress site URL
 */
function get_site_url() {
    return 'https://masomoteletraining.co.ke';
}
?>
