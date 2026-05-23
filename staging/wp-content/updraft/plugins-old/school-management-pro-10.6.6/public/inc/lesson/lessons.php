<?php
defined('ABSPATH') || die();
global $wpdb;
$school = null;
$lesson_per_page = WLSM_M::lesson_per_page();
$lesson_query = WLSM_M::lesson_query();

$lesson_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$lesson_query}) AS combined_table"));

$lesson_page = isset($_GET['lesson_page']) ? absint($_GET['lesson_page']) : 1;

$lesson_page_offset = ($lesson_page * $lesson_per_page) - $lesson_per_page;

$lessons = $wpdb->get_results($wpdb->prepare($lesson_query . ' ORDER BY l.ID DESC LIMIT %d, %d', $lesson_page_offset, $lesson_per_page));

if (isset($attr['school_id'])) {
	$school_id = absint($attr['school_id']);
	$classes = WLSM_M_Staff_General::fetch_school_classes($school_id);
}
$user_id = get_current_user_id();
$student = WLSM_M_User::user_is_student($user_id);
$class_id = null;
if ($student) {
	$school_id = $student->school_id;
	$class_id = $student->class_id;
	$subjects = WLSM_M_Staff_Class::get_class_subjects($school_id, $class_id);
}
?>

<div class="wlsm-grid">
	<!-- Filter Form -->
	<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-get-student-lesson-form">
		<input type="hidden" name="<?php echo esc_attr("nonce"); ?>" value="<?php echo wp_create_nonce("lessons"); ?>">

		<input type="hidden" name="action" value="wlsm-p-submit-lessons">
		<input type="hidden" id="school_id" name="school_id" value="<?php echo $school_id; ?>">
		<?php if ($class_id): ?>
			<input type="hidden" id="class_id" name="class_id" value="<?php echo $class_id; ?>">

		<?php endif ?>

		<div class="wlsm-row">
			<?php if (!$class_id): ?>
				<div class="wlsm-form-group wlsm-col-4">
					<label for="wlsm_school_class" class="wlsm-font-bold">
						<?php esc_html_e('Class', 'school-management'); ?>:
					</label>
					<select name="class_id" class="wlsm-form-control wlsm_school_class_subject" data-nonce="<?php echo esc_attr(wp_create_nonce('get-class-subject')); ?>" id="wlsm_school_class_subject">
						<option value=""><?php esc_html_e('Select Class', 'school-management'); ?></option>
						<?php
						if (isset($classes)) {
							foreach ($classes as $class) {
						?>
								<option value="<?php echo esc_attr($class->ID); ?>">
									<?php echo esc_html(WLSM_M_Class::get_label_text($class->label)); ?>
								</option>
						<?php
							}
						}
						?>
						</option>
					</select>
				</div>
			<?php endif ?>


			<div class="wlsm-form-group wlsm-col-4">
				<label for="wlsm_subject" class="wlsm-font-bold">
					<?php esc_html_e('Subject', 'school-management'); ?>:
				</label>
				<select name="subject_id" class="wlsm-form-control" id="wlsm_class_subject" data-nonce="<?php echo esc_attr(wp_create_nonce('get-subject-chapter')); ?>">
					<option value=""><?php esc_html_e('Select Subject', 'school-management'); ?></option>
					<?php if ($subjects): ?>
						<?php foreach ($subjects as $subject): ?>
							<option value="<?php echo esc_attr($subject->ID); ?>"><?php echo esc_html($subject->label); ?></option>
						<?php endforeach ?>
					<?php endif ?>
				</select>
			</div>

			<div class="wlsm-form-group wlsm-col-4">
				<label for="wlsm_chapter" class="wlsm-font-bold">
					<?php esc_html_e('Chapter', 'school-management'); ?>:
				</label>
				<select name="chapter_id" class="wlsm-form-control" id="wlsm_chapter">
					<option value=""><?php esc_html_e('Select All', 'school-management'); ?></option>
				</select>
			</div>
			<br>


		</div>
		<div class="wlsm-form-group wlsm-col-2">
			<button class="button wlsm-btn btn btn-primary" id="wlsm-get-student-lesson-btn"><?php esc_html_e('Filter', 'school-management'); ?></button>
		</div>
	</form>

	<br>

	<?php if (empty($lessons)): ?>
		<p><?php esc_html_e('No lessons found.', 'school-management'); ?></p>
	<?php else: ?>
		<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
			<?php foreach ($lessons as $lesson): ?>
				<div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
					<h3>
						<?php if (!empty($lesson->url)): ?>
							<a href="<?php echo esc_url($lesson->url); ?>"><?php echo esc_html($lesson->title); ?></a>
						<?php else: ?>
							<?php echo esc_html($lesson->title); ?>
						<?php endif; ?>
					</h3>

					<p><strong><?php esc_html_e('Subject:', 'school-management'); ?></strong> <?php echo esc_html($lesson->subject); ?></p>
					<p><strong><?php esc_html_e('Chapter:', 'school-management'); ?></strong> <?php echo esc_html($lesson->chapter); ?></p>

					<details>
						<summary><strong><?php esc_html_e('Description', 'school-management'); ?></strong> (<?php echo esc_html_e('Click to expand', 'school-management'); ?>)</summary>
						<div style="padding: 10px 0;">
							<?php echo wp_kses_post($lesson->description); ?>
						</div>
					</details>

					<?php if ($lesson->link_to == 'attachment' && !empty($lesson->attachment)): ?>
						<p style="margin-top: 10px;">
							<a href="<?php echo esc_url(wp_get_attachment_url($lesson->attachment)); ?>" target="_blank">
								<?php esc_html_e('View Attachment', 'school-management'); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php
		// Simple pagination
		if ($lesson_total > $lesson_per_page) {
			$total_pages = ceil($lesson_total / $lesson_per_page);
			echo '<div style="margin-top: 20px; text-align: center;">';
			for ($i = 1; $i <= $total_pages; $i++) {
				if ($i == $lesson_page) {
					echo '<span style="padding: 5px 10px; margin: 0 5px; background: #f0f0f0; border: 1px solid #ccc;">' . $i . '</span>';
				} else {
					echo '<a href="' . add_query_arg('lesson_page', $i) . '" style="padding: 5px 10px; margin: 0 5px; border: 1px solid #ccc; text-decoration: none;">' . $i . '</a>';
				}
			}
			echo '</div>';
		}
		?>
	<?php endif; ?>
</div>
