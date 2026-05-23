<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Helper.php';

$page_url     = WLSM_M_Staff_tickets::get_tickets_page_url();
$school_id    = $current_school['id'];
$ticket       = null;
$nonce_action = 'add-ticket';

// Get ticket if editing
if (isset($_GET['id']) && ! empty($_GET['id'])) {
	$id     = absint($_GET['id']);
	$ticket = WLSM_M_Staff_tickets::fetch_ticket($id);
	if ($ticket) {
		$nonce_action = 'edit-ticket-' . $ticket->ID;
	}
}

// Initialize variables
$title       = isset($ticket) ? $ticket->title : '';
$description = isset($ticket) ? $ticket->description : '';
$priority    = isset($ticket) ? $ticket->priority : 'normal';
$assigned_to = isset($ticket) ? $ticket->assigned_to : '';
$due_date    = isset($ticket) ? $ticket->due_date : '';
$assigned_to = isset($ticket) ? $ticket->assigned_to : '';
$status      = isset($ticket) ? $ticket->status : 'open';
$class_id    = isset($ticket) ? $ticket->class_id : '';
$section_id  = isset($ticket) ? $ticket->section_id : '';
$student_id  = isset($ticket) ? $ticket->student_id : '';
$role_id		= isset($ticket) ? $ticket->role_id : '';

// Fetch required data
global $wpdb;
$staff_members = WLSM_M_Staff_General::fetch_staff_list($school_id);

$role_query = WLSM_M_Staff_General::fetch_role_query($school_id);
$roles      = $wpdb->get_results($role_query);

if ( ! current_user_can( 'administrator' ) ) {
	$current_user 	= WLSM_M_Role::can('assigned_class');
	if ( $current_user ) {
		$role 			= $current_user['school']['role'];
	}
	if ( $current_user && $role !== 'admin' ) {
		$classes  = WLSM_M_Staff_Class::fetch_class_by_section_id( $school_id, absint($current_school['section_id']) );
	}else{
		$classes  = WLSM_M_Staff_Class::fetch_classes( $school_id );
	}
}else{
	$classes  = WLSM_M_Staff_Class::fetch_classes( $school_id );
}

