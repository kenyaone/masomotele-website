<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Accountant.php';

$page_url = WLSM_M_School::get_page_url();


// Get current school info
$user_info = WLSM_M_Role::get_user_info();
$current_school = $user_info['current_school'];
if ( ! $current_school ) {
	wp_die( __( 'School not found.', 'school-management' ) );
}

$school_id = $current_school['id'];
$school = WLSM_M_School::fetch_school( $school_id );

if ( ! $school ) {
	wp_die( __( 'School not found.', 'school-management' ) );
}

// Setup wizard configuration
$setup_steps = array(
	'welcome' => array(
		'title'       => __( 'Welcome', 'school-management' ),
		'description' => __( 'Welcome to the School Setup Wizard', 'school-management' ),
		'icon'        => 'fas fa-hand-wave',
		'required'    => false,
	),
	'classes' => array(
		'title'       => __( 'Assign Classes', 'school-management' ),
		'description' => __( 'Select and assign classes for your school', 'school-management' ),
		'icon'        => 'fas fa-chalkboard',
		'required'    => true,
	),
	'subjects' => array(
		'title'       => __( 'Add Subjects', 'school-management' ),
		'description' => __( 'Configure subjects for your classes', 'school-management' ),
		'icon'        => 'fas fa-book',
		'required'    => true,
	),
	'student_types' => array(
		'title'       => __( 'Student Types', 'school-management' ),
		'description' => __( 'Define different student types', 'school-management' ),
		'icon'        => 'fas fa-users',
		'required'    => true,
	),
	'fee_types' => array(
		'title'       => __( 'Fee Types', 'school-management' ),
		'description' => __( 'Set up fee structures and types', 'school-management' ),
		'icon'        => 'fas fa-money-bill-wave',
		'required'    => true,
	),
	// 'general_settings' => array(
	// 	'title'       => __( 'General Settings', 'school-management' ),
	// 	'description' => __( 'Configure general school settings', 'school-management' ),
	// 	'icon'        => 'fas fa-cog',
	// 	'required'    => false,
	// ),
	'registration_settings' => array(
		'title'       => __( 'Registration Settings', 'school-management' ),
		'description' => __( 'Set up student registration options', 'school-management' ),
		'icon'        => 'fas fa-user-plus',
		'required'    => false,
	),
	'complete' => array(
		'title'       => __( 'Extras', 'school-management' ),
		'description' => __( 'Setup wizard completed successfully', 'school-management' ),
		'icon'        => 'fas fa-check-circle',
		'required'    => false,
	),
);

// Get current step
$current_step = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : 'welcome';
if ( ! array_key_exists( $current_step, $setup_steps ) ) {
	$current_step = 'welcome';
}

// Calculate progress
$total_steps = count( $setup_steps );
$current_step_index = array_search( $current_step, array_keys( $setup_steps ) );

// Calculate progress based on actual data completion
function wlsm_get_setup_completion_status( $school_id ) {
	global $wpdb;

	$completion_status = array();

	// Check classes completion
	$assigned_classes = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM " . WLSM_CLASS_SCHOOL . " WHERE school_id = %d",
		$school_id
	) );
	$completion_status['classes'] = $assigned_classes > 0;

	// Check subjects completion (subjects are linked to school via class_school_id)
	$subjects_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(DISTINCT sj.ID) FROM " . WLSM_SUBJECTS . " as sj
		 JOIN " . WLSM_CLASS_SCHOOL . " as cs ON cs.ID = sj.class_school_id
		 WHERE cs.school_id = %d",
		$school_id
	) );
	$completion_status['subjects'] = $subjects_count > 0;

	// Check student types completion
	$student_types_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM " . WLSM_STUDENT_TYPE . " WHERE school_id = %d",
		$school_id
	) );
	$completion_status['student_types'] = $student_types_count > 0;

	// Check fee types completion
	$fee_types_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM " . WLSM_FEES . " WHERE school_id = %d",
		$school_id
	) );
	$completion_status['fee_types'] = $fee_types_count > 0;

	// Check registration settings completion
	$settings_registration = WLSM_M_Setting::get_settings_registration( $school_id );
	$completion_status['registration_settings'] = !empty( $settings_registration['form_title'] );

	// Welcome and complete steps are always considered complete when visited
	$completion_status['welcome'] = true;
	$completion_status['complete'] = true;

	return $completion_status;
}

// Get completion status for all steps
$completion_status = wlsm_get_setup_completion_status( $school_id );

// Calculate completion percentage based on required steps
$required_steps = array_filter( $setup_steps, function( $step ) {
	return $step['required'];
} );

$completed_required_steps = 0;
foreach ( array_keys( $required_steps ) as $step_key ) {
	if ( isset( $completion_status[ $step_key ] ) && $completion_status[ $step_key ] ) {
		$completed_required_steps++;
	}
}

$total_required_steps = count( $required_steps );
$data_completion_percentage = $total_required_steps > 0 ? ( $completed_required_steps / $total_required_steps ) * 100 : 0;

// For current step tracking (shows user progress through steps)
$step_navigation_percentage = ( ( $current_step_index + 1 ) / $total_steps ) * 100;

// Use data completion percentage as the main progress, but ensure it reflects current step navigation
$progress_percentage = max( $data_completion_percentage, ( $current_step_index / $total_steps ) * 100 );

// Register AJAX actions for both logged-in and non-logged-in users
// Include the main setup wizard template
require_once dirname( __FILE__ ) . '/index.php';
