<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate - Masomotele Technical Training Institute</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        /* Header - matching your site's blue gradient */
        .site-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e22ce 100%);
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo-container {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }
        
        .site-title {
            color: white;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header-nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        .nav-link:hover {
            opacity: 0.8;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
        }
        
        .social-icon {
            color: white;
            font-size: 20px;
            transition: transform 0.3s;
        }
        
        .social-icon:hover {
            transform: scale(1.1);
        }
        
        /* Page Title Banner */
        .page-banner {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 40px 40px;
            color: white;
        }
        
        .page-title {
            font-size: 42px;
            font-weight: 700;
            margin: 0;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 40px;
        }
        
        .verification-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .card-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 28px;
            color: #1e3c72;
            margin-bottom: 10px;
        }
        
        .card-subtitle {
            font-size: 16px;
            color: #666;
        }
        
        .search-form {
            margin: 30px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 20px;
            font-size: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2a5298;
            box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1);
        }
        
        .form-input::placeholder {
            letter-spacing: normal;
            text-transform: none;
        }
        
        .btn-verify {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 82, 152, 0.3);
        }
        
        .btn-verify:active {
            transform: translateY(0);
        }
        
        /* Result Styles */
        .result-box {
            margin-top: 30px;
            padding: 30px;
            border-radius: 12px;
            border-left: 5px solid;
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .result-box.valid {
            background: #e8f5e9;
            border-color: #2E7D32;
        }
        
        .result-box.invalid {
            background: #ffebee;
            border-color: #c62828;
        }
        
        .result-box.revoked {
            background: #fff3e0;
            border-color: #f57c00;
        }
        
        .result-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .result-icon {
            font-size: 48px;
        }
        
        .result-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        
        .result-box.valid .result-title {
            color: #2E7D32;
        }
        
        .result-box.invalid .result-title {
            color: #c62828;
        }
        
        .result-box.revoked .result-title {
            color: #f57c00;
        }
        
        .result-description {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .certificate-details {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .detail-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            min-width: 200px;
            color: #666;
        }
        
        .detail-value {
            color: #333;
            flex: 1;
        }
        
        .detail-value strong {
            color: #2a5298;
            font-size: 18px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-badge.valid {
            background: #2E7D32;
            color: white;
        }
        
        .status-badge.revoked {
            background: #f57c00;
            color: white;
        }
        
        .help-box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 14px;
            color: #666;
            line-height: 1.8;
        }
        
        .help-box strong {
            color: #333;
            display: block;
            margin-bottom: 8px;
        }
        
        .help-box ul {
            margin: 10px 0 0 20px;
        }
        
        .help-box li {
            margin: 5px 0;
        }
        
        /* Footer */
        .site-footer {
            background: #1e3c72;
            color: white;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer-text {
            font-size: 14px;
            line-height: 1.8;
        }
        
        .footer-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .site-header {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
            }
            
            .page-banner {
                padding: 30px 20px;
            }
            
            .page-title {
                font-size: 32px;
            }
            
            .main-content {
                padding: 0 20px;
            }
            
            .verification-card {
                padding: 30px 20px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header matching your site -->
    <header class="site-header">
        <div class="header-left">
            <div class="logo-container">
                <img src="/wp-content/uploads/mtti-logo.png" alt="MTTI Logo" onerror="this.style.display='none'">
            </div>
            <div class="site-title">MTTI</div>
        </div>
        <nav class="header-nav">
            <a href="/" class="nav-link">Home</a>
            <div class="social-icons">
                <a href="#" class="social-icon">🐦</a>
                <a href="#" class="social-icon">📘</a>
                <a href="#" class="social-icon">📷</a>
            </div>
        </nav>
    </header>

    <!-- Page Title Banner -->
    <div class="page-banner">
        <h1 class="page-title">Verify Certificate</h1>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="verification-card">
            <div class="card-header">
                <div class="card-icon">🎓</div>
                <h2 class="card-title">Certificate Verification</h2>
                <p class="card-subtitle">Verify the authenticity of MTTI certificates</p>
            </div>

            <form method="POST" action="" class="search-form">
                <div class="form-group">
                    <label for="search_term" class="form-label">
                        Enter Certificate Number or Verification Code
                    </label>
                    <input 
                        type="text" 
                        name="search_term" 
                        id="search_term"
                        class="form-input"
                        placeholder="e.g., MTTI/CERT/2024/123456 or ABCD-EFGH-JKLM"
                        value="<?php echo isset($search_term) ? htmlspecialchars($search_term) : ''; ?>"
                        required
                        autofocus
                    >
                </div>
                <button type="submit" name="verify_certificate" class="btn-verify">
                    🔍 Verify Certificate
                </button>
            </form>

            <?php
            // WordPress database connection
            require_once('../../../wp-load.php');
            global $wpdb;
            
            $search_term = '';
            $auto_verify = false;
            
            // Check for QR code scan
            if (isset($_GET['code']) && !empty($_GET['code'])) {
                $search_term = sanitize_text_field($_GET['code']);
                $auto_verify = true;
            } elseif (isset($_POST['verify_certificate'])) {
                $search_term = sanitize_text_field($_POST['search_term']);
                $auto_verify = true;
            }
            
            if ($auto_verify && !empty($search_term)) {
                $table = $wpdb->prefix . 'mtti_certificates';
                
                $certificate = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE certificate_number = %s 
                     OR verification_code = %s 
                     LIMIT 1",
                    $search_term, $search_term
                ));
                
                if ($certificate && $certificate->status === 'Valid') {
                    // Valid Certificate
                    ?>
                    <div class="result-box valid">
                        <div class="result-header">
                            <div class="result-icon">✅</div>
                            <h2 class="result-title">Certificate Valid</h2>
                        </div>
                        <p class="result-description">
                            This is an authentic certificate issued by Masomotele Technical Training Institute. 
                            All information has been verified and is accurate.
                        </p>
                        
                        <div class="certificate-details">
                            <div class="detail-row">
                                <div class="detail-label">Certificate Number:</div>
                                <div class="detail-value"><strong><?php echo esc_html($certificate->certificate_number); ?></strong></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Student Name:</div>
                                <div class="detail-value"><?php echo esc_html($certificate->student_name); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Admission Number:</div>
                                <div class="detail-value"><?php echo esc_html($certificate->admission_number); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Course:</div>
                                <div class="detail-value">
                                    <?php echo esc_html($certificate->course_name); ?>
                                    <?php if ($certificate->course_code): ?>
                                        (<?php echo esc_html($certificate->course_code); ?>)
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Grade Achieved:</div>
                                <div class="detail-value"><strong><?php echo esc_html($certificate->grade); ?></strong></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Completion Date:</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($certificate->completion_date)); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Issue Date:</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($certificate->issue_date)); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Verification Code:</div>
                                <div class="detail-value" style="font-family: monospace; letter-spacing: 2px;">
                                    <?php echo esc_html($certificate->verification_code); ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <span class="status-badge valid">✓ VALID</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    
                } elseif ($certificate && $certificate->status === 'Revoked') {
                    // Revoked Certificate
                    ?>
                    <div class="result-box revoked">
                        <div class="result-header">
                            <div class="result-icon">⚠️</div>
                            <h2 class="result-title">Certificate Revoked</h2>
                        </div>
                        <p class="result-description">
                            This certificate has been revoked and is no longer valid.
                        </p>
                        
                        <div class="certificate-details">
                            <div class="detail-row">
                                <div class="detail-label">Certificate Number:</div>
                                <div class="detail-value"><?php echo esc_html($certificate->certificate_number); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Student Name:</div>
                                <div class="detail-value"><?php echo esc_html($certificate->student_name); ?></div>
                            </div>
                            <?php if ($certificate->notes): ?>
                            <div class="detail-row">
                                <div class="detail-label">Reason:</div>
                                <div class="detail-value"><?php echo esc_html($certificate->notes); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <span class="status-badge revoked">⚠ REVOKED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    
                } else {
                    // Certificate Not Found
                    ?>
                    <div class="result-box invalid">
                        <div class="result-header">
                            <div class="result-icon">❌</div>
                            <h2 class="result-title">Certificate Not Found</h2>
                        </div>
                        <p class="result-description">
                            The certificate number or verification code you entered could not be found in our records.
                        </p>
                        
                        <div class="certificate-details">
                            <p style="margin-bottom: 15px;"><strong>This could mean:</strong></p>
                            <ul style="margin-left: 20px; line-height: 1.8;">
                                <li>The certificate number or code was entered incorrectly</li>
                                <li>The certificate was not issued by MTTI</li>
                                <li>The certificate is fraudulent</li>
                            </ul>
                            <p style="margin-top: 20px;">
                                Please double-check the information and try again. Contact MTTI if you believe this is an error.
                            </p>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

            <div class="help-box">
                <strong>📌 How to Verify</strong>
                <p>You can verify any MTTI certificate using either:</p>
                <ul>
                    <li><strong>Certificate Number</strong> - Found at bottom left of certificate (e.g., MTTI/CERT/2024/123456)</li>
                    <li><strong>Verification Code</strong> - Found at bottom center (e.g., ABCD-EFGH-JKLM)</li>
                    <li><strong>QR Code</strong> - Scan with your smartphone camera for instant verification</li>
                </ul>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-text">
            <strong>Masomotele Technical Training Institute</strong><br>
            Sagaas Center, Fourth Floor, Eldoret, Kenya<br>
            "Start Learning, Start Earning"<br><br>
            <a href="mailto:info@mtti.ac.ke" class="footer-link">info@mtti.ac.ke</a>
        </div>
    </footer>
</body>
</html>
