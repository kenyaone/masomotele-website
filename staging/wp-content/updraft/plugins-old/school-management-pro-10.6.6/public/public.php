<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/constants.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_Language.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_Shortcode.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_Widget.php';

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Log.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Schedule.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Transport_Invoice_Manager.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Lecture.php';

require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Invoice.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Student.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Inquiry.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Registration.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Exam.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Certificate.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Invoice_History.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/WLSM_P_Print.php';
require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/class/WLSM_Staff_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'public/api/WLSM_Api.php';

require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/accountant/WLSM_Staff_Accountant.php';
add_filter('doing_it_wrong_trigger_error', function () {return false;}, 10, 0);
// Load translation.
add_action( 'plugins_loaded', array( 'WLSM_Language', 'load_translation' ) );

// Register widgets.
add_action( 'widgets_init', array( 'WLSM_Widget', 'register_widgets' ) );

// Add shortcodes.
add_shortcode( 'school_management_account', array( 'WLSM_Shortcode', 'account' ) );
add_shortcode( 'school_management_inquiry', array( 'WLSM_Shortcode', 'inquiry' ) );
add_shortcode( 'school_management_registration', array( 'WLSM_Shortcode', 'registration' ) );
add_shortcode( 'school_management_staff_registration', array( 'WLSM_Shortcode', 'staff_registration' ) );
add_shortcode( 'school_management_fees', array( 'WLSM_Shortcode', 'fees' ) );
add_shortcode( 'school_management_noticeboard', array( 'WLSM_Shortcode', 'noticeboard' ) );
add_shortcode( 'school_management_exam_time_table', array( 'WLSM_Shortcode', 'exam_time_table' ) );
add_shortcode( 'school_management_exam_admit_card', array( 'WLSM_Shortcode', 'exam_admit_card' ) );
add_shortcode( 'school_management_exam_result', array( 'WLSM_Shortcode', 'exam_result' ) );
add_shortcode( 'school_management_certificate', array( 'WLSM_Shortcode', 'certificate' ) );
add_shortcode( 'school_management_lesson', array( 'WLSM_Shortcode', 'lesson' ) );
add_shortcode( 'school_management_invoice_history', array( 'WLSM_Shortcode', 'invoice_history' ) );
add_shortcode( 'school_management_zoom_redirect', array( 'WLSM_Shortcode', 'zoom_redirect' ) );

add_shortcode( 'school_register', array( 'WLSM_Shortcode', 'school_register' ) );

// Enqueue shortcode assets.
add_action('wp_enqueue_scripts', array( 'WLSM_Shortcode', 'enqueue_assets' ) );

if ( class_exists( 'WooCommerce' ) ) {
	// Show admin bar for woocommerce.
	add_filter( 'woocommerce_prevent_admin_access', '__return_false' );
	add_filter( 'woocommerce_disable_admin_bar', '__return_false' );
}

// Schedules.
//  Notification for invoice due date.
if ( ! wp_next_scheduled( 'wlsm_notify_for_invoice_due_date' ) ) {
	wp_schedule_event( time(), 'daily', 'wlsm_notify_for_invoice_due_date' );
}
add_action( 'wlsm_notify_for_invoice_due_date', array( 'WLSM_Schedule', 'notify_for_invoice_due_date' ), 10, 3 );

