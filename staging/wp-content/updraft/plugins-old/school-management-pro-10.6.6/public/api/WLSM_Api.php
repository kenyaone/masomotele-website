<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Library.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Parent.php';

class WLSM_Api
{
	const NS = 'sm';
	const GLOBAL_PREFIX = 'global';
	const SCHOOL_PREFIX = 'school';
	const STUDENT_PREFIX = 'student';
	const PARENT_PREFIX = 'parent';
	const STAFF_PREFIX = 'staff';

	// Checks if user is student or parent.
	public static function token_before_dispatch($data, $user)
	{

		$user_id = $user->ID;

		$student = WLSM_M::get_student($user_id);
		$student_logo = WLSM_M::get_student_profile($user_id);
		$staff = WLSM_M::get_staff($user_id);

		if (!empty($student_logo->photo_id)) {
			$student_photo = wp_get_attachment_url($student_logo->photo_id);
		} else {
			$student_photo = " ";
		}

		$staff_photo = " ";

		if ($student) {
			$data['user_type'] = self::STUDENT_PREFIX;
			$data['photo'] = esc_url($student_photo);
			$data['user_display_name'] = $student->student_name;

			$school_id = $student->school_id;

			$school = WLSM_M_School::fetch_school($school_id);

			$school_data = array();

			if ($school) {
				// General settings.
				$settings_general = WLSM_M_Setting::get_settings_general($school_id);
				//$school_logo      = $settings_general['school_logo'];

				if (wp_get_attachment_url($settings_general['school_logo'])) {
					$school_logo = wp_get_attachment_url($settings_general['school_logo']);
				} else {
					$school_logo = "";
				}

				$school_data = array(
					'name' => esc_html(WLSM_M_School::get_label_text($school->label)),
					'phone' => esc_html(WLSM_M_School::get_phone_text($school->phone)),
					'email' => esc_html(WLSM_M_School::get_email_text($school->email)),
					'address' => esc_html(WLSM_M_School::get_address_text($school->address)),
					'logo' => esc_url($school_logo),
				);
			}

			$data['school'] = $school_data;
		} elseif ($staff) {
			$data['user_type'] = self::STAFF_PREFIX;
			$data['photo'] = esc_url($staff_photo);
			$data['user_display_name'] = $staff->staff_name;

			$school_id = $staff->school_id;

			$school = WLSM_M_School::fetch_school($school_id);

			$school_data = array();

			if ($school) {
				// General settings.
				$settings_general = WLSM_M_Setting::get_settings_general($school_id);

				if (wp_get_attachment_url($settings_general['school_logo'])) {
					$school_logo = wp_get_attachment_url($settings_general['school_logo']);
				} else {
					$school_logo = "";
				}

				$school_data = array(
					'name' => esc_html(WLSM_M_School::get_label_text($school->label)),
					'phone' => esc_html(WLSM_M_School::get_phone_text($school->phone)),
					'email' => esc_html(WLSM_M_School::get_email_text($school->email)),
					'address' => esc_html(WLSM_M_School::get_address_text($school->address)),
					'logo' => esc_url($school_logo),
				);
			}

			$data['school'] = $school_data;
		} else {
			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);
			if (count($unique_student_ids)) {
				$data['user_type'] = self::PARENT_PREFIX;
			} else {
				return self::no_account();
			}
		}

		return $data;
	}

	// API routes.
	public static function register_rest_routes()
	{
		// Global - Settings.
		register_rest_route(
			self::NS,
			self::GLOBAL_PREFIX . '/settings',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'global_settings'),
				'permission_callback' => '__return_true',
			)
		);

		// Account Settings.
		register_rest_route(
			self::NS,
			'account-settings',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'account_settings'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Profile.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/profile',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_profile'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Dashboard.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/dashboard',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_dashboard'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Study materials.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/study-materials',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_study_materials'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Noticeboard.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/noticeboard',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_noticeboard'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Fee Invoices.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/fee-invoices',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'fee_invoices'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Fee Invoice.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/fee-invoices/(?P<invoice_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'fee_invoice'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Payment History.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/payments',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_payments'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Payment Receipt.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/payments/(?P<payment_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_payment'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Events.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/events',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_events'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Event.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/events/(?P<event_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Join event.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/events/(?P<event_id>\d+)/join',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_join_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Unjoin event.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/events/(?P<event_id>\d+)/unjoin',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_unjoin_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Class time table.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/class-time-table',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Books Issued.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/books-issued',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_books_issued'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Book Issued.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/books-issued/(?P<book_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_book_issued'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Live Classes.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/live-classes',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_live_classes'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Attendance.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/attendance',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Study materials.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/study-materials',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_study_materials'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Study material.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/study-materials/(?P<study_material_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_study_material'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Homeworks.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/homework',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_homeworks'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Homework.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/homework/(?P<homework_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Exams time table.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/exam-time-table',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'exams_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Exam time table.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/exam-time-table/(?P<exam_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'exam_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Admit cards.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/admit-cards',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'admit_cards'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Admit card.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/admit-cards/(?P<admit_card_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'admit_card'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Exams results.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/exam-results',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'exam_results'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Exam result.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/exam-results/(?P<admit_card_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'exam_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Overall result.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/overall-result',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'overall_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Leave requests.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/leave-requests',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_leave_requests'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Submit Leave request.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/submit-leave-request',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_submit_leave_request'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Homework request.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/submit-homework-request',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_submit_homework_request'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Submitted homeworks.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/submitted-homeworks',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_submitted_homeworks'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Fetch submitted homework.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/fetch-submitted-homework',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'fetch_submitted_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Submit Invoice payment request.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/submit-invoice-payment-request',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_submit_invoice_payment_request'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - About school.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/about-school',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_about_school'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Profile Update.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/profile-update',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_profile_update'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Students.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_students'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Noticeboard.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/noticeboard',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_noticeboard'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Fee Invoices.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/fee-invoices',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_fee_invoices'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Fee Invoice.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/fee-invoices/(?P<invoice_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_fee_invoice'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Payment History.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/payments',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_payments'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Payment Receipt.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/payments/(?P<payment_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_payment'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Class time table.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/class-time-table',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Attendance.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/attendance',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Exams results.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/exam-results',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_exam_results'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Exam result.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/students/(?P<student_id>\d+)' . '/exam-results/(?P<admit_card_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_exam_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - lesson.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/lesson',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_lessons'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - lessons by subject id and chapter id.
		// Add this to the register_rest_routes() method in WLSM_Api class
		// New improved endpoint with query parameters
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/lessons',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_lessons_by_filters'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - lesson details by ID.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/lesson-details/(?P<lesson_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_lesson_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Student - Submit Invoice payment request.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/st_notification/add',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'st_notification_add'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Students.
		register_rest_route(
			self::NS,
			self::STUDENT_PREFIX . '/fetch_students_notification',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'get_students_notification'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Dashboard.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/dashboard',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'parent_dashboard'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Student And Attendance.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/student-and-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_student_and_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Noticeboard.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/noticeboard',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_noticeboard_data'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Class time table.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/class-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_class_time_table_data'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Events
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/events',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_events'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Events
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/event',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Events
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/event/join',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_join_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Events
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/event/unjoin',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_unjoin_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Exams time table.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/exams-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_exams_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Exam time table.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/exam-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_exam_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Admit cards.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/admit-cards',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_admit_cards'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Admit card.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/admit-card',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_admit_card'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Exams results.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/exam-results',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_results_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Exam result.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/exam-result',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_result_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Overall result.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/overall-result',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_overall_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Attendance.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'attendance_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Books Issued.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/books-issued',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_books_issued'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Book Issued.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/book-issued',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_book_issued'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Live Classes.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/live-classes',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_live_classes'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Leave requests.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/leave-requests',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_leave_requests'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Submit Leave request.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/submit-leave-request',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_submit_leave_request'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Study materials.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/study-materials',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_study_materials'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Study material.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/study-material',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_study_material'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Fee Invoices.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/fee-invoices',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'fee_invoices_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Fee Invoice.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/fee-invoice',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'fee_invoice_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Submit Invoice payment request.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/submit-invoice-payment-request',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_submit_invoice_payment_request'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Payment History.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/payments',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'payments_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Payment Receipt.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/payment',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'payment_parent'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Homeworks.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/homeworks',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_homeworks'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Homework.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/homework',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Homework request.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/submit-homework-request',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_submit_homework_request'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Submitted homeworks.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/submitted-homeworks',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_submitted_homeworks'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - Fetch submitted homework.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/fetch-submitted-homework',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'fetch_parent_submitted_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - lessons by filters.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/lessons-by-filters',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_lessons_by_filters'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - lesson details by ID.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/lesson-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_lesson_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Parent - lesson.
		register_rest_route(
			self::NS,
			self::PARENT_PREFIX . '/lessons',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'parent_lessons'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Profile.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/profile',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'staff_profile'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Dashboard.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/dashboard',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'staff_dashboard'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Class list.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/class_list',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'class_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Class section list.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/section_list',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'section_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Student list.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/student-list',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'student_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Class student list.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/class-student-list',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'class_student_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View Students Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-students-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_students_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Take Student Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/take-student-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'take_students_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Take Bulk Student Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/take-bulk-student-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'take_bulk_student_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Take Students Bulk Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/take-students-bulk-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'take_students_bulk_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Class Subject List
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/class-subject-list',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'class_subject_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Class Subject Student List
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/class-subject-student-list',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'class_subject_student_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Take Subject Wise Student Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/take-subject-wise-student-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'take_subject_wise_student_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Take Subject Wise Bulk Student Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/take-subject-wise-bulk-student-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'take_subject_wise_bulk_student_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View Subject Wise Students Attendance
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-subject-wise-students-attendance',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_subject_wise_students_attendance'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View events
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-events',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_events'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new event
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-event',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View event by id
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-event-by-id',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_event_by_id'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit event
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-event',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete event
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-event',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_event'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - class time table list.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/class-time-table-list',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'class_time_table_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View class time table.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-class-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Subject Teacher List
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/subject-teacher-list',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'subject_teacher_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new class time table
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-class-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new class time table
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-class-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete class time table
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-class-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete staff subjectclass time table
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-staff-subject-class-time-table',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_staff_subject_class_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View notices
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-notices',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_notices'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new notice
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-notice',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_notice'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View notice by id
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-notice-by-id',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_notice_by_id'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit notice
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-notice',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_notice'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete notice
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-notice',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_notice'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View homeworks
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-homeworks',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_homeworks'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new homework
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-homework',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View homework by id
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-homework-by-id',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_homework_by_id'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit homework
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-homework',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete homework
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-homework',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_homework'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View homework Submissions
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-homework-submitted',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_homework_submitted'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View study material
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-study-materials',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_study_materials'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new study material
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-study-material',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_study_material'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View study material by id
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-study-material-by-id',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_study_material_by_id'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit study material
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-study-material',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_study_material'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete study material
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-study-material',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_study_material'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View staff time table.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-staff-time-table',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_staff_time_table'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View student leaves.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-student-leaves',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_student_leaves'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new student leave.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-student-leave',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_student_leave'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Student leave by id.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/student-leave/(?P<leave_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'student_leave'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit student leave.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-student-leave',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_student_leave'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete student leave.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-student-leave',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_student_leave'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View subject types.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-subject-types',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_subject_types'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new subject type.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-subject-type',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_subject_type'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete subject type.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-subject-type',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_subject_type'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View subjects.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-subjects',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_subjects'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new subject.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-subject',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_subject'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Subject details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/subject-details/(?P<subject_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'subject_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit subject.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-subject',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_subject'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete subject.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-subject',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_subject'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Staff list.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/staff-list',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'staff_list'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Assign staff to subject.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/assign-staff-to-subject',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'assign_staff_to_subject'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete assigned staff from subject.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-assigned-staff-from-subject',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_assigned_staff_from_subject'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Students data.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/students-data',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'students_data'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Assigned class section student.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/assigned-class-section-student/(?P<student_id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'assigned_class_section_student'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View inquiries.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-inquiries',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_inquiries'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new inquiry.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-inquiry',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_inquiry'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Inquiry details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/inquiry-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'inquiry_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit inquiry.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-inquiry',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_inquiry'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete inquiry.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-inquiry',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_inquiry'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View exam groups.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-exam-groups',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_exam_groups'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new exam group.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-exam-group',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_exam_group'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Exam group details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/exam-group-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_group_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit exam group.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-exam-group',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_exam_group'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete exam group.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-exam-group',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_exam_group'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Exam Class Subjects.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/exam-class-subjects',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_class_subjects'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View exams.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-exams',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_exams'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new exam.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-exam',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_exam'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Exam details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/exam-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit exam.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-exam',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_exam'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete exam.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-exam',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_exam'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Students without admit card.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/students-without-admit-card',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'students_without_admit_card'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View admit cards.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-admit-cards',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_admit_cards'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View admit cards.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/generate-admit-cards',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'generate_admit_cards'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Admit card details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/admit-card-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'admit_card_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit admit card.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-admit-card',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_admit_card'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete admit card.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-admit-card',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_admit_card'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Students without result.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/students-without-result',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'students_without_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Exam paper details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/exam-paper-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_paper_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View results.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-results',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_results'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new exam result.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-exam-result',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_exam_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Exam result details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/exam-result-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'exam_result_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit exam result.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-exam-result',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_exam_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete exam result.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-exam-result',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_exam_result'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View academic reports.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-academic-reports',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_academic_reports'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new academic report.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/add-new-academic-report',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'add_new_academic_report'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Academic report details.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/academic-report-details',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'academic_report_details'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit academic report.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/edit-academic-report',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'edit_academic_report'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete academic report.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/delete-academic-report',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'delete_academic_report'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View academic report.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-academic-report',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_academic_report'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View student types.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/view-student-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'WLSM_Api', 'view_student_types' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new student type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-student-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_student_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete student type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-student-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_student_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View mediums.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/view-mediums',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'WLSM_Api', 'view_mediums' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new medium.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-medium',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_medium' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete medium.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-medium',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_medium' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View fee types.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-fee-types',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_fee_types'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new fee type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-fee-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_fee_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Fee type details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/fee-type-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'fee_type_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit fee type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-fee-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_fee_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete fee type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-fee-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_fee_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View concession types.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-concession-types',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_concession_types'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new concession type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-concession-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_concession_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Concession type details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/concession-type-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'concession_type_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit concession type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-concession-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_concession_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete concession type.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-concession-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_concession_type' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Student Fees.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/student-fees',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'student_fee_types' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View invoices.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-invoices',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_invoices'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new invoice.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-invoice',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_invoice' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Invoice details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/invoice-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'invoice_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit invoice.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-invoice',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_invoice' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete invoice.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-invoice',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_invoice' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new payment.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_payment' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View pending payment.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/view-pending-payments',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'WLSM_Api', 'view_pending_payments' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete pending payment.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-pending-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_pending_payment' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View payment history.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/view-payment-history',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'view_payment_history' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete payment.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_payment' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View collect payment.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/view-collect-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'view_collect_payment' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View invoices report.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/view-invoices-report',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'view_invoices_report' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View income categories.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-income-categories',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_income_categories'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new income category.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-income-category',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_income_category' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Income category details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/income-category-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'income_category_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit income category.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-income-category',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_income_category' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete income category.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-income-category',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_income_category' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View incomes.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-incomes',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_incomes'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new income.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-income',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_income' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Income details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/income-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'income_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit income.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-income',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_income' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete income.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-income',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_income' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View expense categories.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-expense-categories',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_expense_categories'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new expense category.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-expense-category',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_expense_category' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Expense category details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/expense-category-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'expense_category_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit expense category.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-expense-category',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_expense_category' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete expense category.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-expense-category',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_expense_category' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View expenses.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-expenses',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'view_expenses'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new expense.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-expense',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_expense' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Expense details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/expense-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'expense_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit expense.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-expense',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_expense' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete expense.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-expense',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_expense' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View chapters.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-chapters',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_chapters'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new chapter.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-chapter',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_chapter' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Chapter details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/chapter-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'chapter_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit chapter.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-chapter',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_chapter' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete chapter.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-chapter',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_chapter' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Class subject chapters.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/class-subject-chapters',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array('WLSM_Api', 'class_subject_chapters'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - View lessons.
		register_rest_route(
			self::NS,
			self::STAFF_PREFIX . '/view-lessons',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array('WLSM_Api', 'view_lessons'),
				'permission_callback' => function ($request) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Add new lesson.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/add-new-lesson',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'add_new_lesson' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Lesson details.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/lesson-details',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'lesson_details' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Edit lesson.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/edit-lesson',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'edit_lesson' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

		// Staff - Delete lesson.
		register_rest_route(
			self::NS, self::STAFF_PREFIX . '/delete-lesson',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( 'WLSM_Api', 'delete_lesson' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in();
				}
			)
		);

	}

	public static function get_students_notification($request)
	{

		$user_id = get_current_user_id();

		try {
			if (get_option($user_id . '_st_notification')) {
				$response_data = array('st_notification' => get_option($user_id . '_st_notification'));
			} else {
				throw new Exception(esc_html__('Notification Token is Empty', 'school-management'));
			}

			$success = true;
			$message = esc_html__('Notification Token Save Success', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	public static function st_notification_add($request)
	{

		$user_id = get_current_user_id();

		$params = $request->get_params();
		$st_notification = isset($params['st_notification']) ? $params['st_notification'] : '';

		try {
			if (empty($st_notification)) {
				throw new Exception(esc_html__('Please Send Notification Token', 'school-management'));
			}

			update_option($user_id . '_st_notification', $st_notification);

			$success = true;
			$message = esc_html__('Notification Token Save Success', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Global - Settings.
	public static function global_settings()
	{
		$active_currency = WLSM_Config::currency();
		$active_date_format = WLSM_Config::date_format();

		$message = esc_html__('Global settings retrieved successfully.', 'school-management');

		$response = array(
			'success' => true,
			'message' => $message,
		);

		$response['data'] = array(
			'date_format' => $active_date_format,
			'currency_code' => $active_currency
		);

		return new WP_REST_Response($response, 200);
	}

	// Account Settings.
	public static function account_settings($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M::get_student($user_id);

			if ($student) {
				$data['user_type'] = self::STUDENT_PREFIX;
			} else {
				$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);
				if (count($unique_student_ids)) {
					$data['user_type'] = self::PARENT_PREFIX;
				} else {
					return self::no_account();
				}
			}

			$params = $request->get_params();

			$name = isset($params['name']) ? sanitize_text_field($params['name']) : $student->student_name;
			$email = isset($params['email']) ? sanitize_text_field($params['email']) : '';
			$phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
			$address = isset($params['address']) ? sanitize_text_field($params['address']) : '';
			$city = isset($params['city']) ? sanitize_text_field($params['city']) : '';
			$state = isset($params['state']) ? sanitize_text_field($params['state']) : '';
			$country = isset($params['country']) ? sanitize_text_field($params['country']) : '';
			$father_name = isset($params['father_name']) ? sanitize_text_field($params['father_name']) : '';
			$father_phone = isset($params['father_phone']) ? sanitize_text_field($params['father_phone']) : '';
			$father_occupation = isset($params['father_occupation']) ? sanitize_text_field($params['father_occupation']) : '';
			$mother_name = isset($params['mother_name']) ? sanitize_text_field($params['mother_name']) : '';
			$mother_phone = isset($params['mother_phone']) ? sanitize_text_field($params['mother_phone']) : '';
			$father_occupation = isset($params['father_occupation']) ? sanitize_text_field($params['father_occupation']) : '';
			$mother_occupation = isset($params['mother_occupation']) ? sanitize_text_field($params['mother_occupation']) : '';
			$user_email = isset($params['user_email']) ? sanitize_email($params['user_email']) : '';
			$password = isset($params['password']) ? $params['password'] : '';
			$password_confirm = isset($params['password_confirm']) ? $params['password_confirm'] : '';

			if (empty($user_email)) {
				throw new Exception(esc_html__('Please provide email address.', 'school-management'));
			}

			if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception(esc_html__('Please provide a valid email.', 'school-management'));
			}

			if (empty($password)) {
				throw new Exception(esc_html__('Please provide password.', 'school-management'));
			}

			if (empty($password_confirm)) {
				throw new Exception(esc_html__('Please confirm password.', 'school-management'));
			}

			if ($password !== $password_confirm) {
				throw new Exception(esc_html__('Passwords do not match.', 'school-management'));
			}

			$student_data = array(
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'address' => $address,
				'city' => $city,
				'state' => $state,
				'country' => $country,
				'father_name' => $father_name,
				'father_phone' => $father_phone,
				'father_occupation' => $father_occupation,
				'mother_name' => $mother_name,
				'mother_phone' => $mother_phone,
				'mother_occupation' => $mother_occupation,
			);

			$success = $wpdb->update(WLSM_STUDENT_RECORDS, $student_data, array('user_id' => $user_id));
			$message = esc_html__('Account settings updated.', 'school-management');

			$user = wp_get_current_user();

			$data = array(
				'ID' => $user->ID,
				'user_email' => $user_email,
				'user_pass' => $password,
			);

			$user_id = wp_update_user($data);

			if (is_wp_error($user_id)) {
				throw new Exception($user_id->get_error_message());
			}

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Profile.
	public static function student_profile($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M::get_student_profile($user_id);
			$student_common_details = WLSM_M::get_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			if (wp_get_attachment_url($student->photo_id)) {
				$photo_url = wp_get_attachment_url($student->photo_id);
			} else {
				$photo_url = " ";
			}

			$common_details = self::student_common_details($student_common_details);
			$other_details = array(
				'enrollment_number' => esc_html($student_common_details->enrollment_number),
				'name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)),
				'photo' => esc_url($photo_url),
				'session' => esc_html(WLSM_M_Session::get_label_text($student->session_label)),
				'class' => esc_html(WLSM_M_Class::get_label_text($student->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($student->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($student_common_details->roll_number)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student_common_details->father_name)),

			);
			$response_data['student'] = array_merge($common_details, $other_details);

			$success = true;
			$message = esc_html__('Student profile retrieved successfully.', 'school-management');

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Dashboard.
	public static function student_dashboard($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M::get_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;
			$class_school_id = $student->class_school_id;

			// Student photo
			$student_logo = WLSM_M::get_student_profile($user_id);
			$student_photo = wp_get_attachment_url($student_logo->photo_id) ?: '';

			// Student details
			$common_details = self::student_common_details($student);
			$other_details = array(
				'name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)),
				'enrollment_number' => esc_html($student->enrollment_number),
				'session' => esc_html(WLSM_M_Session::get_label_text($student->session_label)),
				'class' => esc_html(WLSM_M_Class::get_label_text($student->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($student->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->father_name)),
				'photo' => esc_url($student_photo),
			);
			$response_data['student'] = array_merge($common_details, $other_details);

			// Attendance stats
			$response_data['attendance'] = WLSM_M_Staff_General::get_student_attendance_stats($student->ID);

			$params = $request->get_params();

			// Notices query
			$notices_query = WLSM_M::notices_query();

			// Total notices
			$notices_total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(1) FROM ({$notices_query}) AS combined_table",
					$class_school_id,
					$class_school_id,
					$school_id
				)
			);

			// Pagination
			$notices_page = isset($params['notices_page']) ? absint($params['notices_page']) : 1;
			$notices_per_page = isset($params['notices_per_page']) ? absint($params['notices_per_page']) : WLSM_M::notices_per_page();
			$notices_offset = ($notices_page - 1) * $notices_per_page;

			// Paginated data
			$notices = $wpdb->get_results(
				$wpdb->prepare(
					$notices_query . ' ORDER BY n.ID DESC LIMIT %d, %d',
					$notices_offset,
					$notices_per_page
				)
			);

			// Filter notices by class only
			$filtered_notices = array();
			foreach ($notices as $notice) {
				$notice_data = @unserialize($notice->notice_data);

				// Default to 'all' if no valid notice_data
				if ($notice_data === false || !isset($notice_data['classes'])) {
					$notice_data = array('classes' => array('all'));
				}

				$notice_data['classes'] = (array) $notice_data['classes'];

				// Class match check
				if (in_array($student->class_id, $notice_data['classes'], true) || in_array('all', $notice_data['classes'], true)) {
					$filtered_notices[] = $notice;
				}
			}

			// Prepare notices data
			$notices_data = array();
			$notices_pagination = array(
				'current_page' => $notices_page,
				'per_page' => $notices_per_page,
			);

			if (count($filtered_notices)) {
				$today = new DateTime();
				$today->setTime(0, 0, 0);

				foreach ($filtered_notices as $notice) {
					$link = '#';

					if ('url' === $notice->link_to && !empty($notice->url)) {
						$link = $notice->url;
					} elseif ('attachment' === $notice->link_to && !empty($notice->attachment)) {
						$link = wp_get_attachment_url($notice->attachment);
					}

					$notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $notice->created_at);
					$notice_date->setTime(0, 0, 0);

					$is_new = ($today->diff($notice_date)->days < 7);

					$notices_data[] = array(
						'id' => $notice->ID,
						'title' => esc_html(stripslashes($notice->title)),
						'description' => esc_html(stripslashes($notice->description)),
						'link' => esc_url($link),
						'date' => esc_html(WLSM_Config::get_date_text($notice->created_at)),
						'is_new' => $is_new,
						'notice_filter_data' => $notice->notice_data,
					);
				}

				$notices_pagination['total_pages'] = ceil($notices_total / $notices_per_page);
				$notices_pagination['total_records'] = $notices_total;
			}

			// Response success
			$success = true;
			$message = esc_html__('Student dashboard retrieved successfully.', 'school-management');

			$response_data['noticeboard'] = array(
				'new_notice_icon' => esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'),
				'data' => $notices_data
				// Pagination intentionally removed like in original code
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Noticeboard.
	public static function student_noticeboard($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;
			$params = $request->get_params();

			// Query
			$notices_query = WLSM_M::notices_query();

			// Total count
			$notices_total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(1) FROM ({$notices_query}) AS combined_table",
					$class_school_id,
					$class_school_id,
					$school_id
				)
			);

			// Pagination
			$notices_page = isset($params['notices_page']) ? (int) $params['notices_page'] : 1;
			$notices_per_page = isset($params['notices_per_page']) ? (int) $params['notices_per_page'] : WLSM_M::notices_per_page();
			$notices_offset = ($notices_page - 1) * $notices_per_page;

			// Paginated results
			$notices = $wpdb->get_results(
				$wpdb->prepare(
					$notices_query . ' ORDER BY n.ID DESC LIMIT %d, %d',
					$notices_offset,
					$notices_per_page
				)
			);

			$filtered_notices = array();

			// Only filter by class
			foreach ($notices as $notice) {
				$notice_data = @unserialize($notice->notice_data);
				if ($notice_data === false) {
					$notice_data = array('classes' => array('all'));
				}

				// Ensure classes array exists
				$notice_data['classes'] = isset($notice_data['classes']) ? (array) $notice_data['classes'] : array('all');

				// Match by class_id or "all"
				if (in_array($student->class_id, $notice_data['classes'], true) || in_array('all', $notice_data['classes'], true)) {
					$filtered_notices[] = $notice;
				}
			}

			// Format response
			$notices_data = array();
			$notices_pagination = array(
				'current_page' => $notices_page,
				'per_page' => $notices_per_page,
			);

			if (count($filtered_notices)) {
				$today = new DateTime();
				$today->setTime(0, 0, 0);

				foreach ($filtered_notices as $notice) {
					$link = '#';

					if ('url' === $notice->link_to && !empty($notice->url)) {
						$link = $notice->url;
					} elseif ('attachment' === $notice->link_to && !empty($notice->attachment)) {
						$link = wp_get_attachment_url($notice->attachment);
					}

					$notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $notice->created_at);
					$notice_date->setTime(0, 0, 0);

					$is_new = ($today->diff($notice_date)->days < 7);

					$notices_data[] = array(
						'id' => $notice->ID,
						'title' => esc_html(stripslashes($notice->title)),
						'description' => esc_html(stripslashes($notice->description)),
						'link' => esc_url($link),
						'date' => esc_html(WLSM_Config::get_date_text($notice->created_at)),
						'is_new' => $is_new,
						'notice_filter_data' => $notice->notice_data,
					);
				}

				$notices_pagination['total_pages'] = ceil($notices_total / $notices_per_page);
				$notices_pagination['total_records'] = $notices_total;
			}

			$success = true;
			$message = esc_html__('Noticeboard retrieved successfully.', 'school-management');

			$response_data['noticeboard'] = array(
				'new_notice_icon' => esc_url(trailingslashit(WLSM_PLUGIN_URL) . 'assets/images/new-notice-icon.gif'),
				'data' => $notices_data,
				'pagination' => $notices_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Fee Invoices.
	public static function fee_invoices($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$invoices = WLSM_M_Staff_Accountant::get_student_pending_invoices($student->ID);

			$invoices_data = array();

			if (count($invoices)) {
				foreach ($invoices as $row) {
					$due = $row->payable - $row->paid;
					$invoices_data[] = array(
						'id' => $row->ID,
						'invoice_number' => esc_html($row->invoice_number),
						'invoice_title' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($row->invoice_title)),
						'payable' => esc_html(WLSM_Config::sanitize_money($row->payable)),
						'payable_display' => esc_html(WLSM_Config::get_money_text($row->payable, $school_id)),
						'paid' => esc_html(WLSM_Config::sanitize_money($row->paid)),
						'paid_display' => esc_html(WLSM_Config::get_money_text($row->paid, $school_id)),
						'due' => esc_html(WLSM_Config::sanitize_money($due)),
						'due_display' => esc_html(WLSM_Config::get_money_text($due, $school_id)),
						'status' => esc_html($row->status),
						'status_text' => esc_html(WLSM_M_Invoice::get_status_text($row->status, false)),
						'show_pay_now' => WLSM_M_Invoice::get_paid_key() !== $row->status,
						'date_issued' => esc_html(WLSM_Config::get_date_text($row->date_issued)),
						'due_date' => esc_html(WLSM_Config::get_date_text($row->due_date)),
					);
				}
			}

			$success = true;
			$message = esc_html__('Fee invoices retrieved successfully.', 'school-management');

			$response_data['invoices'] = array(
				'data' => $invoices_data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Fee Invoice.
	public static function fee_invoice($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$invoice_id = isset($params['invoice_id']) ? absint($params['invoice_id']) : 0;

			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

			if (!$invoice) {
				throw new Exception(esc_html__('Invoice not found.', 'school-management'));
			}

			$due = $invoice->payable - $invoice->paid;

			$invoice_partial_payment = $invoice->partial_payment;

			$currency = WLSM_Config::currency($school_id);

			// Razorpay settings.
			$settings_razorpay = WLSM_M_Setting::get_settings_razorpay($school_id);
			$school_razorpay_enable = $settings_razorpay['enable'];

			// Stripe settings.
			$settings_stripe = WLSM_M_Setting::get_settings_stripe($school_id);
			$school_stripe_enable = $settings_stripe['enable'];

			// PayPal settings.
			$settings_paypal = WLSM_M_Setting::get_settings_paypal($school_id);
			$school_paypal_enable = $settings_paypal['enable'];

			// Pesapal settings.
			$settings_pesapal = WLSM_M_Setting::get_settings_pesapal($school_id);
			$school_pesapal_enable = $settings_pesapal['enable'];

			// Paystack settings.
			$settings_paystack = WLSM_M_Setting::get_settings_paystack($school_id);
			$school_paystack_enable = $settings_paystack['enable'];

			// Paytm settings.
			$settings_paytm = WLSM_M_Setting::get_settings_paytm($school_id);
			$school_paytm_enable = $settings_paytm['enable'];

			// bank settings.
			$settings_bank = WLSM_M_Setting::get_settings_bank_transfer($school_id);
			$school_bank_enable = $settings_bank['enable'];

			$settings_upi_transfer = WLSM_M_Setting::get_settings_upi_transfer($school_id);
			$school_upi_transfer_enable = $settings_upi_transfer['enable'];

			$success = true;
			$message = esc_html__('Fee invoice retrieved successfully.', 'school-management');

			$response_data['invoice'] = array(
				'id' => $invoice->ID,
				'invoice_number' => esc_html($invoice->invoice_number),
				'invoice_title' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
				'date_issued' => esc_html(WLSM_Config::get_date_text($invoice->date_issued)),
				'due_date' => esc_html(WLSM_Config::get_date_text($invoice->due_date)),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($invoice->student_name)),
				'enrollment_number' => esc_html($invoice->enrollment_number),
				'class' => esc_html(WLSM_M_Class::get_label_text($invoice->class_label)),
				'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($invoice->section_label)),
				'fees_due' => esc_html(WLSM_Config::sanitize_money($due)),
				'fees_due_display' => esc_html(WLSM_Config::get_money_text($due, $school_id)),
				'partial_payment_allowed' => (bool) $invoice_partial_payment,
				'currency' => $currency
			);

			$payment_methods = array();

			if (true) {
				$payment_methods['razorpay'] = esc_html(WLSM_M_Invoice::get_payment_method_text('razorpay'));

				$school_razorpay_key = $settings_razorpay['razorpay_key'];
				$school_razorpay_secret = $settings_razorpay['razorpay_secret'];

				$response_data['razorpay_api_data'] = array(
					'school_razorpay_enable' => $school_razorpay_enable,
					'school_razorpay_key' => $school_razorpay_key,
					'school_razorpay_secret' => $school_razorpay_secret
				);
			}

			if (true) {
				$payment_methods['stripe'] = esc_html(WLSM_M_Invoice::get_payment_method_text('stripe'));

				$school_stripe_publishable_key = $settings_stripe['publishable_key'];
				$school_stripe_secret_key = $settings_stripe['secret_key'];

				$response_data['stripe_api_data'] = array(
					'enable' => $school_stripe_enable,
					'publishable_key' => $school_stripe_publishable_key,
					'secret_key' => $school_stripe_secret_key
				);
			}

			if (true) {
				$payment_methods['paypal'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paypal'));

				$school_paypal_business_email = $settings_paypal['business_email'];
				$school_paypal_mode = $settings_paypal['mode'];
				$school_paypal_notify_url = $settings_paypal['notify_url'];

				$response_data['paypal_api_data'] = array(
					'school_paypal_enable' => $school_paypal_enable,
					'school_paypal_business_email' => $school_paypal_business_email,
					'school_paypal_mode' => $school_paypal_mode,
					'school_paypal_notify_url' => $school_paypal_notify_url
				);
			}

			if (true) {
				$payment_methods['pesapal'] = esc_html(WLSM_M_Invoice::get_payment_method_text('pesapal'));

				$school_pesapal_consumer_key = $settings_pesapal['consumer_key'];
				$school_pesapal_consumer_secret = $settings_pesapal['consumer_secret'];
				$school_pesapal_mode = $settings_pesapal['mode'];
				$school_pesapal_notify_url = $settings_pesapal['notify_url'];

				$response_data['pesapal_api_data'] = array(
					'school_pesapal_enable' => $school_pesapal_enable,
					'school_pesapal_consumer_key' => $school_pesapal_consumer_key,
					'school_pesapal_consumer_secret' => $school_pesapal_consumer_secret,
					'school_pesapal_notify_url' => $school_pesapal_notify_url,
					'school_pesapal_mode' => $school_pesapal_mode
				);
			}

			if (true) {
				$payment_methods['paystack'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paystack'));

				$school_paystack_public_key = $settings_paystack['paystack_public_key'];
				$school_paystack_secret_key = $settings_paystack['paystack_secret_key'];

				$response_data['paystack_api_data'] = array(
					'school_paystack_enable' => $school_paystack_enable,
					'school_paystack_public_key' => $school_paystack_public_key,
					'school_paystack_secret_key' => $school_paystack_secret_key
				);
			}

			if (true) {
				$payment_methods['paytm'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paytm'));

				// Paytm settings.
				$school_paytm_merchant_id = $settings_paytm['merchant_id'];
				$school_paytm_merchant_key = $settings_paytm['merchant_key'];
				$school_paytm_industry_type_id = $settings_paytm['industry_type_id'];
				$school_paytm_website = $settings_paytm['website'];
				$school_paytm_mode = $settings_paytm['mode'];


				$response_data['paytm_api_data'] = array(
					'school_paytm_enable' => $school_paytm_enable,
					'school_paytm_merchant_id' => $school_paytm_merchant_id,
					'school_paytm_merchant_key' => $school_paytm_merchant_key,
					'school_paytm_industry_type_id' => $school_paytm_industry_type_id,
					'school_paytm_website' => $school_paytm_website,
					'school_paytm_mode' => $school_paytm_mode
				);
			}

			if (true) {
				$payment_methods['bank-transfer'] = esc_html(WLSM_M_Invoice::get_payment_method_text('bank-transfer'));

				$settings_bank = WLSM_M_Setting::get_settings_bank_transfer($school_id);
				$school_bank_enable = $settings_bank['enable'];
				$branch = $settings_bank['branch'];
				$account = $settings_bank['account'];
				$name = $settings_bank['name'];
				$message = $settings_bank['message'];


				$response_data['bank-tranfer-data'] = array(
					'enable' => $school_bank_enable,
					'branch' => $branch,
					'account' => $account,
					'name' => $name,
					'message' => $message,
				);
			}

			if (true) {
				$payment_methods['upi-transfer'] = esc_html(WLSM_M_Invoice::get_payment_method_text('upi-transfer'));

				$settings_upi_transfer = WLSM_M_Setting::get_settings_upi_transfer($school_id);
				$school_upi_transfer_enable = $settings_upi_transfer['enable'];
				$school_upi_transfer_qr_url = !empty($settings_upi_transfer['qr']) ? wp_get_attachment_url($settings_upi_transfer['qr']) : '';
				$school_upi_transfer_id = $settings_upi_transfer['id'];
				$school_upi_transfer_name = $settings_upi_transfer['name'];
				$school_upi_transfer_message = $settings_upi_transfer['message'];

				$response_data['upi-transfer-data'] = array(
					'school_upi_transfer_enable' => $school_upi_transfer_enable,
					'school_upi_transfer_qr' => $school_upi_transfer_qr_url,
					'school_upi_transfer_id' => $school_upi_transfer_id,
					'school_upi_transfer_name' => $school_upi_transfer_name,
					'school_upi_transfer_message' => $school_upi_transfer_message,
				);
			}

			$response_data['payment_methods'] = $payment_methods;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Payment History.
	public static function student_payments($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$payments_query = WLSM_M::payments_query();

			// Total.
			$payments_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$payments_query}) AS combined_table", $student->ID));

			// Current page and per page.
			$payments_page = isset($params['payments_page']) ? absint($params['payments_page']) : 1;
			$payments_per_page = isset($params['payments_per_page']) ? absint($params['payments_per_page']) : WLSM_M::payments_per_page();

			// Page offset.
			$payments_page_offset = ($payments_page * $payments_per_page) - $payments_per_page;

			// Paginated data.
			$payments = $wpdb->get_results($wpdb->prepare($payments_query . ' ORDER BY p.ID DESC LIMIT %d, %d', $student->ID, $payments_page_offset, $payments_per_page));

			// Paginated response.
			$payments_data = array();
			$payments_pagination = array(
				'current_page' => $payments_page,
				'per_page' => $payments_per_page,
			);

			// Format response.
			if (count($payments)) {
				foreach ($payments as $key => $row) {
					if ($row->invoice_id) {
						$invoice_title = $row->invoice_title;
					} else {
						$invoice_title = $row->invoice_label;
					}

					$payments_data[] = array(
						'id' => $row->ID,
						'receipt_number' => esc_html(WLSM_M_Invoice::get_receipt_number_text($row->receipt_number)),
						'amount' => esc_html(WLSM_Config::sanitize_money($row->amount)),
						'amount_display' => esc_html(WLSM_Config::get_money_text($row->amount, $school_id)),
						'payment_method' => esc_html(WLSM_M_Invoice::get_payment_method_text($row->payment_method)),
						'transaction_id' => esc_html(WLSM_M_Invoice::get_transaction_id_text($row->transaction_id)),
						'date' => esc_html(WLSM_Config::get_date_text($row->created_at)),
						'invoice' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice_title))
					);
				}

				$payments_pagination['total_pages'] = ceil($payments_total / $payments_per_page);
				$payments_pagination['total_records'] = $payments_total;
			}

			$success = true;
			$message = esc_html__('Payments retrieved successfully.', 'school-management');

			$response_data['payments'] = array(
				'data' => $payments_data,
				'pagination' => $payments_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Payment Receipt.
	public static function student_payment($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$payment_id = isset($params['payment_id']) ? absint($params['payment_id']) : 0;

			$payment = WLSM_M_Staff_Accountant::get_student_payment($student_id, $payment_id);

			if (!$payment) {
				throw new Exception(esc_html__('Payment not found.', 'school-management'));
			}

			$success = true;
			$message = esc_html__('Payment details retrieved successfully.', 'school-management');

			if ($payment->invoice_id) {
				$invoice_title = esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($payment->invoice_title));
			} else {
				$invoice_title = esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($payment->invoice_label));
			}

			$response_data['payment'] = array(
				'id' => $payment->ID,
				'receipt_number' => esc_html(WLSM_M_Invoice::get_receipt_number_text($payment->receipt_number)),
				'amount' => esc_html(WLSM_Config::sanitize_money($payment->amount)),
				'amount_display' => esc_html(WLSM_Config::get_money_text($payment->amount, $school_id)),
				'payment_method' => esc_html(WLSM_M_Invoice::get_payment_method_text($payment->payment_method)),
				'transaction_id' => esc_html(WLSM_M_Invoice::get_transaction_id_text($payment->transaction_id)),
				'date' => esc_html(WLSM_Config::get_date_text($payment->created_at)),
				'invoice' => esc_html($invoice_title),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->student_name)),
				'enrollment_number' => esc_html($payment->enrollment_number),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($payment->phone)),
				'email' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->email)),
				'class' => esc_html(WLSM_M_Class::get_label_text($payment->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($payment->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($payment->roll_number)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->father_name)),
				'father_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($payment->father_phone)),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Events.
	public static function student_events($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$events_query = WLSM_M::events_query();

			// Total.
			$events_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$events_query}) AS combined_table", $student->ID, $school_id));

			// Current page and per page.
			$events_page = isset($params['events_page']) ? absint($params['events_page']) : 1;
			$events_per_page = isset($params['events_per_page']) ? absint($params['events_per_page']) : WLSM_M::events_per_page();

			// Page offset.
			$events_page_offset = ($events_page * $events_per_page) - $events_per_page;

			// Paginated data.
			$events = $wpdb->get_results($wpdb->prepare($events_query . ' ORDER BY ev.ID DESC LIMIT %d, %d', $student->ID, $school_id, $events_page_offset, $events_per_page));

			// Paginated response.
			$events_data = array();
			$events_pagination = array(
				'current_page' => $events_page,
				'per_page' => $events_per_page,
			);

			// Format response.
			if (count($events)) {
				foreach ($events as $key => $event) {
					$events_data[] = array(
						'id' => $event->ID,
						'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
						'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
						'image' => esc_url(wp_get_attachment_url($event->image_id)),
						'description' => wp_kses_post(stripslashes($event->description)),
						'has_joined' => $event->student_joined ? true : false
					);
				}

				$events_pagination['total_pages'] = ceil($events_total / $events_per_page);
				$events_pagination['total_records'] = $events_total;
			}

			$success = true;
			$message = esc_html__('Events retrieved successfully.', 'school-management');

			$response_data['events'] = array(
				'events_data' => $events_data,
				'pagination' => $events_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Event.
	public static function student_event($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

			$event = WLSM_M_Staff_Class::fetch_active_event($school_id, $event_id, $student_id);

			if (!$event) {
				throw new Exception(esc_html__('Event not found.', 'school-management'));
			}

			$success = true;
			$message = esc_html__('Event details retrieved successfully.', 'school-management');

			$response_data['event'] = array(
				'id' => $event->ID,
				'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
				'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
				'image' => esc_url(wp_get_attachment_url($event->image_id)),
				'description' => wp_kses_post(stripslashes($event->description)),
				'has_joined' => $event->student_joined ? true : false
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Join event.
	public static function student_join_event($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$params = $request->get_params();

			$event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

			$event = WLSM_M_Staff_Class::fetch_active_event($school_id, $event_id, $student_id);

			if (!$event) {
				throw new Exception(esc_html__('Event not found.', 'school-management'));
			}

			if ($event->student_joined) {
				throw new Exception(esc_html__('You have already joined.', 'school-management'));
			}

			// Event participant data.
			$data = array(
				'student_record_id' => $student_id,
				'event_id' => $event_id,
			);

			$data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_EVENT_RESPONSES, $data);

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('You have joined the event successfully.', 'school-management');

			$response_data['event'] = array(
				'id' => $event->ID,
				'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
				'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Unjoin event.
	public static function student_unjoin_event($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$params = $request->get_params();

			$event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

			$event = WLSM_M_Staff_Class::fetch_active_event($school_id, $event_id, $student_id);

			if (!$event) {
				throw new Exception(esc_html__('Event not found.', 'school-management'));
			}

			if (!$event->student_joined) {
				throw new Exception(esc_html__('You have not joined this event.', 'school-management'));
			}

			$event_response_id = $event->event_response_id;

			$success = $wpdb->delete(WLSM_EVENT_RESPONSES, array('ID' => $event_response_id));

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('You have left from this event.', 'school-management');

			$response_data['event'] = array(
				'id' => $event->ID,
				'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
				'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Class time table.
	public static function student_class_time_table($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$section = WLSM_M_Staff_Class::get_school_section($school_id, $student->section_id);

			if (!$section) {
				die;
			}

			$class_label = $section->class_label;
			$section_label = $section->label;

			$data = array();

			foreach (WLSM_Helper::days_list() as $key => $day) {
				$routines = WLSM_M_Staff_Class::get_section_routines_by_day($school_id, $section_id, $key);

				$day_routine = array(
					'day' => $day
				);

				$routines_data = array();
				foreach ($routines as $routine) {
					$routine_data = array();
					$routine_data['subject'] = sprintf(
						wp_kses(
							/* translators: 1: subject label, 2: subject code */
							_x('%1$s (%2$s)', 'Subject', 'school-management'),
							array('span' => array('class' => array()))
						),
						esc_html(WLSM_M_Staff_Class::get_subject_label_text($routine->subject_label)),
						esc_html($routine->subject_code)
					);

					$routine_data['start_time'] = esc_html(WLSM_Config::get_time_text($routine->start_time));
					$routine_data['end_time'] = esc_html(WLSM_Config::get_time_text($routine->end_time));

					$routine_data['room'] = esc_html($routine->room_number);

					if ($routine->teacher_name) {
						$routine_data['teacher'] = esc_html(WLSM_M_Staff_Class::get_name_text($routine->teacher_name));
					}

					array_push($routines_data, $routine_data);
				}

				$day_routine['routines'] = $routines_data;

				array_push($data, $day_routine);
			}

			$success = true;
			$message = esc_html__('Class time table retrieved successfully.', 'school-management');

			$response_data['class_time_table'] = array(
				'class' => esc_html(WLSM_M_Class::get_label_text($class_label)),
				'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($section_label)),
				'data' => $data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Books issued.
	public static function student_books_issued($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$books_issued_query = WLSM_M::books_issued_query();

			// Total.
			$books_issued_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$books_issued_query}) AS combined_table", $school_id, $session_id, $student->ID));

			// Current page and per page.
			$books_issued_page = isset($params['books_issued_page']) ? absint($params['books_issued_page']) : 1;
			$books_issued_per_page = isset($params['books_issued_per_page']) ? absint($params['books_issued_per_page']) : WLSM_M::books_issued_per_page();

			// Page offset.
			$books_issued_page_offset = ($books_issued_page * $books_issued_per_page) - $books_issued_per_page;

			// Paginated data.
			$books_issued = $wpdb->get_results($wpdb->prepare($books_issued_query . ' ORDER BY bki.date_issued DESC LIMIT %d, %d', $school_id, $session_id, $student->ID, $books_issued_page_offset, $books_issued_per_page));

			// Paginated response.
			$books_issued_data = array();
			$books_issued_pagination = array(
				'current_page' => $books_issued_page,
				'per_page' => $books_issued_per_page,
			);

			// Format response.
			if (count($books_issued)) {
				foreach ($books_issued as $key => $row) {
					$books_issued_data[] = array(
						'id' => $row->ID,
						'book_title' => esc_html(WLSM_M_Staff_Library::get_book_title($row->title)),
						'issued_quantity' => esc_html(WLSM_M_Staff_Library::get_book_quantity($row->issued_quantity)),
						'date_issued' => esc_html(WLSM_Config::get_date_text($row->date_issued)),
						'return_date' => esc_html(WLSM_Config::get_date_text($row->return_date)),
						'return_status' => strip_tags(WLSM_M_Staff_Library::get_book_issued_status_text($row->returned_at)),
						'returned_at' => esc_html(WLSM_Config::get_date_text($row->returned_at)),
						'author' => esc_html(WLSM_M_Staff_Library::get_book_author($row->author)),
						'subject' => esc_html(WLSM_M_Staff_Library::get_book_subject($row->subject)),
						'rack_number' => esc_html(WLSM_M_Staff_Library::get_book_rack_number($row->rack_number)),
						'book_number' => esc_html(WLSM_M_Staff_Library::get_book_number($row->book_number)),
						'isbn_number' => esc_html(WLSM_M_Staff_Library::get_book_isbn_number($row->isbn_number)),
					);
				}

				$books_issued_pagination['total_pages'] = ceil($books_issued_total / $books_issued_per_page);
				$books_issued_pagination['total_records'] = $books_issued_total;
			}

			$success = true;
			$message = esc_html__('Books issued retrieved successfully.', 'school-management');

			$response_data['books_issued'] = array(
				'books_issued_data' => $books_issued_data,
				'pagination' => $books_issued_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Book Issued.
	public static function student_book_issued($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$params = $request->get_params();

			$book_id = isset($params['book_id']) ? absint($params['book_id']) : 0;

			$book = WLSM_M_Staff_Library::get_book($school_id, $book_id);

			if (!$book) {
				throw new Exception(esc_html__('Book not found.', 'school-management'));
			}

			$book_issued = WLSM_M::get_book_issued($school_id, $session_id, $book_id);

			$book_issued_data = array();

			// Format response.
			if ($book_issued) {
				$book_issued = array(
					'id' => $book_issued->ID,
					'book_title' => isset($book_issued->title) ? esc_html(WLSM_M_Staff_Library::get_book_title($book_issued->title)) : '',
					'issued_quantity' => isset($book_issued->issued_quantity) ? esc_html(WLSM_M_Staff_Library::get_book_quantity($book_issued->issued_quantity)) : '',
					'date_issued' => isset($book_issued->date_issued) ? esc_html(WLSM_Config::get_date_text($book_issued->date_issued)) : '',
					'return_date' => isset($book_issued->return_date) ? esc_html(WLSM_Config::get_date_text($book_issued->return_date)) : '',
					'return_status' => isset($book_issued->returned_at) ? strip_tags(WLSM_M_Staff_Library::get_book_issued_status_text($book_issued->returned_at)) : '',
					'returned_at' => isset($book_issued->returned_at) ? esc_html(WLSM_Config::get_date_text($book_issued->returned_at)) : '',
					'author' => isset($book_issued->author) ? esc_html(WLSM_M_Staff_Library::get_book_author($book_issued->author)) : '',
					'subject' => isset($book_issued->subject) ? esc_html(WLSM_M_Staff_Library::get_book_subject($book_issued->subject)) : '',
					'rack_number' => isset($book_issued->rack_number) ? esc_html(WLSM_M_Staff_Library::get_book_rack_number($book_issued->rack_number)) : '',
					'book_number' => isset($book_issued->book_number) ? esc_html(WLSM_M_Staff_Library::get_book_number($book_issued->book_number)) : '',
					'isbn_number' => isset($book_issued->isbn_number) ? esc_html(WLSM_M_Staff_Library::get_book_isbn_number($book_issued->isbn_number)) : '',
				);
			}

			$success = true;
			$message = esc_html__('Book issued retrieved successfully.', 'school-management');

			$response_data['book_issued'] = $book_issued;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Live classes.
	public static function student_live_classes($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$meetings_query = WLSM_M::meetings_query();

			// Total.
			$meetings_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$meetings_query}) AS combined_table", $school_id, $class_school_id));

			// Current page and per page.
			$meetings_page = isset($params['live_classes_page']) ? absint($params['live_classes_page']) : 1;
			$meetings_per_page = isset($params['live_classes_per_page']) ? absint($params['live_classes_per_page']) : WLSM_M::meetings_per_page();

			// Page offset.
			$meetings_page_offset = ($meetings_page * $meetings_per_page) - $meetings_per_page;

			// Paginated data.
			$meetings = $wpdb->get_results($wpdb->prepare($meetings_query . ' ORDER BY mt.start_at DESC, mt.ID DESC LIMIT %d, %d', $school_id, $class_school_id, $meetings_page_offset, $meetings_per_page));

			// Paginated response.
			$meetings_data = array();
			$meetings_pagination = array(
				'current_page' => $meetings_page,
				'per_page' => $meetings_per_page,
			);

			// Format response.
			if (count($meetings)) {
				foreach ($meetings as $key => $row) {

					$sdk_key = get_the_author_meta('sdk_key', $row->user_id);
					$sdk_secret = get_the_author_meta('sdk_secret', $row->user_id);
					$api_key = get_the_author_meta('api_key', $row->user_id);
					$api_secret = get_the_author_meta('api_secret', $row->user_id);

					$meetings_data[] = array(
						'id' => $row->ID,
						'meeting_id' => $row->meeting_id,
						'topic' => esc_html($row->topic),
						'duration' => esc_html($row->duration),
						'start_date_time' => esc_html(WLSM_Config::get_at_text($row->start_at)),
						'type' => esc_html(WLSM_Helper::get_meeting_type($row->type)),
						'join_url' => esc_url($row->join_url),
						'password' => esc_html($row->password),
						'subject' => esc_html($row->subject_name),
						'teacher' => esc_html($row->name),
						'sdk_key' => esc_html($sdk_key),
						'sdk_secret' => esc_html($sdk_secret),
						'api_key' => esc_html($api_key),
						'api_secret' => esc_html($api_secret),
					);
				}

				$meetings_pagination['total_pages'] = ceil($meetings_total / $meetings_per_page);
				$meetings_pagination['total_records'] = $meetings_total;
			}

			$success = true;
			$message = esc_html__('Live classes retrieved successfully.', 'school-management');

			$response_data['live_classes'] = array(
				'data' => $meetings_data,
				'pagination' => $meetings_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Attendance.
	public static function student_attendance($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$attendance = WLSM_M_Staff_General::get_student_attendance_report($student_id);

			$data = array();

			$total_attendance = 0;
			$total_present = 0;
			$total_absent = 0;

			$attendance_monthly = array();
			foreach ($attendance as $monthly) {
				$month = new DateTime();
				$month->setDate($monthly->year, $monthly->month, 1);
				$total_attendance += $monthly->total_attendance;
				$total_present += $monthly->total_present;
				$total_absent += $monthly->total_absent;

				$attendance_data = array(
					'month' => esc_html($month->format('F Y')),
					'total_attendance' => esc_html($monthly->total_attendance),
					'total_present' => esc_html($monthly->total_present),
					'total_absent' => esc_html($monthly->total_absent),
					'total_holidays' => esc_html($monthly->total_holiday),
					'total_late' => esc_html($monthly->total_late),
				);

				array_push($attendance_monthly, $attendance_data);
			}

			$data['overall'] = array(
				'total_attendance' => $total_attendance,
				'total_present' => $total_present,
				'total_absent' => $total_absent,
				'percentage_value' => WLSM_Config::sanitize_percentage($total_attendance, $total_present, 1),
				'percentage_text' => WLSM_Config::get_percentage_text($total_attendance, $total_present, 1)
			);

			$data['monthly'] = $attendance_monthly;

			$success = true;
			$message = esc_html__('Attendance retrieved successfully.', 'school-management');

			$response_data['attendance'] = $data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	public static function student_lessons($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();
			$class_school_id = $student->class_school_id;

			$params = $request->get_params();

			// Query - Using the new function for lessons only
			$lessons_query = WLSM_M::lessons_only_query();

			// Total.
			$lessons_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$lessons_query}) AS combined_table", $class_school_id));

			// Current page and per page.
			$lessons_page = isset($params['lesson_page']) ? absint($params['lesson_page']) : 1;
			$lessons_per_page = isset($params['lesson_per_page']) ? absint($params['lesson_per_page']) : WLSM_M::lessons_per_page();

			// Page offset.
			$lessons_page_offset = ($lessons_page * $lessons_per_page) - $lessons_per_page;

			// Paginated data.
			// Fix: Properly handle parameters for prepare() function
			$limit_query = " ORDER BY l.ID DESC LIMIT %d, %d";
			$lessons = $wpdb->get_results(
				$wpdb->prepare(
					$lessons_query . $limit_query,
					// First parameter for $lessons_query's placeholder
					$class_school_id,
					// Following parameters for LIMIT clause
					$lessons_page_offset,
					$lessons_per_page
				)
			);

			// Paginated response.
			$lessons_data = array();
			$lessons_pagination = array(
				'current_page' => $lessons_page,
				'per_page' => $lessons_per_page,
			);

			// Format response.
			if (count($lessons)) {
				foreach ($lessons as $lesson) {
					$lessons_data[] = array(
						'id' => $lesson->ID,
						'title' => $lesson->title,
						'description' => $lesson->description,
						'attachment' => $lesson->attachment,
						'url' => $lesson->url,
						'link_to' => $lesson->link_to,
						'created_at' => $lesson->created_at,
						'class' => $lesson->class_label,
						'subject' => $lesson->subject_label,
						'subject_id' => $lesson->subject_id,
						'chapter' => $lesson->chapter_title,
						'chapter_id' => $lesson->chapter_id
					);
				}

				$lessons_pagination['total_pages'] = ceil($lessons_total / $lessons_per_page);
				$lessons_pagination['total_records'] = $lessons_total;
			}

			$success = true;
			$message = esc_html__('Lessons retrieved successfully.', 'school-management');

			$response_data = array(
				'lessons' => $lessons_data,
				'pagination' => $lessons_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Add this method to the WLSM_Api class
	public static function student_lessons_by_filters($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();
			$class_school_id = $student->class_school_id;

			$params = $request->get_params();
			$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
			$chapter_id = isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;

			// Build query conditions based on filters
			$query_conditions = [];
			$query_args = [$class_school_id]; // First param is always class_school_id

			// Base query - Added subject_id and chapter_id
			$lessons_query = "SELECT l.ID, l.title, l.description, l.attachment, l.url, l.link_to, l.created_at,
				c.label as class_label, s.label as subject_label, cp.title as chapter_title,
				l.subject_id, l.chapter_id
				FROM " . WLSM_LECTURE . " as l
				LEFT JOIN " . WLSM_CLASSES . " as c ON l.class_id = c.ID
				LEFT JOIN " . WLSM_SUBJECTS . " as s ON s.ID = l.subject_id
				LEFT JOIN " . WLSM_CHAPTER . " as cp ON cp.ID = l.chapter_id
				WHERE s.class_school_id = %d";

			// Add subject filter if specified
			if ($subject_id) {
				$lessons_query .= " AND l.subject_id = %d";
				$query_args[] = $subject_id;
			}

			// Add chapter filter if specified
			if ($chapter_id) {
				$lessons_query .= " AND l.chapter_id = %d";
				$query_args[] = $chapter_id;
			}

			// Current page and per page
			$lessons_page = isset($params['page']) ? absint($params['page']) : 1;
			$lessons_per_page = isset($params['per_page']) ? absint($params['per_page']) : WLSM_M::lessons_per_page();

			// Get total count
			$count_query = "SELECT COUNT(1) FROM ({$lessons_query}) AS count_table";
			$lessons_total = $wpdb->get_var($wpdb->prepare($count_query, ...$query_args));

			// Add pagination
			$lessons_page_offset = ($lessons_page * $lessons_per_page) - $lessons_per_page;
			$lessons_query .= " ORDER BY l.ID DESC LIMIT %d, %d";
			$query_args[] = $lessons_page_offset;
			$query_args[] = $lessons_per_page;

			// Execute the query
			$lessons = $wpdb->get_results($wpdb->prepare($lessons_query, ...$query_args));

			// Format data
			$lessons_data = array();
			foreach ($lessons as $lesson) {
				// Get attachment details if available
				$attachment_url = '';
				$attachment_name = '';
				if (!empty($lesson->attachment)) {
					$attachment_url = wp_get_attachment_url($lesson->attachment);
					$attachment_name = basename(get_attached_file($lesson->attachment));
				}

				$lessons_data[] = array(
					'id' => $lesson->ID,
					'title' => $lesson->title,
					'description' => $lesson->description,
					'attachment' => $lesson->attachment,
					'url' => $lesson->url,
					'link_to' => $lesson->link_to,
					'created_at' => $lesson->created_at,
					'class' => $lesson->class_label,
					'subject' => $lesson->subject_label,
					'subject_id' => $lesson->subject_id,
					'chapter' => $lesson->chapter_title,
					'chapter_id' => $lesson->chapter_id
				);
			}

			$lessons_pagination = array(
				'current_page' => $lessons_page,
				'per_page' => $lessons_per_page,
				'total_pages' => ceil($lessons_total / $lessons_per_page),
				'total_records' => $lessons_total
			);

			$success = true;
			$message = esc_html__('Lessons retrieved successfully.', 'school-management');

			$response_data = array(
				'lessons' => $lessons_data,
				'pagination' => $lessons_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Get lesson details by lesson ID.
	public static function student_lesson_details($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();
			$class_school_id = $student->class_school_id;

			$params = $request->get_params();
			$lesson_id = isset($params['lesson_id']) ? absint($params['lesson_id']) : 0;

			// Query for the specific lesson - Added subject_id and chapter_id to the SELECT clause
			$lesson_query = "SELECT l.ID, l.title, l.description, l.attachment, l.url, l.link_to, l.created_at,
				c.label as class_label, s.label as subject_label, cp.title as chapter_title,
				l.subject_id, l.chapter_id
				FROM " . WLSM_LECTURE . " as l
				LEFT JOIN " . WLSM_CLASSES . " as c ON l.class_id = c.ID
				LEFT JOIN " . WLSM_SUBJECTS . " as s ON s.ID = l.subject_id
				LEFT JOIN " . WLSM_CHAPTER . " as cp ON cp.ID = l.chapter_id
				WHERE l.ID = %d";

			$lesson = $wpdb->get_row($wpdb->prepare($lesson_query, $lesson_id));

			if (!$lesson) {
				throw new Exception(esc_html__('Lesson not found.', 'school-management'));
			}

			// Get attachment details if available
			$attachment_url = '';
			$attachment_name = '';
			if (!empty($lesson->attachment)) {
				$attachment_url = wp_get_attachment_url($lesson->attachment);
				$attachment_name = basename(get_attached_file($lesson->attachment));
			}

			$success = true;
			$message = esc_html__('Lesson details retrieved successfully.', 'school-management');

			$response_data = array(
				'id' => $lesson->ID,
				'title' => $lesson->title,
				'description' => $lesson->description,
				'attachment' => $lesson->attachment,
				'url' => $lesson->url,
				'link_to' => $lesson->link_to,
				'created_at' => $lesson->created_at,
				'class_label' => $lesson->class_label,
				'subject' => $lesson->subject_label,
				'subject_id' => $lesson->subject_id,
				'chapter' => $lesson->chapter_title,
				'chapter_id' => $lesson->chapter_id
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Study materials.
	public static function student_study_materials($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$study_materials_query = WLSM_M::study_materials_query();

			// Total.
			$study_materials_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$study_materials_query}) AS combined_table", $class_school_id));

			// Current page and per page.
			$study_materials_page = isset($params['study_materials_page']) ? absint($params['study_materials_page']) : 1;
			$study_materials_per_page = isset($params['study_materials_per_page']) ? absint($params['study_materials_per_page']) : WLSM_M::study_materials_per_page();

			// Page offset.
			$study_materials_page_offset = ($study_materials_page * $study_materials_per_page) - $study_materials_per_page;

			// Paginated data.
			$study_materials = $wpdb->get_results($wpdb->prepare($study_materials_query . ' ORDER BY cssm.ID DESC LIMIT %d, %d', $class_school_id, $study_materials_page_offset, $study_materials_per_page));

			// Paginated response.
			$study_materials_data = array();
			$study_materials_pagination = array(
				'current_page' => $study_materials_page,
				'per_page' => $study_materials_per_page,
			);

			// Format response.
			if (count($study_materials)) {
				foreach ($study_materials as $key => $study_material) {
					$study_materials_data[] = array(
						'id' => $study_material->ID,
						'title' => esc_html(stripslashes($study_material->title)),
						'date' => esc_html(WLSM_Config::get_date_text($study_material->created_at)),
					);
				}

				$study_materials_pagination['total_pages'] = ceil($study_materials_total / $study_materials_per_page);
				$study_materials_pagination['total_records'] = $study_materials_total;
			}

			$success = true;
			$message = esc_html__('Study materials retrieved successfully.', 'school-management');

			$response_data['study_materials'] = array(
				'data' => $study_materials_data,
				'pagination' => $study_materials_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Study material.
	public static function student_study_material($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$study_material_id = isset($params['study_material_id']) ? absint($params['study_material_id']) : 0;

			$study_material = $wpdb->get_row($wpdb->prepare(WLSM_M::study_material_query(), $class_school_id, $study_material_id));

			if (!$study_material) {
				throw new Exception(esc_html__('Study material not found.', 'school-management'));
			}

			$attachments = $study_material->attachments;
			if (is_serialized($attachments)) {
				$attachments = unserialize($attachments);
			} else {
				if (!is_array($attachments)) {
					$attachments = array();
				}
			}

			$attachments_data = array();
			if (count($attachments)) {
				foreach ($attachments as $attachment) {
					if (!empty($attachment)) {
						$file_name = basename(get_attached_file($attachment));
						array_push(
							$attachments_data,
							array(
								'file_name' => esc_html($file_name),
								'url' => esc_url(wp_get_attachment_url($attachment))
							)
						);
					}
				}
			}

			$success = true;
			$message = esc_html__('Study material retrieved successfully.', 'school-management');

			$response_data['study_material'] = array(
				'id' => $study_material->ID,
				'title' => esc_html(stripslashes($study_material->title)),
				'description' => esc_html(stripslashes($study_material->description)),
				'downloadable' => (intval($study_material->downloadable)),
				'date' => esc_html(WLSM_Config::get_date_text($study_material->created_at)),
				'url' => esc_url($study_material->url),
				'attachments' => $attachments_data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Homeworks.
	public static function student_homeworks($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$homeworks_query = WLSM_M::homeworks_query();

			// Total.
			$homeworks_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$homeworks_query}) AS combined_table", $school_id, $session_id, $section_id));

			// Current page and per page.
			$homeworks_page = isset($params['homeworks_page']) ? absint($params['homeworks_page']) : 1;
			$homeworks_per_page = isset($params['homeworks_per_page']) ? absint($params['homeworks_per_page']) : WLSM_M::homeworks_per_page();

			// Page offset.
			$homeworks_page_offset = ($homeworks_page * $homeworks_per_page) - $homeworks_per_page;

			// Paginated data.
			$homeworks = $wpdb->get_results($wpdb->prepare($homeworks_query . ' ORDER BY hw.homework_date DESC LIMIT %d, %d', $school_id, $session_id, $section_id, $homeworks_page_offset, $homeworks_per_page));

			// Paginated response.
			$homeworks_data = array();
			$homeworks_pagination = array(
				'current_page' => $homeworks_page,
				'per_page' => $homeworks_per_page,
			);

			// Format response.
			if (count($homeworks)) {
				foreach ($homeworks as $key => $homework) {
					$homeworks_data[] = array(
						'id' => $homework->ID,
						'title' => esc_html(stripslashes($homework->title)),
						'date' => esc_html(WLSM_Config::get_date_text($homework->homework_date)),
						'due_date' => esc_html(WLSM_Config::get_date_text($homework->homework_due_date)),
					);
				}

				$homeworks_pagination['total_pages'] = ceil($homeworks_total / $homeworks_per_page);
				$homeworks_pagination['total_records'] = $homeworks_total;
			}

			$success = true;
			$message = esc_html__('Homework retrieved successfully.', 'school-management');

			$response_data['homeworks'] = array(
				'homeworks_data' => $homeworks_data,
				'pagination' => $homeworks_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Homework.
	public static function student_homework($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;

			$homework = $wpdb->get_row($wpdb->prepare(WLSM_M::homework_query(), $school_id, $session_id, $section_id, $homework_id));

			$homework_status = $wpdb->get_row($wpdb->prepare(WLSM_M::homework_query_submission(), $school_id, $session_id, $section_id, $homework_id, $student_id));

			if (!$homework) {
				throw new Exception(esc_html__('Homework not found.', 'school-management'));
			}

			$attachments = $homework->attachments;
			if (is_serialized($attachments)) {
				$attachments = unserialize($attachments);
			} else {
				if (!is_array($attachments)) {
					$attachments = array();
				}
			}
			foreach ($attachments as $attachment) {
				if (!empty($attachment)) {
					$file_name = basename(get_attached_file($attachment));
					$file_link = wp_get_attachment_url($attachment);
				}
			}

			if ($homework_status) {
				$sub_date = isset($homework_status->created_at) ? $homework_status->created_at : '';
				$update_date = isset($homework_status->updated_at) ? $homework_status->updated_at : '';
				$status = 1;
			} else {
				$sub_date = '';
				$update_date = '';
				$status = 0;
			}

			$success = true;
			$message = esc_html__('Homework retrieved successfully.', 'school-management');

			$response_data['homework'] = array(
				'id' => $homework->ID,
				'title' => esc_html(stripslashes($homework->title)),
				'description' => esc_html(stripslashes($homework->description)),
				'downloadable' => intval($homework->downloadable),
				'date' => esc_html(WLSM_Config::get_date_text($homework->homework_date)),
				'due_date' => esc_html(WLSM_Config::get_date_text($homework->homework_due_date)),
				'attachment_link' => ($file_link),
				'attachment_name' => ($file_name),
				'Submitted' => ($status),
				'Submitted_date' => ($sub_date),
				'updated_date' => ($update_date),

			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Submit homework.
	public static function student_submit_homework_request($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$submission_id = isset($_POST['submission_id']) ? absint($_POST['submission_id']) : 0;
			$description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
			$homework_update_id = isset($_POST['homework_update']) ? absint($_POST['homework_update']) : 0;
			$homework_sub_id = isset($_POST['homework_sub_id']) ? absint($_POST['homework_sub_id']) : 0;
			$attachment = isset($_FILES['attachments']) && is_array($_FILES['attachments']) ? $_FILES['attachments'] : array();

			if (empty($submission_id)) {
				$errors['submission_id'] = esc_html__('Please Enter Submission Subject', 'school-management');
			}

			$homework = WLSM_M::fetch_homework($school_id, $session_id, $submission_id);

			if (empty($homework)) {
				$errors['submission_id'] = esc_html__('Homework not found.', 'school-management');
			}

			if (empty($description)) {
				$errors['description'] = esc_html__('Please Enter description', 'school-management');
			}

			if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
					$errors['attachment'] = esc_html__('Please provide attachment PDF format.', 'school-management');
				}
			}

			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/media.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			if (!empty($attachment)) {
				$attachment = media_handle_upload('attachments', 0);
				if (is_wp_error($attachment)) {
					throw new Exception($attachment->get_error_message());
				}
			}

			$homework_sub_id = WLSM_M::fetch_submitted_homework($school_id, $session_id, $submission_id, $student_id);

			if ( $homework->homework_due_date < date('Y-m-d') ) {
				throw new Exception(esc_html__('Homework due date has passed.', 'school-management'));
			}

			// Student leave data.
			$data = array(
				'submission_id' => $submission_id,
				'description' => $description,
				'school_id' => $school_id,
				'session_id' => $session_id,
				'student_id' => $student_id,
				'attachments' => $attachment,
			);

			if ($homework_update_id) {
				$data['updated_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->update(WLSM_HOMEWORK_SUBMISSION, $data, array(
					'ID' => $homework_sub_id,
					'submission_id' => $submission_id,
				));
			} else {
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->insert(WLSM_HOMEWORK_SUBMISSION, $data);
			}

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$homework_id = $wpdb->insert_id;

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('homework submitted successfully.', 'school-management');

			$response_data['homework'] = array(
				'id' => $homework_id,
				'description' => esc_html(($description)),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Submitted homeworks.
	public static function student_submitted_homeworks($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$params = $request->get_params();
			$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;

			$submitted_homeworks = WLSM_M::fetch_submitted_homeworks($school_id, $session_id, $homework_id, $student_id);

			if (!$submitted_homeworks) {
				throw new Exception(esc_html__('No submitted homeworks found.', 'school-management'));
			}

			if ($submitted_homeworks) {
				foreach ($submitted_homeworks as $homework) {
					$data[] = array(
						'homework_id' => isset($homework_id) ? $homework_id : 0,
						'submission_id' => isset($homework->ID) ? $homework->ID : 0,
						'description' => isset($homework->description) ? $homework->description : '',
						'date' => isset($homework->created_at) ? date('d-m-Y', strtotime($homework->created_at)) : ''
					);
				}
			}

			WLSM_Helper::check_buffer();

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Submitted homeworks retrieved successfully.', 'school-management');

			$response_data['homework'] = $data;
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Submitted homework.
	public static function fetch_submitted_homework($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$params = $request->get_params();
			$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;
			$submission_id = isset($params['submission_id']) ? absint($params['submission_id']) : 0;

			$submitted_homework = WLSM_M::get_submitted_homework($school_id, $session_id, $homework_id, $submission_id, $student_id);

			if (!$submitted_homework) {
				throw new Exception(esc_html__('Submitted homework not found.', 'school-management'));
			}

			$data = array(
				'submission_id' => isset($submitted_homework->ID) ? $submitted_homework->ID : 0,
				'attachment' => isset($submitted_homework->attachments) ? wp_get_attachment_url($submitted_homework->attachments) : '',
				'description' => isset($submitted_homework->description) ? $submitted_homework->description : ''
			);

			WLSM_Helper::check_buffer();

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Submitted homeworks retrieved successfully.', 'school-management');

			$response_data['homework'] = $data;
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Exams time table.
	public static function exams_time_table($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$start_date = $student->start_date;
			$end_date = $student->end_date;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$exams = WLSM_M_Staff_Examination::get_class_school_exams_time_table($school_id, $class_school_id, $start_date, $end_date);

			$exams_data = array();

			if (count($exams)) {
				foreach ($exams as $key => $exam) {
					$exams_data[] = array(
						'id' => $exam->ID,
						'title' => esc_html(stripslashes($exam->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($exam->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($exam->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Exams retrieved successfully.', 'school-management');

			$response_data['exams'] = array(
				'data' => $exams_data,
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Exam time table.
	public static function exam_time_table($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$exam_id = isset($params['exam_id']) ? absint($params['exam_id']) : 0;

			$exam = WLSM_M_Staff_Examination::get_class_school_exam_time_table($school_id, $class_school_id, $exam_id);

			if (!$exam) {
				throw new Exception(esc_html__('Exam not found.', 'school-management'));
			}

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label($school_id, $exam_id);
			$exam_papers = WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);

			$exam_title = $exam->exam_title;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$class_names = array();
			foreach ($exam_classes as $exam_class) {
				array_push($class_names, WLSM_M_Class::get_label_text($exam_class->label));
			}

			$class_names = implode(', ', $class_names);

			$data = array();

			foreach ($exam_papers as $key => $exam_paper) {
				$exam_data = array();

				$exam_data['subject'] = esc_html(stripcslashes($exam_paper->subject_label));
				$exam_data['paper_code'] = esc_html($exam_paper->paper_code);
				$exam_data['paper_date'] = esc_html(WLSM_Config::get_date_text($exam_paper->paper_date));
				$exam_data['start_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->start_time));
				$exam_data['end_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->end_time));

				if ($exam->enable_room_numbers) {
					$exam_data['room_number'] = esc_html($exam_paper->room_number);
				}

				array_push($data, $exam_data);
			}

			$success = true;
			$message = esc_html__('Exam time table retrieved successfully.', 'school-management');

			$response_data['exam'] = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'class' => esc_html($class_names),
				'show_room_number' => (bool) $exam->enable_room_numbers,
				'data' => $data,
				'exam_center' => esc_html(WLSM_M_Staff_Examination::get_exam_center_text($exam->exam_center))
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Admit cards.
	public static function admit_cards($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$admit_cards = WLSM_M_Staff_Examination::get_student_admit_cards($school_id, $student->ID);

			$admit_cards_data = array();

			if (count($admit_cards)) {
				foreach ($admit_cards as $key => $admit_card) {
					$admit_cards_data[] = array(
						'id' => $admit_card->ID,
						'exam_title' => esc_html(stripslashes($admit_card->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($admit_card->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($admit_card->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Admit cards retrieved successfully.', 'school-management');

			$response_data['admit_cards'] = array(
				'data' => $admit_cards_data,
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Admit card.
	public static function admit_card($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$admit_card_id = isset($params['admit_card_id']) ? absint($params['admit_card_id']) : 0;

			// Checks if admit card exists.
			$admit_card = WLSM_M_Staff_Examination::fetch_student_admit_card($school_id, $student_id, $admit_card_id);

			if (!$admit_card) {
				throw new Exception(esc_html__('Admit card not found.', 'school-management'));
			}

			$exam_id = $admit_card->exam_id;

			// Checks if exam exists.
			$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);

			if (!$exam) {
				throw new Exception(esc_html__('Exam not found.', 'school-management'));
			}

			$exam_id = $exam->ID;
			$exam_title = $exam->exam_title;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label($school_id, $exam_id);
			$exam_papers = WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);

			$class_names = array();
			foreach ($exam_classes as $exam_class) {
				array_push($class_names, WLSM_M_Class::get_label_text($exam_class->label));
			}

			$class_names = implode(', ', $class_names);

			$photo_id = $admit_card->photo_id;

			$data = array();

			foreach ($exam_papers as $key => $exam_paper) {
				$exam_data = array();

				$exam_data['subject'] = esc_html(stripcslashes($exam_paper->subject_label));
				$exam_data['paper_code'] = esc_html($exam_paper->paper_code);
				$exam_data['paper_date'] = esc_html(WLSM_Config::get_date_text($exam_paper->paper_date));
				$exam_data['start_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->start_time));
				$exam_data['end_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->end_time));

				if ($exam->enable_room_numbers) {
					$exam_data['room_number'] = esc_html($exam_paper->room_number);
				}

				array_push($data, $exam_data);
			}

			$success = true;
			$message = esc_html__('Admit card retrieved successfully.', 'school-management');

			$response_data['exam'] = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'class' => esc_html($class_names),
				'show_room_number' => (bool) $exam->enable_room_numbers,
				'data' => $data,
				'exam_center' => esc_html(WLSM_M_Staff_Examination::get_exam_center_text($exam->exam_center))
			);

			$response_data['admit_card'] = array(
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->name)),
				'enrollment_number' => esc_html($admit_card->enrollment_number),
				'session_label' => esc_html(WLSM_M_Session::get_label_text($admit_card->session_label)),
				'class' => esc_html(WLSM_M_Class::get_label_text($admit_card->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($admit_card->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number)),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($admit_card->phone)),
				'email' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->email)),
				'photo' => esc_url(wp_get_attachment_url($photo_id))
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Exams results.
	public static function exam_results($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$exam_results = WLSM_M_Staff_Examination::get_student_published_exam_results($school_id, $student_id);

			$results_data = array();

			if (count($exam_results)) {
				foreach ($exam_results as $key => $value) {
					$results_data[] = array(
						'id' => $value->ID,
						'title' => esc_html(stripslashes($value->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($value->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($value->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Exam results retrieved successfully.', 'school-management');

			$response_data['results'] = array(
				'data' => $results_data,
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Exam result.
	public static function exam_result($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$admit_card_id = isset($params['admit_card_id']) ? absint($params['admit_card_id']) : 0;

			// Checks if admit card exists for published exam result.
			$admit_card = WLSM_M_Staff_Examination::get_student_published_exam_result($school_id, $student->ID, $admit_card_id);

			if (!$admit_card) {
				throw new Exception(esc_html__('Exam result not found.', 'school-management'));
			}

			$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $admit_card->exam_id);

			$exam_id = $exam->ID;
			$exam_title = $exam->exam_title;
			$exam_center = $exam->exam_center;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$exam_papers = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card($school_id, $admit_card_id);
			$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id, $admit_card_id);

			$grade_criteria = WLSM_Config::sanitize_grade_criteria($exam->grade_criteria);

			$enable_overall_grade = $grade_criteria['enable_overall_grade'];
			$marks_grades = $grade_criteria['marks_grades'];

			$show_marks_grades = count($marks_grades);

			$student_rank = WLSM_M_Staff_Examination::calculate_exam_ranks($school_id, $exam_id, array(), $admit_card->ID);

			$data = array();

			$total_maximum_marks = 0;
			$total_obtained_marks = 0;

			foreach ($exam_papers as $key => $exam_paper) {
				$results_data = array();

				if ($admit_card && isset($exam_results[$exam_paper->ID])) {
					$exam_result = $exam_results[$exam_paper->ID];
					$obtained_marks = $exam_result->obtained_marks;
				} else {
					$obtained_marks = '';
				}

				$percentage = WLSM_Config::sanitize_percentage($exam_paper->maximum_marks, WLSM_Config::sanitize_marks($obtained_marks));

				$total_maximum_marks += $exam_paper->maximum_marks;
				$total_obtained_marks += WLSM_Config::sanitize_marks($obtained_marks);

				$results_data['paper_code'] = esc_html($exam_paper->paper_code);
				$results_data['subject_name'] = esc_html(stripcslashes($exam_paper->subject_label));
				$results_data['subject_type'] = esc_html(WLSM_Helper::get_subject_type_text($exam_paper->subject_type));
				$results_data['maximum_marks'] = esc_html($exam_paper->maximum_marks);
				$results_data['obtained_marks'] = esc_html($obtained_marks);

				if ($show_marks_grades) {
					$results_data['grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $percentage));
				}

				array_push($data, $results_data);
			}

			$total_percentage = WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks);

			$success = true;
			$message = esc_html__('Exam result retrieved successfully.', 'school-management');

			$response_data['result'] = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->name)),
				'enrollment_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->enrollment_number)),
				'session' => esc_html(WLSM_M_Session::get_label_text($admit_card->session_label)),
				'class' => esc_html(WLSM_M_Class::get_label_text($admit_card->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($admit_card->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number)),
				'show_marks_grades' => (bool) $show_marks_grades,
				'show_overall_grade' => (bool) $enable_overall_grade,
				'data' => $data,
				'total_maximum_marks' => $total_maximum_marks,
				'total_obtained_marks' => $total_obtained_marks,
				'percentage_value' => esc_html($total_percentage),
				'percentage_text' => esc_html(WLSM_Config::get_percentage_text($total_maximum_marks, $total_obtained_marks)),
			);

			if ($show_marks_grades && $enable_overall_grade) {
				$response_data['result']['overall_grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $total_percentage));
			}

			$response_data['result']['student_rank'] = esc_html($student_rank);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Overall result.
	public static function overall_result($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);
			$session_label = $student->session_label;


			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$admit_cards = WLSM_M_Staff_Examination::get_student_exam_results_assessment($school_id, $student_id);

			$results_data = array();

			$overall_maximum_marks = 0;
			$overall_obtained_marks = 0;
			foreach ($admit_cards as $admit_card) {
				$exam_id = $admit_card->exam_id;
				$exam_title = $admit_card->exam_title;
				$start_date = $admit_card->start_date;
				$end_date = $admit_card->end_date;
				$admit_card_id = $admit_card->ID;

				$exam_papers = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card($school_id, $admit_card_id);
				$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id, $admit_card_id);

				$total_maximum_marks = 0;
				$total_obtained_marks = 0;

				foreach ($exam_papers as $key => $exam_paper) {
					if ($admit_card && isset($exam_results[$exam_paper->ID])) {
						$exam_result = $exam_results[$exam_paper->ID];
						$obtained_marks = $exam_result->obtained_marks;
					} else {
						$obtained_marks = '';
					}

					$percentage = WLSM_Config::sanitize_percentage($exam_paper->maximum_marks, WLSM_Config::sanitize_marks($obtained_marks));

					$total_maximum_marks += $exam_paper->maximum_marks;
					$total_obtained_marks += WLSM_Config::sanitize_marks($obtained_marks);
				}

				$total_percentage = WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks);

				$overall_maximum_marks += $total_maximum_marks;
				$overall_obtained_marks += WLSM_Config::sanitize_marks($total_obtained_marks);

				$results_data[] = array(
					'id' => $admit_card->ID,
					'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
					'exam_date' => esc_html(WLSM_Config::get_date_text($start_date)),
					'maximum_marks' => esc_html($total_maximum_marks),
					'obtained_marks' => esc_html($total_obtained_marks),
					'percentage_value' => esc_html(WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks)),
					'percentage_text' => esc_html(WLSM_Config::get_percentage_text($total_maximum_marks, $total_obtained_marks)),
				);
			}

			$success = true;
			$message = esc_html__('Overall result retrieved successfully.', 'school-management');

			$response_data['result'] = array(
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)),
				'enrollment_number' => esc_html($student->enrollment_number),
				'session' => esc_html(WLSM_M_Session::get_label_text($session_label)),
				'class' => esc_html(WLSM_M_Class::get_label_text($student->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($student->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($student->phone)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->father_name)),
				'father_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($student->father_phone)),
				'data' => $results_data,
				'overall_maximum_marks' => esc_html($overall_maximum_marks),
				'overall_obtained_marks' => esc_html($overall_obtained_marks),
				'overall_percentage_value' => esc_html(WLSM_Config::sanitize_percentage($overall_maximum_marks, $overall_obtained_marks)),
				'overall_percentage_text' => esc_html(WLSM_Config::get_percentage_text($overall_maximum_marks, $overall_obtained_marks)),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Leave requests.
	public static function student_leave_requests($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			// Query.
			$leaves_query = WLSM_M::leaves_query();

			// Total.
			$leaves_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$leaves_query}) AS combined_table", $school_id, $session_id, $student_id));

			// Current page and per page.
			$leaves_page = isset($params['leaves_page']) ? absint($params['leaves_page']) : 1;
			$leaves_per_page = isset($params['leaves_per_page']) ? absint($params['leaves_per_page']) : WLSM_M::leaves_per_page();

			// Page offset.
			$leaves_page_offset = ($leaves_page * $leaves_per_page) - $leaves_per_page;

			// Paginated data.
			$leaves = $wpdb->get_results($wpdb->prepare($leaves_query . ' ORDER BY lv.ID DESC LIMIT %d, %d', $school_id, $session_id, $student_id, $leaves_page_offset, $leaves_per_page));

			// Paginated response.
			$leaves_data = array();
			$leaves_pagination = array(
				'current_page' => $leaves_page,
				'per_page' => $leaves_per_page,
			);

			// Format response.
			if (count($leaves)) {
				foreach ($leaves as $key => $leave) {
					if ($leave->end_date) {
						$leave_date = sprintf(
							/* translators: 1: leave start date, 2: leave end date */
							esc_html__('%1$s to %2$s', 'school-management'),
							esc_html(WLSM_Config::get_date_text($leave->start_date)),
							esc_html(WLSM_Config::get_date_text($leave->end_date))
						);
					} else {
						$leave_date = esc_html(WLSM_Config::get_date_text($leave->start_date));
					}

					$leaves_data[] = array(
						'id' => $leave->ID,
						'reason' => esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($leave->description))),
						'leave_date' => $leave_date,
						'approval' => esc_html(WLSM_M_Staff_Class::get_leave_approval_text($leave->is_approved))
					);
				}

				$leaves_pagination['total_pages'] = ceil($leaves_total / $leaves_per_page);
				$leaves_pagination['total_records'] = $leaves_total;
			}

			$success = true;
			$message = esc_html__('Leave requests retrieved successfully.', 'school-management');

			$response_data['leaves'] = array(
				'data' => $leaves_data,
				'pagination' => $leaves_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Submit invoice payment request.
	public static function student_submit_invoice_payment_request($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$invoice_id = isset($params['id']) ? sanitize_text_field($params['id']) : '';
			$amount = isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
			$transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
			$payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
			$attachment = (isset($_POST['attachment']) && sanitize_text_field($_POST['attachment'])) ? $_POST['attachment'] : NULL;

			if ($payment_method == 'bank-transfer') {
				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
						$errors['attachment'] = esc_html__('Please provide attachment PDF format.', 'school-management');
					}
				}
				$title = rand(3, 5);
				// Upload dir.
				$upload_dir = wp_upload_dir();
				$upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

				$img = str_replace('data:image/jpeg;base64,', '', $attachment);
				$img = str_replace(' ', '+', $img);
				$decoded = base64_decode($img);
				$filename = $title . '.jpeg';
				$file_type = 'image/jpeg';
				$hashed_filename = md5($filename . microtime()) . '_' . $filename;

				// Save the image in the uploads directory.
				$upload_file = file_put_contents($upload_path . $hashed_filename, $decoded);

				$attachment = array(
					'post_mime_type' => $file_type,
					'post_title' => preg_replace('/\.[^.]+$/', '', basename($hashed_filename)),
					'post_content' => '',
					'post_status' => 'inherit',
					'guid' => $upload_dir['url'] . '/' . basename($hashed_filename)
				);

				$attach_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $hashed_filename);
			}


			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);
			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);
			$data = array(
				'receipt_number' => $receipt_number,
				'amount' => $amount,
				'transaction_id' => $transaction_id,
				'payment_method' => $payment_method,
				'invoice_label' => $invoice->invoice_title,
				'invoice_payable' => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id' => $invoice_id,
				'school_id' => $school_id,
				'added_by' => 1,
			);
			$data['created_at'] = current_time('Y-m-d H:i:s');

			if ($payment_method == 'bank-transfer') {
				$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);
				$pending_payment_data = array(
					'receipt_number' => $receipt_number,
					'amount' => $amount,
					'payment_method' => $payment_method,
					'transaction_id' => $transaction_id,
					'invoice_label' => $invoice->invoice_title,
					'invoice_payable' => $invoice->payable,
					'student_record_id' => $invoice->student_id,
					'invoice_id' => $invoice_id,
					'school_id' => $school_id,
					'attachment' => $attach_id,
				);
				$pending_payment_data['created_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->insert(WLSM_PENDING_PAYMENTS, $pending_payment_data);
			} else {
				$success = $wpdb->insert(WLSM_PAYMENTS, $data);
			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			if (WLSM_M_Invoice::get_paid_key() === $invoice_status && ($invoice_status !== $invoice->status)) {
				$reload = true;
			} else {
				$reload = false;
			}

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$payment_id = $wpdb->insert_id;

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Request submitted successfully.', 'school-management');

			$response_data['payment'] = array(
				'id' => $payment_id,
				// 'reason'     => esc_html( WLSM_Config::limit_string( WLSM_M_Staff_Class::get_name_text( $description ) ) ),
				// 'leave_date' => esc_html( $leave_date ),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Submit leave request.
	public static function student_submit_leave_request($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$wpdb->query('BEGIN;');

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$params = $request->get_params();

			$description = isset($params['reason']) ? sanitize_text_field($params['reason']) : '';
			$start_date = isset($params['start_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['start_date'])) : NULL;
			$end_date = isset($params['end_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['end_date'])) : NULL;
			$multiple_days = isset($params['is_multiple_days']) ? (bool) $params['is_multiple_days'] : 0;

			if ($multiple_days) {
				if ($start_date >= $end_date) {
					throw new Exception(esc_html__('Start date must be lower than end date.', 'school-management'));
				}
			}

			if (empty($description)) {
				throw new Exception(esc_html__('Please specify reason.', 'school-management'));
			}

			if (empty($start_date)) {
				if ($multiple_days) {
					throw new Exception(esc_html__('Please specify leave start date.', 'school-management'));
				} else {
					throw new Exception(esc_html__('Please specify leave date.', 'school-management'));
				}
			} else {
				$start_date = $start_date->format('Y-m-d');
			}

			if ($multiple_days) {
				if (empty($end_date)) {
					throw new Exception(esc_html__('Please specify leave end date.', 'school-management'));
				} else {
					$end_date = $end_date->format('Y-m-d');
				}
			} else {
				$end_date = NULL;
			}

			// Student leave data.
			$data = array(
				'student_record_id' => $student_id,
				'description' => $description,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'school_id' => $school_id,
			);

			$data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_LEAVES, $data);

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$leave_id = $wpdb->insert_id;

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Leave request submitted successfully.', 'school-management');

			if ($end_date) {
				$leave_date = sprintf(
					/* translators: 1: leave start date, 2: leave end date */
					esc_html__('%1$s to %2$s', 'school-management'),
					esc_html(WLSM_Config::get_date_text($start_date)),
					esc_html(WLSM_Config::get_date_text($end_date))
				);
			} else {
				$leave_date = esc_html(WLSM_Config::get_date_text($start_date));
			}

			$response_data['leave'] = array(
				'id' => $leave_id,
				'reason' => esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($description))),
				'leave_date' => esc_html($leave_date),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - About school.
	public static function student_about_school($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$school = WLSM_M_School::fetch_school($school_id);
			if (!$school) {
				throw new Exception(esc_html__('School not found.', 'school-management'));
			}

			// General settings.
			$settings_general = WLSM_M_Setting::get_settings_general($school_id);
			$school_logo = $settings_general['school_logo'];

			$success = true;
			$message = esc_html__('School details retrieved successfully.', 'school-management');

			$response_data['school'] = array(
				'name' => esc_html(WLSM_M_School::get_label_text($school->label)),
				'phone' => esc_html(WLSM_M_School::get_phone_text($school->phone)),
				'email' => esc_html(WLSM_M_School::get_email_text($school->email)),
				'address' => esc_html(WLSM_M_School::get_address_text($school->address)),
				'logo' => esc_url(wp_get_attachment_url($school_logo)),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Student - Common details.
	public static function student_common_details($student)
	{
		$school_data = array();

		$school_id 	= isset($student->school_id) ? absint($student->school_id) : 0;
		$school 	= WLSM_M_School::fetch_school($school_id);

		if ($school) {
			// General settings.
			$settings_general = WLSM_M_Setting::get_settings_general($school_id);
			if (wp_get_attachment_url($settings_general['school_logo'])) {
				$school_logo = wp_get_attachment_url($settings_general['school_logo']);
			} else {
				$school_logo = "";
			}


			$school_data = array(
				'name' => esc_html(WLSM_M_School::get_label_text($school->label)),
				'phone' => esc_html(WLSM_M_School::get_phone_text($school->phone)),
				'email' => esc_html(WLSM_M_School::get_email_text($school->email)),
				'address' => esc_html(WLSM_M_School::get_address_text($school->address)),
				'description' => esc_html(WLSM_M_School::get_address_text($school->description)),
				'logo' => esc_url($school_logo),
			);
		}

		return array(
			'id' => esc_html($student->ID),
			'school' => $school_data,
		);
	}

	// Parent - Students.
	public static function parent_students($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$students = WLSM_M_Parent::fetch_students($unique_student_ids);

			$response_data = array();

			$students_data = array();

			if (count($students)) {
				foreach ($students as $key => $student) {
					$students_data[] = array(
						'id' => $student->ID,
						'name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)),
						'school' => esc_html(WLSM_M_School::get_label_text($student->school_name)),
						'session' => esc_html(WLSM_M_Session::get_label_text($student->session_label)),
						'class' => esc_html(WLSM_M_Class::get_label_text($student->class_label)),
						'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($student->section_label)),
						'admission_number' => esc_html(WLSM_M_Staff_Class::get_admission_no_text($student->admission_number)),
						'enrollment_number' => esc_html($student->enrollment_number),
						'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)),
					);
				}
			}

			$success = true;
			$message = esc_html__('Students retrieved successfully.', 'school-management');

			$response_data['students'] = array(
				'data' => $students_data,
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Noticeboard.
	public static function parent_noticeboard($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			// Query.
			$notices_query = WLSM_M::notices_query();

			// Total.
			$notices_total = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(1) FROM ({$notices_query}) AS combined_table",
				$class_school_id,
				$class_school_id,
				$school_id
			));

			// Current page and per page.
			$notices_page = isset($params['notices_page']) ? absint($params['notices_page']) : 1;
			$notices_per_page = isset($params['notices_per_page']) ? absint($params['notices_per_page']) : WLSM_M::notices_per_page();

			// Page offset.
			$notices_page_offset = ($notices_page * $notices_per_page) - $notices_per_page;

			// Paginated data.
			$notices = $wpdb->get_results($wpdb->prepare(
				$notices_query . ' ORDER BY n.ID DESC LIMIT %d, %d',
				$notices_page_offset,
				$notices_per_page
			));

			// Paginated response.
			$notices_data = array();
			$notices_pagination = array(
				'current_page' => $notices_page,
				'per_page' => $notices_per_page,
			);

			// Filter by class only.
			$filtered_notices = [];
			foreach ($notices as $notice) {
				$notice_data = @unserialize($notice->notice_data);
				if ($notice_data === false) {
					$notice_data = array(
						'classes' => array('all')
					);
				}

				$notice_data['classes'] = isset($notice_data['classes']) ? (array) $notice_data['classes'] : array('all');

				// Class match check
				if (in_array($student->class_id, $notice_data['classes'], true) || in_array('all', $notice_data['classes'], true)) {
					$filtered_notices[] = $notice;
				}
			}

			// Format response.
			if (count($filtered_notices)) {
				$today = new DateTime();
				$today->setTime(0, 0, 0);

				foreach ($filtered_notices as $notice) {
					$link_to = $notice->link_to;
					$link = '#';

					if ('url' === $link_to) {
						if (!empty($notice->url)) {
							$link = $notice->url;
						}
					} else if ('attachment' === $link_to) {
						if (!empty($notice->attachment)) {
							$attachment = $notice->attachment;
							$link = wp_get_attachment_url($attachment);
						}
					}

					$notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $notice->created_at);
					$notice_date->setTime(0, 0, 0);

					$interval = $today->diff($notice_date);

					$notices_data[] = array(
						'id' => $notice->ID,
						'title' => esc_html(stripslashes($notice->title)),
						'description' => esc_html(stripslashes($notice->description)),
						'link' => esc_url($link),
						'date' => esc_html(WLSM_Config::get_date_text($notice->created_at)),
						'is_new' => ($interval->days < 7) ? true : false
					);
				}

				$notices_pagination['total_pages'] = ceil($notices_total / $notices_per_page);
				$notices_pagination['total_records'] = $notices_total;
			}

			$success = true;
			$message = esc_html__('Noticeboard retrieved successfully.', 'school-management');

			$response_data['noticeboard'] = array(
				'new_notice_icon' => esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'),
				'data' => $notices_data,
				'pagination' => $notices_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Fee Invoices.
	public static function parent_fee_invoices($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$invoices = WLSM_M_Staff_Accountant::get_student_pending_invoices($student->ID);

			$invoices_data = array();

			if (count($invoices)) {
				foreach ($invoices as $row) {
					$due = $row->payable - $row->paid;
					$invoices_data[] = array(
						'id' => $row->ID,
						'invoice_number' => esc_html($row->invoice_number),
						'invoice_title' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($row->invoice_title)),
						'payable' => esc_html(WLSM_Config::sanitize_money($row->payable)),
						'payable_display' => esc_html(WLSM_Config::get_money_text($row->payable, $school_id)),
						'paid' => esc_html(WLSM_Config::sanitize_money($row->paid)),
						'paid_display' => esc_html(WLSM_Config::get_money_text($row->paid, $school_id)),
						'due' => esc_html(WLSM_Config::sanitize_money($due)),
						'due_display' => esc_html(WLSM_Config::get_money_text($due, $school_id)),
						'status' => esc_html($row->status),
						'status_text' => esc_html(WLSM_M_Invoice::get_status_text($row->status, false)),
						'show_pay_now' => WLSM_M_Invoice::get_paid_key() !== $row->status,
						'date_issued' => esc_html(WLSM_Config::get_date_text($row->date_issued)),
						'due_date' => esc_html(WLSM_Config::get_date_text($row->due_date)),
					);
				}
			}

			$success = true;
			$message = esc_html__('Fee invoices retrieved successfully.', 'school-management');

			$response_data['invoices'] = array(
				'data' => $invoices_data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Fee Invoice.
	public static function parent_fee_invoice($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$invoice_id = isset($params['invoice_id']) ? absint($params['invoice_id']) : 0;

			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

			if (!$invoice) {
				throw new Exception(esc_html__('Invoice not found.', 'school-management'));
			}

			$due = $invoice->payable - $invoice->paid;

			$invoice_partial_payment = $invoice->partial_payment;

			$currency = WLSM_Config::currency($school_id);

			// Razorpay settings.
			$settings_razorpay = WLSM_M_Setting::get_settings_razorpay($school_id);
			$school_razorpay_enable = $settings_razorpay['enable'];

			// Stripe settings.
			$settings_stripe = WLSM_M_Setting::get_settings_stripe($school_id);
			$school_stripe_enable = $settings_stripe['enable'];

			// PayPal settings.
			$settings_paypal = WLSM_M_Setting::get_settings_paypal($school_id);
			$school_paypal_enable = $settings_paypal['enable'];

			// Pesapal settings.
			$settings_pesapal = WLSM_M_Setting::get_settings_pesapal($school_id);
			$school_pesapal_enable = $settings_pesapal['enable'];

			// Paystack settings.
			$settings_paystack = WLSM_M_Setting::get_settings_paystack($school_id);
			$school_paystack_enable = $settings_paystack['enable'];

			// Paytm settings.
			$settings_paytm = WLSM_M_Setting::get_settings_paytm($school_id);
			$school_paytm_enable = $settings_paytm['enable'];

			$success = true;
			$message = esc_html__('Fee invoice retrieved successfully.', 'school-management');

			$response_data['invoice'] = array(
				'id' => $invoice->ID,
				'invoice_number' => esc_html($invoice->invoice_number),
				'invoice_title' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
				'date_issued' => esc_html(WLSM_Config::get_date_text($invoice->date_issued)),
				'due_date' => esc_html(WLSM_Config::get_date_text($invoice->due_date)),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($invoice->student_name)),
				'enrollment_number' => esc_html($invoice->enrollment_number),
				'class' => esc_html(WLSM_M_Class::get_label_text($invoice->class_label)),
				'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($invoice->section_label)),
				'fees_due' => esc_html(WLSM_Config::sanitize_money($due)),
				'fees_due_display' => esc_html(WLSM_Config::get_money_text($due, $school_id)),
				'partial_payment_allowed' => (bool) $invoice_partial_payment,
				'currency' => $currency
			);

			$payment_methods = array();

			if ($school_razorpay_enable && WLSM_Payment::currency_supports_razorpay($currency)) {
				$payment_methods['razorpay'] = esc_html(WLSM_M_Invoice::get_payment_method_text('razorpay'));
			}

			if ($school_stripe_enable && WLSM_Payment::currency_supports_stripe($currency)) {
				$payment_methods['stripe'] = esc_html(WLSM_M_Invoice::get_payment_method_text('stripe'));
			}

			if ($school_paypal_enable && WLSM_Payment::currency_supports_paypal($currency)) {
				$payment_methods['paypal'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paypal'));
			}

			if ($school_pesapal_enable && WLSM_Payment::currency_supports_pesapal($currency)) {
				$payment_methods['pesapal'] = esc_html(WLSM_M_Invoice::get_payment_method_text('pesapal'));
			}

			if ($school_paystack_enable && WLSM_Payment::currency_supports_paystack($currency)) {
				$payment_methods['paystack'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paystack'));
			}

			if ($school_paytm_enable && WLSM_Payment::currency_supports_paytm($currency)) {
				$payment_methods['paytm'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paytm'));
			}

			$response_data['payment_methods'] = $payment_methods;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Payment History.
	public static function parent_payments($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			// Query.
			$payments_query = WLSM_M::payments_query();

			// Total.
			$payments_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$payments_query}) AS combined_table", $student->ID));

			// Current page and per page.
			$payments_page = isset($params['payments_page']) ? absint($params['payments_page']) : 1;
			$payments_per_page = isset($params['payments_per_page']) ? absint($params['payments_per_page']) : WLSM_M::payments_per_page();

			// Page offset.
			$payments_page_offset = ($payments_page * $payments_per_page) - $payments_per_page;

			// Paginated data.
			$payments = $wpdb->get_results($wpdb->prepare($payments_query . ' ORDER BY p.ID DESC LIMIT %d, %d', $student->ID, $payments_page_offset, $payments_per_page));

			// Paginated response.
			$payments_data = array();
			$payments_pagination = array(
				'current_page' => $payments_page,
				'per_page' => $payments_per_page,
			);

			// Format response.
			if (count($payments)) {
				foreach ($payments as $key => $row) {
					if ($row->invoice_id) {
						$invoice_title = $row->invoice_title;
					} else {
						$invoice_title = $row->invoice_label;
					}

					$payments_data[] = array(
						'id' => $row->ID,
						'receipt_number' => esc_html(WLSM_M_Invoice::get_receipt_number_text($row->receipt_number)),
						'amount' => esc_html(WLSM_Config::sanitize_money($row->amount)),
						'amount_display' => esc_html(WLSM_Config::get_money_text($row->amount, $school_id)),
						'payment_method' => esc_html(WLSM_M_Invoice::get_payment_method_text($row->payment_method)),
						'transaction_id' => esc_html(WLSM_M_Invoice::get_transaction_id_text($row->transaction_id)),
						'date' => esc_html(WLSM_Config::get_date_text($row->created_at)),
						'invoice' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice_title))
					);
				}

				$payments_pagination['total_pages'] = ceil($payments_total / $payments_per_page);
				$payments_pagination['total_records'] = $payments_total;
			}

			$success = true;
			$message = esc_html__('Payments retrieved successfully.', 'school-management');

			$response_data['payments'] = array(
				'data' => $payments_data,
				'pagination' => $payments_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Payment Receipt.
	public static function parent_payment($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$payment_id = isset($params['payment_id']) ? absint($params['payment_id']) : 0;

			$payment = WLSM_M_Staff_Accountant::get_student_payment($student_id, $payment_id);

			if (!$payment) {
				throw new Exception(esc_html__('Payment not found.', 'school-management'));
			}

			$success = true;
			$message = esc_html__('Payment details retrieved successfully.', 'school-management');

			if ($payment->invoice_id) {
				$invoice_title = esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($payment->invoice_title));
			} else {
				$invoice_title = esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($payment->invoice_label));
			}

			$response_data['payment'] = array(
				'id' => $payment->ID,
				'receipt_number' => esc_html(WLSM_M_Invoice::get_receipt_number_text($payment->receipt_number)),
				'amount' => esc_html(WLSM_Config::sanitize_money($payment->amount)),
				'amount_display' => esc_html(WLSM_Config::get_money_text($payment->amount, $school_id)),
				'payment_method' => esc_html(WLSM_M_Invoice::get_payment_method_text($payment->payment_method)),
				'transaction_id' => esc_html(WLSM_M_Invoice::get_transaction_id_text($payment->transaction_id)),
				'date' => esc_html(WLSM_Config::get_date_text($payment->created_at)),
				'invoice' => esc_html($invoice_title),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->student_name)),
				'enrollment_number' => esc_html($payment->enrollment_number),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($payment->phone)),
				'email' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->email)),
				'class' => esc_html(WLSM_M_Class::get_label_text($payment->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($payment->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($payment->roll_number)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->father_name)),
				'father_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($payment->father_phone)),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Class time table.
	public static function parent_class_time_table($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$section = WLSM_M_Staff_Class::get_school_section($school_id, $student->section_id);

			if (!$section) {
				die;
			}

			$class_label = $section->class_label;
			$section_label = $section->label;

			$data = array();

			foreach (WLSM_Helper::days_list() as $key => $day) {
				$routines = WLSM_M_Staff_Class::get_section_routines_by_day($school_id, $section_id, $key);

				$day_routine = array(
					'day' => $day
				);

				$routines_data = array();
				foreach ($routines as $routine) {
					$routine_data = array();
					$routine_data['subject'] = sprintf(
						wp_kses(
							/* translators: 1: subject label, 2: subject code */
							_x('%1$s (%2$s)', 'Subject', 'school-management'),
							array('span' => array('class' => array()))
						),
						esc_html(WLSM_M_Staff_Class::get_subject_label_text($routine->subject_label)),
						esc_html($routine->subject_code)
					);

					$routine_data['start_time'] = esc_html(WLSM_Config::get_time_text($routine->start_time));
					$routine_data['end_time'] = esc_html(WLSM_Config::get_time_text($routine->end_time));

					$routine_data['room'] = esc_html($routine->room_number);

					if ($routine->teacher_name) {
						$routine_data['teacher'] = esc_html(WLSM_M_Staff_Class::get_name_text($routine->teacher_name));
					}

					array_push($routines_data, $routine_data);
				}

				$day_routine['routines'] = $routines_data;

				array_push($data, $day_routine);
			}

			$success = true;
			$message = esc_html__('Class time table retrieved successfully.', 'school-management');

			$response_data['class_time_table'] = array(
				'class' => esc_html(WLSM_M_Class::get_label_text($class_label)),
				'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($section_label)),
				'data' => $data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Attendance.
	public static function parent_attendance($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$attendance = WLSM_M_Staff_General::get_student_attendance_report($student_id);

			$data = array();

			$total_attendance = 0;
			$total_present = 0;
			$total_absent = 0;

			$attendance_monthly = array();
			foreach ($attendance as $monthly) {
				$month = new DateTime();
				$month->setDate($monthly->year, $monthly->month, 1);
				$total_attendance += $monthly->total_attendance;
				$total_present += $monthly->total_present;
				$total_absent += $monthly->total_absent;

				$attendance_data = array(
					'month' => esc_html($month->format('F Y')),
					'total_attendance' => esc_html($monthly->total_attendance),
					'total_present' => esc_html($monthly->total_present),
					'total_absent' => esc_html($monthly->total_absent),
				);

				array_push($attendance_monthly, $attendance_data);
			}

			$data['overall'] = array(
				'total_attendance' => $total_attendance,
				'total_present' => $total_present,
				'total_absent' => $total_absent,
				'percentage_value' => WLSM_Config::sanitize_percentage($total_attendance, $total_present, 1),
				'percentage_text' => WLSM_Config::get_percentage_text($total_attendance, $total_present, 1)
			);

			$data['monthly'] = $attendance_monthly;

			$success = true;
			$message = esc_html__('Attendance retrieved successfully.', 'school-management');

			$response_data['attendance'] = $data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Exams results.
	public static function parent_exam_results($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$exam_results = WLSM_M_Staff_Examination::get_student_published_exam_results($school_id, $student_id);

			$results_data = array();

			if (count($exam_results)) {
				foreach ($exam_results as $key => $value) {
					$results_data[] = array(
						'id' => $value->ID,
						'title' => esc_html(stripslashes($value->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($value->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($value->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Exam results retrieved successfully.', 'school-management');

			$response_data['results'] = array(
				'data' => $results_data,
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Exam result.
	public static function parent_exam_result($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$common_details = self::student_common_details($student);
			$response_data['student'] = $common_details;

			$admit_card_id = isset($params['admit_card_id']) ? absint($params['admit_card_id']) : 0;

			// Checks if admit card exists for published exam result.
			$admit_card = WLSM_M_Staff_Examination::get_student_published_exam_result($school_id, $student->ID, $admit_card_id);

			if (!$admit_card) {
				throw new Exception(esc_html__('Exam result not found.', 'school-management'));
			}

			$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $admit_card->exam_id);

			$exam_id = $exam->ID;
			$exam_title = $exam->exam_title;
			$exam_center = $exam->exam_center;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$exam_papers = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card($school_id, $admit_card_id);
			$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id, $admit_card_id);

			$grade_criteria = WLSM_Config::sanitize_grade_criteria($exam->grade_criteria);

			$enable_overall_grade = $grade_criteria['enable_overall_grade'];
			$marks_grades = $grade_criteria['marks_grades'];

			$show_marks_grades = count($marks_grades);

			$student_rank = WLSM_M_Staff_Examination::calculate_exam_ranks($school_id, $exam_id, array(), $admit_card->ID);

			$data = array();

			$total_maximum_marks = 0;
			$total_obtained_marks = 0;

			foreach ($exam_papers as $key => $exam_paper) {
				$results_data = array();

				if ($admit_card && isset($exam_results[$exam_paper->ID])) {
					$exam_result = $exam_results[$exam_paper->ID];
					$obtained_marks = $exam_result->obtained_marks;
				} else {
					$obtained_marks = '';
				}

				$percentage = WLSM_Config::sanitize_percentage($exam_paper->maximum_marks, WLSM_Config::sanitize_marks($obtained_marks));

				$total_maximum_marks += $exam_paper->maximum_marks;
				$total_obtained_marks += WLSM_Config::sanitize_marks($obtained_marks);

				$results_data['paper_code'] = esc_html($exam_paper->paper_code);
				$results_data['subject_name'] = esc_html(stripcslashes($exam_paper->subject_label));
				$results_data['subject_type'] = esc_html(WLSM_Helper::get_subject_type_text($exam_paper->subject_type));
				$results_data['maximum_marks'] = esc_html($exam_paper->maximum_marks);
				$results_data['obtained_marks'] = esc_html($obtained_marks);

				if ($show_marks_grades) {
					$results_data['grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $percentage));
				}

				array_push($data, $results_data);
			}

			$total_percentage = WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks);

			$success = true;
			$message = esc_html__('Exam result retrieved successfully.', 'school-management');

			$response_data['result'] = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->name)),
				'enrollment_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->enrollment_number)),
				'session' => esc_html(WLSM_M_Session::get_label_text($admit_card->session_label)),
				'class' => esc_html(WLSM_M_Class::get_label_text($admit_card->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($admit_card->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number)),
				'show_marks_grades' => (bool) $show_marks_grades,
				'show_overall_grade' => (bool) $enable_overall_grade,
				'data' => $data,
				'total_maximum_marks' => $total_maximum_marks,
				'total_obtained_marks' => $total_obtained_marks,
				'percentage_value' => esc_html($total_percentage),
				'percentage_text' => esc_html(WLSM_Config::get_percentage_text($total_maximum_marks, $total_obtained_marks)),
			);

			if ($show_marks_grades && $enable_overall_grade) {
				$response_data['result']['overall_grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $total_percentage));
			}

			$response_data['result']['student_rank'] = esc_html($student_rank);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	public static function no_account()
	{
		return new WP_Error(
			'sm_no_account',
			esc_html__('There is no student or parent account.', 'school-management'),
			array(
				'status' => 403,
			)
		);
	}

	// Parent - Dashboard.
	public static function parent_dashboard($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$students = WLSM_M_Parent::fetch_students($unique_student_ids);

			$response_data = array();
			$data = array();

			if (count($students)) {
				foreach ($students as $key => $student) {
					$school_id = isset($student->school_id) ? $student->school_id : 0;

					$settings_general = WLSM_M_Setting::get_settings_general($school_id);
					if (wp_get_attachment_url($settings_general['school_logo'])) {
						$school_logo = wp_get_attachment_url($settings_general['school_logo']);
					} else {
						$school_logo = "";
					}

					$data[] = array(
						'id' => $student->ID,
						'name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)),
						'school' => esc_html(WLSM_M_School::get_label_text($student->school_name)),
						'session' => esc_html(WLSM_M_Session::get_label_text($student->session_label)),
						'class' => esc_html(WLSM_M_Class::get_label_text($student->class_label)),
						'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($student->section_label)),
						'admission_number' => esc_html(WLSM_M_Staff_Class::get_admission_no_text($student->admission_number)),
						'enrollment_number' => esc_html($student->enrollment_number),
						'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)),
						'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->father_name)),
						'father_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($student->father_phone)),
						'father_occupation' => esc_html(WLSM_M_Staff_Class::get_label_text($student->father_occupation)),
						'mother_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->mother_name)),
						'mother_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($student->mother_phone)),
						'mother_occupation' => esc_html(WLSM_M_Staff_Class::get_label_text($student->mother_occupation)),
						'school_name' => esc_html(WLSM_M_School::get_label_text($student->school_name)),
						'school_phone' => esc_html(WLSM_M_School::get_phone_text($student->school_phone)),
						'school_email' => esc_html(WLSM_M_School::get_email_text($student->school_email)),
						'school_address' => esc_html(WLSM_M_School::get_address_text($student->school_address)),
						'school_description' => esc_html(WLSM_M_School::get_description_text($student->school_description)),
						'school_logo' => esc_url($school_logo),
					);
				}
			}

			$success = true;
			$message = esc_html__('Students retrieved successfully.', 'school-management');

			$response_data = $data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Student and Attendance.
	public static function parent_student_and_attendance($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$class_school_id = $student->class_school_id;

			$response_data['student'] = array(
				'student_name' => $student->student_name,
				'father_name' => $student->father_name,
				'mother_name' => $student->mother_name,
				'class_label' => $student->class_label,
				'section_label' => $student->section_label,
				'school_name' => $student->school_name,
				'enrollment_number' => $student->enrollment_number,
				'admission_number' => $student->admission_number,
				'roll_number' => $student->roll_number
			);

			$attendance = WLSM_M_Parent::fetch_student_attendance($student->ID);

			$success = true;
			$message = esc_html__('Student and attendance retrieved successfully.', 'school-management');

			$response_data['attendance'] = array(
				'total_attendance' => $attendance->total_attendance,
				'total_present' => $attendance->total_present,
				'total_absent' => $attendance->total_absent,
				'total_holiday' => $attendance->total_holiday,
				'total_late' => $attendance->total_late
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Noticeboard Data.
	public static function parent_noticeboard_data($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = $student->school_id;
			$class_school_id = $student->class_school_id;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$notices_query = WLSM_M::notices_query();

			// Total.
			$notices_total = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(1) FROM ({$notices_query}) AS combined_table",
				$class_school_id,
				$class_school_id,
				$school_id
			));

			// Current page and per page.
			$notices_page = isset($params['notices_page']) ? absint($params['notices_page']) : 1;
			$notices_per_page = isset($params['notices_per_page']) ? absint($params['notices_per_page']) : WLSM_M::notices_per_page();

			// Page offset.
			$notices_page_offset = ($notices_page * $notices_per_page) - $notices_per_page;

			// Paginated data.
			$notices = $wpdb->get_results($wpdb->prepare(
				$notices_query . ' ORDER BY n.ID DESC LIMIT %d, %d',
				$notices_page_offset,
				$notices_per_page
			));

			// Paginated response.
			$notices_data = array();
			$notices_pagination = array(
				'current_page' => $notices_page,
				'per_page' => $notices_per_page,
			);

			// Filter by class only.
			$filtered_notices = [];
			foreach ($notices as $notice) {
				$notice_data = @unserialize($notice->notice_data);
				if ($notice_data === false) {
					$notice_data = array(
						'classes' => array('all')
					);
				}

				$notice_data['classes'] = isset($notice_data['classes']) ? (array) $notice_data['classes'] : array('all');

				// Class match check
				if (in_array($student->class_id, $notice_data['classes'], true) || in_array('all', $notice_data['classes'], true)) {
					$filtered_notices[] = $notice;
				}
			}

			// Format response.
			if (count($filtered_notices)) {
				$today = new DateTime();
				$today->setTime(0, 0, 0);

				foreach ($filtered_notices as $notice) {
					$link_to = $notice->link_to;
					$link = '#';

					if ('url' === $link_to) {
						if (!empty($notice->url)) {
							$link = $notice->url;
						}
					} else if ('attachment' === $link_to) {
						if (!empty($notice->attachment)) {
							$attachment = $notice->attachment;
							$link = wp_get_attachment_url($attachment);
						}
					}

					$notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $notice->created_at);
					$notice_date->setTime(0, 0, 0);

					$interval = $today->diff($notice_date);

					$notices_data[] = array(
						'id' => $notice->ID,
						'title' => esc_html(stripslashes($notice->title)),
						'description' => esc_html(stripslashes($notice->description)),
						'link' => esc_url($link),
						'date' => esc_html(WLSM_Config::get_date_text($notice->created_at)),
						'is_new' => ($interval->days < 7) ? true : false
					);
				}

				$notices_pagination['total_pages'] = ceil($notices_total / $notices_per_page);
				$notices_pagination['total_records'] = $notices_total;
			}

			$success = true;
			$message = esc_html__('Noticeboard retrieved successfully.', 'school-management');

			$response_data['noticeboard'] = array(
				'new_notice_icon' => esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'),
				'noticeboard_data' => $notices_data,
				'pagination' => $notices_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Class time table data.
	public static function parent_class_time_table_data($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$section_id = $student->section_id;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$section = WLSM_M_Staff_Class::get_school_section($school_id, $student->section_id);

			if (!$section) {
				die;
			}

			$class_label = $section->class_label;
			$section_label = $section->label;

			$data = array();

			foreach (WLSM_Helper::days_list() as $key => $day) {
				$routines = WLSM_M_Staff_Class::get_section_routines_by_day($school_id, $section_id, $key);

				$day_routine = array(
					'day' => $day
				);

				$routines_data = array();
				foreach ($routines as $routine) {
					$routine_data = array();
					$routine_data['subject'] = sprintf(
						wp_kses(
							/* translators: 1: subject label, 2: subject code */
							_x('%1$s (%2$s)', 'Subject', 'school-management'),
							array('span' => array('class' => array()))
						),
						esc_html(WLSM_M_Staff_Class::get_subject_label_text($routine->subject_label)),
						esc_html($routine->subject_code)
					);

					$routine_data['start_time'] = esc_html(WLSM_Config::get_time_text($routine->start_time));
					$routine_data['end_time'] = esc_html(WLSM_Config::get_time_text($routine->end_time));

					$routine_data['room'] = esc_html($routine->room_number);

					if ($routine->teacher_name) {
						$routine_data['teacher'] = esc_html(WLSM_M_Staff_Class::get_name_text($routine->teacher_name));
					}

					array_push($routines_data, $routine_data);
				}

				$day_routine['routines'] = $routines_data;

				array_push($data, $day_routine);
			}

			$success = true;
			$message = esc_html__('Class time table retrieved successfully.', 'school-management');

			$response_data['class_time_table'] = array(
				'class' => esc_html(WLSM_M_Class::get_label_text($class_label)),
				'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($section_label)),
				'class_time_table_data' => $data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Events.
	public static function parent_events($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;

			// Query.
			$events_query = WLSM_M::events_query();

			// Total.
			$events_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$events_query}) AS combined_table", $student->ID, $school_id));

			// Current page and per page.
			$events_page = isset($params['events_page']) ? absint($params['events_page']) : 1;
			$events_per_page = isset($params['events_per_page']) ? absint($params['events_per_page']) : WLSM_M::events_per_page();

			// Page offset.
			$events_page_offset = ($events_page * $events_per_page) - $events_per_page;

			// Paginated data.
			$events = $wpdb->get_results($wpdb->prepare($events_query . ' ORDER BY ev.ID DESC LIMIT %d, %d', $student->ID, $school_id, $events_page_offset, $events_per_page));

			// Paginated response.
			$events_data = array();
			$events_pagination = array(
				'current_page' => $events_page,
				'per_page' => $events_per_page,
			);

			// Format response.
			if (count($events)) {
				foreach ($events as $key => $event) {
					$events_data[] = array(
						'id' => $event->ID,
						'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
						'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
						'image' => esc_url(wp_get_attachment_url($event->image_id)),
						'description' => wp_kses_post(stripslashes($event->description)),
						'has_joined' => $event->student_joined ? true : false
					);
				}

				$events_pagination['total_pages'] = ceil($events_total / $events_per_page);
				$events_pagination['total_records'] = $events_total;
			}

			$success = true;
			$message = esc_html__('Events retrieved successfully.', 'school-management');

			$response_data['events'] = array(
				'events_data' => $events_data,
				'pagination' => $events_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Event.
	public static function parent_event($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$event_id = isset($request['event_id']) ? absint($request['event_id']) : 0;

			$event = WLSM_M_Staff_Class::fetch_active_event($school_id, $event_id, $student_id);

			if (!$event) {
				throw new Exception(esc_html__('Event not found.', 'school-management'));
			}

			$success = true;
			$message = esc_html__('Event details retrieved successfully.', 'school-management');

			$response_data['event'] = array(
				'id' => $event->ID,
				'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
				'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
				'image' => esc_url(wp_get_attachment_url($event->image_id)),
				'description' => wp_kses_post(stripslashes($event->description)),
				'has_joined' => $event->student_joined ? true : false
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Join event.
	public static function parent_join_event($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

			$event = WLSM_M_Staff_Class::fetch_active_event($school_id, $event_id, $student_id);

			if (!$event) {
				throw new Exception(esc_html__('Event not found.', 'school-management'));
			}

			if ($event->student_joined) {
				throw new Exception(esc_html__('You have already joined.', 'school-management'));
			}

			// Event participant data.
			$data = array(
				'student_record_id' => $student_id,
				'event_id' => $event_id,
			);

			$data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_EVENT_RESPONSES, $data);

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('You have joined the event successfully.', 'school-management');

			$response_data['event'] = array(
				'id' => $event->ID,
				'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
				'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Unjoin event.
	public static function parent_unjoin_event($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = $student->ID;
			$school_id = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			$event_id = isset($params['event_id']) ? absint($params['event_id']) : 0;

			$event = WLSM_M_Staff_Class::fetch_active_event($school_id, $event_id, $student_id);

			if (!$event) {
				throw new Exception(esc_html__('Event not found.', 'school-management'));
			}

			if (!$event->student_joined) {
				throw new Exception(esc_html__('You have not joined this event.', 'school-management'));
			}

			$event_response_id = $event->event_response_id;

			$success = $wpdb->delete(WLSM_EVENT_RESPONSES, array('ID' => $event_response_id));

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('You have left from this event.', 'school-management');

			$response_data['event'] = array(
				'id' => $event->ID,
				'title' => esc_html(WLSM_M_Staff_Class::get_name_text($event->title)),
				'event_date' => esc_html(WLSM_Config::get_date_text($event->event_date)),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Exams time table.
	public static function parent_exams_time_table($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			$start_date = isset($student->start_date) ? absint($student->start_date) : 0;
			$end_date = isset($student->end_date) ? absint($student->end_date) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$exams = WLSM_M_Staff_Examination::get_class_school_exams_time_table($school_id, $class_school_id, $start_date, $end_date);

			$exams_data = array();

			if (count($exams)) {
				foreach ($exams as $key => $exam) {
					$exams_data[] = array(
						'id' => $exam->ID,
						'title' => esc_html(stripslashes($exam->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($exam->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($exam->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Exams retrieved successfully.', 'school-management');

			$response_data['exams_data'] = $exams_data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Exam time table.
	public static function parent_exam_time_table($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$exam_id = isset($params['exam_id']) ? absint($params['exam_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;
			$section_id = isset($student->section_id) ? absint($student->section_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$exam = WLSM_M_Staff_Examination::get_class_school_exam_time_table($school_id, $class_school_id, $exam_id);

			if (!$exam) {
				throw new Exception(esc_html__('Exam not found.', 'school-management'));
			}

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label($school_id, $exam_id);
			$exam_papers = WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);

			$exam_title = $exam->exam_title;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$class_names = array();
			foreach ($exam_classes as $exam_class) {
				array_push($class_names, WLSM_M_Class::get_label_text($exam_class->label));
			}

			$class_names = implode(', ', $class_names);

			$data = array();

			foreach ($exam_papers as $key => $exam_paper) {
				$exam_data = array();

				$exam_data['subject'] = esc_html(stripcslashes($exam_paper->subject_label));
				$exam_data['paper_code'] = esc_html($exam_paper->paper_code);
				$exam_data['paper_date'] = esc_html(WLSM_Config::get_date_text($exam_paper->paper_date));
				$exam_data['start_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->start_time));
				$exam_data['end_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->end_time));

				if ($exam->enable_room_numbers) {
					$exam_data['room_number'] = esc_html($exam_paper->room_number);
				}

				array_push($data, $exam_data);
			}

			$success = true;
			$message = esc_html__('Exam time table retrieved successfully.', 'school-management');

			$response_data = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'class_label' => esc_html($class_names),
				'show_room_number' => (bool) $exam->enable_room_numbers,
				'exam_data' => $data,
				'exam_center' => esc_html(WLSM_M_Staff_Examination::get_exam_center_text($exam->exam_center))
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Admit cards.
	public static function parent_admit_cards($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$admit_cards = WLSM_M_Staff_Examination::get_student_admit_cards($school_id, $student->ID);

			$admit_cards_data = array();

			if (count($admit_cards)) {
				foreach ($admit_cards as $key => $admit_card) {
					$admit_cards_data[] = array(
						'id' => $admit_card->ID,
						'exam_title' => esc_html(stripslashes($admit_card->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($admit_card->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($admit_card->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Admit cards retrieved successfully.', 'school-management');

			$response_data['admit_cards_data'] = $admit_cards_data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Admit card.
	public static function parent_admit_card($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$admit_card_id = isset($params['admit_card_id']) ? absint($params['admit_card_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Checks if admit card exists.
			$admit_card = WLSM_M_Staff_Examination::fetch_student_admit_card($school_id, $student_id, $admit_card_id);

			if (!$admit_card) {
				throw new Exception(esc_html__('Admit card not found.', 'school-management'));
			}

			$exam_id = $admit_card->exam_id;

			// Checks if exam exists.
			$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);

			if (!$exam) {
				throw new Exception(esc_html__('Exam not found.', 'school-management'));
			}

			$exam_id = $exam->ID;
			$exam_title = $exam->exam_title;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label($school_id, $exam_id);
			$exam_papers = WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);

			$class_names = array();
			foreach ($exam_classes as $exam_class) {
				array_push($class_names, WLSM_M_Class::get_label_text($exam_class->label));
			}

			$class_names = implode(', ', $class_names);

			$photo_id = $admit_card->photo_id;

			$data = array();

			foreach ($exam_papers as $key => $exam_paper) {
				$exam_data = array();

				$exam_data['subject'] = esc_html(stripcslashes($exam_paper->subject_label));
				$exam_data['paper_code'] = esc_html($exam_paper->paper_code);
				$exam_data['paper_date'] = esc_html(WLSM_Config::get_date_text($exam_paper->paper_date));
				$exam_data['start_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->start_time));
				$exam_data['end_time'] = esc_html(WLSM_Config::get_time_text($exam_paper->end_time));

				if ($exam->enable_room_numbers) {
					$exam_data['room_number'] = esc_html($exam_paper->room_number);
				}

				array_push($data, $exam_data);
			}

			$success = true;
			$message = esc_html__('Admit card retrieved successfully.', 'school-management');

			$response_data = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'class_label' => esc_html($class_names),
				'show_room_number' => (bool) $exam->enable_room_numbers,
				'exam_data' => $data,
				'exam_center' => esc_html(WLSM_M_Staff_Examination::get_exam_center_text($exam->exam_center))
			);

			$response_data['admit_card'] = array(
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->name)),
				'enrollment_number' => esc_html($admit_card->enrollment_number),
				'session_label' => esc_html(WLSM_M_Session::get_label_text($admit_card->session_label)),
				'class_label' => esc_html(WLSM_M_Class::get_label_text($admit_card->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($admit_card->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number)),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($admit_card->phone)),
				'email' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->email)),
				'photo' => esc_url(wp_get_attachment_url($photo_id))
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Exams results.
	public static function exam_results_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$exam_results = WLSM_M_Staff_Examination::get_student_published_exam_results($school_id, $student_id);

			$results_data = array();

			if (count($exam_results)) {
				foreach ($exam_results as $key => $value) {
					$results_data[] = array(
						'id' => $value->ID,
						'title' => esc_html(stripslashes($value->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($value->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($value->end_date))
					);
				}
			}

			$success = true;
			$message = esc_html__('Exam results retrieved successfully.', 'school-management');

			$response_data['results_data'] = $results_data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Exam result.
	public static function exam_result_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$admit_card_id = isset($params['admit_card_id']) ? absint($params['admit_card_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Checks if admit card exists for published exam result.
			$admit_card = WLSM_M_Staff_Examination::get_student_published_exam_result($school_id, $student->ID, $admit_card_id);

			if (!$admit_card) {
				throw new Exception(esc_html__('Exam result not found.', 'school-management'));
			}

			$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $admit_card->exam_id);

			$exam_id = $exam->ID;
			$exam_title = $exam->exam_title;
			$exam_center = $exam->exam_center;
			$start_date = $exam->start_date;
			$end_date = $exam->end_date;

			$exam_papers = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card($school_id, $admit_card_id);
			$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id, $admit_card_id);

			$grade_criteria = WLSM_Config::sanitize_grade_criteria($exam->grade_criteria);

			$enable_overall_grade = $grade_criteria['enable_overall_grade'];
			$marks_grades = $grade_criteria['marks_grades'];

			$show_marks_grades = count($marks_grades);

			$student_rank = WLSM_M_Staff_Examination::calculate_exam_ranks($school_id, $exam_id, array(), $admit_card->ID);

			$data = array();

			$total_maximum_marks = 0;
			$total_obtained_marks = 0;

			foreach ($exam_papers as $key => $exam_paper) {
				$results_data = array();

				if ($admit_card && isset($exam_results[$exam_paper->ID])) {
					$exam_result = $exam_results[$exam_paper->ID];
					$obtained_marks = $exam_result->obtained_marks;
				} else {
					$obtained_marks = '';
				}

				$percentage = WLSM_Config::sanitize_percentage($exam_paper->maximum_marks, WLSM_Config::sanitize_marks($obtained_marks));

				$total_maximum_marks += $exam_paper->maximum_marks;
				$total_obtained_marks += WLSM_Config::sanitize_marks($obtained_marks);

				$results_data['paper_code'] = esc_html($exam_paper->paper_code);
				$results_data['subject_name'] = esc_html(stripcslashes($exam_paper->subject_label));
				$results_data['subject_type'] = esc_html(WLSM_Helper::get_subject_type_text($exam_paper->subject_type));
				$results_data['maximum_marks'] = esc_html($exam_paper->maximum_marks);
				$results_data['obtained_marks'] = esc_html($obtained_marks);

				if ($show_marks_grades) {
					$results_data['grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $percentage));
				}

				array_push($data, $results_data);
			}

			$total_percentage = WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks);

			$success = true;
			$message = esc_html__('Exam result retrieved successfully.', 'school-management');

			$response_data['result'] = array(
				'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
				'start_date' => esc_html(WLSM_Config::get_date_text($start_date)),
				'end_date' => esc_html(WLSM_Config::get_date_text($end_date)),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($admit_card->name)),
				'enrollment_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->enrollment_number)),
				'session' => esc_html(WLSM_M_Session::get_label_text($admit_card->session_label)),
				'class_label' => esc_html(WLSM_M_Class::get_label_text($admit_card->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($admit_card->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number)),
				'show_marks_grades' => (bool) $show_marks_grades,
				'show_overall_grade' => (bool) $enable_overall_grade,
				'result_data' => $data,
				'total_maximum_marks' => $total_maximum_marks,
				'total_obtained_marks' => $total_obtained_marks,
				'percentage_value' => esc_html($total_percentage),
				'percentage_text' => esc_html(WLSM_Config::get_percentage_text($total_maximum_marks, $total_obtained_marks)),
			);

			if ($show_marks_grades && $enable_overall_grade) {
				$response_data['result']['overall_grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $total_percentage));
			}

			$response_data['result']['student_rank'] = esc_html($student_rank);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Overall result.
	public static function parent_overall_result($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);
			$session_label = isset($student->session_label) ? $student->session_label : '';

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$admit_cards = WLSM_M_Staff_Examination::get_student_exam_results_assessment($school_id, $student_id);

			$results_data = array();

			$overall_maximum_marks = 0;
			$overall_obtained_marks = 0;
			foreach ($admit_cards as $admit_card) {
				$exam_id = $admit_card->exam_id;
				$exam_title = $admit_card->exam_title;
				$start_date = $admit_card->start_date;
				$end_date = $admit_card->end_date;
				$admit_card_id = $admit_card->ID;

				$exam_papers = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card($school_id, $admit_card_id);
				$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id, $admit_card_id);

				$total_maximum_marks = 0;
				$total_obtained_marks = 0;

				foreach ($exam_papers as $key => $exam_paper) {
					if ($admit_card && isset($exam_results[$exam_paper->ID])) {
						$exam_result = $exam_results[$exam_paper->ID];
						$obtained_marks = $exam_result->obtained_marks;
					} else {
						$obtained_marks = '';
					}

					$percentage = WLSM_Config::sanitize_percentage($exam_paper->maximum_marks, WLSM_Config::sanitize_marks($obtained_marks));

					$total_maximum_marks += $exam_paper->maximum_marks;
					$total_obtained_marks += WLSM_Config::sanitize_marks($obtained_marks);
				}

				$total_percentage = WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks);

				$overall_maximum_marks += $total_maximum_marks;
				$overall_obtained_marks += WLSM_Config::sanitize_marks($total_obtained_marks);

				$results_data[] = array(
					'id' => $admit_card->ID,
					'title' => esc_html(WLSM_M_Staff_Examination::get_exam_label_text($exam_title)),
					'exam_date' => esc_html(WLSM_Config::get_date_text($start_date)),
					'maximum_marks' => esc_html($total_maximum_marks),
					'obtained_marks' => esc_html($total_obtained_marks),
					'percentage_value' => esc_html(WLSM_Config::sanitize_percentage($total_maximum_marks, $total_obtained_marks)),
					'percentage_text' => esc_html(WLSM_Config::get_percentage_text($total_maximum_marks, $total_obtained_marks)),
				);
			}

			$success = true;
			$message = esc_html__('Overall result retrieved successfully.', 'school-management');

			$response_data['result'] = array(
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)),
				'enrollment_number' => esc_html($student->enrollment_number),
				'session' => esc_html(WLSM_M_Session::get_label_text($session_label)),
				'class_label' => esc_html(WLSM_M_Class::get_label_text($student->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($student->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($student->phone)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($student->father_name)),
				'father_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($student->father_phone)),
				'result_data' => $results_data,
				'overall_maximum_marks' => esc_html($overall_maximum_marks),
				'overall_obtained_marks' => esc_html($overall_obtained_marks),
				'overall_percentage_value' => esc_html(WLSM_Config::sanitize_percentage($overall_maximum_marks, $overall_obtained_marks)),
				'overall_percentage_text' => esc_html(WLSM_Config::get_percentage_text($overall_maximum_marks, $overall_obtained_marks)),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Attendance.
	public static function attendance_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;

			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;
			$section_id = isset($student->section_id) ? absint($student->section_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$attendance = WLSM_M_Staff_General::get_student_attendance_report($student_id);

			$data = array();

			$total_attendance = 0;
			$total_present = 0;
			$total_absent = 0;

			$attendance_monthly = array();
			foreach ($attendance as $monthly) {
				$month = new DateTime();
				$month->setDate($monthly->year, $monthly->month, 1);
				$total_attendance += $monthly->total_attendance;
				$total_present += $monthly->total_present;
				$total_absent += $monthly->total_absent;

				$attendance_data = array(
					'month' => esc_html($month->format('F Y')),
					'total_attendance' => esc_html($monthly->total_attendance),
					'total_present' => esc_html($monthly->total_present),
					'total_absent' => esc_html($monthly->total_absent),
				);

				array_push($attendance_monthly, $attendance_data);
			}

			$data['overall'] = array(
				'total_attendance' => $total_attendance,
				'total_present' => $total_present,
				'total_absent' => $total_absent,
				'percentage_value' => WLSM_Config::sanitize_percentage($total_attendance, $total_present, 1),
				'percentage_text' => WLSM_Config::get_percentage_text($total_attendance, $total_present, 1)
			);

			$data['monthly'] = $attendance_monthly;

			$success = true;
			$message = esc_html__('Attendance retrieved successfully.', 'school-management');

			$response_data['attendance'] = $data;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Books issued.
	public static function parent_books_issued($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$books_issued_query = WLSM_M::books_issued_query();

			// Total.
			$books_issued_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$books_issued_query}) AS combined_table", $school_id, $session_id, $student->ID));

			// Current page and per page.
			$books_issued_page = isset($params['books_issued_page']) ? absint($params['books_issued_page']) : 1;
			$books_issued_per_page = isset($params['books_issued_per_page']) ? absint($params['books_issued_per_page']) : WLSM_M::books_issued_per_page();

			// Page offset.
			$books_issued_page_offset = ($books_issued_page * $books_issued_per_page) - $books_issued_per_page;

			// Paginated data.
			$books_issued = $wpdb->get_results($wpdb->prepare($books_issued_query . ' ORDER BY bki.date_issued DESC LIMIT %d, %d', $school_id, $session_id, $student->ID, $books_issued_page_offset, $books_issued_per_page));

			// Paginated response.
			$books_issued_data = array();
			$books_issued_pagination = array(
				'current_page' => $books_issued_page,
				'per_page' => $books_issued_per_page,
			);

			// Format response.
			if (count($books_issued)) {
				foreach ($books_issued as $key => $row) {
					$books_issued_data[] = array(
						'id' => $row->ID,
						'book_title' => esc_html(WLSM_M_Staff_Library::get_book_title($row->title)),
						'issued_quantity' => esc_html(WLSM_M_Staff_Library::get_book_quantity($row->issued_quantity)),
						'date_issued' => esc_html(WLSM_Config::get_date_text($row->date_issued)),
						'return_date' => esc_html(WLSM_Config::get_date_text($row->return_date)),
						'return_status' => strip_tags(WLSM_M_Staff_Library::get_book_issued_status_text($row->returned_at)),
						'returned_at' => esc_html(WLSM_Config::get_date_text($row->returned_at)),
						'author' => esc_html(WLSM_M_Staff_Library::get_book_author($row->author)),
						'subject' => esc_html(WLSM_M_Staff_Library::get_book_subject($row->subject)),
						'rack_number' => esc_html(WLSM_M_Staff_Library::get_book_rack_number($row->rack_number)),
						'book_number' => esc_html(WLSM_M_Staff_Library::get_book_number($row->book_number)),
						'isbn_number' => esc_html(WLSM_M_Staff_Library::get_book_isbn_number($row->isbn_number)),
					);
				}

				$books_issued_pagination['total_pages'] = ceil($books_issued_total / $books_issued_per_page);
				$books_issued_pagination['total_records'] = $books_issued_total;
			}

			$success = true;
			$message = esc_html__('Books issued retrieved successfully.', 'school-management');

			$response_data = array(
				'books_issued_data' => $books_issued_data,
				'pagination' => $books_issued_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Book Issued.
	public static function parent_book_issued($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();

			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$book_id = isset($params['book_id']) ? absint($params['book_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			$book = WLSM_M_Staff_Library::get_book($school_id, $book_id);

			if (!$book) {
				throw new Exception(esc_html__('Book not found.', 'school-management'));
			}

			$book_issued = WLSM_M::get_book_issued($school_id, $session_id, $book_id);

			// Format response.
			if ($book_issued) {
				$book_issued = array(
					'id' => $book_issued->ID,
					'book_title' => isset($book_issued->title) ? esc_html(WLSM_M_Staff_Library::get_book_title($book_issued->title)) : '',
					'issued_quantity' => isset($book_issued->issued_quantity) ? esc_html(WLSM_M_Staff_Library::get_book_quantity($book_issued->issued_quantity)) : '',
					'date_issued' => isset($book_issued->date_issued) ? esc_html(WLSM_Config::get_date_text($book_issued->date_issued)) : '',
					'return_date' => isset($book_issued->return_date) ? esc_html(WLSM_Config::get_date_text($book_issued->return_date)) : '',
					'return_status' => isset($book_issued->returned_at) ? strip_tags(WLSM_M_Staff_Library::get_book_issued_status_text($book_issued->returned_at)) : '',
					'returned_at' => isset($book_issued->returned_at) ? esc_html(WLSM_Config::get_date_text($book_issued->returned_at)) : '',
					'author' => isset($book_issued->author) ? esc_html(WLSM_M_Staff_Library::get_book_author($book_issued->author)) : '',
					'subject' => isset($book_issued->subject) ? esc_html(WLSM_M_Staff_Library::get_book_subject($book_issued->subject)) : '',
					'rack_number' => isset($book_issued->rack_number) ? esc_html(WLSM_M_Staff_Library::get_book_rack_number($book_issued->rack_number)) : '',
					'book_number' => isset($book_issued->book_number) ? esc_html(WLSM_M_Staff_Library::get_book_number($book_issued->book_number)) : '',
					'isbn_number' => isset($book_issued->isbn_number) ? esc_html(WLSM_M_Staff_Library::get_book_isbn_number($book_issued->isbn_number)) : '',
				);
			}

			$success = true;
			$message = esc_html__('Book issued retrieved successfully.', 'school-management');

			$response_data['book_issued'] = $book_issued;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Live classes.
	public static function parent_live_classes($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$meetings_query = WLSM_M::meetings_query();

			// Total.
			$meetings_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$meetings_query}) AS combined_table", $school_id, $class_school_id));

			// Current page and per page.
			$meetings_page = isset($params['live_classes_page']) ? absint($params['live_classes_page']) : 1;
			$meetings_per_page = isset($params['live_classes_per_page']) ? absint($params['live_classes_per_page']) : WLSM_M::meetings_per_page();

			// Page offset.
			$meetings_page_offset = ($meetings_page * $meetings_per_page) - $meetings_per_page;

			// Paginated data.
			$meetings = $wpdb->get_results($wpdb->prepare($meetings_query . ' ORDER BY mt.start_at DESC, mt.ID DESC LIMIT %d, %d', $school_id, $class_school_id, $meetings_page_offset, $meetings_per_page));

			// Paginated response.
			$meetings_data = array();
			$meetings_pagination = array(
				'current_page' => $meetings_page,
				'per_page' => $meetings_per_page,
			);

			// Format response.
			if (count($meetings)) {
				foreach ($meetings as $key => $row) {

					$sdk_key = get_the_author_meta('sdk_key', $row->user_id);
					$sdk_secret = get_the_author_meta('sdk_secret', $row->user_id);
					$api_key = get_the_author_meta('api_key', $row->user_id);
					$api_secret = get_the_author_meta('api_secret', $row->user_id);

					$meetings_data[] = array(
						'id' => $row->ID,
						'meeting_id' => $row->meeting_id,
						'topic' => esc_html($row->topic),
						'duration' => esc_html($row->duration),
						'start_date_time' => esc_html(WLSM_Config::get_at_text($row->start_at)),
						'type' => esc_html(WLSM_Helper::get_meeting_type($row->type)),
						'join_url' => esc_url($row->join_url),
						'password' => esc_html($row->password),
						'subject' => esc_html($row->subject_name),
						'teacher' => esc_html($row->name),
						'sdk_key' => esc_html($sdk_key),
						'sdk_secret' => esc_html($sdk_secret),
						'api_key' => esc_html($api_key),
						'api_secret' => esc_html($api_secret),
					);
				}

				$meetings_pagination['total_pages'] = ceil($meetings_total / $meetings_per_page);
				$meetings_pagination['total_records'] = $meetings_total;
			}

			$success = true;
			$message = esc_html__('Live classes retrieved successfully.', 'school-management');

			$response_data = array(
				'live_classes_data' => $meetings_data,
				'pagination' => $meetings_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Leave requests.
	public static function parent_leave_requests($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$leaves_query = WLSM_M::leaves_query();

			// Total.
			$leaves_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$leaves_query}) AS combined_table", $school_id, $session_id, $student_id));

			// Current page and per page.
			$leaves_page = isset($params['leaves_page']) ? absint($params['leaves_page']) : 1;
			$leaves_per_page = isset($params['leaves_per_page']) ? absint($params['leaves_per_page']) : WLSM_M::leaves_per_page();

			// Page offset.
			$leaves_page_offset = ($leaves_page * $leaves_per_page) - $leaves_per_page;

			// Paginated data.
			$leaves = $wpdb->get_results($wpdb->prepare($leaves_query . ' ORDER BY lv.ID DESC LIMIT %d, %d', $school_id, $session_id, $student_id, $leaves_page_offset, $leaves_per_page));

			// Paginated response.
			$leaves_data = array();
			$leaves_pagination = array(
				'current_page' => $leaves_page,
				'per_page' => $leaves_per_page,
			);

			// Format response.
			if (count($leaves)) {
				foreach ($leaves as $key => $leave) {
					if ($leave->end_date) {
						$leave_date = sprintf(
							/* translators: 1: leave start date, 2: leave end date */
							esc_html__('%1$s to %2$s', 'school-management'),
							esc_html(WLSM_Config::get_date_text($leave->start_date)),
							esc_html(WLSM_Config::get_date_text($leave->end_date))
						);
					} else {
						$leave_date = esc_html(WLSM_Config::get_date_text($leave->start_date));
					}

					$leaves_data[] = array(
						'id' => $leave->ID,
						'reason' => esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($leave->description))),
						'leave_date' => $leave_date,
						'approval' => esc_html(WLSM_M_Staff_Class::get_leave_approval_text($leave->is_approved))
					);
				}

				$leaves_pagination['total_pages'] = ceil($leaves_total / $leaves_per_page);
				$leaves_pagination['total_records'] = $leaves_total;
			}

			$success = true;
			$message = esc_html__('Leave requests retrieved successfully.', 'school-management');

			$response_data = array(
				'leaves_data' => $leaves_data,
				'pagination' => $leaves_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Submit leave request.
	public static function parent_submit_leave_request($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$description = isset($params['reason']) ? sanitize_text_field($params['reason']) : '';
			$start_date = isset($params['start_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['start_date'])) : NULL;
			$end_date = isset($params['end_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['end_date'])) : NULL;
			$multiple_days = isset($params['is_multiple_days']) ? (bool) $params['is_multiple_days'] : 0;

			if ($multiple_days) {
				if ($start_date >= $end_date) {
					throw new Exception(esc_html__('Start date must be lower than end date.', 'school-management'));
				}
			}

			if (empty($description)) {
				throw new Exception(esc_html__('Please specify reason.', 'school-management'));
			}

			if (empty($start_date)) {
				if ($multiple_days) {
					throw new Exception(esc_html__('Please specify leave start date.', 'school-management'));
				} else {
					throw new Exception(esc_html__('Please specify leave date.', 'school-management'));
				}
			} else {
				$start_date = $start_date->format('Y-m-d');
			}

			if ($multiple_days) {
				if (empty($end_date)) {
					throw new Exception(esc_html__('Please specify leave end date.', 'school-management'));
				} else {
					$end_date = $end_date->format('Y-m-d');
				}
			} else {
				$end_date = NULL;
			}

			// Student leave data.
			$data = array(
				'student_record_id' => $student_id,
				'description' => $description,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'school_id' => $school_id,
			);

			$data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_LEAVES, $data);

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$leave_id = $wpdb->insert_id;

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Leave request submitted successfully.', 'school-management');

			if ($end_date) {
				$leave_date = sprintf(
					/* translators: 1: leave start date, 2: leave end date */
					esc_html__('%1$s to %2$s', 'school-management'),
					esc_html(WLSM_Config::get_date_text($start_date)),
					esc_html(WLSM_Config::get_date_text($end_date))
				);
			} else {
				$leave_date = esc_html(WLSM_Config::get_date_text($start_date));
			}

			$response_data['leave'] = array(
				'id' => $leave_id,
				'reason' => esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($description))),
				'leave_date' => esc_html($leave_date),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Study materials.
	public static function parent_study_materials($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$study_materials_query = WLSM_M::study_materials_query();

			// Total.
			$study_materials_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$study_materials_query}) AS combined_table", $class_school_id));

			// Current page and per page.
			$study_materials_page = isset($params['study_materials_page']) ? absint($params['study_materials_page']) : 1;
			$study_materials_per_page = isset($params['study_materials_per_page']) ? absint($params['study_materials_per_page']) : WLSM_M::study_materials_per_page();

			// Page offset.
			$study_materials_page_offset = ($study_materials_page * $study_materials_per_page) - $study_materials_per_page;

			// Paginated data.
			$study_materials = $wpdb->get_results($wpdb->prepare($study_materials_query . ' ORDER BY cssm.ID DESC LIMIT %d, %d', $class_school_id, $study_materials_page_offset, $study_materials_per_page));

			// Paginated response.
			$study_materials_data = array();
			$study_materials_pagination = array(
				'current_page' => $study_materials_page,
				'per_page' => $study_materials_per_page,
			);

			// Format response.
			if (count($study_materials)) {
				foreach ($study_materials as $key => $study_material) {
					$study_materials_data[] = array(
						'id' => $study_material->ID,
						'title' => esc_html(stripslashes($study_material->title)),
						'date' => esc_html(WLSM_Config::get_date_text($study_material->created_at)),
					);
				}

				$study_materials_pagination['total_pages'] = ceil($study_materials_total / $study_materials_per_page);
				$study_materials_pagination['total_records'] = $study_materials_total;
			}

			$success = true;
			$message = esc_html__('Study materials retrieved successfully.', 'school-management');

			$response_data = array(
				'study_materials_data' => $study_materials_data,
				'pagination' => $study_materials_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Study material.
	public static function parent_study_material($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$study_material_id = isset($params['study_material_id']) ? absint($params['study_material_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$study_material = $wpdb->get_row($wpdb->prepare(WLSM_M::study_material_query(), $class_school_id, $study_material_id));

			if (!$study_material) {
				throw new Exception(esc_html__('Study material not found.', 'school-management'));
			}

			$attachments = $study_material->attachments;
			if (is_serialized($attachments)) {
				$attachments = unserialize($attachments);
			} else {
				if (!is_array($attachments)) {
					$attachments = array();
				}
			}

			$attachments_data = array();
			if (count($attachments)) {
				foreach ($attachments as $attachment) {
					if (!empty($attachment)) {
						$file_name = basename(get_attached_file($attachment));
						array_push(
							$attachments_data,
							array(
								'file_name' => esc_html($file_name),
								'url' => esc_url(wp_get_attachment_url($attachment))
							)
						);
					}
				}
			}

			$success = true;
			$message = esc_html__('Study material retrieved successfully.', 'school-management');

			$response_data['study_material'] = array(
				'id' => $study_material->ID,
				'title' => esc_html(stripslashes($study_material->title)),
				'description' => esc_html(stripslashes($study_material->description)),
				'downloadable' => (intval($study_material->downloadable)),
				'date' => esc_html(WLSM_Config::get_date_text($study_material->created_at)),
				'url' => esc_url($study_material->url),
				'attachments' => $attachments_data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Fee Invoices.
	public static function fee_invoices_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$invoices = WLSM_M_Staff_Accountant::get_student_pending_invoices($student->ID);

			$invoices_data = array();

			if (count($invoices)) {
				foreach ($invoices as $row) {
					$due = $row->payable - $row->paid;
					$invoices_data[] = array(
						'id' => $row->ID,
						'invoice_number' => esc_html($row->invoice_number),
						'invoice_title' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($row->invoice_title)),
						'payable' => esc_html(WLSM_Config::sanitize_money($row->payable)),
						'payable_display' => esc_html(WLSM_Config::get_money_text($row->payable, $school_id)),
						'paid' => esc_html(WLSM_Config::sanitize_money($row->paid)),
						'paid_display' => esc_html(WLSM_Config::get_money_text($row->paid, $school_id)),
						'due' => esc_html(WLSM_Config::sanitize_money($due)),
						'due_display' => esc_html(WLSM_Config::get_money_text($due, $school_id)),
						'status' => esc_html($row->status),
						'status_text' => esc_html(WLSM_M_Invoice::get_status_text($row->status, false)),
						'show_pay_now' => WLSM_M_Invoice::get_paid_key() !== $row->status,
						'date_issued' => esc_html(WLSM_Config::get_date_text($row->date_issued)),
						'due_date' => esc_html(WLSM_Config::get_date_text($row->due_date)),
					);
				}
			}

			$success = true;
			$message = esc_html__('Fee invoices retrieved successfully.', 'school-management');

			$response_data = array(
				'invoices_data' => $invoices_data
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Fee Invoice.
	public static function fee_invoice_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$invoice_id = isset($params['invoice_id']) ? absint($params['invoice_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

			if (!$invoice) {
				throw new Exception(esc_html__('Invoice not found.', 'school-management'));
			}

			$due = $invoice->payable - $invoice->paid;

			$invoice_partial_payment = $invoice->partial_payment;

			$currency = WLSM_Config::currency($school_id);

			// Razorpay settings.
			$settings_razorpay = WLSM_M_Setting::get_settings_razorpay($school_id);
			$school_razorpay_enable = $settings_razorpay['enable'];

			// Stripe settings.
			$settings_stripe = WLSM_M_Setting::get_settings_stripe($school_id);
			$school_stripe_enable = $settings_stripe['enable'];

			// PayPal settings.
			$settings_paypal = WLSM_M_Setting::get_settings_paypal($school_id);
			$school_paypal_enable = $settings_paypal['enable'];

			// Pesapal settings.
			$settings_pesapal = WLSM_M_Setting::get_settings_pesapal($school_id);
			$school_pesapal_enable = $settings_pesapal['enable'];

			// Paystack settings.
			$settings_paystack = WLSM_M_Setting::get_settings_paystack($school_id);
			$school_paystack_enable = $settings_paystack['enable'];

			// Paytm settings.
			$settings_paytm = WLSM_M_Setting::get_settings_paytm($school_id);
			$school_paytm_enable = $settings_paytm['enable'];

			// bank settings.
			$settings_bank = WLSM_M_Setting::get_settings_bank_transfer($school_id);
			$school_bank_enable = $settings_bank['enable'];

			$settings_upi_transfer = WLSM_M_Setting::get_settings_upi_transfer($school_id);
			$school_upi_transfer_enable = $settings_upi_transfer['enable'];

			$success = true;
			$message = esc_html__('Fee invoice retrieved successfully.', 'school-management');

			$response_data['invoice'] = array(
				'id' => $invoice->ID,
				'invoice_number' => esc_html($invoice->invoice_number),
				'invoice_title' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
				'date_issued' => esc_html(WLSM_Config::get_date_text($invoice->date_issued)),
				'due_date' => esc_html(WLSM_Config::get_date_text($invoice->due_date)),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($invoice->student_name)),
				'enrollment_number' => esc_html($invoice->enrollment_number),
				'class_label' => esc_html(WLSM_M_Class::get_label_text($invoice->class_label)),
				'section' => esc_html(WLSM_M_Staff_Class::get_section_label_text($invoice->section_label)),
				'fees_due' => esc_html(WLSM_Config::sanitize_money($due)),
				'fees_due_display' => esc_html(WLSM_Config::get_money_text($due, $school_id)),
				'partial_payment_allowed' => (bool) $invoice_partial_payment,
				'currency' => $currency
			);

			$payment_methods = array();

			if (true) {
				$payment_methods['razorpay'] = esc_html(WLSM_M_Invoice::get_payment_method_text('razorpay'));

				$school_razorpay_key = $settings_razorpay['razorpay_key'];
				$school_razorpay_secret = $settings_razorpay['razorpay_secret'];

				$response_data['razorpay_api_data'] = array(
					'school_razorpay_enable' => $school_razorpay_enable,
					'school_razorpay_key' => $school_razorpay_key,
					'school_razorpay_secret' => $school_razorpay_secret
				);
			}

			if (true) {
				$payment_methods['stripe'] = esc_html(WLSM_M_Invoice::get_payment_method_text('stripe'));

				$school_stripe_publishable_key = $settings_stripe['publishable_key'];
				$school_stripe_secret_key = $settings_stripe['secret_key'];

				$response_data['stripe_api_data'] = array(
					'enable' => $school_stripe_enable,
					'publishable_key' => $school_stripe_publishable_key,
					'secret_key' => $school_stripe_secret_key
				);
			}

			if (true) {
				$payment_methods['paypal'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paypal'));

				$school_paypal_business_email = $settings_paypal['business_email'];
				$school_paypal_mode = $settings_paypal['mode'];
				$school_paypal_notify_url = $settings_paypal['notify_url'];

				$response_data['paypal_api_data'] = array(
					'school_paypal_enable' => $school_paypal_enable,
					'school_paypal_business_email' => $school_paypal_business_email,
					'school_paypal_mode' => $school_paypal_mode,
					'school_paypal_notify_url' => $school_paypal_notify_url
				);
			}

			if (true) {
				$payment_methods['pesapal'] = esc_html(WLSM_M_Invoice::get_payment_method_text('pesapal'));

				$school_pesapal_consumer_key = $settings_pesapal['consumer_key'];
				$school_pesapal_consumer_secret = $settings_pesapal['consumer_secret'];
				$school_pesapal_mode = $settings_pesapal['mode'];
				$school_pesapal_notify_url = $settings_pesapal['notify_url'];

				$response_data['pesapal_api_data'] = array(
					'school_pesapal_enable' => $school_pesapal_enable,
					'school_pesapal_consumer_key' => $school_pesapal_consumer_key,
					'school_pesapal_consumer_secret' => $school_pesapal_consumer_secret,
					'school_pesapal_notify_url' => $school_pesapal_notify_url,
					'school_pesapal_mode' => $school_pesapal_mode
				);
			}

			if (true) {
				$payment_methods['paystack'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paystack'));

				$school_paystack_public_key = $settings_paystack['paystack_public_key'];
				$school_paystack_secret_key = $settings_paystack['paystack_secret_key'];

				$response_data['paystack_api_data'] = array(
					'school_paystack_enable' => $school_paystack_enable,
					'school_paystack_public_key' => $school_paystack_public_key,
					'school_paystack_secret_key' => $school_paystack_secret_key
				);
			}

			if (true) {
				$payment_methods['paytm'] = esc_html(WLSM_M_Invoice::get_payment_method_text('paytm'));

				// Paytm settings.
				$school_paytm_merchant_id = $settings_paytm['merchant_id'];
				$school_paytm_merchant_key = $settings_paytm['merchant_key'];
				$school_paytm_industry_type_id = $settings_paytm['industry_type_id'];
				$school_paytm_website = $settings_paytm['website'];
				$school_paytm_mode = $settings_paytm['mode'];


				$response_data['paytm_api_data'] = array(
					'school_paytm_enable' => $school_paytm_enable,
					'school_paytm_merchant_id' => $school_paytm_merchant_id,
					'school_paytm_merchant_key' => $school_paytm_merchant_key,
					'school_paytm_industry_type_id' => $school_paytm_industry_type_id,
					'school_paytm_website' => $school_paytm_website,
					'school_paytm_mode' => $school_paytm_mode
				);
			}

			if (true) {
				$payment_methods['bank-transfer'] = esc_html(WLSM_M_Invoice::get_payment_method_text('bank-transfer'));

				$settings_bank = WLSM_M_Setting::get_settings_bank_transfer($school_id);
				$school_bank_enable = $settings_bank['enable'];
				$branch = $settings_bank['branch'];
				$account = $settings_bank['account'];
				$name = $settings_bank['name'];
				$message = $settings_bank['message'];


				$response_data['bank-tranfer-data'] = array(
					'enable' => $school_bank_enable,
					'branch' => $branch,
					'account' => $account,
					'name' => $name,
					'message' => $message,
				);
			}

			if (true) {
				$payment_methods['upi-transfer'] = esc_html(WLSM_M_Invoice::get_payment_method_text('upi-transfer'));

				$settings_upi_transfer = WLSM_M_Setting::get_settings_upi_transfer($school_id);
				$school_upi_transfer_enable = $settings_upi_transfer['enable'];
				$school_upi_transfer_qr_url = !empty($settings_upi_transfer['qr']) ? wp_get_attachment_url($settings_upi_transfer['qr']) : '';
				$school_upi_transfer_id = $settings_upi_transfer['id'];
				$school_upi_transfer_name = $settings_upi_transfer['name'];
				$school_upi_transfer_message = $settings_upi_transfer['message'];

				$response_data['upi-transfer-data'] = array(
					'school_upi_transfer_enable' => $school_upi_transfer_enable,
					'school_upi_transfer_qr' => $school_upi_transfer_qr_url,
					'school_upi_transfer_id' => $school_upi_transfer_id,
					'school_upi_transfer_name' => $school_upi_transfer_name,
					'school_upi_transfer_message' => $school_upi_transfer_message,
				);
			}

			$response_data['payment_methods'] = $payment_methods;

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Submit invoice payment request.
	public static function parent_submit_invoice_payment_request($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$invoice_id = isset($params['id']) ? sanitize_text_field($params['id']) : '';
			$amount = isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
			$transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
			$payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
			$attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : NULL;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			if ($payment_method == 'bank-transfer') {
				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
						$errors['attachment'] = esc_html__('Please provide attachment PDF format.', 'school-management');
					}
				}

				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/media.php');
				require_once(ABSPATH . 'wp-admin/includes/image.php');

				if (!empty($attachment)) {
					$attachment = media_handle_upload('attachment', 0);
					if (is_wp_error($attachment)) {
						throw new Exception($attachment->get_error_message());
					}
				}
			}

			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);
			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);
			$data = array(
				'receipt_number' => $receipt_number,
				'amount' => $amount,
				'transaction_id' => $transaction_id,
				'payment_method' => $payment_method,
				'invoice_label' => $invoice->invoice_title,
				'invoice_payable' => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id' => $invoice_id,
				'school_id' => $school_id,
				'added_by' => 1,
			);
			$data['created_at'] = current_time('Y-m-d H:i:s');

			if ($payment_method == 'bank-transfer') {
				$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);
				$pending_payment_data = array(
					'receipt_number' => $receipt_number,
					'amount' => $amount,
					'payment_method' => $payment_method,
					'transaction_id' => $transaction_id,
					'invoice_label' => $invoice->invoice_title,
					'invoice_payable' => $invoice->payable,
					'student_record_id' => $invoice->student_id,
					'invoice_id' => $invoice_id,
					'school_id' => $school_id,
					'attachment' => $attachment,
				);
				$pending_payment_data['created_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->insert(WLSM_PENDING_PAYMENTS, $pending_payment_data);
			} else {
				$success = $wpdb->insert(WLSM_PAYMENTS, $data);
			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			if (WLSM_M_Invoice::get_paid_key() === $invoice_status && ($invoice_status !== $invoice->status)) {
				$reload = true;
			} else {
				$reload = false;
			}

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$payment_id = $wpdb->insert_id;

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Request submitted successfully.', 'school-management');

			$response_data['payment'] = array(
				'id' => $payment_id,
				// 'reason'     => esc_html( WLSM_Config::limit_string( WLSM_M_Staff_Class::get_name_text( $description ) ) ),
				// 'leave_date' => esc_html( $leave_date ),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Payment History.
	public static function payments_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$payments_query = WLSM_M::payments_query();

			// Total.
			$payments_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$payments_query}) AS combined_table", $student->ID));

			// Current page and per page.
			$payments_page = isset($params['payments_page']) ? absint($params['payments_page']) : 1;
			$payments_per_page = isset($params['payments_per_page']) ? absint($params['payments_per_page']) : WLSM_M::payments_per_page();

			// Page offset.
			$payments_page_offset = ($payments_page * $payments_per_page) - $payments_per_page;

			// Paginated data.
			$payments = $wpdb->get_results($wpdb->prepare($payments_query . ' ORDER BY p.ID DESC LIMIT %d, %d', $student->ID, $payments_page_offset, $payments_per_page));

			// Paginated response.
			$payments_data = array();
			$payments_pagination = array(
				'current_page' => $payments_page,
				'per_page' => $payments_per_page,
			);

			// Format response.
			if (count($payments)) {
				foreach ($payments as $key => $row) {
					if ($row->invoice_id) {
						$invoice_title = $row->invoice_title;
					} else {
						$invoice_title = $row->invoice_label;
					}

					$payments_data[] = array(
						'id' => $row->ID,
						'receipt_number' => esc_html(WLSM_M_Invoice::get_receipt_number_text($row->receipt_number)),
						'amount' => esc_html(WLSM_Config::sanitize_money($row->amount)),
						'amount_display' => esc_html(WLSM_Config::get_money_text($row->amount, $school_id)),
						'payment_method' => esc_html(WLSM_M_Invoice::get_payment_method_text($row->payment_method)),
						'transaction_id' => esc_html(WLSM_M_Invoice::get_transaction_id_text($row->transaction_id)),
						'date' => esc_html(WLSM_Config::get_date_text($row->created_at)),
						'invoice' => esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice_title))
					);
				}

				$payments_pagination['total_pages'] = ceil($payments_total / $payments_per_page);
				$payments_pagination['total_records'] = $payments_total;
			}

			$success = true;
			$message = esc_html__('Payments retrieved successfully.', 'school-management');

			$response_data = array(
				'payments_data' => $payments_data,
				'pagination' => $payments_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Payment Receipt.
	public static function payment_parent($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$payment_id = isset($params['payment_id']) ? absint($params['payment_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$payment = WLSM_M_Staff_Accountant::get_student_payment($student_id, $payment_id);

			if (!$payment) {
				throw new Exception(esc_html__('Payment not found.', 'school-management'));
			}

			$success = true;
			$message = esc_html__('Payment details retrieved successfully.', 'school-management');

			if ($payment->invoice_id) {
				$invoice_title = esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($payment->invoice_title));
			} else {
				$invoice_title = esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($payment->invoice_label));
			}

			$response_data['payment'] = array(
				'id' => $payment->ID,
				'receipt_number' => esc_html(WLSM_M_Invoice::get_receipt_number_text($payment->receipt_number)),
				'amount' => esc_html(WLSM_Config::sanitize_money($payment->amount)),
				'amount_display' => esc_html(WLSM_Config::get_money_text($payment->amount, $school_id)),
				'payment_method' => esc_html(WLSM_M_Invoice::get_payment_method_text($payment->payment_method)),
				'transaction_id' => esc_html(WLSM_M_Invoice::get_transaction_id_text($payment->transaction_id)),
				'date' => esc_html(WLSM_Config::get_date_text($payment->created_at)),
				'invoice' => esc_html($invoice_title),
				'student_name' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->student_name)),
				'enrollment_number' => esc_html($payment->enrollment_number),
				'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($payment->phone)),
				'email' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->email)),
				'class_label' => esc_html(WLSM_M_Class::get_label_text($payment->class_label)),
				'section' => esc_html(WLSM_M_Class::get_label_text($payment->section_label)),
				'roll_number' => esc_html(WLSM_M_Staff_Class::get_roll_no_text($payment->roll_number)),
				'father_name' => esc_html(WLSM_M_Staff_Class::get_name_text($payment->father_name)),
				'father_phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($payment->father_phone)),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Homeworks.
	public static function parent_homeworks($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;
			$section_id = isset($student->section_id) ? absint($student->section_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			// Query.
			$homeworks_query = WLSM_M::homeworks_query();

			// Total.
			$homeworks_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$homeworks_query}) AS combined_table", $school_id, $session_id, $section_id));

			// Current page and per page.
			$homeworks_page = isset($params['homeworks_page']) ? absint($params['homeworks_page']) : 1;
			$homeworks_per_page = isset($params['homeworks_per_page']) ? absint($params['homeworks_per_page']) : WLSM_M::homeworks_per_page();

			// Page offset.
			$homeworks_page_offset = ($homeworks_page * $homeworks_per_page) - $homeworks_per_page;

			// Paginated data.
			$homeworks = $wpdb->get_results($wpdb->prepare($homeworks_query . ' ORDER BY hw.homework_date DESC LIMIT %d, %d', $school_id, $session_id, $section_id, $homeworks_page_offset, $homeworks_per_page));

			// Paginated response.
			$homeworks_data = array();
			$homeworks_pagination = array(
				'current_page' => $homeworks_page,
				'per_page' => $homeworks_per_page,
			);

			// Format response.
			if (count($homeworks)) {
				foreach ($homeworks as $key => $homework) {
					$homeworks_data[] = array(
						'id' => $homework->ID,
						'title' => esc_html(stripslashes($homework->title)),
						'date' => esc_html(WLSM_Config::get_date_text($homework->homework_date)),
						'due_date' => esc_html(WLSM_Config::get_date_text($homework->homework_due_date)),
					);
				}

				$homeworks_pagination['total_pages'] = ceil($homeworks_total / $homeworks_per_page);
				$homeworks_pagination['total_records'] = $homeworks_total;
			}

			$success = true;
			$message = esc_html__('Homework retrieved successfully.', 'school-management');

			$response_data = array(
				'homeworks_data' => $homeworks_data,
				'pagination' => $homeworks_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Homework.
	public static function parent_homework($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;
			$section_id = isset($student->section_id) ? absint($student->section_id) : 0;

			// $common_details = self::student_common_details($student);
			// $response_data['student'] = $common_details;

			$homework = $wpdb->get_row($wpdb->prepare(WLSM_M::homework_query(), $school_id, $session_id, $section_id, $homework_id));

			$homework_status = $wpdb->get_row($wpdb->prepare(WLSM_M::homework_query_submission(), $school_id, $session_id, $section_id, $homework_id, $student_id));

			if (!$homework) {
				throw new Exception(esc_html__('Homework not found.', 'school-management'));
			}

			$file_name = '';
			$file_link = '';

			$attachments = $homework->attachments;
			if (is_serialized($attachments)) {
				$attachments = unserialize($attachments);
			} else {
				if (!is_array($attachments)) {
					$attachments = array();
				}
			}
			foreach ($attachments as $attachment) {
				if (!empty($attachment)) {
					$file_name = basename(get_attached_file($attachment));
					$file_link = wp_get_attachment_url($attachment);
				}
			}
			if ($homework_status) {
				$sub_date = isset($homework_status->created_at) ? $homework_status->created_at : '';
				$update_date = isset($homework_status->updated_at) ? $homework_status->updated_at : '';
				$status = 1;
			} else {
				$sub_date = '';
				$update_date = '';
				$status = 0;
			}

			$success = true;
			$message = esc_html__('Homework retrieved successfully.', 'school-management');

			$response_data['homework'] = array(
				'id' => $homework->ID,
				'title' => esc_html(stripslashes($homework->title)),
				'description' => esc_html(stripslashes($homework->description)),
				'downloadable' => intval($homework->downloadable),
				'date' => esc_html(WLSM_Config::get_date_text($homework->homework_date)),
				'due_date' => esc_html(WLSM_Config::get_date_text($homework->homework_due_date)),
				'attachment_link' => ($file_link),
				'attachment_name' => ($file_name),
				'Submitted' => ($status),
				'Submitted_date' => ($sub_date),
				'updated_date' => ($update_date),
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Submit homework.
	public static function parent_submit_homework_request($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// $common_details 			= self::student_common_details($student);
			// $response_data['student'] 	= $common_details;

			$submission_id = isset($_POST['submission_id']) ? absint($_POST['submission_id']) : 0;
			$description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
			$homework_update_id = isset($_POST['homework_update']) ? absint($_POST['homework_update']) : 0;
			$homework_sub_id = isset($_POST['homework_sub_id']) ? absint($_POST['homework_sub_id']) : 0;
			$attachment = isset($_FILES['attachments']) && is_array($_FILES['attachments']) ? $_FILES['attachments'] : array();

			if (empty($submission_id)) {
				$errors['submission_id'] = esc_html__('Please Enter Submission Subject', 'school-management');
			}

			if (empty($description)) {
				$errors['description'] = esc_html__('Please Enter description', 'school-management');
			}

			if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
					$errors['attachment'] = esc_html__('Please provide attachment PDF format.', 'school-management');
				}
			}

			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/media.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			if (!empty($attachment)) {
				$attachment = media_handle_upload('attachments', 0);
				if (is_wp_error($attachment)) {
					throw new Exception($attachment->get_error_message());
				}
			}

			// $homework_sub_id = WLSM_M::fetch_submitted_homework($school_id, $session_id, $submission_id, $student_id);

			// Student leave data.
			$data = array(
				'submission_id' => $submission_id,
				'description' => $description,
				'school_id' => $school_id,
				'session_id' => $session_id,
				'student_id' => $student_id,
				'attachments' => $attachment,
			);

			if ($homework_update_id) {
				$data['updated_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->update(WLSM_HOMEWORK_SUBMISSION, $data, array(
					'ID' => $homework_sub_id,
					'submission_id' => $submission_id,
				));
				$submitted_homework_id = $homework_sub_id;
			} else {
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->insert(WLSM_HOMEWORK_SUBMISSION, $data);
				$submitted_homework_id = $wpdb->insert_id;
			}

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('homework submitted successfully.', 'school-management');

			$response_data['homework'] = array(
				'submitted_homework_id' => $submitted_homework_id,
				'description' => esc_html(($description)),
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Submitted homeworks.
	public static function parent_submitted_homeworks($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			$submitted_homeworks = WLSM_M::fetch_submitted_homeworks($school_id, $session_id, $homework_id, $student_id);

			if (!$submitted_homeworks) {
				throw new Exception(esc_html__('No submitted homeworks found.', 'school-management'));
			}

			if ($submitted_homeworks) {
				foreach ($submitted_homeworks as $homework) {
					$data[] = array(
						'homework_id' => isset($homework_id) ? $homework_id : 0,
						'submission_id' => isset($homework->ID) ? $homework->ID : 0,
						'description' => isset($homework->description) ? $homework->description : '',
						'date' => isset($homework->created_at) ? date('d-m-Y', strtotime($homework->created_at)) : ''
					);
				}
			}

			WLSM_Helper::check_buffer();

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Submitted homeworks retrieved successfully.', 'school-management');

			$response_data['homework'] = $data;
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Submitted homework.
	public static function fetch_parent_submitted_homework($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;
			$submission_id = isset($params['submission_id']) ? absint($params['submission_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();

			$student_id = isset($student->ID) ? absint($student->ID) : 0;
			$school_id = isset($student->school_id) ? absint($student->school_id) : 0;
			$session_id = isset($student->session_id) ? absint($student->session_id) : 0;
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			$submitted_homework = WLSM_M::get_submitted_homework($school_id, $session_id, $homework_id, $submission_id, $student_id);

			if (!$submitted_homework) {
				throw new Exception(esc_html__('Submitted homework not found.', 'school-management'));
			}

			$data = array(
				'submission_id' => isset($submitted_homework->ID) ? $submitted_homework->ID : 0,
				'attachment' => isset($submitted_homework->attachments) ? wp_get_attachment_url($submitted_homework->attachments) : '',
				'description' => isset($submitted_homework->description) ? $submitted_homework->description : ''
			);

			WLSM_Helper::check_buffer();

			$wpdb->query('COMMIT;');

			$success = true;
			$message = esc_html__('Submitted homeworks retrieved successfully.', 'school-management');

			$response_data['homework'] = $data;
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Lessons by filters.
	public static function parent_lessons_by_filters($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
			$chapter_id = isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();
			$class_school_id = isset($student->class_school_id) ? absint($student->class_school_id) : 0;

			// Build query conditions based on filters
			$query_conditions = [];
			$query_args = [$class_school_id]; // First param is always class_school_id

			// Base query - Added subject_id and chapter_id
			$lessons_query = "SELECT l.ID, l.title, l.description, l.attachment, l.url, l.link_to, l.created_at,
				c.label as class_label, s.label as subject_label, cp.title as chapter_title,
				l.subject_id, l.chapter_id
				FROM " . WLSM_LECTURE . " as l
				LEFT JOIN " . WLSM_CLASSES . " as c ON l.class_id = c.ID
				LEFT JOIN " . WLSM_SUBJECTS . " as s ON s.ID = l.subject_id
				LEFT JOIN " . WLSM_CHAPTER . " as cp ON cp.ID = l.chapter_id
				WHERE s.class_school_id = %d";

			// Add subject filter if specified
			if ($subject_id) {
				$lessons_query .= " AND l.subject_id = %d";
				$query_args[] = $subject_id;
			}

			// Add chapter filter if specified
			if ($chapter_id) {
				$lessons_query .= " AND l.chapter_id = %d";
				$query_args[] = $chapter_id;
			}

			// Current page and per page
			$lessons_page = isset($params['page']) ? absint($params['page']) : 1;
			$lessons_per_page = isset($params['per_page']) ? absint($params['per_page']) : WLSM_M::lessons_per_page();

			// Get total count
			$count_query = "SELECT COUNT(1) FROM ({$lessons_query}) AS count_table";
			$lessons_total = $wpdb->get_var($wpdb->prepare($count_query, ...$query_args));

			// Add pagination
			$lessons_page_offset = ($lessons_page * $lessons_per_page) - $lessons_per_page;
			$lessons_query .= " ORDER BY l.ID DESC LIMIT %d, %d";
			$query_args[] = $lessons_page_offset;
			$query_args[] = $lessons_per_page;

			// Execute the query
			$lessons = $wpdb->get_results($wpdb->prepare($lessons_query, ...$query_args));

			// Format data
			$lessons_data = array();
			foreach ($lessons as $lesson) {
				// Get attachment details if available
				$attachment_url = '';
				$attachment_name = '';
				if (!empty($lesson->attachment)) {
					$attachment_url = wp_get_attachment_url($lesson->attachment);
					$attachment_name = basename(get_attached_file($lesson->attachment));
				}

				$lessons_data[] = array(
					'id' => $lesson->ID,
					'title' => $lesson->title,
					'description' => $lesson->description,
					'attachment' => $lesson->attachment,
					'url' => $lesson->url,
					'link_to' => $lesson->link_to,
					'created_at' => $lesson->created_at,
					'class_label' => $lesson->class_label,
					'subject' => $lesson->subject_label,
					'subject_id' => $lesson->subject_id,
					'chapter' => $lesson->chapter_title,
					'chapter_id' => $lesson->chapter_id
				);
			}

			$lessons_pagination = array(
				'current_page' => $lessons_page,
				'per_page' => $lessons_per_page,
				'total_pages' => ceil($lessons_total / $lessons_per_page),
				'total_records' => $lessons_total
			);

			$success = true;
			$message = esc_html__('Lessons retrieved successfully.', 'school-management');

			$response_data = array(
				'lessons' => $lessons_data,
				'pagination' => $lessons_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Lesson details.
	public static function parent_lesson_details($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
			$lesson_id = isset($params['lesson_id']) ? absint($params['lesson_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();
			$class_school_id = isset($student) ? absint($student->class_school_id) : 0;

			// Query for the specific lesson - Added subject_id and chapter_id to the SELECT clause
			$lesson_query = "SELECT l.ID, l.title, l.description, l.attachment, l.url, l.link_to, l.created_at,
				c.label as class_label, s.label as subject_label, cp.title as chapter_title,
				l.subject_id, l.chapter_id
				FROM " . WLSM_LECTURE . " as l
				LEFT JOIN " . WLSM_CLASSES . " as c ON l.class_id = c.ID
				LEFT JOIN " . WLSM_SUBJECTS . " as s ON s.ID = l.subject_id
				LEFT JOIN " . WLSM_CHAPTER . " as cp ON cp.ID = l.chapter_id
				WHERE l.ID = %d";

			$lesson = $wpdb->get_row($wpdb->prepare($lesson_query, $lesson_id));

			if (!$lesson) {
				throw new Exception(esc_html__('Lesson not found.', 'school-management'));
			}

			// Get attachment details if available
			$attachment_url = '';
			$attachment_name = '';
			if (!empty($lesson->attachment)) {
				$attachment_url = wp_get_attachment_url($lesson->attachment);
				$attachment_name = basename(get_attached_file($lesson->attachment));
			}

			$success = true;
			$message = esc_html__('Lesson details retrieved successfully.', 'school-management');

			$response_data = array(
				'id' => $lesson->ID,
				'title' => $lesson->title,
				'description' => $lesson->description,
				'attachment' => $lesson->attachment,
				'url' => $lesson->url,
				'link_to' => $lesson->link_to,
				'created_at' => $lesson->created_at,
				'class_label' => $lesson->class_label,
				'subject' => $lesson->subject_label,
				'subject_id' => $lesson->subject_id,
				'chapter' => $lesson->chapter_title,
				'chapter_id' => $lesson->chapter_id
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Parent - Lesson.
	public static function parent_lessons($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids($user_id);

			if (!count($unique_student_ids)) {
				throw new Exception(esc_html__('Parent not found.', 'school-management'));
			}

			$params = $request->get_params();
			$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

			if ($student_id && !in_array($student_id, $unique_student_ids)) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student = WLSM_M_Parent::fetch_student($student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$response_data = array();
			$class_school_id = $student->class_school_id;

			// Query - Using the new function for lessons only
			$lessons_query = WLSM_M::lessons_only_query();

			// Total.
			$lessons_total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM ({$lessons_query}) AS combined_table", $class_school_id));

			// Current page and per page.
			$lessons_page = isset($params['lesson_page']) ? absint($params['lesson_page']) : 1;
			$lessons_per_page = isset($params['lesson_per_page']) ? absint($params['lesson_per_page']) : WLSM_M::lessons_per_page();

			// Page offset.
			$lessons_page_offset = ($lessons_page * $lessons_per_page) - $lessons_per_page;

			// Paginated data.
			// Fix: Properly handle parameters for prepare() function
			$limit_query = " ORDER BY l.ID DESC LIMIT %d, %d";
			$lessons = $wpdb->get_results(
				$wpdb->prepare(
					$lessons_query . $limit_query,
					// First parameter for $lessons_query's placeholder
					$class_school_id,
					// Following parameters for LIMIT clause
					$lessons_page_offset,
					$lessons_per_page
				)
			);

			// Paginated response.
			$lessons_data = array();
			$lessons_pagination = array(
				'current_page' => $lessons_page,
				'per_page' => $lessons_per_page,
			);

			// Format response.
			if (count($lessons)) {
				foreach ($lessons as $lesson) {
					$lessons_data[] = array(
						'id' => $lesson->ID,
						'title' => $lesson->title,
						'description' => $lesson->description,
						'attachment' => $lesson->attachment,
						'url' => $lesson->url,
						'link_to' => $lesson->link_to,
						'created_at' => $lesson->created_at,
						'class_label' => $lesson->class_label,
						'subject' => $lesson->subject_label,
						'subject_id' => $lesson->subject_id,
						'chapter' => $lesson->chapter_title,
						'chapter_id' => $lesson->chapter_id
					);
				}

				$lessons_pagination['total_pages'] = ceil($lessons_total / $lessons_per_page);
				$lessons_pagination['total_records'] = $lessons_total;
			}

			$success = true;
			$message = esc_html__('Lessons retrieved successfully.', 'school-management');

			$response_data = array(
				'lessons' => $lessons_data,
				'pagination' => $lessons_pagination
			);

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Profile.
	public static function staff_profile($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$staff = WLSM_M::get_staff_profile($user_id);
			$staff_common_details = WLSM_M::get_staff($user_id);

			if (!$staff) {
				throw new Exception(esc_html__('Staff not found.', 'school-management'));
			}

			$response_data = array();
			$school_data = array();
			$data = array();

			$school_id = $staff->school_id;
			$photo_url = " ";

			$school = WLSM_M_School::fetch_school($school_id);

			if ($school) {
				// General settings.
				$settings_general = WLSM_M_Setting::get_settings_general($school_id);
				$school_logo = " ";
				$school_currency = "";
				$school_currency = $settings_general['school_currency'];

				$school_data = array(
					'name' => esc_html(WLSM_M_School::get_label_text($school->label)),
					'phone' => esc_html(WLSM_M_School::get_phone_text($school->phone)),
					'email' => esc_html(WLSM_M_School::get_email_text($school->email)),
					'description' => esc_html(WLSM_M_School::get_description_text($school->description)),
					'address' => esc_html(WLSM_M_School::get_address_text($school->address)),
					'logo' => esc_url($school_logo),
				);
			}
			$response_data['school'] = $school_data;

			if ($staff) {
				$staff_data = array(
					'name' => esc_html(WLSM_M_Staff_Class::get_name_text($staff->staff_name)),
					'gender' => esc_html(WLSM_M_Staff_Class::get_gender_text($staff->gender)),
					'dob' => esc_html(WLSM_M_Staff_Class::get_date_text($staff->dob)),
					'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($staff->phone)),
					'email' => esc_html(WLSM_M_Staff_Class::get_email_text($staff->email)),
					'address' => esc_html(WLSM_M_Staff_Class::get_address_text($staff->address)),
					'salary' => esc_html(WLSM_M_Staff_Class::get_salary_text($staff->salary, $school_currency)),
					'designation' => esc_html(WLSM_M_Staff_Class::get_name_text($staff->designation)),
					'joining_date' => esc_html(WLSM_M_Staff_Class::get_date_text($staff->joining_date)),
					'qualification' => esc_html(WLSM_M_Staff_Class::get_name_text($staff->qualification)),
				);
			}
			$response_data['staff'] = $staff_data;

			$success = true;
			$message = esc_html__('Staff profile retrieved successfully.', 'school-management');

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Dashboard.
	public static function staff_dashboard($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$staff = WLSM_M::get_staff($user_id);

			if (!$staff) {
				throw new Exception(esc_html__('Staff not found.', 'school-management'));
			}

			$response_data = array();
			$staff_permissions = array();
			$school_id = $staff->school_id;
			$photo_url = " ";

			$school = WLSM_M_School::fetch_school($school_id);

			if ($school) {
				// General settings.
				$settings_general = WLSM_M_Setting::get_settings_general($school_id);
				$school_logo = " ";
				$school_currency = "";
				$school_currency = $settings_general['school_currency'];

				$school_data = array(
					'name' => esc_html(WLSM_M_School::get_label_text($school->label)),
					'phone' => esc_html(WLSM_M_School::get_phone_text($school->phone)),
					'email' => esc_html(WLSM_M_School::get_email_text($school->email)),
					'description' => esc_html(WLSM_M_School::get_description_text($school->description)),
					'address' => esc_html(WLSM_M_School::get_address_text($school->address)),
					'logo' => esc_url($school_logo),
				);
			}
			$response_data['school'] = $school_data;

			if ($staff) {
				$staff_data = array(
					'name' => esc_html(WLSM_M_Staff_Class::get_name_text($staff->staff_name)),
					'gender' => esc_html(WLSM_M_Staff_Class::get_gender_text($staff->gender)),
					'dob' => esc_html(WLSM_M_Staff_Class::get_date_text($staff->dob)),
					'phone' => esc_html(WLSM_M_Staff_Class::get_phone_text($staff->phone)),
					'email' => esc_html(WLSM_M_Staff_Class::get_email_text($staff->email)),
					'address' => esc_html(WLSM_M_Staff_Class::get_address_text($staff->address)),
					'salary' => esc_html(WLSM_M_Staff_Class::get_salary_text($staff->salary, $school_currency)),
					'designation' => esc_html(WLSM_M_Staff_Class::get_name_text($staff->designation)),
					'joining_date' => esc_html(WLSM_M_Staff_Class::get_date_text($staff->joining_date)),
					'qualification' => esc_html(WLSM_M_Staff_Class::get_name_text($staff->qualification)),
				);

				$permissions = unserialize($staff->permissions);

				if ($permissions) {
					$keys = [
						'view_attendance' => 'Attendance',
						'view_events' => 'Events',
						'view_notices' => 'Notices',
						'view_timetable' => 'Timetable',
						'view_homework' => 'Homework',
						'view_study_materials' => 'StudyMaterial',
						'view_subjects' => 'Subjects',
						'view_inquiries' => 'Inquiries',
						'view_exams' => 'Exams',
						'view_admit_cards' => 'Admitcards',
						'view_exam_results' => 'Results',
						'view_transport' => 'Transport',
						'view_expenses' => 'Expenses',
						'view_income' => 'Donation',
						'view_student_leaves' => 'Student_leaves',
						'view_hostel' => 'Hostel',
						'view_activities' => 'Activities',
						'view_lessons' => 'Lessons',
						'view_tickets' => 'Tickets',
						'view_students' => 'Students',
						'view_fees' => 'Fee_types',
						'view_concession_types' => 'Concession_types',
					];

					foreach ($keys as $key => $label) {
						$staff_permissions[$label] = in_array($key, $permissions);
					}
				}
			}
			$response_data['staff'] = $staff_data;
			$response_data['features'] = $staff_permissions;

			$success = true;
			$message = esc_html__('Staff dashboard retrieved successfully.', 'school-management');

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Class list.
	public static function class_list($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$staff = WLSM_M::get_staff($user_id);

			if (!$staff) {
				throw new Exception(esc_html__('Staff not found.', 'school-management'));
			}

			$response_data = array();
			$classes 	= array();
			$school_id 	= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id = isset($staff->section_id) ? absint($staff->section_id) : 0;

			if ($staff) {
				$permissions = unserialize($staff->permissions);
				$staff_permissions = in_array('assigned_class', $permissions);

				if ($staff_permissions && $section_id) {
					$classes = WLSM_M::get_assigned_class($section_id);
				} else {
					$classes = WLSM_M::fetch_classes($school_id);
				}
			}

			$response_data['classes'] = $classes;

			$success = true;
			$message = esc_html__('Staff dashboard retrieved successfully.', 'school-management');

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Section list.
	public static function section_list($request)
	{

		$user_id = get_current_user_id();

		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$class_id = isset($params['class_id']) ? $params['class_id'] : 0;

		try {
			global $wpdb;

			if (empty($class_id)) {
				throw new Exception(esc_html__('Please select a class.', 'school-management'));
			}

			$sections = WLSM_M::fetch_sections($school_id, $class_id);

			if (!$sections) {
				throw new Exception(esc_html__('No sections found.', 'school-management'));
			}

			$response_data = array();
			$response_data['sections'] = $sections;

			$success = true;
			$message = esc_html__('Sections Retrieved Successfully.', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Student list.
	public static function student_list($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = absint($staff->school_id);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$date 		= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';

		try {
			global $wpdb;

			$current_session_id = get_option('wlsm_current_session');
			$session = WLSM_M_Session::get_session($current_session_id);
			if (!$session) {
				throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
			}
			$session_id = isset($session->ID) ? absint($session->ID) : 0;

			if (empty($class_id)) {
				throw new Exception(esc_html__('Please select a class.', 'school-management'));
			}

			if (empty($section_id)) {
				throw new Exception(esc_html__('Please select a section.', 'school-management'));
			}

			if (empty($date)) {
				throw new Exception(esc_html__('Please select a date.', 'school-management'));
			}

			$students = WLSM_M::fetch_students($school_id, $class_id, $section_id, $session_id);

			if (!$students) {
				throw new Exception(esc_html__('No students found.', 'school-management'));
			}

			foreach ($students as $key => $student) {
				$student_attendance = WLSM_M::fetch_student_attendance($student->ID, $date);
				$status = isset($student_attendance->status) ? $student_attendance->status : '';

				if ($status == 'p') {
					$present = 1;
					$absent = 0;
					$holiday = 0;
					$late = 0;
					$reason = "";
				} elseif ($status == 'a') {
					$present = 0;
					$absent = 1;
					$holiday = 0;
					$late = 0;
					$reason = "";
				} elseif ($status == 'h') {
					$present = 0;
					$absent = 0;
					$holiday = 1;
					$late = 0;
					$reason = "";
				} elseif ($status == 'l') {
					$present = 0;
					$absent = 0;
					$holiday = 0;
					$late = 1;
					$reason = $student_attendance->reason;
				} else {
					$present = 0;
					$absent = 0;
					$holiday = 0;
					$late = 0;
					$reason = "";
				}

				$students[$key]->ID = WLSM_M_Staff_Class::get_label_text($student->ID);
				$students[$key]->name = WLSM_M_Staff_Class::get_name_text($student->name);
				$students[$key]->roll_number = WLSM_M_Staff_Class::get_roll_no_text($student->roll_number);
				$students[$key]->present = $present;
				$students[$key]->absent = $absent;
				$students[$key]->holiday = $holiday;
				$students[$key]->late = $late;
				$students[$key]->reason = $reason;
			}

			$response_data = array();
			$response_data['students'] = $students;

			$success = true;
			$message = esc_html__('Students Retrieved Successfully.', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Class student list.
	public static function class_student_list($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 	= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id = isset($staff->section_id) ? absint($staff->section_id) : 0;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;

		try {
			global $wpdb;

			$current_session_id = get_option('wlsm_current_session');
			$session = WLSM_M_Session::get_session($current_session_id);
			if (!$session) {
				throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
			}
			$session_id = isset($session->ID) ? absint($session->ID) : 0;

			if (empty($class_id)) {
				throw new Exception(esc_html__('Please select a class.', 'school-management'));
			}

			$students = WLSM_M::fetch_class_students($school_id, $session_id, $class_id);

			if (!$students) {
				throw new Exception(esc_html__('No students found.', 'school-management'));
			}

			$response_data = array();
			$response_data['students'] = $students;

			$success = true;
			$message = esc_html__('Students Retrieved Successfully.', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Student Attendances.
	public static function view_students_attendance($request)
	{

		$user_id = get_current_user_id();

		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? $params['class_id'] : 0;
		$section_id = isset($params['section_id']) ? $params['section_id'] : 0;
		$month_year = isset($params['month_year']) ? $params['month_year'] : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($month_year)) {
					throw new Exception(esc_html__('Please select a month.', 'school-management'));
				}

				$students = WLSM_M::fetch_students($school_id, $class_id, $section_id, $session_id);

				if (!$students) {
					throw new Exception(esc_html__('No students found.', 'school-management'));
				}

				foreach ($students as $key => $student) {
					$date = DateTime::createFromFormat('F Y', $month_year);
					$month = $date->format('m');
					$year = $date->format('Y');
					$attendance = WLSM_M_Staff_General::student_attendance($student->ID, $month, $year);

					if (empty($attendance)) {
						$attendance = array(
							'attendance_date' 	=> "",
							'status' 			=> "",
							'reason' 			=> "",
						);
					}
					$students[$key]->attendance = $attendance;
				}

				$response_data = array();
				$response_data['students'] = $students;

				$success = true;
				$message = esc_html__('Students Attendance Retrieved Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view students attendance.', 'school-management'));
				die();
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Take Student Attendance.
	public static function take_students_attendance($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$student_id = isset($params['student_id']) ? $params['student_id'] : 0;
		$status 	= isset($params['status']) ? $params['status'] : '';
		$date 		= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';
		$reason 	= isset($params['reason']) ? $params['reason'] : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($student_id)) {
					throw new Exception(esc_html__('Please select a student.', 'school-management'));
				}

				if (empty($status)) {
					throw new Exception(esc_html__('Please select a status.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please select a date.', 'school-management'));
				}

				if ($status == 'l' && empty($reason)) {
					throw new Exception(esc_html__('Please enter a reason.', 'school-management'));
				}

				$attendance_exists = WLSM_M::fetch_student_attendance($student_id, $date);
				if ($attendance_exists) {
					throw new Exception(esc_html__('Attendance already exisits for this date.', 'school-management'));
				}

				$data = array(
					'attendance_date' => $date,
					'status' => $status,
					'student_record_id' => $student_id,
					'reason' => $reason,
					'added_by' => $user_id,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_ATTENDANCE, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Student Attendance Added Successfully.', 'school-management');

				$response_data['attendance'] = array(
					'attendance_date' => $date,
					'status' => $status,
					'student_record_id' => $student_id,
					'reason' => $reason,
					'added_by' => $user_id,
				);
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to take student attendance.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Take Bulk Student Attendance.
	public static function take_bulk_student_attendance($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$status 	= isset($params['status']) ? $params['status'] : '';
		$date 		= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($status)) {
					throw new Exception(esc_html__('Please select a status.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please select a date.', 'school-management'));
				}

				$students = WLSM_M::fetch_students($school_id, $class_id, $section_id, $session_id);

				if ($students) {
					foreach ($students as $student) {
						$student_id = $student->ID;
						$attendance_exists = WLSM_M::fetch_student_attendance($student_id, $date);
						if ($attendance_exists) {
							throw new Exception(esc_html__('Attendance already exisits for this date.', 'school-management'));
						}

						$data = array(
							'attendance_date' => $date,
							'status' => $status,
							'student_record_id' => $student_id,
							'reason' => '',
							'added_by' => $user_id,
						);

						$data['created_at'] = current_time('Y-m-d H:i:s');

						$success = $wpdb->insert(WLSM_ATTENDANCE, $data);
					}
				} else {
					throw new Exception(esc_html__('No student found.', 'school-management'));
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$success = true;
				$message = esc_html__('Students Attendance Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to take student attendance.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Take Student Bulk Attendance.
	public static function take_students_bulk_attendance($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= $staff->school_id;
			$permissions	 	= unserialize($staff->permissions);
			$add_attendance 	= in_array('add_attendance', $permissions);
			$edit_attendance 	= in_array('edit_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 				= $request->get_params();
		$class_id 				= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id 			= isset($params['section_id']) ? absint($params['section_id']) : 0;
		$student_attendances 	= isset($params['attendance']) ? $params['attendance'] : '';
		$date 					= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';

		try {

			if ($add_attendance && $edit_attendance) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($student_attendances)) {
					throw new Exception(esc_html__('Please select a status.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please select a date.', 'school-management'));
				}


				if ($student_attendances) {
					foreach ($student_attendances as $student_attendance) {
						$student_id = $student_attendance['student_id'];
						$status = $student_attendance['status'];

						if ($status) {
							$data = array(
								'attendance_date' => $date,
								'status' => $status,
								'student_record_id' => $student_id,
								'reason' => '',
								'added_by' => $user_id,
							);

							$attendance = WLSM_M::fetch_student_attendance($student_id, $date);

							if ($attendance) {
								if ($edit_attendance) {
									$attendance_id = isset($attendance->ID) ? absint($attendance->ID) : '';
									$data['updated_at'] = current_time('Y-m-d H:i:s');

									$success = $wpdb->update(WLSM_ATTENDANCE, $data, array('ID' => $attendance_id, 'student_record_id' => $student_id));
									$success = true;
									$message = esc_html__('Student Attendance Updated Successfully.', 'school-management');
								} else {
									throw new Exception(esc_html__('You do not have permission to edit student attendance.', 'school-management'));
								}
							} else {
								if ($add_attendance) {
									$data['created_at'] = current_time('Y-m-d H:i:s');

									$success = $wpdb->insert(WLSM_ATTENDANCE, $data);
									$success = true;
									$message = esc_html__('Student Attendance Added Successfully.', 'school-management');
								} else {
									throw new Exception(esc_html__('You do not have permission to add student attendance.', 'school-management'));
								}
							}
						}
					}
				} else {
					throw new Exception(esc_html__('No student found.', 'school-management'));
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				if (!$add_attendance) {
					$success = false;
					throw new Exception(esc_html__('You do not have permission to take student attendance.', 'school-management'));
				}

				if (!$edit_attendance) {
					$success = false;
					throw new Exception(esc_html__('You do not have permission to edit student attendance.', 'school-management'));
				}
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - Class Subject list.
	public static function class_subject_list($request)
	{

		$user_id = get_current_user_id();

		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$class_id = isset($params['class_id']) ? $params['class_id'] : 0;

		try {

			if (empty($class_id)) {
				throw new Exception(esc_html__('Please select a class.', 'school-management'));
			}

			global $wpdb;
			$subjects = WLSM_M::fetch_class_subjects($school_id, $class_id);

			if (!$subjects) {
				throw new Exception(esc_html__('No subjects found.', 'school-management'));
			}

			$response_data = array();
			$response_data['subjects'] = $subjects;

			$success = true;
			$message = esc_html__('Subjects Retrieved Successfully.', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Class Subject Student list.
	public static function class_subject_student_list($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? $params['class_id'] : 0;
		$section_id = isset($params['section_id']) ? $params['section_id'] : 0;
		$subject_id = isset($params['subject_id']) ? $params['subject_id'] : 0;
		$date 		= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';

		try {
			global $wpdb;

			$current_session_id = get_option('wlsm_current_session');
			$session = WLSM_M_Session::get_session($current_session_id);
			if (!$session) {
				throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
			}
			$session_id = isset($session->ID) ? absint($session->ID) : 0;

			if (empty($class_id)) {
				throw new Exception(esc_html__('Please select a class.', 'school-management'));
			}

			if (empty($section_id)) {
				throw new Exception(esc_html__('Please select a section.', 'school-management'));
			}

			if (empty($subject_id)) {
				throw new Exception(esc_html__('Please select a subject.', 'school-management'));
			}

			if (empty($date)) {
				throw new Exception(esc_html__('Please select a date.', 'school-management'));
			}

			$students = WLSM_M::fetch_class_subject_students($school_id, $session_id, $class_id, $section_id, $subject_id);

			if (!$students) {
				throw new Exception(esc_html__('There is no student register with this subject.', 'school-management'));
			}

			foreach ($students as $key => $student) {
				$student_attendance = WLSM_M::fetch_student_attendance($student->ID, $date);
				$status = isset($student_attendance->status) ? $student_attendance->status : '';

				if ($status == 'p') {
					$present = 1;
					$absent = 0;
					$holiday = 0;
					$late = 0;
					$reason = "";
				} elseif ($status == 'a') {
					$present = 0;
					$absent = 1;
					$holiday = 0;
					$late = 0;
					$reason = "";
				} elseif ($status == 'h') {
					$present = 0;
					$absent = 0;
					$holiday = 1;
					$late = 0;
					$reason = "";
				} elseif ($status == 'l') {
					$present = 0;
					$absent = 0;
					$holiday = 0;
					$late = 1;
					$reason = $student_attendance->reason;
				} else {
					$present = 0;
					$absent = 0;
					$holiday = 0;
					$late = 0;
					$reason = "";
				}

				$students[$key]->ID 			= WLSM_M_Staff_Class::get_label_text($student->ID);
				$students[$key]->name 			= WLSM_M_Staff_Class::get_name_text($student->name);
				$students[$key]->roll_number 	= isset($student->roll_number) ? $student->roll_number : '-';
				$students[$key]->present 		= $present;
				$students[$key]->absent 		= $absent;
				$students[$key]->holiday 		= $holiday;
				$students[$key]->late 			= $late;
				$students[$key]->reason 		= $reason;
			}

			$response_data = array();
			$response_data['students'] = $students;

			$success = true;
			$message = esc_html__('Student Retrieved Successfully.', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Subject Wise Students Attendances.
	public static function view_subject_wise_students_attendance($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= $staff->school_id;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$month_year = isset($params['month_year']) ? $params['month_year'] : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($month_year)) {
					throw new Exception(esc_html__('Please select a month.', 'school-management'));
				}

				$students = WLSM_M::fetch_class_subject_students($school_id, $session_id, $class_id, $section_id, $subject_id);

				if (!$students) {
					throw new Exception(esc_html__('No students found.', 'school-management'));
				}

				foreach ($students as $key => $student) {
					$date 		= DateTime::createFromFormat('F Y', $month_year);
					$month 		= $date->format('m');
					$year 		= $date->format('Y');
					$attendance = WLSM_M_Staff_General::subject_wise_student_attendance($student->ID, $month, $year, $subject_id);

					if (empty($attendance)) {
						$attendance = array(
							'attendance_date' => "",
							'status' => "",
							'reason' => "",
						);
					}
					$students[$key]->attendance = $attendance;
				}

				$response_data = array();
				$response_data['students'] = $students;

				$success = true;
				$message = esc_html__('Students Attendance Retrieved Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view students attendance.', 'school-management'));
				die();
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Take Subject Wise Student Attendance.
	public static function take_subject_wise_student_attendance($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= $staff->school_id;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$student_id = isset($params['student_id']) ? $params['student_id'] : 0;
		$subject_id = isset($params['subject_id']) ? $params['subject_id'] : 0;
		$status 	= isset($params['status']) ? $params['status'] : '';
		$date 		= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';
		$reason 	= isset($params['reason']) ? $params['reason'] : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($student_id)) {
					throw new Exception(esc_html__('Please select a student.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($status)) {
					throw new Exception(esc_html__('Please select a status.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please select a date.', 'school-management'));
				}

				if ($status == 'l' && empty($reason)) {
					throw new Exception(esc_html__('Please enter a reason.', 'school-management'));
				}

				$attendance_exists = WLSM_M::fetch_student_attendance($student_id, $date);
				if ($attendance_exists) {
					throw new Exception(esc_html__('Attendance already exisits for this date.', 'school-management'));
				}

				$data = array(
					'attendance_date' 	=> $date,
					'status' 			=> $status,
					'student_record_id' => $student_id,
					'reason' 			=> $reason,
					'subject_id' 		=> $subject_id,
					'added_by' 			=> $user_id,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_ATTENDANCE, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Student Attendance Added Successfully.', 'school-management');

				$response_data['attendance'] = array(
					'attendance_date' 	=> $date,
					'status' 			=> $status,
					'student_record_id' => $student_id,
					'reason' 			=> $reason,
					'subject_id' 		=> $subject_id,
					'added_by' 			=> $user_id,
				);
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to take student attendance.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Take Bulk Student Attendance.
	public static function take_subject_wise_bulk_student_attendance($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= $staff->school_id;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_attendance', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$status 	= isset($params['status']) ? $params['status'] : '';
		$date 		= isset($params['date']) ? date('Y-m-d', strtotime($params['date'])) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session 			= WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($status)) {
					throw new Exception(esc_html__('Please select a status.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please select a date.', 'school-management'));
				}

				$students = WLSM_M::fetch_class_subject_students($school_id, $session_id, $class_id, $section_id, $subject_id);

				if ($students) {
					foreach ($students as $student) {
						$student_id = $student->ID;
						$attendance_exists = WLSM_M::fetch_student_attendance($student_id, $date);
						if ($attendance_exists) {
							throw new Exception(esc_html__('Attendance already exisits for this date.', 'school-management'));
						}

						$data = array(
							'attendance_date' 	=> $date,
							'status' 			=> $status,
							'student_record_id' => $student_id,
							'reason' 			=> '',
							'subject_id' 		=> $subject_id,
							'added_by' 			=> $user_id,
						);

						$data['created_at'] = current_time('Y-m-d H:i:s');

						$success = $wpdb->insert(WLSM_ATTENDANCE, $data);
					}
				} else {
					throw new Exception(esc_html__('No student found.', 'school-management'));
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$success = true;
				$message = esc_html__('Students Attendance Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to take student attendance.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View events.
	public static function view_events($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_events', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;
				$data = array();
				$response_data['events'] = $data;
				$events = WLSM_M::fetch_events($school_id);

				if ($events) {
					foreach ($events as $event) {
						$data[] = array(
							'ID' => isset($event->ID) ? WLSM_M_Staff_Class::get_label_text($event->ID) : '',
							'title' => isset($event->title) ? WLSM_M_Staff_Class::get_label_text($event->title) : '',
							'description' => isset($event->description) ? WLSM_M_Staff_Class::get_label_text($event->description) : '',
							'image_url' => isset($event->image_id) ? wp_get_attachment_url($event->image_id) : '',
							'event_date' => isset($event->event_date) ? WLSM_M_Staff_Class::get_date_text($event->event_date) : '',
							'added_by' => isset($event->display_name) ? WLSM_M_Staff_Class::get_label_text($event->display_name) : '',
						);
					}
					$response_data['events'] = $data;
				}

				$success = true;
				$message = esc_html__('Events Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view events.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Event.
	public static function add_new_event($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_events', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$title = isset($params['title']) ? $params['title'] : '';
		$description = isset($params['description']) ? $params['description'] : '';
		$image = isset($_FILES['image']) ? $_FILES['image'] : NULL;
		$event_date = isset($params['event_date']) ? $params['event_date'] : NULL;
		$is_active = isset($params['is_active']) ? $params['is_active'] : 1;

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($title)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (!empty($event_date)) {
					$event_date = date('Y-m-d', strtotime($event_date));
				} else {
					$event_date = NULL;
				}

				if (isset($image) && !empty($_FILES['image']['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($_FILES['image'], 'image')) {
						throw new Exception(esc_html__('Please provide event image in JPG, JPEG or PNG format.', 'school-management'));
					}

					if (!function_exists('media_handle_upload')) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';
					}

					$image_id = media_handle_upload('image', 0);
					if (is_wp_error($image_id)) {
						throw new Exception($image_id->get_error_message());
					}
				} else {
					$image_id = NULL;
				}

				$data = array(
					'title' => $title,
					'description' => $description,
					'image_id' => $image_id,
					'event_date' => $event_date,
					'is_active' => $is_active,
					'school_id' => $school_id,
					'added_by' => $user_id,
				);



				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_EVENTS, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Event Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new event.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View event by id.
	public static function view_event_by_id($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_events', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$event_id = isset($params['id']) ? absint($params['id']) : 0;

		try {
			if ($staff_permissions) {
				global $wpdb;

				$event = WLSM_M::fetch_event($school_id, $event_id);
				if ($event) {
					$data = array(
						'ID' => isset($event->ID) ? WLSM_M_Staff_Class::get_label_text($event->ID) : '',
						'title' => isset($event->title) ? WLSM_M_Staff_Class::get_label_text($event->title) : '',
						'description' => isset($event->description) ? WLSM_M_Staff_Class::get_label_text($event->description) : '',
						'image_url' => isset($event->image_id) ? wp_get_attachment_url($event->image_id) : '',
						'event_date' => isset($event->event_date) ? WLSM_M_Staff_Class::get_date_text($event->event_date) : '',
						'added_by' => isset($event->display_name) ? WLSM_M_Staff_Class::get_label_text($event->display_name) : '',
					);
					$response_data['event'] = $data;
				}

				$success = true;
				$message = esc_html__('Event Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view events.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit Event.
	public static function edit_event($request)
	{
		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_events', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id = isset($params['id']) ? $params['id'] : 0;
		$title = isset($params['title']) ? $params['title'] : '';
		$description = isset($params['description']) ? $params['description'] : '';
		$image = isset($_FILES['image']) ? $_FILES['image'] : '';
		$event_date = isset($params['event_date']) ? $params['event_date'] : '';
		$is_active = isset($params['is_active']) ? $params['is_active'] : 1;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$event = WLSM_M_Staff_Class::get_event($school_id, $id);
				if (empty($event)) {
					throw new Exception(esc_html__('Event not found.', 'school-management'));
				}

				if (empty($title)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (!empty($event_date)) {
					$event_date = date('Y-m-d', strtotime($event_date));
				} else {
					$event_date = $event->event_date;
				}

				if (isset($image) && !empty($_FILES['image']['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($_FILES['image'], 'image')) {
						throw new Exception(esc_html__('Please provide event image in JPG, JPEG or PNG format.', 'school-management'));
					}

					if (!function_exists('media_handle_upload')) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';
					}

					$image_id = media_handle_upload('image', 0);
					if (is_wp_error($image_id)) {
						throw new Exception($image_id->get_error_message());
					}
				} else {
					$image_id = $event->image_id;
				}

				$data = array(
					'title' => $title,
					'description' => $description,
					'image_id' => $image_id,
					'event_date' => $event_date,
					'is_active' => $is_active,
					'school_id' => $school_id,
					'added_by' => $user_id
				);

				$data['updated_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_EVENTS, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Event Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit event.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Event.
	public static function delete_event($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_events', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$event_id = isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;
				$event = WLSM_M_Staff_Class::get_event($school_id, $event_id);

				if (empty($event)) {
					throw new Exception(esc_html__('Event not found.', 'school-management'));
				}
				$success = $wpdb->delete(WLSM_EVENTS, array('ID' => $event_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Event Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete event.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Class Time Table List.
	public static function class_time_table_list($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;
				$routines = WLSM_M::fetch_routines($school_id);

				if (empty($routines)) {
					throw new Exception(esc_html__('Routines not found.', 'school-management'));
				}

				if ($routines) {
					foreach ($routines as $routine) {
						global $wpdb;
						$data[] = array(
							'class_id' => isset($routine->class_id) ? WLSM_M_Staff_Class::get_label_text($routine->class_id) : '',
							'section_id' => isset($routine->section_id) ? WLSM_M_Staff_Class::get_label_text($routine->section_id) : '',
							'class_label' => isset($routine->class_label) ? WLSM_M_Staff_Class::get_label_text($routine->class_label) : '',
							'section_label' => isset($routine->section_label) ? WLSM_M_Staff_Class::get_label_text($routine->section_label) : '',
						);
					}
					$response_data['routines'] = $data;
				}

				$success = true;
				$message = esc_html__('Routines Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view routines.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Class Time Table.
	public static function view_class_time_table($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {

				$params = $request->get_params();
				$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
				$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				$response_data = array();

				global $wpdb;
				$routines = WLSM_M::fetch_class_section_routine($school_id, $class_id, $section_id);

				if (empty($routines)) {
					throw new Exception(esc_html__('Routines not found.', 'school-management'));
				}

				if ($routines) {
					foreach ($routines as $routine) {
						global $wpdb;
						$data[] = array(
							'ID' => isset($routine->ID) ? WLSM_M_Staff_Class::get_label_text($routine->ID) : '',
							'start_time' => isset($routine->start_time) ? WLSM_M_Staff_Class::get_label_text($routine->start_time) : '',
							'end_time' => isset($routine->end_time) ? WLSM_M_Staff_Class::get_label_text($routine->end_time) : '',
							'day' => isset($routine->day) ? WLSM_M_Staff_Class::get_day_text($routine->day) : '',
							'room_number' => isset($routine->room_number) ? WLSM_M_Staff_Class::get_label_text($routine->room_number) : '',
							'class_id' => isset($routine->class_id) ? WLSM_M_Staff_Class::get_label_text($routine->class_id) : '',
							'section_id' => isset($routine->section_id) ? WLSM_M_Staff_Class::get_label_text($routine->section_id) : '',
							'subject_id' => isset($routine->subject_id) ? WLSM_M_Staff_Class::get_label_text($routine->subject_id) : '',
							'class_label' => isset($routine->class_label) ? WLSM_M_Staff_Class::get_label_text($routine->class_label) : '',
							'section_label' => isset($routine->section_label) ? WLSM_M_Staff_Class::get_label_text($routine->section_label) : '',
							'subject_label' => isset($routine->subject_label) ? WLSM_M_Staff_Class::get_label_text($routine->subject_label) : '',
						);
					}
					$response_data['routines'] = $data;
				}

				$success = true;
				$message = esc_html__('Routines Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view routines.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Subject Teacher List.
	public static function subject_teacher_list($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = absint($staff->school_id);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {

			global $wpdb;
			$subject = WLSM_M_Staff_Class::get_subject($school_id, $subject_id);

			if (empty($subject)) {
				throw new Exception(esc_html__('Subject not found.', 'school-management'));
			}

			$subject_teachers = WLSM_M::get_subject_teachers($school_id, $subject_id);

			if (empty($subject_teachers)) {
				throw new Exception(esc_html__('Subject Teachers not found.', 'school-management'));
			}

			if ($subject_teachers) {
				foreach ($subject_teachers as $subject_teacher) {
					global $wpdb;
					$data[] = array(
						'ID' => isset($subject_teacher->ID) ? WLSM_M_Staff_Class::get_label_text($subject_teacher->ID) : '',
						'name' => isset($subject_teacher->name) ? WLSM_M_Staff_Class::get_label_text($subject_teacher->name) : '',
					);
				}
				$response_data['teachers'] = $data;
			}

			$success = true;
			$message = esc_html__('Subject Teachers Retrieved Successfully.', 'school-management');

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Class Time Table.
	public static function add_new_class_time_table($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$start_time = isset($params['start_time']) ? $params['start_time'] : '';
		$end_time = isset($params['end_time']) ? $params['end_time'] : '';
		$day = isset($params['day']) ? $params['day'] : '';
		$room_number = isset($params['room_number']) ? $params['room_number'] : '';
		$admin_id = isset($params['admin_id']) ? absint($params['admin_id']) : 0;

		try {

			if ($staff_permissions) {
				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($start_time)) {
					throw new Exception(esc_html__('Please select a start time.', 'school-management'));
				}

				if (empty($end_time)) {
					throw new Exception(esc_html__('Please select a end time.', 'school-management'));
				}

				if (empty($day)) {
					throw new Exception(esc_html__('Please select atleast a day.', 'school-management'));
				}

				global $wpdb;
				$data = array(
					'start_time' => $start_time,
					'end_time' => $end_time,
					'room_number' => $room_number,
					'section_id' => $section_id,
					'subject_id' => $subject_id,
					'admin_id' => $admin_id,
					'day' => $day,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_ROUTINES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Time Table Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new time table.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit Class Time Table.
	public static function edit_class_time_table($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id = isset($params['id']) ? absint($params['id']) : 0;
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$start_time = isset($params['start_time']) ? $params['start_time'] : '';
		$end_time = isset($params['end_time']) ? $params['end_time'] : '';
		$day = isset($params['day']) ? $params['day'] : '';
		$room_number = isset($params['room_number']) ? $params['room_number'] : '';
		$admin_id = isset($params['admin_id']) ? absint($params['admin_id']) : 0;

		try {

			if ($staff_permissions) {
				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($start_time)) {
					throw new Exception(esc_html__('Please select a start time.', 'school-management'));
				}

				if (empty($end_time)) {
					throw new Exception(esc_html__('Please select a end time.', 'school-management'));
				}

				if (empty($day)) {
					throw new Exception(esc_html__('Please select atleast a day.', 'school-management'));
				}

				global $wpdb;
				$data = array(
					'start_time' => $start_time,
					'end_time' => $end_time,
					'room_number' => $room_number,
					'section_id' => $section_id,
					'subject_id' => $subject_id,
					'admin_id' => $admin_id,
					'day' => $day,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_ROUTINES, $data, array('id' => $id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Time Table Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit time table.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Class Time Table.
	public static function delete_class_time_table($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$routine = WLSM_M::fetch_time_table_by_section_id($school_id, $section_id);

				if (empty($routine)) {
					throw new Exception(esc_html__('Routine not found.', 'school-management'));
				}
				$success = $wpdb->delete(WLSM_ROUTINES, array('section_id' => $section_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Routine Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete routine.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Staff Subject Class Time Table.
	public static function delete_staff_subject_class_time_table($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {

			if ($staff_permissions) {

				$params = $request->get_params();
				$id = isset($params['id']) ? absint($params['id']) : 0;

				global $wpdb;

				$routine = WLSM_M::get_time_table($school_id, $id);

				if (empty($routine)) {
					throw new Exception(esc_html__('Routine not found.', 'school-management'));
				}
				$success = $wpdb->delete(WLSM_ROUTINES, array('ID' => $id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Routine Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete routine.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View notices.
	public static function view_notices($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_notices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;
				$data = array();
				$response_data['notices'] = $data;

				$notices = WLSM_M::fetch_notices($school_id);
				if ($notices) {
					foreach ($notices as $notice) {
						$notice_data = unserialize($notice->notice_data);

						// if( in_array( $section_id, $notice_data['sections'], true ) ){
						$data[] = array(
							'ID' => isset($notice->ID) ? WLSM_M_Staff_Class::get_label_text($notice->ID) : '',
							'title' => isset($notice->title) ? WLSM_M_Staff_Class::get_label_text($notice->title) : '',
							'attachment' => isset($notice->attachment) ? wp_get_attachment_url($notice->attachment) : '',
							'url' => isset($notice->url) ? WLSM_M_Staff_Class::get_label_text($notice->url) : '',
							'link_to' => isset($notice->link_to) ? WLSM_M_Staff_Class::get_label_text($notice->link_to) : '',
							'description' => isset($notice->description) ? WLSM_M_Staff_Class::get_label_text($notice->description) : '',
							'date' => isset($notice->created_at) ? WLSM_M_Staff_Class::get_date_text($notice->created_at) : '',
							'added_by' => isset($notice->display_name) ? WLSM_M_Staff_Class::get_label_text($notice->display_name) : '',
						);
						// }
						$response_data['notices'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Notices Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view notices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Notice.
	public static function add_new_notice($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_notices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$notice_title = isset($params['title']) ? $params['title'] : '';
		$class_id = isset($params['class_id']) ? [$params['class_id']] : array();
		$section_id = isset($params['section_id']) ? [$params['section_id']] : array();
		$student_id = isset($params['student_id']) ? [$params['student_id']] : array();
		$description = isset($params['description']) ? $params['description'] : '';
		$link_to = isset($params['link_to']) ? $params['link_to'] : 'url';
		$attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : NULL;
		$url = isset($params['url']) ? $params['url'] : '';
		$is_active = isset($params['is_active']) ? $params['is_active'] : 1;

		try {

			if ($staff_permissions) {
				if (empty($notice_title)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				// if ( empty( $section_id ) ) {
				// 	throw new Exception( esc_html__( 'Please select a section.', 'school-management' ) );
				// }

				// if ( empty( $student_id ) ) {
				// 	throw new Exception( esc_html__( 'Please select a student.', 'school-management' ) );
				// }

				if (empty($description)) {
					throw new Exception(esc_html__('Please enter description.', 'school-management'));
				}

				if (isset($attachment) && !empty($_FILES['attachment']['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment)) {
						throw new Exception(esc_html__('Please provide attachment in PDF, JPG, JPEG or PNG format.', 'school-management'));
					}

					if (!function_exists('media_handle_upload')) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';
					}

					$attachment_id = media_handle_upload('attachment', 0);
					if (is_wp_error($attachment_id)) {
						throw new Exception($attachment_id->get_error_message());
					}
				} else {
					$attachment_id = NULL;
				}

				$notice_data = array(
					'classes' => $class_id,
					'sections' => $section_id,
					'students' => $student_id
				);

				$notice_data = serialize($notice_data);

				global $wpdb;
				$data = array(
					'title' => $notice_title,
					'attachment' => $attachment_id,
					'url' => $url,
					'link_to' => $link_to,
					'is_active' => $is_active,
					'school_id' => $school_id,
					'added_by' => $user_id,
					'description' => $description,
					'notice_data' => $notice_data,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_NOTICES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Notice Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new notice.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View notice by id.
	public static function view_notice_by_id($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_notices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$notice_id = isset($params['id']) ? absint($params['id']) : 0;

		try {
			if ($staff_permissions) {
				global $wpdb;

				$notice = WLSM_M::fetch_notice($school_id, $notice_id);
				if ($notice) {
					$notice_data = unserialize($notice->notice_data);

					$data = array(
						'ID' => isset($notice->ID) ? WLSM_M_Staff_Class::get_label_text($notice->ID) : '',
						'title' => isset($notice->title) ? WLSM_M_Staff_Class::get_label_text($notice->title) : '',
						'attachment' => isset($notice->attachment) ? wp_get_attachment_url($notice->attachment) : '',
						'url' => isset($notice->url) ? WLSM_M_Staff_Class::get_label_text($notice->url) : '',
						'link_to' => isset($notice->link_to) ? WLSM_M_Staff_Class::get_label_text($notice->link_to) : '',
						'description' => isset($notice->description) ? WLSM_M_Staff_Class::get_label_text($notice->description) : '',
						'added_by' => isset($notice->display_name) ? WLSM_M_Staff_Class::get_label_text($notice->display_name) : '',
					);
					$response_data['notice'] = $data;
				}

				$success = true;
				$message = esc_html__('Notice Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view notices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit Notice.
	public static function edit_notice($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_notices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id = isset($params['id']) ? $params['id'] : 0;
		$notice_title = isset($params['title']) ? $params['title'] : '';
		$class_id = isset($params['class_id']) ? [$params['class_id']] : array();
		$section_id = isset($params['section_id']) ? [$params['section_id']] : array();
		$student_id = isset($params['student_id']) ? [$params['student_id']] : array();
		$description = isset($params['description']) ? $params['description'] : '';
		$link_to = isset($params['link_to']) ? $params['link_to'] : 'url';
		$attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : NULL;
		$url = isset($params['url']) ? $params['url'] : '';
		$is_active = isset($params['is_active']) ? $params['is_active'] : 1;

		try {

			if ($staff_permissions) {
				if (empty($notice_title)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				// if ( empty( $section_id ) ) {
				// 	throw new Exception( esc_html__( 'Please select a section.', 'school-management' ) );
				// }

				// if ( empty( $student_id ) ) {
				// 	throw new Exception( esc_html__( 'Please select a student.', 'school-management' ) );
				// }

				if (empty($description)) {
					throw new Exception(esc_html__('Please enter description.', 'school-management'));
				}

				if (isset($attachment) && !empty($_FILES['attachment']['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment)) {
						throw new Exception(esc_html__('Please provide attachment in PDF, JPG, JPEG or PNG format.', 'school-management'));
					}

					if (!function_exists('media_handle_upload')) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';
					}

					$attachment_id = media_handle_upload('attachment', 0);
					if (is_wp_error($attachment_id)) {
						throw new Exception($attachment_id->get_error_message());
					}
				} else {
					$attachment_id = NULL;
				}

				$notice_data = array(
					'classes' => $class_id,
					'sections' => $section_id,
					'students' => $student_id
				);

				$notice_data = serialize($notice_data);

				global $wpdb;
				$data = array(
					'title' => $notice_title,
					'attachment' => $attachment_id,
					'url' => $url,
					'link_to' => $link_to,
					'is_active' => $is_active,
					'school_id' => $school_id,
					'added_by' => $user_id,
					'description' => $description,
					'notice_data' => $notice_data,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_NOTICES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Notice Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit notice.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Notice.
	public static function delete_notice($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_notices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$notice_id = isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;
				$notice = WLSM_M_Staff_Class::get_notice($school_id, $notice_id);

				if (empty($notice)) {
					throw new Exception(esc_html__('Notice not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_NOTICES, array('ID' => $notice_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Notice Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete notice.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Homeworks.
	public static function view_homeworks($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_homework', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {

				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = absint($session->ID);

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$homeworks = WLSM_M::fetch_homeworks($school_id, $session_id, $restrict_to_section);

				if ($homeworks) {
					foreach ($homeworks as $homework) {

						$data[] = array(
							'ID' => isset($homework->ID) ? WLSM_M_Staff_Class::get_label_text($homework->ID) : '',
							'title' => isset($homework->title) ? WLSM_M_Staff_Class::get_label_text($homework->title) : '',
							'description' => isset($homework->description) ? WLSM_M_Staff_Class::get_label_text($homework->description) : '',
							'class_label' => isset($homework->class_label) ? WLSM_M_Staff_Class::get_label_text($homework->class_label) : '',
							'homework_date' => isset($homework->homework_date) ? WLSM_Config::get_date_text($homework->homework_date) : '',
							'homework_due_date' => isset($homework->homework_due_date) ? WLSM_Config::get_date_text($homework->homework_due_date) : '',
							'added_by' => isset($homework->display_name) ? WLSM_M_Staff_Class::get_label_text($homework->display_name) : '',
						);
						$response_data['homeworks'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Homeworks Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view homeworks.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Homework.
	public static function add_new_homework($request)
	{
		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_homework', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$homework_title   = isset($params['title']) ? $params['title'] : '';
		$class_id         = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id       = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$date             = isset($params['date']) ? $params['date'] : '';
		$due_date         = isset($params['due_date']) ? $params['due_date'] : '';
		$subject_id       = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$description      = isset($params['description']) ? $params['description'] : '';
		$homework 		  = isset($_FILES['homework']) && is_array($_FILES['homework']) ? $_FILES['homework'] : '';
		$is_downloadable  = isset($params['is_downloadable']) ? absint($params['is_downloadable']) : 0;
		$sms_to_students  = isset($params['sms_to_students']) ? absint($params['sms_to_students']) : 1;
		$sms_to_parents   = isset($params['sms_to_parents']) ? absint($params['sms_to_parents']) : 0;

		try {
			if ($staff_permissions) {
				if (empty($homework_title)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (!empty($date)) {
					$date = date('Y-m-d', strtotime($date));
				} else {
					$date = NULL;
				}

				if (!empty($due_date)) {
					$due_date = date('Y-m-d', strtotime($due_date));
				} else {
					$due_date = NULL;
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (isset($homework) && is_array($homework)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					// Handle Multiple Attachments
					$attachment_ids = array();
					if (isset($homework) && is_array($homework)) {
						foreach ($_FILES['homework']['name'] as $index => $file_name) {
							if (!empty($_FILES['homework']['tmp_name'][$index])) {
								$file_array = array(
									'name' => $_FILES['homework']['name'][$index],
									'type' => $_FILES['homework']['type'][$index],
									'tmp_name' => $_FILES['homework']['tmp_name'][$index],
									'error' => $_FILES['homework']['error'][$index],
									'size' => $_FILES['homework']['size'][$index],
								);

								if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
									throw new Exception(esc_html__('Invalid file format. Only PDF/DOC allowed.', 'school-management'));
								}

								$_FILES['homework_single'] = $file_array;
								$attachment_id = media_handle_upload('homework_single', 0);

								if (is_wp_error($attachment_id)) {
									throw new Exception($attachment_id->get_error_message());
								}

								$attachment_ids[] = $attachment_id;
							}
						}
					}
					$serialized_attachments = !empty($attachment_ids) ? serialize($attachment_ids) : '';
				} else {
					$serialized_attachments = '';
				}

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);

				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				$students = array();
				if ($section_id && ($sms_to_students || $sms_to_parents)) {
					$students = WLSM_M_Staff_General::fetch_class_section_subject_students($school_id, $session_id, $section_id, $subject_id);
				}

				global $wpdb;
				$data = array(
					'title' => $homework_title,
					'description' => $description,
					'attachments' => $serialized_attachments,
					'homework_date' => $date,
					'homework_due_date' => $due_date,
					'added_by' => $user_id,
					'session_id' => $session_id,
					'school_id' => $school_id,
					'downloadable' => $is_downloadable,
					'subject' => $subject_id,
					'created_at' => current_time('Y-m-d H:i:s')
				);

				$success = $wpdb->insert(WLSM_HOMEWORK, $data);
				$homework_id = $wpdb->insert_id;

				if ($homework_id) {
					$homework_section_data = array(
						'homework_id' => $homework_id,
						'section_id' => $section_id,
						'created_at' => current_time('Y-m-d H:i:s'),
					);

					$success = $wpdb->insert(WLSM_HOMEWORK_SECTION, $homework_section_data);
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				foreach ($students as $student) {
					$student_id = $student->ID;

					$data = array(
						'school_id' => $school_id,
						'student_id' => $student_id,
						'sms' => array(
							'message' => $description,
							'to_student' => $sms_to_students,
							'to_parent' => $sms_to_parents,
						),
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_homework_message', $data);
				}

				$response_data = array();
				$success = true;
				$message = esc_html__('Homework Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new homework.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Homework by ID.
	public static function view_homework_by_id($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_homework', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params = $request->get_params();
				$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;

				if (empty($homework_id)) {
					throw new Exception(esc_html__('Please provide homework ID.', 'school-management'));
				}

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				$homework = WLSM_M::fetch_homework($school_id, $session_id, $homework_id);

				if ($homework) {
					$data = array();
					$homework_attachments = array(); // Initialize the variable

					$attachments = maybe_unserialize($homework->attachments);
					if (!empty($attachments) && is_array($attachments)) {
						foreach ($attachments as $attachment_id) {
							$attachment_url = wp_get_attachment_url($attachment_id);
							if ($attachment_url) {
								$homework_attachments['URL'][] = $attachment_url; // Append to array instead of overwriting
							}
						}
					} else {
						$homework_attachments['URL'] = array(''); // Initialize as empty array
					}

					$data[] = array(
						'ID' => isset($homework->ID) ? WLSM_M_Staff_Class::get_label_text($homework->ID) : '',
						'title' => isset($homework->title) ? WLSM_M_Staff_Class::get_label_text($homework->title) : '',
						'class_id' => isset($homework->class_id) ? absint($homework->class_id) : 0,
						'section_id' => isset($homework->section_id) ? absint($homework->section_id) : 0,
						'subject_id' => isset($homework->subject_id) ? absint($homework->subject_id) : 0,
						'class_label' => isset($homework->class_label) ? WLSM_M_Staff_Class::get_label_text($homework->class_label) : '',
						'section_label' => isset($homework->section_label) ? WLSM_M_Staff_Class::get_label_text($homework->section_label) : '',
						'subject_label' => isset($homework->subject_label) ? WLSM_M_Staff_Class::get_label_text($homework->subject_label) : '',
						'date' => isset($homework->homework_date) ? WLSM_Config::get_date_text($homework->homework_date) : '',
						'due_date' => isset($homework->homework_due_date) ? WLSM_Config::get_date_text($homework->homework_due_date) : '',
						'description' => isset($homework->description) ? WLSM_M_Staff_Class::get_label_text($homework->description) : '',
						'homework' => $homework_attachments, // Removed isset check since we always define it now
						'is_downloadable' => isset($homework->downloadable) ? WLSM_M_Staff_Class::get_label_text($homework->downloadable) : '',
					);
					$response_data['homework'] = $data;
				} else {
					throw new Exception(esc_html__('Homework not found.', 'school-management'));
				}

				$success = true;
				$message = esc_html__('Homework Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view homeworks.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit Homework.
	public static function edit_homework($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_homework', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id = isset($params['id']) ? absint($params['id']) : 0;
		$homework_title = isset($params['title']) ? $params['title'] : '';
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$date = isset($params['date']) ? $params['date'] : '';
		$due_date = isset($params['due_date']) ? $params['due_date'] : '';
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$description = isset($params['description']) ? $params['description'] : '';
		$homework = isset($_FILES['homework']) && is_array($_FILES['homework']) ? $_FILES['homework'] : '';
		$is_downloadable = isset($params['is_downloadable']) ? absint($params['is_downloadable']) : 0;
		$sms_to_students = isset($params['sms_to_students']) ? absint($params['sms_to_students']) : 1;
		$sms_to_parents = isset($params['sms_to_parents']) ? absint($params['sms_to_parents']) : 0;

		try {

			if ($staff_permissions) {
				if (empty($homework_title)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (!empty($date)) {
					$date = date('Y-m-d', strtotime($date));
				} else {
					$date = NULL;
				}

				if (!empty($due_date)) {
					$due_date = date('Y-m-d', strtotime($due_date));
				} else {
					$due_date = NULL;
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (isset($homework) && is_array($homework)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					// Handle Multiple Attachments
					$attachment_ids = array();
					if (isset($homework) && is_array($homework)) {
						foreach ($_FILES['homework']['name'] as $index => $file_name) {
							if (!empty($_FILES['homework']['tmp_name'][$index])) {
								$file_array = array(
									'name' => $_FILES['homework']['name'][$index],
									'type' => $_FILES['homework']['type'][$index],
									'tmp_name' => $_FILES['homework']['tmp_name'][$index],
									'error' => $_FILES['homework']['error'][$index],
									'size' => $_FILES['homework']['size'][$index],
								);

								if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
									throw new Exception(esc_html__('Invalid file format. Only PDF/DOC allowed.', 'school-management'));
								}

								$_FILES['homework_single'] = $file_array;
								$attachment_id = media_handle_upload('homework_single', 0);

								if (is_wp_error($attachment_id)) {
									throw new Exception($attachment_id->get_error_message());
								}

								$attachment_ids[] = $attachment_id;
							}
						}
					}
					$serialized_attachments = !empty($attachment_ids) ? serialize($attachment_ids) : '';
				} else {
					$serialized_attachments = '';
				}

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				$students = array();
				if ($section_id && ($sms_to_students || $sms_to_parents)) {
					$students = WLSM_M_Staff_General::fetch_class_section_subject_students($school_id, $session_id, $section_id, $subject_id);
				}

				global $wpdb;
				$data = array(
					'title' => $homework_title,
					'description' => $description,
					'attachments' => $serialized_attachments,
					'homework_date' => $date,
					'homework_due_date' => $due_date,
					'added_by' => $user_id,
					'session_id' => $session_id,
					'school_id' => $school_id,
					'downloadable' => $is_downloadable,
					'subject' => $subject_id
				);

				$data['updated_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_HOMEWORK, $data, array('ID' => $id, 'school_id' => $school_id));

				if ($id) {
					$homework_section_data = array(
						'homework_id' => $id,
						'section_id' => $section_id
					);

					$homework_section_data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_HOMEWORK_SECTION, $homework_section_data, array('homework_id' => $id));
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				foreach ($students as $student) {
					$student_id = $student->ID;

					$data = array(
						'school_id' => $school_id,
						'student_id' => $student_id,
						'sms' => array(
							'message' => $description,
							'to_student' => $sms_to_students,
							'to_parent' => $sms_to_parents
						)
					);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_homework_message', $data);
				}

				$response_data = array();

				$success = true;
				$message = esc_html__('Homework Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit homework.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Homework.
	public static function delete_homework($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_homework', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$homework_id = isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				global $wpdb;
				$homework = WLSM_M_Staff_Class::get_homework($school_id, $session_id, $homework_id);

				if (empty($homework)) {
					throw new Exception(esc_html__('Homework not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_HOMEWORK, array('ID' => $homework_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Homework Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete homework.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Homework submitted.
	public static function view_homework_submitted($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_homework', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params = $request->get_params();
				$homework_id = isset($params['homework_id']) ? absint($params['homework_id']) : 0;

				if (empty($homework_id)) {
					throw new Exception(esc_html__('Please provide homework ID.', 'school-management'));
				}

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				$homework_submissions = WLSM_M::fetch_homework_submissions($school_id, $session_id, $homework_id);

				if ($homework_submissions) {

					foreach ($homework_submissions as $homework) {
						global $wpdb;
						$data = array(
							'student_name' => isset($homework->student_name) ? WLSM_M_Staff_Class::get_label_text($homework->student_name) : '',
							'roll_number' => isset($homework->roll_number) ? WLSM_M_Staff_Class::get_label_text($homework->roll_number) : '',
							'class_label' => isset($homework->class_label) ? WLSM_M_Staff_Class::get_label_text($homework->class_label) : '',
							'added' => isset($homework->created_at) ? WLSM_M_Staff_Class::get_date_text($homework->created_at) : '',
							'title' => isset($homework->title) ? WLSM_M_Staff_Class::get_label_text($homework->title) : '',
							'description' => isset($homework->description) ? WLSM_M_Staff_Class::get_label_text($homework->description) : '',
							'submission' => isset($homework->attachments) ? wp_get_attachment_url($homework->attachments) : '',
							'response' => isset($homework->response) ? WLSM_M_Staff_Class::get_label_text($homework->response) : '',
						);
						$response_data['homework_submissions'][] = $data;
					}
				} else {
					throw new Exception(esc_html__('Homework Submission not found.', 'school-management'));
				}

				$success = true;
				$message = esc_html__('Homework Submission Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view homeworks.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Study Materials.
	public static function view_study_materials($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_study_materials', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$study_materials = WLSM_M::fetch_study_materials($school_id, $restrict_to_section);

				if ($study_materials) {
					foreach ($study_materials as $study_material) {
						$data[] = array(
							'ID' => isset($study_material->ID) ? WLSM_M_Staff_Class::get_label_text($study_material->ID) : '',
							'label' => isset($study_material->label) ? WLSM_M_Staff_Class::get_label_text($study_material->label) : '',
							'class_label' => isset($study_material->class_label) ? WLSM_M_Staff_Class::get_label_text($study_material->class_label) : '',
							'subject_label' => isset($study_material->subject_label) ? WLSM_M_Staff_Class::get_label_text($study_material->subject_label) : '',
							'description' => isset($study_material->description) ? WLSM_M_Staff_Class::get_label_text($study_material->description) : '',
							'date_added' => isset($study_material->created_at) ? WLSM_Config::get_date_text($study_material->created_at) : '',
							'added_by' => isset($study_material->display_name) ? WLSM_M_Staff_Class::get_label_text($study_material->display_name) : '',
						);
						$response_data['study_materials'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Study Materials Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view study materials.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Study Material.
	public static function add_new_study_material($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_study_materials', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$label 			= isset($params['label']) ? $params['label'] : '';
		$description 	= isset($params['description']) ? $params['description'] : '';
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$url 			= isset($params['url']) ? $params['url'] : '';
		$downloadable 	= isset($params['downloadable']) ? absint($params['downloadable']) : 0;
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id 	= isset($params['section_id']) ? absint($params['section_id']) : 0;
		$subject_id 	= isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {

			if ($staff_permissions) {
				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				$class_school_id = WLSM_M_Staff_Class::get_class_school_id($school_id, $class_id);

				if (empty($class_school_id)) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					// Handle Multiple Attachments
					$attachment_ids = array();
					if (isset($attachment) && is_array($attachment)) {
						foreach ($_FILES['attachment']['name'] as $index => $file_name) {
							if (!empty($_FILES['attachment']['tmp_name'][$index])) {
								$file_array = array(
									'name' => $_FILES['attachment']['name'][$index],
									'type' => $_FILES['attachment']['type'][$index],
									'tmp_name' => $_FILES['attachment']['tmp_name'][$index],
									'error' => $_FILES['attachment']['error'][$index],
									'size' => $_FILES['attachment']['size'][$index],
								);

								if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
									throw new Exception(esc_html__('Invalid file format. Only PDF/DOC allowed.', 'school-management'));
								}

								$_FILES['attachment_single'] = $file_array;
								$attachment_id = media_handle_upload('attachment_single', 0);

								if (is_wp_error($attachment_id)) {
									throw new Exception($attachment_id->get_error_message());
								}

								$attachment_ids[] = $attachment_id;
							}
						}
					}
					$serialized_attachments = !empty($attachment_ids) ? serialize($attachment_ids) : '';
				} else {
					$serialized_attachments = '';
				}

				global $wpdb;
				$data = array(
					'label' => $label,
					'description' => $description,
					'attachments' => $serialized_attachments,
					'added_by' => $user_id,
					'school_id' => $school_id,
					'url' => $url,
					'downloadable' => $downloadable,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_STUDY_MATERIALS, $data);

				$study_material_id = $wpdb->insert_id;

				if ($study_material_id) {
					$study_material_id_section_data = array(
						'class_school_id' => $class_school_id,
						'study_material_id' => $study_material_id,
						'study_material_section_id' => $section_id,
						'study_material_subject_id' => $subject_id
					);

					$study_material_id_section_data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_CLASS_SCHOOL_STUDY_MATERIAL, $study_material_id_section_data);
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Study Material Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new study material.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Study Material By Id.
	public static function view_study_material_by_id($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_study_materials', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {

				global $wpdb;

				$params = $request->get_params();
				$id = isset($params['id']) ? absint($params['id']) : 0;

				$study_material = WLSM_M::fetch_study_material($school_id, $id);

				if ($study_material) {

					$sm_attachments = array();
					$attachments = maybe_unserialize($study_material->attachments);
					if (!empty($attachments) && is_array($attachments)) {
						foreach ($attachments as $attachment_id) {
							$attachment_url = wp_get_attachment_url($attachment_id);
							if ($attachment_url) {
								$sm_attachments[] = $attachment_url; // Append to array instead of overwriting
							}
						}
					}

					$data = array(
						'ID' => isset($study_material->ID) ? WLSM_M_Staff_Class::get_label_text($study_material->ID) : '',
						'label' => isset($study_material->label) ? WLSM_M_Staff_Class::get_label_text($study_material->label) : '',
						'description' => isset($study_material->description) ? WLSM_M_Staff_Class::get_label_text($study_material->description) : '',
						'attachments' => $sm_attachments,
						'url' => isset($study_material->url) ? WLSM_M_Staff_Class::get_label_text($study_material->url) : '',
						'downloadable' => isset($study_material->downloadable) ? absint($study_material->downloadable) : 0,
						'class_id' => isset($study_material->class_id) ? absint($study_material->class_id) : 0,
						'section_id' => isset($study_material->section_id) ? absint($study_material->section_id) : 0,
						'subject_id' => isset($study_material->subject_id) ? absint($study_material->subject_id) : 0,
						'class_label' => isset($study_material->class_label) ? WLSM_M_Staff_Class::get_label_text($study_material->class_label) : '',
						'subject_label' => isset($study_material->subject_label) ? WLSM_M_Staff_Class::get_label_text($study_material->subject_label) : '',
						'date_added' => isset($study_material->created_at) ? WLSM_Config::get_date_text($study_material->created_at) : '',
						'added_by' => isset($study_material->display_name) ? WLSM_M_Staff_Class::get_label_text($study_material->display_name) : '',
					);
					$response_data['study_material'] = $data;
				}

				$success = true;
				$message = esc_html__('Study Material Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view study materials.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit Study Material.
	public static function edit_study_material($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_study_materials', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$id = isset($params['id']) ? absint($params['id']) : 0;
		$label = isset($params['label']) ? $params['label'] : '';
		$description = isset($params['description']) ? $params['description'] : '';
		$attachment = isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$url = isset($params['url']) ? $params['url'] : '';
		$downloadable = isset($params['downloadable']) ? absint($params['downloadable']) : 0;
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				$class_school_id = WLSM_M_Staff_Class::get_class_school_id($school_id, $class_id);

				if (empty($class_school_id)) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					// Handle Multiple Attachments
					$attachment_ids = array();
					if (isset($attachment) && is_array($attachment)) {
						foreach ($_FILES['attachment']['name'] as $index => $file_name) {
							if (!empty($_FILES['attachment']['tmp_name'][$index])) {
								$file_array = array(
									'name' => $_FILES['attachment']['name'][$index],
									'type' => $_FILES['attachment']['type'][$index],
									'tmp_name' => $_FILES['attachment']['tmp_name'][$index],
									'error' => $_FILES['attachment']['error'][$index],
									'size' => $_FILES['attachment']['size'][$index],
								);

								if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
									throw new Exception(esc_html__('Invalid file format. Only PDF/DOC allowed.', 'school-management'));
								}

								$_FILES['attachment_single'] = $file_array;
								$attachment_id = media_handle_upload('attachment_single', 0);

								if (is_wp_error($attachment_id)) {
									throw new Exception($attachment_id->get_error_message());
								}

								$attachment_ids[] = $attachment_id;
							}
						}
					}
					$serialized_attachments = !empty($attachment_ids) ? serialize($attachment_ids) : '';
				} else {
					$serialized_attachments = '';
				}

				$data = array(
					'label' => $label,
					'description' => $description,
					'attachments' => $serialized_attachments,
					'added_by' => $user_id,
					'school_id' => $school_id,
					'url' => $url,
					'downloadable' => $downloadable,
				);

				$data['updated_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_STUDY_MATERIALS, $data, array('ID' => $id, 'school_id' => $school_id));

				if ($id) {
					$study_material_id_section_data = array(
						'class_school_id' => $class_school_id,
						'study_material_id' => $id,
						'study_material_section_id' => $section_id,
						'study_material_subject_id' => $subject_id
					);

					$study_material_id_section_data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_CLASS_SCHOOL_STUDY_MATERIAL, $study_material_id_section_data, array('study_material_id' => $id, ));
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Study Material Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit study material.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Study Material.
	public static function delete_study_material($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_study_materials', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$study_material_id = isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {

				global $wpdb;
				$study_material = WLSM_M_Staff_Class::get_study_material($school_id, $study_material_id);

				if (empty($study_material)) {
					throw new Exception(esc_html__('Study material not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_STUDY_MATERIALS, array('ID' => $study_material_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Study Material Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete study material.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Staff Time Table.
	public static function view_staff_time_table($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_timetable', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;
				$routines = WLSM_M::fetch_staff_routines($school_id, $user_id);

				if (empty($routines)) {
					throw new Exception(esc_html__('Routines not found.', 'school-management'));
				}

				if ($routines) {
					foreach ($routines as $routine) {
						global $wpdb;
						$data[] = array(
							'ID' => isset($routine->ID) ? WLSM_M_Staff_Class::get_label_text($routine->ID) : '',
							'start_time' => isset($routine->start_time) ? date('h:i a', strtotime($routine->start_time)) : '',
							'end_time' => isset($routine->end_time) ? date('h:i a', strtotime($routine->end_time)) : '',
							'day' => isset($routine->day) ? WLSM_Helper::days_list($routine->day) : '',
							'room_number' => isset($routine->room_number) ? WLSM_M_Staff_Class::get_label_text($routine->room_number) : '',
							'class_id' => isset($routine->class_id) ? WLSM_M_Staff_Class::get_label_text($routine->class_id) : '',
							'section_id' => isset($routine->section_id) ? WLSM_M_Staff_Class::get_label_text($routine->section_id) : '',
							'subject_id' => isset($routine->subject_id) ? WLSM_M_Staff_Class::get_label_text($routine->subject_id) : '',
							'class_label' => isset($routine->class_label) ? WLSM_M_Staff_Class::get_label_text($routine->class_label) : '',
							'section_label' => isset($routine->section_label) ? WLSM_M_Staff_Class::get_label_text($routine->section_label) : '',
							'subject_label' => isset($routine->subject_label) ? WLSM_M_Staff_Class::get_label_text($routine->subject_label) : '',
						);
					}
					$response_data['routines'] = $data;
				}

				$success = true;
				$message = esc_html__('Staff Routines Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view staff routines.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Student Leaves.
	public static function view_student_leaves($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_student_leaves', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;

				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$student_leaves = WLSM_M::fetch_student_leaves($school_id, $restrict_to_section);

				if ($student_leaves) {
					foreach ($student_leaves as $student_leave) {
						$leave_start_date = date('d-m-Y', strtotime($student_leave->start_date));
						$leave_end_date = isset($student_leave->end_date) ? date('d-m-Y', strtotime($student_leave->end_date)) : '';
						$status = isset($student_leave->is_approved) ? $student_leave->is_approved : 0;

						if ($leave_start_date && $leave_end_date) {
							$leave_date = $leave_start_date . ' to ' . $leave_end_date;
						} else {
							$leave_date = isset($leave_start_date) ? $leave_start_date : '';
						}

						if ($status == 1) {
							$status = 'Approved';
						} else {
							$status = 'Unapproved';
						}

						$data[] = array(
							'ID' => isset($student_leave->ID) ? WLSM_M_Staff_Class::get_label_text($student_leave->ID) : 0,
							'enrollment_number' => isset($student_leave->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($student_leave->enrollment_number) : '',
							'name' => isset($student_leave->name) ? WLSM_M_Staff_Class::get_label_text($student_leave->name) : '',
							'class_label' => isset($student_leave->class_label) ? WLSM_M_Staff_Class::get_label_text($student_leave->class_label) : '',
							'section_label' => isset($student_leave->section_label) ? WLSM_M_Staff_Class::get_label_text($student_leave->section_label) : '',
							'reason' => isset($student_leave->reason) ? WLSM_M_Staff_Class::get_label_text($student_leave->reason) : '',
							'leave_date' => isset($leave_date) ? $leave_date : '',
							'status' => $status,
						);
						$response_data['student_leaves'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Student Leaves Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view student leaves.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);

	}

	// Staff - Add New Student Leave.
	public static function add_new_student_leave($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_student_leaves', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
		$leave_type = isset($params['leave_type']) ? $params['leave_type'] : 'multiple';
		$reason = isset($params['reason']) ? $params['reason'] : '';
		$start_date = isset($params['start_date']) ? $params['start_date'] : NULL;
		$end_date = isset($params['end_date']) ? $params['end_date'] : NULL;
		$status = isset($params['status']) ? absint($params['status']) : 0;

		try {

			if ($staff_permissions) {

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($student_id)) {
					throw new Exception(esc_html__('Please select a student.', 'school-management'));
				}

				if ($leave_type == 'multiple') {
					if (empty($start_date)) {
						throw new Exception(esc_html__('Please select a start date.', 'school-management'));
					} else {
						$start_date = date('Y-m-d', strtotime($start_date));
					}

					if (empty($end_date)) {
						throw new Exception(esc_html__('Please select a end date.', 'school-management'));
					} else {
						$end_date = date('Y-m-d', strtotime($end_date));
					}
				} else {
					if (empty($start_date)) {
						throw new Exception(esc_html__('Please select a leave date.', 'school-management'));
					} else {
						$start_date = date('Y-m-d', strtotime($start_date));
					}

					$end_date = NULL;
				}

				global $wpdb;
				$data = array(
					'description' => $reason,
					'start_date' => $start_date,
					'end_date' => $end_date,
					'is_approved' => $status,
					'student_record_id' => $student_id,
					'admin_id' => NULL,
					'school_id' => $school_id,
					'approved_by' => NULL,
					'added_by' => $user_id
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_LEAVES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Student Leave Added Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new student leave.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - View Student Leave By id.
	public static function student_leave($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_student_leaves', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$leave_id = isset($params['leave_id']) ? absint($params['leave_id']) : 0;

		try {

			if ($staff_permissions) {

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				global $wpdb;
				$student_leave = WLSM_M_Staff_Class::fetch_student_leave($school_id, $session_id, $leave_id);

				if (empty($student_leave)) {
					throw new Exception(esc_html__('Student leave not found.', 'school-management'));
				}

				$response_data = array();

				$leave_start_date = date('d-m-Y', strtotime($student_leave->start_date));
				$leave_end_date = isset($student_leave->end_date) ? date('d-m-Y', strtotime($student_leave->end_date)) : '';
				$status = isset($student_leave->is_approved) ? $student_leave->is_approved : 0;

				if ($leave_start_date && $leave_end_date) {
					$leave_date = isset($leave_start_date) ? $leave_start_date : '';
					$end_date = $leave_end_date;
					$leave_type = 'multiple';
				} else {
					$leave_date = isset($leave_start_date) ? $leave_start_date : '';
					$end_date = NULL;
					$leave_type = 'single';
				}

				$data[] = array(
					'student_name' => isset($student_leave->student_name) ? WLSM_M_Staff_Class::get_label_text($student_leave->student_name) : '',
					'enrollment_number' => isset($student_leave->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($student_leave->enrollment_number) : '',
					'leave_type' => isset($leave_type) ? WLSM_M_Staff_Class::get_label_text($leave_type) : '',
					'start_date' => isset($leave_date) ? $leave_date : '',
					'end_date' => isset($end_date) ? $end_date : '',
					'reason' => isset($student_leave->reason) ? WLSM_M_Staff_Class::get_label_text($student_leave->reason) : '',
					'status' => $status,
				);

				$response_data['student_leave'] = $data;

				$success = true;
				$message = esc_html__('Student Leave Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');


			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit student leave.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);

	}

	// Staff - Edit Student Leave.
	public static function edit_student_leave($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_student_leaves', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$leave_id = isset($params['leave_id']) ? absint($params['leave_id']) : 0;
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
		$leave_type = isset($params['leave_type']) ? $params['leave_type'] : 'multiple';
		$reason = isset($params['reason']) ? $params['reason'] : '';
		$start_date = isset($params['start_date']) ? $params['start_date'] : NULL;
		$end_date = isset($params['end_date']) ? $params['end_date'] : NULL;
		$status = isset($params['status']) ? absint($params['status']) : 0;

		try {

			if ($staff_permissions) {

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				if (empty($student_id)) {
					throw new Exception(esc_html__('Please select a student.', 'school-management'));
				}

				if ($leave_type == 'multiple') {
					if (empty($start_date)) {
						throw new Exception(esc_html__('Please select a start date.', 'school-management'));
					} else {
						$start_date = date('Y-m-d', strtotime($start_date));
					}

					if (empty($end_date)) {
						throw new Exception(esc_html__('Please select a end date.', 'school-management'));
					} else {
						$end_date = date('Y-m-d', strtotime($end_date));
					}
				} else {
					if (empty($start_date)) {
						throw new Exception(esc_html__('Please select a leave date.', 'school-management'));
					} else {
						$start_date = date('Y-m-d', strtotime($start_date));
					}

					$end_date = NULL;
				}

				global $wpdb;
				$data = array(
					'description' => $reason,
					'start_date' => $start_date,
					'end_date' => $end_date,
					'is_approved' => $status,
					'student_record_id' => $student_id,
					'admin_id' => NULL,
					'school_id' => $school_id,
					'approved_by' => NULL,
					'added_by' => $user_id
				);

				$data['updated_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->update(WLSM_LEAVES, $data, array('ID' => $leave_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Student Leave Updated Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit student leave.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - Delete Student Leave.
	public static function delete_student_leave($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_student_leaves', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$student_leave_id = isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				global $wpdb;
				$student_leave = WLSM_M_Staff_Class::get_student_leave($school_id, $session_id, $student_leave_id);

				if (empty($student_leave)) {
					throw new Exception(esc_html__('Student leave not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_LEAVES, array('ID' => $student_leave_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Student Leave Deleted Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete student leave.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - View Subject Types.
	public static function view_subject_types($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {

			if ($staff_permissions) {

				global $wpdb;
				$subject_types = WLSM_M::fetch_subject_types();

				if (empty($subject_types)) {
					throw new Exception(esc_html__('Subject types not found.', 'school-management'));
				}

				foreach ($subject_types as $type) {
					$data = array(
						'ID' => isset($type->ID) ? $type->ID : '',
						'label' => isset($type->label) ? $type->label : '',
					);
					$types_array[] = $data;
				}

				$response_data['subject_types'] = $types_array;

				$success = true;
				$message = esc_html__('Subject Types Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view subject.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);

	}

	// Staff - Add New Subject Type.
	public static function add_new_subject_type($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = $staff->school_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$label = isset($params['subject_type']) ? $params['subject_type'] : NULL;

		try {

			if ($staff_permissions) {

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a subject type.', 'school-management'));
				}

				$subject_type = WLSM_M_Staff_Class::get_subject_type_by_label($label);

				if (!empty($subject_type)) {
					throw new Exception(esc_html__('Subject type already exists.', 'school-management'));
				}

				global $wpdb;
				$data = array(
					'label' => $label,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_SUBJECT_TYPES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Subject Type Added Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new subject type.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - Delete Subject Type.
	public static function delete_subject_type($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$subject_type_id = isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {

				global $wpdb;
				$subject_type = WLSM_M_Staff_Class::get_subject_type_by_id($subject_type_id);

				if (empty($subject_type)) {
					throw new Exception(esc_html__('Subject type not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_SUBJECT_TYPES, array('ID' => $subject_type_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Subject Type Deleted Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete subject type.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - View Subjects.
	public static function view_subjects($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id = $staff->section_id;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = $session->ID;

				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
					$subjects = WLSM_M::fetch_subjects($school_id, $session_id, $restrict_to_section);
				}

				$current_user = WLSM_M_Role::can('assigned_subjects');
				if ($current_user) {
					$subjects = WLSM_M::fetch_staff_subjects($user_id);
				}

				if (!$current_user) {
					$restrict_to_section = false;
					$subjects = WLSM_M::fetch_subjects($school_id, $session_id, $restrict_to_section);
				}

				if ($subjects) {
					foreach ($subjects as $subject) {


						$data[] = array(
							'ID' => isset($subject->ID) ? WLSM_M_Staff_Class::get_label_text($subject->ID) : 0,
							'subject_name' => isset($subject->label) ? WLSM_M_Staff_Class::get_label_text($subject->label) : '',
							'subject_code' => isset($subject->code) ? WLSM_M_Staff_Class::get_label_text($subject->code) : '',
							'subject_type' => isset($subject->type) ? WLSM_M_Staff_Class::get_label_text($subject->type) : '',
							'class_label' => isset($subject->class_label) ? WLSM_M_Staff_Class::get_label_text($subject->class_label) : '',
							'teacher' => isset($subject->teacher) ? absint($subject->teacher) : '',
						);
						$response_data['subjects'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Subjects Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view subjects.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);

	}

	// Staff - Add New Subject.
	public static function add_new_subject($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('add_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$name = isset($params['name']) ? $params['name'] : '';
		$code = isset($params['code']) ? $params['code'] : '';
		$type = isset($params['type']) ? $params['type'] : '';
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;

		try {

			if ($staff_permissions) {

				global $wpdb;

				if (empty($name)) {
					throw new Exception(esc_html__('Please enter a subject name.', 'school-management'));
				}

				if (empty($type)) {
					throw new Exception(esc_html__('Please select a subject type.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				$class_school_id = WLSM_M_Staff_Class::get_class_school_id($school_id, $class_id);
				if (empty($class_school_id)) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				$subject_exists = WLSM_M_Staff_Class::check_subject_exists($class_school_id, $name);
				if (!empty($subject_exists)) {
					throw new Exception(esc_html__('Subject already exists.', 'school-management'));
				}

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = absint($session->ID);

				$data = array(
					'label' => $name,
					'code' => $code,
					'type' => $type,
					'class_school_id' => $class_school_id,
					'session_id' => $session_id,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_SUBJECTS, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Subject Added Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new subject.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - View Subject By id.
	public static function subject_details($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {

			if ($staff_permissions) {

				// $current_session_id = get_option( 'wlsm_current_session' );

				// $session = WLSM_M_Session::get_session( $current_session_id );
				// if ( ! $session ) {
				// 	throw new Exception( esc_html__( 'Current session not found. Please contact the administrator.', 'school-management' ) );
				// }

				// $session_id 	= absint( $session->ID );

				global $wpdb;
				$subject = WLSM_M_Staff_Class::fetch_subject($school_id, $subject_id);

				if (empty($subject)) {
					throw new Exception(esc_html__('Subject not found.', 'school-management'));
				}

				$response_data = array();

				$data[] = array(
					'subject_name' => isset($subject->subject_name) ? WLSM_M_Staff_Class::get_label_text($subject->subject_name) : '',
					'subject_code' => isset($subject->code) ? WLSM_M_Staff_Class::get_label_text($subject->code) : '',
					'subject_type' => isset($subject->type) ? WLSM_M_Staff_Class::get_label_text($subject->type) : '',
					'class_id' => isset($subject->class_id) ? absint($subject->class_id) : '',
				);

				$response_data['subject_details'] = $data;

				$success = true;
				$message = esc_html__('Subject Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit subject.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);

	}

	// Staff - Edit Subject.
	public static function edit_subject($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('edit_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();

		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$label = isset($params['name']) ? $params['name'] : '';
		$code = isset($params['code']) ? $params['code'] : '';
		$type = isset($params['type']) ? $params['type'] : '';
		$class_id = isset($params['class_id']) ? absint($params['class_id']) : 0;

		try {

			if ($staff_permissions) {

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a subject name.', 'school-management'));
				}

				if (empty($type)) {
					throw new Exception(esc_html__('Please select a subject type.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				$subject = WLSM_M_Staff_Class::fetch_subject($school_id, $subject_id);

				if (empty($subject)) {
					throw new Exception(esc_html__('Subject not found.', 'school-management'));
				}

				$class_school_id = WLSM_M_Staff_Class::get_class_school_id($school_id, $class_id);
				if (empty($class_school_id)) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				$subject_exists = WLSM_M_Staff_Class::check_subject_exists($class_school_id, $label);
				if (!empty($subject_exists)) {
					throw new Exception(esc_html__('Subject already exists.', 'school-management'));
				}

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = absint($session->ID);

				global $wpdb;
				$data = array(
					'label' => $label,
					'code' => $code,
					'type' => $type,
					'class_school_id' => $class_school_id,
					'session_id' => $session_id
				);

				$data['updated_at'] = current_time('Y-m-d H:i:s');
				$success = $wpdb->update(WLSM_SUBJECTS, $data, array('ID' => $subject_id, 'session_id' => $session_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Subject Updated Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit subject.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - Delete Subject.
	public static function delete_subject($request)
	{
		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('delete_subjects', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$subject_id = isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {

				// $current_session_id = get_option( 'wlsm_current_session' );

				// $session = WLSM_M_Session::get_session( $current_session_id );
				// if ( ! $session ) {
				// 	throw new Exception( esc_html__( 'Current session not found. Please contact the administrator.', 'school-management' ) );
				// }

				// $session_id = absint( $session->ID );

				global $wpdb;
				$subject = WLSM_M_Staff_Class::get_subject($school_id, $subject_id);

				if (empty($subject)) {
					throw new Exception(esc_html__('Subject not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_SUBJECTS, array('ID' => $subject_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Subject Deleted Successfully.', 'school-management');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete subject.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - Staff list.
	public static function staff_list($request)
	{
		$user_id = get_current_user_id();

		try {
			global $wpdb;

			$staff = WLSM_M::get_staff($user_id);

			if ($staff) {
				$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			} else {
				throw new Exception(esc_html__('Staff not found.', 'school-management'));
			}

			$response_data = array();
			$staff = array();

			$staffs = WLSM_M_Staff_General::fetch_staff_list($school_id);
			if (empty($staffs)) {
				throw new Exception(esc_html__('No staff found.', 'school-management'));
			}

			foreach ($staffs as $member) {
				$staff[] = array(
					'ID' => isset($member->ID) ? absint($member->ID) : 0,
					'name' => isset($member->name) ? WLSM_M_Staff_Class::get_label_text($member->name) : '',
					'role' => isset($member->role) ? WLSM_M_Staff_Class::get_label_text($member->role) : '',
					'user_id' => isset($member->user_id) ? WLSM_M_Staff_Class::get_label_text($member->user_id) : '',
				);
			}

			$response_data['staff'] = $staff;

			$success = true;
			$message = esc_html__('Staff retrieved successfully.', 'school-management');

			WLSM_Helper::check_buffer();

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Assign staff to subject.
	public static function assign_staff_to_subject($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$admin_id = isset($params['admin_id']) ? absint($params['admin_id']) : 0;

		try {

			if (empty($subject_id)) {
				throw new Exception(esc_html__('Please select a subject.', 'school-management'));
			}

			if (empty($admin_id)) {
				throw new Exception(esc_html__('Please select a staff.', 'school-management'));
			}

			global $wpdb;

			$subject = WLSM_M::fetch_subject($school_id, $subject_id);

			if (!$subject) {
				throw new Exception(esc_html__('No subjects found.', 'school-management'));
			}

			$admin = WLSM_M::get_admin($admin_id);

			if (!$admin) {
				throw new Exception(esc_html__('No staff found.', 'school-management'));
			}

			$data = array(
				'admin_id' => $admin_id,
				'subject_id' => $subject_id,
			);

			$data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_ADMIN_SUBJECT, $data);

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$response_data = array();

			$success = true;
			$message = esc_html__('Subject Assigned Successfully.', 'school-management');

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);

	}

	// Staff - Delete assigned staff from subject.
	public static function delete_assigned_staff_from_subject($request)
	{
		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$admin_id = isset($params['admin_id']) ? absint($params['admin_id']) : 0;

		try {
			global $wpdb;

			$subject = WLSM_M::fetch_subject($school_id, $subject_id);

			if (!$subject) {
				throw new Exception(esc_html__('No subjects found.', 'school-management'));
			}

			$admin = WLSM_M::get_admin($admin_id);

			if (!$admin) {
				throw new Exception(esc_html__('No staff found.', 'school-management'));
			}

			$data = array(
				'admin_id' => $admin_id,
				'subject_id' => $subject_id,
			);

			$success = $wpdb->delete(WLSM_ADMIN_SUBJECT, $data);

			WLSM_Helper::check_buffer();

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			$response_data = array();

			$success = true;
			$message = esc_html__('Delete Assigned Staff Successfully.', 'school-management');

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	public static function students_data($request)
	{
		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id = isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_students', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$students = WLSM_M::fetch_students_data($school_id, $session_id, $restrict_to_section);

				if ($students) {
					foreach ($students as $student) {

						$data[] = array(
							'ID' 				=> isset($student->student_id) ? WLSM_M_Staff_Class::get_label_text($student->student_id) : '',
							'photo' 			=> isset($student->photo_id) ? esc_url(wp_get_attachment_url($student->photo_id)) : '',
							'name' 				=> isset($student->name) ? WLSM_M_Staff_Class::get_label_text($student->name) : '',
							'roll_number' 		=> isset($student->roll_number) ? WLSM_M_Staff_Class::get_label_text($student->roll_number) : '',
							'enrollment_number' => isset($student->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($student->enrollment_number) : '',
						);
						$response_data['students'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Students Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view students.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Assigned class section students.
	public static function assigned_class_section_student($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id = isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id = isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions = unserialize($staff->permissions);
			$staff_permissions = in_array('view_students', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$current_session_id = get_option('wlsm_current_session');

				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}

				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$student = WLSM_M::fetch_assigned_class_section_student($school_id, $session_id, $restrict_to_section, $student_id);

				if ($student) {

					$data[] = array(
						'ID' => isset($student->student_id) ? WLSM_M_Staff_Class::get_label_text($student->student_id) : '',
						'enrollment_number' => isset($student->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($student->enrollment_number) : '',
						'admission_number' => isset($student->admission_number) ? WLSM_M_Staff_Class::get_label_text($student->admission_number) : '',
						'name' => isset($student->name) ? WLSM_M_Staff_Class::get_label_text($student->name) : '',
						'gender' => isset($student->gender) ? WLSM_M_Staff_Class::get_label_text($student->gender) : '',
						'dob' => isset($student->dob) ? WLSM_Config::get_date_text($student->dob) : '',
						'phone' => isset($student->phone) ? WLSM_M_Staff_Class::get_label_text($student->phone) : '',
						'email' => isset($student->email) ? WLSM_M_Staff_Class::get_label_text($student->email) : '',
						'address' => isset($student->address) ? WLSM_M_Staff_Class::get_label_text($student->address) : '',
						'admission_date' => isset($student->admission_date) ? WLSM_Config::get_date_text($student->admission_date) : '',
						'religion' => isset($student->religion) ? WLSM_M_Staff_Class::get_label_text($student->religion) : '',
						'caste' => isset($student->caste) ? WLSM_M_Staff_Class::get_label_text($student->caste) : '',
						'blood_group' => isset($student->blood_group) ? WLSM_M_Staff_Class::get_label_text($student->blood_group) : '',
						'father_name' => isset($student->father_name) ? WLSM_M_Staff_Class::get_label_text($student->father_name) : '',
						'mother_name' => isset($student->mother_name) ? WLSM_M_Staff_Class::get_label_text($student->mother_name) : '',
						'father_phone' => isset($student->father_phone) ? WLSM_M_Staff_Class::get_label_text($student->father_phone) : '',
						'mother_phone' => isset($student->mother_phone) ? WLSM_M_Staff_Class::get_label_text($student->mother_phone) : '',
						'father_occupation' => isset($student->father_occupation) ? WLSM_M_Staff_Class::get_label_text($student->father_occupation) : '',
						'mother_occupation' => isset($student->mother_occupation) ? WLSM_M_Staff_Class::get_label_text($student->mother_occupation) : '',
						'father_id_number' => isset($student->father_id_number) ? WLSM_M_Staff_Class::get_label_text($student->father_id_number) : '',
						'mother_id_number' => isset($student->mother_id_number) ? WLSM_M_Staff_Class::get_label_text($student->mother_id_number) : '',
						'roll_number' => isset($student->roll_number) ? WLSM_M_Staff_Class::get_label_text($student->roll_number) : '',
						'photo' => isset($student->photo_id) ? esc_url(wp_get_attachment_url($student->photo_id)) : '',
						'class' => isset($student->class_label) ? WLSM_M_Staff_Class::get_label_text($student->class_label) : '',
						'section' => isset($student->section_label) ? WLSM_M_Staff_Class::get_label_text($student->section_label) : '',
						'status' => isset($student->is_active) ? 'Active' : 'Inactive',
						'city' => isset($student->city) ? WLSM_M_Staff_Class::get_label_text($student->city) : '',
						'state' => isset($student->state) ? WLSM_M_Staff_Class::get_label_text($student->state) : '',
						'country' => isset($student->country) ? WLSM_M_Staff_Class::get_label_text($student->country) : '',
						'medium' => isset($student->medium) ? WLSM_M_Staff_Class::get_label_text($student->medium) : '',
						'category' => isset($student->category) ? WLSM_M_Staff_Class::get_label_text($student->category) : '',
						'pen' => isset($student->pen) ? WLSM_M_Staff_Class::get_label_text($student->pen) : '',
						'apaar' => isset($student->apaar) ? WLSM_M_Staff_Class::get_label_text($student->apaar) : '',
						'dob_in_words' => isset($student->dob_in_words) ? WLSM_M_Staff_Class::get_label_text($student->dob_in_words) : '',
						'birth_place' => isset($student->birth_place) ? WLSM_M_Staff_Class::get_label_text($student->birth_place) : '',
						'mother_tongue' => isset($student->mother_tongue) ? WLSM_M_Staff_Class::get_label_text($student->mother_tongue) : '',
						'school_name' => isset($student->school_name) ? WLSM_M_Staff_Class::get_label_text($student->school_name) : '',
						'school_address' => isset($student->school_address) ? WLSM_M_Staff_Class::get_label_text($student->school_address) : '',
						'school_class' => isset($student->school_class) ? WLSM_M_Staff_Class::get_label_text($student->school_class) : '',
						'pass_out_year' => isset($student->pass_out_year) ? WLSM_M_Staff_Class::get_label_text($student->pass_out_year) : '',
						'student_type' => isset($student->student_type) ? WLSM_M_Staff_Class::get_label_text($student->student_type) : '',
						'id_number' => isset($student->id_number) ? WLSM_M_Staff_Class::get_label_text($student->id_number) : '',
						'id_proof' => isset($student->id_proof) ? esc_url(wp_get_attachment_url($student->id_proof)) : '',
						'parent_id_proof' => isset($student->parent_id_proof) ? esc_url(wp_get_attachment_url($student->parent_id_proof)) : '',
						'note' => isset($student->note) ? WLSM_M_Staff_Class::get_label_text($student->note) : '',
						'parent_signature' => isset($student->parent_signature) ? esc_url(wp_get_attachment_url($student->parent_signature)) : '',
					);
					$response_data['student'] = $data;
				}

				$success = true;
				$message = esc_html__('Students Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view students.', 'school-management'));
			}

		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);

	}

	// Staff - View inquiries.
	public static function view_inquiries($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_inquiries', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$inquiries = WLSM_M::fetch_inquiries($school_id, $restrict_to_section);

				if ($inquiries) {
					foreach ($inquiries as $inquiry) {
						$data[] = array(
							'ID' 				=> isset($inquiry->ID) ? WLSM_M_Staff_Class::get_label_text($inquiry->ID) : '',
							'name' 				=> isset($inquiry->name) ? WLSM_M_Staff_Class::get_label_text($inquiry->name) : '',
							'phone' 			=> isset($inquiry->phone) ? WLSM_M_Staff_Class::get_phone_text($inquiry->phone) : '',
							'email' 			=> isset($inquiry->email) ? WLSM_M_Staff_Class::get_email_text($inquiry->email) : '',
							'message' 			=> isset($inquiry->message) ? WLSM_M_Staff_Class::get_address_text($inquiry->message) : '',
							'note' 				=> isset($inquiry->note) ? WLSM_M_Staff_Class::get_address_text($inquiry->note) : '',
							'next_follow_up' 	=> isset($inquiry->next_follow_up) ? WLSM_M_Staff_Class::get_date_text($inquiry->next_follow_up) : '',
							'is_active' 		=> isset($inquiry->is_active) ? WLSM_M_Staff_Class::get_label_text($inquiry->is_active) : '',
							'reference' 		=> isset($inquiry->reference) ? WLSM_M_Staff_Class::get_label_text($inquiry->reference) : '',
							'class_id' 			=> isset($inquiry->class_id) ? WLSM_M_Staff_Class::get_label_text($inquiry->class_id) : '',
							'section_id' 		=> isset($inquiry->section_id) ? WLSM_M_Staff_Class::get_label_text($inquiry->section_id) : '',
							'class_label' 		=> isset($inquiry->class_label) ? WLSM_M_Staff_Class::get_label_text($inquiry->class_label) : '',
							'section_label' 	=> isset($inquiry->section_label) ? WLSM_M_Staff_Class::get_label_text($inquiry->section_label) : '',
						);
						$response_data['inquiries'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Inquiries Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view inquiries.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Inquiry.
	public static function add_new_inquiry($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_inquiries', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$name 			= isset($params['name']) ? sanitize_text_field($params['name']) : '';
		$phone 			= isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
		$email 			= isset($params['email']) ? sanitize_email($params['email']) : '';
		$message 		= isset($params['message']) ? sanitize_text_field($params['message']) : '';
		$note 			= isset($params['note']) ? sanitize_text_field($params['note']) : '';
		$next_follow_up = isset($params['next_follow_up']) ? sanitize_text_field($params['next_follow_up']) : '';
		$is_active 		= isset($params['is_active']) ? absint($params['is_active']) : 0;
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id 	= isset($params['section_id']) ? absint($params['section_id']) : 0;
		$reference 		= isset($params['reference']) ? sanitize_text_field($params['reference']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($name)) {
					throw new Exception(esc_html__('Please enter a name.', 'school-management'));
				}

				if ( ! empty( $email ) && is_email( $email ) ) {
					if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$errors['email'] = esc_html__('Please provide a valid email.', 'school-management');
					}
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				$class_school_id = WLSM_M_Staff_Class::get_class_school_id($school_id, $class_id);

				if (empty($class_school_id)) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				if( !empty($next_follow_up) ) {
					$next_follow_up = date('Y-m-d', strtotime($next_follow_up));
				}

				$data = array(
					'name' 				=> $name,
					'phone' 			=> $phone,
					'email' 			=> $email,
					'message' 			=> $message,
					'note' 				=> $note,
					'next_follow_up' 	=> $next_follow_up,
					'is_active' 		=> $is_active,
					'class_school_id' 	=> $class_school_id,
					'school_id' 		=> $school_id,
					'reference' 		=> $reference,
					'section_id' 		=> $section_id,
				);

				$data['created_at'] 	= current_time('Y-m-d H:i:s');
				$data['gdpr_agreed'] 	= 0;

				$success = $wpdb->insert(WLSM_INQUIRIES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Inquiry Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new inquiry.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Inquiry details.
	public static function inquiry_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_inquiries', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$inquiry 	= WLSM_M::fetch_inquiry($school_id, $id);

				if ($inquiry) {

					$data = array(
						'ID' 				=> isset($inquiry->ID) ? WLSM_M_Staff_Class::get_label_text($inquiry->ID) : '',
						'name' 				=> isset($inquiry->name) ? WLSM_M_Staff_Class::get_label_text($inquiry->name) : '',
						'phone' 			=> isset($inquiry->phone) ? WLSM_M_Staff_Class::get_label_text($inquiry->phone) : '',
						'email' 			=> isset($inquiry->email) ? WLSM_M_Staff_Class::get_label_text($inquiry->email) : '',
						'message' 			=> isset($inquiry->message) ? WLSM_M_Staff_Class::get_address_text($inquiry->message) : '',
						'note' 				=> isset($inquiry->note) ? WLSM_M_Staff_Class::get_address_text($inquiry->note) : '',
						'next_follow_up' 	=> isset($inquiry->next_follow_up) ? WLSM_M_Staff_Class::get_date_text($inquiry->next_follow_up) : '',
						'reference' 		=> isset($inquiry->reference) ? WLSM_M_Staff_Class::get_address_text($inquiry->reference) : '',
						'class_label' 		=> isset($inquiry->class_label) ? WLSM_M_Staff_Class::get_label_text($inquiry->class_label) : '',
						'section_label' 	=> isset($inquiry->section_label) ? WLSM_M_Staff_Class::get_label_text($inquiry->section_label) : '',
						'class_id' 			=> isset($inquiry->class_id) ? WLSM_M_Staff_Class::get_label_text($inquiry->class_id) : '',
						'section_id' 		=> isset($inquiry->section_id) ? WLSM_M_Staff_Class::get_label_text($inquiry->section_id) : '',
					);
					$response_data['inquiry'] = $data;
				}

				$success = true;
				$message = esc_html__('Inquiry Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view inquiries.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit Inquiry.
	public static function edit_inquiry($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_inquiries', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$name 			= isset($params['name']) ? sanitize_text_field($params['name']) : '';
		$phone 			= isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
		$email 			= isset($params['email']) ? sanitize_email($params['email']) : '';
		$message 		= isset($params['message']) ? sanitize_text_field($params['message']) : '';
		$note 			= isset($params['note']) ? sanitize_text_field($params['note']) : '';
		$next_follow_up = isset($params['next_follow_up']) ? sanitize_text_field($params['next_follow_up']) : '';
		$is_active 		= isset($params['is_active']) ? absint($params['is_active']) : 0;
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id 	= isset($params['section_id']) ? absint($params['section_id']) : 0;
		$reference 		= isset($params['reference']) ? sanitize_text_field($params['reference']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$inquiry 	= WLSM_M::fetch_inquiry($school_id, $id);
				if (empty($inquiry)) {
					throw new Exception(esc_html__('Inquiry not found.', 'school-management'));
				}

				if (empty($name)) {
					throw new Exception(esc_html__('Please enter a name.', 'school-management'));
				}

				if ( ! empty( $email ) && is_email( $email ) ) {
					if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$errors['email'] = esc_html__('Please provide a valid email.', 'school-management');
					}
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($section_id)) {
					throw new Exception(esc_html__('Please select a section.', 'school-management'));
				}

				$class_school_id = WLSM_M_Staff_Class::get_class_school_id($school_id, $class_id);

				if (empty($class_school_id)) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				if( !empty($next_follow_up) ) {
					$next_follow_up = date('Y-m-d', strtotime($next_follow_up));
				}

				$data = array(
					'name' 				=> $name,
					'phone' 			=> $phone,
					'email' 			=> $email,
					'message' 			=> $message,
					'note' 				=> $note,
					'next_follow_up' 	=> $next_follow_up,
					'is_active' 		=> $is_active,
					'class_school_id' 	=> $class_school_id,
					'school_id' 		=> $school_id,
					'reference' 		=> $reference,
					'section_id' 		=> $section_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');
				$data['gdpr_agreed'] 	= 0;

				$success = $wpdb->update(WLSM_INQUIRIES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Inquiry Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit inquiry.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Inquiry.
	public static function delete_inquiry($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_inquiries', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$inquiry_id 	= isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {
				global $wpdb;

				$inquiry 	= WLSM_M::fetch_inquiry($school_id, $inquiry_id);

				if (empty($inquiry)) {
					throw new Exception(esc_html__('Inquiry not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_INQUIRIES, array('ID' => $inquiry_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Inquiry Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete inquiry.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View exam groups.
	public static function view_exam_groups($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$exam_groups = WLSM_M::fetch_exam_groups($school_id );

				if ($exam_groups) {
					foreach ($exam_groups as $exam_group) {
						$data[] = array(
							'ID' 		=> isset($exam_group->ID) ? WLSM_M_Staff_Class::get_label_text($exam_group->ID) : '',
							'label' 	=> isset($exam_group->label) ? WLSM_M_Staff_Class::get_label_text($exam_group->label) : '',
							'is_active' => isset($exam_group->is_active) ? WLSM_M_Staff_Class::get_status_text($exam_group->is_active) : '',
						);
						$response_data['exam_groups'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Exam Groups Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exams.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Exam Group.
	public static function add_new_exam_group($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$is_active 		= isset($params['is_active']) ? absint($params['is_active']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a label.', 'school-management'));
				}

				$data = array(
					'label' 			=> $label,
					'is_active' 		=> $is_active,
					'school_id' 		=> $school_id,
				);

				$data['created_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_EXAMS_GROUP, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Group Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new exam.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Exam group details.
	public static function exam_group_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$exam_group 	= WLSM_M::fetch_exam_group($school_id, $id);

				if ($exam_group) {
					$data = array(
						'ID' 			=> isset($exam_group->ID) ? WLSM_M_Staff_Class::get_label_text($exam_group->ID) : '',
						'label' 		=> isset($exam_group->label) ? WLSM_M_Staff_Class::get_label_text($exam_group->label) : '',
						'is_active' 	=> isset($exam_group->is_active) ? WLSM_M_Staff_Class::get_status_text($exam_group->is_active) : '',
					);
					$response_data['exam_group'] = $data;
				}

				$success = true;
				$message = esc_html__('Exam Group Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exams.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit exam group.
	public static function edit_exam_group($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$is_active 		= isset($params['is_active']) ? absint($params['is_active']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam_group 	= WLSM_M::fetch_exam_group($school_id, $id);
				if (empty($exam_group)) {
					throw new Exception(esc_html__('Exam group not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a label.', 'school-management'));
				}

				$data = array(
					'label' 			=> $label,
					'is_active' 		=> $is_active
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_EXAMS_GROUP, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Group Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit exam.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete exam group.
	public static function delete_exam_group($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$exam_group_id 	= isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam_group 	= WLSM_M::fetch_exam_group($school_id, $exam_group_id);

				if (empty($exam_group)) {
					throw new Exception(esc_html__('Exam group not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_EXAMS_GROUP, array('ID' => $exam_group_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Group Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete exams.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Exam Class Subjects.
	public static function exam_class_subjects($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 	= isset( $staff->school_id ) ? absint( $staff->school_id ) : 0;
			$section_id = isset( $staff->section_id ) ? absint( $staff->section_id ) : 0;
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$class_id = isset($params['class_id']) ? $params['class_id'] : 0;

		try {

			if (empty($class_id)) {
				throw new Exception(esc_html__('Please select a class.', 'school-management'));
			}

			global $wpdb;
			$subjects = WLSM_M::fetch_exam_class_subjects($school_id, $class_id);

			if (!$subjects) {
				throw new Exception(esc_html__('No subjects found.', 'school-management'));
			}

			$response_data = array();
			$response_data['subjects'] = $subjects;

			$success = true;
			$message = esc_html__('Subjects Retrieved Successfully.', 'school-management');
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View exams.
	public static function view_exams($request)
	{

		$user_id = get_current_user_id();
		$staff = WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$exams = WLSM_M::fetch_exams($school_id, $restrict_to_section);

				if ($exams) {
					foreach ($exams as $exam) {
						$data[] = array(
							'ID' 				=> isset($exam->ID) ? WLSM_M_Staff_Class::get_label_text($exam->ID) : '',
							'exam_title' 		=> isset($exam->label) ? WLSM_M_Staff_Class::get_label_text($exam->label) : '',
							'class_id' 			=> isset($exam->class_id) ? absint($exam->class_id) : '',
							'class_label' 		=> isset($exam->class_label) ? WLSM_M_Staff_Class::get_label_text($exam->class_label) : '',
							'exam_center' 		=> isset($exam->exam_center) ? WLSM_M_Staff_Class::get_label_text($exam->exam_center) : '',
							'start_date' 		=> isset($exam->start_date) ? WLSM_M_Staff_Class::get_date_text($exam->start_date) : '',
							'end_date' 			=> isset($exam->end_date) ? WLSM_M_Staff_Class::get_date_text($exam->end_date) : '',
							'is_active' 		=> isset($exam->is_active) ? WLSM_M_Staff_Class::get_label_text($exam->is_active) : '',
						);
						$response_data['exams'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Exams Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exams.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Exam.
	public static function add_new_exam($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		// Exam.
		$exam_title  			= isset( $params['label'] ) ? sanitize_text_field( $params['label'] ) : '';
		$exam_center 			= isset( $params['exam_center'] ) ? sanitize_text_field( $params['exam_center'] ) : '';
		$exam_group  			= isset( $params['exam_group'] ) ? sanitize_text_field( $params['exam_group'] ) : '';
		$start_date  			= isset( $params['start_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['start_date'] ) ) : NULL;
		$end_date    			= isset( $params['end_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['end_date'] ) ) : NULL;
		$class_id     			= isset( $params['class_id'] ) ? absint($params['class_id']) : 0;
		$enable_room_numbers   	= isset( $params['enable_room_numbers'] ) ? (bool) $params['enable_room_numbers'] : 0;
		$enable_total_marks     = isset( $params['enable_total_marks'] ) ?  intval($params['enable_total_marks']) : 0;
		$results_obtained_marks = isset( $params['results_obtained_marks'] ) ? intval($params['results_obtained_marks']) : 0;
		$admit_cards_published 	= isset( $params['admit_cards_published'] ) ? (bool) $params['admit_cards_published'] : 0;
		$time_table_published  	= isset( $params['time_table_published'] ) ? (bool) $params['time_table_published'] : 0;
		$results_published     	= isset( $params['results_published'] ) ? (bool) $params['results_published'] : 0;
		$show_in_assessment    	= isset( $params['show_in_assessment'] ) ? (bool) $params['show_in_assessment'] : 1;
		$is_active             	= isset( $params['is_active'] ) ? (bool) $params['is_active'] : 1;
		$show_rank    			= isset( $params['show_rank'] ) ? (bool) $params['show_rank'] : 1;
		$show_remark  			= isset( $params['show_remark'] ) ? (bool) $params['show_remark'] : 1;
		$show_eremark 			= isset( $params['show_eremark'] ) ? (bool) $params['show_eremark'] : 1;
		$psychomotor_analysis   = isset( $params['psychomotor_analysis'] ) ? (bool) $params['psychomotor_analysis'] : 1;
		$teacher_signature 		= isset($_FILES['teacher_signature']) && is_array($_FILES['teacher_signature']) ? $_FILES['teacher_signature'] : NULL;

		// Exam papers.
		$subject_id    = isset( $params['subject_id'] ) && is_array( $params['subject_id'] ) ? $params['subject_id'] : array();
		$subject_label = isset( $params['subject_label'] ) && is_array( $params['subject_label'] ) ? $params['subject_label'] : array();
		$subject_type  = isset( $params['subject_type'] ) && is_array( $params['subject_type'] ) ? $params['subject_type'] : array();
		$maximum_marks = isset( $params['maximum_marks'] ) && is_array( $params['maximum_marks'] ) ? $params['maximum_marks'] : array();
		$paper_code    = isset( $params['paper_code'] ) && is_array( $params['paper_code'] ) ? $params['paper_code'] : array();
		$paper_date    = isset( $params['paper_date'] ) && is_array( $params['paper_date'] ) ? $params['paper_date'] : array();
		$start_time    = isset( $params['start_time'] ) && is_array( $params['start_time'] ) ? $params['start_time'] : array();
		$end_time      = isset( $params['end_time'] ) && is_array( $params['end_time'] ) ? $params['end_time'] : array();
		$room_number   = isset( $params['room_number'] ) && is_array( $params['room_number'] ) ? $params['room_number'] : array();

		// Enable overall grade.
		$enable_overall_grade = isset( $params['enable_overall_grade'] ) ? (bool) $params['enable_overall_grade'] : false;

		// Grade criteria.
		$gc_min   = isset( $params['grade_criteria']['min'] ) && is_array( $params['grade_criteria']['min'] ) ? $params['grade_criteria']['min'] : array();
		$gc_max   = isset( $params['grade_criteria']['max'] ) && is_array( $params['grade_criteria']['max'] ) ? $params['grade_criteria']['max'] : array();
		$gc_grade = isset( $params['grade_criteria']['grade'] ) && is_array( $params['grade_criteria']['grade'] ) ? $params['grade_criteria']['grade'] : array();

		// psychomotor
		$psych 	= isset( $params['psych']) && is_array( $params['psych']) ? $params['psych']: array();
		$scale 	= isset( $params['scale']) && is_array( $params['scale']) ? $params['scale']: array();
		$def   	= isset( $params['def']) && is_array( $params['def']) ? $params['def']: array();

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (isset($teacher_signature['tmp_name']) && !empty($teacher_signature['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($teacher_signature, 'image')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				if (isset($teacher_signature) && is_array($teacher_signature)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					$attachment_id = NULL;
					if (isset($teacher_signature) && is_array($teacher_signature)) {
						if (!empty($_FILES['teacher_signature']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['teacher_signature']['name'],
								'type'     => $_FILES['teacher_signature']['type'],
								'tmp_name' => $_FILES['teacher_signature']['tmp_name'],
								'error'    => $_FILES['teacher_signature']['error'],
								'size'     => $_FILES['teacher_signature']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'image')) {
								throw new Exception(esc_html__('Invalid file format. Only JPG, JPEG and PNG allowed.', 'school-management'));
							}

							$_FILES['teacher_signature'] = $file_array;
							$attachment_id = media_handle_upload('teacher_signature', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$current_user = WLSM_M_Role::can( 'assigned_class' );
				// Exam.
				if ( empty( $exam_title ) ) {
					throw new Exception(esc_html__('Please provide exam title.', 'school-management'));
				}

				if ( empty( $start_date ) ) {
					throw new Exception(esc_html__('Please provide start date of exam.', 'school-management'));
				} else {
					$start_date = $start_date->format( 'Y-m-d' );
				}

				if ( empty( $end_date ) ) {
					$end_date = NULL;
				} else {
					$end_date = $end_date->format( 'Y-m-d' );

					if ( ! empty( $end_date ) && $start_date > $end_date ) {
						throw new Exception(esc_html__('Exam start date must be lower than end date.', 'school-management'));

					}
				}

				$class_school = WLSM_M_Staff_Class::get_class( $school_id, $class_id );
				if ( ! $class_school ) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

					if ($restrict_to_section) {
						$restrict_to_section_detail = WLSM_M_Staff_Class::get_section_by_id($restrict_to_section);

						if (($restrict_to_section_detail->class_id )!== $class_id) {
							throw new Exception(esc_html__('You don\'t have permission to save for this class.', 'school-management'));
						}
					}
				}

				// Exam papers.
				if ( ! count( $paper_code ) ) {
					wp_send_json_error( esc_html__( 'Please add at least one exam paper.', 'school-management' ) );
				} else {
					if ( 1 !== count( array_unique( array( count( $subject_label ), count( $subject_type ), count( $maximum_marks ), count( $paper_code ), count( $paper_date ), count( $start_time ), count( $end_time ), count( $room_number ) ) ) ) ) {
						wp_send_json_error( esc_html__( 'Invalid exam paper.', 'school-management' ) );
					} elseif ( count( $paper_code ) !== count( array_unique( $paper_code ) ) ) {
						wp_send_json_error( esc_html__( 'Paper codes must be different.', 'school-management' ) );
					} else {
						foreach ( $paper_code as $key => $value ) {
							$subject_id[ $key ]    = sanitize_text_field( $subject_id[ $key ] );
							$subject_label[ $key ] = sanitize_text_field( $subject_label[ $key ] );
							$subject_type[ $key ]  = sanitize_text_field( $subject_type[ $key ] );
							$maximum_marks[ $key ] = absint( $maximum_marks[ $key ] );
							$paper_code[ $key ]    = sanitize_text_field( $value );
							$paper_date[ $key ]    = DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $paper_date[ $key ] ) );
							$start_time[ $key ]    = DateTime::createFromFormat( WLSM_Config::get_default_time_format(), sanitize_text_field( $start_time[ $key ] ) );
							$end_time[ $key ]      = DateTime::createFromFormat( WLSM_Config::get_default_time_format(), sanitize_text_field( $end_time[ $key ] ) );
							$room_number[ $key ]   = sanitize_text_field( $room_number[ $key ] );

							if ( empty( $subject_label[ $key ] ) ) {
								wp_send_json_error( esc_html__( 'Please provide subject name.', 'school-management' ) );
							} elseif ( strlen( $subject_label[ $key ] ) > 100 ) {
								wp_send_json_error( esc_html__( 'Maximum length cannot exceed 100 characters.', 'school-management' ) );
							}


							if ( empty( $paper_code[ $key ] ) ) {
								wp_send_json_error( esc_html__( 'Please provide paper code.', 'school-management' ) );
							} elseif ( strlen( $paper_code[ $key ] ) > 40 ) {
								wp_send_json_error( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) );
							}

							if ( $maximum_marks[ $key ] < 1 ) {
								wp_send_json_error( esc_html__( 'Maximum marks must be a positive integer.', 'school-management' ) );
							} elseif ( $maximum_marks[ $key ] > 9999 ) {
								wp_send_json_error( esc_html__( 'Maximum marks must be lower than 10000.', 'school-management' ) );
							}

							if ( ! empty( $room_number[ $key ] ) && ( strlen( $room_number[ $key ] ) > 40 ) ) {
								wp_send_json_error( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) );
							}

							if ( empty( $paper_date[ $key ] ) ) {
								wp_send_json_error( esc_html__( 'Please provide exam paper date.', 'school-management' ) );
							} else {
								$exam_paper_date    = $paper_date[ $key ];
								$paper_date[ $key ] = $exam_paper_date->format( 'Y-m-d' );
							}

							if ( empty( $start_time[ $key ] ) ) {
								$start_time[ $key ] = NULL;
							} else {
								$exam_start_time    = $start_time[ $key ];
								$start_time[ $key ] = $exam_start_time->format( 'H:i:s' );
							}

							if ( empty( $end_time[ $key ] ) ) {
								$end_time[ $key ] = NULL;
							} else {
								$exam_end_time    = $end_time[ $key ];
								$end_time[ $key ] = $exam_end_time->format( 'H:i:s' );
							}
						}
					}
				}

				// Grade criteria.
				if ( 1 !== count( array_unique( array( count( $gc_grade ), count( $gc_min ), count( $gc_max ) ) ) ) ) {
					wp_send_json_error( esc_html__( 'Invalid grade criteria.', 'school-management' ) );
				} else {
					$i      = 0;
					$length = count( $gc_grade );

					$marks_grades = array();
					foreach ( $gc_grade as $key => $grade ) {
						$min = absint( $gc_min[ $key ] );
						$max = absint( $gc_max[ $key ] );

						if ( empty( $grade ) ) {
							wp_send_json_error( esc_html__( 'Please specify grade.', 'school-management' ) );
						}

						if( $min > $max ) {
							wp_send_json_error(
								sprintf(
									/* translators: 1: minimum percentage, 2: maximum percentage */
									__( 'Minimum percentage %1$s must be greater than maximum percentage %2$s.', 'school-management' ), $min, $max
								)
							);
						}

						$last_max = isset( $gc_max[ $key - 1 ] ) ? absint( $gc_max[ $key - 1 ] ) : 0;

						if ( $last_max > 0 && ( ( $last_max + 1 ) !== $min ) ) {
							wp_send_json_error(
								sprintf(
									/* translators: 1: minimum percentage, 2: last maximum percentage, 3: correct value of minimum percentage */
									__( 'Minimum percentage %1$s must be greater than last maximum percentage %2$s by 1%% (which is %3$s).', 'school-management' ), $min, $last_max, $last_max + 1
								)
							);
						}

						if ( 0 === $i ) {
							if ( 0 !== $min ) {
								wp_send_json_error( esc_html__( 'First minimum percentage must be 0 for grade criteria.', 'school-management' ) );
							}
						} elseif ( ( $length - 1 ) === $i ) {
							if ( 100 !== $max ) {
								wp_send_json_error( esc_html__( 'Last maximum percentage must be 100 for grade criteria.', 'school-management' ) );
							}
						}

						$i++;

						array_push(
							$marks_grades,
							array(
								'min'   => $min,
								'max'   => $max,
								'grade' => sanitize_text_field( $grade ),
							)
						);
					}
				}

				$grade_criteria = serialize(
					array(
						'enable_overall_grade' => $enable_overall_grade,
						'marks_grades'         => $marks_grades
					)
				);

				// Psychmotor
				$psychomotor = serialize(
				array(
					'psych' => $psych,
					'scale' => $scale,
					'def'   => $def,
					)
				);

				// Exam data.
				$exam_data = array(
					'label'                  => $exam_title,
					'exam_center'            => $exam_center,
					'exam_group'             => $exam_group,
					'start_date'             => $start_date,
					'end_date'               => $end_date,
					'grade_criteria'         => $grade_criteria,
					'psychomotor'            => $psychomotor,
					'enable_room_numbers'    => $enable_room_numbers,
					'enable_total_marks'     => $enable_total_marks,
					'results_obtained_marks' => $results_obtained_marks,
					'results_published'      => $results_published,
					'admit_cards_published'  => $admit_cards_published,
					'time_table_published'   => $time_table_published,
					'show_in_assessment'     => $show_in_assessment,
					'is_active'              => $is_active,
					'show_rank'              => $show_rank,
					'show_remark'            => $show_remark,
					'show_eremark'           => $show_eremark,
					'psychomotor_analysis'   => $psychomotor_analysis,
					'teacher_signature'   	=> $attachment_id,
				);

				$exam_data['created_at'] 	= current_time( 'Y-m-d H:i:s' );
				$exam_data['school_id'] 	= $school_id;

				$success = $wpdb->insert( WLSM_EXAMS, $exam_data );
				$exam_id = $wpdb->insert_id;

				if ( $class_school ) {
					$class_school_id = isset($class_school->ID ) ? absint($class_school->ID) : 0;

					// Insert single record with IGNORE (to avoid duplicates).
					$sql = 'INSERT IGNORE INTO ' . WLSM_CLASS_SCHOOL_EXAM . ' (class_school_id, exam_id) VALUES (%d, %d)';
					$success = $wpdb->query( $wpdb->prepare( $sql, $class_school_id, $exam_id ) );

					// Delete records for this exam_id except the current class_school_id
					$sql = 'DELETE FROM ' . WLSM_CLASS_SCHOOL_EXAM . ' WHERE exam_id = %d AND class_school_id != %d';
					$success = $wpdb->query( $wpdb->prepare( $sql, $exam_id, $class_school_id ) );
				} else {
					$success = $wpdb->delete( WLSM_CLASS_SCHOOL_EXAM, array( 'exam_id' => $exam_id ) );
				}

				// Exam papers.
				$place_holders_paper_codes = array();

				$paper_order = 10;
				foreach ( $paper_code as $key => $value ) {
					array_push( $place_holders_paper_codes, '%s' );
					$paper_order++;

					// Exam paper data.
					$exam_paper_data = array(
						'subject_id'    => $subject_id[ $key ],
						'subject_label' => $subject_label[ $key ],
						'subject_type'  => $subject_type[ $key ],
						'maximum_marks' => $maximum_marks[ $key ],
						'paper_date'    => $paper_date[ $key ],
						'start_time'    => $start_time[ $key ],
						'end_time'      => $end_time[ $key ],
						'room_number'   => $room_number[ $key ],
						'paper_order'   => $paper_order,
					);

					// Exam paper does not exist, insert exam paper.
					$exam_paper_data['paper_code'] = $value;
					$exam_paper_data['exam_id']    = $exam_id;
					$exam_paper_data['created_at'] = current_time( 'Y-m-d H:i:s' );

					$success = $wpdb->insert( WLSM_EXAM_PAPERS, $exam_paper_data );
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new exam.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Exam details.
	public static function exam_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_inquiries', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $id);
				$exam_class  	= WLSM_M::fetch_exam_class( $id);
				$exam_papers  	= WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $id);

				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				if ($exam) {
					$grade_criteria = unserialize( $exam->grade_criteria );
					$psychomotor    = unserialize( $exam->psychomotor );

					$data_1 = array(
						'ID' 						=> isset($exam->ID) ? absint($exam->ID) : 0,
						'label' 					=> isset($exam->exam_title) ? WLSM_M_Staff_Class::get_label_text($exam->exam_title) : '',
						'exam_center' 				=> isset($exam->exam_center) ? WLSM_M_Staff_Class::get_label_text($exam->exam_center) : '',
						'start_date' 				=> isset($exam->start_date) ? WLSM_M_Staff_Class::get_date_text($exam->start_date) : '',
						'end_date' 					=> isset($exam->end_date) ? WLSM_M_Staff_Class::get_date_text($exam->end_date) : '',
						'class_id' 					=> isset($exam_class->class_id) ? absint($exam_class->class_id) : 0,
						'class_label' 				=> isset($exam_class->class_label) ? WLSM_M_Staff_Class::get_label_text($exam_class->class_label) : '',
						'exam_group' 				=> isset($exam->exam_group) ? absint($exam->exam_group) : 0,
						'admit_cards_published' 	=> isset($exam->admit_cards_published) ? absint($exam->admit_cards_published) : 0,
						'time_table_published' 		=> isset($exam->time_table_published) ? absint($exam->time_table_published) : 0,
						'enable_room_numbers' 		=> isset($exam->enable_room_numbers) ? absint($exam->enable_room_numbers) : 0,
						'results_published' 		=> isset($exam->results_published) ? absint($exam->results_published) : 0,
						'enable_total_marks' 		=> isset($exam->enable_total_marks) ? absint($exam->enable_total_marks) : 0,
						'results_obtained_marks' 	=> isset($exam->results_obtained_marks) ? absint($exam->results_obtained_marks) : 0,
						'show_in_assessment' 		=> isset($exam->show_in_assessment) ? absint($exam->show_in_assessment) : 0,
						'show_rank' 				=> isset($exam->show_rank) ? absint($exam->show_rank) : 0,
						'show_remark' 				=> isset($exam->show_remark) ? absint($exam->show_remark) : 0,
						'show_eremark' 				=> isset($exam->show_eremark) ? absint($exam->show_eremark) : 0,
						'psychomotor_analysis' 		=> isset($exam->psychomotor_analysis) ? absint($exam->psychomotor_analysis) : 0,
						'teacher_signature' 		=> isset($exam->teacher_signature) ? wp_get_attachment_url($exam->teacher_signature) : '',
						'is_active' 				=> isset($exam->is_active) ? WLSM_M_Staff_Class::get_status_text($exam->is_active) : '',
					);

					$data_2 = array(
						'enable_overall_grade' => isset($grade_criteria['enable_overall_grade']) ? (bool) $grade_criteria['enable_overall_grade'] : false,
						'marks_grades'         => isset($grade_criteria['marks_grades']) ? $grade_criteria['marks_grades'] : array(),
					);

					$data_3 = array(
						'psych' => isset($psychomotor['psych']) ? $psychomotor['psych'] : array(),
						'scale' => isset($psychomotor['scale']) ? $psychomotor['scale'] : array(),
						'def'   => isset($psychomotor['def']) ? $psychomotor['def'] : array(),
					);

					$data = array_merge($data_1, $data_2, $data_3);
					$response_data['exam'] = $data;
				}

				if ( $exam_papers ) {
					foreach ( $exam_papers as $key => $paper ) {
						$exam_papers[ $key ]->subject_label = WLSM_M_Staff_Class::get_label_text( $paper->subject_label );
						$exam_papers[ $key ]->subject_type  = WLSM_M_Staff_Class::get_label_text( $paper->subject_type );
						$exam_papers[ $key ]->paper_code  	= WLSM_M_Staff_Class::get_label_text( $paper->paper_code );
						$exam_papers[ $key ]->paper_order  	= WLSM_M_Staff_Class::get_label_text( $paper->paper_order );
						$exam_papers[ $key ]->paper_date  	= WLSM_M_Staff_Class::get_date_text( $paper->paper_date );
						$exam_papers[ $key ]->start_time  	= WLSM_Config::get_time_text( $paper->start_time );
						$exam_papers[ $key ]->end_time    	= WLSM_Config::get_time_text( $paper->end_time );
						$exam_papers[ $key ]->room_number  	= WLSM_M_Staff_Class::get_label_text( $paper->room_number );
						$exam_papers[ $key ]->maximum_marks = WLSM_M_Staff_Class::get_label_text( $paper->maximum_marks );
						$exam_papers[ $key ]->subject_id  	= WLSM_M_Staff_Class::get_label_text( $paper->subject_id );
					}
					$response_data['exam_papers'] = $exam_papers;
				}

				$success = true;
				$message = esc_html__('Exam Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exams.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit exam.
	public static function edit_exam($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		// Exam.
		$exam_id     			= isset( $params['id'] ) ? absint($params['id']) : 0;
		$exam_title  			= isset( $params['label'] ) ? sanitize_text_field( $params['label'] ) : '';
		$exam_center 			= isset( $params['exam_center'] ) ? sanitize_text_field( $params['exam_center'] ) : '';
		$exam_group  			= isset( $params['exam_group'] ) ? sanitize_text_field( $params['exam_group'] ) : '';
		$start_date  			= isset( $params['start_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['start_date'] ) ) : NULL;
		$end_date    			= isset( $params['end_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['end_date'] ) ) : NULL;
		$class_id     			= isset( $params['class_id'] ) ? absint($params['class_id']) : 0;
		$enable_room_numbers   	= isset( $params['enable_room_numbers'] ) ? (bool) $params['enable_room_numbers'] : 0;
		$enable_total_marks     = isset( $params['enable_total_marks'] ) ?  intval($params['enable_total_marks']) : 0;
		$results_obtained_marks = isset( $params['results_obtained_marks'] ) ? intval($params['results_obtained_marks']) : 0;
		$admit_cards_published 	= isset( $params['admit_cards_published'] ) ? (bool) $params['admit_cards_published'] : 0;
		$time_table_published  	= isset( $params['time_table_published'] ) ? (bool) $params['time_table_published'] : 0;
		$results_published     	= isset( $params['results_published'] ) ? (bool) $params['results_published'] : 0;
		$show_in_assessment    	= isset( $params['show_in_assessment'] ) ? (bool) $params['show_in_assessment'] : 1;
		$is_active             	= isset( $params['is_active'] ) ? (bool) $params['is_active'] : 1;
		$show_rank    			= isset( $params['show_rank'] ) ? (bool) $params['show_rank'] : 1;
		$show_remark  			= isset( $params['show_remark'] ) ? (bool) $params['show_remark'] : 1;
		$show_eremark 			= isset( $params['show_eremark'] ) ? (bool) $params['show_eremark'] : 1;
		$psychomotor_analysis   = isset( $params['psychomotor_analysis'] ) ? (bool) $params['psychomotor_analysis'] : 1;
		$teacher_signature 		= isset($_FILES['teacher_signature']) && is_array($_FILES['teacher_signature']) ? $_FILES['teacher_signature'] : NULL;

		// Exam papers.
		$subject_id    = isset( $params['subject_id'] ) && is_array( $params['subject_id'] ) ? $params['subject_id'] : array();
		$subject_label = isset( $params['subject_label'] ) && is_array( $params['subject_label'] ) ? $params['subject_label'] : array();
		$subject_type  = isset( $params['subject_type'] ) && is_array( $params['subject_type'] ) ? $params['subject_type'] : array();
		$maximum_marks = isset( $params['maximum_marks'] ) && is_array( $params['maximum_marks'] ) ? $params['maximum_marks'] : array();
		$paper_code    = isset( $params['paper_code'] ) && is_array( $params['paper_code'] ) ? $params['paper_code'] : array();
		$paper_date    = isset( $params['paper_date'] ) && is_array( $params['paper_date'] ) ? $params['paper_date'] : array();
		$start_time    = isset( $params['start_time'] ) && is_array( $params['start_time'] ) ? $params['start_time'] : array();
		$end_time      = isset( $params['end_time'] ) && is_array( $params['end_time'] ) ? $params['end_time'] : array();
		$room_number   = isset( $params['room_number'] ) && is_array( $params['room_number'] ) ? $params['room_number'] : array();

		// Enable overall grade.
		$enable_overall_grade = isset( $params['enable_overall_grade'] ) ? (bool) $params['enable_overall_grade'] : false;

		// Grade criteria.
		$gc_min   = isset( $params['grade_criteria']['min'] ) && is_array( $params['grade_criteria']['min'] ) ? $params['grade_criteria']['min'] : array();
		$gc_max   = isset( $params['grade_criteria']['max'] ) && is_array( $params['grade_criteria']['max'] ) ? $params['grade_criteria']['max'] : array();
		$gc_grade = isset( $params['grade_criteria']['grade'] ) && is_array( $params['grade_criteria']['grade'] ) ? $params['grade_criteria']['grade'] : array();

		// psychomotor
		$psych 	= isset( $params['psych']) && is_array( $params['psych']) ? $params['psych']: array();
		$scale 	= isset( $params['scale']) && is_array( $params['scale']) ? $params['scale']: array();
		$def   	= isset( $params['def']) && is_array( $params['def']) ? $params['def']: array();

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				if (isset($teacher_signature['tmp_name']) && !empty($teacher_signature['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($teacher_signature, 'image')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				if (isset($teacher_signature) && is_array($teacher_signature)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					$attachment_id = NULL;
					if (isset($teacher_signature) && is_array($teacher_signature)) {
						if (!empty($_FILES['teacher_signature']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['teacher_signature']['name'],
								'type'     => $_FILES['teacher_signature']['type'],
								'tmp_name' => $_FILES['teacher_signature']['tmp_name'],
								'error'    => $_FILES['teacher_signature']['error'],
								'size'     => $_FILES['teacher_signature']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'image')) {
								throw new Exception(esc_html__('Invalid file format. Only JPG, JPEG and PNG allowed.', 'school-management'));
							}

							$_FILES['teacher_signature'] = $file_array;
							$attachment_id = media_handle_upload('teacher_signature', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$current_user = WLSM_M_Role::can( 'assigned_class' );
				// Exam.
				if ( empty( $exam_title ) ) {
					throw new Exception(esc_html__('Please provide exam title.', 'school-management'));
				}

				if ( empty( $start_date ) ) {
					throw new Exception(esc_html__('Please provide start date of exam.', 'school-management'));
				} else {
					$start_date = $start_date->format( 'Y-m-d' );
				}

				if ( empty( $end_date ) ) {
					$end_date = NULL;
				} else {
					$end_date = $end_date->format( 'Y-m-d' );

					if ( ! empty( $end_date ) && $start_date > $end_date ) {
						throw new Exception(esc_html__('Exam start date must be lower than end date.', 'school-management'));

					}
				}

				$class_school = WLSM_M_Staff_Class::get_class( $school_id, $class_id );
				if ( ! $class_school ) {
					throw new Exception(esc_html__('Class not found.', 'school-management'));
				}

				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

					if ($restrict_to_section) {
						$restrict_to_section_detail = WLSM_M_Staff_Class::get_section_by_id($restrict_to_section);

						if (($restrict_to_section_detail->class_id )!== $class_id) {
							throw new Exception(esc_html__('You don\'t have permission to save for this class.', 'school-management'));
						}
					}
				}

				// Exam papers.
				if ( ! count( $paper_code ) ) {
					wp_send_json_error( esc_html__( 'Please add at least one exam paper.', 'school-management' ) );
				} else {
					if ( 1 !== count( array_unique( array( count( $subject_label ), count( $subject_type ), count( $maximum_marks ), count( $paper_code ), count( $paper_date ), count( $start_time ), count( $end_time ), count( $room_number ) ) ) ) ) {
						wp_send_json_error( esc_html__( 'Invalid exam paper.', 'school-management' ) );
					} elseif ( count( $paper_code ) !== count( array_unique( $paper_code ) ) ) {
						wp_send_json_error( esc_html__( 'Paper codes must be different.', 'school-management' ) );
					} else {
						foreach ( $paper_code as $key => $value ) {
							$subject_id[ $key ]    = sanitize_text_field( $subject_id[ $key ] );
							$subject_label[ $key ] = sanitize_text_field( $subject_label[ $key ] );
							$subject_type[ $key ]  = sanitize_text_field( $subject_type[ $key ] );
							$maximum_marks[ $key ] = absint( $maximum_marks[ $key ] );
							$paper_code[ $key ]    = sanitize_text_field( $value );
							$paper_date[ $key ]    = DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $paper_date[ $key ] ) );
							$start_time[ $key ]    = DateTime::createFromFormat( WLSM_Config::get_default_time_format(), sanitize_text_field( $start_time[ $key ] ) );
							$end_time[ $key ]      = DateTime::createFromFormat( WLSM_Config::get_default_time_format(), sanitize_text_field( $end_time[ $key ] ) );
							$room_number[ $key ]   = sanitize_text_field( $room_number[ $key ] );

							if ( empty( $subject_label[ $key ] ) ) {
								wp_send_json_error( esc_html__( 'Please provide subject name.', 'school-management' ) );
							} elseif ( strlen( $subject_label[ $key ] ) > 100 ) {
								wp_send_json_error( esc_html__( 'Maximum length cannot exceed 100 characters.', 'school-management' ) );
							}


							if ( empty( $paper_code[ $key ] ) ) {
								wp_send_json_error( esc_html__( 'Please provide paper code.', 'school-management' ) );
							} elseif ( strlen( $paper_code[ $key ] ) > 40 ) {
								wp_send_json_error( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) );
							}

							if ( $maximum_marks[ $key ] < 1 ) {
								wp_send_json_error( esc_html__( 'Maximum marks must be a positive integer.', 'school-management' ) );
							} elseif ( $maximum_marks[ $key ] > 9999 ) {
								wp_send_json_error( esc_html__( 'Maximum marks must be lower than 10000.', 'school-management' ) );
							}

							if ( ! empty( $room_number[ $key ] ) && ( strlen( $room_number[ $key ] ) > 40 ) ) {
								wp_send_json_error( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) );
							}

							if ( empty( $paper_date[ $key ] ) ) {
								wp_send_json_error( esc_html__( 'Please provide exam paper date.', 'school-management' ) );
							} else {
								$exam_paper_date    = $paper_date[ $key ];
								$paper_date[ $key ] = $exam_paper_date->format( 'Y-m-d' );
							}

							if ( empty( $start_time[ $key ] ) ) {
								$start_time[ $key ] = NULL;
							} else {
								$exam_start_time    = $start_time[ $key ];
								$start_time[ $key ] = $exam_start_time->format( 'H:i:s' );
							}

							if ( empty( $end_time[ $key ] ) ) {
								$end_time[ $key ] = NULL;
							} else {
								$exam_end_time    = $end_time[ $key ];
								$end_time[ $key ] = $exam_end_time->format( 'H:i:s' );
							}
						}
					}
				}

				// Grade criteria.
				if ( 1 !== count( array_unique( array( count( $gc_grade ), count( $gc_min ), count( $gc_max ) ) ) ) ) {
					wp_send_json_error( esc_html__( 'Invalid grade criteria.', 'school-management' ) );
				} else {
					$i      = 0;
					$length = count( $gc_grade );

					$marks_grades = array();
					foreach ( $gc_grade as $key => $grade ) {
						$min = absint( $gc_min[ $key ] );
						$max = absint( $gc_max[ $key ] );

						if ( empty( $grade ) ) {
							wp_send_json_error( esc_html__( 'Please specify grade.', 'school-management' ) );
						}

						if( $min > $max ) {
							wp_send_json_error(
								sprintf(
									/* translators: 1: minimum percentage, 2: maximum percentage */
									__( 'Minimum percentage %1$s must be greater than maximum percentage %2$s.', 'school-management' ), $min, $max
								)
							);
						}

						$last_max = isset( $gc_max[ $key - 1 ] ) ? absint( $gc_max[ $key - 1 ] ) : 0;

						if ( $last_max > 0 && ( ( $last_max + 1 ) !== $min ) ) {
							wp_send_json_error(
								sprintf(
									/* translators: 1: minimum percentage, 2: last maximum percentage, 3: correct value of minimum percentage */
									__( 'Minimum percentage %1$s must be greater than last maximum percentage %2$s by 1%% (which is %3$s).', 'school-management' ), $min, $last_max, $last_max + 1
								)
							);
						}

						if ( 0 === $i ) {
							if ( 0 !== $min ) {
								wp_send_json_error( esc_html__( 'First minimum percentage must be 0 for grade criteria.', 'school-management' ) );
							}
						} elseif ( ( $length - 1 ) === $i ) {
							if ( 100 !== $max ) {
								wp_send_json_error( esc_html__( 'Last maximum percentage must be 100 for grade criteria.', 'school-management' ) );
							}
						}

						$i++;

						array_push(
							$marks_grades,
							array(
								'min'   => $min,
								'max'   => $max,
								'grade' => sanitize_text_field( $grade ),
							)
						);
					}
				}

				$grade_criteria = serialize(
					array(
						'enable_overall_grade' => $enable_overall_grade,
						'marks_grades'         => $marks_grades
					)
				);

				// Psychmotor
				$psychomotor = serialize(
				array(
					'psych' => $psych,
					'scale' => $scale,
					'def'   => $def,
					)
				);

				// Exam data.
				$exam_data = array(
					'label'                  => $exam_title,
					'exam_center'            => $exam_center,
					'exam_group'             => $exam_group,
					'start_date'             => $start_date,
					'end_date'               => $end_date,
					'grade_criteria'         => $grade_criteria,
					'psychomotor'            => $psychomotor,
					'enable_room_numbers'    => $enable_room_numbers,
					'enable_total_marks'     => $enable_total_marks,
					'results_obtained_marks' => $results_obtained_marks,
					'results_published'      => $results_published,
					'admit_cards_published'  => $admit_cards_published,
					'time_table_published'   => $time_table_published,
					'show_in_assessment'     => $show_in_assessment,
					'is_active'              => $is_active,
					'show_rank'              => $show_rank,
					'show_remark'            => $show_remark,
					'show_eremark'           => $show_eremark,
					'psychomotor_analysis'   => $psychomotor_analysis,
					'teacher_signature'   	=> $attachment_id,
				);

				$exam_data['updated_at'] 	= current_time( 'Y-m-d H:i:s' );
				$exam_data['school_id'] 	= $school_id;

				$success = $wpdb->update( WLSM_EXAMS, $exam_data, array('ID' => $exam_id, 'school_id' => $school_id) );

				if ( $class_school ) {
					$class_school_id = isset($class_school->ID ) ? absint($class_school->ID) : 0;

					// Insert single record with IGNORE (to avoid duplicates).
					$sql = 'INSERT IGNORE INTO ' . WLSM_CLASS_SCHOOL_EXAM . ' (class_school_id, exam_id) VALUES (%d, %d)';
					$success = $wpdb->query( $wpdb->prepare( $sql, $class_school_id, $exam_id ) );

					// Delete records for this exam_id except the current class_school_id
					$sql = 'DELETE FROM ' . WLSM_CLASS_SCHOOL_EXAM . ' WHERE exam_id = %d AND class_school_id != %d';
					$success = $wpdb->query( $wpdb->prepare( $sql, $exam_id, $class_school_id ) );
				} else {
					$success = $wpdb->delete( WLSM_CLASS_SCHOOL_EXAM, array( 'exam_id' => $exam_id ) );
				}

				// Exam papers.
				$place_holders_paper_codes = array();

				$paper_order = 10;
				foreach ( $paper_code as $key => $value ) {
					array_push( $place_holders_paper_codes, '%s' );
					$paper_order++;

					// Exam paper data.
					$exam_paper_data = array(
						'subject_id'    => $subject_id[ $key ],
						'subject_label' => $subject_label[ $key ],
						'subject_type'  => $subject_type[ $key ],
						'maximum_marks' => $maximum_marks[ $key ],
						'paper_date'    => $paper_date[ $key ],
						'start_time'    => $start_time[ $key ],
						'end_time'      => $end_time[ $key ],
						'room_number'   => $room_number[ $key ],
						'paper_order'   => $paper_order,
					);

					$exam_paper_exist = $wpdb->get_row( $wpdb->prepare( 'SELECT ep.ID FROM ' . WLSM_EXAM_PAPERS . ' as ep WHERE ep.exam_id = %d AND ep.paper_code = %s', $exam_id, $value ) );

					if ( $exam_paper_exist ) {
						// Exam paper exists, update exam paper.
						$exam_paper_data['updated_at'] = current_time( 'Y-m-d H:i:s' );

						$success = $wpdb->update( WLSM_EXAM_PAPERS, $exam_paper_data, array( 'ID' => $exam_paper_exist->ID, 'exam_id' => $exam_id ) );
					} else {
						// Exam paper does not exist, insert exam paper.
						$exam_paper_data['paper_code'] = $value;
						$exam_paper_data['exam_id']    = $exam_id;

						$exam_paper_data['created_at'] = current_time( 'Y-m-d H:i:s' );

						$success = $wpdb->insert( WLSM_EXAM_PAPERS, $exam_paper_data );
					}

					// Delete exam papers not in paper_code array.
					$exam_id_paper_codes = array_merge( array( $exam_id ), array_values( $paper_code ) );

					$success = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . WLSM_EXAM_PAPERS . ' WHERE exam_id = %d AND paper_code NOT IN (' . implode( ', ', $place_holders_paper_codes ) . ')', $exam_id_paper_codes ) );
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit exam.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete exam.
	public static function delete_exam($request) {
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_exams', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$exam_id 	= isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_EXAMS, array('ID' => $exam_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete exam.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Students without admit card.
	public static function students_without_admit_card($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_admit_cards', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$exam_id    = isset( $params['exam_id'] ) ? absint($params['exam_id']) : 0;
		$class_id   = isset( $params['class_id'] ) ? absint($params['class_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$students_with_admit_card 	= WLSM_M::get_students_with_admit_card($school_id, $exam_id);
				$class_students 			= WLSM_M::get_class_students($school_id, $class_id);

				$students_without_admit_card = array_diff_key($class_students, $students_with_admit_card);
				if ( ! $students_without_admit_card ) {
					throw new Exception(esc_html__('No student without admit cards.', 'school-management'));
				}

				foreach( $students_without_admit_card as $student ) {
					$data[] = array(
						'ID' 				=> isset($student->ID) ? absint($student->ID) : 0,
						'name' 				=> isset($student->name) ? WLSM_M_Staff_Class::get_label_text($student->name) : '',
						'enrollment_number' => isset($student->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($student->enrollment_number) : '',
						'phone' 			=> isset($student->phone) ? WLSM_M_Staff_Class::get_phone_text($student->phone) : '',
						'email' 			=> isset($student->email) ? WLSM_M_Staff_Class::get_email_text($student->email) : '',
						'class_label' 		=> isset($student->class_label) ? WLSM_M_Staff_Class::get_label_text($student->class_label) : '',
						'section_label' 	=> isset($student->section_label) ? WLSM_M_Staff_Class::get_label_text($student->section_label) : '',
					);
					$response_data['students_without_admit_card'] = $data;
				}

				$success = true;
				$message = esc_html__('Students Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add admit card.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View admit cards.
	public static function view_admit_cards($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_admit_cards', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$exam_id 	= isset($params['exam_id']) ? absint($params['exam_id']) : 0;

				$exam 		= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_admit_cards 	= WLSM_M_Staff_Examination::get_exam_admit_cards($school_id, $exam_id);

				if ( ! $exam_admit_cards ) {
					throw new Exception(esc_html__('Admit cards not found.', 'school-management'));
				}

				foreach ($exam_admit_cards as $admit_card) {
					$data[] = array(
						'ID' 						=> isset($admit_card->ID) ? absint($admit_card->ID) : 0,
						'name' 						=> isset($admit_card->name) ? WLSM_M_Staff_Class::get_label_text($admit_card->name) : '',
						'roll_number' 				=> isset($admit_card->roll_number) ? WLSM_M_Staff_Class::get_label_text($admit_card->roll_number) : '',
						'class_label' 				=> isset($admit_card->class_label) ? WLSM_M_Staff_Class::get_label_text($admit_card->class_label) : '',
						'section_label' 			=> isset($admit_card->section_label) ? WLSM_M_Staff_Class::get_label_text($admit_card->section_label) : '',
						'enrollment_number' 		=> isset($admit_card->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($admit_card->enrollment_number) : '',
						'phone' 					=> isset($admit_card->phone) ? WLSM_M_Staff_Class::get_phone_text($admit_card->phone) : '',
						'email' 					=> isset($admit_card->email) ? WLSM_M_Staff_Class::get_email_text($admit_card->email) : '',
						'class_id' 					=> isset($admit_card->class_id) ? absint($admit_card->class_id) : 0,
						'student_id' 				=> isset($admit_card->student_id) ? absint($admit_card->student_id) : 0,
					);
					$response_data['exam_admit_cards'] = $data;
				}

				$success = true;
				$message = esc_html__('Admit cards Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view admit cards.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Generate admit cards.
	public static function generate_admit_cards($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_admit_cards', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 			= $request->get_params();

		$prefix  			= isset( $params['prefix'] ) ? sanitize_text_field( $params['prefix'] ) : '';
		$starting_roll_no 	= isset( $params['starting_roll_no'] ) ? absint( $params['starting_roll_no'] ) : 0;
		$exam_id 			= isset($params['exam_id']) ? absint($params['exam_id']) : 0;
		$student_ids 		= isset($params['student_ids']) && is_array($params['student_ids']) ? $params['student_ids'] : array();

		try {

			if ($staff_permissions) {
				global $wpdb;

				foreach ($student_ids as $student_record_id) {
					$roll_number = $prefix . $starting_roll_no;

					$admit_card_data = array(
						'roll_number'        => $roll_number,
						'exam_id'            => $exam_id,
						'student_record_id'  => $student_record_id,
					);

					$admit_card_data['created_at'] 	= current_time( 'Y-m-d H:i:s' );

					$success = $wpdb->insert( WLSM_ADMIT_CARDS, $admit_card_data );

					if (false === $success) {
						throw new Exception($wpdb->last_error);
					}

					$starting_roll_no++;
				}

				WLSM_Helper::check_buffer();

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Admit Card Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new admit card.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Admit card details.
	public static function admit_card_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_admit_cards', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$admit_card_id  = isset( $params['admit_card_id'] ) ? absint($params['admit_card_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$admit_card = WLSM_M_Staff_Examination::fetch_admit_card($school_id, $admit_card_id);
				if ( ! $admit_card ) {
					throw new Exception(esc_html__('Admit card not found.', 'school-management'));
				}

				$exam_id = isset( $admit_card->exam_id ) ? absint( $admit_card->exam_id ) : 0;
				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_papers 	= WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);
				if ( ! $exam_papers ) {
					throw new Exception(esc_html__('Exam papers not found.', 'school-management'));
				}

				$student_data = array(
					'ID' 				=> isset($admit_card->student_id) ? absint($admit_card->student_id) : 0,
					'name'				=> isset($admit_card->name) ? WLSM_M_Staff_Class::get_name_text($admit_card->name) : '',
					'exam_roll_number' 	=> isset($admit_card->roll_number) ? WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number) : '',
					'enrollment_number' => isset($admit_card->enrollment_number) ? WLSM_M_Staff_Class::get_roll_no_text($admit_card->enrollment_number) : '',
					'class_label' 		=> isset($admit_card->class_label) ? WLSM_M_Staff_Class::get_label_text($admit_card->class_label) : '',
					'section_label' 	=> isset($admit_card->section_label) ? WLSM_M_Staff_Class::get_label_text($admit_card->section_label) : '',
				);
				$response_data['student_data'] = $student_data;

				$exam_data = array(
					'ID' 			=> isset($exam->ID) ? absint($exam->ID) : 0,
					'exam_title'	=> isset($exam->exam_title) ? WLSM_M_Staff_Class::get_label_text($exam->exam_title) : '',
					'exam_center' 	=> isset($exam->exam_center) ? WLSM_M_Staff_Class::get_label_text($exam->exam_center) : '',
					'start_date' 	=> isset($exam->start_date) ? WLSM_M_Staff_Class::get_date_text($exam->start_date) : '',
					'end_date' 		=> isset($exam->end_date) ? WLSM_M_Staff_Class::get_date_text($exam->end_date) : '',
				);
				$response_data['exam_data'] = $exam_data;

				foreach ($exam_papers as $paper) {
					$exam_paper_data = [
						'subject_label' 	=> isset($paper->subject_label) ? $paper->subject_label : '',
						'paper_code' 		=> isset($paper->paper_code) ? $paper->paper_code : '',
						'paper_date'		=> isset($paper->paper_date) ? $paper->paper_date : '',
						'start_time' 		=> isset($paper->start_time) ? WLSM_Config::get_time_text($paper->start_time) : '',
						'end_time' 			=> isset($paper->end_time) ? WLSM_Config::get_time_text($paper->end_time) : '',
						'room_number'		=> isset($paper->room_number) ? $paper->room_number : '',
					];
					$response_data['exam_paper_data'][] = $exam_paper_data;
				}

				$success = true;
				$message = esc_html__('Admit Card Details Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view admit card details.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit admit card.
	public static function edit_admit_card($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_admit_cards', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {

			if ($staff_permissions) {
				global $wpdb;

				$params 			= $request->get_params();
				$id     			= isset( $params['id'] ) ? absint($params['id']) : 0;
				$exam_id 			= isset($params['exam_id']) ? absint($params['exam_id']) : 0;
				$student_record_id 	= isset($params['student_id']) ? absint($params['student_id']) : 0;
				$roll_number 		= isset($params['roll_number']) ? sanitize_text_field($params['roll_number']) : '';

				$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$admit_card = WLSM_M_Staff_Examination::get_admit_card($school_id, $id);
				if ( ! $admit_card ) {
					throw new Exception(esc_html__('Admit card not found.', 'school-management'));
				}

				$admit_card_data = array(
					'roll_number'        => $roll_number,
					'exam_id'            => $exam_id,
				);

				$success = $wpdb->update(WLSM_ADMIT_CARDS, $admit_card_data, array('ID' => $id, 'student_record_id' => $student_record_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Roll Number Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit admit card.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete admit card.
	public static function delete_admit_card($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_admit_cards', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 			= $request->get_params();
		$id 				= isset($params['id']) ? absint($params['id']) : 0;
		$exam_id 			= isset($params['exam_id']) ? absint($params['exam_id']) : 0;
		$student_record_id 	= isset($params['student_id']) ? absint($params['student_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$admit_card = WLSM_M_Staff_Examination::get_admit_card($school_id, $id);
				if ( ! $admit_card ) {
					throw new Exception(esc_html__('Admit card not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_ADMIT_CARDS, array('ID' => $id, 'student_record_id' => $student_record_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Admit Card Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete admit card.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Students without result.
	public static function students_without_result($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$exam_id    = isset( $params['exam_id'] ) ? absint($params['exam_id']) : 0;
		$class_id   = isset( $params['class_id'] ) ? absint($params['class_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$students_with_result 	= WLSM_M::get_students_with_result($school_id, $exam_id);
				$class_students 		= WLSM_M::get_class_students($school_id, $class_id);

				$students_without_result = array_diff_key($class_students, $students_with_result);

				if ( ! $students_without_result ) {
					throw new Exception(esc_html__('No student without results.', 'school-management'));
				}

				foreach( $students_without_result as $student ) {
					$student_id 	= isset($student->ID) ? absint($student->ID) : 0;
					$admit_card 	= WLSM_M_Staff_Examination::get_admit_card_by_exam_student($school_id, $exam_id, $student_id);
					$admit_card_id 	= isset($admit_card->ID) ? absint($admit_card->ID) : 0;

					$data[] = array(
						'ID' 				=> isset($student_id) ? absint($student_id) : 0,
						'name' 				=> isset($student->name) ? WLSM_M_Staff_Class::get_label_text($student->name) : '',
						'enrollment_number' => isset($student->enrollment_number) ? WLSM_M_Staff_Class::get_label_text($student->enrollment_number) : '',
						'phone' 			=> isset($student->phone) ? WLSM_M_Staff_Class::get_phone_text($student->phone) : '',
						'email' 			=> isset($student->email) ? WLSM_M_Staff_Class::get_email_text($student->email) : '',
						'class_label' 		=> isset($student->class_label) ? WLSM_M_Staff_Class::get_label_text($student->class_label) : '',
						'section_label' 	=> isset($student->section_label) ? WLSM_M_Staff_Class::get_label_text($student->section_label) : '',
						'admit_card_id' 	=> isset($admit_card_id) ? absint($admit_card_id) : 0,
					);
					$response_data['students_without_result'] = $data;
				}

				$success = true;
				$message = esc_html__('Students Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Exam paper details.
	public static function exam_paper_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$exam_id    = isset( $params['exam_id'] ) ? absint($params['exam_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_papers 	= WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);
				if ( ! $exam_papers ) {
					throw new Exception(esc_html__('Exam papers not found.', 'school-management'));
				}

				foreach( $exam_papers as $exam_paper ) {
					$data[] = array(
						'ID' 				=> isset($exam_paper->ID) ? absint($exam_paper->ID) : 0,
						'paper_code' 		=> isset($exam_paper->paper_code) ? WLSM_M_Staff_Class::get_label_text($exam_paper->paper_code) : '',
						'subject_label' 	=> isset($exam_paper->name) ? WLSM_M_Staff_Class::get_label_text($exam_paper->name) : '',
						'subject_type' 		=> isset($exam_paper->subject_type) ? WLSM_M_Staff_Class::get_label_text($exam_paper->subject_type) : '',
						'maximum_marks' 	=> isset($exam_paper->maximum_marks) ? WLSM_M_Staff_Class::get_label_text($exam_paper->maximum_marks) : '',
					);
					$response_data['exam_papers'] = $data;
				}

				$success = true;
				$message = esc_html__('Exam Papers Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View results.
	public static function view_results($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$exam_id 	= isset($params['exam_id']) ? absint($params['exam_id']) : 0;

				$exam 		= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_results 	= WLSM_M_Staff_Examination::get_exam_results( $school_id, $exam_id, 'DESC' );
				$student_ranks 	= WLSM_M_Staff_Examination::calculate_exam_ranks( '', '', $exam_results );

				foreach ( $exam_results as $exam_result ) {
					$percentage = WLSM_Config::get_percentage_text( $exam_result->total_marks, $exam_result->obtained_marks );

					if ( isset( $student_ranks[ $exam_result->ID ] ) ) {
						$rank = $student_ranks[ $exam_result->ID ];
					} else {
						$rank = '-';
					}

					$data[] = array(
						'ID' 				=> isset($exam_result->ID) ? absint($exam_result->ID) : 0,
						'name' 				=> isset($exam_result->name) ? WLSM_M_Staff_Class::get_name_text( $exam_result->name ) : '',
						'roll_number' 		=> isset($exam_result->roll_number) ? WLSM_M_Staff_Class::get_roll_no_text( $exam_result->roll_number ) : '',
						'class_label' 		=> isset($exam_result->class_label) ? WLSM_M_Class::get_label_text( $exam_result->class_label ) : '',
						'section_label' 	=> isset($exam_result->section_label) ? WLSM_M_Staff_Class::get_section_label_text($exam_result->section_label) : '',
						'percentage' 		=> $percentage,
						'rank' 				=> $rank,
						'enrollment_number' => isset($exam_result->enrollment_number) ? WLSM_M_Staff_Class::get_roll_no_text( $exam_result->enrollment_number ) : '',
						'admit_card_id' 	=> isset($exam_result->admit_card_id) ? WLSM_M_Staff_Class::get_roll_no_text( $exam_result->admit_card_id ) : '',
					);
					$response_data['exam_results'] = $data;
				}

				$success = true;
				$message = esc_html__('Results Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Exam result.
	public static function add_new_exam_result($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$admit_card_id 	= isset( $params['admit_card_id'] ) ? absint( $params['admit_card_id'] ) : 0;
		$obtained_marks = isset( $params['obtained_marks'] ) && is_array( $params['obtained_marks'] ) ? $params['obtained_marks'] : array();
		$remark         = isset( $params['remark'] ) && is_array( $params['remark'] ) ? $params['remark'] : array();
		$scale          = isset( $params['scale'] ) && is_array( $params['scale'] ) ? $params['scale'] : array();
		$teacher_remark = isset( $params['teacher_remark'] ) ? sanitize_text_field( $params['teacher_remark'] ) : '';
		$school_remark  = isset( $params['school_remark'] ) ? sanitize_text_field( $params['school_remark'] ) : '';
		$attachment 	= isset($_FILES['attachment']) ? $_FILES['attachment'] : NULL;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card( $school_id, $admit_card_id );
				if ( $exam_results ) {
					throw new Exception( esc_html__( 'Exam result already exists for this admit card.', 'school-management' ) );
				}

				$admit_card = WLSM_M_Staff_Examination::get_admit_card( $school_id, $admit_card_id );
				if ( ! $admit_card ) {
					throw new Exception( esc_html__( 'Admit card not found.', 'school-management' ) );
				}

				$exam_id = isset( $admit_card->exam_id ) ? absint( $admit_card->exam_id ) : 0;
				$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_papers  = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card( $school_id, $admit_card_id );
				if ( count( $exam_papers ) < 1 ) {
					throw new Exception( esc_html__( 'Please assign the subjects to student', 'school-management' ) );
				}

				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'image')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				$attachment_url = '';

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'image')) {
								throw new Exception(esc_html__('Invalid file format. Only JPG, JPEG and PNG allowed.', 'school-management'));
							}

							$allowed_mimes = array(
								'jpg|jpeg|jpe'	=> 'image/jpeg',
								'gif'			=> 'image/gif',
								'png'			=> 'image/png',
								'pdf'			=> 'application/pdf',
							);

							$attachment = wp_handle_sideload( $_FILES['attachment'], array(
									'test_form' => false,
									'mimes'		=> $allowed_mimes,
									'unique_filename_callback' => 'some_string'.rand(2, 4),
								)
							);

							$attachment_url = isset($attachment['url']) ? esc_url_raw($attachment['url']) : '';
						}
					}
				}

				$scale = serialize($scale);

				foreach ( $exam_papers as $exam_paper ) {
					if ( isset( $obtained_marks[ $exam_paper->ID ] ) ) {

						$marks_obtained = WLSM_Config::sanitize_marks( $obtained_marks[ $exam_paper->ID ] );

						if ( $exam_paper->maximum_marks < $marks_obtained ) {
							throw new Exception( esc_html__( 'Obtained marks can\'t be greater than maximum marks.', 'school-management' ) );
						}

						$exam_result_data = array(
							'obtained_marks' => $marks_obtained,
							'teacher_remark' => $teacher_remark,
							'school_remark'  => $school_remark,
							'scale'          => $scale,
							'remark'         => $remark[$exam_paper->ID],
							'exam_paper_id'  => $exam_paper->ID,
							'answer_key'     => $attachment_url,
							'admit_card_id'  => $admit_card_id
						);

						$exam_result_data['created_at'] = current_time( 'Y-m-d H:i:s' );

						$success = $wpdb->insert( WLSM_EXAM_RESULTS, $exam_result_data );

						if (false === $success) {
							throw new Exception($wpdb->last_error);
						}
					}
				}

				$success = true;
				$message = esc_html__('Exam Result Added Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Exam result details.
	public static function exam_result_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$admit_card_id  = isset( $params['admit_card_id'] ) ? absint($params['admit_card_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$admit_card = WLSM_M_Staff_Examination::fetch_admit_card($school_id, $admit_card_id);
				if ( ! $admit_card ) {
					throw new Exception(esc_html__('Admit card not found.', 'school-management'));
				}

				$exam_id = isset( $admit_card->exam_id ) ? absint( $admit_card->exam_id ) : 0;
				$exam 			= WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_papers 	= WLSM_M_Staff_Examination::fetch_exam_papers($school_id, $exam_id);
				if ( ! $exam_papers ) {
					throw new Exception(esc_html__('Exam papers not found.', 'school-management'));
				}

				$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card($school_id, $admit_card_id);

				$student_data = array(
					'ID' 				=> isset($admit_card->student_id) ? absint($admit_card->student_id) : 0,
					'name'				=> isset($admit_card->name) ? WLSM_M_Staff_Class::get_name_text($admit_card->name) : '',
					'exam_roll_number' 	=> isset($admit_card->roll_number) ? WLSM_M_Staff_Class::get_roll_no_text($admit_card->roll_number) : '',
					'enrollment_number' => isset($admit_card->enrollment_number) ? WLSM_M_Staff_Class::get_roll_no_text($admit_card->enrollment_number) : '',
					'class_label' 		=> isset($admit_card->class_label) ? WLSM_M_Staff_Class::get_label_text($admit_card->class_label) : '',
					'section_label' 	=> isset($admit_card->section_label) ? WLSM_M_Staff_Class::get_label_text($admit_card->section_label) : '',
				);
				$response_data['student_data'] = $student_data;

				$exam_data = array(
					'ID' 			=> isset($exam->ID) ? absint($exam->ID) : 0,
					'exam_title'	=> isset($exam->exam_title) ? WLSM_M_Staff_Class::get_label_text($exam->exam_title) : '',
					'exam_center' 	=> isset($exam->exam_center) ? WLSM_M_Staff_Class::get_label_text($exam->exam_center) : '',
					'start_date' 	=> isset($exam->start_date) ? WLSM_M_Staff_Class::get_date_text($exam->start_date) : '',
					'end_date' 		=> isset($exam->end_date) ? WLSM_M_Staff_Class::get_date_text($exam->end_date) : '',
				);
				$response_data['exam_data'] = $exam_data;

				foreach ($exam_papers as $paper) {
					$paper_id = $paper->ID;

					if (isset($exam_results[$paper_id])) {
						$result = $exam_results[$paper_id];
						$result_data = [
							'obtained_marks' 	=> isset($result->obtained_marks) ? $result->obtained_marks : '',
							'remark' 			=> isset($result->remark) ? $result->remark : '',
							'teacher_remark'	=> isset($result->teacher_remark) ? $result->teacher_remark : '',
							'school_remark'		=> isset($result->school_remark) ? $result->school_remark : '',
							'scale' 			=> isset($result->scale) ? maybe_unserialize($result->scale) : array(),
							'answer_key'		=> isset($result->answer_key) ? $result->answer_key : '',
						];
					}

					$merged_results[] = array_merge((array)$paper, $result_data);
					$response_data['exam_papers'] = $merged_results;
				}

				$success = true;
				$message = esc_html__('Exam Result Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit exam result.
	public static function edit_exam_result($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$admit_card_id 	= isset( $params['admit_card_id'] ) ? absint( $params['admit_card_id'] ) : 0;
		$obtained_marks = isset( $params['obtained_marks'] ) && is_array( $params['obtained_marks'] ) ? $params['obtained_marks'] : array();
		$remark         = isset( $params['remark'] ) && is_array( $params['remark'] ) ? $params['remark'] : array();
		$scale          = isset( $params['scale'] ) && is_array( $params['scale'] ) ? $params['scale'] : array();
		$teacher_remark = isset( $params['teacher_remark'] ) ? sanitize_text_field( $params['teacher_remark'] ) : '';
		$school_remark  = isset( $params['school_remark'] ) ? sanitize_text_field( $params['school_remark'] ) : '';
		$attachment 	= isset($_FILES['attachment']) ? $_FILES['attachment'] : NULL;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$admit_card = WLSM_M_Staff_Examination::fetch_admit_card( $school_id, $admit_card_id );
				if ( ! $admit_card ) {
					throw new Exception( esc_html__( 'Admit card not found.', 'school-management' ) );
				}

				$exam_id = isset( $admit_card->exam_id ) ? absint( $admit_card->exam_id ) : 0;
				$exam = WLSM_M_Staff_Examination::fetch_exam($school_id, $exam_id);
				if ( ! $exam ) {
					throw new Exception(esc_html__('Exam not found.', 'school-management'));
				}

				$exam_papers  = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card( $school_id, $admit_card_id );
				if ( count( $exam_papers ) < 1 ) {
					throw new Exception( esc_html__( 'Please assign the subjects to student', 'school-management' ) );
				}

				$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card( $school_id, $admit_card_id );
				if ( $exam_results ) {

					if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
						if (!WLSM_Helper::is_valid_file($attachment, 'image')) {
							throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
						}
					}

					$attachment_url = '';

					if (isset($attachment) && is_array($attachment)) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';

						if (isset($attachment) && is_array($attachment)) {
							if (!empty($_FILES['attachment']['tmp_name'])) {
								$file_array = array(
									'name'     => $_FILES['attachment']['name'],
									'type'     => $_FILES['attachment']['type'],
									'tmp_name' => $_FILES['attachment']['tmp_name'],
									'error'    => $_FILES['attachment']['error'],
									'size'     => $_FILES['attachment']['size'],
								);

								if (!WLSM_Helper::is_valid_file($file_array, 'image')) {
									throw new Exception(esc_html__('Invalid file format. Only JPG, JPEG and PNG allowed.', 'school-management'));
								}

								$allowed_mimes = array(
									'jpg|jpeg|jpe'	=> 'image/jpeg',
									'gif'			=> 'image/gif',
									'png'			=> 'image/png',
									'pdf'			=> 'application/pdf',
								);

								$attachment = wp_handle_sideload( $_FILES['attachment'], array(
										'test_form' => false,
										'mimes'		=> $allowed_mimes,
										'unique_filename_callback' => 'some_string'.rand(2, 4),
									)
								);

								$attachment_url = isset($attachment['url']) ? esc_url_raw($attachment['url']) : '';
							}
						}
					}

					$scale = serialize($scale);

					foreach ( $exam_papers as $exam_paper ) {
						if ( isset( $obtained_marks[ $exam_paper->ID ] ) ) {
							$marks_obtained = WLSM_Config::sanitize_marks( $obtained_marks[ $exam_paper->ID ] );

							if ( $exam_paper->maximum_marks < $marks_obtained ) {
								throw new Exception( esc_html__( 'Obtained marks can\'t be greater than maximum marks.', 'school-management' ) );
							}

							if ( isset( $exam_results[ $exam_paper->ID ] ) ) {
								$exam_result = $exam_results[ $exam_paper->ID ];

								$exam_result_data = array(
									'obtained_marks' => $marks_obtained,
									'remark'         => $remark[$exam_paper->ID],
									'scale'          => $scale,
									'teacher_remark' => $teacher_remark,
									'school_remark'  => $school_remark,
									'answer_key'     => $attachment_url,
									'updated_at'     => current_time( 'Y-m-d H:i:s' )
								);

								$success = $wpdb->update( WLSM_EXAM_RESULTS, $exam_result_data, array( 'ID' => $exam_result->ID ) );

								if (false === $success) {
									throw new Exception($wpdb->last_error);
								}
							}
						}
					}
				}

				$success = true;
				$message = esc_html__('Exam Result Updated Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete exam result.
	public static function delete_exam_result($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$admit_card_id 	= isset($params['admit_card_id']) ? absint($params['admit_card_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$admit_card = WLSM_M_Staff_Examination::get_admit_card($school_id, $admit_card_id);
				if ( ! $admit_card ) {
					throw new Exception(esc_html__('Admit card not found.', 'school-management'));
				}

				$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card( $school_id, $admit_card_id );
				if ( ! $exam_results ) {
					throw new Exception( esc_html__( 'Exam result not found for this admit card.', 'school-management' ) );
				}

				$success = $wpdb->delete(WLSM_EXAM_RESULTS, array('admit_card_id' => $admit_card_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Exam Result Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete exam result.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View academic reports.
	public static function view_academic_reports($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$academic_reports = WLSM_M::fetch_academic_reports($school_id, $restrict_to_section);

				if ($academic_reports) {
					foreach ($academic_reports as $academic_report) {

						$exam_ids = isset($academic_report->exams) ? $academic_report->exams : [];
						if (!is_array($exam_ids)) {
							$exam_ids = json_decode($academic_report->exams, true);
						}

						if (!empty($exam_ids)) {
							global $wpdb;
							$placeholders = implode(',', array_fill(0, count($exam_ids), '%d'));
							$query = "SELECT ID, label FROM " . WLSM_EXAMS . " WHERE ID IN ($placeholders)";
							$exam_names = $wpdb->get_results($wpdb->prepare($query, $exam_ids));
							$exam_names = wp_list_pluck($exam_names, 'label');
						} else {
							$exam_names = [];
						}

						$data[] = array(
							'ID' 				=> isset($academic_report->ID) ? WLSM_M_Staff_Class::get_label_text($academic_report->ID) : '',
							'title' 			=> isset($academic_report->label) ? WLSM_M_Staff_Class::get_label_text($academic_report->label) : '',
							'class_label' 		=> isset($academic_report->class_label) ? WLSM_M_Staff_Class::get_label_text($academic_report->class_label) : '',
							'class_id' 			=> isset($academic_report->class_id) ? WLSM_M_Staff_Class::get_label_text($academic_report->class_id) : '',
							'exams' 			=> isset($exam_names) ? WLSM_M_Staff_Class::get_label_text($exam_names) : '',
							'exam_group_label' 	=> isset($academic_report->exam_group_label) ? WLSM_M_Staff_Class::get_label_text($academic_report->exam_group_label) : '',
						);
						$response_data['academic_reports'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Academic Reports Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Academic Report.
	public static function add_new_academic_report($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$exam_group		= isset($params['exam_group']) ? absint($params['exam_group']) : 0;
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$exam_ids 		= isset($params['exam_ids']) && is_array($params['exam_ids']) ? $params['exam_ids'] : [];

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($exam_group)) {
					throw new Exception(esc_html__('Please select an exam group.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($exam_ids)) {
					throw new Exception(esc_html__('Please select atleast an exam.', 'school-management'));
				}

				$exam_ids = json_encode($exam_ids);

				$data = array(
					'label' 			=> $label,
					'exam_group'		=> $exam_group,
					'class_id' 			=> $class_id,
					'exams' 			=> $exam_ids,
					'is_active' 		=> 1,
					'school_id' 		=> $school_id,
				);

				$data['created_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_ACADEMIC_REPORTS, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Academic Report Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add new exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Academic report details.
	public static function academic_report_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$academic_report 	= WLSM_M::fetch_academic_report($school_id, $id);

				if ($academic_report) {

					$exam_ids = isset($academic_report->exams) ? $academic_report->exams : [];
					if (!is_array($exam_ids)) {
						$exam_ids = json_decode($academic_report->exams, true);
					}

					if (!empty($exam_ids)) {
						global $wpdb;
						$placeholders = implode(',', array_fill(0, count($exam_ids), '%d'));
						$query = "SELECT ID, label FROM " . WLSM_EXAMS . " WHERE ID IN ($placeholders)";
						$exam_names = $wpdb->get_results($wpdb->prepare($query, $exam_ids));
						$exam_names = wp_list_pluck($exam_names, 'label');
					} else {
						$exam_names = [];
					}

					$data = array(
						'ID' 				=> isset($academic_report->ID) ? absint($academic_report->ID) : 0,
						'title' 			=> isset($academic_report->label) ? WLSM_M_Staff_Class::get_label_text($academic_report->label) : '',
						'exam_group'		=> isset($academic_report->exam_group) ? absint($academic_report->exam_group) : 0,
						'exam_group_label'	=> isset($academic_report->exam_group_label) ? WLSM_M_Staff_Class::get_label_text($academic_report->exam_group_label) : '',
						'class_label' 		=> isset($academic_report->class_label) ? WLSM_M_Staff_Class::get_label_text($academic_report->class_label) : '',
						'class_id' 			=> isset($academic_report->class_id) ? absint($academic_report->class_id) : 0,
						'exams' 			=> isset($exam_ids) ? $exam_ids : array(),
						'exams_name' 		=> isset($exam_names) ? WLSM_M_Staff_Class::get_label_text($exam_names) : '',
					);
					$response_data['academic_report'] = $data;
				}

				$success = true;
				$message = esc_html__('Academic Report Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit academic report.
	public static function edit_academic_report($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$exam_group		= isset($params['exam_group']) ? absint($params['exam_group']) : 0;
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$exam_ids 		= isset($params['exam_ids']) && is_array($params['exam_ids']) ? $params['exam_ids'] : [];

		try {

			if ($staff_permissions) {
				global $wpdb;

				$academic_report 	= WLSM_M::fetch_academic_report($school_id, $id);
				if (empty($academic_report)) {
					throw new Exception(esc_html__('Academic report not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($exam_group)) {
					throw new Exception(esc_html__('Please select an exam group.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($exam_ids)) {
					throw new Exception(esc_html__('Please select atleast an exam.', 'school-management'));
				}

				$exam_ids = json_encode($exam_ids);

				$data = array(
					'label' 			=> $label,
					'exam_group'		=> $exam_group,
					'class_id' 			=> $class_id,
					'exams' 			=> $exam_ids,
					'is_active' 		=> 1,
					'school_id' 		=> $school_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_ACADEMIC_REPORTS, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Academic Report Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete academic report.
	public static function delete_academic_report($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;
		try {

			if ($staff_permissions) {
				global $wpdb;

				$academic_report = WLSM_M::fetch_academic_report($school_id, $id);

				if (empty($academic_report)) {
					throw new Exception(esc_html__('Academic report not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_ACADEMIC_REPORTS, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Academic Report Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete exam results.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View academic report.
	public static function view_academic_report($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$params = $request->get_params();

				$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
				$report_id  = isset($params['report_id']) ? absint($params['report_id']) : 0;

				if (! $student_id) {
					throw new Exception(esc_html__('Student id is required.', 'school-management'));
				}

				if (! $report_id) {
					throw new Exception(esc_html__('Report id is required.', 'school-management'));
				}

				// Fetch student and school context.
				$student = WLSM_M_PARENT::fetch_student( $student_id );
				if (! $student) {
					throw new Exception(esc_html__('Student not found.', 'school-management'));
				}

				// $school_id = $student->school_id;
				$class_school_id = $student->class_school_id;
				$class_id = $student->class_id;

				$common_details = self::student_common_details($student);
				$response_data['student'] = $common_details;

				// Get academic report and exams.
				$academic_report = WLSM_M_Staff_Examination::get_academic_report($school_id, $report_id);
				if (! $academic_report) {
					throw new Exception(esc_html__('Academic report not found.', 'school-management'));
				}

				$response_data['report'] = array(
					'id' => $academic_report->ID,
					'label' => esc_html(stripslashes($academic_report->label)),
				);

				$exams = WLSM_M_Staff_Examination::get_class_school_exams_academic_report($school_id, $class_school_id, $report_id);

				// Subjects for the class and student
				$subjects = WLSM_M_Staff_Class::get_class_subjects_students($school_id, $class_id, $student_id);

				$exam_ids = array();
				$subject_list = array();

				// Prepare exam entries
				$exam_entries = array();
				foreach ($exams as $exam) {
					$exam_entries[$exam->ID] = array(
						'id' => $exam->ID,
						'title' => esc_html(stripslashes($exam->exam_title)),
						'start_date' => esc_html(WLSM_Config::get_date_text($exam->start_date)),
						'end_date' => esc_html(WLSM_Config::get_date_text($exam->end_date)),
						'show_rank' => (bool) $exam->show_rank,
						'show_remark' => (bool) $exam->show_remark,
						'enable_total_marks' => (bool) $exam->enable_total_marks,
					);
					array_push($exam_ids, $exam->ID);
				}

				// Per subject, collect per exam results
				foreach ($subjects as $subject) {
					$subject_row = array(
						'id' => $subject->ID,
						'label' => esc_html(WLSM_M_Staff_Class::get_subject_label_text($subject->label)),
						'code' => esc_html($subject->code),
						'exam_results' => array(),
					);

					$total_maximum_marks_subject = 0;
					$total_obtained_marks_subject = 0;
					$psychomotor_scale = array();
					$grade_subject_percentage = 0;
					$remark = '';

					foreach ($exams as $exam) {
						$exam_result = WLSM_M_Staff_Examination::get_exam_result_by_subject_code($school_id, $exam->ID, $student_id, $subject->code);
						if ($exam_result) {
							$subject_row['exam_results'][$exam->ID] = array(
								'maximum_marks' => (int) $exam_result->maximum_marks,
								'obtained_marks' => $exam_result->obtained_marks !== null ? (float) $exam_result->obtained_marks : '',
								'remark' => esc_html($exam_result->remark),
								'scale' => $exam_result->scale,
							);
							$total_maximum_marks_subject += $exam_result->maximum_marks;
							$total_obtained_marks_subject += WLSM_Config::sanitize_marks($exam_result->obtained_marks);
							$grade_subject_percentage = WLSM_Config::sanitize_percentage($total_maximum_marks_subject, $total_obtained_marks_subject);
						} else {
							$subject_row['exam_results'][$exam->ID] = null;
						}
					}

					// Overall grade for subject if possible
					$grade_criteria = array();
					if (!empty($exams)) {
						$first_exam = $exams[0];
						$grade_criteria = WLSM_Config::sanitize_grade_criteria($first_exam->grade_criteria);
					}
					$marks_grades = isset($grade_criteria['marks_grades']) ? $grade_criteria['marks_grades'] : array();
					if (!empty($marks_grades) && $grade_subject_percentage) {
						$subject_row['grade'] = esc_html(WLSM_Helper::calculate_grade($marks_grades, $grade_subject_percentage));
					} else {
						$subject_row['grade'] = '';
					}

					$subject_row['total_maximum_marks'] = $total_maximum_marks_subject;
					$subject_row['total_obtained_marks'] = $total_obtained_marks_subject;

					array_push($subject_list, $subject_row);
				}

				// Exam totals and percentages
				$exam_totals = array();
				$total_percentage_obtained = 0;
				$total_percentage_maximum = 0;
				foreach ($exams as $exam) {
					$er = WLSM_M_Staff_Examination::get_exam_results_total_by_student_id($school_id, $exam->ID, $student_id);
					if ($er && $er->total_marks) {
						$exam_totals[$exam->ID] = array(
							'total_marks' => (int) $er->total_marks,
							'obtained_marks' => (int) $er->obtained_marks,
							'percentage_value' => WLSM_Config::sanitize_percentage($er->total_marks, $er->obtained_marks),
							'percentage_text' => WLSM_Config::get_percentage_text($er->total_marks, $er->obtained_marks),
						);
						$total_percentage_obtained += $er->obtained_marks;
						$total_percentage_maximum += $er->total_marks;
					} else {
						$exam_totals[$exam->ID] = null;
					}
				}

				// Overall totals
				$overall_total_max = 0;
				$overall_total_obtained = 0;
				foreach ($exam_totals as $et) {
					if ($et) {
						$overall_total_max += $et['total_marks'];
						$overall_total_obtained += $et['obtained_marks'];
					}
				}

				$overall_percentage_value = 0;
				$overall_percentage_text = '-';
				if ($overall_total_max) {
					$overall_percentage_value = WLSM_Config::sanitize_percentage($overall_total_max, $overall_total_obtained);
					$overall_percentage_text = WLSM_Config::get_percentage_text($overall_total_max, $overall_total_obtained);
				}

				// Overall grade - use first exam's grade criteria if available
				$overall_grade = '';
				if (!empty($exams)) {
					$first_exam = $exams[0];
					$grade_criteria = WLSM_Config::sanitize_grade_criteria($first_exam->grade_criteria);
					$enable_overall_grade = $grade_criteria['enable_overall_grade'];
					$marks_grades = $grade_criteria['marks_grades'];
					if ($enable_overall_grade && !empty($marks_grades)) {
						$overall_grade = esc_html(WLSM_Helper::calculate_grade($marks_grades, $overall_percentage_value));
					}
				}

				// Ranks
				$overall_ranks = array();
				$results = $wpdb->get_results($wpdb->prepare(
					"SELECT student_id, total_marks FROM " . WLSM_STUDENT_TOTAL_MARKS . " WHERE report_id = %d ORDER BY total_marks DESC",
					$report_id
				));

				$rank = 1;
				foreach ($results as $row) {
					$overall_ranks[$row->student_id] = $rank++;
				}

				$exam_ranks = array();
				foreach ($exams as $exam) {
					$admit_card = WLSM_M_Staff_Examination::get_admit_card_by_exam_student($school_id, $exam->ID, $student_id);
					if ($admit_card) {
						$exam_ranks[$exam->ID] = WLSM_M_Staff_Examination::calculate_exam_ranks($school_id, $exam->ID, array(), $admit_card->ID);
					} else {
						$exam_ranks[$exam->ID] = '-';
					}
				}

				$response_data['report']['exams'] = $exam_entries;
				$response_data['report']['subjects'] = $subject_list;
				$response_data['report']['exam_totals'] = $exam_totals;
				$response_data['report']['overall'] = array(
					'total_maximum' => $overall_total_max,
					'total_obtained' => $overall_total_obtained,
					'percentage_value' => $overall_percentage_value,
					'percentage_text' => $overall_percentage_text,
					'overall_grade' => $overall_grade,
					'overall_rank' => isset($overall_ranks[$student_id]) ? $overall_ranks[$student_id] : '-',
					'exam_ranks' => $exam_ranks,
				);
			}

			$success = true;
			$message = esc_html__('Academic report retrieved successfully.', 'school-management');

			WLSM_Helper::check_buffer();
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Student Types.
	public static function view_student_types( $request ) {

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff( $user_id );

		if ( $staff ) {
			$school_id 			= isset( $staff->school_id ) ? absint( $staff->school_id ) : 0;
			$permissions 		= unserialize( $staff->permissions );
			$staff_permissions 	= in_array('manage_classes', $permissions);
		} else {
			throw new Exception( esc_html__( 'Staff not found.', 'school-management' ) );
		}

		try {

			if( $staff_permissions ) {

				global $wpdb;
				$student_types = WLSM_M::fetch_student_types( $school_id );

				if ( empty( $student_types ) ) {
					throw new Exception( esc_html__( 'Student types not found.', 'school-management' ) );
				}

				foreach ($student_types as $type) {
					$data = array(
						'ID' => isset( $type->ID ) ? $type->ID : '',
						'label' => isset( $type->label ) ? $type->label : '',
					);
					$types_array[] = $data;
				}

				$response_data['student_types'] = $types_array;

				$success = true;
				$message = esc_html__( 'Student Types Retrieved Successfully.', 'school-management' );

				WLSM_Helper::check_buffer();

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

			} else {
				$success = false;
				throw new Exception( esc_html__( 'You do not have permission to view student types.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if ( isset( $response_data ) ) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response( $response, 200 );

	}

	// Staff - Add New Student Type.
	public static function add_new_student_type( $request ) {

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff( $user_id );

		if ( $staff ) {
			$school_id 			= $staff->school_id;
			$permissions 		= unserialize( $staff->permissions );
			$staff_permissions 	= in_array('manage_classes', $permissions);
		} else {
			throw new Exception( esc_html__( 'Staff not found.', 'school-management' ) );
		}

		$params = $request->get_params();
		$label 	= isset( $params['student_type'] ) ? $params['student_type'] : NULL;

		try {

			if( $staff_permissions ) {

				if ( empty( $label ) ) {
					throw new Exception( esc_html__( 'Please enter a student type.', 'school-management' ) );
				}

				$student_type = WLSM_M_Staff_Class::get_student_type_by_label( $school_id, $label );

				if ( ! empty( $student_type ) ) {
					throw new Exception( esc_html__( 'Student type already exists.', 'school-management' ) );
				}

				global $wpdb;
				$data = array(
					'label' 		=> $label,
					'school_id' 	=> $school_id
				);

				$data['created_at'] = current_time( 'Y-m-d H:i:s' );

				$success = $wpdb->insert( WLSM_STUDENT_TYPE, $data );

				WLSM_Helper::check_buffer();

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$response_data = array();

				$success = true;
				$message = esc_html__( 'Student Type Added Successfully.', 'school-management' );

			} else {
				$success = false;
				throw new Exception( esc_html__( 'You do not have permission to add new student type.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response( $response, 200 );

	}

	// Staff - Delete Student Type.
	public static function delete_student_type( $request ) {

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff( $user_id );

		if ( $staff ) {
			$school_id 			= isset( $staff->school_id ) ? absint( $staff->school_id ) : 0;
			$permissions 		= unserialize( $staff->permissions );
			$staff_permissions 	= in_array('manage_classes', $permissions);
		} else {
			throw new Exception( esc_html__( 'Staff not found.', 'school-management' ) );
		}

		$params 			= $request->get_params();
		$student_type_id 	= isset( $params['id'] ) ? absint( $params['id'] ) : 0;

		try {

			if( $staff_permissions ) {

				global $wpdb;
				$student_type = WLSM_M_Staff_Class::get_student_type( $school_id, $student_type_id );

				if ( empty( $student_type ) ) {
					throw new Exception( esc_html__( 'Student type not found.', 'school-management' ) );
				}

				$success = $wpdb->delete( WLSM_STUDENT_TYPE, array( 'ID' => $student_type_id, 'school_id' => $school_id ) );

				WLSM_Helper::check_buffer();

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$response_data = array();

				$success = true;
				$message = esc_html__( 'Subject Type Deleted Successfully.', 'school-management' );

			} else {
				$success = false;
				throw new Exception( esc_html__( 'You do not have permission to delete student type.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response( $response, 200 );

	}

	// Staff - View Mediums.
	public static function view_mediums( $request ) {

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff( $user_id );

		if ( $staff ) {
			$school_id 			= isset( $staff->school_id ) ? absint( $staff->school_id ) : 0;
			$permissions 		= unserialize( $staff->permissions );
			$staff_permissions 	= in_array('manage_classes', $permissions);
		} else {
			throw new Exception( esc_html__( 'Staff not found.', 'school-management' ) );
		}

		try {

			if( $staff_permissions ) {

				global $wpdb;
				$mediums = WLSM_M::fetch_mediums( $school_id );

				if ( empty( $mediums ) ) {
					throw new Exception( esc_html__( 'Student types not found.', 'school-management' ) );
				}

				foreach ($mediums as $medium) {
					$data = array(
						'ID' => isset( $medium->ID ) ? $medium->ID : '',
						'label' => isset( $medium->label ) ? $medium->label : '',
					);
					$mediums_array[] = $data;
				}

				$response_data['mediums'] = $mediums_array;

				$success = true;
				$message = esc_html__( 'Mediums Retrieved Successfully.', 'school-management' );

				WLSM_Helper::check_buffer();

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

			} else {
				$success = false;
				throw new Exception( esc_html__( 'You do not have permission to view mediums.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if ( isset( $response_data ) ) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response( $response, 200 );

	}

	// Staff - Add New Medium.
	public static function add_new_medium( $request ) {

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff( $user_id );

		if ( $staff ) {
			$school_id 			= $staff->school_id;
			$permissions 		= unserialize( $staff->permissions );
			$staff_permissions 	= in_array('manage_classes', $permissions);
		} else {
			throw new Exception( esc_html__( 'Staff not found.', 'school-management' ) );
		}

		$params = $request->get_params();
		$label 	= isset( $params['medium'] ) ? $params['medium'] : NULL;

		try {

			if( $staff_permissions ) {

				if ( empty( $label ) ) {
					throw new Exception( esc_html__( 'Please enter a medium.', 'school-management' ) );
				}

				$medium = WLSM_M_Staff_Class::get_medium_by_label( $school_id, $label );

				if ( ! empty( $medium ) ) {
					throw new Exception( esc_html__( 'Medium already exists.', 'school-management' ) );
				}

				global $wpdb;
				$data = array(
					'label' 		=> $label,
					'school_id' 	=> $school_id
				);

				$data['created_at'] = current_time( 'Y-m-d H:i:s' );

				$success = $wpdb->insert( WLSM_MEDIUM, $data );

				WLSM_Helper::check_buffer();

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$response_data = array();

				$success = true;
				$message = esc_html__( 'Medium Added Successfully.', 'school-management' );

			} else {
				$success = false;
				throw new Exception( esc_html__( 'You do not have permission to add new medium.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response( $response, 200 );

	}

	// Staff - Delete Medium.
	public static function delete_medium( $request ) {

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff( $user_id );

		if ( $staff ) {
			$school_id 			= isset( $staff->school_id ) ? absint( $staff->school_id ) : 0;
			$permissions 		= unserialize( $staff->permissions );
			$staff_permissions 	= in_array('manage_classes', $permissions);
		} else {
			throw new Exception( esc_html__( 'Staff not found.', 'school-management' ) );
		}

		$params 	= $request->get_params();
		$medium_id 	= isset( $params['id'] ) ? absint( $params['id'] ) : 0;

		try {

			if( $staff_permissions ) {

				global $wpdb;
				$medium = WLSM_M_Staff_Class::get_medium( $school_id, $medium_id );

				if ( empty( $medium ) ) {
					throw new Exception( esc_html__( 'Medium not found.', 'school-management' ) );
				}

				$success = $wpdb->delete( WLSM_MEDIUM, array( 'ID' => $medium_id, 'school_id' => $school_id ) );

				WLSM_Helper::check_buffer();

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$response_data = array();

				$success = true;
				$message = esc_html__( 'Medium Deleted Successfully.', 'school-management' );

			} else {
				$success = false;
				throw new Exception( esc_html__( 'You do not have permission to delete medium.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response( $response, 200 );

	}

	// Staff - View fee types.
	public static function view_fee_types($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_fees', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$fee_types = WLSM_M::fetch_fees($school_id);

				if ($fee_types) {
					foreach ($fee_types as $fee_type) {
						$data[] = array(
							'ID'   			=> isset($fee_type->ID) ? WLSM_M_Staff_Class::get_label_text($fee_type->ID) : '',
							'label'   		=> isset($fee_type->label) ? WLSM_M_Staff_Class::get_label_text($fee_type->label) : '',
							'amount'   		=> isset($fee_type->amount) ? WLSM_M_Staff_Class::get_label_text($fee_type->amount) : '',
							'class_label' 	=> isset($fee_type->class_label) ? WLSM_M_Staff_Class::get_label_text($fee_type->class_label) : '',
							'period'   		=> isset($fee_type->period) ? WLSM_M_Staff_Class::get_label_text($fee_type->period) : '',
						);
						$response_data['fee_types'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Fee Types Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view fees.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Fee Types.
	public static function add_new_fee_type($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_fees', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 							= $request->get_params();

		$label 								= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$class_ids	 						= isset($params['class_ids']) && is_array($params['class_ids']) ? $params['class_ids'] : '';
		$student_types 						= isset($params['student_types']) && is_array($params['student_types']) ? $params['student_types'] : '';
		$period 							= isset($params['period']) ? sanitize_text_field($params['period']) : 'one-time';
		$amount 							= isset($params['amount']) ? sanitize_text_field($params['amount']) : 0;
		$auto_generate_invoice_on_admission = isset($params['auto_generate_invoice_on_admission']) ? (bool) $params['auto_generate_invoice_on_admission'] : true;
		$dashboard_disable 					= isset($params['dashboard_disable']) ? (bool) $params['dashboard_disable'] : false;

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($class_ids)) {
					throw new Exception(esc_html__('Please select atleast a class.', 'school-management'));
				}

				if (empty($student_types)) {
					throw new Exception(esc_html__('Please select atleast a student type.', 'school-management'));
				}

				if (empty($amount)) {
					throw new Exception(esc_html__('Please enter amount.', 'school-management'));
				}

				$student_type = is_array($student_types) ? serialize($student_types) : '';

				foreach ( $class_ids as $class_id ) {
					$data = array(
						'label'   					=> $label,
						'amount' 					=> $amount,
						'period'   					=> $period,
						'school_id' 				=> $school_id,
						'active_on_admission'   	=> $auto_generate_invoice_on_admission,
						'active_on_dashboard'   	=> $dashboard_disable,
						'student_type'   			=> $student_type,
						'class_id'					=> $class_id,
					);
					$data['created_at'] = current_time('Y-m-d H:i:s');
					$success 			= $wpdb->insert(WLSM_FEES, $data);
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Fee Type Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add fees.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Fee type details.
	public static function fee_type_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_fees', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$fee_type 	= WLSM_M_Staff_Accountant::fetch_fee($school_id, $id);

				if ($fee_type) {
					$student_type = isset($fee_type->student_type) ? unserialize($fee_type->student_type) : '';

					$data = array(
						'ID' 					=> isset($fee_type->ID) ? absint($fee_type->ID) : 0,
						'title' 				=> isset($fee_type->label) ? WLSM_M_Staff_Class::get_label_text($fee_type->label) : '',
						'class_id' 				=> isset($fee_type->class_id) ? absint($fee_type->class_id) : 0,
						'student_types' 		=> is_array($student_type) ? $student_type : array(),
						'period'				=> isset($fee_type->period) ? WLSM_M_Staff_Class::get_label_text($fee_type->period) : '',
						'amount'				=> isset($fee_type->amount) ? WLSM_M_Staff_Class::get_label_text($fee_type->amount) : '',
						'active_on_admission' 	=> isset($fee_type->active_on_admission) ? (bool) $fee_type->active_on_admission : true,
						'active_on_dashboard' 	=> isset($fee_type->active_on_dashboard) ? (bool) $fee_type->active_on_dashboard : false,
					);
					$response_data['fee_type'] = $data;
				}

				$success = true;
				$message = esc_html__('Fee Type Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view fees.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit fee type.
	public static function edit_fee_type($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_fees', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();

		$id 								= isset($params['id']) ? absint($params['id']) : 0;
		$label 								= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$class_id 							= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$student_type 						= isset($params['student_type']) && is_array($params['student_type']) ? serialize($params['student_type']) : '';
		$period								= isset($params['period']) ? sanitize_text_field($params['period']) : 'one-time';
		$amount								= isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
		$auto_generate_invoice_on_admission = isset($params['auto_generate_invoice_on_admission']) ? (bool) $params['auto_generate_invoice_on_admission'] : true;
		$dashboard_disable					= isset($params['dashboard_disable']) ? (bool) $params['dashboard_disable'] : false;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$fee_type 	= WLSM_M_Staff_Accountant::fetch_fee($school_id, $id);
				if (empty($fee_type)) {
					throw new Exception(esc_html__('Fee type not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				if (empty($amount)) {
					throw new Exception(esc_html__('Please enter an amount.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select class.', 'school-management'));
				}

				if (empty($student_type)) {
					throw new Exception(esc_html__('Please select atleast a student type.', 'school-management'));
				}

				$data = array(
					'label' 				=> $label,
					'amount'				=> $amount,
					'period' 				=> $period,
					'active_on_admission'	=> $auto_generate_invoice_on_admission,
					'active_on_dashboard' 	=> $dashboard_disable,
					'student_type' 			=> $student_type,
					'class_id' 				=> $class_id,
					'school_id' 			=> $school_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_FEES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Fee Type Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit fees.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Fee Type.
	public static function delete_fee_type($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_fees', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$fee_type = WLSM_M_Staff_Accountant::get_fee($school_id, $id);

				if (empty($fee_type)) {
					throw new Exception(esc_html__('Fee type not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_FEES, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Fee Type Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete fees.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View concession types.
	public static function view_concession_types($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_concession_types', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$concession_types = WLSM_M::fetch_concessions($school_id);

				if ($concession_types) {
					foreach ($concession_types as $concession_type) {
						$concession_value = '';
						if ($concession_type->concession_type === 'percentage') {
							$concession_value = $concession_type->percentage_value . '%';
						} else if ($concession_type->concession_type === 'fixed_amount') {
							$concession_value = WLSM_Config::get_money_text($concession_type->fixed_amount, $school_id);
							$concession_value = html_entity_decode($concession_value, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // "₹1,000.00"
						}
						$data[] = array(
							'ID'   			=> isset($concession_type->ID) ? absint($concession_type->ID) : 0,
							'name'   		=> isset($concession_type->concession_name) ? WLSM_M_Staff_Class::get_label_text($concession_type->concession_name) : '',
							'type'   		=> isset($concession_type->concession_type) ? WLSM_M_Staff_Class::get_label_text($concession_type->concession_type) : '',
							'value'   		=> $concession_value,
							'class_label' 	=> isset($concession_type->class_label) ? WLSM_M_Staff_Class::get_label_text($concession_type->class_label) : '',
							'status'   		=> isset($concession_type->is_active) ? WLSM_M_Staff_Class::get_status_text($concession_type->is_active) : '',
						);
						$response_data['concession_types'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Concession Types Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view concession types.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Concession Types.
	public static function add_new_concession_type($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_concession_types', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

			$params 			  = $request->get_params();

			$concession_name      = isset($params['concession_name']) ? sanitize_text_field($params['concession_name']) : '';
			$concession_type      = isset($params['concession_type']) ? sanitize_text_field($params['concession_type']) : 'percentage';
			$percentage_value     = isset($params['percentage_value']) ? WLSM_Config::sanitize_money($params['percentage_value']) : null;
			$fixed_amount         = isset($params['fixed_amount']) ? WLSM_Config::sanitize_money($params['fixed_amount']) : null;
			$eligibility_criteria = isset($params['eligibility_criteria']) ? sanitize_textarea_field($params['eligibility_criteria']) : '';
			$class_id             = isset($params['class_id']) ? absint($params['class_id']) : 0;
			$is_active            = isset($params['is_active']) ? (bool) ($params['is_active']) : 0;
			$fee_type_ids         = isset($params['fee_type_ids']) && is_array($params['fee_type_ids']) ? array_map('absint', $_POST['fee_type_ids']) : array();

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($concession_name)) {
					throw new Exception(esc_html__('Please enter concession name.', 'school-management'));
				}

				if (!in_array($concession_type, array('percentage', 'fixed_amount'))) {
					throw new Exception(esc_html__('Please specify valid concession type.', 'school-management'));
				}

				if ($concession_type === 'percentage') {
					if (empty($percentage_value) || $percentage_value < 0 || $percentage_value > 100) {
						throw new Exception(esc_html__('Please specify valid percentage value (0-100).', 'school-management'));
					}
					$fixed_amount = null;
				} else if ($concession_type === 'fixed_amount') {
					if (empty($fixed_amount) || $fixed_amount < 0) {
						throw new Exception(esc_html__('Please specify valid fixed amount.', 'school-management'));
					}
					$percentage_value = null;
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				$data = array(
					'concession_name'      => $concession_name,
					'concession_type'      => $concession_type,
					'percentage_value'     => $percentage_value,
					'fixed_amount'         => $fixed_amount,
					'eligibility_criteria' => $eligibility_criteria,
					'is_active'            => $is_active,
					'school_id'            => $school_id,
					'class_id'             => $class_id,
				);

				$data['created_at'] 	= current_time('Y-m-d H:i:s');

				$success 				= $wpdb->insert(WLSM_CONCESSION_TYPES, $data);
				$saved_concession_id 	= $wpdb->insert_id;

				// Save fee type mappings (only if fee types are provided)
				if (!empty($fee_type_ids)) {
					WLSM_M_Staff_Accountant::save_concession_fee_mappings($saved_concession_id, $fee_type_ids);
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Concession Type Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add concession types.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Concession type details.
	public static function concession_type_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_concession_types', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$concession_type 	= WLSM_M::fetch_concession_type($school_id, $id);

				if( ! $concession_type ) {
					throw new Exception(esc_html__('Concession type not found.', 'school-management'));
				}

				if ($concession_type) {
					$data = array(
						'ID' 				=> isset($concession_type->ID) ? absint($concession_type->ID) : 0,
						'concession_name' 	=> isset($concession_type->concession_name) ? WLSM_M_Staff_Class::get_label_text($concession_type->concession_name) : '',
						'concession_type' 	=> isset($concession_type->concession_type) ? WLSM_M_Staff_Class::get_label_text($concession_type->concession_type) : '',
						'class_id' 			=> isset($concession_type->class_id) ? absint($concession_type->class_id) : 0,
						'class_label'		=> isset($concession_type->class_label) ? WLSM_M_Staff_Class::get_label_text($concession_type->class_label) : '',
						'fixed_amount'		=> isset($concession_type->fixed_amount) ? WLSM_M_Staff_Class::get_label_text($concession_type->fixed_amount) : '',
						'percentage'		=> isset($concession_type->percentage_value) ? WLSM_M_Staff_Class::get_label_text($concession_type->percentage_value) : '',
						'eligibility_criteria' 	=> isset($concession_type->eligibility_criteria) ? WLSM_M_Staff_Class::get_address_text($concession_type->eligibility_criteria) : '',
						'is_active' 	=> isset($concession_type->is_active) ? WLSM_M_Staff_Class::get_status_text($concession_type->is_active) : '',
					);
					$response_data['concession_type'] = $data;
				}

				$success = true;
				$message = esc_html__('Concession Type Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view concession types.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit concession type.
	public static function edit_concession_type($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_concession_types', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 				= $request->get_params();

		$id 					= isset($params['id']) ? absint($params['id']) : 0;
		$name 					= isset($params['concession_name']) ? sanitize_text_field($params['concession_name']) : '';
		$type 					= isset($params['concession_type']) ? sanitize_text_field($params['concession_type']) : '';
		$class_id 				= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$percentage_value		= isset($params['percentage_value']) ? sanitize_text_field($params['percentage_value']) : '';
		$fixed_amount			= isset($params['fixed_amount']) ? sanitize_text_field($params['fixed_amount']) : '';
		$eligibility_criteria 	= isset($params['eligibility_criteria']) ? sanitize_text_field($params['eligibility_criteria']) : '';
		$is_active				= isset($params['is_active']) ? (bool) $params['is_active'] : true;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$concession_type 	= WLSM_M::fetch_concession_type($school_id, $id);
				if (empty($concession_type)) {
					throw new Exception(esc_html__('Concession not found.', 'school-management'));
				}

				if (empty($name)) {
					throw new Exception(esc_html__('Please enter a name.', 'school-management'));
				}

				if (!in_array($type, array('percentage', 'fixed_amount'))) {
					throw new Exception(esc_html__('Please specify valid concession type.', 'school-management'));
				}

				if ($type === 'percentage') {
					if (empty($percentage_value) || $percentage_value < 0 || $percentage_value > 100) {
						throw new Exception(esc_html__('Please specify valid percentage value (0-100).', 'school-management'));
					}
					$fixed_amount = null;
				} else if ($type === 'fixed_amount') {
					if (empty($fixed_amount) || $fixed_amount < 0) {
						throw new Exception(esc_html__('Please specify valid fixed amount.', 'school-management'));
					}
					$percentage_value = null;
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select class.', 'school-management'));
				}

				$data = array(
					'concession_name'      => $name,
					'concession_type'      => $type,
					'percentage_value'     => $percentage_value,
					'fixed_amount'         => $fixed_amount,
					'eligibility_criteria' => $eligibility_criteria,
					'is_active'            => $is_active,
					'school_id'            => $school_id,
					'class_id'             => $class_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_CONCESSION_TYPES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Concession Type Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit concession types.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete Concession Type.
	public static function delete_concession_type($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_concession_types', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$concession_type = WLSM_M::fetch_concession_type($school_id, $id);

				if (empty($concession_type)) {
					throw new Exception(esc_html__('Concession type not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_CONCESSION_TYPES, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Concession Type Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete concession types.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View student concession.
	public static function view_student_concession($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_students_concession', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$students_concession = WLSM_M::get_student_concessions($school_id, $session_id);

				if ($students_concession) {
					foreach ($students_concession as $student_concession) {
						$data[] = array(
							'ID'   				=> isset($student_concession->ID) ? absint($student_concession->ID) : 0,
							'student_name'   	=> isset($student_concession->student_name) ? WLSM_M_Staff_Class::get_label_text($student_concession->concession_name) : '',
							'admission_number'  => isset($student_concession->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($student_concession->admission_number) : '',
							'concession_name' 	=> isset($student_concession->concession_name) ? WLSM_M_Staff_Class::get_label_text($student_concession->concession_name) : '',
							'class_label' 		=> isset($student_concession->class_label) ? WLSM_M_Staff_Class::get_label_text($student_concession->class_label) : '',
							'section_label' 	=> isset($student_concession->section_label) ? WLSM_M_Staff_Class::get_label_text($student_concession->section_label) : '',
							'status'   			=> isset($student_concession->status) ? WLSM_M_Staff_Class::get_label_text($student_concession->status) : '',
							'applied_by'   		=> isset($student_concession->applied_by) ? WLSM_M_Staff_Class::get_label_text($student_concession->applied_by) : '',
							'applied_date'   	=> isset($student_concession->applied_date) ? WLSM_M_Staff_Class::get_date_text($student_concession->applied_date) : '',
						);
						$response_data['students_concession'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Student Concession Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view student concessions.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Student concession details.
	public static function student_concession_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_concession_types', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$student_concession 	= WLSM_M_Staff_Accountant::get_student_concession($school_id, $session_id, $id);

				if ($student_concession) {
					$data = array(
						'ID' 				=> isset($student_concession->ID) ? absint($student_concession->ID) : 0,
						'student_name'   	=> isset($student_concession->student_name) ? WLSM_M_Staff_Class::get_label_text($student_concession->concession_name) : '',
						'admission_number'  => isset($student_concession->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($student_concession->admission_number) : '',
						'class_label' 		=> isset($student_concession->class_label) ? WLSM_M_Staff_Class::get_label_text($student_concession->class_label) : '',
						'section_label' 	=> isset($student_concession->section_label) ? WLSM_M_Staff_Class::get_label_text($student_concession->section_label) : '',
						'concession_type' 	=> isset($student_concession->concession_type_id) ? WLSM_M_Staff_Class::get_label_text($student_concession->concession_type_id) : '',
						'status' 			=> isset($concession_type->status) ? WLSM_M_Staff_Class::get_label_text($student_concession->status) : '',
						'approval_date'		=> isset($student_concession->approval_date) ? WLSM_M_Staff_Class::get_date_text($student_concession->approval_date) : '',
						'approval_by'		=> isset($student_concession->approval_by) ? WLSM_M_Staff_Class::get_label_text($student_concession->approval_by) : '',
						'remark'			=> isset($student_concession->remark) ? WLSM_M_Staff_Class::get_label_text($student_concession->remark) : '',
						'rejection_reason'	=> isset($student_concession->rejection_reason) ? WLSM_M_Staff_Class::get_label_text($student_concession->rejection_reason) : '',
					);
					$response_data['student_concession'] = $data;
				}

				$success = true;
				$message = esc_html__('Student Concession Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view student concession.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit student concession.
	public static function edit_student_concession($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_students_concession', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 			= $request->get_params();
		$id 				= isset($params['id']) ? absint($params['id']) : 0;
		$type 				= isset($params['concession_type']) ? absint($params['concession_type']) : '';
		$status				= isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';
		$remark 			= isset($params['remark']) ? sanitize_text_field($params['remark']) : '';
		$rejection_reason	= isset($params['rejection_reason']) ? sanitize_text_field($params['rejection_reason']) : '';
		$approval_date  	= isset( $params['approval_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['approval_date'] ) ) : NULL;
		
		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$student_concession 	= WLSM_M_Staff_Accountant::get_student_concession($school_id, $session_id, $id);
				if (empty($student_concession)) {
					throw new Exception(esc_html__('Concession not found.', 'school-management'));
				}

				if (empty($type)) {
					throw new Exception(esc_html__('Please select concession type.', 'school-management'));
				}

				if ( ! empty( $approval_date ) ) {
				$approval_date = $approval_date->format( 'Y-m-d H:i:s' );
				}

				if ( $status == 'approved' ) {
					$approved_by = $user_id;
				} else {
					$approved_by = NULL;
				}

				$data = array(
					'concession_type_id'   	=> $type,
					'approved_by'     		=> $approved_by,
					'approval_date'         => $approval_date,
					'status' 				=> $status,
					'remark'            	=> $remark,
					'rejection_reason'      => $rejection_reason,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_STUDENT_CONCESSION, $data, array('ID' => $id,'session_id' => $session_id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Student Concession Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit student concession.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Student fee types.
	public static function student_fee_types($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;

				$student_fee_types 	= WLSM_M_Staff_Accountant::fetch_student_assigned_fees($school_id, $student_id);

				if (! $student_fee_types) {
					throw new Exception(esc_html__('Student fee not found.', 'school-management'));
				}

				foreach ($student_fee_types as $student_fee_type) {
					$data = array(
						'ID' 		=> isset($student_fee_type->ID) ? absint($student_fee_type->ID) : 0,
						'label' 	=> isset($student_fee_type->label) ? WLSM_M_Staff_Class::get_label_text($student_fee_type->label) : '',
						'amount' 	=> isset($student_fee_type->amount) ? WLSM_M_Staff_Class::get_label_text($student_fee_type->amount) : '',
						'period' 	=> isset($student_fee_type->period) ? WLSM_M_Staff_Class::get_label_text($student_fee_type->period) : '',
					);
					$response_data['student_fee_types'][] = $data;
				}

				$success = true;
				$message = esc_html__('Student Fee Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View invoices.
	public static function view_invoices($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$restrict_to_section = false;
				$current_user = WLSM_M_Role::can('assigned_class');
				if ($current_user) {
					$current_school = $current_user['school'];
					$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);
				}

				$invoices = WLSM_M::fetch_invoices($school_id, $session_id, $restrict_to_section);

				if ($invoices) {
					foreach ($invoices as $invoice) {
						$invoice_id = isset($invoice->ID) ? absint($invoice->ID) : 0;
						$paid 		= WLSM_M_Staff_Accountant::get_invoice_payments_total($invoice_id);
						$due 		= isset($invoice->amount) ? ($invoice->amount) - $paid : '';
						$data[] = array(
							'ID'   				=> isset($invoice->ID) ? absint($invoice->ID) : 0,
							'student_name'  	=> isset($invoice->student_name) ? WLSM_M_Staff_Class::get_label_text($invoice->student_name) : '',
							'father_name'   	=> isset($invoice->father_name) ? WLSM_M_Staff_Class::get_label_text($invoice->father_name) : '',
							'admission_number'  => isset($invoice->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($invoice->admission_number) : '',
							'invoice_number'  	=> isset($invoice->invoice_number) ? WLSM_M_Staff_Class::get_admission_no_text($invoice->invoice_number) : '',
							'invoice_title'   	=> isset($invoice->label) ? WLSM_M_Staff_Class::get_label_text($invoice->label) : '',
							'description' 		=> isset($invoice->description) ? WLSM_M_Staff_Class::get_address_text($invoice->description) : '',
							'payable' 			=> isset($invoice->amount) ? WLSM_M_Staff_Class::get_label_text($invoice->amount) : '',
							'paid' 				=> isset($paid) ? $paid : '',
							'due' 				=> isset($due) ? $due : '',
							'status' 			=> isset($invoice->status) ? WLSM_M_Staff_Class::get_label_text($invoice->status) : '',
							'date_issued' 		=> isset($invoice->date_issued) ? WLSM_M_Staff_Class::get_date_text($invoice->date_issued) : '',
							'due_date' 			=> isset($invoice->due_date) ? WLSM_M_Staff_Class::get_date_text($invoice->due_date) : '',
							'phone' 			=> isset($invoice->phone) ? WLSM_M_Staff_Class::get_phone_text($invoice->phone) : '',
							'class_label' 		=> isset($invoice->class_label) ? WLSM_M_Staff_Class::get_label_text($invoice->class_label) : '',
						);
						$response_data['invoices'] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Invoices Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add New Invoice.
	public static function add_new_invoice($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 				= $request->get_params();
		$invoice_type 			= isset($params['invoice_type']) ? sanitize_text_field($params['invoice_type']) : '';
		$invoice_title        	= isset($params['invoice_label']) ? sanitize_text_field($params['invoice_label']) : '';
		$discount_note        	= isset($params['invoice_description']) ? sanitize_text_field($params['invoice_description']) : '';
		$partial_payment      	= isset($params['partial_payment']) ? (bool) $params['partial_payment'] : 0;
		$invoice_amount_total 	= isset($params['invoice_amount_total']) ? WLSM_Config::sanitize_money($params['invoice_amount_total']) : 0;
		$invoice_amount       	= isset($params['invoice_amount']) ? WLSM_Config::sanitize_money($params['invoice_amount']) : 0;
		$invoice_discount     	= isset($params['invoice_discount']) ? WLSM_Config::sanitize_money($params['invoice_discount']) : 0;
		$discount_amount      	= isset($params['invoice_discount_amount']) ? WLSM_Config::sanitize_money($params['invoice_discount_amount']) : 0;
		$invoice_date_issued  	= isset($params['invoice_date_issued']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['invoice_date_issued'])) : NULL;
		$invoice_due_date     	= isset($params['invoice_due_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['invoice_due_date'])) : NULL;
		$due_date_amount 	  	= isset($params['due_date_amount']) ? WLSM_Config::sanitize_money($params['due_date_amount']) : 0;
		$due_date_period 	  	= isset($params['due_date_period']) ? sanitize_text_field($params['due_date_period']) : '';

		// Fees.
		$fee_id     	= (isset($params['fee_id']) && is_array($params['fee_id'])) ? $params['fee_id'] : array();
		$fee_label  	= (isset($params['fee_label']) && is_array($params['fee_label'])) ? $params['fee_label'] : array();
		$fee_period 	= (isset($params['fee_period']) && is_array($params['fee_period'])) ? $params['fee_period'] : array();
		$fee_amount 	= (isset($params['fee_amount']) && is_array($params['fee_amount'])) ? $params['fee_amount'] : array();

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				if (empty($invoice_title)) {
					throw new Exception(esc_html__('Please provide invoice title.', 'school-management'));
				} else {
					if (strlen($invoice_title) > 50) {
						throw new Exception(esc_html__('Maximum length cannot exceed 100 characters.', 'school-management'));
					}
				}

				if ($invoice_date_issued > $invoice_due_date) {
					throw new Exception(esc_html__('Invoice due date must be greater than issued date.', 'school-management'));
				}

				if (empty($invoice_date_issued)) {
					throw new Exception(esc_html__('Please provide date issued.', 'school-management'));
				} else {
					$invoice_date_issued = $invoice_date_issued->format('Y-m-d');
				}

				if (empty($invoice_due_date)) {
					$invoice_due_date = NULL;
				} else {
					$invoice_due_date = $invoice_due_date->format('Y-m-d');
				}

				if (!in_array($invoice_type, array('single_invoice', 'bulk_invoice', 'single_invoice_fee_type'))) {
					throw new Exception(esc_html__('Please select either single invoice or bulk invoice option.', 'school-management'));
				}

				if ('single_invoice' === $invoice_type) {
					$student_id 				= isset($params['student']) ? absint($params['student']) : 0;
					$collect_invoice_payment 	= isset($params['collect_invoice_payment']) ? (bool) $params['collect_invoice_payment'] : 0;

					if (empty($student_id)) {
						throw new Exception(esc_html__('Please select a student.', 'school-management'));
					}

					// Checks if student exists.
					$student = WLSM_M_Staff_General::get_student($school_id, $session_id, $student_id, true, true);

					if (!$student) {
						throw new Exception(esc_html__('Student not found.', 'school-management'));
					}

					if ($collect_invoice_payment) {
						$payment_amount = isset($params['payment_amount']) ? WLSM_Config::sanitize_money($params['payment_amount']) : 0;
						$payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
						$transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
						$payment_note   = isset($params['payment_note']) ? sanitize_text_field($params['payment_note']) : '';

						$due = WLSM_M_Invoice::get_due_amount(
							array(
								'total'    => $invoice_amount,
								'discount' => $invoice_discount,
							)
						);

						if (!in_array($payment_method, array_keys(WLSM_M_Invoice::collect_payment_methods()))) {
							throw new Exception(esc_html__('Please select a valid payment method.', 'school-management'));
						}
					}
				} else if ('single_invoice_fee_type' === $invoice_type) {
					$student_id = isset($params['student']) ? absint($params['student']) : 0;

					if (empty($student_id)) {
						throw new Exception(esc_html__('Please select a student.', 'school-management'));
					}

					// Checks if student exists.
					$student = WLSM_M_Staff_General::get_student($school_id, $session_id, $student_id, true, true);

					if (!$student) {
						throw new Exception(esc_html__('Student not found.', 'school-management'));
					}

					// Student fees.
					if (count($fee_label)) {
						if (1 !== count(array_unique(array(count($fee_label), count($fee_period), count($fee_amount))))) {
							throw new Exception(esc_html__('Invalid fees.', 'school-management'));
						} elseif (count($fee_label) !== count(array_unique($fee_label))) {
							throw new Exception(esc_html__('Fee type must be different.', 'school-management'));
						} else {
							foreach ($fee_label as $key => $value) {
								$fee_id    [$key]  = sanitize_text_field($fee_id[$key]);
								$fee_label [$key] = sanitize_text_field($fee_label[$key]);
								$fee_period[$key] = sanitize_text_field($fee_period[$key]);
								$fee_amount[$key] = WLSM_Config::sanitize_money($fee_amount[$key]);

								if (empty($fee_label[$key])) {
									throw new Exception(esc_html__('Please specify fee type.', 'school-management'));
								} elseif (strlen($fee_label[$key]) > 100) {
									throw new Exception(esc_html__('Maximum length cannot exceed 100 characters.', 'school-management'));
								}

								if (!in_array($fee_period[$key], array_keys(WLSM_Helper::fee_period_list()))) {
									throw new Exception(esc_html__('Please specify fee period.', 'school-management'));
								}

								if ($fee_amount[$key] < 0) {
									$fee_amount[$key] = 0;
								}
							}
						}
					}
				} else {
					$student_ids = (isset($params['student']) && is_array($params['student'])) ? $params['student'] : array();

					if (!count($student_ids)) {
						throw new Exception(esc_html__('Please select students.', 'school-management'));
					}

					// Checks if students exists.
					$students_count = WLSM_M_Staff_General::get_students_count($school_id, $session_id, $student_ids, true, true);

					if ($students_count != count($student_ids)) {
						throw new Exception(esc_html__('Student(s) not found.', 'school-management'));
					}
				}

				// Invoice data.
				$invoice_data = array(
					'label'                => $invoice_title,
					'description'          => $discount_note,
					'amount'               => $invoice_amount,
					'invoice_amount_total' => $invoice_amount_total,
					'discount'             => $invoice_discount,
					'date_issued'          => $invoice_date_issued,
					'due_date'             => $invoice_due_date,
					'partial_payment'      => $partial_payment,
					'due_date_amount'      => $due_date_amount,
					'due_date_period'      => $due_date_period,
				);

				if ('bulk_invoice' === $invoice_type) {
					$bulk_invoice_ids = array();
					foreach ($student_ids as $student_id) {
						$invoice_number = WLSM_M_Invoice::get_invoice_number($school_id);

						$invoice_data['invoice_number']    = $invoice_number;
						$invoice_data['student_record_id'] = $student_id;

						$invoice_data['added_by'] = get_current_user_id();

						$invoice_data['created_at'] = current_time('Y-m-d H:i:s');

						$success = $wpdb->insert(WLSM_INVOICES, $invoice_data);

						$bulk_invoice_id = $wpdb->insert_id;
						array_push($bulk_invoice_ids, $bulk_invoice_id);

						// Insert discount data if any discount is applied
						if ($discount_amount > 0 || $invoice_discount > 0) {
							$discount_data = array(
								'amount'           => $discount_amount,
								'discount_percent' => $invoice_discount,
								'note'             => $discount_note,
								'invoice_id'       => $bulk_invoice_id,
								'created_at'       => current_time('Y-m-d H:i:s'),
							);
							$wpdb->insert(WLSM_DISCOUNTS, $discount_data);
						}

						$buffer = ob_get_clean();
						if (!empty($buffer)) {
							throw new Exception($buffer);
						}
					}
				} else if (('single_invoice' === $invoice_type)) {
					$invoice_number = WLSM_M_Invoice::get_invoice_number($school_id);

					$invoice_data['invoice_number']    	= $invoice_number;
					$invoice_data['student_record_id'] 	= $student_id;
					$invoice_data['added_by'] 			= get_current_user_id();
					$invoice_data['created_at'] 		= current_time('Y-m-d H:i:s');

					$success 			= $wpdb->insert(WLSM_INVOICES, $invoice_data);
					$single_invoice_id 	= $wpdb->insert_id;

					// Insert discount data if any discount is applied
					if ($discount_amount > 0 || $invoice_discount > 0) {
						$discount_data = array(
							'amount'           => $discount_amount,
							'discount_percent' => $invoice_discount,
							'note'             => $discount_note,
							'invoice_id'       => $single_invoice_id,
							'created_at'       => current_time('Y-m-d H:i:s'),
						);
						$wpdb->insert(WLSM_DISCOUNTS, $discount_data);
					}

					if ($collect_invoice_payment) {
						// $invoice_id = $wpdb->insert_id;

						$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

						?><?php
						// Payment data.
						$payment_data = array(
							'receipt_number'    => $receipt_number,
							'amount'            => $payment_amount,
							'payment_method'    => $payment_method,
							'transaction_id'    => $transaction_id,
							'note'              => $payment_note,
							'invoice_label'     => $invoice_title,
							'invoice_payable'   => $due,
							'student_record_id' => $student_id,
							'invoice_id'        => $single_invoice_id,
							'school_id'         => $school_id,
						);

						$payment_data['added_by'] = get_current_user_id();

						$payment_data['created_at'] = current_time('Y-m-d H:i:s');

						$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

						$new_payment_id = $wpdb->insert_id;

						$buffer = ob_get_clean();
						if (!empty($buffer)) {
							throw new Exception($buffer);
						}

						WLSM_M_Staff_Accountant::refresh_invoice_status($single_invoice_id);
					}
				} else if (('single_invoice_fee_type' === $invoice_type)) {
					// Fees.
					$place_holders_fee_labels 	= array();
					$list_data 					= array();
					$fee_order 					= 10;
					
					foreach ($fee_label as $key => $value) {
						array_push($place_holders_fee_labels, '%s');
						$fee_order++;

						// Student fee data.
						$student_fee_data = array(
							'id'        => $fee_id[$key],
							'amount'    => $fee_amount[$key],
							'period'    => $fee_period[$key],
							'label'     => $fee_label[$key],
							'fee_order' => $fee_order,
						);
						
						// Invoice data.
						$fee_data = array(
							'label'           => $student_fee_data['label'],
							'period'          => $student_fee_data['period'],
							'amount'          => $student_fee_data['amount'],
							'partial_payment' => 0,
						);

						array_push($list_data, $fee_data );
					}

					$list_data_fee = serialize($list_data);

					$invoice_data['fee_list'] 			= $list_data_fee;
					$invoice_number 					= WLSM_M_Invoice::get_invoice_number($school_id);
					$invoice_data['invoice_number']    	= $invoice_number;
					$invoice_data['student_record_id'] 	= $student_id;
					$invoice_data['added_by'] 			= get_current_user_id();
					$invoice_data['created_at'] 		= current_time('Y-m-d H:i:s');

					// Invoice data.
					$success = $wpdb->insert(WLSM_INVOICES, $invoice_data);
					$single_invoice_id = $wpdb->insert_id;

					// Insert discount data if any discount is applied
					if ($discount_amount > 0 || $invoice_discount > 0) {
						$discount_data = array(
							'amount'           => $discount_amount,
							'discount_percent' => $invoice_discount,
							'note'             => $discount_note,
							'invoice_id'       => $single_invoice_id,
							'created_at'       => current_time('Y-m-d H:i:s'),
						);
						$wpdb->insert(WLSM_DISCOUNTS, $discount_data);
					}
				}

				$message = esc_html__('Invoice Added Successfully.', 'school-management');

				if (isset($bulk_invoice_ids) && count($bulk_invoice_ids) > 0) {
					foreach ($bulk_invoice_ids as $bulk_invoice_id) {
						// Notify for invoice generated.
						$data = array(
							'school_id'  => $school_id,
							'session_id' => $session_id,
							'invoice_id' => $bulk_invoice_id,
						);

						wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated', $data);
						wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated_to_parent', $data);
					}
				} else if (isset($single_invoice_id)) {
					// Notify for invoice generated.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'invoice_id' => $single_invoice_id,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated_to_parent', $data);
				}

				if (isset($new_payment_id)) {
					// Notify for offline fee submission.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'payment_id' => $new_payment_id,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_offline_fee_submission', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_offline_fee_submission_to_parent', $data);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				WLSM_Helper::check_buffer();

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Invoice details.
	public static function invoice_details($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$invoice_id = isset( $params['invoice_id'] ) ? absint($params['invoice_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$invoice = WLSM_M_Staff_Accountant::fetch_invoice($school_id, $session_id, $invoice_id);
				if ( ! $invoice ) {
					throw new Exception(esc_html__('Admit card not found.', 'school-management'));
				}

				$student_id = isset($invoice->student_id) ? absint($invoice->student_id) : 0;
				$student 	= WLSM_M::get_student_by_id( $student_id );

				$student_data = array(
					'ID' 				=> isset($student->student_id) ? absint($student->student_id) : 0,
					'name'				=> isset($student->student_name) ? WLSM_M_Staff_Class::get_name_text($student->student_name) : '',
					'admission_number' 	=> isset($student->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($student->admission_number) : '',
					'enrollment_number' => isset($student->enrollment_number) ? WLSM_M_Staff_Class::get_roll_no_text($student->enrollment_number) : '',
					'roll_number' 		=> isset($student->roll_number) ? WLSM_M_Staff_Class::get_roll_no_text($student->roll_number) : '',
					'class_label' 		=> isset($student->class_label) ? WLSM_M_Staff_Class::get_label_text($student->class_label) : '',
					'section_label' 	=> isset($student->section_label) ? WLSM_M_Staff_Class::get_label_text($student->section_label) : '',
					'phone' 			=> isset($student->phone) ? WLSM_M_Staff_Class::get_phone_text($student->phone) : '',
					'email' 			=> isset($student->email) ? WLSM_M_Staff_Class::get_email_text($student->email) : '',
					'father_name' 		=> isset($student->father_name) ? WLSM_M_Staff_Class::get_name_text($student->father_name) : '',
					'father_phone' 		=> isset($student->father_phone) ? WLSM_M_Staff_Class::get_phone_text($student->father_phone) : '',
				);
				$response_data['student_data'] = $student_data;

				$fee_list = isset($invoice->fee_list) ? unserialize($invoice->fee_list) : array();

				foreach ( $fee_list as $fee ) {
					$fee_types = array(
						'fee_type' 	=> $fee['label'],
						'amount' 	=> $fee['amount'],
					);
					$response_data['fee_types'][] = $fee_types;
				}

				$discount_data 		 	= WLSM_M_Staff_Accountant::get_discount_data($invoice->ID);
				$discount_amount 		= isset($discount_data->amount) ? $discount_data->amount : 0;
				$discount_percentage    = isset($invoice->discount) ? rtrim(rtrim(number_format($invoice->discount, 2), '0'), '.') : 0;
				$due 					= $invoice->payable - $invoice->paid;

				$invoice_data = array(
					'ID' 					=> isset($invoice->ID) ? absint($invoice->ID) : 0,
					'invoice_number' 		=> isset($invoice->invoice_number) ? WLSM_M_Staff_Class::get_roll_no_text($invoice->invoice_number) : '',
					'invoice_title'			=> isset($invoice->invoice_title) ? WLSM_M_Staff_Class::get_label_text($invoice->invoice_title) : '',
					'invoice_description' 	=> isset($invoice->invoice_description) ? WLSM_M_Staff_Class::get_address_text($invoice->invoice_description) : '',
					'partial_payment' 		=> isset($invoice->partial_payment) ? $invoice->partial_payment : 0,
					'total_amount' 			=> isset($invoice->invoice_amount_total) ? WLSM_M_Staff_Class::get_label_text($invoice->invoice_amount_total) : '',
					'payable' 				=> isset($invoice->payable) ? WLSM_M_Staff_Class::get_label_text($invoice->payable) : '',
					'amount' 				=> isset($invoice->amount) ? WLSM_M_Staff_Class::get_label_text($invoice->amount) : '',
					'discount_percentage' 	=> $discount_percentage,
					'discount_amount' 		=> $discount_amount,
					'date_issued' 			=> isset($invoice->date_issued) ? WLSM_M_Staff_Class::get_date_text($invoice->date_issued) : '',
					'due_date' 				=> isset($invoice->due_date) ? WLSM_M_Staff_Class::get_date_text($invoice->due_date) : '',
					'due_date_amount' 		=> isset($invoice->due_date_amount) ? WLSM_M_Staff_Class::get_label_text($invoice->due_date_amount) : '',
					'paid' 					=> isset($invoice->paid) ? WLSM_M_Staff_Class::get_label_text($invoice->paid) : '',
					'due' 					=> isset($due) ? $due : 0.00,
					'status' 				=> isset($invoice->status) ? WLSM_M_Staff_Class::get_label_text($invoice->status) : '',
				);
				$response_data['invoice_data'] = $invoice_data;

				$invoice_payments = WLSM_M_Staff_Accountant::get_invoice_payments($invoice_id);
				// var_dump($invoice_payments);
				// die;

				foreach ( $invoice_payments as $payment ) {
					$payment_history = array(
						'ID' 				=> isset($payment->ID) ? absint($payment->ID) : 0,
						'receipt_number' 	=> isset($payment->receipt_number) ? WLSM_M_Staff_Class::get_label_text($payment->receipt_number) : '',
						'amount'			=> isset($payment->amount) ? WLSM_M_Staff_Class::get_label_text($payment->amount) : '',
						'payment_method' 	=> isset($payment->payment_method) ? WLSM_M_Staff_Class::get_label_text($payment->payment_method) : '',
						'transaction_id' 	=> isset($payment->transaction_id) ? WLSM_M_Staff_Class::get_label_text($payment->transaction_id) : '',
						'date' 				=> isset($payment->created_at) ? WLSM_M_Staff_Class::get_date_text($payment->created_at) : '',
						'note' 				=> isset($payment->note) ? WLSM_M_Staff_Class::get_label_text($payment->note) : '',
					);
					$response_data['payment_history'][] = $payment_history;
				}

				$success = true;
				$message = esc_html__('Invoice Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit invoice.
	public static function edit_invoice($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_exam_results', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 				= $request->get_params();
		$invoice_id 			= isset($params['invoice_id']) ? absint($params['invoice_id']) : 0;
		$invoice_title        	= isset($params['invoice_label']) ? sanitize_text_field($params['invoice_label']) : '';
		$discount_note        	= isset($params['invoice_description']) ? sanitize_text_field($params['invoice_description']) : '';
		$partial_payment      	= isset($params['partial_payment']) ? (bool) $params['partial_payment'] : 0;
		$invoice_amount_total 	= isset($params['invoice_amount_total']) ? WLSM_Config::sanitize_money($params['invoice_amount_total']) : 0;
		$invoice_amount       	= isset($params['invoice_amount']) ? WLSM_Config::sanitize_money($params['invoice_amount']) : 0;
		$invoice_discount     	= isset($params['invoice_discount']) ? WLSM_Config::sanitize_money($params['invoice_discount']) : 0;
		$discount_amount      	= isset($params['invoice_discount_amount']) ? WLSM_Config::sanitize_money($params['invoice_discount_amount']) : 0;
		$invoice_date_issued  	= isset($params['invoice_date_issued']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['invoice_date_issued'])) : NULL;
		$invoice_due_date     	= isset($params['invoice_due_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['invoice_due_date'])) : NULL;
		$due_date_amount 	  	= isset($params['due_date_amount']) ? WLSM_Config::sanitize_money($params['due_date_amount']) : 0;
		$due_date_period 	  	= isset($params['due_date_period']) ? sanitize_text_field($params['due_date_period']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$invoice = WLSM_M_Staff_Accountant::fetch_invoice($school_id, $session_id, $invoice_id);

				if (!$invoice) {
					throw new Exception(esc_html__('Invoice not found.', 'school-management'));
				}

				if (!current_user_can('administrator') && $invoice->status === WLSM_M_Invoice::get_paid_key()) {
					throw new Exception(esc_html__('Unable to update because (Invoice Is Paid)', 'school-management'));
				}

				if (empty($invoice_title)) {
					throw new Exception(esc_html__('Please provide invoice title.', 'school-management'));
				} else {
					if (strlen($invoice_title) > 50) {
						throw new Exception(esc_html__('Maximum length cannot exceed 100 characters.', 'school-management'));
					}
				}

				if ($invoice_date_issued > $invoice_due_date) {
					throw new Exception(esc_html__('Invoice due date must be greater than issued date.', 'school-management'));
				}

				if (empty($invoice_date_issued)) {
					throw new Exception(esc_html__('Please provide date issued.', 'school-management'));
				} else {
					$invoice_date_issued = $invoice_date_issued->format('Y-m-d');
				}

				if (empty($invoice_due_date)) {
					$invoice_due_date = NULL;
				} else {
					$invoice_due_date = $invoice_due_date->format('Y-m-d');
				}

				// Invoice data.
				$invoice_data = array(
					'label'                => $invoice_title,
					'description'          => $discount_note,
					'amount'               => $invoice_amount,
					'invoice_amount_total' => $invoice_amount_total,
					'discount'             => $invoice_discount,
					'date_issued'          => $invoice_date_issued,
					'due_date'             => $invoice_due_date,
					'partial_payment'      => $partial_payment,
					'due_date_amount'      => $due_date_amount,
					'due_date_period'      => $due_date_period,
				);

				if ($invoice_id) {
					$message = esc_html__('Invoice updated successfully.', 'school-management');

					$invoice_data['updated_at'] = current_time('Y-m-d H:i:s');
					$success = $wpdb->update(WLSM_INVOICES, $invoice_data, array('ID' => $invoice_id));

					$buffer = ob_get_clean();
					if (!empty($buffer)) {
						throw new Exception($buffer);
					}

					// Insert or update discount data
					$discount_data = array(
						'amount'           => $discount_amount,
						'discount_percent' => $invoice_discount,
						'note'             => $discount_note,
						'invoice_id'       => $invoice_id,
						'updated_at'       => current_time('Y-m-d H:i:s'),
					);

					$existing_discount = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM " . WLSM_DISCOUNTS . " WHERE invoice_id = %d",
							$invoice_id
						)
					);

					if ($existing_discount) {
						// Log the discount change
						$change_data = array(
							'invoice_id'  => $invoice_id,
							'discount_id' => $existing_discount->ID,
							'old_amount'  => $existing_discount->amount,
							'new_amount'  => $discount_amount,
							'change_note' => $discount_note,
							'staff_id'    => $staff->ID,
							'change_date' => current_time('Y-m-d H:i:s'),
						);
						$wpdb->insert(WLSM_INVOICE_DISCOUNT_CHANGES, $change_data);

						// Update the existing discount
						$wpdb->update(WLSM_DISCOUNTS, $discount_data, array('ID' => $existing_discount->ID));
					} else {
						// Insert new discount
						$discount_data['created_at'] = current_time('Y-m-d H:i:s');
						$wpdb->insert(WLSM_DISCOUNTS, $discount_data);
					}

					WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);
				}

				if (isset($bulk_invoice_ids) && count($bulk_invoice_ids) > 0) {
					foreach ($bulk_invoice_ids as $bulk_invoice_id) {
						// Notify for invoice generated.
						$data = array(
							'school_id'  => $school_id,
							'session_id' => $session_id,
							'invoice_id' => $bulk_invoice_id,
						);

						wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated', $data);
						wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated_to_parent', $data);
					}
				} else if (isset($single_invoice_id)) {
					// Notify for invoice generated.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'invoice_id' => $single_invoice_id,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_invoice_generated_to_parent', $data);
				}

				if (isset($new_payment_id)) {
					// Notify for offline fee submission.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'payment_id' => $new_payment_id,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_offline_fee_submission', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_offline_fee_submission_to_parent', $data);
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete invoice.
	public static function delete_invoice($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$invoice_id = isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				// Checks if invoice exists.
				$invoice = WLSM_M_Staff_Accountant::get_invoice($school_id, $session_id, $invoice_id);

				if (!$invoice) {
					throw new Exception(esc_html__('Invoice not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_INVOICES, array('ID' => $invoice_id));
				$success = $wpdb->delete(WLSM_PAYMENTS, array('ID' => $invoice->payment_id));
				$message = esc_html__('Invoice deleted successfully.', 'school-management');

				$success = $wpdb->delete(WLSM_DISCOUNTS, array('invoice_id' => $invoice_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete exam result.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}
	
	// Staff - Add New Payment.
	public static function add_new_payment($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$invoice_id 	= isset($params['invoice_id']) ? absint($params['invoice_id']) : 0;
		$payment_amount = isset($params['payment_amount']) ? WLSM_Config::sanitize_money($params['payment_amount']) : 0;
		$payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
		$transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
		$payment_note   = isset($params['payment_note']) ? sanitize_text_field($params['payment_note']) : '';
		$payment_date   = isset($params['payment_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['payment_date'])) : NULL;
		$bank_name 		= isset($params['bank_name']) ? sanitize_text_field($params['bank_name']) : '';
		$cheque_number 	= isset($params['cheque_number']) ? sanitize_text_field($params['cheque_number']) : '';
		$cheque_date   	= isset($params['cheque_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['cheque_date'])) : NULL;
		$authorized_by 	= isset($params['authorized_by']) ? sanitize_text_field($params['authorized_by']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (! $invoice_id) {
					throw new Exception(esc_html__('Please select a valid invoice.', 'school-management'));
				}

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				// Checks if invoice exists.
				$invoice = WLSM_M_Staff_Accountant::fetch_invoice($school_id, $session_id, $invoice_id);

				if (! $invoice) {
					throw new Exception(esc_html__('Invoice not found.', 'school-management'));
				}

				$partial_payment = isset($invoice->partial_payment) ? sanitize_text_field($invoice->partial_payment) : '';

				if (strlen($payment_method) > 50) {
					throw new Exception(esc_html__('Maximum length cannot exceed 50 characters', 'school-management'));
				}

				if (empty($payment_date)) {
					throw new Exception(esc_html__('Please specify payment date.', 'school-management'));
				} else {
					$payment_date = $payment_date->format('Y-m-d');
				}

				if ( !empty($cheque_date)) {
					$cheque_date = $cheque_date->format('Y-m-d');
				}else{
					$cheque_date = NULL;
				}

				$due = $invoice->payable - $invoice->paid;

				if (strlen($payment_method) > 50) {
					throw new Exception(esc_html__('Maximum length cannot exceed 50 characters.', 'school-management'));
				}

				if (!in_array($payment_method, array_keys(WLSM_M_Invoice::collect_payment_methods()))) {
					throw new Exception(esc_html__('Please select a valid payment method.', 'school-management'));
				}

				$message = esc_html__('Payment added successfully.', 'school-management');

				$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

				// Payment data.
				$payment_data = array(
					'receipt_number'    => $receipt_number,
					'amount'            => $payment_amount,
					'transaction_id'    => $transaction_id,
					'payment_method'    => $payment_method,
					'note'              => $payment_note,
					'invoice_label'     => $invoice->invoice_title,
					'invoice_payable'   => $invoice->payable,
					'student_record_id' => $invoice->student_id,
					'invoice_id'        => $invoice_id,
					'school_id'         => $school_id,
					'created_at'        => $payment_date,
					'bank_name'        	=> $bank_name,
					'cheque_number'     => $cheque_number,
					'cheque_date'       => $cheque_date,
					'authorized_by'     => $authorized_by,
				);

				$payment_data['added_by'] = get_current_user_id();

				$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

				$new_payment_id = $wpdb->insert_id;

				$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

				if (isset($new_payment_id)) {
					// Notify for offline fee submission.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'payment_id' => $new_payment_id,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_offline_fee_submission', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_offline_fee_submission_to_parent', $data);
				}

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Payment Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View pending payments.
	public static function view_pending_payments($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$pending_payments = WLSM_M::fetch_pending_payments($school_id, $session_id);

				if ( ! $pending_payments ) {
					throw new Exception(esc_html__('There is no pending payments.', 'school-management'));
				}

				if ($pending_payments) {
					foreach ($pending_payments as $pending_payment) {
						$data = array(
							'ID'   				=> isset($pending_payment->ID) ? absint($pending_payment->ID) : 0,
							'receipt_number'   	=> isset($pending_payment->receipt_number) ? WLSM_M_Staff_Class::get_label_text($pending_payment->receipt_number) : '',
							'amount'   			=> isset($pending_payment->amount) ? WLSM_M_Staff_Class::get_label_text($pending_payment->amount) : '',
							'payment_method' 	=> isset($pending_payment->payment_method) ? WLSM_M_Staff_Class::get_label_text($pending_payment->payment_method) : '',
							'transaction_id'   	=> isset($pending_payment->transaction_id) ? WLSM_M_Staff_Class::get_label_text($pending_payment->transaction_id) : '',
							'attachment'   		=> isset($pending_payment->attachment) ? WLSM_M_Staff_Class::get_label_text($pending_payment->attachment) : '',
							'date'   			=> isset($pending_payment->created_at) ? WLSM_M_Staff_Class::get_date_text($pending_payment->created_at) : '',
							'note'   			=> isset($pending_payment->note) ? WLSM_M_Staff_Class::get_label_text($pending_payment->note) : '',
							'invoice_id'   		=> isset($pending_payment->invoice_id) ? absint($pending_payment->invoice_id) : 0,
							'student_id'   		=> isset($pending_payment->student_id) ? absint($pending_payment->student_id) : 0,
							'name'   			=> isset($pending_payment->name) ? WLSM_M_Staff_Class::get_name_text($pending_payment->name) : '',
							'admission_number' 	=> isset($pending_payment->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($pending_payment->admission_number) : '',
							'class_label' 		=> isset($pending_payment->class_label) ? WLSM_M_Staff_Class::get_label_text($pending_payment->class_label) : '',
							'section_label' 	=> isset($pending_payment->section_label) ? WLSM_M_Staff_Class::get_label_text($pending_payment->section_label) : '',
							'enrollment_number' => isset($pending_payment->enrollment_number) ? WLSM_M_Staff_Class::get_admission_no_text($pending_payment->enrollment_number) : '',
							'phone'   			=> isset($pending_payment->phone) ? WLSM_M_Staff_Class::get_phone_text($pending_payment->phone) : '',
							'father_name'   	=> isset($pending_payment->father_name) ? WLSM_M_Staff_Class::get_name_text($pending_payment->father_name) : '',
							'father_phone'   	=> isset($pending_payment->father_phone) ? WLSM_M_Staff_Class::get_phone_text($pending_payment->father_phone) : '',
						);
						$response_data['verify_payments'][] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Payments For Verification Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete pending payment.
	public static function delete_pending_payment($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_payments', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$pending_payment = WLSM_M_Staff_Accountant::get_pending_payment($school_id, $session_id, $id);

				if ( ! $pending_payment ) {
					throw new Exception(esc_html__('Pending payment not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_PENDING_PAYMENTS, array('ID' => $id, 'school_id' => $school_id));
				$message = esc_html__('Pending payment deleted successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View payment history.
	public static function view_payment_history($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$start_date  	= isset( $params['start_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['start_date'] ) ) : NULL;
		$end_date    	= isset( $params['end_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $params['end_date'] ) ) : NULL;

		try {
			if ($staff_permissions) {
				global $wpdb;

				if ( ! empty( $start_date ) ) {
					$start_date = $start_date->format( 'Y-m-d' );
				}

				if ( ! empty( $end_date ) ) {
					$end_date = $end_date->format( 'Y-m-d' );

					if ( ! empty( $end_date ) && $start_date > $end_date ) {
						throw new Exception(esc_html__('Exam start date must be lower than end date.', 'school-management'));

					}
				}

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$payments = WLSM_M::fetch_payments($school_id, $session_id, $start_date, $end_date);
				
				if ( ! $payments ) {
					throw new Exception(esc_html__('There is no payments.', 'school-management'));
				}

				if ($payments) {
					foreach ($payments as $payment) {
						$data = array(
							'ID'   				=> isset($payment->ID) ? absint($payment->ID) : 0,
							'receipt_number'   	=> isset($payment->receipt_number) ? WLSM_M_Staff_Class::get_label_text($payment->receipt_number) : '',
							'amount'   			=> isset($payment->amount) ? WLSM_M_Staff_Class::get_label_text($payment->amount) : '',
							'payment_method' 	=> isset($payment->payment_method) ? WLSM_M_Staff_Class::get_label_text($payment->payment_method) : '',
							'transaction_id'   	=> isset($payment->transaction_id) ? WLSM_M_Staff_Class::get_label_text($payment->transaction_id) : '',
							'date'   			=> isset($payment->created_at) ? WLSM_M_Staff_Class::get_date_text($payment->created_at) : '',
							'note'   			=> isset($payment->note) ? WLSM_M_Staff_Class::get_label_text($payment->note) : '',
							'invoice_id'   		=> isset($payment->invoice_id) ? absint($payment->invoice_id) : 0,
							'invoice_label'   	=> isset($payment->invoice_label) ? WLSM_M_Staff_Class::get_label_text($payment->invoice_label) : '',
							'student_id'   		=> isset($payment->student_id) ? absint($payment->student_id) : 0,
							'name'   			=> isset($payment->name) ? WLSM_M_Staff_Class::get_name_text($payment->name) : '',
							'admission_number' 	=> isset($payment->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($payment->admission_number) : '',
							'class_label' 		=> isset($payment->class_label) ? WLSM_M_Staff_Class::get_label_text($payment->class_label) : '',
							'section_label' 	=> isset($payment->section_label) ? WLSM_M_Staff_Class::get_label_text($payment->section_label) : '',
							'enrollment_number' => isset($payment->enrollment_number) ? WLSM_M_Staff_Class::get_admission_no_text($payment->enrollment_number) : '',
							'phone'   			=> isset($payment->phone) ? WLSM_M_Staff_Class::get_phone_text($payment->phone) : '',
							'father_name'   	=> isset($payment->father_name) ? WLSM_M_Staff_Class::get_name_text($payment->father_name) : '',
							'father_phone'   	=> isset($payment->father_phone) ? WLSM_M_Staff_Class::get_phone_text($payment->father_phone) : '',
						);
						$response_data['payments'][] = $data;
					}

					if ( $start_date && $end_date ) {
						$response_data['total_amount'] = WLSM_M::fetch_total_payment_amount($school_id, $session_id, $start_date, $end_date);
					} else {
						$response_data['total_amount'] = "";
					}
				}

				$success = true;
				$message = esc_html__('Payments Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete payment.
	public static function delete_payment($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_payments', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$payment = WLSM_M_Staff_Accountant::get_payment($school_id, $session_id, $id);

				if ( ! $payment ) {
					throw new Exception(esc_html__('Payment not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_PAYMENTS, array('ID' => $id, 'school_id' => $school_id));
				$message = esc_html__('Payment deleted successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View Collect Payment.
	public static function view_collect_payment($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id = isset($params['section_id']) ? absint($params['section_id']) : 0;
		$student_id = isset($params['student_id']) ? absint($params['student_id']) : 0;
		$status 	= isset($params['status']) ? sanitize_text_field($params['status']) : '';

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				$filter = array(
					'class_id' 		=> $class_id,
					'section_id' 	=> $section_id,
					'student_id' 	=> $student_id,
					'status' 		=> $status,
				);

				$invoices = WLSM_M::fetch_collect_payment($school_id, $session_id, $filter);

				// var_dump( $invoices ); die;

				if ($invoices) {
					foreach ($invoices as $invoice) {
						$invoice_id = isset($invoice->ID) ? absint($invoice->ID) : 0;
						$paid 		= WLSM_M_Staff_Accountant::get_invoice_payments_total($invoice_id);
						$due 		= isset($invoice->amount) ? ($invoice->amount) - $paid : '';
						$data = array(
							'ID'   				=> isset($invoice->ID) ? absint($invoice->ID) : 0,
							'student_name'  	=> isset($invoice->student_name) ? WLSM_M_Staff_Class::get_label_text($invoice->student_name) : '',
							'father_name'   	=> isset($invoice->father_name) ? WLSM_M_Staff_Class::get_label_text($invoice->father_name) : '',
							'admission_number'  => isset($invoice->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($invoice->admission_number) : '',
							'invoice_number'  	=> isset($invoice->invoice_number) ? WLSM_M_Staff_Class::get_admission_no_text($invoice->invoice_number) : '',
							'invoice_title'   	=> isset($invoice->label) ? WLSM_M_Staff_Class::get_label_text($invoice->label) : '',
							'description' 		=> isset($invoice->description) ? WLSM_M_Staff_Class::get_address_text($invoice->description) : '',
							'payable' 			=> isset($invoice->amount) ? WLSM_M_Staff_Class::get_label_text($invoice->amount) : '',
							'paid' 				=> isset($paid) ? $paid : '',
							'due' 				=> isset($due) ? $due : '',
							'status' 			=> isset($invoice->status) ? WLSM_M_Staff_Class::get_label_text($invoice->status) : '',
							'date_issued' 		=> isset($invoice->date_issued) ? WLSM_M_Staff_Class::get_date_text($invoice->date_issued) : '',
							'due_date' 			=> isset($invoice->due_date) ? WLSM_M_Staff_Class::get_date_text($invoice->due_date) : '',
							'phone' 			=> isset($invoice->phone) ? WLSM_M_Staff_Class::get_phone_text($invoice->phone) : '',
							'class_label' 		=> isset($invoice->class_label) ? WLSM_M_Staff_Class::get_label_text($invoice->class_label) : '',
						);
						$response_data['invoices'][] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Invoices Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View invoices report.
	public static function view_invoices_report($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_invoices', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$section_id 	= isset($params['section_id']) ? absint($params['section_id']) : 0;
		$status 		= isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';

		try {
			if ($staff_permissions) {
				global $wpdb;

				$current_session_id = get_option('wlsm_current_session');
				$session = WLSM_M_Session::get_session($current_session_id);
				if (!$session) {
					throw new Exception(esc_html__('Current session not found. Please contact the administrator.', 'school-management'));
				}
				$session_id = isset($session->ID) ? absint($session->ID) : 0;

				// Get all payment methods
				$collect_payment_methods = WLSM_M_Invoice::collect_payment_methods();

				// Get payment gateway settings
				// Razorpay settings
				$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay($school_id);
				$school_razorpay_enable = $settings_razorpay['enable'];

				// Paytm settings
				$settings_paytm      = WLSM_M_Setting::get_settings_paytm($school_id);
				$school_paytm_enable = $settings_paytm['enable'];

				// Stripe settings
				$settings_stripe      = WLSM_M_Setting::get_settings_stripe($school_id);
				$school_stripe_enable = $settings_stripe['enable'];

				// PayPal settings
				$settings_paypal      = WLSM_M_Setting::get_settings_paypal($school_id);
				$school_paypal_enable = $settings_paypal['enable'];

				// Amberpay settings
				$settings_amberpay      = WLSM_M_Setting::get_settings_amberpay($school_id);
				$school_amberpay_enable = $settings_amberpay['enable'];

				// Pesapal settings
				$settings_pesapal      = WLSM_M_Setting::get_settings_pesapal($school_id);
				$school_pesapal_enable = $settings_pesapal['enable'];

				// Sslcommerz settings
				$settings_sslcommerz      = WLSM_M_Setting::get_settings_sslcommerz($school_id);
				$school_sslcommerz_enable = $settings_sslcommerz['enable'];

				// Paystack settings
				$settings_paystack      = WLSM_M_Setting::get_settings_paystack($school_id);
				$school_paystack_enable = $settings_paystack['enable'];

				// Authorize settings
				$settings_authorize      = WLSM_M_Setting::get_settings_authorize($school_id);
				$school_authorize_enable = $settings_authorize['enable'];

				// Bank transfer settings
				$settings_bank_transfer      = WLSM_M_Setting::get_settings_bank_transfer($school_id);
				$school_bank_transfer_enable = $settings_bank_transfer['enable'];

				// UPI transfer settings
				$settings_upi_transfer      = WLSM_M_Setting::get_settings_upi_transfer($school_id);
				$school_upi_transfer_enable = $settings_upi_transfer['enable'];

				// Cash payment settings
				$settings_cash = WLSM_M_Setting::get_settings_cash($school_id);
				$school_cash_enable = $settings_cash['enable'];

				// Card payment settings
				$settings_card = WLSM_M_Setting::get_settings_card($school_id);
				$school_card_enable = $settings_card['enable'];

				// Check payment settings
				$settings_check = WLSM_M_Setting::get_settings_check($school_id);
				$school_check_enable = $settings_check['enable'];

				// Demand Draft payment settings
				$settings_demand_draft = WLSM_M_Setting::get_settings_demand_draft($school_id);
				$school_demand_draft_enable = $settings_demand_draft['enable'];

				// Create an array of enabled payment methods
				$enabled_payment_methods = array();

				if ($school_stripe_enable && isset($collect_payment_methods['stripe'])) {
					$enabled_payment_methods['stripe'] = $collect_payment_methods['stripe'];
				} else {
					$enabled_payment_methods['stripe'] = '';
				}

				if ($school_paypal_enable && isset($collect_payment_methods['paypal'])) {
					$enabled_payment_methods['paypal'] = $collect_payment_methods['paypal'];
				} else {
					$enabled_payment_methods['paypal'] = '';
				}

				if ($school_razorpay_enable && isset($collect_payment_methods['razorpay'])) {
					$enabled_payment_methods['razorpay'] = $collect_payment_methods['razorpay'];
				} else {
					$enabled_payment_methods['razorpay'] = '';
				}

				if ($school_paytm_enable && isset($collect_payment_methods['paytm'])) {
					$enabled_payment_methods['paytm'] = $collect_payment_methods['paytm'];
				} else {
					$enabled_payment_methods['paytm'] = '';
				}

				if ($school_pesapal_enable && isset($collect_payment_methods['pesapal'])) {
					$enabled_payment_methods['pesapal'] = $collect_payment_methods['pesapal'];
				} else {
					$enabled_payment_methods['pesapal'] = '';
				}

				if ($school_paystack_enable && isset($collect_payment_methods['paystack'])) {
					$enabled_payment_methods['paystack'] = $collect_payment_methods['paystack'];
				} else {
					$enabled_payment_methods['paystack'] = '';
				}

				if ($school_sslcommerz_enable && isset($collect_payment_methods['sslcommerz'])) {
					$enabled_payment_methods['sslcommerz'] = $collect_payment_methods['sslcommerz'];
				} else {
					$enabled_payment_methods['sslcommerz'] = '';
				}

				// Add other payment gateways if they are enabled
				if ($school_bank_transfer_enable && isset($collect_payment_methods['bank-transfer'])) {
					$enabled_payment_methods['bank-transfer'] = $collect_payment_methods['bank-transfer'];
				} else {
					$enabled_payment_methods['bank-transfer'] = '';
				}

				if ($school_upi_transfer_enable && isset($collect_payment_methods['upi-transfer'])) {
					$enabled_payment_methods['upi-transfer'] = $collect_payment_methods['upi-transfer'];
				} else {
					$enabled_payment_methods['upi-transfer'] = '';
				}

				if ($school_amberpay_enable && isset($collect_payment_methods['amberpay'])) {
					$enabled_payment_methods['amberpay'] = $collect_payment_methods['amberpay'];
				} else {
					$enabled_payment_methods['amberpay'] = '';
				}

				// Add cash payment method if enabled
				if ($school_cash_enable && isset($collect_payment_methods['cash'])) {
					$enabled_payment_methods['cash'] = $collect_payment_methods['cash'];
				} else {
					$enabled_payment_methods['cash'] = '';
				}

				// Add check payment method if enabled
				if ($school_check_enable && isset($collect_payment_methods['check'])) {
					$enabled_payment_methods['check'] = $collect_payment_methods['check'];
				} else {
					$enabled_payment_methods['check'] = '';
				}

				// Add demand-draft payment method if enabled
				if ($school_demand_draft_enable && isset($collect_payment_methods['demand-draft'])) {
					$enabled_payment_methods['demand-draft'] = $collect_payment_methods['demand-draft'];
				} else {
					$enabled_payment_methods['demand-draft'] = '';
				}

				// Add card payment method if enabled
				if ($school_card_enable && isset($collect_payment_methods['card'])) {
					$enabled_payment_methods['card'] = $collect_payment_methods['card'];
				} else {
					$enabled_payment_methods['card'] = '';
				}

				$response_data['payment_methods'] = $enabled_payment_methods;

				// Get payment statistics
				$payment_statistics = WLSM_M_Staff_Accountant::get_payment_method_statistics($school_id, $session_id);
				$response_data['payment_statistics'] = $payment_statistics;

				$filter = array(
					'class_id' 			=> $class_id,
					'section_id' 		=> $section_id,
					'payment_method' 	=> $payment_method,
					'status' 			=> $status,
				);

				$invoices = WLSM_M::fetch_invoices_report($school_id, $session_id, $filter);

				if ($invoices) {
					foreach ($invoices as $invoice) {
						$data = array(
							'ID'   				=> isset($invoice->student_id) ? absint($invoice->student_id) : 0,
							'student_name'  	=> isset($invoice->student_name) ? WLSM_M_Staff_Class::get_label_text($invoice->student_name) : '',
							'father_name'   	=> isset($invoice->father_name) ? WLSM_M_Staff_Class::get_label_text($invoice->father_name) : '',
							'admission_number'  => isset($invoice->admission_number) ? WLSM_M_Staff_Class::get_admission_no_text($invoice->admission_number) : '',
							'enrollment_number' => isset($invoice->enrollment_number) ? WLSM_M_Staff_Class::get_admission_no_text($invoice->enrollment_number) : '',
							'phone' 			=> isset($invoice->phone) ? WLSM_M_Staff_Class::get_phone_text($invoice->phone) : '',
							'class_label' 		=> isset($invoice->class_label) ? WLSM_M_Staff_Class::get_label_text($invoice->class_label) : '',
							'section_label' 	=> isset($invoice->section_label) ? WLSM_M_Staff_Class::get_label_text($invoice->section_label) : '',
							'payable'   		=> isset($invoice->payable) ? WLSM_M_Staff_Class::get_label_text($invoice->payable) : '',
							'paid'   			=> isset($invoice->paid) ? WLSM_M_Staff_Class::get_label_text($invoice->paid) : '',
							'due'   			=> isset($invoice->due) ? WLSM_M_Staff_Class::get_label_text($invoice->due) : '',
							// 'payable' 			=> isset($invoice->amount) ? WLSM_M_Staff_Class::get_label_text($invoice->amount) : '',
							// 'paid' 				=> isset($paid) ? $paid : '',
							// 'due' 				=> isset($due) ? $due : '',
						);
						$response_data['invoices'][] = $data;
					}
				}

				$success = true;
				$message = esc_html__('Invoices Report Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view invoices.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View income categories.
	public static function view_income_categories($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$income_categories = WLSM_M_Staff_Accountant::fetch_income_categories($school_id);

				foreach ($income_categories as $income_category) {
					$data[] = array(
						'ID'   			=> isset($income_category->ID) ? absint($income_category->ID) : 0,
						'label'   		=> isset($income_category->label) ? WLSM_M_Staff_Class::get_label_text($income_category->label) : '',
					);
					$response_data['income_categories'] = $data;
				}

				$success = true;
				$message = esc_html__('Income Categories Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add new income category.
	public static function add_new_income_category($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$label 	= isset($params['label']) ? sanitize_text_field($params['label']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please specify income category.', 'school-management'));
				}

				$income_category_exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) as count FROM ' . WLSM_INCOME_CATEGORIES . ' as ic WHERE ic.label = %s AND ic.school_id = %d', $label, $school_id));

				if ($income_category_exist) {
					throw new Exception(esc_html__('Income category already exists with this label.', 'school-management'));
				}

				$data = array(
					'label' 	 => $label,
					'school_id'  => $school_id,
				);
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success 			= $wpdb->insert(WLSM_INCOME_CATEGORIES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Income Category Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Income category details.
	public static function income_category_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$income_category 	= WLSM_M_Staff_Accountant::fetch_income_category($school_id, $id);

				if ($income_category) {
					$data = array(
						'ID' 	=> isset($income_category->ID) ? absint($income_category->ID) : 0,
						'title' => isset($income_category->label) ? WLSM_M_Staff_Class::get_label_text($income_category->label) : '',
					);
					$response_data['income_category'] = $data;
				}

				$success = true;
				$message = esc_html__('Income Category Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit income category.
	public static function edit_income_category($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;
		$label 	= isset($params['label']) ? sanitize_text_field($params['label']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$income_category 	= WLSM_M_Staff_Accountant::get_income_category($school_id, $id);
				if (empty($income_category)) {
					throw new Exception(esc_html__('Income category not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				$income_category_exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) as count FROM ' . WLSM_INCOME_CATEGORIES . ' as ic WHERE ic.label = %s AND ic.school_id = %d AND ic.ID != %d', $label, $school_id, $id));

				if ($income_category_exist) {
					throw new Exception(esc_html__('Income category already exists with this label.', 'school-management'));
				}

				$data = array(
					'label' 	=> $label,
					'school_id' => $school_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_INCOME_CATEGORIES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Income Category Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete income category.
	public static function delete_income_category($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$income_category = WLSM_M_Staff_Accountant::get_income_category($school_id, $id);

				if (empty($income_category)) {
					throw new Exception(esc_html__('Income category not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_INCOME_CATEGORIES, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Income Category Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View incomes.
	public static function view_incomes($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$start_date = isset($params['start_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['start_date'])) : NULL;
		$end_date 	= isset($params['end_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['end_date'])) : NULL;

		try {
			if ($staff_permissions) {
				global $wpdb;

				if ( ! empty( $start_date ) ) {
					$start_date = $start_date->format('Y-m-d');
				}

				if ( ! empty( $end_date ) ) {
					$end_date = $end_date->format('Y-m-d');
				}

				$incomes = WLSM_M::fetch_incomes($school_id, $start_date, $end_date);

				foreach ($incomes as $income) {
					$data[] = array(
						'ID'   				=> isset($income->ID) ? absint($income->ID) : 0,
						'label'   			=> isset($income->label) ? WLSM_M_Staff_Class::get_label_text($income->label) : '',
						'invoice_number'	=> isset($income->invoice_number) ? WLSM_M_Staff_Class::get_label_text($income->invoice_number) : '',
						'amount'   			=> isset($income->amount) ? WLSM_M_Staff_Class::get_label_text($income->amount) : '',
						'income_date'   	=> isset($income->income_date) ? WLSM_M_Staff_Class::get_date_text($income->income_date) : '',
						'note'   			=> isset($income->note) ? WLSM_M_Staff_Class::get_label_text($income->note) : '',
						'doner_name'   		=> isset($income->doner_name) ? WLSM_M_Staff_Class::get_label_text($income->doner_name) : '',
						'income_category'   => isset($income->income_category) ? WLSM_M_Staff_Class::get_label_text($income->income_category) : '',
					);
					$response_data['incomes'] = $data;
				}

				$success = true;
				$message = esc_html__('Incomes Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add new income.
	public static function add_new_income($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$category 		= isset($params['category']) ? sanitize_text_field($params['category']) : '';
		$doner_name 	= isset($params['doner_name']) ? sanitize_text_field($params['doner_name']) : '';
		$amount 		= isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
		$invoice_number = isset($params['invoice_number']) ? sanitize_text_field($params['invoice_number']) : '';
		$date 			= isset($params['date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['date'])) : NULL;
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$note 			= isset($params['note']) ? sanitize_text_field($params['note']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($amount)) {
					throw new Exception(esc_html__('Please enter an amount.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please specify date.', 'school-management'));
				} else {
					$date = $date->format('Y-m-d');
				}

				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				$attachment_id = NULL;

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
								throw new Exception(esc_html__('Invalid file format.', 'school-management'));
							}

							$_FILES['attachment'] = $file_array;
							$attachment_id = media_handle_upload('attachment', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$data = array(
					'label' 	 			=> $label,
					'income_category_id'  	=> $category,
					'doner_name' 	 		=> $doner_name,
					'amount' 	 			=> $amount,
					'invoice_number' 	 	=> $invoice_number,
					'income_date' 	 		=> $date,
					'attachment' 	 		=> $attachment_id,
					'note' 	 				=> $note,
					'school_id'  			=> $school_id,
					'added_by'				=> $user_id
				);
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success 			= $wpdb->insert(WLSM_INCOME, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Income Category Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Income details.
	public static function income_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$income	= WLSM_M_Staff_Accountant::fetch_income($school_id, $id);

				if ($income) {
					$data = array(
						'ID' 					=> isset($income->ID) ? absint($income->ID) : 0,
						'title' 				=> isset($income->label) ? WLSM_M_Staff_Class::get_label_text($income->label) : '',
						'income_category_id'	=> isset($income->income_category_id) ? absint($income->income_category_id) : '',
						'income_category' 		=> isset($income->income_category) ? WLSM_M_Staff_Class::get_label_text($income->income_category) : '',
						'doner_name' 			=> isset($income->doner_name) ? WLSM_M_Staff_Class::get_label_text($income->doner_name) : '',
						'amount' 				=> isset($income->amount) ? WLSM_M_Staff_Class::get_label_text($income->amount) : '',
						'invoice_number' 		=> isset($income->invoice_number) ? WLSM_M_Staff_Class::get_label_text($income->invoice_number) : '',
						'income_date' 			=> isset($income->income_date) ? WLSM_M_Staff_Class::get_date_text($income->income_date) : '',
						'attachment' 			=> isset($income->attachment) ? wp_get_attachment_url($income->attachment) : '',
						'note' 					=> isset($income->label) ? WLSM_M_Staff_Class::get_label_text($income->note) : '',
					);
					$response_data['income'] = $data;
				}

				$success = true;
				$message = esc_html__('Income Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit income.
	public static function edit_income($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$category 		= isset($params['category']) ? sanitize_text_field($params['category']) : '';
		$doner_name 	= isset($params['doner_name']) ? sanitize_text_field($params['doner_name']) : '';
		$amount 		= isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
		$invoice_number = isset($params['invoice_number']) ? sanitize_text_field($params['invoice_number']) : '';
		$date 			= isset($params['date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['date'])) : NULL;
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$note 			= isset($params['note']) ? sanitize_text_field($params['note']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$income 	= WLSM_M_Staff_Accountant::get_income($school_id, $id);
				if (empty($income)) {
					throw new Exception(esc_html__('Income not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($amount)) {
					throw new Exception(esc_html__('Please enter an amount.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please specify date.', 'school-management'));
				} else {
					$date = $date->format('Y-m-d');
				}

				$attachment_id = NULL;

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
								throw new Exception(esc_html__('Invalid file format.', 'school-management'));
							}

							$_FILES['attachment'] = $file_array;
							$attachment_id = media_handle_upload('attachment', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$data = array(
					'label' 	 			=> $label,
					'income_category_id'  	=> $category,
					'doner_name' 	 		=> $doner_name,
					'amount' 	 			=> $amount,
					'invoice_number' 	 	=> $invoice_number,
					'income_date' 	 		=> $date,
					'attachment' 	 		=> $attachment_id,
					'note' 	 				=> $note,
					'school_id'  			=> $school_id,
					'added_by'				=> $user_id
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_INCOME, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Income Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete income.
	public static function delete_income($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_income', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$income = WLSM_M_Staff_Accountant::get_income($school_id, $id);

				if (empty($income)) {
					throw new Exception(esc_html__('Income not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_INCOME, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Income Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete income.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View expense categories.
	public static function view_expense_categories($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$expense_categories = WLSM_M_Staff_Accountant::fetch_expense_categories($school_id);

				foreach ($expense_categories as $expense_category) {
					$data[] = array(
						'ID'   			=> isset($expense_category->ID) ? absint($expense_category->ID) : 0,
						'label'   		=> isset($expense_category->label) ? WLSM_M_Staff_Class::get_label_text($expense_category->label) : '',
					);
					$response_data['expense_categories'] = $data;
				}

				$success = true;
				$message = esc_html__('Expense Categories Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add new expense category.
	public static function add_new_expense_category($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$label 	= isset($params['label']) ? sanitize_text_field($params['label']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please specify expense category.', 'school-management'));
				}

				$expense_category_exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) as count FROM ' . WLSM_EXPENSE_CATEGORIES . ' as ec WHERE ec.label = %s AND ec.school_id = %d', $label, $school_id));

				if ($expense_category_exist) {
					throw new Exception(esc_html__('Expense category already exists with this label.', 'school-management'));
				}

				$data = array(
					'label' 	 => $label,
					'school_id'  => $school_id,
				);
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success 			= $wpdb->insert(WLSM_EXPENSE_CATEGORIES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Expense Category Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Expense category details.
	public static function expense_category_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$expense_category 	= WLSM_M_Staff_Accountant::fetch_expense_category($school_id, $id);

				if ($expense_category) {
					$data = array(
						'ID' 	=> isset($expense_category->ID) ? absint($expense_category->ID) : 0,
						'title' => isset($expense_category->label) ? WLSM_M_Staff_Class::get_label_text($expense_category->label) : '',
					);
					$response_data['expense_category'] = $data;
				}

				$success = true;
				$message = esc_html__('Expense Category Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit expense category.
	public static function edit_expense_category($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;
		$label 	= isset($params['label']) ? sanitize_text_field($params['label']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$expense_category 	= WLSM_M_Staff_Accountant::get_expense_category($school_id, $id);
				if (empty($expense_category)) {
					throw new Exception(esc_html__('Expense category not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please enter a title.', 'school-management'));
				}

				$expense_category_exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) as count FROM ' . WLSM_EXPENSE_CATEGORIES . ' as ec WHERE ec.label = %s AND ec.school_id = %d AND ec.ID != %d', $label, $school_id, $id));

				if ($expense_category_exist) {
					throw new Exception(esc_html__('Expense category already exists with this label.', 'school-management'));
				}

				$data = array(
					'label' 	=> $label,
					'school_id' => $school_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_EXPENSE_CATEGORIES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Expense Category Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit expeses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete expense category.
	public static function delete_expense_category($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$income_category = WLSM_M_Staff_Accountant::get_expense_category($school_id, $id);

				if (empty($income_category)) {
					throw new Exception(esc_html__('Expense category not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_EXPENSE_CATEGORIES, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Expense Category Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View expenses.
	public static function view_expenses($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$start_date = isset($params['start_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['start_date'])) : NULL;
		$end_date 	= isset($params['end_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['end_date'])) : NULL;

		try {
			if ($staff_permissions) {
				global $wpdb;

				if ( ! empty( $start_date ) ) {
					$start_date = $start_date->format('Y-m-d');
				}

				if ( ! empty( $end_date ) ) {
					$end_date = $end_date->format('Y-m-d');
				}

				$expenses = WLSM_M::fetch_expenses($school_id, $start_date, $end_date);

				foreach ($expenses as $expense) {
					$data[] = array(
						'ID'   				=> isset($expense->ID) ? absint($expense->ID) : 0,
						'label'   			=> isset($expense->label) ? WLSM_M_Staff_Class::get_label_text($expense->label) : '',
						'invoice_number'	=> isset($expense->invoice_number) ? WLSM_M_Staff_Class::get_label_text($expense->invoice_number) : '',
						'amount'   			=> isset($expense->amount) ? WLSM_M_Staff_Class::get_label_text($expense->amount) : '',
						'income_date'   	=> isset($expense->expense_date) ? WLSM_M_Staff_Class::get_date_text($expense->expense_date) : '',
						'note'   			=> isset($expense->note) ? WLSM_M_Staff_Class::get_label_text($expense->note) : '',
						'supplier_name'   	=> isset($expense->supplier_name) ? WLSM_M_Staff_Class::get_label_text($expense->supplier_name) : '',
						'expense_category'  => isset($expense->expense_category) ? WLSM_M_Staff_Class::get_label_text($expense->expense_category) : '',
					);
					$response_data['expenses'] = $data;
				}

				$success = true;
				$message = esc_html__('Expense Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add new expense.
	public static function add_new_expense($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$category 		= isset($params['category']) ? sanitize_text_field($params['category']) : '';
		$supplier_name 	= isset($params['supplier_name']) ? sanitize_text_field($params['supplier_name']) : '';
		$amount 		= isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
		$invoice_number = isset($params['invoice_number']) ? sanitize_text_field($params['invoice_number']) : '';
		$date 			= isset($params['date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['date'])) : NULL;
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$note 			= isset($params['note']) ? sanitize_text_field($params['note']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($label)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($amount)) {
					throw new Exception(esc_html__('Please enter an amount.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please specify date.', 'school-management'));
				} else {
					$date = $date->format('Y-m-d');
				}

				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				$attachment_id = NULL;

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
								throw new Exception(esc_html__('Invalid file format.', 'school-management'));
							}

							$_FILES['attachment'] = $file_array;
							$attachment_id = media_handle_upload('attachment', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$data = array(
					'label' 	 			=> $label,
					'expense_category_id'  	=> $category,
					'supplier_name' 	 	=> $supplier_name,
					'amount' 	 			=> $amount,
					'invoice_number' 	 	=> $invoice_number,
					'expense_date' 	 		=> $date,
					'attachment' 	 		=> $attachment_id,
					'note' 	 				=> $note,
					'school_id'  			=> $school_id,
					'added_by'				=> $user_id
				);
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success 			= $wpdb->insert(WLSM_EXPENSES, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Expense Category Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Expese details.
	public static function expense_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$expense	= WLSM_M_Staff_Accountant::fetch_expense($school_id, $id);

				if ($expense) {
					$data = array(
						'ID' 					=> isset($expense->ID) ? absint($expense->ID) : 0,
						'title' 				=> isset($expense->label) ? WLSM_M_Staff_Class::get_label_text($expense->label) : '',
						'expense_category_id'	=> isset($expense->expense_category_id) ? absint($expense->expense_category_id) : '',
						'expense_category' 		=> isset($expense->expense_category) ? WLSM_M_Staff_Class::get_label_text($expense->expense_category) : '',
						'supplier_name' 		=> isset($expense->supplier_name) ? WLSM_M_Staff_Class::get_label_text($expense->supplier_name) : '',
						'amount' 				=> isset($expense->amount) ? WLSM_M_Staff_Class::get_label_text($expense->amount) : '',
						'invoice_number' 		=> isset($expense->invoice_number) ? WLSM_M_Staff_Class::get_label_text($expense->invoice_number) : '',
						'expense_date' 			=> isset($expense->expense_date) ? WLSM_M_Staff_Class::get_date_text($expense->expense_date) : '',
						'attachment' 			=> isset($expense->attachment) ? wp_get_attachment_url($expense->attachment) : '',
						'note' 					=> isset($expense->label) ? WLSM_M_Staff_Class::get_label_text($expense->note) : '',
					);
					$response_data['expense'] = $data;
				}

				$success = true;
				$message = esc_html__('Expense Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit expense.
	public static function edit_expense($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$label 			= isset($params['label']) ? sanitize_text_field($params['label']) : '';
		$category 		= isset($params['category']) ? sanitize_text_field($params['category']) : '';
		$supplier_name 	= isset($params['supplier_name']) ? sanitize_text_field($params['supplier_name']) : '';
		$amount 		= isset($params['amount']) ? sanitize_text_field($params['amount']) : '';
		$invoice_number = isset($params['invoice_number']) ? sanitize_text_field($params['invoice_number']) : '';
		$date 			= isset($params['date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($params['date'])) : NULL;
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$note 			= isset($params['note']) ? sanitize_text_field($params['note']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$expense 	= WLSM_M_Staff_Accountant::get_expense($school_id, $id);
				if (empty($expense)) {
					throw new Exception(esc_html__('Expense not found.', 'school-management'));
				}

				if (empty($label)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($amount)) {
					throw new Exception(esc_html__('Please enter an amount.', 'school-management'));
				}

				if (empty($date)) {
					throw new Exception(esc_html__('Please specify date.', 'school-management'));
				} else {
					$date = $date->format('Y-m-d');
				}

				$attachment_id = NULL;

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
								throw new Exception(esc_html__('Invalid file format.', 'school-management'));
							}

							$_FILES['attachment'] = $file_array;
							$attachment_id = media_handle_upload('attachment', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$data = array(
					'label' 	 			=> $label,
					'expense_category_id'  	=> $category,
					'supplier_name' 	 	=> $supplier_name,
					'amount' 	 			=> $amount,
					'invoice_number' 	 	=> $invoice_number,
					'expense_date' 	 		=> $date,
					'attachment' 	 		=> $attachment_id,
					'note' 	 				=> $note,
					'school_id'  			=> $school_id,
					'added_by'				=> $user_id
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_EXPENSES, $data, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Expense Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit expense.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete expense.
	public static function delete_expense($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_expenses', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$expense = WLSM_M_Staff_Accountant::get_expense($school_id, $id);

				if (empty($expense)) {
					throw new Exception(esc_html__('Expense not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_EXPENSES, array('ID' => $id, 'school_id' => $school_id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Expense Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete expenses.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - View chapters.
	public static function view_chapters($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$chapters = WLSM_M::fetch_chapters($school_id);

				foreach ($chapters as $chapter) {
					$data[] = array(
						'ID'   				=> isset($chapter->ID) ? absint($chapter->ID) : 0,
						'title'   			=> isset($chapter->title) ? WLSM_M_Staff_Class::get_label_text($chapter->title) : '',
						'class_id'   		=> isset($chapter->class_id) ? absint($chapter->class_id) : 0,
						'class_label'   	=> isset($chapter->class_label) ? WLSM_M_Staff_Class::get_label_text($chapter->class_label) : '',
						'subject_id'   		=> isset($chapter->subject_id) ? absint($chapter->subject_id) : 0,
						'subject_label'		=> isset($chapter->subject_label) ? WLSM_M_Staff_Class::get_label_text($chapter->subject_label) : '',
						'created_on'   		=> isset($chapter->created_at) ? WLSM_M_Staff_Class::get_date_text($chapter->created_at) : '',
					);
					$response_data['chapters'] = $data;
				}

				$success = true;
				$message = esc_html__('Chapters Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Add new chapter.
	public static function add_new_chapter($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$title 			= isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$subject_id 	= isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($title)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				$data = array(
					'title' 	 	=> $title,
					'class_id'  	=> $class_id,
					'subject_id' 	=> $subject_id,
				);
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success 			= $wpdb->insert(WLSM_CHAPTER, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Chapter Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Chapter details.
	public static function chapter_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$chapter	= WLSM_M_Staff_Lecture::fetch_chapter($id);

				if ($chapter) {
					$data = array(
						'ID'   				=> isset($chapter->ID) ? absint($chapter->ID) : 0,
						'title'   			=> isset($chapter->title) ? WLSM_M_Staff_Class::get_label_text($chapter->title) : '',
						'class_id'   		=> isset($chapter->class_id) ? absint($chapter->class_id) : 0,
						'class_label'   	=> isset($chapter->class) ? WLSM_M_Staff_Class::get_label_text($chapter->class) : '',
						'subject_id'   		=> isset($chapter->subject_id) ? absint($chapter->subject_id) : 0,
						'subject_label'		=> isset($chapter->label) ? WLSM_M_Staff_Class::get_label_text($chapter->label) : '',
						'created_on'   		=> isset($chapter->created_at) ? WLSM_M_Staff_Class::get_date_text($chapter->created_at) : '',
					);
					$response_data['chapter'] = $data;
				}

				$success = true;
				$message = esc_html__('Chapter Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit chapter.
	public static function edit_chapter($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$title 			= isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$subject_id 	= isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$chapter 	= WLSM_M_Staff_Lecture::get_chapter($id);
				if (empty($chapter)) {
					throw new Exception(esc_html__('Chapter not found.', 'school-management'));
				}

				if (empty($title)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please selct a subject.', 'school-management'));
				}

				$data = array(
					'title' 	 	=> $title,
					'class_id'  	=> $class_id,
					'subject_id' 	=> $subject_id,
				);

				$data['updated_at'] 	= current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_CHAPTER, $data, array('ID' => $id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Chapter Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete chapter.
	public static function delete_chapter($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$chapter = WLSM_M_Staff_Lecture::get_chapter($id);

				if (empty($chapter)) {
					throw new Exception(esc_html__('Chapter not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_CHAPTER, array('ID' => $id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Chapter Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete chapter.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Class subject chapters.
	public static function class_subject_chapters($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 	= $request->get_params();
		$class_id 	= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$subject_id = isset($params['subject_id']) ? absint($params['subject_id']) : 0;

		try {
			if ($staff_permissions) {
				global $wpdb;

				$chapters = WLSM_M::fetch_class_subject_chapters($class_id, $subject_id);

				foreach ($chapters as $chapter) {
					$data[] = array(
						'ID'   				=> isset($chapter->ID) ? absint($chapter->ID) : 0,
						'title'   			=> isset($chapter->title) ? WLSM_M_Staff_Class::get_label_text($chapter->title) : '',
					);
					$response_data['chapters'] = $data;
				}

				$success = true;
				$message = esc_html__('Chapters Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - View lessons.
	public static function view_lessons($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$lessons = WLSM_M::fetch_lessons($school_id);

				foreach ($lessons as $lesson) {
					$data[] = array(
						'ID'   				=> isset($lesson->ID) ? absint($lesson->ID) : 0,
						'title'   			=> isset($lesson->title) ? WLSM_M_Staff_Class::get_label_text($lesson->title) : '',
						'class_id'   		=> isset($lesson->class_id) ? absint($lesson->class_id) : 0,
						'class_label'   	=> isset($lesson->class_label) ? WLSM_M_Staff_Class::get_label_text($lesson->class_label) : '',
						'subject_id'   		=> isset($lesson->subject_id) ? absint($lesson->subject_id) : 0,
						'subject_label'		=> isset($lesson->subject_label) ? WLSM_M_Staff_Class::get_label_text($lesson->subject_label) : '',
						'chapter_id'   		=> isset($lesson->chapter_id) ? absint($lesson->chapter_id) : 0,
						'chapter'			=> isset($lesson->chapter) ? WLSM_M_Staff_Class::get_label_text($lesson->chapter) : '',
						'created_on'   		=> isset($lesson->created_at) ? WLSM_M_Staff_Class::get_date_text($lesson->created_at) : '',
					);
					$response_data['lessons'] = $data;
				}

				$success = true;
				$message = esc_html__('Lessons Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}


	// Staff - Add new lesson.
	public static function add_new_lesson($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('add_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$title 			= isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$subject_id 	= isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$chapter_id 	= isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;
		$link_to 		= isset($params['link_to']) ? sanitize_text_field($params['link_to']) : '';
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$url 			= isset($params['url']) ? sanitize_text_field($params['url']) : '';
		$description	= isset($params['description']) ? wp_kses_post($params['description']) : '';
		try {

			if ($staff_permissions) {
				global $wpdb;

				if (empty($title)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($chapter_id)) {
					throw new Exception(esc_html__('Please select a chapter.', 'school-management'));
				}

				if ($link_to == 'url' ) {
					if (empty($url)) {
						throw new Exception(esc_html__('Please enter a url.', 'school-management'));
					}
				} else if ($link_to == 'attachment' ) {
					if (empty($attachment)) {
						throw new Exception(esc_html__('Please upload an attachment.', 'school-management'));
					}
				} else {
					$link_to 	= '';
					$url 		= '';
					$attachment = NULL;
				}

				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				$attachment_id = NULL;

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
								throw new Exception(esc_html__('Invalid file format.', 'school-management'));
							}

							$_FILES['attachment'] = $file_array;
							$attachment_id = media_handle_upload('attachment', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$data = array(
					'title' 	 	=> $title,
					'class_id'  	=> $class_id,
					'subject_id' 	=> $subject_id,
					'chapter_id' 	=> $chapter_id,
					'link_to' 		=> $link_to,
					'attachment' 	=> $attachment_id,
					'url' 			=> $url,
					'description' 	=> $description,
				);
				$data['created_at'] = current_time('Y-m-d H:i:s');
				$success 			= $wpdb->insert(WLSM_LECTURE, $data);

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Lesson Added Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to add lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Lesson details.
	public static function lesson_details($request)
	{
		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('view_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		try {
			if ($staff_permissions) {
				global $wpdb;

				$params 	= $request->get_params();
				$id 		= isset($params['id']) ? absint($params['id']) : 0;

				$lesson	= WLSM_M_Staff_Lecture::fetch_lecture($id);

				if ($lesson) {
					$data = array(
						'ID'   				=> isset($lesson->ID) ? absint($lesson->ID) : 0,
						'title'   			=> isset($lesson->title) ? WLSM_M_Staff_Class::get_label_text($lesson->title) : '',
						'class_id'   		=> isset($lesson->class_id) ? absint($lesson->class_id) : 0,
						'class_label'   	=> isset($lesson->class) ? WLSM_M_Staff_Class::get_label_text($lesson->class) : '',
						'subject_id'   		=> isset($lesson->subject_id) ? absint($lesson->subject_id) : 0,
						'subject_label'		=> isset($lesson->subject) ? WLSM_M_Staff_Class::get_label_text($lesson->subject) : '',
						'chapter_id'   		=> isset($lesson->chapter_id) ? absint($lesson->chapter_id) : 0,
						'chapter_label'		=> isset($lesson->chapter) ? WLSM_M_Staff_Class::get_label_text($lesson->chapter) : '',
						'link_to'			=> isset($lesson->link_to) ? WLSM_M_Staff_Class::get_link_to_text($lesson->link_to) : '',
						'attachment'		=> isset($lesson->attachment) ? wp_get_attachment_url($lesson->attachment) : '',
						'url'				=> isset($lesson->url) ? WLSM_M_Staff_Class::get_label_text($lesson->url) : '',
						'description'		=> isset($lesson->description) ? WLSM_M_Staff_Class::get_label_text($lesson->description) : '',
						'created_on'   		=> isset($lesson->created_at) ? WLSM_M_Staff_Class::get_date_text($lesson->created_at) : '',
					);
					$response_data['lesson'] = $data;
				}

				$success = true;
				$message = esc_html__('Lesson Retrieved Successfully.', 'school-management');

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to view lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		if (isset($response_data)) {
			$response['data'] = $response_data;
		}

		return new WP_REST_Response($response, 200);
	}

	// Staff - Edit lesson.
	public static function edit_lesson($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : null;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : null;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('edit_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params 		= $request->get_params();
		$id 			= isset($params['id']) ? absint($params['id']) : 0;
		$title 			= isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$class_id 		= isset($params['class_id']) ? absint($params['class_id']) : 0;
		$subject_id 	= isset($params['subject_id']) ? absint($params['subject_id']) : 0;
		$chapter_id 	= isset($params['chapter_id']) ? absint($params['chapter_id']) : 0;
		$link_to 		= isset($params['link_to']) ? sanitize_text_field($params['link_to']) : '';
		$attachment 	= isset($_FILES['attachment']) && is_array($_FILES['attachment']) ? $_FILES['attachment'] : '';
		$url 			= isset($params['url']) ? sanitize_text_field($params['url']) : '';
		$description	= isset($params['description']) ? wp_kses_post($params['description']) : '';

		try {

			if ($staff_permissions) {
				global $wpdb;

				$lesson = WLSM_M_Staff_Lecture::get_lecture($id);
				if (empty($lesson)) {
					throw new Exception(esc_html__('Lesson not found.', 'school-management'));
				}

				if (empty($title)) {
					throw new Exception(esc_html__('Please specify title.', 'school-management'));
				}

				if (empty($class_id)) {
					throw new Exception(esc_html__('Please select a class.', 'school-management'));
				}

				if (empty($subject_id)) {
					throw new Exception(esc_html__('Please select a subject.', 'school-management'));
				}

				if (empty($chapter_id)) {
					throw new Exception(esc_html__('Please select a chapter.', 'school-management'));
				}

				if ($link_to == 'url' ) {
					if (empty($url)) {
						throw new Exception(esc_html__('Please enter a url.', 'school-management'));
					}
				} else if ($link_to == 'attachment' ) {
					if (empty($attachment)) {
						throw new Exception(esc_html__('Please upload an attachment.', 'school-management'));
					}
				} else {
					$link_to 	= '';
					$url 		= '';
					$attachment = NULL;
				}

				if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
					if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
						throw new Exception(esc_html__('This file type is not allowed.', 'school-management'));
					}
				}

				$attachment_id = NULL;

				if (isset($attachment) && is_array($attachment)) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					if (isset($attachment) && is_array($attachment)) {
						if (!empty($_FILES['attachment']['tmp_name'])) {
							$file_array = array(
								'name'     => $_FILES['attachment']['name'],
								'type'     => $_FILES['attachment']['type'],
								'tmp_name' => $_FILES['attachment']['tmp_name'],
								'error'    => $_FILES['attachment']['error'],
								'size'     => $_FILES['attachment']['size'],
							);

							if (!WLSM_Helper::is_valid_file($file_array, 'attachment')) {
								throw new Exception(esc_html__('Invalid file format.', 'school-management'));
							}

							$_FILES['attachment'] = $file_array;
							$attachment_id = media_handle_upload('attachment', 0);

							if (is_wp_error($attachment_id)) {
								throw new Exception($attachment_id->get_error_message());
							}
						}
					}
				}

				$data = array(
					'title' 	 	=> $title,
					'class_id'  	=> $class_id,
					'subject_id' 	=> $subject_id,
					'chapter_id' 	=> $chapter_id,
					'link_to' 		=> $link_to,
					'attachment' 	=> $attachment_id,
					'url' 			=> $url,
					'description' 	=> $description,
				);
				$data['updated_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->update(WLSM_LECTURE, $data, array('ID' => $id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Lesson Updated Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to edit lessons.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}

	// Staff - Delete lesson.
	public static function delete_lesson($request)
	{

		$user_id 	= get_current_user_id();
		$staff 		= WLSM_M::get_staff($user_id);

		if ($staff) {
			$school_id 			= isset($staff->school_id) ? absint($staff->school_id) : 0;
			$section_id 		= isset($staff->section_id) ? absint($staff->section_id) : 0;
			$permissions 		= unserialize($staff->permissions);
			$staff_permissions 	= in_array('delete_lessons', $permissions);
		} else {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$params = $request->get_params();
		$id 	= isset($params['id']) ? absint($params['id']) : 0;

		try {

			if ($staff_permissions) {
				global $wpdb;

				$lesson = WLSM_M_Staff_Lecture::get_lecture($id);

				if (empty($lesson)) {
					throw new Exception(esc_html__('Lesson not found.', 'school-management'));
				}

				$success = $wpdb->delete(WLSM_LECTURE, array('ID' => $id));

				WLSM_Helper::check_buffer();

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$response_data = array();

				$success = true;
				$message = esc_html__('Lesson Deleted Successfully.', 'school-management');
			} else {
				$success = false;
				throw new Exception(esc_html__('You do not have permission to delete chapter.', 'school-management'));
			}
		} catch (Exception $exception) {
			$success = false;
			$message = $exception->getMessage();
		}

		$response = array(
			'success' => (bool) $success,
			'message' => $message,
		);

		return new WP_REST_Response($response, 200);
	}
}