// $classes  = WLSM_M_Staff_Class::fetch_classes($school_id);
$sections = WLSM_M_Staff_Class::fetch_sections_by_class_id($school_id, $class_id);
$students = WLSM_M_Staff_Class::fetch_students_by_section_id($section_id);
?>
<div class="row">
	<div class="col-md-12">
		<div class="wlsm-content-area">
			<div class="wlsm-content-area px-3 py-4">
				<!-- Page Header Card -->
				<div class=" mb-4 border border-light ">
					<div class="card-body d-flex justify-content-between align-items-center">
						<h2 class="h4 mb-0">
							<?php echo $ticket ? esc_html__('Edit Ticket', 'school-management') : esc_html__('Create New Ticket', 'school-management'); ?>
						</h2>
						<a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-primary btn-sm">
							<?php esc_html_e('View All Tickets', 'school-management'); ?>
						</a>
					</div>
				</div>

				<!-- Form Card -->
				<div class="border border-light shadow-sm ">
					<div class="card-body">
						<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-save-ticket-form">
							<?php $nonce = wp_create_nonce($nonce_action); ?>
							<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">
							<input type="hidden" name="action" value="wlsm-save-ticket">
							<?php if ($ticket) { ?>
								<input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->ID); ?>">
							<?php } ?>

							<div class="row">
								<!-- Title -->
								<div class="col-md-12 mb-3">
									<label for="wlsm_ticket_title" class="form-label font-weight-bold">
										<?php esc_html_e('Ticket Title', 'school-management'); ?>
										<span class="text-danger">*</span>
									</label>
									<input type="text" name="title" class="form-control" id="wlsm_ticket_title" value="<?php echo esc_attr($title); ?>" placeholder="<?php esc_attr_e('Enter ticket title', 'school-management'); ?>" required>
								</div>
							</div>

							<div class="row">
								<!-- Due Date -->
								<div class="form-group col-md-3 mb-3">
									<label for="wlsm_due_date" class="wlsm-font-bold">
										<?php esc_html_e('Due Date', 'school-management'); ?>
										<span class="wlsm-important">*</span>
									</label>
									<input type="text" name="due_date" class="form-control"
										id="wlsm_due_date"
										value="<?php echo esc_attr(WLSM_Config::get_date_text($due_date)); ?>">
								</div>

								<!-- Class -->
								<div class="form-group col-md-3 mb-3">
									<label for="wlsm_class" class="wlsm-font-bold">
										<?php esc_html_e('Class', 'school-management'); ?>
										<span class="wlsm-important">*</span>
									</label>
									<select name="class_id" class="form-control selectpicker"
										id="wlsm_class"
										data-nonce="<?php echo esc_attr(wp_create_nonce('get-class-sections')); ?>"
										data-nonce-subjects="<?php echo esc_attr(wp_create_nonce('get-class-subjects')); ?>"
										data-live-search="true"
										required>
										<option value=""><?php esc_html_e('Select Class', 'school-management'); ?></option>
										<?php foreach ($classes as $class) { ?>
											<option value="<?php echo esc_attr($class->ID); ?>"
												<?php selected($class_id, $class->ID); ?>>
												<?php echo esc_html(WLSM_M_Class::get_label_text($class->label)); ?>
											</option>
										<?php } ?>
									</select>
								</div>

								<!-- Section -->
								<div class="form-group col-md-3 mb-3">
									<label for="wlsm_section" class="wlsm-font-bold">
										<?php esc_html_e('Section', 'school-management'); ?>
										<span class="wlsm-important">*</span>
									</label>
									<select name="section_id" class="form-control selectpicker wlsm_section"
										id="wlsm_section"
										data-nonce="<?php echo esc_attr(wp_create_nonce('get-section-students')); ?>"
										data-live-search="true"
										required>
										<option value=""><?php esc_html_e('Select Section', 'school-management'); ?></option>
										<?php foreach ($sections as $section) { ?>
											<option value="<?php echo esc_attr($section->ID); ?>"
												<?php selected($section_id, $section->ID); ?>>
												<?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($section->label)); ?>
											</option>
										<?php } ?>
									</select>
								</div>

								<!-- Student -->
								<div class="form-group col-md-3 mb-3">
									<label for="wlsm_student" class="wlsm-font-bold">
										<?php esc_html_e('Student', 'school-management'); ?>
										<span class="wlsm-important">*</span>
									</label>
									<select name="student_id" class="form-control selectpicker"
										id="wlsm_student"
										data-live-search="true"
										required>
										<option value=""><?php esc_html_e('Select Student', 'school-management'); ?></option>
										<?php foreach ($students as $student) { ?>
											<option value="<?php echo esc_attr($student->ID); ?>"
												<?php selected($student_id, $student->ID); ?>>
												<?php echo esc_html($student->label); ?>
											</option>
										<?php } ?>
									</select>
								</div>
							</div>


							<div class="row">
								<!-- Role & Assign To -->
								<div class="col-md-6 mb-3">
									<label for="wlsm_role" class="form-label font-weight-bold">
										<?php esc_html_e('Role', 'school-management'); ?>
									</label>
									<select name="role" class="form-control selectpicker" id="wlsm_role" data-live-search="true">
										<option value=""><?php esc_html_e('Select Role', 'school-management'); ?></option>
										<?php foreach ($roles as $role) { ?>
											<option value="<?php echo esc_attr($role->ID); ?>"
												<?php selected($role_id, $role->ID); ?>>
												<?php echo esc_html($role->name); ?>
											</option>
										<?php } ?>
									</select>
								</div>

								<div class="col-md-6 mb-3">
									<label for="wlsm_assigned_to" class="form-label font-weight-bold">
										<?php esc_html_e('Assign To', 'school-management'); ?>
									</label>
									<select name="assigned_to" class="form-control selectpicker" id="wlsm_assigned_to" data-live-search="true">
										<option value=""><?php esc_html_e('Select Staff Member', 'school-management'); ?></option>
										<?php foreach ($staff_members as $staff) { ?>
											<option value="<?php echo esc_attr($staff->ID); ?>" <?php selected($assigned_to, $staff->ID); ?>>
												<?php echo esc_html($staff->name) . ' (' . esc_html($staff->role) . ')'; ?>
											</option>
										<?php } ?>
									</select>
								</div>


								<!-- Description -->
								<div class="col-md-12 mb-3">
									<label for="wlsm_description" class="form-label font-weight-bold">
										<?php esc_html_e('Description', 'school-management'); ?>
									</label>
									<textarea name="description" id="wlsm_description" class="form-control" rows="8" placeholder="<?php esc_attr_e('Enter ticket description', 'school-management'); ?>"><?php echo esc_textarea(stripslashes($description)); ?></textarea>
								</div>

							</div>

							<!-- Priority & Status -->
							<div class="row">
								<div class="col-md-6 mb-3">
									<label for="wlsm_priority" class="form-label font-weight-bold">
										<?php esc_html_e('Priority', 'school-management'); ?>
									</label>
									<select name="priority" class="form-control selectpicker" id="wlsm_priority">
										<option value="low" <?php selected($priority, 'low'); ?>><?php esc_html_e('Low', 'school-management'); ?></option>
										<option value="normal" <?php selected($priority, 'normal'); ?>><?php esc_html_e('Normal', 'school-management'); ?></option>
										<option value="high" <?php selected($priority, 'high'); ?>><?php esc_html_e('High', 'school-management'); ?></option>
										<option value="urgent" <?php selected($priority, 'urgent'); ?>><?php esc_html_e('Urgent', 'school-management'); ?></option>
									</select>
								</div>

								<div class="col-md-6 mb-3">
									<label for="wlsm_status" class="form-label font-weight-bold">
										<?php esc_html_e('Status', 'school-management'); ?>
									</label>
									<select name="status" class="form-control selectpicker" id="wlsm_status">
										<option value="open" <?php selected($status, 'open'); ?>><?php esc_html_e('Open', 'school-management'); ?></option>
										<option value="in_progress" <?php selected($status, 'in_progress'); ?>><?php esc_html_e('In Progress', 'school-management'); ?></option>
										<option value="resolved" <?php selected($status, 'resolved'); ?>><?php esc_html_e('Resolved', 'school-management'); ?></option>
										<option value="closed" <?php selected($status, 'closed'); ?>><?php esc_html_e('Closed', 'school-management'); ?></option>
									</select>
								</div>
							</div>

							<!-- Submit -->
							<div class="form-group text-center mt-4">
								<button type="submit" class="btn btn-primary px-4" id="wlsm-save-ticket-btn">
									<?php if ($ticket) { ?>
										<?php esc_html_e('Update Ticket', 'school-management'); ?>
									<?php } else { ?>
										<?php esc_html_e('Create Ticket', 'school-management'); ?>
									<?php } ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
if (isset($_GET['id']) && !empty($_GET['id'])) :
	$id = absint($_GET['id']);
	// Fetch ticket history
	$history = WLSM_M_Staff_Tickets::get_ticket_history($id);
?>

	<div class="wlsm-ticket-history">
		<h3><?php esc_html_e('Ticket History', 'school-management'); ?></h3>
		<?php if (!empty($history)) : ?>
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th><?php esc_html_e('Changed By', 'school-management'); ?></th>
							<th><?php esc_html_e('Status', 'school-management'); ?></th>
							<th><?php esc_html_e('Comment', 'school-management'); ?></th>
							<th><?php esc_html_e('Date', 'school-management'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($history as $record) : ?>
							<tr>
								<td><?php echo esc_html($record->changed_by_name); ?></td>
								<td><?php echo WLSM_M_Staff_Tickets::get_status_badge($record->status); ?></td>
								<td><?php echo esc_html($record->comment); ?></td>
								<td><?php echo esc_html(WLSM_Config::get_date_text($record->created_at)); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p><?php esc_html_e('No ticket history available.', 'school-management'); ?></p>
		<?php endif; ?>
	</div>

<?php endif; ?>
