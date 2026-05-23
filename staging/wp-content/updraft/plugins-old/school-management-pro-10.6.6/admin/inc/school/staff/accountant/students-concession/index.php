<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Accountant::get_students_concession_page_url();
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-users"></i>
				<?php esc_html_e( 'Students with Concession', 'school-management' ); ?>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-students-concession-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col"><?php esc_html_e( 'Student Name', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Admission Number', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Class', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Section', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Concession Name', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Approved By', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Applied Date', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