add_action( 'wlsm_notify_for_student_admission', array( 'WLSM_Schedule', 'notify_for_student_admission' ), 10, 4 );
add_action( 'wlsm_notify_for_invoice_generated', array( 'WLSM_Schedule', 'notify_for_invoice_generated' ), 10, 3 );
add_action( 'wlsm_notify_for_online_fee_submission', array( 'WLSM_Schedule', 'notify_for_online_fee_submission' ), 10, 3 );
add_action( 'wlsm_notify_for_offline_fee_submission', array( 'WLSM_Schedule', 'notify_for_offline_fee_submission' ), 10, 3 );
add_action( 'wlsm_notify_for_student_admission_to_parent', array( 'WLSM_Schedule', 'notify_for_student_admission_to_parent' ), 10, 4 );
add_action( 'wlsm_notify_for_invoice_generated_to_parent', array( 'WLSM_Schedule', 'notify_for_invoice_generated_to_parent' ), 10, 3 );
add_action( 'wlsm_notify_for_online_fee_submission_to_parent', array( 'WLSM_Schedule', 'notify_for_online_fee_submission_to_parent' ), 10, 3 );
add_action( 'wlsm_notify_for_offline_fee_submission_to_parent', array( 'WLSM_Schedule', 'notify_for_offline_fee_submission_to_parent' ), 10, 3 );
add_action( 'wlsm_notify_for_absent_student', array( 'WLSM_Schedule', 'notify_for_absent_student' ), 10, 4 );
add_action( 'wlsm_notify_for_student_absent_to_student', array( 'WLSM_Schedule', 'notify_for_absent_student' ), 10, 4 );
add_action( 'wlsm_notify_for_custom_message', array( 'WLSM_Schedule', 'notify_for_custom_message' ), 10, 1 );
add_action( 'wlsm_notify_for_homework_message', array( 'WLSM_Schedule', 'notify_for_homework_message' ), 10, 3 );
add_action( 'wlsm_notify_for_exam_result_report', array( 'WLSM_Schedule', 'notify_for_exam_result_report' ), 10, 4 );
add_action( 'wlsm_notify_for_inquiry_received_to_inquisitor', array( 'WLSM_Schedule', 'notify_for_inquiry_received_to_inquisitor' ), 10, 2 );
add_action( 'wlsm_notify_for_inquiry_received_to_admin', array( 'WLSM_Schedule', 'notify_for_inquiry_received_to_admin' ), 10, 2 );
add_action( 'wlsm_notify_for_student_registration_to_student', array( 'WLSM_Schedule', 'notify_for_student_registration_to_student' ), 10, 4 );
add_action( 'wlsm_notify_for_student_registration_to_admin', array( 'WLSM_Schedule', 'notify_for_student_registration_to_admin' ), 10, 4 );

