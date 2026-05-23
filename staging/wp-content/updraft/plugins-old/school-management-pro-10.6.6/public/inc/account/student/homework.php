<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/navigation.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';

$section_id = $student->section_id;
$student_id = $student->ID;
$session_id = $student->session_id;
$homeworks_per_page = WLSM_M::homeworks_per_page();

$page_url = WLSM_M_Staff_Class::get_homeworks_submisson_page();

$homeworks_query = WLSM_M::homeworks_query();

$homeworks_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$homeworks_query}) AS combined_table", $school_id, $session_id, $section_id));

$homeworks_page = isset($_GET['homeworks_page']) ? absint($_GET['homeworks_page']) : 1;

$homeworks_page_offset = ($homeworks_page * $homeworks_per_page) - $homeworks_per_page;

$homeworks = $wpdb->get_results($wpdb->prepare($homeworks_query . ' ORDER BY hw.homework_date DESC LIMIT %d, %d', $school_id, $session_id, $section_id, $homeworks_page_offset, $homeworks_per_page));

$homeworks_student = $wpdb->get_results(('SELECT ID, student_id, created_at, description FROM ' . WLSM_HOMEWORK_SUBMISSION . ' as hs WHERE hs.student_id = ' . $student_id . ' ORDER BY hs.ID DESC '));
?>
<div class="wlsm-content-area wlsm-section-homeworks wlsm-student-homeworks">
	<!-- Homework Section -->
	<div class="">
		<div class="wlsm-registration-header">
			<div class="wlsm-homework-header">
				<h3 class="wlsm-registration-title"><?php esc_html_e('Homework', 'school-management'); ?></h3>
				<a href="<?php echo esc_url(add_query_arg(array('action' => 'submit-homework'), $current_page_url)); ?>"
				   class="wlsm-submit-homework-btn">
					<?php esc_html_e('Submit Homework', 'school-management'); ?>
				</a>
			</div>
		</div>
		<div class="wlsm-registration-content">
			<?php
			if (count($homeworks)) {
			?>
				<ul class="wlsm-homework-list">
					<?php
					foreach ($homeworks as $key => $homework) {
					?>
						<li class="wlsm-homework-item">
							<span class="wlsm-homework-title"><?php echo esc_html(stripslashes($homework->title)); ?></span>

							<div class="wlsm-homework-meta">
								<span class="wlsm-homework-date">
									<?php echo esc_html(WLSM_Config::get_date_text($homework->homework_date)); ?>
								</span>

								<?php if ( ! empty( $homework->homework_due_date ) ) { ?>
									<span class="wlsm-homework-due">
										<?php esc_html_e('Due:', 'school-management'); ?> <?php echo esc_html(WLSM_Config::get_date_text($homework->homework_due_date)); ?>
									</span>
								<?php } ?>

								<?php if ( ! empty( $homework->attachment_url ) ) { ?>
									<span class="wlsm-homework-attachment">
										<?php esc_html_e('Additional resource available', 'school-management'); ?>
									</span>
								<?php } ?>

								<a class="wlsm-view-homework-btn wlsm-st-view-homework" data-user-id="<?php esc_attr_e($user_id); ?>" data-homework="<?php echo esc_attr($homework->ID); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('st-view-homework-' . $homework->ID)); ?>" href="#" data-message-title="<?php echo esc_attr(stripslashes($homework->title)); ?>" data-close="<?php echo esc_attr__('Close', 'school-management'); ?>">
									<?php esc_html_e('View', 'school-management'); ?>
								</a>
							</div>
						</li>
					<?php
					}
					?>
				</ul>
				<div class="wlsm-text-right wlsm-font-medium wlsm-font-bold wlsm-mt-2">
					<?php
					echo paginate_links(
						array(
							'base'      => add_query_arg('homeworks_page', '%#%'),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => ceil($homeworks_total / $homeworks_per_page),
							'current'   => $homeworks_page,
						)
					);
					?>
				</div>
			<?php
			} else {
			?>
				<div class="wlsm-text-center" style="padding: 2rem;">
					<span class="wlsm-font-medium wlsm-font-bold">
						<?php esc_html_e('There is no homework.', 'school-management'); ?>
					</span>
				</div>
			<?php
			}
			?>
		</div>
	</div>
	<!-- Recent Homework Submissions Section -->
	<div class="">
		<div class="wlsm-registration-header">
			<h3 class="wlsm-registration-title"><?php esc_html_e('Recent Homework Submission', 'school-management'); ?></h3>
		</div>
		<div class="wlsm-registration-content">
			<?php
			if (count($homeworks_student)) {
			?>
				<!-- Student homework requests. -->
				<div class="wlsm-table-section">
					<div class="table-responsive w-100 wlsm-w-100">
						<table class="wlsm-homework-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Description', 'school-management'); ?></th>
									<th><?php esc_html_e('Date', 'school-management'); ?></th>
									<th class="text-nowrap"><?php esc_html_e('Action', 'school-management'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($homeworks_student as $row) {
								?>
									<tr>
										<td>
											<?php echo esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($row->description))); ?>
										</td>
										<td>
											<?php echo '<span class="wlsm-font-bold">' . esc_html(WLSM_Config::get_date_text($row->created_at)) . '</span>'; ?>
										</td>
										<td>
											<a class="wlsm-edit-homework-btn" id="homework_submission_edit" href="<?php echo esc_url(($page_url) . '/?action=submit-homework&id=' . ($row->ID)) ?>"><?php esc_html_e('Edit', 'school-management'); ?></a>
										</td>
									</tr>
								<?php
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="wlsm-text-right wlsm-font-medium wlsm-font-bold wlsm-mt-2">
					<?php
					echo paginate_links(
						array(
							'base'      => add_query_arg('homeworks_page', '%#%'),
							'format'    => '',
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
							'total'     => ceil($homeworks_total / $homeworks_per_page),
							'current'   => $homeworks_page,
						)
					);
					?>
				</div>
			<?php
			} else {
			?>
				<div class="wlsm-text-center" style="padding: 2rem;">
					<span class="wlsm-font-medium wlsm-font-bold">
						<?php esc_html_e("You haven't made any homework submission yet.", 'school-management'); ?>
					</span>
				</div>
			<?php
			}
			?>
		</div>
	</div>
</div>
