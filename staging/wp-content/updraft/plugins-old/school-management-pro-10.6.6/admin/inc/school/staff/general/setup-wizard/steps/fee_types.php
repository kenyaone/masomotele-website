<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Class.php';

$school_id = $current_school['id'];

// Get classes for the school
$classes = WLSM_M_Staff_Class::fetch_classes($school_id);

// Get student types for the school  
$student_types = WLSM_M_Staff_General::fetch_student_types($school_id);

// Fee periods
$fee_periods = WLSM_Helper::fee_period_list();

// Setup nonce for save fee action
$nonce_action = 'add-fee';
?>

<div class="wlsm-fee-types-step">
    <!-- Step Header -->
    <div class="step-header mb-5">
        <h5 class="step-title mb-0">
            <?php esc_html_e( 'Fee Types', 'school-management' ); ?>
        </h5>
    </div>

    <!-- Fee Types Management Form -->
    <form id="wlsm-step-fee-types-form" class="wlsm-setup-step-form" data-step="fee_types" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
        
        <!-- Hidden fields -->
        <?php $nonce = wp_create_nonce( $nonce_action ); ?>
        <input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">
        <input type="hidden" name="action" value="wlsm-save-fee">
        
        <!-- Quick Add Section -->
        <div class="mb-5 p-4 bg-light rounded">
            <h6 class="mb-4 text-dark font-weight-normal"><?php esc_html_e( 'Quick Add', 'school-management' ); ?></h6>
            <div class="quick-add-buttons d-flex flex-wrap">
                <?php 
                $common_fees = array(
                    array( 'label' => __( 'Tuition Fee', 'school-management' ), 'amount' => '5000' ),
                    array( 'label' => __( 'Admission Fee', 'school-management' ), 'amount' => '1000' ),
                    array( 'label' => __( 'Development Fee', 'school-management' ), 'amount' => '2000' ),
                    array( 'label' => __( 'Library Fee', 'school-management' ), 'amount' => '500' ),
                    array( 'label' => __( 'Laboratory Fee', 'school-management' ), 'amount' => '800' ),
                    array( 'label' => __( 'Sports Fee', 'school-management' ), 'amount' => '300' ),
                    array( 'label' => __( 'Transport Fee', 'school-management' ), 'amount' => '1200' ),
                    array( 'label' => __( 'Hostel Fee', 'school-management' ), 'amount' => '3000' ),
                    array( 'label' => __( 'Examination Fee', 'school-management' ), 'amount' => '200' ),
                    array( 'label' => __( 'Computer Fee', 'school-management' ), 'amount' => '600' )
                );
                foreach ( $common_fees as $fee ) :
                ?>
                <button type="button" class="btn btn-outline-primary btn-sm mr-3 mb-3 quick-add-fee-type d-flex flex-column align-items-center py-3 px-4" 
                        data-label="<?php echo esc_attr( $fee['label'] ); ?>" 
                        data-amount="<?php echo esc_attr( $fee['amount'] ); ?>"
                        style="min-width: 140px;">
                    <span class="font-weight-medium"><?php echo esc_html( $fee['label'] ); ?></span>
                    <small class="text-muted mt-1">₹<?php echo esc_html( number_format( $fee['amount'] ) ); ?></small>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Fee Types Management -->
        <div class="fee-types-management mb-5 p-4 bg-white border rounded">
            
            <!-- Section Header -->
            <div class="section-header mb-4 pb-3 border-bottom">
                <h6 class="mb-0 d-flex align-items-center justify-content-between text-dark font-weight-normal">
                    <span>
                        <?php esc_html_e( 'Fee Types', 'school-management' ); ?>
                    </span>
                    <span class="badge badge-secondary fee-type-count">0 types</span>
                </h6>
            </div>
            
            <!-- Fee Type Items Container -->
            <div class="fee-types-container">
                <!-- Container for dynamically added fee types -->
            </div>
            
            <!-- Action Buttons -->
            <div class="type-actions mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-primary add-fee-type-btn mr-3">
                    <?php esc_html_e( 'Add Another Fee Type', 'school-management' ); ?>
                </button>
                
                <button type="button" class="btn btn-outline-secondary clear-all-fee-types">
                    <?php esc_html_e( 'Clear All', 'school-management' ); ?>
                </button>
            </div>
            
        </div>
        
        <!-- Fee Structure Preview -->
        <div class="fee-preview mb-5 p-4 bg-light  rounded">
            <div class="section-header mb-3">
                <h6 class="mb-0 d-flex align-items-center justify-content-between ">
                    <span>
                        <?php esc_html_e( 'Fee Structure Preview', 'school-management' ); ?>
                    </span>
                    <span class="badge badge-light text-info total-amount">₹0</span>
                </h6>
            </div>
            <div class="preview-content">
                <p class="text-white-50 text-center mb-0">
                    <?php esc_html_e( 'Fee types will appear here as you add them.', 'school-management' ); ?>
                </p>
            </div>
        </div>
        
        <!-- Validation Alerts -->
        <div class="alerts-container">
            <div class="alert alert-warning" id="fee-types-validation-alert" style="display: none;">
                <span id="validation-message"><?php esc_html_e( 'Please add at least one fee type.', 'school-management' ); ?></span>
            </div>
            
            <div class="alert alert-info" id="fee-types-info-alert" style="display: none;">
                <span id="info-message"></span>
            </div>
        </div>
        
    </form>
