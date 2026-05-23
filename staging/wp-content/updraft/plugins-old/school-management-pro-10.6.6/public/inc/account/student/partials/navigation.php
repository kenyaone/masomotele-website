<?php
defined('ABSPATH') || die();
$settings_dashboard = WLSM_M_Setting::get_settings_dashboard($school_id);
$navigation_items = [
	'fee-invoices' => $settings_dashboard['school_invoice'],
	'fee-structure' => $settings_dashboard['school_fee_structure'],
	'payment-history' => $settings_dashboard['school_payment_history'],
	'study-materials' => $settings_dashboard['school_study_material'],
	'homework' => $settings_dashboard['school_home_work'],
	'noticeboard' => $settings_dashboard['school_noticeboard'],
	'events' => $settings_dashboard['school_events'],
	'class-time-table' => $settings_dashboard['school_class_time_table'],
	'live-classes' => $settings_dashboard['school_live_classes'],
	'books-issued' => $settings_dashboard['school_books_issues'],
	'exams-time-table' => $settings_dashboard['school_exam_time_table'],
	'exam-admit-card' => $settings_dashboard['school_admit_card'],
	'exam-results' => $settings_dashboard['school_exam_result'],
	'certificates' => $settings_dashboard['school_certificate'],
	'attendance' => $settings_dashboard['school_attendance'],
	'leave-request' => $settings_dashboard['school_leave_request'],
	'tickets' => $settings_dashboard['school_tickets']
];

// Define icons for each navigation item
$nav_icons = [
	'dashboard' => 'fas fa-tachometer-alt',
	'fee-invoices' => 'fas fa-file-invoice',
	'fee-structure' => 'fas fa-stream',
	'payment-history' => 'fas fa-history',
	'study-materials' => 'fas fa-book',
	'homework' => 'fas fa-tasks',
	'noticeboard' => 'fas fa-clipboard',
	'events' => 'fas fa-calendar-alt',
	'class-time-table' => 'fas fa-clock',
	'live-classes' => 'fas fa-video',
	'books-issued' => 'fas fa-book-reader',
	'exams-time-table' => 'fas fa-calendar-check',
	'exam-admit-card' => 'fas fa-id-card',
	'exam-results' => 'fas fa-chart-bar',
	'certificates' => 'fas fa-certificate',
	'attendance' => 'fas fa-calendar',
	'leave-request' => 'fas fa-envelope',
	'tickets' => 'fas fa-ticket-alt',
	'settings' => 'fas fa-cog'
];
?>
<!-- Add navigation header -->
<div class="wlsm-navigation-header">
	<div class="wlsm-header-left">
		<?php if ($unique_student_ids): ?>
			<h3><i class="fas fa-user-graduate"></i> <?php esc_html_e('Parent Dashboard', 'school-management'); ?></h3>
		<?php endif; ?>
		<?php if (!$unique_student_ids): ?>
			<h3><i class="fas fa-user-graduate"></i> <?php esc_html_e('Student Dashboard', 'school-management'); ?></h3>
		<?php endif; ?>
	</div>

	<?php if (is_array($unique_student_ids) && !empty($unique_student_ids)) : ?>
		<div class="wlsm-header-right">
			<div class="wlsm-student-selector" id="wlsm-student-selector">
				<select name="student_id" class="wlsm-select-student" data-nonce="<?php echo esc_attr(wp_create_nonce('switch-student')); ?>">


					<?php foreach ($records as $record) :
						$selected = ($record->ID === $student->ID) ? 'selected' : '';
					?>
						<option value="<?php echo esc_attr($record->ID); ?>" <?php echo $selected; ?>>
							<?php echo esc_html($record->student_name); ?>
							(<?php echo esc_html($record->enrollment_number); ?>)
						</option>
					<?php endforeach; ?>

				</select>
			</div>
		</div>
	<?php endif ?>
</div>

<input type="checkbox" id="wlsm-menu-toggle" class="wlsm-menu-btn">
<label for="wlsm-menu-toggle" class="wlsm-menu-label">
	<span class="wlsm-menu-icon"></span>
</label>
<div class="wlsm-overlay"></div>
<ul class="wlsm-navigation-links">
	<li>
		<a class="wlsm-navigation-link<?php if ('' === $action) {
											echo ' active';
										} ?>" href="<?php echo esc_url(add_query_arg(array(), $current_page_url)); ?>">
			<i class="<?php echo esc_attr($nav_icons['dashboard']); ?>"></i>
			<?php esc_html_e('Dashboard', 'school-management'); ?>
		</a>
	</li>
	<?php foreach ($navigation_items as $nav_action => $enabled): ?>
		<?php if ($enabled): ?>
			<li>
				<a class="wlsm-navigation-link<?php if ($nav_action === $action) {
													echo ' active';
												} ?>" href="<?php echo esc_url(add_query_arg(array('action' => $nav_action), $current_page_url)); ?>">
					<i class="<?php echo esc_attr($nav_icons[$nav_action]); ?>"></i>
					<?php esc_html_e(ucwords(str_replace('-', ' ', $nav_action)), 'school-management'); ?>
				</a>
			</li>
		<?php endif; ?>
	<?php endforeach; ?>
	<li>
		<a class="wlsm-navigation-link<?php if ('settings' === $action) {
											echo ' active';
										} ?>" href="<?php echo esc_url(add_query_arg(array('action' => 'settings'), $current_page_url)); ?>">
			<i class="<?php echo esc_attr($nav_icons['settings']); ?>"></i>
			<?php esc_html_e('Account Settings', 'school-management'); ?>
		</a>
	</li>
</ul>