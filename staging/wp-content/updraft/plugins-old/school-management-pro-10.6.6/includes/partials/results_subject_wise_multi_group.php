<?php
defined( 'ABSPATH' ) || die();

if ( ! isset( $selected_group_ids ) || ! is_array( $selected_group_ids ) ) {
	$selected_group_ids = array();
}

$selected_group_ids = array_values( array_unique( array_map( 'absint', $selected_group_ids ) ) );

$subjects = WLSM_M_Staff_Class::get_class_subjects_students( $school_id, $class_id, $student_id );

if ( empty( $selected_group_ids ) ) {
	require WLSM_PLUGIN_DIR_PATH . 'includes/partials/results_subject_wise.php';
	return;
}

$group_objects = WLSM_M_Staff_Examination::fetch_exam_groups_by_ids( $school_id, $selected_group_ids );
$group_labels  = array();

foreach ( $group_objects as $group_object ) {
	$group_labels[ (int) $group_object->ID ] = $group_object->label;
}

$group_order = $selected_group_ids;

$exams = WLSM_M_Staff_Examination::get_class_school_exams_academic_multi_group_report( $school_id, $class_school_id, $academic_report->ID );

if ( empty( $exams ) ) {
	require WLSM_PLUGIN_DIR_PATH . 'includes/partials/results_subject_wise.php';
	return;
}

$grouped_exams        = array();
$group_exam_counts    = array();
$group_summaries      = array();
$show_total_marks     = false;
$show_remark          = '0';
$psychomotor_enable   = array();
$psychomotor          = array();
$teacher_signature    = null;
$school_signature     = isset( $academic_report->teacher_signature ) ? $academic_report->teacher_signature : null;

foreach ( $group_order as $group_id ) {
	$grouped_exams[ $group_id ]     = array();
	$group_exam_counts[ $group_id ] = 0;
	$group_summaries[ $group_id ]   = array(
		'obtained' => 0,
		'maximum'  => 0,
		'exams'    => 0,
	);
}

foreach ( $exams as $exam ) {
	$group_id = (int) $exam->exam_group_id;

	if ( ! isset( $grouped_exams[ $group_id ] ) ) {
		$grouped_exams[ $group_id ]     = array();
		$group_exam_counts[ $group_id ] = 0;
		$group_summaries[ $group_id ]   = array(
			'obtained' => 0,
			'maximum'  => 0,
			'exams'    => 0,
		);
		$group_order[] = $group_id;
	}

	$grouped_exams[ $group_id ][] = $exam;
	$group_exam_counts[ $group_id ]++;

	if ( ! isset( $group_labels[ $group_id ] ) ) {
		$group_labels[ $group_id ] = isset( $exam->exam_group_label ) ? $exam->exam_group_label : '';
	}

	if ( $exam->enable_total_marks ) {
		$show_total_marks = true;
	}

	if ( '1' === $exam->show_remark ) {
		$show_remark = '1';
	}

	if ( null === $teacher_signature ) {
		$teacher_signature = $exam->teacher_signature;
	}

	$psychomotor_enable[] = $exam->psychomotor_analysis;
	$psychomotor[]        = WLSM_Config::sanitize_psychomotor( $exam->psychomotor );
	$group_summaries[ $group_id ]['exams']++;
}

$group_order = array_values( array_unique( $group_order ) );
$overall_obtained = 0;
$overall_maximum  = 0;
$marks_grades     = array();

