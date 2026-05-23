<?php
defined( 'ABSPATH' ) || die();

$school_id = $current_school['id'];

// Get staff roles for filtering
global $wpdb;
$roles = $wpdb->get_results( $wpdb->prepare( 'SELECT r.ID, r.name FROM ' . WLSM_ROLES . ' as r WHERE r.school_id = %d', $school_id ) );
?>
<div class="row">
	<div class="col-md-12">
		<div class="mt-2 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-id-card"></i>
				<?php esc_html_e( 'Print Staff ID Cards in Bulk', 'school-management' ); ?>
			</span>
		</div>
		<div class="wlsm-students-block wlsm-form-section">
			<div class="row">
				<div class="col-md-12">
					<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-print-bulk-staff-id-cards-form" class="mb-3"><?php /* Form will be handled by ajaxForm plugin */ ?>
						<?php
						$nonce_action = 'print-staff-id-cards';
						?>
						<?php $nonce = wp_create_nonce( $nonce_action ); ?>
						<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

						<input type="hidden" name="action" value="wlsm-print-bulk-staff-id-cards">

						<div class="pt-2">
							<div class="row">
								<div class="col-md-8 mb-1">
									<div class="h6">
										<span class="text-secondary border-bottom">
										<?php esc_html_e( 'Search Staff By Role', 'school-management' ); ?>
										</span>
									</div>
								</div>
							</div>
							<div class="form-row">
								<div class="form-group col-md-6">
									<label for="wlsm_staff_role" class="wlsm-font-bold">
										<?php esc_html_e( 'Staff Role', 'school-management' ); ?>:
									</label>
									<select name="role_id" class="form-control selectpicker" id="wlsm_staff_role" data-live-search="true" title="<?php esc_attr_e( 'All Roles', 'school-management' ); ?>">
										<option value=""><?php esc_html_e( 'All Staff', 'school-management' ); ?></option>
										<?php foreach ( $roles as $role ) { ?>
										<option value="<?php echo esc_attr( $role->ID ); ?>">
											<?php echo esc_html( $role->name ); ?>
										</option>
										<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-6">
									<label for="wlsm_staff_status" class="wlsm-font-bold">
										<?php esc_html_e( 'Staff Status', 'school-management' ); ?>:
									</label>
									<select name="only_active" class="form-control selectpicker" id="wlsm_staff_status">
										<option value="1"><?php esc_html_e( 'Active Staff', 'school-management' ); ?></option>
										<option value="0"><?php esc_html_e( 'All Staff', 'school-management' ); ?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="form-row">
							<div class="col-md-12">
								<button type="submit" class="btn btn-sm btn-outline-primary" id="wlsm-print-bulk-staff-id-cards-btn">
									<i class="fas fa-print"></i>&nbsp;
									<?php esc_html_e( 'Print Staff ID Cards', 'school-management' ); ?>
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>