<?php
defined( 'ABSPATH' ) || die();

$school = NULL;
if ( isset( $attr['school_id'] ) ) {
	$school_id = absint( $attr['school_id'] );
	$school    = WLSM_M_School::get_active_school( $school_id );
}

if ( ! $school ) {
	$invalid_message = esc_html__( 'School not found.', 'school-management' );
	return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/partials/invalid.php';
}

?>
<div class="wlsm-shortcode-entity">
	<?php // require_once WLSM_PLUGIN_DIR_PATH . 'includes/partials/noticeboard.php'; ?>

	<?php

		global $wpdb;

		$notices_per_page = WLSM_M::notices_per_page();

		if (!isset($class_school_id)) {
		$class_school_id = '';
		}

		// $class_school_id = $student->class_id;

		$notices_query = WLSM_M::notices_query($school_id);

		$notices_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$notices_query}) AS combined_table", $school_id));

		$notices_page = isset($_GET['notices_page']) ? absint($_GET['notices_page']) : 1;

		$notices_page_offset = ($notices_page * $notices_per_page) - $notices_per_page;

		$notices = $wpdb->get_results($wpdb->prepare($notices_query . ' ORDER BY n.ID DESC LIMIT %d, %d', $school_id, $notices_page_offset, $notices_per_page));

		// Placeholder for filtered notices
		// $filtered_notices = [];

			// Iterate over each notice
		foreach ($notices as $notice) {
			// Unserialize the notice_data
			$notice_data = unserialize($notice->notice_data);

			// Check for class_id match or 'all'
			// $class_match = in_array($student->class_id, $notice_data['classes']) || in_array('all', $notice_data['classes']);

			// Check for section_id match or 'all'
			// $section_match = in_array($student->section_id, $notice_data['sections']) || in_array('all', $notice_data['sections']);

			// Check for student_id match or 'all'
			// $student_match = in_array($student->ID, $notice_data['students']) || in_array('all', $notice_data['students']);

			// If all conditions match, add the notice to the filtered list
			// if ($class_match && $section_match && $student_match) {
			// 	$filtered_notices[] = $notice;
			// }
		}

	?>

	<div class="wlsm-content-area wlsm-section-noticeboard wlsm-student-noticeboard">
		<div class="wlsm-st-main-title">
			<span><?php esc_html_e('Noticeboard', 'school-management'); ?></span>
		</div>

		<div class="wlsm-st-notices-section">
			<?php
			if (count($notices)) {
				$today = new DateTime();
				$today->setTime(0, 0, 0);
			?>
			<ul class="wlst-st-list wlsm-st-notices">
				<?php
				foreach ($notices as $key => $notice) {

					$link_to = $notice->link_to;
					$link = '#';

					if ('url' === $link_to) {
						if (!empty($notice->url)) {
							$link = $notice->url;
						}
					} else if ('attachment' === $link_to) {
						if (!empty($notice->attachment)) {
							$attachment = $notice->attachment;
							$link = wp_get_attachment_url($attachment);
						}
					} else {
						$link = '#';
					}

					$notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $notice->created_at);
					$notice_date->setTime(0, 0, 0);

					$interval = $today->diff($notice_date);
				?>
					<li>
						<?php if (!empty($link_to)) { ?>
							<span>
								<a target="_blank" href="<?php echo esc_url($link); ?>"><?php echo esc_html(stripslashes($notice->title)); ?> <span class="wlsm-st-notice-date wlsm-font-bold"><?php echo esc_html(WLSM_Config::get_date_text($notice->created_at)); ?></span></a>

								<?php if ($interval->days < 7) { ?>
									<img class="wlsm-st-notice-new" src="<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'); ?>">
								<?php } ?>
								<p><?php echo esc_html(stripslashes($notice->description)); ?></p>
							</span>
						<?php } else { ?>
							<span><?php echo esc_html(stripslashes($notice->title)); ?> <span class="wlsm-st-notice-date wlsm-font-bold"><?php echo esc_html(WLSM_Config::get_date_text($notice->created_at)); ?></span>
							<?php if ($interval->days < 7) { ?>
								<img class="wlsm-st-notice-new" src="<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'); ?>">
							<?php } ?>
							<p><?php echo esc_html(stripslashes($notice->description)); ?></p>
						<?php } ?>
						</span>
					</li>
				<?php
				}
				?>
			</ul>
			<div class="wlsm-text-right wlsm-font-medium wlsm-font-bold wlsm-mt-2">
				<?php
				echo paginate_links(
					array(
						'base'      => add_query_arg('notices_page', '%#%'),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => ceil($notices_total / $notices_per_page),
						'current'   => $notices_page,
					)
				);
				?>
			</div>
			<?php
			} else {
			?>
				<div>
					<span class="wlsm-font-medium wlsm-font-bold">
						<?php esc_html_e('There is no notice.', 'school-management'); ?>
					</span>
				</div>
			<?php
			}
			?>
		</div>
	</div>
</div>
<?php
return ob_get_clean();
