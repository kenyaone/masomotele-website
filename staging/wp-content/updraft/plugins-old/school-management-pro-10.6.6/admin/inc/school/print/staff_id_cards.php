<?php
defined( 'ABSPATH' ) || die();

if ( ! count( $staff_members ) ) {
	?>
	<div class="text-center">
		<span class="text-danger wlsm-font-bold">
			<?php esc_html_e( 'No staff found.', 'school-management' ); ?>
		</span>
	</div>
	<?php
	return;
}

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

$print_button_classes = 'btn btn-sm btn-success';

// Get school settings for background and signature
$settings_background = WLSM_M_Setting::get_settings_background( $school->ID );
$id_card_background = $settings_background['id_card_background'];
$school_signature = $settings_background['school_signature'];
?>

<!-- Print Staff ID cards. -->
<div class="wlsm-container d-flex mb-2">
	<div class="col-md-12 wlsm-text-center">
		<br>
		<button type="button" class="<?php echo esc_attr( $print_button_classes ); ?>" id="wlsm-print-staff-id-cards-btn" data-styles='["<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/bootstrap.min.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/wlsm-school-header.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/print/wlsm-id-cards.css' ); ?>"]' data-title="<?php esc_attr_e( 'Staff ID Cards', 'school-management' ); ?>">
			<?php esc_html_e( 'Print Staff ID Cards', 'school-management' ); ?>
		</button>
	</div>
</div>

<!-- Print Staff ID cards section. -->
<div class="wlsm-container wlsm" id="wlsm-print-staff-id-cards">
	<div class="wlsm-print-id-cards-container">
		<!-- Print Staff ID cards section. -->
		<?php foreach ( $staff_members as $staff_member ) { ?>
		<!-- Print Staff ID card section. -->
		<div class="wlsm wlsm-print-id-card">
			<div class="wlsm-print-id-card-container bg-img bg-cover" style="background: no-repeat center/100% url(<?php echo ( wp_get_attachment_url($id_card_background) );  ?>) !important;">

				<?php 
				// Include school header
				$school_id = $school->ID;
				require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; 
				?>

				<div class="row wlsm-print-id-card-details mt-1 mobile-id-card">
					<div class="col-8 wlsm-print-id-card-right">
						<ul>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Staff Name', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $staff_member->name ) ); ?></span>
							</li>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Staff ID', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( sprintf( 'STAFF-%04d', $staff_member->ID ) ); ?></span>
							</li>
							<?php if ( $staff_member->designation ) { ?>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Designation', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( $staff_member->designation ); ?></span>
							</li>
							<?php } ?>
							<?php if ( $staff_member->role_name ) { ?>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Role', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( $staff_member->role_name ); ?></span>
							</li>
							<?php } ?>
							<?php if ( $staff_member->phone ) { ?>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Phone', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( WLSM_M_Staff_Class::get_phone_text( $staff_member->phone ) ); ?></span>
							</li>
							<?php } ?>
							<?php if ( $staff_member->email ) { ?>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Email', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( $staff_member->email ); ?></span>
							</li>
							<?php } ?>
							<?php if ( $staff_member->joining_date ) { ?>
							<li>
								<span class="wlsm-font-bold"><?php esc_html_e( 'Joining Date', 'school-management' ); ?>:</span>
								<span><?php echo esc_html( WLSM_Config::get_date_text( $staff_member->joining_date ) ); ?></span>
							</li>
							<?php } ?>
						</ul>
					</div>

					<div class="col-3 wlsm-print-id-card-left">
						<div class="wlsm-print-id-card-photo-box">
							<?php if ( ! empty( $staff_member->photo_id ) ) { ?>
								<img src="<?php echo esc_url( wp_get_attachment_url( $staff_member->photo_id ) ); ?>" class="wlsm-print-id-card-photo">
							<?php } else { ?>
								<!-- Staff photo placeholder -->
								<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f8f9fa; color: #6c757d;">
									<i class="fas fa-user" style="font-size: 24pt;"></i>
								</div>
							<?php } ?>
						</div>
						<div class="wlsm-print-id-card-authorized-by">
							<?php if ( ! empty( $school_signature ) ) { ?>
								<img src="<?php echo esc_url( wp_get_attachment_url( $school_signature ) ); ?>" class="wlsm-print-id-card-signature">
							<?php } ?>
							<span><?php esc_html_e( 'Authorized By', 'school-management' ); ?></span>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php } ?>
	</div>
</div>