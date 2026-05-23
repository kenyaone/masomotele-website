<?php
/**
 * Online Admission Form – shortcode [mtti_admission_form]
 * Generates a PDF admission letter immediately on submission.
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Public_Admission_Form {

    private $db;

    public function __construct() {
        $this->db = MTTI_MIS_Database::get_instance();
        // PDF download via admin-ajax.php (works regardless of DirectoryIndex)
        add_action('wp_ajax_mtti_download_letter',        array($this, 'ajax_download'));
        add_action('wp_ajax_nopriv_mtti_download_letter', array($this, 'ajax_download'));
    }

    // ---------------------------------------------------------------
    // PDF DOWNLOAD – served via /wp-admin/admin-ajax.php
    // ---------------------------------------------------------------

    public function ajax_download() {
        $token = sanitize_text_field($_GET['token'] ?? '');
        $data  = get_transient('mtti_admission_' . $token);

        if (!$token || !$data || empty($data['student_id'])) {
            wp_die(
                'Your download link has expired (valid for 2 hours). ' .
                'Please contact the school office with your admission number.',
                'Link Expired',
                array('response' => 410)
            );
        }

        $admission_number = sanitize_file_name($data['admission_number'] ?? 'Letter');
        $pdf_bytes        = $this->generate_pdf((int) $data['student_id']);

        if (!$pdf_bytes) {
            wp_die('Could not generate PDF. Please contact the school office.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="MTTI_Admission_Letter_' . $admission_number . '.pdf"');
        header('Content-Length: ' . strlen($pdf_bytes));
        header('Cache-Control: private, no-store');
        echo $pdf_bytes;
        exit;
    }

    // ---------------------------------------------------------------
    // SHORTCODE CALLBACK
    // ---------------------------------------------------------------

    public function render_shortcode($atts) {
        // Prevent caching of this page so nonces are always fresh
        do_action('litespeed_control_set_nocache', 'mtti admission form contains a nonce');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        ob_start();

        if (!empty($_POST['mtti_adm_nonce'])) {
            if (!wp_verify_nonce($_POST['mtti_adm_nonce'], 'mtti_adm_submit')) {
                echo '<p style="color:red;">Security check failed. Please refresh the page and try again.</p>';
            } else {
                $token = $this->process_submission();
                if ($token !== false) {
                    $this->render_success($token);
                }
                // On failure process_submission renders form with errors
            }
            return ob_get_clean();
        }

        $this->render_form();
        return ob_get_clean();
    }

    // ---------------------------------------------------------------
    // FORM
    // ---------------------------------------------------------------

    private function render_form(array $errors = [], array $old = []) {
        $courses = $this->db->get_courses(array('status' => 'Active'));
        $counties = $this->get_kenya_counties();
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <div class="mtti-adm-wrap">
        <style>
            /* ── TOKENS ── */
            .mtti-adm-wrap {
                --mg: #2E7D32;
                --mg-dark: #1B5E20;
                --mg-light: #4CAF50;
                --mo: #FF9800;
                --mo-dark: #F57C00;
                --dark: #1a1a2e;
                --gray: #666;
                --off: #f7f9f7;
                --border: #e8ede8;
                max-width: 780px;
                margin: 0 auto 40px;
                font-family: 'Poppins', sans-serif;
                color: var(--dark);
            }

            /* ── HERO HEADER ── */
            .mtti-adm-header {
                background: linear-gradient(135deg, var(--mg) 0%, var(--mg-dark) 60%, #0d3b10 100%);
                padding: 36px 32px 32px;
                border-radius: 16px 16px 0 0;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            .mtti-adm-header::before {
                content: '';
                position: absolute;
                inset: 0;
                background: radial-gradient(circle at 80% 40%, rgba(255,152,0,.12) 0%, transparent 60%);
                pointer-events: none;
            }
            .mtti-adm-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(255,255,255,.15);
                border: 1px solid rgba(255,255,255,.25);
                padding: 5px 16px;
                border-radius: 50px;
                font-size: 11px;
                font-weight: 700;
                color: #fff;
                text-transform: uppercase;
                letter-spacing: .5px;
                margin-bottom: 14px;
            }
            .mtti-adm-header h2 {
                margin: 0 0 8px;
                font-size: clamp(20px, 4vw, 28px);
                font-weight: 800;
                color: #fff;
                line-height: 1.2;
            }
            .mtti-adm-header h2 span { color: var(--mo); }
            .mtti-adm-header p {
                margin: 0;
                font-size: 13px;
                color: rgba(255,255,255,.82);
                font-weight: 500;
            }

            /* ── BODY ── */
            .mtti-adm-body {
                background: var(--off);
                padding: 28px 28px 32px;
                border: 1px solid var(--border);
                border-top: none;
                border-radius: 0 0 16px 16px;
            }

            /* ── SECTION CARDS ── */
            .mtti-adm-section {
                background: #fff;
                border-radius: 12px;
                padding: 22px 20px;
                margin-bottom: 18px;
                box-shadow: 0 2px 12px rgba(0,0,0,.06);
                border-left: 4px solid var(--mg);
            }
            .mtti-adm-section h3 {
                margin: 0 0 16px;
                font-size: 13px;
                font-weight: 700;
                color: var(--mg);
                text-transform: uppercase;
                letter-spacing: .5px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .mtti-adm-section h3::after {
                content: '';
                flex: 1;
                height: 2px;
                background: linear-gradient(90deg, var(--mo), transparent);
                border-radius: 2px;
            }

            /* ── FORM ROWS & FIELDS ── */
            .mtti-adm-row   { display: flex; gap: 14px; margin-bottom: 14px; }
            .mtti-adm-field { flex: 1; min-width: 0; }
            .mtti-adm-field label {
                display: block;
                font-weight: 600;
                font-size: 12px;
                color: var(--gray);
                margin-bottom: 5px;
                text-transform: uppercase;
                letter-spacing: .3px;
            }
            .mtti-adm-field input,
            .mtti-adm-field select {
                width: 100%;
                padding: 11px 13px;
                border: 1.5px solid #dde3dd;
                border-radius: 8px;
                font-size: 14px;
                font-family: 'Poppins', sans-serif;
                box-sizing: border-box;
                background: #fff;
                color: var(--dark);
                transition: border-color .2s, box-shadow .2s;
            }
            .mtti-adm-field input:focus,
            .mtti-adm-field select:focus {
                outline: none;
                border-color: var(--mg);
                box-shadow: 0 0 0 3px rgba(46,125,50,.12);
            }
            .mtti-adm-field input::placeholder { color: #bbb; }
            .req { color: var(--mo-dark); }

            /* ── FEE PILL ── */
            .mtti-fee-info {
                display: none;
                background: rgba(46,125,50,.08);
                border: 1px solid rgba(46,125,50,.2);
                color: var(--mg);
                padding: 9px 13px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                margin-top: 8px;
            }

            /* ── SUBMIT BUTTON ── */
            .mtti-adm-btn {
                background: linear-gradient(135deg, var(--mg), var(--mg-dark));
                color: #fff;
                border: none;
                padding: 16px 24px;
                font-size: 16px;
                font-weight: 700;
                font-family: 'Poppins', sans-serif;
                border-radius: 50px;
                cursor: pointer;
                width: 100%;
                margin-top: 8px;
                transition: transform .2s, box-shadow .2s;
                box-shadow: 0 6px 20px rgba(46,125,50,.35);
                letter-spacing: .3px;
            }
            .mtti-adm-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 28px rgba(46,125,50,.45);
            }

            /* ── ERRORS ── */
            .mtti-adm-errors {
                background: #fff8e1;
                border-left: 4px solid var(--mo);
                padding: 14px 18px;
                border-radius: 8px;
                margin-bottom: 18px;
                font-size: 13px;
                color: #5d4037;
            }
            .mtti-adm-errors ul { margin: 6px 0 0 20px; line-height: 1.9; }

            /* ── DISCLAIMER ── */
            .mtti-adm-disclaimer {
                text-align: center;
                font-size: 11.5px;
                color: #aaa;
                margin-top: 12px;
                line-height: 1.6;
            }

            /* ── RESPONSIVE ── */
            @media (max-width: 580px) {
                .mtti-adm-body { padding: 18px 16px 24px; }
                .mtti-adm-section { padding: 18px 16px; }
                .mtti-adm-row { flex-direction: column; gap: 0; }
                .mtti-adm-row .mtti-adm-field { margin-bottom: 14px; }
                .mtti-adm-header { padding: 28px 20px 24px; border-radius: 12px 12px 0 0; }
            }
        </style>

        <div class="mtti-adm-header">
            <div class="mtti-adm-badge">&#127891; TVETA Accredited &middot; Eldoret, Kenya</div>
            <h2>Online <span>Admission</span> Application</h2>
            <p>Masomotele Technical Training Institute (M.T.T.I) &mdash; Apply &amp; get your letter instantly</p>
        </div>

        <div class="mtti-adm-body">

            <?php if (!empty($errors)): ?>
            <div class="mtti-adm-errors">
                <strong>Please fix the following:</strong>
                <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo esc_html($e); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('mtti_adm_submit', 'mtti_adm_nonce'); ?>

                <!-- PERSONAL INFORMATION -->
                <div class="mtti-adm-section">
                    <h3>&#128100; Personal Information</h3>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field">
                            <label>First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" required
                                   value="<?php echo esc_attr($old['first_name'] ?? ''); ?>">
                        </div>
                        <div class="mtti-adm-field">
                            <label>Last Name <span class="req">*</span></label>
                            <input type="text" name="last_name" required
                                   value="<?php echo esc_attr($old['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field">
                            <label>National ID / Passport No. <span class="req">*</span></label>
                            <input type="text" name="id_number" required
                                   value="<?php echo esc_attr($old['id_number'] ?? ''); ?>">
                        </div>
                        <div class="mtti-adm-field">
                            <label>Date of Birth <span class="req">*</span></label>
                            <input type="date" name="date_of_birth" required
                                   value="<?php echo esc_attr($old['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field">
                            <label>Gender <span class="req">*</span></label>
                            <select name="gender" required>
                                <option value="">-- Select --</option>
                                <?php foreach (['Male','Female','Other'] as $g): ?>
                                <option value="<?php echo esc_attr($g); ?>"
                                    <?php selected($old['gender'] ?? '', $g); ?>>
                                    <?php echo esc_html($g); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mtti-adm-field">
                            <label>Phone Number (WhatsApp) <span class="req">*</span></label>
                            <input type="tel" name="phone" required placeholder="e.g. 0712345678"
                                   value="<?php echo esc_attr($old['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Optional"
                                   value="<?php echo esc_attr($old['email'] ?? ''); ?>">
                        </div>
                        <div class="mtti-adm-field">
                            <label>County <span class="req">*</span></label>
                            <select name="county" required>
                                <option value="">-- Select County --</option>
                                <?php foreach ($counties as $c): ?>
                                <option value="<?php echo esc_attr($c); ?>"
                                    <?php selected($old['county'] ?? '', $c); ?>>
                                    <?php echo esc_html($c); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field" style="flex:2;">
                            <label>Physical Address / Town</label>
                            <input type="text" name="address" placeholder="Town / Estate / Area"
                                   value="<?php echo esc_attr($old['address'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- EMERGENCY CONTACT -->
                <div class="mtti-adm-section">
                    <h3>&#128222; Emergency Contact</h3>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field">
                            <label>Contact Name</label>
                            <input type="text" name="emergency_contact"
                                   value="<?php echo esc_attr($old['emergency_contact'] ?? ''); ?>">
                        </div>
                        <div class="mtti-adm-field">
                            <label>Contact Phone</label>
                            <input type="tel" name="emergency_phone" placeholder="e.g. 0712345678"
                                   value="<?php echo esc_attr($old['emergency_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- COURSE SELECTION -->
                <div class="mtti-adm-section">
                    <h3>&#127979; Course Selection</h3>
                    <div class="mtti-adm-row">
                        <div class="mtti-adm-field">
                            <label>Choose Your Course <span class="req">*</span></label>
                            <select name="course_id" id="mtti_adm_course" required
                                    onchange="mttiShowFee(this)">
                                <option value="">-- Select a Course --</option>
                                <?php foreach ($courses as $c): ?>
                                <option value="<?php echo esc_attr($c->course_id); ?>"
                                        data-fee="<?php echo esc_attr($c->fee); ?>"
                                        data-weeks="<?php echo esc_attr($c->duration_weeks); ?>"
                                    <?php selected($old['course_id'] ?? '', $c->course_id); ?>>
                                    <?php echo esc_html($c->course_name . ' (' . $c->course_code . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mtti-fee-info" id="mtti_fee_info"></div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="mtti-adm-btn">
                    &#128221; Submit Application &amp; Get Admission Letter
                </button>
                <p class="mtti-adm-disclaimer">
                    By submitting, you confirm all information is accurate. Your admission letter PDF will be available for download immediately.
                </p>
            </form>
        </div>
        </div>

        <script>
        function mttiShowFee(sel) {
            var opt = sel.options[sel.selectedIndex];
            var fee   = parseFloat(opt.dataset.fee || 0);
            var weeks = opt.dataset.weeks;
            var box   = document.getElementById('mtti_fee_info');
            if (fee > 0) {
                box.style.display = 'block';
                box.innerHTML = '<strong>Course Fee:</strong> KES ' + fee.toLocaleString()
                    + (weeks ? ' &nbsp;|&nbsp; <strong>Duration:</strong> ' + weeks + ' Weeks' : '');
            } else {
                box.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            var sel = document.getElementById('mtti_adm_course');
            if (sel && sel.value) mttiShowFee(sel);
        });
        </script>
        <?php
    }

    // ---------------------------------------------------------------
    // FORM SUBMISSION HANDLER – returns token on success, false on failure
    // ---------------------------------------------------------------

    private function process_submission() {
        $first_name        = sanitize_text_field($_POST['first_name']        ?? '');
        $last_name         = sanitize_text_field($_POST['last_name']         ?? '');
        $id_number         = sanitize_text_field($_POST['id_number']         ?? '');
        $date_of_birth     = sanitize_text_field($_POST['date_of_birth']     ?? '');
        $gender            = sanitize_text_field($_POST['gender']            ?? '');
        $phone             = sanitize_text_field($_POST['phone']             ?? '');
        $email             = sanitize_email($_POST['email']                  ?? '');
        $county            = sanitize_text_field($_POST['county']            ?? '');
        $address           = sanitize_text_field($_POST['address']           ?? '');
        $emergency_contact = sanitize_text_field($_POST['emergency_contact'] ?? '');
        $emergency_phone   = sanitize_text_field($_POST['emergency_phone']   ?? '');
        $course_id         = intval($_POST['course_id']                      ?? 0);

        // Validate required fields
        $errors = [];
        if (!$first_name)    $errors[] = 'First name is required.';
        if (!$last_name)     $errors[] = 'Last name is required.';
        if (!$id_number)     $errors[] = 'ID / Passport number is required.';
        if (!$date_of_birth) $errors[] = 'Date of birth is required.';
        if (!$gender)        $errors[] = 'Gender is required.';
        if (!$phone)         $errors[] = 'Phone number is required.';
        if (!$county)        $errors[] = 'County is required.';
        if (!$course_id)     $errors[] = 'Please select a course.';

        // Check for duplicate ID number
        if ($id_number && empty($errors)) {
            global $wpdb;
            $students_table = $wpdb->prefix . 'mtti_students';
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT student_id FROM {$students_table} WHERE id_number = %s LIMIT 1",
                $id_number
            ));
            if ($existing) {
                $errors[] = 'A student with this ID/Passport number already exists in our system. Contact the office if you need help.';
            }
        }

        if (!empty($errors)) {
            $this->render_form($errors, $_POST);
            return false;
        }

        // Build email — use provided or generate a placeholder
        if (empty($email)) {
            $slug  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name . $last_name));
            $email = $slug . '_' . time() . '@student.mtti.local';
        }

        // Unique username derived from email
        $user_login = strpos($email, '@student.mtti.local') !== false
            ? str_replace('@student.mtti.local', '', $email)
            : strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $email));

        if (username_exists($user_login)) {
            $user_login = $user_login . '_' . substr(uniqid(), -5);
        }

        // Suppress WordPress new-user emails
        add_filter('wp_send_new_user_notification_to_user',  '__return_false');
        add_filter('wp_send_new_user_notification_to_admin', '__return_false');

        $user_id = wp_insert_user(array(
            'user_login'   => $user_login,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'user_pass'    => wp_generate_password(20, true),
            'role'         => 'mtti_student',
        ));

        remove_filter('wp_send_new_user_notification_to_user',  '__return_false');
        remove_filter('wp_send_new_user_notification_to_admin', '__return_false');

        if (is_wp_error($user_id)) {
            $this->render_form(
                array('System error creating account: ' . $user_id->get_error_message()),
                $_POST
            );
            return false;
        }

        update_user_meta($user_id, 'phone', $phone);

        // Generate admission number before creating student record
        $admission_number = $this->db->generate_admission_number($course_id);

        // Create student record
        $student_id = $this->db->create_student(array(
            'user_id'           => $user_id,
            'admission_number'  => $admission_number,
            'course_id'         => $course_id,
            'id_number'         => $id_number,
            'date_of_birth'     => $date_of_birth,
            'gender'            => $gender,
            'phone'             => $phone,
            'address'           => $address,
            'county'            => $county,
            'emergency_contact' => $emergency_contact,
            'emergency_phone'   => $emergency_phone,
            'enrollment_date'   => current_time('mysql'),
            'status'            => 'Active',
        ));

        if (!$student_id) {
            wp_delete_user($user_id);
            $this->render_form(
                array('System error: could not save student record. Please try again or contact the office.'),
                $_POST
            );
            return false;
        }

        // Enroll in selected course + all its active units
        $this->enroll_student($student_id, $course_id);

        // Store student ID in a transient keyed by a random token (valid 2 hours)
        $token = bin2hex(random_bytes(20));
        set_transient('mtti_admission_' . $token, array(
            'student_id'       => $student_id,
            'admission_number' => $admission_number,
        ), 2 * HOUR_IN_SECONDS);

        return $token;
    }

    // ---------------------------------------------------------------
    // ENROLLMENT HELPER
    // ---------------------------------------------------------------

    private function enroll_student($student_id, $course_id) {
        global $wpdb;

        $enrollments_table      = $wpdb->prefix . 'mtti_enrollments';
        $balances_table         = $wpdb->prefix . 'mtti_student_balances';
        $courses_table          = $wpdb->prefix . 'mtti_courses';
        $units_table            = $wpdb->prefix . 'mtti_course_units';
        $unit_enrollments_table = $wpdb->prefix . 'mtti_unit_enrollments';

        $course = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$courses_table} WHERE course_id = %d",
            $course_id
        ));
        if (!$course) return;

        $has_discount_col = !empty($wpdb->get_results(
            "SHOW COLUMNS FROM {$enrollments_table} LIKE 'discount_amount'"
        ));

        $enrollment_data = array(
            'student_id'      => $student_id,
            'course_id'       => $course_id,
            'enrollment_date' => current_time('Y-m-d'),
            'status'          => 'Active',
        );
        if ($has_discount_col) {
            $enrollment_data['discount_amount'] = 0;
        }

        $wpdb->insert($enrollments_table, $enrollment_data);
        $enrollment_id = (int) $wpdb->insert_id;

        if ($enrollment_id) {
            $fee = floatval($course->fee);
            $wpdb->insert(
                $balances_table,
                array(
                    'student_id'      => $student_id,
                    'enrollment_id'   => $enrollment_id,
                    'total_fee'       => $fee,
                    'discount_amount' => 0,
                    'total_paid'      => 0,
                    'balance'         => $fee,
                    'updated_at'      => current_time('mysql'),
                ),
                array('%d', '%d', '%f', '%f', '%f', '%f', '%s')
            );

            // Auto-enroll in all active course units
            $units = $wpdb->get_results($wpdb->prepare(
                "SELECT unit_id FROM {$units_table} WHERE course_id = %d AND status = 'Active'",
                $course_id
            ));
            foreach ($units as $unit) {
                $wpdb->insert($unit_enrollments_table, array(
                    'unit_id'         => $unit->unit_id,
                    'student_id'      => $student_id,
                    'enrollment_date' => current_time('Y-m-d'),
                    'status'          => 'Active',
                ));
            }
        }
    }

    // ---------------------------------------------------------------
    // SUCCESS PAGE
    // ---------------------------------------------------------------

    private function render_success($token) {
        $data             = get_transient('mtti_admission_' . $token);
        $valid            = $data && !empty($data['student_id']);
        $admission_number = $valid ? $data['admission_number'] : '';

        $download_url = $valid
            ? add_query_arg(array(
                'action' => 'mtti_download_letter',
                'token'  => $token,
              ), admin_url('admin-ajax.php'))
            : '';
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            .mtti-success-wrap {
                max-width: 640px;
                margin: 30px auto 40px;
                font-family: 'Poppins', sans-serif;
                color: #1a1a2e;
            }
            .mtti-success-box {
                background: #fff;
                border-radius: 16px;
                padding: 44px 36px;
                text-align: center;
                box-shadow: 0 8px 40px rgba(0,0,0,.1);
                border-top: 5px solid #2E7D32;
            }
            .mtti-sicon { font-size: 68px; margin-bottom: 14px; }
            .mtti-success-box h2 {
                color: #2E7D32;
                font-size: 26px;
                font-weight: 800;
                margin: 0 0 10px;
            }
            .mtti-success-box > p {
                color: #666;
                font-size: 15px;
                margin: 6px 0;
            }
            .mtti-adm-badge {
                font-size: 20px;
                font-weight: 800;
                color: #2E7D32;
                background: rgba(46,125,50,.08);
                border: 2px solid rgba(46,125,50,.2);
                padding: 10px 24px;
                border-radius: 50px;
                display: inline-block;
                margin: 16px 0 4px;
                letter-spacing: 2px;
            }
            .mtti-dl-btn {
                display: inline-block;
                background: linear-gradient(135deg, #2E7D32, #1B5E20);
                color: #fff !important;
                padding: 16px 36px;
                border-radius: 50px;
                font-size: 16px;
                font-weight: 700;
                text-decoration: none;
                margin-top: 20px;
                box-shadow: 0 6px 20px rgba(46,125,50,.35);
                transition: transform .2s, box-shadow .2s;
            }
            .mtti-dl-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 28px rgba(46,125,50,.45);
                color: #fff !important;
            }
            .mtti-next-steps {
                background: #f7f9f7;
                border-radius: 12px;
                padding: 22px 24px;
                margin-top: 28px;
                text-align: left;
                border-left: 4px solid #FF9800;
            }
            .mtti-next-steps h4 {
                margin: 0 0 12px;
                color: #2E7D32;
                font-size: 14px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .5px;
            }
            .mtti-next-steps ol {
                margin: 0 0 0 20px;
                line-height: 2;
                font-size: 14px;
                color: #444;
            }
            .mtti-next-steps strong { color: #1a1a2e; }
            @media (max-width: 580px) {
                .mtti-success-box { padding: 30px 20px; }
            }
        </style>

        <div class="mtti-success-wrap">
            <div class="mtti-success-box">
                <div class="mtti-sicon">&#127881;</div>
                <h2>Application Successful!</h2>
                <p style="color:#555; font-size:15px;">Congratulations! Your application to <strong>M.T.T.I</strong> has been received and processed.</p>

                <?php if ($admission_number): ?>
                <p style="color:#555; font-size:14px; margin-top:14px;">Your Admission Number:</p>
                <div class="mtti-adm-badge"><?php echo esc_html($admission_number); ?></div>
                <p style="font-size:12px; color:#999; margin:4px 0 0;">Write this down &mdash; you will need it for payments and when reporting to the office.</p>
                <?php endif; ?>

                <?php if ($valid): ?>
                <a href="<?php echo esc_url($download_url); ?>" class="mtti-dl-btn">
                    &#x2B07; Download Your Admission Letter (PDF)
                </a>
                <p style="font-size:12px; color:#aaa; margin-top:8px;">Download link expires in 2 hours. Save your letter now.</p>
                <?php else: ?>
                <p style="color:#c00; font-size:14px;">Download link has expired. Please contact the office with your admission number.</p>
                <?php endif; ?>

                <div class="mtti-next-steps">
                    <h4>Next Steps</h4>
                    <ol>
                        <li>Download and <strong>print</strong> your admission letter</li>
                        <li>Pay at least <strong>50% of the course fee</strong> via M-Pesa Paybill <strong>880100</strong>, Account <strong>219391</strong></li>
                        <li>Report to the office with your letter, a copy of your <strong>National ID</strong>, and <strong>2 passport photos</strong></li>
                        <li>Get your class schedule and start learning!</li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }

    // ---------------------------------------------------------------
    // PDF GENERATION
    // ---------------------------------------------------------------

    private function generate_pdf($student_id) {
        $autoload = MTTI_MIS_PLUGIN_DIR . 'vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('MTTI Admission Form: dompdf autoload not found at ' . $autoload);
            return false;
        }
        require_once $autoload;

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled',      false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont',          'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = $this->build_letter_html($student_id);

        if (!$html) return false;

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // ---------------------------------------------------------------
    // ADMISSION LETTER HTML (dompdf-safe, table-based layout)
    // ---------------------------------------------------------------

    private function build_letter_html($student_id) {
        global $wpdb;

        $students_table    = $wpdb->prefix . 'mtti_students';
        $courses_table     = $wpdb->prefix . 'mtti_courses';
        $enrollments_table = $wpdb->prefix . 'mtti_enrollments';
        $balances_table    = $wpdb->prefix . 'mtti_student_balances';
        $payments_table    = $wpdb->prefix . 'mtti_payments';

        $student = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.display_name, u.user_email
             FROM {$students_table} s
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             WHERE s.student_id = %d",
            $student_id
        ));

        if (!$student) return false;

        // Get enrolled courses (same logic as admin letter)
        $enrolled_courses = $wpdb->get_results($wpdb->prepare(
            "SELECT c.course_id, c.course_name, c.course_code, c.fee, c.duration_weeks, c.category,
                    e.enrollment_id,
                    GREATEST(
                        COALESCE(b.discount_amount, 0),
                        COALESCE(pd.payment_discount, 0)
                    ) AS discount_amount
             FROM   {$enrollments_table} e
             JOIN   {$courses_table}     c  ON c.course_id     = e.course_id
             LEFT JOIN {$balances_table} b  ON b.enrollment_id = e.enrollment_id
             LEFT JOIN (
                 SELECT enrollment_id, SUM(discount) AS payment_discount
                 FROM   {$payments_table}
                 WHERE  status = 'Completed'
                 GROUP  BY enrollment_id
             ) pd ON pd.enrollment_id = e.enrollment_id
             WHERE  e.student_id = %d
             ORDER  BY e.enrollment_date DESC",
            $student_id
        ));

        if (empty($enrolled_courses) && $student->course_id) {
            $course = $wpdb->get_row($wpdb->prepare(
                "SELECT *, 0 AS discount_amount FROM {$courses_table} WHERE course_id = %d",
                $student->course_id
            ));
            if ($course) $enrolled_courses = array($course);
        }

        // Fee calculations (mirrors admin admission letter exactly)
        $total_discount = 0;
        foreach ($enrolled_courses as $ec) {
            $total_discount += floatval($ec->discount_amount);
        }

        $excluded_courses       = array('computer applications', 'computer essentials');
        $total_tuition          = 0;
        $requires_admission_fee = false;

        foreach ($enrolled_courses as $ec) {
            $stored = floatval($wpdb->get_var($wpdb->prepare(
                "SELECT total_fee FROM {$balances_table} WHERE enrollment_id = %d LIMIT 1",
                $ec->enrollment_id
            )));
            $ec->fee        = $stored > 0 ? $stored : floatval($ec->fee);
            $total_tuition += $ec->fee;

            $cn       = strtolower($ec->course_name);
            $excluded = false;
            foreach ($excluded_courses as $excl) {
                if (strpos($cn, $excl) !== false) { $excluded = true; break; }
            }
            if (!$excluded) $requires_admission_fee = true;
        }

        $admission_fee = $requires_admission_fee ? 1500 : 0;
        $total_payable = $total_tuition + $admission_fee - $total_discount;

        // Institute settings
        $settings       = get_option('mtti_mis_settings', array());
        $institute_name = $settings['institute_name']    ?? 'Masomotele Technical Training Institute';
        $inst_address   = $settings['institute_address'] ?? 'Sagaas Center, Fourth Floor, Eldoret, Kenya';
        $inst_phone     = $settings['institute_phone']   ?? '0712464936';
        $inst_email     = $settings['institute_email']   ?? 'info@masomoteletraining.co.ke';
        $inst_website   = $settings['institute_website'] ?? 'masomoteletraining.co.ke';

        // Embed logo as base64 (dompdf can't fetch remote URLs)
        $logo_path = MTTI_MIS_PLUGIN_DIR . 'assets/images/logo.jpeg';
        $logo_src  = '';
        if (file_exists($logo_path)) {
            $logo_src = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logo_path));
        }

        $student_name = $student->display_name ?: 'Student';
        $first_name   = explode(' ', trim($student_name))[0];

        $course_names = array_map(function($c) { return strtoupper($c->course_name); }, $enrolled_courses);
        $courses_text = !empty($course_names) ? implode(', ', $course_names) : 'YOUR SELECTED COURSE(S)';

        $is_health = false;
        foreach ($enrolled_courses as $ec) {
            $cn = strtolower($ec->course_name);
            if (strpos($cn, 'health') !== false || strpos($cn, 'nursing') !== false || strpos($cn, 'cna') !== false) {
                $is_health = true;
                break;
            }
        }

        // Load stamp and signature images saved in admin settings
        $stamp_b64 = get_option('mtti_admission_stamp_b64', '');
        $sig_b64   = get_option('mtti_admission_signature_b64', '');

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
/* @page handles top/bottom margins; left/right via body padding (more reliable in dompdf) */
@page {
    margin-top:    15mm;
    margin-right:  0;
    margin-bottom: 15mm;
    margin-left:   0;
}
* { margin: 0; padding: 0; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8.5pt; color: #222; line-height: 1.5;
       padding-left: 16mm; padding-right: 16mm; }

