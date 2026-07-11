<?php
/**
 * MTTI MIS Certificate Verification
 *
 * Public page for verifying certificates online
 * Endpoint: /verify-certificate/?code=XXXX-XXXX-XXXX
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Certificate_Verification {

    /**
     * Display verification page
     */
    public static function display_verification_page() {
        // Get verification code from query string
        $code = isset($_GET['verify_cert']) ? sanitize_text_field($_GET['verify_cert']) : '';
        $cert_number = isset($_GET['cert_number']) ? sanitize_text_field($_GET['cert_number']) : '';

        $certificate = null;
        $verified = false;
        $error = null;

        if ($code) {
            $certificate = self::verify_certificate_by_code($code);
            if ($certificate) {
                $verified = true;
            } else {
                $error = 'Certificate not found. Please check your verification code and try again.';
            }
        } elseif ($cert_number) {
            $certificate = self::verify_certificate_by_number($cert_number);
            if ($certificate) {
                $verified = true;
            } else {
                $error = 'Certificate not found. Please check your certificate number and try again.';
            }
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Certificate Verification - Masomo Teletraining</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 700px;
                    width: 100%;
                    overflow: hidden;
                }

                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                }

                .header h1 {
                    font-size: 32px;
                    margin-bottom: 10px;
                }

                .header p {
                    opacity: 0.9;
                    font-size: 16px;
                }

                .content {
                    padding: 40px 30px;
                }

                .search-form {
                    display: grid;
                    grid-template-columns: 1fr auto;
                    gap: 10px;
                    margin-bottom: 30px;
                }

                .search-form input {
                    padding: 12px 15px;
                    border: 2px solid #e0e0e0;
                    border-radius: 6px;
                    font-size: 14px;
                    transition: border-color 0.3s;
                }

                .search-form input:focus {
                    outline: none;
                    border-color: #667eea;
                    background: #f8f9ff;
                }

                .search-form button {
                    padding: 12px 30px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: background 0.3s;
                }

                .search-form button:hover {
                    background: #764ba2;
                }

                .error {
                    background: #fee;
                    border: 1px solid #fcc;
                    color: #c33;
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                    text-align: center;
                }

                .success {
                    background: #efe;
                    border: 1px solid #cfc;
                    color: #3a3;
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 20px;
                }

                .certificate-display {
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    border: 5px solid #2c3e50;
                    padding: 40px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    text-align: center;
                }

                .certificate-display h2 {
                    color: #2c3e50;
                    font-size: 28px;
                    margin-bottom: 20px;
                }

                .cert-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin: 30px 0;
                    text-align: left;
                }

                .cert-detail {
                    background: rgba(255,255,255,0.8);
                    padding: 15px;
                    border-radius: 6px;
                }

                .cert-detail label {
                    display: block;
                    font-weight: bold;
                    color: #667eea;
                    font-size: 12px;
                    text-transform: uppercase;
                    margin-bottom: 5px;
                    letter-spacing: 1px;
                }

                .cert-detail span {
                    display: block;
                    color: #333;
                    font-size: 16px;
                }

                .cert-grade {
                    font-size: 48px;
                    font-weight: bold;
                    margin: 20px 0;
                    color: #f39c12;
                }

                .verification-badge {
                    background: #d4edda;
                    border: 2px solid #28a745;
                    color: #155724;
                    padding: 15px;
                    border-radius: 6px;
                    text-align: center;
                    margin-bottom: 20px;
                }

                .verification-badge h3 {
                    margin: 0 0 10px 0;
                    font-size: 18px;
                }

                .verification-badge p {
                    margin: 0;
                    font-size: 12px;
                }

                .tabs {
                    display: flex;
                    gap: 10px;
                    margin-top: 30px;
                    border-top: 1px solid #e0e0e0;
                    padding-top: 20px;
                }

                .tab-button {
                    flex: 1;
                    padding: 15px;
                    background: #f8f9fa;
                    border: 1px solid #e0e0e0;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: bold;
                    transition: all 0.3s;
                }

                .tab-button:hover {
                    background: #667eea;
                    color: white;
                    border-color: #667eea;
                }

                .help-text {
                    background: #ecf0f1;
                    padding: 20px;
                    border-radius: 6px;
                    margin-top: 30px;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #555;
                }

                .help-text h3 {
                    margin: 0 0 10px 0;
                    color: #333;
                }

                .help-text ul {
                    margin: 0;
                    padding-left: 20px;
                }

                .help-text li {
                    margin: 5px 0;
                }

                .status-badge {
                    display: inline-block;
                    padding: 8px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-top: 10px;
                }

                .status-valid {
                    background: #d4edda;
                    color: #155724;
                }

                .status-invalid {
                    background: #f8d7da;
                    color: #721c24;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <!-- Header -->
                <div class="header">
                    <h1>🎓 Certificate Verification</h1>
                    <p>Verify your Masomo Teletraining certificate authenticity</p>
                </div>

                <div class="content">

                    <!-- Search Form -->
                    <div class="search-form">
                        <input type="text" placeholder="Enter verification code (XXXX-XXXX-XXXX) or certificate number" id="search-input" value="<?php echo esc_attr($code ?: $cert_number); ?>">
                        <button onclick="verifyNow()">Verify</button>
                    </div>

                    <!-- Results -->
                    <?php if ($error): ?>
                        <div class="error">
                            <strong>❌ Verification Failed</strong><br>
                            <?php echo esc_html($error); ?>
                        </div>
                    <?php elseif ($verified && $certificate): ?>

                        <!-- Verified Badge -->
                        <div class="verification-badge">
                            <h3>✅ Certificate Verified</h3>
                            <p>This certificate is genuine and authentic</p>
                            <span class="status-badge status-valid">
                                <?php echo ($certificate->valid) ? 'VALID' : 'INVALID'; ?>
                            </span>
                        </div>

                        <!-- Certificate Display -->
                        <div class="certificate-display">
                            <h2>Certificate of Completion</h2>

                            <p style="font-size: 14px; color: #666; margin: 20px 0;">This certifies that</p>

                            <div style="font-size: 24px; font-weight: bold; color: #c0392b; margin: 20px 0;">
                                <?php echo esc_html($certificate->student_name); ?>
                            </div>

                            <p style="font-size: 14px; color: #666;">has successfully completed the course</p>

                            <div style="font-size: 20px; font-weight: bold; color: #2c3e50; margin: 15px 0;">
                                <?php echo esc_html($certificate->course_title); ?>
                            </div>

                            <div class="cert-details">
                                <div class="cert-detail">
                                    <label>Certificate Number</label>
                                    <span><?php echo esc_html($certificate->certificate_number); ?></span>
                                </div>
                                <div class="cert-detail">
                                    <label>Issue Date</label>
                                    <span><?php echo esc_html(date('F d, Y', strtotime($certificate->issued_at))); ?></span>
                                </div>
                                <div class="cert-detail">
                                    <label>Final Score</label>
                                    <span><?php echo esc_html($certificate->final_score); ?>%</span>
                                </div>
                                <div class="cert-detail">
                                    <label>Grade</label>
                                    <span><?php echo esc_html($certificate->grade); ?></span>
                                </div>
                            </div>

                            <div class="cert-grade">
                                Grade <?php echo esc_html($certificate->grade); ?>
                            </div>

                            <p style="color: #7f8c8d; font-size: 12px;">
                                Verification Code: <strong><?php echo esc_html($certificate->verification_code); ?></strong>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="tabs">
                            <button class="tab-button" onclick="window.print()">🖨️ Print</button>
                            <button class="tab-button" onclick="downloadCertificate('<?php echo esc_js($certificate->certificate_id); ?>')">📥 Download PDF</button>
                            <button class="tab-button" onclick="shareCertificate()">📤 Share</button>
                        </div>

                    <?php endif; ?>

                    <!-- Help Section -->
                    <div class="help-text">
                        <h3>How to verify a certificate:</h3>
                        <ul>
                            <li>Enter the verification code found on the certificate (e.g., ABCD-EFGH-JKLM)</li>
                            <li>OR enter the certificate number (e.g., SMD01/CERT/2026/000123)</li>
                            <li>Click "Verify" to check authenticity</li>
                            <li>Only certificates issued by Masomo Teletraining will pass verification</li>
                        </ul>
                    </div>

                </div>
            </div>

            <script>
            function verifyNow() {
                const input = document.getElementById('search-input').value.trim();
                if (!input) {
                    alert('Please enter a verification code or certificate number');
                    return;
                }
                window.location.href = '<?php echo esc_js(self::get_verification_url('')); ?>' + encodeURIComponent(input);
            }

            function downloadCertificate(certId) {
                window.location.href = '<?php echo esc_js(wp_nonce_url(admin_url('admin-ajax.php?action=mtti_download_certificate&cert_id='), 'mtti_cert')); ?>' + certId;
            }

            function shareCertificate() {
                const text = 'Check out my certificate of completion from Masomo Teletraining!';
                const url = window.location.href;
                if (navigator.share) {
                    navigator.share({
                        title: 'My Certificate',
                        text: text,
                        url: url
                    });
                } else {
                    alert('Share: ' + text + '\n' + url);
                }
            }

            // Allow Enter key to search
            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') verifyNow();
            });
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Verify certificate by code
     */
    private static function verify_certificate_by_code($code) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE verification_code = %s",
            $code
        ));
    }

    /**
     * Verify certificate by number
     */
    private static function verify_certificate_by_number($number) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE certificate_number = %s",
            $number
        ));
    }

    /**
     * Get verification URL
     */
    public static function get_verification_url($code) {
        return home_url('/?mtti_action=verify_certificate&code=' . urlencode($code));
    }
}
