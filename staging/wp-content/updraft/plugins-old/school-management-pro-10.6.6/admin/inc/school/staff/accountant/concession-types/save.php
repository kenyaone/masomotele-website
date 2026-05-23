<?php
defined('ABSPATH') || die();

global $wpdb;
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';
$page_url = WLSM_M_Staff_Accountant::get_concession_types_page_url();

$school_id = $current_school['id'];

$concession_types = NULL;

$nonce_action         = 'add-concession-types';
$class_id             = '';
$concession_name      = '';
$concession_type      = 'fixed_amount';
$percentage_value     = '';
$fixed_amount         = '';
$eligibility_criteria = '';
$is_active            = 1;
$selected_fee_types   = array();

if (isset($_GET['id']) && !empty($_GET['id'])) {
	$id  = absint($_GET['id']);
	$concession_types = WLSM_M_Staff_Accountant::fetch_concession_types($school_id, $id);
	if ($concession_types) {
		$nonce_action = 'edit-concession_types-' . $concession_types->ID;

		$concession_name      = $concession_types->concession_name;
		$concession_type      = $concession_types->concession_type;
		$percentage_value     = $concession_types->percentage_value;
		$fixed_amount         = $concession_types->fixed_amount;
		$eligibility_criteria = $concession_types->eligibility_criteria;
		$class_id             = $concession_types->class_id;
		$is_active            = $concession_types->is_active;

		// Get mapped fee types
		$fee_mappings = WLSM_M_Staff_Accountant::get_concession_fee_mappings($concession_types->ID);
		foreach ($fee_mappings as $mapping) {
			$selected_fee_types[] = $mapping->fee_type_id;
		}
	}
}

if (! current_user_can('administrator')) {
	$current_user 	= WLSM_M_Role::can('assigned_class');
	if ($current_user) {
		$role 			= $current_user['school']['role'];
	}
	if ($current_user && $role !== 'admin') {
		$classes  = WLSM_M_Staff_Class::fetch_class_by_section_id($school_id, absint($current_school['section_id']));
	} else {
		$classes  = WLSM_M_Staff_Class::fetch_classes($school_id);
	}
} else {
	$classes  = WLSM_M_Staff_Class::fetch_classes($school_id);
}

// $classes = WLSM_M_Staff_Class::fetch_classes($school_id);
$concession_type_options = array(
	'percentage' => esc_html__('Percentage', 'school-management'),
	'fixed_amount' => esc_html__('Fixed Amount', 'school-management')
);