</div>

<!-- Hidden template for fee type items -->
<div id="fee-type-template" style="display: none;">
    <div class="fee-type-item mb-4 p-4 bg-light rounded border-left border-primary" style="border-left-width: 4px !important;">
        <!-- Fee Type Name -->
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="mb-2 font-weight-medium"><?php esc_html_e( 'Fee Type Name', 'school-management' ); ?> *</label>
                <input type="text" 
                       class="form-control fee-type-name" 
                       placeholder="<?php esc_attr_e( 'Enter fee type name', 'school-management' ); ?>"
                       required>
            </div>
            <div class="col-md-4">
                <label class="mb-2 font-weight-medium"><?php esc_html_e( 'Amount', 'school-management' ); ?> *</label>
                <input type="number" 
                       class="form-control fee-type-amount" 
                       placeholder="<?php esc_attr_e( 'Enter amount', 'school-management' ); ?>"
                       min="0" 
                       step="0.01"
                       required>
            </div>
        </div>
        
        <!-- Class and Student Type Selection -->
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="mb-2 font-weight-medium"><?php esc_html_e( 'Classes', 'school-management' ); ?> *</label>
                <select class="form-control selectpicker fee-type-classes" 
                        data-live-search="true" 
                        data-selected-text-format="count > 2"
                        multiple
                        required>
                    <option value=""><?php esc_html_e( 'Select Classes', 'school-management' ); ?></option>
                    <?php foreach ( $classes as $class ) : ?>
                    <option value="<?php echo esc_attr( $class->ID ); ?>">
                        <?php echo esc_html( WLSM_M_Class::get_label_text( $class->label ) ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="mb-2 font-weight-medium"><?php esc_html_e( 'Student Types', 'school-management' ); ?> *</label>
                <select class="form-control selectpicker fee-type-student-types" 
                        data-live-search="true" 
                        data-selected-text-format="count > 2"
                        multiple
                        required>
                    <option value=""><?php esc_html_e( 'Select Student Types', 'school-management' ); ?></option>
                    <?php foreach ( $student_types as $student_type ) : ?>
                    <option value="<?php echo esc_attr( $student_type->label ); ?>">
                        <?php echo esc_html( $student_type->label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Period and Settings -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="mb-2 font-weight-medium"><?php esc_html_e( 'Period', 'school-management' ); ?> *</label>
                <select class="form-control fee-type-period" required>
                    <option value=""><?php esc_html_e( 'Select Period', 'school-management' ); ?></option>
                    <?php foreach ( $fee_periods as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label class="mb-3 font-weight-medium"><?php esc_html_e( 'Settings', 'school-management' ); ?></label>
                <div class="form-group">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" 
                               class="custom-control-input fee-type-admission" 
                               id="">
                        <label class="custom-control-label" for="">
                            <?php esc_html_e( 'Auto Generate Invoice On Admission', 'school-management' ); ?>
                        </label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" 
                               class="custom-control-input fee-type-dashboard" 
                               id="">
                        <label class="custom-control-label" for="">
                            <?php esc_html_e( 'Dashboard Disable', 'school-management' ); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Remove Button -->
        <div class="text-right">
            <button type="button" class="btn btn-sm btn-outline-danger remove-fee-type" title="<?php esc_attr_e( 'Remove fee type', 'school-management' ); ?>">
                <?php esc_html_e( 'Remove', 'school-management' ); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
// Make classes and student types data available to JavaScript
window.wlsmFeeTypesData = {
    classes: <?php echo json_encode($classes); ?>,
    studentTypes: <?php echo json_encode($student_types); ?>,
    feePeriods: <?php echo json_encode($fee_periods); ?>
};
console.log('Fee periods data:', window.wlsmFeeTypesData.feePeriods);
</script>