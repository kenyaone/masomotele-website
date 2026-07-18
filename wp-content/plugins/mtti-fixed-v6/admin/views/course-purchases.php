<?php
/**
 * Course Purchases — online self-checkout requests (see [mtti_course_checkout]).
 * Bridge screen until a real M-Pesa webhook is wired up: staff confirm a
 * payment happened (M-Pesa receipt), then "Complete Enrollment" runs the
 * exact same mtti_mis_complete_course_purchase() a live webhook would call.
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_mtti') && !current_user_can('manage_options')) wp_die('Access denied.');

global $wpdb;

// Handle "mark paid & enroll" action
if (isset($_POST['mtti_complete_purchase']) && isset($_POST['purchase_id'])) {
    check_admin_referer('mtti_complete_purchase_action', 'mtti_complete_purchase_nonce');
    $purchase_id = intval($_POST['purchase_id']);
    $receipt = sanitize_text_field($_POST['mpesa_receipt'] ?? '');
    if ($receipt) {
        $wpdb->update($wpdb->prefix . 'mtti_course_purchases', array('mpesa_receipt' => $receipt), array('purchase_id' => $purchase_id));
    }
    $result = mtti_mis_complete_course_purchase($purchase_id);
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>Could not complete enrollment: ' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>✅ Enrolled — student_id ' . intval($result) . '. <a href="' . esc_url(admin_url('admin.php?page=mtti-mis-students&action=view&id=' . intval($result))) . '">View student</a></p></div>';
    }
}

// Handle cancel
if (isset($_POST['mtti_cancel_purchase']) && isset($_POST['purchase_id'])) {
    check_admin_referer('mtti_cancel_purchase_action', 'mtti_cancel_purchase_nonce');
    $wpdb->update($wpdb->prefix . 'mtti_course_purchases', array('status' => 'cancelled'), array('purchase_id' => intval($_POST['purchase_id'])));
    echo '<div class="notice notice-success"><p>Purchase cancelled.</p></div>';
}

$purchases = $wpdb->get_results(
    "SELECT p.*, c.course_name, c.course_code
     FROM {$wpdb->prefix}mtti_course_purchases p
     LEFT JOIN {$wpdb->prefix}mtti_courses c ON c.course_id = p.course_id
     ORDER BY p.created_at DESC"
);
?>
<div class="wrap">
    <h1>Course Purchases</h1>
    <div class="notice notice-info">
        <p><strong>💡 How this works right now:</strong> a prospective online learner fills in the
        <code>[mtti_course_checkout]</code> form, picks a course, and lands here as "Awaiting Payment" with a
        reference code. Once you've confirmed their M-Pesa payment (there's no automatic gateway wired in yet),
        enter the M-Pesa receipt code and click <strong>Complete Enrollment</strong> — this creates their account,
        enrolls them (online, fully paid) in the chosen course, and enrolls them in every unit, exactly like the
        admission form does. When a real M-Pesa integration is connected later, it can call this same completion
        step automatically instead of requiring a click here.</p>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Course</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($purchases) : foreach ($purchases as $p) : ?>
            <tr>
                <td><code><?php echo esc_html($p->reference_code); ?></code></td>
                <td><?php echo esc_html($p->first_name . ' ' . $p->last_name); ?><?php if ($p->email) : ?><br><span style="color:#888;font-size:12px;"><?php echo esc_html($p->email); ?></span><?php endif; ?></td>
                <td><?php echo esc_html($p->phone); ?></td>
                <td><?php echo esc_html($p->course_name ?: '—'); ?> <span style="color:#888;">(<?php echo esc_html($p->course_code); ?>)</span></td>
                <td>KES <?php echo number_format($p->amount, 2); ?></td>
                <td>
                    <?php
                    $badge = array(
                        'awaiting_payment' => '#e65100',
                        'paid'             => '#1976d2',
                        'enrolled'         => '#2e7d32',
                        'cancelled'        => '#999',
                    );
                    $color = $badge[$p->status] ?? '#666';
                    ?>
                    <span style="color:<?php echo esc_attr($color); ?>;font-weight:600;"><?php echo esc_html(ucwords(str_replace('_', ' ', $p->status))); ?></span>
                    <?php if ($p->status === 'enrolled' && $p->student_id) : ?>
                        <br><a href="<?php echo esc_url(admin_url('admin.php?page=mtti-mis-students&action=view&id=' . intval($p->student_id))); ?>" style="font-size:12px;">View student →</a>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html(date('M j, Y g:ia', strtotime($p->created_at))); ?></td>
                <td>
                    <?php if ($p->status === 'awaiting_payment' || $p->status === 'paid') : ?>
                    <form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        <?php wp_nonce_field('mtti_complete_purchase_action', 'mtti_complete_purchase_nonce'); ?>
                        <input type="hidden" name="purchase_id" value="<?php echo intval($p->purchase_id); ?>">
                        <input type="text" name="mpesa_receipt" placeholder="M-Pesa receipt" style="width:120px;" value="<?php echo esc_attr($p->mpesa_receipt); ?>">
                        <button type="submit" name="mtti_complete_purchase" class="button button-primary button-small" onclick="return confirm('Confirm payment received and enroll this student?');">Complete Enrollment</button>
                    </form>
                    <form method="post" style="margin-top:4px;">
                        <?php wp_nonce_field('mtti_cancel_purchase_action', 'mtti_cancel_purchase_nonce'); ?>
                        <input type="hidden" name="purchase_id" value="<?php echo intval($p->purchase_id); ?>">
                        <button type="submit" name="mtti_cancel_purchase" class="button button-small" onclick="return confirm('Cancel this purchase request?');">Cancel</button>
                    </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr><td colspan="8">No course purchase requests yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