// Get available fee types for this school (will be loaded dynamically based on class selection)
$fee_types = array();
?>
<div class="row">
	<div class="col-md-12">
		<div class="mt-3 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading-box">
				<span class="wlsm-section-heading">
					<?php
					if ($concession_types) {
						printf(
							wp_kses(
								/* translators: %s: Concession_types type */
								__('Edit Concession Type: %s', 'school-management'),
								array(
									'span' => array('class' => array())
								)
							),
							esc_html(stripcslashes($concession_name))
						);
					} else {
						esc_html_e('Add New Concession Type', 'school-management');
					}
					?>
				</span>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url($page_url); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-file-invoice"></i>&nbsp;
					<?php esc_html_e('View All', 'school-management'); ?>
				</a>
			</span>
		</div>
		<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-save-concession-form">

			<?php $nonce = wp_create_nonce($nonce_action); ?>
			<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">

			<input type="hidden" name="action" value="wlsm-save-concession">

			<?php if ($concession_types) { ?>
				<input type="hidden" name="concession_id" value="<?php echo esc_attr($concession_types->ID); ?>">
			<?php } ?>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="form-group col-md-4">
						<label for="wlsm_concession_name" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Concession Name', 'school-management'); ?>:
						</label>
						<input type="text" name="concession_name" class="form-control" id="wlsm_concession_name" placeholder="<?php esc_attr_e('Enter concession name', 'school-management'); ?>" value="<?php echo esc_attr(stripcslashes($concession_name)); ?>">
					</div>
					<div class="form-group col-md-4">
						<label for="wlsm_class" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Class', 'school-management'); ?>:
						</label>
						<select name="class_id" class="form-control selectpicker" data-nonce="<?php echo esc_attr(wp_create_nonce('get-class-fee-types')); ?>" id="wlsm_class_concession_types" data-live-search="true">
							<option value=""><?php esc_html_e('Select Class', 'school-management'); ?></option>
							<?php foreach ($classes as $class) { ?>
								<option value="<?php echo esc_attr($class->ID); ?>" <?php selected($class->ID, $class_id, true); ?>>
									<?php echo esc_html(WLSM_M_Class::get_label_text($class->label)); ?>
								</option>
							<?php } ?>
						</select>
					</div>
					<div class="form-group col-md-4">
						<label class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Concession Type', 'school-management'); ?>:
						</label>
						<div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="concession_type" id="wlsm_concession_type_fixed" value="fixed_amount" <?php checked('fixed_amount', $concession_type, true); ?>>
								<label class="form-check-label" for="wlsm_concession_type_fixed">
									<?php esc_html_e('Fixed Amount', 'school-management'); ?>
								</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="concession_type" id="wlsm_concession_type_percentage" value="percentage" <?php checked('percentage', $concession_type, true); ?>>
								<label class="form-check-label" for="wlsm_concession_type_percentage">
									<?php esc_html_e('Percentage', 'school-management'); ?>
								</label>
							</div>
						</div>
					</div>
				</div>


				<div class="form-row">
					<div class="form-group col-md-6 wlsm-concession-input" id="wlsm_fixed_amount_group">
						<label for="wlsm_fixed_amount" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Fixed Amount', 'school-management'); ?>:
						</label>
						<input type="number" step="any" min="0" name="fixed_amount" class="form-control" id="wlsm_fixed_amount" placeholder="<?php esc_attr_e('Enter fixed amount', 'school-management'); ?>" value="<?php echo esc_attr($fixed_amount ? WLSM_Config::sanitize_money($fixed_amount) : ''); ?>">
						<small class="form-text text-muted"><?php esc_html_e('Enter the fixed concession amount', 'school-management'); ?></small>
					</div>
					<div class="form-group col-md-6 wlsm-concession-input" id="wlsm_percentage_value_group">
						<label for="wlsm_percentage_value" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Percentage Value', 'school-management'); ?>:
						</label>
						<input type="number" step="any" min="0" max="100" name="percentage_value" class="form-control" id="wlsm_percentage_value" placeholder="<?php esc_attr_e('Enter percentage (0-100)', 'school-management'); ?>" value="<?php echo esc_attr($percentage_value !== '' ? WLSM_Config::sanitize_money($percentage_value) : ''); ?>">
						<small class="form-text text-muted"><?php esc_html_e('Enter the concession percentage', 'school-management'); ?></small>
					</div>
				</div>



				<div class="form-row">
					<div class="form-group col-md-12">
						<label class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e('Applicable Fee Types', 'school-management'); ?>:
						</label>
						<div id="wlsm_fee_types_container" data-selected-fee-types='<?php echo esc_attr(json_encode($selected_fee_types)); ?>'>
							<div class="table-responsive">
								<table class="table table-bordered table-striped" id="wlsm_fee_types_table">
									<thead>
										<tr>
											<th style="width:40px;" class="text-center">
												#
											</th>
											<th><?php esc_html_e('Fee Type Name', 'school-management'); ?></th>
											<th class="text-right"><?php esc_html_e('Amount', 'school-management'); ?></th>
											<th><?php esc_html_e('Period', 'school-management'); ?></th>
											<th class="text-center"><?php esc_html_e('Period Value', 'school-management'); ?></th>
											<th class="text-right"><?php esc_html_e('Session Total', 'school-management'); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td colspan="6" class="text-muted text-center">
												<?php esc_html_e('Please select a class first', 'school-management'); ?>
											</td>
										</tr>
									</tbody>
									<tfoot id="wlsm_fee_types_footer" style="display: none;">
										<tr>
											<th colspan="5" class="text-right font-weight-bold">
												<?php esc_html_e('Total Session Amount:', 'school-management'); ?>
											</th>
											<th class="text-right font-weight-bold" id="wlsm_total_session_amount">
												-
											</th>
										</tr>
										<tr id="wlsm_concession_row" style="display: none;">
											<th colspan="5" class="text-right font-weight-bold">
												<span id="wlsm_concession_label"><?php esc_html_e('Concession Amount:', 'school-management'); ?></span>
											</th>
											<th class="text-right font-weight-bold" id="wlsm_concession_amount">
												-
											</th>
										</tr>
										<tr id="wlsm_payable_row" style="display: none;">
											<th colspan="5" class="text-right font-weight-bold">
												<?php esc_html_e('Payable Amount Will Be:', 'school-management'); ?>
											</th>
											<th class="text-right font-weight-bold" id="wlsm_payable_amount">
												-
											</th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>


								<div class="form-row">
					<div class="form-group col-md-12">
						<label for="wlsm_eligibility_criteria" class="wlsm-font-bold">
							<?php esc_html_e('Eligibility Criteria', 'school-management'); ?>:
						</label>
						<textarea name="eligibility_criteria" class="form-control" id="wlsm_eligibility_criteria" rows="3" placeholder="<?php esc_attr_e('Enter eligibility criteria for this concession', 'school-management'); ?>"><?php echo esc_textarea(stripcslashes($eligibility_criteria)); ?></textarea>
					</div>
				</div>

				<div class="form-row mt-1">
					<div class="form-group col-md-6">
						<input <?php checked($is_active, 1, true); ?> class="form-check-input mt-1" type="checkbox" name="is_active" id="wlsm_is_active" value="1">
						<label class="ml-4 mb-1 form-check-label wlsm-font-bold text-dark" for="wlsm_is_active">
							<?php esc_html_e('Active', 'school-management'); ?>
						</label>
						<small class="form-text text-muted"><?php esc_html_e('Check to make this concession type active and available for use.', 'school-management'); ?></small>
					</div>
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-12 text-center">
					<button type="submit" class="btn btn-primary" id="wlsm-save-concession-btn">
						<?php
						if ($concession_types) {
						?>
							<i class="fas fa-save"></i>&nbsp;
						<?php
							esc_html_e('Update Concession Type', 'school-management');
						} else {
						?>
							<i class="fas fa-plus-square"></i>&nbsp;
						<?php
							esc_html_e('Add New Concession Type', 'school-management');
						}
						?>
					</button>
				</div>
			</div>

		</form>
	</div>
</div>