// Get students with pending invoices.
add_action( 'wp_ajax_wlsm-p-get-students-with-pending-invoices', array( 'WLSM_P_Invoice', 'get_students_with_pending_invoices' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-students-with-pending-invoices', array( 'WLSM_P_Invoice', 'get_students_with_pending_invoices' ) );

// Get student pending invoices.
add_action( 'wp_ajax_wlsm-p-get-student-pending-invoices', array( 'WLSM_P_Invoice', 'get_student_pending_invoices' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-student-pending-invoices', array( 'WLSM_P_Invoice', 'get_student_pending_invoices' ) );

// Get student pending invoice.
add_action( 'wp_ajax_wlsm-p-get-student-pending-invoice', array( 'WLSM_P_Invoice', 'get_student_pending_invoice' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-student-pending-invoice', array( 'WLSM_P_Invoice', 'get_student_pending_invoice' ) );

// Get student pending invoice.
add_action( 'wp_ajax_wlsm-p-get-student-pending-invoice-bulk', array( 'WLSM_P_Invoice', 'get_student_pending_invoice_bulk' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-student-pending-invoice-bulk', array( 'WLSM_P_Invoice', 'get_student_pending_invoice_bulk' ) );

// Pay invoice amount.
add_action( 'wp_ajax_wlsm-p-pay-invoice-amount', array( 'WLSM_P_Invoice', 'pay_invoice_amount' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-pay-invoice-amount', array( 'WLSM_P_Invoice', 'pay_invoice_amount' ) );

// Submit inquiry.
add_action( 'wp_ajax_wlsm-p-submit-inquiry', array( 'WLSM_P_Inquiry', 'submit_inquiry' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-submit-inquiry', array( 'WLSM_P_Inquiry', 'submit_inquiry' ) );

// Submit registration.
add_action( 'wp_ajax_wlsm-p-submit-registration', array( 'WLSM_P_Registration', 'submit_registration' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-submit-registration', array( 'WLSM_P_Registration', 'submit_registration' ) );

// Submit school_register.
add_action( 'wp_ajax_wlsm-p-submit-school_register', array( 'WLSM_P_Registration', 'submit_school_register' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-submit-school_register', array( 'WLSM_P_Registration', 'submit_school_register' ) );

// Submit staff registration.
add_action( 'wp_ajax_wlsm-p-submit-staff-registration', array( 'WLSM_P_Registration', 'submit_staff_registration' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-submit-staff-registration', array( 'WLSM_P_Registration', 'submit_staff_registration' ) );

// Process Razorpay.
add_action( 'wp_ajax_wlsm-p-pay-with-razorpay', array( 'WLSM_P_Invoice', 'process_razorpay' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-razorpay', array( 'WLSM_P_Invoice', 'process_razorpay' ) );

// Process Stripe.
add_action( 'wp_ajax_wlsm-p-pay-with-stripe', array( 'WLSM_P_Invoice', 'process_stripe' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-stripe', array( 'WLSM_P_Invoice', 'process_stripe' ) );

// Process PayPal.
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-paypal', array( 'WLSM_P_Invoice', 'process_paypal' ) );

// Process Amberpay.
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-amberpay', array( 'WLSM_P_Invoice', 'process_amberpay' ) );
add_action( 'wp_ajax_wlsm-p-pay-with-amberpay', array( 'WLSM_P_Invoice', 'process_amberpay' ) );

// PayU callback rewrite endpoint.
add_action( 'init', function () {
	add_rewrite_rule( 'wlsm-payu-callback/?$', 'index.php?wlsm_payu_callback=1', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'wlsm_payu_callback';
	return $vars;
} );

add_action( 'template_redirect', function () {
	if ( get_query_var( 'wlsm_payu_callback' ) ) {
		WLSM_P_Invoice::process_payu();
		exit;
	}
} );

// Process SSLCommerz.
add_action( 'wp_ajax_wlsm-p-pay-with-sslcommerz', array( 'WLSM_P_Invoice', 'process_sslcommerz' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-sslcommerz', array( 'WLSM_P_Invoice', 'process_sslcommerz' ) );

// Process Pesapal.
add_action( 'wp_ajax_wlsm-p-pay-with-pesapal', array( 'WLSM_P_Invoice', 'process_pesapal' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-pesapal', array( 'WLSM_P_Invoice', 'process_pesapal' ) );

// Process Paystack.
add_action( 'wp_ajax_wlsm-p-pay-with-paystack', array( 'WLSM_P_Invoice', 'process_paystack' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-pay-with-paystack', array( 'WLSM_P_Invoice', 'process_paystack' ) );

// Process SSLCommerz.
add_action( 'init', array( 'WLSM_P_Invoice', 'process_sslcommerz' ), 20000 );

// Process Paytm.
add_action( 'init', array( 'WLSM_P_Invoice', 'process_paytm' ) );

// Process PayU.
add_action( 'init', array( 'WLSM_P_Invoice', 'process_payu' ) );

// Get exam time table.
add_action( 'wp_ajax_wlsm-p-get-exam-time-table', array( 'WLSM_P_Exam', 'get_exam_time_table' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-exam-time-table', array( 'WLSM_P_Exam', 'get_exam_time_table' ) );

// view attendance
add_action( 'wp_ajax_wlsm-view-attendance-student', array( 'WLSM_Staff_Class', 'view_attendance_student' ) );

// Get exam admit card.
add_action( 'wp_ajax_wlsm-p-get-exam-admit-card', array( 'WLSM_P_Exam', 'get_exam_admit_card' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-exam-admit-card', array( 'WLSM_P_Exam', 'get_exam_admit_card' ) );

// Get exam result.
add_action( 'wp_ajax_wlsm-p-get-exam-result', array( 'WLSM_P_Exam', 'get_exam_result' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-exam-result', array( 'WLSM_P_Exam', 'get_exam_result' ) );

// Get certificate.
add_action( 'wp_ajax_wlsm-p-get-certificate', array( 'WLSM_P_Certificate', 'get_certificate' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-certificate', array( 'WLSM_P_Certificate', 'get_certificate' ) );

// Get invoice_history.
add_action( 'wp_ajax_wlsm-p-get-pending-invoices-history', array( 'WLSM_P_Invoice_History', 'get_invoice_history' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-pending-invoices-history', array( 'WLSM_P_Invoice_History', 'get_invoice_history' ) );

// General Actions.
add_action( 'wp_ajax_wlsm-p-get-school-classes', array( 'WLSM_P_General', 'get_school_classes' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-school-classes', array( 'WLSM_P_General', 'get_school_classes' ) );
add_action( 'wp_ajax_wlsm-p-get-class-sections', array( 'WLSM_P_General', 'get_class_sections' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-class-sections', array( 'WLSM_P_General', 'get_class_sections' ) );
add_action( 'wp_ajax_wlsm-p-get-school-routes-vehicles', array( 'WLSM_P_General', 'get_school_routes_vehicles' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-school-routes-vehicles', array( 'WLSM_P_General', 'get_school_routes_vehicles' ) );
add_action( 'wp_ajax_wlsm-p-get-school-exams-time-table', array( 'WLSM_P_General', 'get_school_exams_time_table' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-school-exams-time-table', array( 'WLSM_P_General', 'get_school_exams_time_table' ) );
add_action( 'wp_ajax_wlsm-p-get-school-exams-admit-card', array( 'WLSM_P_General', 'get_school_exams_admit_card' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-school-exams-admit-card', array( 'WLSM_P_General', 'get_school_exams_admit_card' ) );
add_action( 'wp_ajax_wlsm-p-get-school-exams-result', array( 'WLSM_P_General', 'get_school_exams_result' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-school-exams-result', array( 'WLSM_P_General', 'get_school_exams_result' ) );
add_action( 'wp_ajax_wlsm-p-get-school-certificates', array( 'WLSM_P_General', 'get_school_certificates' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-school-certificates', array( 'WLSM_P_General', 'get_school_certificates' ) );

add_action( 'wp_ajax_wlsm-p-get-student-types', array( 'WLSM_P_General', 'get_student_types' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-student-types', array( 'WLSM_P_General', 'get_student_types' ) );

add_action( 'wp_ajax_wlsm-p-get-class-fees', array( 'WLSM_P_General', 'get_class_fees' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-class-fees', array( 'WLSM_P_General', 'get_class_fees' ) );

add_action( 'wp_ajax_wlsm-p-get-class-subjects', array( 'WLSM_P_General', 'get_class_subjects' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-class-subjects', array( 'WLSM_P_General', 'get_class_subjects' ) );
add_action( 'wp_ajax_wlsm-p-get-subject-chapter', array( 'WLSM_P_General', 'get_subject_chapters' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-subject-chapter', array( 'WLSM_P_General', 'get_subject_chapters' ) );

add_action( 'wp_ajax_wlsm-p-get-class-activity', array( 'WLSM_P_General', 'get_class_activity' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-get-class-activity', array( 'WLSM_P_General', 'get_class_activity' ) );

add_action( 'wp_ajax_wlsm-p-submit-lessons', array( 'WLSM_P_General', 'get_lessons' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-submit-lessons', array( 'WLSM_P_General', 'get_lessons' ) );

// Student: BigBlueButton join meeting.
add_action( 'wp_ajax_wlsm-student-bbb-join-meeting', array( 'WLSM_P_General', 'student_bbb_join_meeting' ) );

// Student: Print ID card.
add_action( 'wp_ajax_wlsm-p-st-print-id-card', array( 'WLSM_P_Print', 'student_print_id_card' ) );

// Parent: Print ID card.
add_action( 'wp_ajax_wlsm-p-pr-print-id-card', array( 'WLSM_P_Print', 'parent_print_id_card' ) );

// Student: Print invoice payment.
add_action( 'wp_ajax_wlsm-p-st-print-invoice-payment', array( 'WLSM_P_Print', 'student_print_payment' ) );

// Parent: Print invoice payment.
add_action( 'wp_ajax_wlsm-p-pr-print-invoice-payment', array( 'WLSM_P_Print', 'parent_print_payment' ) );

// Student: View study material.
add_action( 'wp_ajax_wlsm-p-st-view-study-material', array( 'WLSM_P_Student', 'view_study_material' ) );

// Student: View homework.
add_action( 'wp_ajax_wlsm-p-st-view-homework', array( 'WLSM_P_Student', 'view_homework' ) );

// Student: Join event.
add_action( 'wp_ajax_wlsm-p-st-join-event', array( 'WLSM_P_Student', 'join_event' ) );
add_action( 'wp_ajax_wlsm-p-st-unjoin-event', array( 'WLSM_P_Student', 'unjoin_event' ) );

// Student: Submit leave request.
add_action( 'wp_ajax_wlsm-p-st-submit-student-leave-request', array( 'WLSM_P_Student', 'submit_leave_request' ) );

// Student: homework submit
add_action('wp_ajax_wlsm-p-st-submit-studennt-homework-submission', array( 'WLSM_P_Student', 'submit_homework' ) );

// Account settings.
add_action( 'wp_ajax_wlsm-p-save-account-settings', array( 'WLSM_P_General', 'save_account_settings' ) );

// Student: Print class time table.
add_action( 'wp_ajax_wlsm-p-st-print-class-time-table', array( 'WLSM_P_Print', 'student_print_class_time_table' ) );

// Parent: Print class time table.
add_action( 'wp_ajax_wlsm-p-pr-print-class-time-table', array( 'WLSM_P_Print', 'parent_print_class_time_table' ) );

// Parent: Print class time table.
add_action( 'wp_ajax_wlsm-p-pr-print-class-time-table', array( 'WLSM_P_Print', 'parent_print_class_time_table' ) );

// Student: Print exam time table.
add_action( 'wp_ajax_wlsm-p-st-print-exam-time-table', array( 'WLSM_P_Print', 'student_print_exam_time_table' ) );

// Student: Print exam admit card.
add_action( 'wp_ajax_wlsm-p-st-print-exam-admit-card', array( 'WLSM_P_Print', 'student_print_exam_admit_card' ) );

// Student: live class ratting.
add_action( 'wp_ajax_wlsm-p-st-print-staff-ratting', array( 'WLSM_P_Student', 'staff_class_ratting' ) );
add_action( 'wp_ajax_wlsm-p-staff_class_ratting', array( 'WLSM_P_Student', 'save_ratting' ) );

// switch student
add_action( 'wp_ajax_wlsm-p-switch-student', array( 'WLSM_P_Student', 'switch_student' ) );

// Student: Print exam results.
add_action( 'wp_ajax_wlsm-p-st-print-exam-results', array( 'WLSM_P_Print', 'student_print_exam_results' ) );

add_action( 'wp_ajax_wlsm-p-result-subject-wise', array( 'WLSM_P_Print', 'student_exam_result_subjectwise' ) );

// Student: Print results assessment.
add_action( 'wp_ajax_wlsm-p-st-print-results-assessment', array( 'WLSM_P_Print', 'student_print_results_assessment' ) );
add_action( 'wp_ajax_wlsm-p-st-print-results-subject-wise', array( 'WLSM_P_Print', 'student_print_results_subject_wise' ) );

// Student: View academic reports.
add_action( 'wp_ajax_wlsm-p-st-view-academic-report', array( 'WLSM_P_Print', 'student_view_academic_report' ) );
add_action( 'wp_ajax_wlsm-p-st-view-academic-multi-group-report', array( 'WLSM_P_Print', 'student_view_academic_multi_group_report' ) );

// Parent: Print exam results.
add_action( 'wp_ajax_wlsm-p-pr-print-exam-results', array( 'WLSM_P_Print', 'parent_print_exam_results' ) );

// Shortcode: Print exam time table.
add_action( 'wp_ajax_wlsm-p-print-exam-time-table', array( 'WLSM_P_Print', 'print_exam_time_table' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-print-exam-time-table', array( 'WLSM_P_Print', 'print_exam_time_table' ) );

add_action( 'wp_ajax_wlsm-p-st-ticket', array( 'WLSM_P_Print', 'view_ticket' ) );
add_action( 'wp_ajax_nopriv_wlsm-p-st-ticket', array( 'WLSM_P_Print', 'view_ticket' ) );

add_action('wp_ajax_wlsm_save_ticket', array( 'WLSM_P_Student', 'save_ticket' ));
add_action('wp_ajax_nopriv_wlsm_save_ticket', array( 'WLSM_P_Student', 'save_ticket' ));

// After user is logged in.
add_action( 'wp_login', array( 'WLSM_Log', 'login_record' ) );

// Delete old logs.
if ( ! wp_next_scheduled( 'wlsm_delete_old_logs' ) ) {
	wp_schedule_event( time(), 'daily', 'wlsm_delete_old_logs' );
}
add_action( 'wlsm_delete_old_logs', array( 'WLSM_Log', 'delete_old_logs' ) );

// API routes.
add_action( 'rest_api_init', array( 'WLSM_Api', 'register_rest_routes' ) );

// Filter API response.
add_filter( 'jwt_auth_token_before_dispatch', array( 'WLSM_Api', 'token_before_dispatch' ), 10, 2 );

// Custom Cron Recurrences
function custom_cron_job_recurrence( $schedules ) {
    $schedules['monthly'] = array(
        'display'  => __( 'Once monthly', 'school-management' ),
        'interval' => 2635200,
    );
    $schedules['3month'] = array(
        'display'  => __( 'Three months', 'school-management' ),
        'interval' => 7889238,
    );
    $schedules['4month'] = array(
        'display'  => __( 'Four months', 'school-management' ),
        'interval' => 10518984,
    );
    $schedules['6month'] = array(
        'display'  => __( 'Six months', 'school-management' ),
        'interval' => 15778476,
    );
    $schedules['12month'] = array(
        'display'  => __( 'Yearly', 'school-management' ),
        'interval' => 31556952,
    );
    $schedules['30min'] = array(
        'display'  => __( 'Every 30 minutes', 'school-management' ),
        'interval' => 1800,
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'custom_cron_job_recurrence' );

// Schedule month Cron Job Event
function wlsm_custom_cron_job() {
    // Remove the date('d') === '01' condition that was preventing cron scheduling
    // Schedule monthly invoices (runs every month)
    if ( ! wp_next_scheduled( 'wlsm_monthly_invoices' ) ) {
        // Schedule for the 1st of next month at 2 AM
        $next_month_first = strtotime('first day of next month 02:00:00');
        wp_schedule_event( $next_month_first, 'monthly', 'wlsm_monthly_invoices' );
    }

    // Schedule quarterly invoices (every 3 months)
    if ( ! wp_next_scheduled( 'wlsm_three_month' ) ) {
        // Schedule for 3 months from now
        $next_quarter = strtotime('+3 months first day of month 02:15:00');
        wp_schedule_event( $next_quarter, '3month', 'wlsm_three_month' );
    }

    // Schedule four month invoices
    if ( ! wp_next_scheduled( 'wlsm_four_month' ) ) {
        $next_four_months = strtotime('+4 months first day of month 02:30:00');
        wp_schedule_event( $next_four_months, '4month', 'wlsm_four_month' );
    }

    // Schedule half-yearly invoices (every 6 months)
    if ( ! wp_next_scheduled( 'wlsm_six_month' ) ) {
        $next_half_year = strtotime('+6 months first day of month 02:45:00');
        wp_schedule_event( $next_half_year, '6month', 'wlsm_six_month' );
    }

    // Schedule yearly invoices
    if ( ! wp_next_scheduled( 'wlsm_year' ) ) {
        $next_year = strtotime('+1 year first day of month 03:00:00');
        wp_schedule_event( $next_year, '12month', 'wlsm_year' );
    }

    // Demo data generation (if needed)
    if ( ! wp_next_scheduled( 'wlsm_generate_demo_data' ) ) {
        wp_schedule_event( time(), '30min', 'wlsm_generate_demo_data' );
    }
}
add_action( 'wp', 'wlsm_custom_cron_job' );
add_action( 'wlsm_monthly_invoices', array( 'WLSM_Staff_Accountant', 'invoice_fee_auto_generate' ) );
add_action( 'wlsm_three_month', array( 'WLSM_Staff_Accountant', 'wlsm_three_month' ) );
add_action( 'wlsm_four_month', array( 'WLSM_Staff_Accountant', 'wlsm_quadrimester' ) );
add_action( 'wlsm_six_month', array( 'WLSM_Staff_Accountant', 'wlsm_half_yearly' ) );
add_action( 'wlsm_year', array( 'WLSM_Staff_Accountant', 'wlsm_annually' ) );

// Transport recurring invoice generation hooks - Register with higher priority to ensure they run
add_action( 'wlsm_monthly_invoices', array( 'WLSM_Transport_Invoice_Manager', 'generate_monthly_transport_invoices' ), 5 );
add_action( 'wlsm_three_month', array( 'WLSM_Transport_Invoice_Manager', 'generate_quarterly_transport_invoices' ), 5 );
add_action( 'wlsm_six_month', array( 'WLSM_Transport_Invoice_Manager', 'generate_half_yearly_transport_invoices' ), 5 );
add_action( 'wlsm_year', array( 'WLSM_Transport_Invoice_Manager', 'generate_yearly_transport_invoices' ), 5 );

// for demo only
// add_action( 'wlsm_generate_demo_data', 'wlsm_cron_generate_demo_data' );

// Demo data generation cron job wrapper
function wlsm_cron_generate_demo_data() {
    try {
        // Check if demo mode is enabled or if we should generate demo data
        // if ( defined( 'WLSM_DEMO_MODE' ) && WLSM_DEMO_MODE ) {
            WLSM_Database::generate_demo_data();

            // Log successful generation
            error_log( 'WLSM: Demo data generated successfully at ' . current_time( 'Y-m-d H:i:s' ) );
        // }
    } catch ( Exception $e ) {
        // Log errors
        error_log( 'WLSM: Error generating demo data: ' . $e->getMessage() );
    }
}