?>
<thead>
	<tr>
		<th><?php esc_html_e( 'Subject', 'school-management' ); ?></th>
		<?php foreach ( $group_order as $group_id ) : ?>
			<?php
			$group_label      = isset( $group_labels[ $group_id ] ) ? $group_labels[ $group_id ] : '';
			$group_exam_count = isset( $group_exam_counts[ $group_id ] ) ? $group_exam_counts[ $group_id ] : 0;
			?>
			<th class="text-center" colspan="<?php echo esc_attr( $group_exam_count + 1 ); ?>">
				<?php echo esc_html( stripslashes( $group_label ) ); ?>
			</th>
		<?php endforeach; ?>
		<?php if ( $show_total_marks ) : ?>
			<th class="text-center"><?php esc_html_e( 'Overall Total', 'school-management' ); ?></th>
		<?php endif; ?>
		<th class="text-center"><?php esc_html_e( 'Grade', 'school-management' ); ?></th>
		<?php if ( '1' === $show_remark ) : ?>
			<th class="text-center"><?php esc_html_e( 'Remarks', 'school-management' ); ?></th>
		<?php endif; ?>
	</tr>
	<tr>
		<th></th>
		<?php foreach ( $group_order as $group_id ) : ?>
			<?php foreach ( $grouped_exams[ $group_id ] as $exam ) : ?>
				<th><?php echo esc_html( stripslashes( $exam->exam_title ) ); ?></th>
			<?php endforeach; ?>
			<th class="text-center"><?php esc_html_e( 'Group Total', 'school-management' ); ?></th>
		<?php endforeach; ?>
		<?php if ( $show_total_marks ) : ?>
			<th></th>
		<?php endif; ?>
		<th></th>
		<?php if ( '1' === $show_remark ) : ?>
			<th></th>
		<?php endif; ?>
	</tr>
