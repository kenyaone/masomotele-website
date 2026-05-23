<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';

require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/navigation.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/class-bigbluebutton-api.php';

$class_school_id = $student->class_school_id;
$class_section_id= $student->section_id;

$meetings_per_page = WLSM_M::meetings_per_page();

$meetings_query = WLSM_M::meetings_query();

$meetings_total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM ({$meetings_query}) AS combined_table", $school_id, $class_school_id ) );

$meetings_page = isset( $_GET['meetings_page'] ) ? absint( $_GET['meetings_page'] ) : 1;

$meetings_page_offset = ( $meetings_page * $meetings_per_page ) - $meetings_per_page;

$meetings = $wpdb->get_results( $wpdb->prepare( $meetings_query . ' ORDER BY mt.start_at DESC, mt.ID DESC LIMIT %d, %d', $school_id, $class_school_id, $meetings_page_offset, $meetings_per_page ) );
?>
<div class="wlsm-content-area wlsm-section-meetings wlsm-student-meetings">
	<!-- Live Classes Section -->
	<div class="">
		<div class="wlsm-registration-header">
			<h3 class="wlsm-registration-title"><?php esc_html_e( 'Live Classes', 'school-management' ); ?></h3>
		</div>
		<div class="wlsm-registration-content">
			<?php
			if ( count( $meetings ) ) {
			?>
			<div class="wlsm-table-section">
				<div class="table-responsive w-100 wlsm-w-100">
					<table class="wlsm-live-classes-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Topic', 'school-management' ); ?></th>
								<th><?php esc_html_e( 'Duration (min)', 'school-management' ); ?></th>
								<th class="text-nowrap"><?php esc_html_e( 'Start Date / Time', 'school-management' ); ?></th>
								<th><?php esc_html_e( 'Type', 'school-management' ); ?></th>
								<th class="text-nowrap"><?php esc_html_e( 'Join', 'school-management' ); ?></th>
								<th><?php esc_html_e( 'Password', 'school-management' ); ?></th>
								<th><?php esc_html_e( 'Subject', 'school-management' ); ?></th>
								<th><?php esc_html_e( 'Teacher', 'school-management' ); ?></th>
								<th class="text-nowrap"><?php esc_html_e( 'Rate', 'school-management' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $meetings as $row ) {
							?>
							<?php if ($row->section_id === $class_section_id || $row->section_id === null): ?>

							<tr>
								<td>
									<?php echo esc_html( $row->topic ); ?>
								</td>
								<td>
									<?php echo esc_html( $row->duration ); ?>
								</td>
								<td class="text-nowrap">
									<?php echo esc_html( WLSM_Config::get_at_text( $row->start_at ) ); ?>
								</td>
								<td>
									<span class="wlsm-meeting-type"><?php echo esc_html( WLSM_Helper::get_meeting_type( $row->type ) ); ?></span>
								</td>
								<td class="text-nowrap">
									<?php
									if ( $row->join_url ) {
									?>
									<a target="_blank" class="wlsm-join-btn" href="<?php echo esc_url( $row->join_url ); ?>"><?php esc_html_e( 'Join', 'school-management' ); ?></a>
									<?php
									} else if ( $row->class_type === 'bbb_class' ) {
										// For BigBlueButton, generate URL on-demand to avoid slow loading
									?>
									<a class="wlsm-join-btn wlsm-student-bbb-join-meeting"
										data-meeting-id="<?php echo esc_attr($row->meeting_id); ?>"
										data-password="<?php echo esc_attr($row->password); ?>"
										data-recordable="<?php echo esc_attr($row->recordable); ?>"
										data-nonce="<?php echo esc_attr(wp_create_nonce('student-bbb-join-' . $row->ID)); ?>"
										href="#"><?php esc_html_e( 'Join', 'school-management' ); ?></a>
									<?php
									} else {
									?>
									<span class="text-muted"><?php esc_html_e( 'URL not available', 'school-management' ); ?></span>
									<?php
									}
									?>
								</td>
								<td>
									<?php echo esc_html( $row->password ? $row->password : '-' ); ?>
								</td>
								<td class="text-nowrap">
									<?php echo esc_html( WLSM_M_Staff_Class::get_subject_label_text( $row->subject_name ) ); ?>
								</td>
								<td>
									<?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $row->name ) ); ?>
								</td>
								<td>
									<a href="#" class="wlsm-rating-btn wlsm-st-print-staff-ratting" data-class="<?php echo esc_attr($row->ID); ?>" data-nonce="<?php echo ( wp_create_nonce( 'staff-ratting' ) );  ?>"><?php echo esc_html__( 'Rating', 'school-management' ) ?></a>
								</td>
							</tr>
							<?php endif ?>
							<?php
							}
							?>
						</tbody>
					</table>
					<div class="wlsm-student-ratting-form"></div>
				</div>
			</div>
			<div class="wlsm-text-right wlsm-font-medium wlsm-font-bold wlsm-mt-2">
			<?php
			echo paginate_links(
				array(
					'base'      => add_query_arg( 'meetings_page', '%#%' ),
					'format'    => '',
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
					'total'     => ceil( $meetings_total / $meetings_per_page ),
					'current'   => $meetings_page,
				)
			);
			?>
			</div>
			<?php
			} else {
			?>
			<div class="wlsm-text-center" style="padding: 2rem;">
				<span class="wlsm-font-medium wlsm-font-bold">
					<?php esc_html_e( 'There is no live class.', 'school-management' ); ?>
				</span>
			</div>
			<?php
			}
			?>
		</div>
	</div>
</div>
