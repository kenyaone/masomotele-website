<?php
/**
 * MTTI MIS Certificate System
 *
 * Auto-generates certificates on course pass
 * Unique numbers, verification codes, QR codes
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Certificate {

    /**
     * Check if student should get certificate
     * Trigger: Course average >= 70% (from 3 quizzes)
     */
    public static function check_and_issue_certificate($course_id, $student_id) {
        global $wpdb;

        // Get best scores from each quiz
        $quizzes = $wpdb->get_results($wpdb->prepare(
            "SELECT quiz_id, title FROM wpcu_mtti_quizzes WHERE course_id = %d ORDER BY quiz_id",
            $course_id
        ));

        if (count($quizzes) === 0) {
            return false;
        }

        $quiz_scores = array();
        $all_passed = true;

        foreach ($quizzes as $quiz) {
            $best_score = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(percent) FROM wpcu_mtti_quiz_attempts
                 WHERE quiz_id = %d AND student_id = %d",
                $quiz->quiz_id, $student_id
            ));

            if ($best_score === null) {
                return false; // Not all quizzes attempted
            }

            $quiz_scores[] = floatval($best_score);
        }

        // Calculate average
        $average_score = array_sum($quiz_scores) / count($quiz_scores);

        // Check if passes (70%+)
        if ($average_score < 70) {
            return false;
        }

        // Check if certificate already issued
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE course_id = %d AND student_id = %d",
            $course_id, $student_id
        ));

        if ($existing) {
            return $existing->certificate_id; // Already issued
        }

        // Issue new certificate
        return self::issue_certificate($course_id, $student_id, $average_score);
    }

    /**
     * Issue certificate
     */
    private static function issue_certificate($course_id, $student_id, $score) {
        global $wpdb;

        // Generate unique certificate number
        $cert_number = self::generate_certificate_number($course_id);

        // Generate verification code (12 chars)
        $verification_code = self::generate_verification_code();

        // Get course name
        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_course WHERE course_id = %d",
            $course_id
        ));

        $course_name = $course ? $course->title : "SMD-01";

        // Get student name
        $student = get_userdata($student_id);
        $student_name = $student ? $student->display_name : "Student " . $student_id;

        // Determine grade
        $grade = self::get_grade_letter($score);

        // Insert certificate
        $wpdb->insert('wpcu_mtti_certificates', array(
            'course_id' => $course_id,
            'student_id' => $student_id,
            'certificate_number' => $cert_number,
            'verification_code' => $verification_code,
            'student_name' => $student_name,
            'course_title' => $course_name,
            'final_score' => round($score, 2),
            'grade' => $grade,
            'issued_at' => current_time('mysql'),
            'valid' => 1
        ));

        return $wpdb->insert_id;
    }

    /**
     * Generate unique certificate number
     * Format: SMD01/CERT/2026/[6-DIGIT]
     */
    private static function generate_certificate_number($course_id) {
        global $wpdb;

        $year = date('Y');
        $course_code = 'SMD01'; // Can be dynamic based on course_id

        // Get sequential number
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM wpcu_mtti_certificates WHERE course_id = %d AND YEAR(issued_at) = %d",
            $course_id, $year
        ));

        $sequence = str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        return "{$course_code}/CERT/{$year}/{$sequence}";
    }

    /**
     * Generate random verification code
     * Format: XXXX-XXXX-XXXX (alphanumeric)
     */
    private static function generate_verification_code() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < 12; $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $code .= '-';
            }
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }

    /**
     * Get grade letter from percentage
     */
    private static function get_grade_letter($percentage) {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    /**
     * Get certificate by ID
     */
    public static function get_certificate($certificate_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE certificate_id = %d",
            $certificate_id
        ));
    }

    /**
     * Get certificate by verification code
     */
    public static function get_certificate_by_code($verification_code) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE verification_code = %s",
            $verification_code
        ));
    }

    /**
     * Get certificate by number
     */
    public static function get_certificate_by_number($certificate_number) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE certificate_number = %s",
            $certificate_number
        ));
    }

    /**
     * Get all certificates for student
     */
    public static function get_student_certificates($student_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wpcu_mtti_certificates WHERE student_id = %d ORDER BY issued_at DESC",
            $student_id
        ));
    }

    /**
     * Generate certificate PDF
     * (Requires external library like TCPDF or mPDF)
     */
    public static function generate_pdf($certificate_id) {
        $cert = self::get_certificate($certificate_id);

        if (!$cert) {
            return false;
        }

        // File path for generated PDF
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/mtti-certificates/';

        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }

        $pdf_file = $pdf_dir . 'CERT-' . $cert->certificate_number . '.pdf';

        // Check if already generated
        if (file_exists($pdf_file)) {
            return wp_upload_dir()['baseurl'] . '/mtti-certificates/CERT-' . $cert->certificate_number . '.pdf';
        }

        // Generate HTML certificate content
        $html = self::generate_certificate_html($cert);

        // Use WordPress to convert to PDF (requires external service or library)
        // For now, return HTML view URL
        // TODO: Integrate with TCPDF or similar library

        return $pdf_file;
    }

    /**
     * Generate certificate HTML
     */
    private static function generate_certificate_html($cert) {
        $qr_code = self::generate_qr_code($cert->verification_code);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 40px;
            background: white;
        }
        .certificate {
            border: 5px solid #2c3e50;
            padding: 60px;
            max-width: 900px;
            margin: 0 auto;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
            text-align: center;
        }
        .certificate::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="none" stroke="rgba(44,62,80,0.1)" stroke-width="1"/></svg>');
            pointer-events: none;
        }
        .content {
            position: relative;
            z-index: 1;
        }
        h1 {
            font-size: 48px;
            color: #2c3e50;
            margin: 0 0 20px 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .certificate-of {
            font-size: 24px;
            color: #7f8c8d;
            margin-bottom: 40px;
            font-style: italic;
        }
        .student-name {
            font-size: 36px;
            color: #c0392b;
            margin: 40px 0;
            font-weight: bold;
            border-bottom: 3px solid #c0392b;
            padding-bottom: 10px;
        }
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 40px 0;
            font-size: 14px;
        }
        .detail-item {
            text-align: left;
            background: rgba(255,255,255,0.7);
            padding: 15px;
            border-radius: 5px;
        }
        .detail-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .detail-value {
            color: #34495e;
            margin-top: 5px;
        }
        .grade {
            font-size: 48px;
            font-weight: bold;
            color: #27ae60;
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #7f8c8d;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .qr-code {
            width: 100px;
            height: 100px;
        }
        .signature {
            border-top: 2px solid #2c3e50;
            padding-top: 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="content">
            <h1>Certificate of Completion</h1>
            <p class="certificate-of">This certifies that</p>

            <div class="student-name">{$cert->student_name}</div>

            <p>has successfully completed the course</p>
            <h2>{$cert->course_title}</h2>

            <div class="details">
                <div class="detail-item">
                    <div class="detail-label">Certificate Number:</div>
                    <div class="detail-value">{$cert->certificate_number}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Issue Date:</div>
                    <div class="detail-value">{$cert->issued_at}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Final Score:</div>
                    <div class="detail-value">{$cert->final_score}%</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Grade:</div>
                    <div class="detail-value"><strong>{$cert->grade}</strong></div>
                </div>
            </div>

            <div class="grade">Grade {$cert->grade}</div>

            <p style="font-size: 12px; color: #7f8c8d;">
                Verification Code: {$cert->verification_code}<br>
                Verify at: masomoteletraining.co.ke/verify-certificate
            </p>

            <div class="footer">
                <div class="qr-code">{$qr_code}</div>
                <div class="signature">
                    Masomo Teletraining<br>
                    {$cert->issued_at}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Generate QR code (placeholder)
     * TODO: Use qrcode library
     */
    private static function generate_qr_code($data) {
        return '<div style="width:100px;height:100px;background:#ccc;display:flex;align-items:center;justify-content:center;">QR</div>';
    }

    /**
     * Get verification page URL
     */
    public static function get_verification_url($verification_code) {
        return home_url('/mtti-verify-certificate/?code=' . urlencode($verification_code));
    }

    /**
     * Get student dashboard certificate section
     */
    public static function get_student_certificates_html($student_id) {
        $certs = self::get_student_certificates($student_id);

        if (empty($certs)) {
            return '<p style="color: #7f8c8d;">No certificates yet. Pass your quizzes to earn one!</p>';
        }

        $html = '';
        foreach ($certs as $cert) {
            $html .= sprintf(
                '<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #27ae60;">
                    <h4 style="margin: 0 0 10px 0;">✅ %s</h4>
                    <p style="margin: 5px 0;"><strong>Certificate #:</strong> %s</p>
                    <p style="margin: 5px 0;"><strong>Score:</strong> %s%% (Grade %s)</p>
                    <p style="margin: 5px 0;"><strong>Issued:</strong> %s</p>
                    <a href="%s" style="color: #3498db; text-decoration: none; margin-right: 20px;">📄 View Certificate</a>
                    <a href="%s" style="color: #27ae60; text-decoration: none;">✓ Verify Online</a>
                </div>',
                esc_html($cert->course_title),
                esc_html($cert->certificate_number),
                esc_html($cert->final_score),
                esc_html($cert->grade),
                esc_html(date('F d, Y', strtotime($cert->issued_at))),
                self::get_verification_url($cert->verification_code),
                self::get_verification_url($cert->verification_code)
            );
        }

        return $html;
    }
}
