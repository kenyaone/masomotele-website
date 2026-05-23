<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Class.php';

$permissions = $current_school['permissions'];
$school_id = $current_school['id'];

// Get assigned classes
global $wpdb;
$classes = $wpdb->get_results( $wpdb->prepare( "SELECT c.ID, c.label FROM " . WLSM_CLASSES . " c INNER JOIN " . WLSM_CLASS_SCHOOL . " cs ON c.ID = cs.class_id WHERE cs.school_id = %d ORDER BY c.label", $school_id ) );
?>
<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-unlink"></i>
				<?php esc_html_e( 'Unassign Class', 'school-management' ); ?>
			</span>
		</div>

		<?php if ( ! empty( $classes ) ) : ?>
		<form method="post" id="wlsm-unassign-class-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" >
			<?php $nonce = wp_create_nonce( 'wlsm-unassign-classes' ); ?>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" name="action" value="wlsm-unassign-classes">

			<div class="wlsm-form-section">
				<div class="row">
					<div class="col-md-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							<?php esc_html_e( 'Select Classes to Unassign', 'school-management' ); ?>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-8">
						<label for="wlsm_classes" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e( 'Classes', 'school-management' ); ?>:
						</label>
						<select name="class_ids[]" class="form-control selectpicker" id="wlsm_classes" data-noneSelectedText="<?php esc_attr_e( 'Select Classes', 'school-management' ); ?>" data-selectAllText="<?php esc_attr_e( 'Select All', 'school-management' ); ?>" data-deselectAllText="<?php esc_attr_e( 'Deselect All', 'school-management' ); ?>" title="<?php esc_attr_e( 'Select Classes', 'school-management' ); ?>" multiple data-actions-box="true">
							<?php foreach ( $classes as $class ) : ?>
							<option value="<?php echo esc_attr( $class->ID ); ?>">
								<?php echo esc_html( WLSM_M_Class::get_label_text( $class->label ) ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-12">
						<div class="wlsm-form-group">
							<div class="form-check form-inline">
								<input type="checkbox" class="form-check-input" id="wlsm_confirm_unassign" name="confirm_unassign" required>
								<label class="form-check-label wlsm-font-bold text-danger" for="wlsm_confirm_unassign">
									<i class="fas fa-exclamation-triangle"></i>
									<?php esc_html_e( 'I understand that this action will unassign the selected classes from this school and cannot be undone.', 'school-management' ); ?>
								</label>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<button type="submit" class="btn btn-danger" id="wlsm-unassign-class-btn">
							<i class="fas fa-unlink"></i>&nbsp;
							<?php esc_html_e( 'Unassign Selected Classes', 'school-management' ); ?>
						</button>
					</div>
				</div>
			</div>
		</form>
		<?php else : ?>
		<div class="wlsm-form-section">
			<div class="row">
				<div class="col-md-12">
					<div class="alert alert-info text-center">
						<i class="fas fa-info-circle"></i>
						<?php esc_html_e( 'No classes are currently assigned to this school.', 'school-management' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