/* HEADER */
table.hdr { width: 100%; border-collapse: collapse; border-bottom: 3px solid #1F4E79; margin-bottom: 10px; }
table.hdr td { border: none; padding-bottom: 8px; }
.inst-name { color: #1F4E79; font-size: 13pt; font-weight: bold; }
.tveta     { color: #2E7D32; font-size: 8pt;  font-weight: bold; }
.inst-info { font-size: 7.5pt; color: #555; }
.motto     { font-style: italic; color: #C00000; font-size: 8.5pt; font-weight: bold; }

/* LETTER TITLE BAR — table for dompdf reliability */
table.ltitle { width: 100%; border-collapse: collapse; margin: 8px 0; border-left: 4px solid #1F4E79; border-right: 4px solid #1F4E79; }
table.ltitle td { text-align: center; font-size: 11pt; font-weight: bold; color: #1F4E79;
                  padding: 5px 10px; background: #f0f4f8; }

/* SECTION HEADINGS — table for dompdf reliability */
table.sh { width: 100%; border-collapse: collapse; margin: 9px 0 3px; }
table.sh td { color: #fff; font-weight: bold; font-size: 8pt; letter-spacing: 0.3px;
              padding: 4px 8px; background: #1F4E79; }

/* INFO TABLE (2-col rows) */
table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 8pt; }
table.info th { background: #f0f4f8; font-weight: bold; color: #444;
                border: 1px solid #d0d8e4; padding: 4px 7px; width: 22%; white-space: nowrap; }
table.info td { border: 1px solid #d0d8e4; padding: 4px 7px; }
.red   { color: #C00000; font-weight: bold; }
.blue  { color: #1F4E79; font-weight: bold; }
.green { color: #2E7D32; font-weight: bold; }
.amt   { text-align: right; font-weight: bold; }
.total-row th, .total-row td { background: #e8f5e9; color: #2E7D32; font-size: 9pt; font-weight: bold; }
.disc-row  th, .disc-row  td { background: #fff3e0; color: #e65100; }
.sub-row   th, .sub-row   td { background: #e3f2fd; font-weight: bold; }

/* PAYMENT SIDE-BY-SIDE */
table.pay2 { width: 100%; border-collapse: collapse; margin: 3px 0 5px; }
table.pay2 td { border: none; vertical-align: top; padding: 0; }
table.pay2 td.p-left  { padding-right: 3px; }
table.pay2 td.p-right { padding-left:  3px; }
table.pbox { width: 100%; border-collapse: collapse; }
table.pbox td { font-size: 7.5pt; padding: 6px 9px; }
table.pbox-m td { background: #e8f5e9; border-left: 4px solid #4CAF50; }
table.pbox-b td { background: #e3f2fd; border-left: 4px solid #1565C0; }
.pbox-title { font-weight: bold; font-size: 7.5pt; }
table.prows { width: 100%; border-collapse: collapse; }
table.prows td { border: none; padding: 2px 0; font-size: 7.5pt; }
.pval-m { font-weight: bold; font-size: 10pt; color: #2E7D32; }
.pval-b { font-weight: bold; font-size: 8.5pt; color: #1565C0; }

/* WARN — table-based for dompdf reliability */
table.warn { width: 100%; border-collapse: collapse; margin: 4px 0 7px; }
table.warn td { background: #fff8e1; border-left: 3px solid #f9a825; padding: 5px 8px; font-size: 7.5pt; }

/* INFO LIST */
ul.ilist { margin: 3px 0 0 16px; }
ul.ilist li { margin: 2px 0; font-size: 8pt; }

/* SIGNATURE TABLE — plain table, no absolute positioning */
table.sigt { width: 100%; border-collapse: collapse; margin-top: 14px; }
table.sigt td { border: none; vertical-align: bottom; padding: 2px 0; }
.sigline { border-bottom: 1px solid #333; width: 155px; height: 28px; margin: 3px 0; }

/* FOOTER LINE — table for dompdf reliability */
table.ftr { width: 100%; border-collapse: collapse; margin-top: 10px; border-top: 2px solid #1F4E79; }
table.ftr td { padding-top: 5px; text-align: center; font-size: 7.5pt; color: #666; }
</style>
</head>
<body>

<!-- ═══════════ HEADER ═══════════ -->
<table class="hdr">
<tr>
<?php if ($logo_src): ?>
<td style="width:72px; vertical-align:middle; text-align:center; padding-right:8px;">
    <img src="<?php echo esc_attr($logo_src); ?>" style="width:64px; height:auto;" alt="Logo">
</td>
<?php endif; ?>
<td style="text-align:center; vertical-align:middle;">
    <div class="inst-name"><?php echo esc_html(strtoupper($institute_name)); ?></div>
    <div class="tveta">TVETA Accredited Institution</div>
    <div class="inst-info"><?php echo esc_html($inst_address); ?></div>
    <div class="inst-info">Tel: <?php echo esc_html($inst_phone); ?> | Email: <?php echo esc_html($inst_email); ?> | <?php echo esc_html($inst_website); ?></div>
    <div class="motto">"Start Learning, Start Earning"</div>
</td>
</tr>
</table>

<!-- ═══════════ TITLE ═══════════ -->
<table class="ltitle"><tr><td>LETTER OF ADMISSION</td></tr></table>

<!-- REF / DATE / RECIPIENT -->
<table style="width:100%; border:none; border-collapse:collapse; margin-bottom:7px;">
<tr>
<td style="border:none; font-size:8pt; vertical-align:top; width:50%;">
    <strong>Ref No:</strong> <span class="red"><?php echo esc_html($student->admission_number); ?></span><br>
    <strong>Date:</strong> <?php echo date('j F Y'); ?>
</td>
<td style="border:none; text-align:right; font-size:8pt; vertical-align:top; width:50%;">
    <span class="red"><?php echo esc_html($student_name); ?></span>
</td>
</tr>
</table>

<p style="font-size:8.5pt; margin-bottom:4px;">Dear <span class="red"><?php echo esc_html($first_name); ?></span>,</p>
<p style="font-size:8.5pt; margin-bottom:4px;"><strong>RE: OFFER OF ADMISSION TO <span class="blue" style="text-decoration:underline;"><?php echo esc_html($courses_text); ?></span></strong></p>
<p style="font-size:8pt; margin-bottom:8px;">We are pleased to inform you that your application to <strong><?php echo esc_html($institute_name); ?></strong> has been <span class="green">SUCCESSFUL</span>. Please review your admission details, course information and fee structure below.</p>

<!-- ═══════════ 1. STUDENT INFO ═══════════ -->
<table class="sh"><tr><td>1. STUDENT INFORMATION</td></tr></table>
<table class="info" style="margin-top:3px;">
<tr>
    <th>Admission No.</th>
    <td><strong><?php echo esc_html($student->admission_number); ?></strong></td>
    <th>Full Name</th>
    <td><span class="red"><?php echo esc_html($student_name); ?></span></td>
</tr>
<tr>
    <th>ID / Passport</th>
    <td><?php echo esc_html(!empty($student->id_number) ? $student->id_number : '—'); ?></td>
    <th>Date of Birth</th>
    <td><?php echo !empty($student->date_of_birth) ? date('j M Y', strtotime($student->date_of_birth)) : '—'; ?></td>
</tr>
<tr>
    <th>Gender</th>
    <td><?php echo esc_html(!empty($student->gender) ? $student->gender : '—'); ?></td>
    <th>Phone</th>
    <td><?php echo esc_html(!empty($student->phone) ? $student->phone : '—'); ?></td>
</tr>
</table>

<!-- ═══════════ 2. COURSE DETAILS ═══════════ -->
<table class="sh"><tr><td>2. COURSE DETAILS</td></tr></table>
<?php foreach ($enrolled_courses as $idx => $ec): ?>
<?php if (count($enrolled_courses) > 1): ?><p style="font-size:8pt; font-weight:bold; color:#1F4E79; margin:5px 0 2px;">Course <?php echo $idx + 1; ?>:</p><?php endif; ?>
<table class="info" style="margin-top:3px;">
<tr>
    <th>Course Name</th>
    <td><strong><?php echo esc_html($ec->course_name); ?></strong></td>
    <th>Code</th>
    <td><?php echo esc_html($ec->course_code); ?></td>
</tr>
<tr>
    <th>Duration</th>
    <td><?php echo esc_html($ec->duration_weeks); ?> Weeks</td>
    <th>Mode of Study</th>
    <td>Full-Time / Part-Time</td>
</tr>
</table>
<?php endforeach; ?>

<!-- ═══════════ 3. FEE STRUCTURE ═══════════ -->
<table class="sh"><tr><td>3. FEE STRUCTURE</td></tr></table>
<table class="info" style="margin-top:3px;">
<?php if (count($enrolled_courses) > 1): ?>
    <?php foreach ($enrolled_courses as $ec): ?>
    <tr><th><?php echo esc_html($ec->course_name); ?></th><td colspan="3" class="amt">KES <?php echo number_format($ec->fee, 2); ?></td></tr>
    <?php if (floatval($ec->discount_amount) > 0): ?>
    <tr class="disc-row"><th style="padding-left:12px;">Discount</th><td colspan="3" class="amt">- KES <?php echo number_format($ec->discount_amount, 2); ?></td></tr>
    <?php endif; ?>
    <?php endforeach; ?>
    <tr class="sub-row"><th colspan="3"><strong>Sub-Total (Tuition)</strong></th><td class="amt">KES <?php echo number_format($total_tuition - $total_discount, 2); ?></td></tr>
<?php else: ?>
    <tr><th>Tuition Fee</th><td colspan="3" class="amt">KES <?php echo number_format($total_tuition, 2); ?></td></tr>
    <?php if ($total_discount > 0): ?>
    <tr class="disc-row"><th style="padding-left:12px;">Discount / Scholarship</th><td colspan="3" class="amt">- KES <?php echo number_format($total_discount, 2); ?></td></tr>
    <?php endif; ?>
<?php endif; ?>
<?php if ($requires_admission_fee): ?>
<tr><th>Admission Fee</th><td colspan="3" class="amt">KES <?php echo number_format($admission_fee, 2); ?></td></tr>
<?php endif; ?>
<tr><th>Examination Fee</th><td colspan="3" class="amt">Included</td></tr>
<tr class="total-row"><th colspan="3"><strong>TOTAL PAYABLE</strong></th><td class="amt">KES <?php echo number_format($total_payable, 2); ?></td></tr>
</table>

<!-- ═══════════ 4. PAYMENT INSTRUCTIONS ═══════════ -->
<table class="sh"><tr><td>4. PAYMENT INSTRUCTIONS</td></tr></table>
<table class="pay2" style="margin-top:4px;">
<tr>
<td class="p-left" style="width:50%;">
    <table class="pbox pbox-m"><tr><td>
        <strong class="pbox-title">M-Pesa Paybill</strong>
        <table class="prows">
            <tr><td style="color:#555;">Paybill No:</td><td class="pval-m">880100</td></tr>
            <tr><td style="color:#555;">Account No:</td><td class="pval-m">219391</td></tr>
        </table>
    </td></tr></table>
</td>
<td class="p-right" style="width:50%;">
    <table class="pbox pbox-b"><tr><td>
        <strong class="pbox-title">Bank Transfer — NCBA Bank</strong>
        <table class="prows">
            <tr><td style="color:#555;">Account No:</td><td class="pval-b">1006329155</td></tr>
            <tr><td style="color:#555;">Account Name:</td><td class="pval-b" style="font-size:7pt;"><?php echo esc_html($institute_name); ?></td></tr>
        </table>
    </td></tr></table>
</td>
</tr>
</table>
<table class="warn"><tr><td>
    <strong>Note:</strong> For M-Pesa, always use account number <strong>219391</strong>. Keep your payment receipt as proof. At least <strong>50% must be paid before intake</strong>.
</td></tr></table>

<!-- ═══════════ 5. IMPORTANT INFORMATION ═══════════ -->
<table class="sh"><tr><td>5. IMPORTANT INFORMATION</td></tr></table>
<ul class="ilist" style="margin-top:4px;">
    <li>Bring this letter, a copy of your <strong>National ID / Passport</strong> and <strong>2 passport-size photos</strong></li>
    <li>Report to the <strong>Administration Office</strong> on your first day for orientation and class allocation</li>
    <li>Class sessions: <strong>Morning, Afternoon or Weekend</strong> (Mon–Sat, 8:00 AM – 8:00 PM)</li>
    <li>Maintain <strong>100% attendance</strong> to qualify for your certificate</li>
    <li>Dress code: <strong>Smart casual</strong> | Phones on <strong>silent mode</strong> during class</li>
    <?php if ($is_health): ?>
    <li>CNA / Health students: bring <strong>1 ream of A4 paper</strong> for practical assessments</li>
    <?php endif; ?>
    <?php if (!empty($student->emergency_contact)): ?>
    <li>Emergency contact on record: <strong><?php echo esc_html($student->emergency_contact); ?></strong><?php if (!empty($student->emergency_phone)): ?>, <?php echo esc_html($student->emergency_phone); ?><?php endif; ?></li>
    <?php endif; ?>
</ul>

<p style="font-size:8.5pt; margin:9px 0 3px;">We look forward to welcoming you to <strong><?php echo esc_html($institute_name); ?></strong> and supporting your journey towards meaningful skills and employment.</p>
<p style="font-size:8.5pt; margin-bottom:2px;"><span class="green">Congratulations once again on your admission!</span></p>

<!-- ═══════════ SIGNATURE ═══════════ -->
<table class="sigt" style="margin-top:14px;">
<tr>
<td style="width:55%; vertical-align:bottom;">
    <p style="font-size:8pt; margin-bottom:3px;">Yours faithfully,</p>
    <?php if ($sig_b64): ?>
    <img src="<?php echo esc_attr($sig_b64); ?>"
         style="display:block; max-width:155px; max-height:50px; width:auto; height:auto; margin-bottom:2px;"
         alt="Signature">
    <?php else: ?>
    <div class="sigline"></div>
    <?php endif; ?>
    <p style="font-size:8pt; margin-top:2px;"><strong>Principal / Director</strong></p>
    <p style="font-size:7.5pt; color:#555;"><?php echo esc_html($institute_name); ?></p>
</td>
<td style="width:45%; vertical-align:bottom; text-align:center;">
    <?php if ($stamp_b64): ?>
    <img src="<?php echo esc_attr($stamp_b64); ?>"
         style="display:block; margin:0 auto; max-width:100px; max-height:100px; width:auto; height:auto;"
         alt="Official Stamp">
    <?php else: ?>
    <table style="border:1.5px dashed #bbb; border-collapse:collapse; width:100px; margin:0 auto;">
    <tr><td style="border:none; text-align:center; padding:18px 8px; font-size:7.5pt; color:#aaa;">OFFICIAL<br>STAMP</td></tr>
    </table>
    <?php endif; ?>
</td>
</tr>
</table>

<!-- ═══════════ FOOTER ═══════════ -->
<table class="ftr"><tr><td>
    <strong style="color:#1F4E79;"><?php echo esc_html($institute_name); ?></strong>
    &nbsp;&#124;&nbsp;
    <em style="color:#C00000;">"Start Learning, Start Earning"</em>
</td></tr></table>

</body>
</html>
        <?php
        return ob_get_clean();
    }

    // ---------------------------------------------------------------
    // KENYA COUNTIES LIST
    // ---------------------------------------------------------------

    private function get_kenya_counties() {
        return array(
            'Baringo','Bomet','Bungoma','Busia','Elgeyo-Marakwet','Embu','Garissa',
            'Homa Bay','Isiolo','Kajiado','Kakamega','Kericho','Kiambu','Kilifi',
            'Kirinyaga','Kisii','Kisumu','Kitui','Kwale','Laikipia','Lamu','Machakos',
            'Makueni','Mandera','Marsabit','Meru','Migori','Mombasa','Murang\'a',
            'Nairobi','Nakuru','Nandi','Narok','Nyamira','Nyandarua','Nyeri',
            'Samburu','Siaya','Taita-Taveta','Tana River','Tharaka-Nithi','Trans Nzoia',
            'Turkana','Uasin Gishu','Vihiga','Wajir','West Pokot',
        );
    }
}
