<?php
defined('ABSPATH') || die();
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Tickets.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';

$action_for = 'st';
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
if (isset($change_action)) {
	$action_for = $set_action_for;
}

$student_id = isset($_GET['student_id']) ? absint($_GET['student_id']) : 0;
if ($student) {
	$school_id = $student->school_id;
	if (!$student_id) {
		$student_id = $student->ID;
	}
}

// Fetch student tickets with assigned staff details
$tickets = WLSM_M_Staff_Tickets::get_student_tickets($student_id);

// Fetch details of the selected ticket
$selected_ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
$selected_ticket = $selected_ticket_id ? WLSM_M_Staff_Tickets::fetch_ticket($selected_ticket_id) : null;
$ticket_history = $selected_ticket ? WLSM_M_Staff_Tickets::get_ticket_history($selected_ticket_id) : [];
?>

<div class="wlsm-content-area wlsm-section-ticket-history wlsm-student-ticket-history">
    <div class="wlsm-registration-section">
        <div class="wlsm-registration-section-header">
            <div class="wlsm-st-header-flex">
                <h3 class="wlsm-registration-section-title">
                    <?php esc_html_e('Support Tickets', 'school-management'); ?>
                </h3>
                <a class="wlsm-st-submit-btn wlsm-navigation-link<?php if ( 'add_ticket' === $action ) { echo ' active'; } ?>" href="<?php echo esc_url( add_query_arg( array( 'action' => 'add_ticket', 'student_id' => $student->ID ), $current_page_url ) ); ?>">
                    <?php esc_html_e( 'Add Ticket', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <div class="wlsm-registration-section-content">
            <?php if (!empty($tickets)): ?>
                <div class="wlsm-form-group">
                    <div class="table-responsive">
                        <table class="table table-bordered wlsm-student-ticket-history-table" style="margin-bottom: 0;">
                            <thead style="background-color: #6c757d; color: white;">
                                <tr>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600;"><?php esc_html_e('Title', 'school-management'); ?></th>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600;"><?php esc_html_e('Description', 'school-management'); ?></th>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600;"><?php esc_html_e('Status', 'school-management'); ?></th>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600;"><?php esc_html_e('Priority', 'school-management'); ?></th>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600;"><?php esc_html_e('Assigned To', 'school-management'); ?></th>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600;"><?php esc_html_e('Created At', 'school-management'); ?></th>
                                    <th style="border-color: rgba(255,255,255,0.2); font-weight: 600; text-align: center;"><?php esc_html_e('Action', 'school-management'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <?php
                                    $staff = WLSM_M_Staff_General::fetch_staff_name($ticket->assigned_to);
                                    $staff_name = $staff ? $staff->name : '';
                                    ?>
                                    <tr style="border-bottom: 1px solid #e1e5e9;">
                                        <td style="padding: 0.75rem; vertical-align: middle;"><?php echo esc_html($ticket->title); ?></td>
                                        <td style="padding: 0.75rem; vertical-align: middle;"><?php echo wp_trim_words(esc_html($ticket->description), 10); ?></td>
                                        <td style="padding: 0.75rem; vertical-align: middle;">
                                            <?php echo wp_kses_post(WLSM_M_Staff_Tickets::get_status_badge($ticket->status)); ?>
                                        </td>
                                        <td style="padding: 0.75rem; vertical-align: middle;">
                                            <span class="wlsm-badge" style="padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.85rem; font-weight: 500; color: white; background-color: <?php echo esc_attr($ticket->priority === 'high' ? '#dc3545' : ($ticket->priority === 'medium' ? '#ffc107' : '#17a2b8')); ?>;">
                                                <?php echo esc_html(ucfirst($ticket->priority)); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; vertical-align: middle;"><?php echo esc_html($staff_name ?? '-'); ?></td>
                                        <td style="padding: 0.75rem; vertical-align: middle;"><?php echo esc_html(WLSM_Config::get_date_text($ticket->created_at)); ?></td>
                                        <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
                                            <a class="wlsm-<?php echo esc_attr($action_for); ?>-ticket wlsm-st-submit-btn" style="padding: 0.25rem 0.75rem; font-size: 0.875rem; text-decoration: none;"
                                               data-ticket-id="<?php echo esc_attr($ticket->ID); ?>"
                                               data-student="<?php echo esc_attr($student_id); ?>"
                                               data-nonce="<?php echo esc_attr(wp_create_nonce($action_for . '-ticket-' . $ticket->ID)); ?>"
                                               data-message-title="<?php printf(
                                                   /* translators: %s: ticket ID */
                                                   esc_attr__('Ticket - %s', 'school-management'),
                                                   esc_attr($ticket->ID)
                                               ); ?>"
                                               data-close="<?php echo esc_attr__('Close', 'school-management'); ?>"
                                               href="#">
                                                <?php esc_html_e('View', 'school-management'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="wlsm-form-group">
                    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 1rem; text-align: center;">
                        <p style="margin: 0; color: #856404; font-weight: 500;">
                            <?php esc_html_e('No tickets found.', 'school-management'); ?>
                        </p>
                        <p style="margin: 0.5rem 0 0 0; color: #6c757d; font-size: 0.9rem;">
                            <?php esc_html_e('Click "Add Ticket" to create your first support ticket.', 'school-management'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