</thead>
<tbody>
	<?php foreach ( $subjects as $subject ) : ?>
		<?php
		$subject_overall_obtained = 0;
		$subject_overall_maximum  = 0;
		$grade_subject_percentage = 0;
		$remark                   = '';
		$subject_has_results      = false;

		// Check if this subject has any exam results in any group
		foreach ( $group_order as $group_id ) : ?>
			<?php foreach ( $grouped_exams[ $group_id ] as $exam ) : ?>
				<?php
				$exam_result = WLSM_M_Staff_Examination::get_exam_result_by_subject_code( $school_id, $exam->ID, $student_id, $subject->code );
				if ( $exam_result ) {
					$subject_has_results = true;
				}
				?>
			<?php endforeach; ?>
		<?php endforeach; ?>

		<?php if ( ! $subject_has_results ) : ?>
			<?php continue; ?>
		<?php endif; ?>

		<tr>
			<td>
				<?php
				printf(
					wp_kses(
						/* translators: 1: subject label, 2: subject code */
						_x( '%1$s (%2$s)', 'Subject', 'school-management' ),
						array( 'span' => array( 'class' => array() ) )
					),
					esc_html( WLSM_M_Staff_Class::get_subject_label_text( $subject->label ) ),
					esc_html( $subject->code )
				);
				?>
			</td>
			<?php foreach ( $group_order as $group_id ) : ?>
				<?php
				$group_subject_obtained = 0;
				$group_subject_maximum  = 0;
				?>
				<?php foreach ( $grouped_exams[ $group_id ] as $exam ) : ?>
					<?php
					$exam_result = WLSM_M_Staff_Examination::get_exam_result_by_subject_code( $school_id, $exam->ID, $student_id, $subject->code );
					?>
					<td>
						<?php if ( $exam_result ) : ?>
							<?php
							$group_subject_obtained += $exam_result->obtained_marks;
							$group_subject_maximum  += $exam_result->maximum_marks;
							$remark                  = $exam_result->remark;

							$grade_criteria = WLSM_Config::sanitize_grade_criteria( $exam->grade_criteria );
							$marks_grades   = $grade_criteria['marks_grades'];

							$subject_overall_obtained += $exam_result->obtained_marks;
							$subject_overall_maximum  += $exam_result->maximum_marks;

							$group_summaries[ $group_id ]['obtained'] += $exam_result->obtained_marks;
							$group_summaries[ $group_id ]['maximum']  += $exam_result->maximum_marks;
							?>
							<span class="wlsm-font-bold"><?php echo esc_html( $exam_result->obtained_marks ); ?></span>
						<?php else : ?>
							-
						<?php endif; ?>
					</td>
				<?php endforeach; ?>
				<td>
					<?php
					if ( $group_subject_obtained > 0 ) {
						echo '<span class="wlsm-font-bold">' . esc_html( $group_subject_obtained ) . '</span>';
					} else {
						echo '-';
					}
					?>
				</td>
			<?php endforeach; ?>
			<?php if ( $show_total_marks ) : ?>
				<td>
					<?php
					if ( $subject_overall_obtained > 0 ) {
						echo '<span class="wlsm-font-bold">' . esc_html( $subject_overall_obtained ) . '</span>';
					} else {
						echo '-';
					}
					?>
				</td>
			<?php endif; ?>
			<td>
				<?php
				if ( $subject_overall_maximum > 0 ) {
					$grade_subject_percentage = WLSM_Config::sanitize_percentage( $subject_overall_maximum, WLSM_Config::sanitize_marks( $subject_overall_obtained ) );
					echo esc_html( WLSM_Helper::calculate_grade( $marks_grades, $grade_subject_percentage ) );
				}
				?>
			</td>
			<?php if ( '1' === $show_remark ) : ?>
				<td><?php echo esc_html( $remark ); ?></td>
			<?php endif; ?>
		</tr>
		<?php
		$overall_obtained += $subject_overall_obtained;
		$overall_maximum  += $subject_overall_maximum;
		?>
	<?php endforeach; ?>

	<tr>
		<th><?php esc_html_e( 'Group Totals', 'school-management' ); ?></th>
		<?php foreach ( $group_order as $group_id ) : ?>
			<?php foreach ( $grouped_exams[ $group_id ] as $unused ) : ?>
				<td></td>
			<?php endforeach; ?>
			<td>
				<?php
				$group_total_obtained = $group_summaries[ $group_id ]['obtained'];
				if ( $group_total_obtained > 0 ) {
					echo '<span class="wlsm-font-bold">' . esc_html( $group_total_obtained ) . '</span>';
				} else {
					echo '-';
				}
				?>
			</td>
		<?php endforeach; ?>
		<?php if ( $show_total_marks ) : ?>
			<td>
				<?php
				if ( $overall_obtained > 0 ) {
					echo '<span class="wlsm-font-bold">' . esc_html( $overall_obtained ) . '</span>';
				} else {
					echo '-';
				}
				?>
			</td>
		<?php endif; ?>
		<td></td>
		<?php if ( '1' === $show_remark ) : ?>
			<td></td>
		<?php endif; ?>
	</tr>

	<?php
	$group_averages = array();
	foreach ( $group_order as $group_id ) {
		$exam_count = max( 1, $group_summaries[ $group_id ]['exams'] );
		$group_averages[ $group_id ] = $group_summaries[ $group_id ]['obtained'] / $exam_count;
	}
	$overall_average = ! empty( $group_averages ) ? array_sum( $group_averages ) / count( $group_averages ) : 0;
	?>
	<tr>
		<th><?php esc_html_e( 'Average of Groups', 'school-management' ); ?></th>
		<?php foreach ( $group_order as $group_id ) : ?>
			<?php foreach ( $grouped_exams[ $group_id ] as $unused ) : ?>
				<td></td>
			<?php endforeach; ?>
			<td>
				<?php
				$average_value = $group_averages[ $group_id ];
				echo '<span class="wlsm-font-bold">' . esc_html( number_format_i18n( $average_value, 2 ) ) . '</span>';
				?>
			</td>
		<?php endforeach; ?>
		<?php if ( $show_total_marks ) : ?>
			<td>
				<?php echo '<span class="wlsm-font-bold">' . esc_html( number_format_i18n( $overall_average, 2 ) ) . '</span>'; ?>
			</td>
		<?php endif; ?>
		<td></td>
		<?php if ( '1' === $show_remark ) : ?>
			<td></td>
		<?php endif; ?>
	</tr>
</tbody>
