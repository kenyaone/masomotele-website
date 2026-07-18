<?php
/**
 * Online self-checkout – shortcode [mtti_course_checkout]
 *
 * For purely-online learners: pick a course, see its fee, leave contact
 * details, and land in an "awaiting payment" state. No student/enrollment
 * record is created yet at this point — a mtti_course_purchases row is,
 * with a shareable reference code. Enrollment is created automatically
 * (mtti_mis_complete_course_purchase() in mtti-mis.php) only once that
 * purchase is marked paid, currently done from the admin "Course
 * Purchases" screen and intended to be triggered by a real M-Pesa webhook
 * once those credentials are wired in — the enrollment logic itself
 * doesn't change either way.
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Public_Course_Checkout {

    public function render_shortcode() {
        global $wpdb;

        $courses = $wpdb->get_results(
            "SELECT course_id, course_code, course_name, fee, online_fee, duration_weeks
             FROM {$wpdb->prefix}mtti_courses
             WHERE status = 'Active' AND is_active = 1 AND deleted_at IS NULL
               AND course_name IS NOT NULL
             ORDER BY course_name"
        );
        // Purely-online checkout charges the online fee when one is set,
        // falling back to the standard (campus) fee for courses that don't
        // have a separate online price yet.
        foreach ($courses as $c) {
            $c->effective_fee = (!is_null($c->online_fee) && $c->online_fee > 0) ? $c->online_fee : $c->fee;
        }

        ob_start();

        if (isset($_POST['mtti_checkout_submit']) && wp_verify_nonce($_POST['mtti_checkout_nonce'] ?? '', 'mtti_checkout_action')) {
            $purchase = $this->process_submission($courses);
            if ($purchase) {
                $this->render_pending_screen($purchase);
                return ob_get_clean();
            }
            // fall through to re-render the form with errors on failure
        }

        $this->render_form($courses, $this->last_errors ?? array(), $_POST);
        return ob_get_clean();
    }

    private $last_errors = array();

    private function process_submission($courses) {
        global $wpdb;

        $course_id  = intval($_POST['course_id'] ?? 0);
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name']  ?? '');
        $phone      = sanitize_text_field($_POST['phone']      ?? '');
        $email      = sanitize_email($_POST['email']           ?? '');

        $errors = array();
        if (!$first_name) $errors[] = 'First name is required.';
        if (!$last_name)  $errors[] = 'Last name is required.';
        if (!$phone)      $errors[] = 'Phone number is required.';

        $course = null;
        foreach ($courses as $c) {
            if ((int) $c->course_id === $course_id) { $course = $c; break; }
        }
        if (!$course) $errors[] = 'Please choose a course.';

        if (!empty($errors)) {
            $this->last_errors = $errors;
            return null;
        }

        // Human-shareable reference — student/staff can quote this over
        // the phone or WhatsApp until self-service payment is wired in.
        $reference_code = 'MTTI-' . strtoupper(substr(str_replace('-', '', wp_generate_uuid4()), 0, 8));

        $wpdb->insert($wpdb->prefix . 'mtti_course_purchases', array(
            'reference_code' => $reference_code,
            'course_id'      => $course->course_id,
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'email'          => $email,
            'phone'          => $phone,
            'amount'         => $course->effective_fee,
            'status'         => 'awaiting_payment',
        ));
        $purchase_id = (int) $wpdb->insert_id;

        // Try to hand off to PesaPal for real online payment. If it fails
        // for any reason (API down, bad credentials, etc.) we fall back to
        // the old "we'll confirm your M-Pesa payment manually" flow rather
        // than blocking the whole enrollment.
        $order = MTTI_MIS_Pesapal::submit_order(array(
            'merchant_reference' => $reference_code,
            'amount'             => $course->effective_fee,
            'description'        => $course->course_name . ' (' . $course->course_code . ')',
            'email'              => $email,
            'phone'              => $phone,
            'first_name'         => $first_name,
            'last_name'          => $last_name,
        ));

        if (!is_wp_error($order)) {
            $wpdb->update(
                $wpdb->prefix . 'mtti_course_purchases',
                array('pesapal_tracking_id' => $order['order_tracking_id']),
                array('purchase_id' => $purchase_id)
            );
            wp_redirect($order['redirect_url']);
            exit;
        }

        error_log('MTTI MIS: PesaPal submit_order failed — ' . $order->get_error_message());

        return array(
            'reference_code' => $reference_code,
            'course_name'    => $course->course_name,
            'course_code'    => $course->course_code,
            'amount'         => $course->effective_fee,
            'first_name'     => $first_name,
        );
    }

    private function render_form($courses, $errors, $old) {
        ?>
        <div class="mtti-checkout-wrap">
            <style>
                .mtti-checkout-wrap { max-width: 560px; margin: 0 auto; font-family: -apple-system, "Segoe UI", Roboto, sans-serif; }
                .mtti-checkout-card { background: #fff; border: 1px solid #e0e4dc; border-radius: 12px; padding: 28px 26px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
                .mtti-checkout-card h2 { margin: 0 0 6px; color: #1B5E20; font-size: 22px; }
                .mtti-checkout-sub { color: #667; margin: 0 0 22px; font-size: 14px; }
                .mtti-co-field { margin-bottom: 16px; }
                .mtti-co-field label { display: block; font-weight: 600; font-size: 13px; color: #445; margin-bottom: 5px; }
                .mtti-co-field input, .mtti-co-field select {
                    width: 100%; padding: 10px 12px; border: 1.5px solid #dde3dd; border-radius: 8px;
                    font-size: 14px; box-sizing: border-box;
                }
                .mtti-co-row { display: flex; gap: 12px; }
                .mtti-co-row .mtti-co-field { flex: 1; }
                .mtti-checkout-fee { background: #eaf3ea; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: #1B5E20; margin-bottom: 16px; display: none; }
                .mtti-checkout-btn {
                    width: 100%; padding: 13px; background: #2E7D32; color: #fff; border: none; border-radius: 8px;
                    font-size: 15px; font-weight: 600; cursor: pointer;
                }
                .mtti-checkout-btn:hover { background: #1B5E20; }
                .mtti-checkout-errors { background: #fdecea; border-left: 4px solid #c62828; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 13px; color: #c62828; }
            </style>
            <div class="mtti-checkout-card">
                <h2>🎓 Enroll Online</h2>
                <p class="mtti-checkout-sub">Choose your course and leave your details — we'll confirm your fee and get you learning.</p>

                <?php if (!empty($errors)) : ?>
                <div class="mtti-checkout-errors">
                    <?php foreach ($errors as $e) echo '<div>' . esc_html($e) . '</div>'; ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field('mtti_checkout_action', 'mtti_checkout_nonce'); ?>
                    <div class="mtti-co-field">
                        <label>Course</label>
                        <select name="course_id" id="mtti_co_course" required onchange="mttiCoShowFee(this)">
                            <option value="">-- Select a course --</option>
                            <?php foreach ($courses as $c) : ?>
                            <option value="<?php echo esc_attr($c->course_id); ?>" data-fee="<?php echo esc_attr($c->effective_fee); ?>"
                                <?php selected($old['course_id'] ?? '', $c->course_id); ?>>
                                <?php echo esc_html($c->course_name . ' (' . $c->course_code . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mtti-checkout-fee" id="mtti_co_fee"></div>
                    </div>
                    <div class="mtti-co-row">
                        <div class="mtti-co-field">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo esc_attr($old['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mtti-co-field">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo esc_attr($old['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mtti-co-row">
                        <div class="mtti-co-field">
                            <label>Phone (M-Pesa number)</label>
                            <input type="tel" name="phone" placeholder="07XXXXXXXX" value="<?php echo esc_attr($old['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="mtti-co-field">
                            <label>Email (optional)</label>
                            <input type="email" name="email" value="<?php echo esc_attr($old['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" name="mtti_checkout_submit" class="mtti-checkout-btn">Continue to Payment</button>
                </form>
            </div>
        </div>
        <script>
        function mttiCoShowFee(sel) {
            var box = document.getElementById('mtti_co_fee');
            var opt = sel.options[sel.selectedIndex];
            var fee = opt ? opt.getAttribute('data-fee') : null;
            if (fee) {
                box.style.display = 'block';
                box.innerHTML = '<strong>Online fee:</strong> KES ' + Number(fee).toLocaleString();
            } else {
                box.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            var sel = document.getElementById('mtti_co_course');
            if (sel && sel.value) mttiCoShowFee(sel);
        });
        </script>
        <?php
    }

    private function render_pending_screen($purchase) {
        ?>
        <div class="mtti-checkout-wrap" style="max-width:560px;margin:0 auto;font-family:-apple-system,'Segoe UI',Roboto,sans-serif;">
            <div style="background:#fff;border:1px solid #e0e4dc;border-radius:12px;padding:32px 28px;box-shadow:0 2px 10px rgba(0,0,0,.05);text-align:center;">
                <div style="font-size:44px;margin-bottom:10px;">⏳</div>
                <h2 style="margin:0 0 8px;color:#1B5E20;">Almost there, <?php echo esc_html($purchase['first_name']); ?>!</h2>
                <p style="color:#556;margin:0 0 20px;">
                    Your spot in <strong><?php echo esc_html($purchase['course_name']); ?></strong>
                    (<?php echo esc_html($purchase['course_code']); ?>) is reserved. Online payment isn't switched on
                    just yet — our team will reach out shortly to confirm your M-Pesa payment and activate your account.
                </p>
                <div style="background:#eaf3ea;border-radius:8px;padding:16px;margin-bottom:20px;">
                    <div style="font-size:12px;color:#667;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Amount due</div>
                    <div style="font-size:26px;font-weight:700;color:#1B5E20;">KES <?php echo number_format($purchase['amount']); ?></div>
                </div>
                <div style="background:#f5f7fb;border-radius:8px;padding:14px;">
                    <div style="font-size:12px;color:#667;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Your reference</div>
                    <div style="font-size:18px;font-weight:700;font-family:monospace;letter-spacing:.05em;"><?php echo esc_html($purchase['reference_code']); ?></div>
                </div>
                <p style="color:#889;font-size:12.5px;margin-top:20px;">Quote this reference if you contact the MTTI office about your enrollment.</p>
            </div>
        </div>
        <?php
    }
}
