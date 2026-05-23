<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_User.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Parent.php';

class WLSM_P_Print {
	public static function student_print_id_card() {


		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;


		if (!$user_id) {
			# code...
			$user_id = get_current_user_id();
		}


		if ( ! wp_verify_nonce( $_POST[ 'st-print-id-card-' . $user_id ], 'st-print-id-card-' . $user_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/id_card.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function parent_print_id_card() {
		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'pr-print-id-card-' . $student_id ], 'pr-print-id-card-' . $student_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$user_id = get_current_user_id();

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids( $user_id );

			if ( ! in_array( $student_id, $unique_student_ids ) ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student = WLSM_M_Parent::get_student( $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/id_card.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_payment() {
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-print-invoice-payment-' . $payment_id ], 'st-print-invoice-payment-' . $payment_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			// Checks if payment exists.
			$payment = WLSM_M_Staff_Accountant::get_student_payment( $student_id, $payment_id );
			$school_id = $payment->school_id;

			if ( ! $payment ) {
				throw new Exception( esc_html__( 'Payment not found.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/payment.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function parent_print_payment() {
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'pr-print-invoice-payment-' . $payment_id ], 'pr-print-invoice-payment-' . $payment_id ) ) {
			die();
		}

		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

		try {
			ob_start();
			global $wpdb;

			$user_id = get_current_user_id();

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids( $user_id );

			if ( ! in_array( $student_id, $unique_student_ids ) ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student = WLSM_M_Parent::get_student( $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			// Checks if payment exists.
			$payment = WLSM_M_Staff_Accountant::get_student_payment( $student_id, $payment_id );

			if ( ! $payment ) {
				throw new Exception( esc_html__( 'Payment not found.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/payment.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_class_time_table() {
		try {
			ob_start();
			global $wpdb;

			$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;


			if (!$user_id) {
				# code...
				$user_id = get_current_user_id();
			}

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$section_id = $student->section_id;

			if ( ! wp_verify_nonce( $_POST[ 'st-print-class-time-table-' . $section_id ], 'st-print-class-time-table-' . $section_id ) ) {
				die();
			}

			$section = WLSM_M_Staff_Class::get_school_section( $school_id, $section_id );

			$class_label   = $section->class_label;
			$section_label = $section->label;

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/class_time_table.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function parent_print_class_time_table() {
		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

		try {
			ob_start();
			global $wpdb;

			$user_id = get_current_user_id();

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids( $user_id );

			if ( ! in_array( $student_id, $unique_student_ids ) ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student = WLSM_M_Parent::get_student( $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$section_id = $student->section_id;

			if ( ! wp_verify_nonce( $_POST[ 'pr-print-class-time-table-' . $section_id ], 'pr-print-class-time-table-' . $section_id ) ) {
				die();
			}

			$section = WLSM_M_Staff_Class::get_school_section( $school_id, $section_id );

			$class_label   = $section->class_label;
			$section_label = $section->label;

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/class_time_table.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_exam_time_table() {
		$exam_id = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-print-exam-time-table-' . $exam_id ], 'st-print-exam-time-table-' . $exam_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			if (!$user_id) {
				# code...
				$user_id = get_current_user_id();
			}

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if exam exists.
			$exam = WLSM_M_Staff_Examination::get_class_school_exam_time_table( $school_id, $class_school_id, $exam_id );

			if ( ! $exam ) {
				throw new Exception( esc_html__( 'Exam not found.', 'school-management' ) );
			}

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label( $school_id, $exam_id );
			$exam_papers  = WLSM_M_Staff_Examination::fetch_exam_papers( $school_id, $exam_id );

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/exam_time_table.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_exam_admit_card() {
		$admit_card_id = isset( $_POST['admit_card_id'] ) ? absint( $_POST['admit_card_id'] ) : 0;
		$user_id	   = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-print-exam-admit-card-' . $admit_card_id ], 'st-print-exam-admit-card-' . $admit_card_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;


			if (!$user_id) {
				# code...
				$user_id = get_current_user_id();
			}

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if admit card exists.
			$admit_card = WLSM_M_Staff_Examination::fetch_student_admit_card( $school_id, $student_id, $admit_card_id );

			if ( ! $admit_card ) {
				throw new Exception( esc_html__( 'Admit card not found.', 'school-management' ) );
			}

			$exam_id = $admit_card->exam_id;

			// Checks if exam exists.
			$exam = WLSM_M_Staff_Examination::fetch_exam( $school_id, $exam_id );

			if ( ! $exam ) {
				throw new Exception( esc_html__( 'Exam not found.', 'school-management' ) );
			}

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label( $school_id, $exam_id );
			$exam_papers  = WLSM_M_Staff_Examination::fetch_exam_papers( $school_id, $exam_id );

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/exam_admit_card.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_exam_results() {
		$admit_card_id = isset( $_POST['admit_card_id'] ) ? absint( $_POST['admit_card_id'] ) : 0;
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-print-exam-results-' . $admit_card_id ], 'st-print-exam-results-' . $admit_card_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;


			if (!$user_id) {
				$user_id = get_current_user_id();
				# code...
			}

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if admit card exists for published exam result.
			$admit_card = WLSM_M_Staff_Examination::get_student_published_exam_result( $school_id, $student->ID, $admit_card_id );

			if ( ! $admit_card ) {
				throw new Exception( esc_html__( 'Exam result not found.', 'school-management' ) );
			}

			$exam = WLSM_M_Staff_Examination::fetch_exam( $school_id, $admit_card->exam_id );

			$exam_id     = $exam->ID;
			$exam_title  = $exam->exam_title;
			$exam_center = $exam->exam_center;
			$start_date  = $exam->start_date;
			$end_date    = $exam->end_date;
			$show_rank   = $exam->show_rank;
			$show_remark = $exam->show_remark;
			$show_eremark = $exam->show_eremark;
			$psychomotor_enable = $exam->psychomotor_analysis;

			$enable_max_marks = $exam->enable_total_marks;
			$enable_obtained = $exam->results_obtained_marks;

			$psychomotor =  WLSM_Config::sanitize_psychomotor( $exam->psychomotor );

			$exam_papers  = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card( $school_id, $admit_card_id );
			$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card( $school_id, $admit_card_id );

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/exam_results.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_exam_result_subjectwise() {
		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'result-subject-wise-' . $student_id ], 'result-subject-wise-' . $student_id ) ) {
			die();
		}
			global $wpdb;
			$user_id = get_current_user_id();
			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids( $user_id );

			if ( ! in_array( $student_id, $unique_student_ids ) ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student = WLSM_M_Parent::get_student( $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			if ($student) {
				$student_id = $student->ID;
			}

		try {
			ob_start();
			global $wpdb;


			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$class_school_id = $student->class_school_id;

			$class_id = $student->class_id;

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/result_subject_wise.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function parent_print_exam_results() {
		$student_id    = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
		$admit_card_id = isset( $_POST['admit_card_id'] ) ? absint( $_POST['admit_card_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'pr-print-exam-results-' . $admit_card_id ], 'pr-print-exam-results-' . $admit_card_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$user_id = get_current_user_id();

			$unique_student_ids = WLSM_M_Parent::get_parent_student_ids( $user_id );

			if ( ! in_array( $student_id, $unique_student_ids ) ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student = WLSM_M_Parent::get_student( $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if admit card exists for published exam result.
			$admit_card = WLSM_M_Staff_Examination::get_student_published_exam_result( $school_id, $student->ID, $admit_card_id );

			if ( ! $admit_card ) {
				throw new Exception( esc_html__( 'Exam result not found.', 'school-management' ) );
			}

			$exam = WLSM_M_Staff_Examination::fetch_exam( $school_id, $admit_card->exam_id );

			$exam_id            = $exam->ID;
			$exam_title         = $exam->exam_title;
			$exam_center        = $exam->exam_center;
			$start_date         = $exam->start_date;
			$end_date           = $exam->end_date;
			$show_rank          = $exam->show_rank;
			$show_remark        = $exam->show_remark;
			$show_eremark       = $exam->show_eremark;
			$psychomotor_enable = $exam->psychomotor_analysis;

			$enable_max_marks = $exam->enable_total_marks;
			$enable_obtained  = $exam->results_obtained_marks;

			$psychomotor =  WLSM_Config::sanitize_psychomotor( $exam->psychomotor );

			$exam_papers  = WLSM_M_Staff_Examination::get_exam_papers_by_admit_card( $school_id, $admit_card_id );
			$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card( $school_id, $admit_card_id );

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/exam_results.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_results_assessment() {
		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
		$user_id	   = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-print-results-assessment-' . $student_id ], 'st-print-results-assessment-' . $student_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			if (!$user_id) {
				# code...
				$user_id = get_current_user_id();
			}

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/result_assessment.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function student_print_results_subject_wise() {
		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-print-results-subject-wise-' . $student_id ], 'st-print-results-subject-wise-' . $student_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			if (!$user_id) {
				$user_id = get_current_user_id();
				# code...
			}

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$class_id      = $student->class_id;
			$session_label = $student->session_label;

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/result_subject_wise.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function print_exam_time_table() {
		$exam_id = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'print-exam-time-table-' . $exam_id ], 'print-exam-time-table-' . $exam_id ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;

			// Checks if exam exists.
			$exam = WLSM_M_Staff_Examination::fetch_exam( $school_id, $exam_id );

			if ( ! $exam ) {
				throw new Exception( esc_html__( 'Exam not found.', 'school-management' ) );
			}

			$exam_classes = WLSM_M_Staff_Examination::fetch_exam_classes_label( $school_id, $exam_id );
			$exam_papers  = WLSM_M_Staff_Examination::fetch_exam_papers( $school_id, $exam_id );

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/exam_time_table.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function view_ticket() {
		if (!isset($_POST['ticket_id']) || !isset($_POST['st-ticket-' . $_POST['ticket_id']])) {
			wp_send_json_error(['message' => __('Invalid request.', 'school-management')]);
		}

		$ticket_id = absint($_POST['ticket_id']);
		$nonce = $_POST['st-ticket-' . $ticket_id];

		if (!wp_verify_nonce($nonce, 'st-ticket-' . $ticket_id)) {
			wp_send_json_error(['message' => __('Invalid nonce.', 'school-management')]);
		}

		$ticket = WLSM_M_Staff_Tickets::fetch_ticket($ticket_id);
		if (!$ticket) {
			wp_send_json_error(['message' => __('Ticket not found.', 'school-management')]);
		}

		$staff = WLSM_M_Staff_General::fetch_staff_name($ticket->assigned_to);
		$staff_name = $staff ? $staff->name : '';

		ob_start();
		?>
		<div class="wlsm-ticket-details">
			<h3 class="wlsm-ticket-title"><?php esc_html_e('Ticket Details', 'school-management'); ?></h3>
			<div class="wlsm-row wlsm-ticket-row">
				<div class="wlsm-col-md-6 wlsm-ticket-col">
					<p class="wlsm-ticket-status"><strong><?php esc_html_e('Status:', 'school-management'); ?></strong>
						<?php echo wp_kses_post(WLSM_M_Staff_Tickets::get_status_badge($ticket->status)); ?>
					</p>
					<p class="wlsm-ticket-priority"><strong><?php esc_html_e('Priority:', 'school-management'); ?></strong>
						<span class="wlsm-badge wlsm-badge-<?php echo esc_attr($ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'info')); ?> wlsm-ticket-badge">
							<?php echo esc_html(ucfirst($ticket->priority)); ?>
						</span>
					</p>
				</div>
				<div class="wlsm-col-md-6 wlsm-ticket-col">
					<p class="wlsm-ticket-assigned"><strong><?php esc_html_e('Assigned To:', 'school-management'); ?></strong>
						<?php echo esc_html($staff_name); ?>
					</p>
					<p class="wlsm-ticket-created"><strong><?php esc_html_e('Created At:', 'school-management'); ?></strong>
						<?php echo esc_html(WLSM_Config::get_date_text($ticket->created_at)); ?>
					</p>
				</div>
			</div>
			<hr class="wlsm-ticket-hr">
			<div class="wlsm-ticket-description">
				<h6 class="wlsm-description-title"><?php esc_html_e('Description:', 'school-management'); ?></h6>
				<p class="wlsm-description-content"><?php echo wp_kses_post(nl2br($ticket->description)); ?></p>
			</div>
			<?php
			$history = WLSM_M_Staff_Tickets::get_ticket_history($ticket->ID);
			if (!empty($history)):
			?>
			<hr class="wlsm-ticket-hr">
			<div class="wlsm-ticket-history">
				<h6 class="wlsm-history-title"><?php esc_html_e('Ticket History:', 'school-management'); ?></h6>
				<div class="wlsm-table-responsive">
					<table class="wlsm-table wlsm-table-bordered wlsm-table-sm wlsm-ticket-table">
						<thead class="wlsm-thead">
							<tr class="wlsm-tr">
								<th class="wlsm-th"><?php esc_html_e('Date', 'school-management'); ?></th>
								<th class="wlsm-th"><?php esc_html_e('Status', 'school-management'); ?></th>
								<th class="wlsm-th"><?php esc_html_e('Comment', 'school-management'); ?></th>
								<th class="wlsm-th"><?php esc_html_e('Updated By', 'school-management'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($history as $item): ?>
								<tr class="wlsm-tr">
									<td class="wlsm-td"><?php echo esc_html(WLSM_Config::get_date_text($item->created_at)); ?></td>
									<td class="wlsm-td"><?php echo wp_kses_post(WLSM_M_Staff_Tickets::get_status_badge($item->status)); ?></td>
									<td class="wlsm-td"><?php echo wp_kses_post(nl2br($item->comment)); ?></td>
									<td class="wlsm-td"><?php echo esc_html($item->changed_by_name); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		wp_send_json_success(['html' => $html]);
	}

	public static function student_view_academic_report() {
		// Debug entry (visible in PHP error log)
		error_log( 'WLSM: student_view_academic_report called' );

		$academic_report_id = isset( $_POST['academic_report_id'] ) ? absint( $_POST['academic_report_id'] ) : 0;

		if ( ! $academic_report_id ) {
			error_log( 'WLSM: Missing academic_report_id' );
			wp_send_json_error( esc_html__( 'Invalid request.', 'school-management' ) );
		}

		// Verify nonce
		$nonce_key = 'st-view-academic-report-' . $academic_report_id;
		if ( empty( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( $_POST[ $nonce_key ], $nonce_key ) ) {
			error_log( 'WLSM: Nonce failed for academic_report_id ' . $academic_report_id );
			wp_send_json_error( esc_html__( 'Security check failed.', 'school-management' ) );
		}

		try {
			global $wpdb;

			// Include required helper classes
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Examination.php';
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/examination/WLSM_Staff_Examination.php';

			$user_id = get_current_user_id();
			$student_user = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student_user ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$school_id  = $student_user->school_id;
			$session_id = $student_user->session_id;
			$student_id = $student_user->ID;

			// Load academic report meta
			$academic_report = WLSM_M::fetch_academic_report( $school_id, $academic_report_id );
			if ( ! $academic_report || ! $academic_report->is_active ) {
				throw new Exception( esc_html__( 'Academic report not found.', 'school-management' ) );
			}

			error_log( 'WLSM: Loaded academic_report ID ' . $academic_report_id . ' for user ID ' . $user_id );

			// Fetch complete student record with class and section info
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$class_school_id = $student->class_school_id;
			$class_id        = $student->class_id;

			// Get session label from database
			$session = $wpdb->get_row( $wpdb->prepare(
				"SELECT label FROM " . WLSM_SESSIONS . " WHERE ID = %d",
				$session_id
			) );
			$session_label = $session ? $session->label : '';

			// Render the report using the existing template
			ob_start();
			$from_front = true;
			$report_id = $academic_report_id;
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/result_subject_wise.php';
			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( Exception $e ) {
			$response = $e->getMessage();
			error_log( 'WLSM: Academic report error - ' . $response );
			wp_send_json_error( $response );
		}
	}

	public static function student_view_academic_multi_group_report() {
		// Debug entry (visible in PHP error log)
		error_log( 'WLSM: student_view_academic_multi_group_report called' );

		$academic_report_id = isset( $_POST['academic_report_id'] ) ? absint( $_POST['academic_report_id'] ) : 0;

		if ( ! $academic_report_id ) {
			error_log( 'WLSM: Missing academic_report_id for multi-group' );
			wp_send_json_error( esc_html__( 'Invalid request.', 'school-management' ) );
		}

		// Verify nonce
		$nonce_key = 'st-view-academic-multi-group-report-' . $academic_report_id;
		if ( empty( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( $_POST[ $nonce_key ], $nonce_key ) ) {
			error_log( 'WLSM: Nonce failed for academic_multi_group_report_id ' . $academic_report_id );
			wp_send_json_error( esc_html__( 'Security check failed.', 'school-management' ) );
		}

		try {
			global $wpdb;

			// Include required helper classes
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Examination.php';
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/examination/WLSM_Staff_Examination.php';

			$user_id = get_current_user_id();
			$student_user = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student_user ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$school_id  = $student_user->school_id;
			$session_id = $student_user->session_id;
			$student_id = $student_user->ID;

			// Load academic multi-group report meta from the correct table
			$academic_report = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM " . WLSM_ACADEMIC_MULTI_GROUP_REPORTS . " WHERE ID = %d AND school_id = %d",
				$academic_report_id,
				$school_id
			) );

			if ( ! $academic_report ) {
				throw new Exception( esc_html__( 'Academic report not found.', 'school-management' ) );
			}

			error_log( 'WLSM: Loaded academic_multi_group_report ID ' . $academic_report_id . ' for user ID ' . $user_id );

			// Fetch complete student record with class and section info
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$class_school_id = $student->class_school_id;
			$class_id        = $student->class_id;
			$is_multi_group_report = true;
			$selected_group_ids = json_decode( $academic_report->exam_groups, true );
			$selected_group_ids = is_array( $selected_group_ids ) ? array_map( 'absint', $selected_group_ids ) : array();

			// Get session label from database
			$session = $wpdb->get_row( $wpdb->prepare(
				"SELECT label FROM " . WLSM_SESSIONS . " WHERE ID = %d",
				$session_id
			) );
			$session_label = $session ? $session->label : '';

			// Render the report using the existing template
			ob_start();
			$from_front = true;
			$report_id = $academic_report_id;
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/result_subject_wise.php';
			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( Exception $e ) {
			$response = $e->getMessage();
			error_log( 'WLSM: Academic multi-group report error - ' . $response );
			wp_send_json_error( $response );
		}
	}
}
