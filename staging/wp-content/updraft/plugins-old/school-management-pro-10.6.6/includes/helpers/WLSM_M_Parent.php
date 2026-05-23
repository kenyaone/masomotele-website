<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/general/WLSM_Staff_General.php';

class WLSM_M_Parent {
	public static function remove_old_transfer_records( $student_records_ids ) {
		$unique_records = array();
		foreach ( $student_records_ids as $from_student_record ) {

			$last_student_record = $from_student_record;
			while ( $from_student_record = WLSM_Staff_General::student_transfer_new_record_exists( $from_student_record ) ) {
				$last_student_record = $from_student_record;
			}

			array_push( $unique_records, $last_student_record );
		}

		return array_unique( $unique_records );
	}

	public static function remove_old_promotion_records( $student_records_ids ) {
		$unique_records = array();
		foreach ( $student_records_ids as $from_student_record ) {

			$last_student_record = $from_student_record;
			while ( $from_student_record = WLSM_Staff_General::student_new_record_exists( $from_student_record ) ) {
				$last_student_record = $from_student_record;
			}

			array_push( $unique_records, $last_student_record );
		}

		return array_unique( $unique_records );
	}

	public static function fetch_students( $student_ids ) {
		global $wpdb;

		// Return empty array if no student IDs provided
		if ( empty( $student_ids ) || ! is_array( $student_ids ) ) {
			return array();
		}

		$place_holders = array();
		foreach ( $student_ids as $student_id ) {
			array_push( $place_holders, '%d' );
		}

		$students = $wpdb->get_results(
			$wpdb->prepare( 'SELECT sr.ID, sr.name as student_name, se.ID as section_id, c.label as class_label, se.label as section_label, s.label as school_name, s.phone as school_phone, s.email as school_email, s.address as school_address, s.description as school_description, cs.school_id, sr.enrollment_number, sr.admission_number, sr.roll_number, sr.father_name, sr.mother_name, sr.father_phone, sr.mother_phone, sr.father_occupation, sr.mother_occupation, ss.label as session_label, sr.user_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
			WHERE sr.ID IN(' . implode( ', ', $place_holders ) . ')', $student_ids )
		);
		return $students;
	}

	public static function fetch_student( $student_id ) {
		global $wpdb;
		$student = $wpdb->get_row(
			$wpdb->prepare( 'SELECT sr.ID, sr.name as student_name, sr.father_name, sr.mother_name, s.ID as school_id, c.ID as class_id, c.label as class_label, sr.section_id, se.label as section_label, s.label as school_name, sr.enrollment_number, sr.admission_number, sr.roll_number, ss.label as session_label, se.class_school_id, sr.session_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
			WHERE sr.ID = %d', $student_id )
		);
		return $student;
	}

	public static function get_student( $student_id ) {
		global $wpdb;
		$student = $wpdb->get_row(
			$wpdb->prepare( 'SELECT sr.ID, sr.session_id, s.ID as school_id, cs.ID as class_school_id, sr.section_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
			WHERE sr.ID = %d', $student_id )
		);
		return $student;
	}

	public static function get_parent_student_ids( $user_id ) {
		global $wpdb;

		// Check if user is parent.
		$student_records_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT sr.ID FROM ' . WLSM_STUDENT_RECORDS . ' as sr WHERE sr.parent_user_id = %d ORDER BY sr.ID', $user_id ) );

		// Remove old transfers.
		$unique_student_ids = WLSM_M_Parent::remove_old_transfer_records( $student_records_ids );

		// Remove old promotions.
		$unique_student_ids = WLSM_M_Parent::remove_old_promotion_records( $unique_student_ids );

		return $unique_student_ids;
	}

	public static function fetch_student_attendance( $student_id )
	{
		global $wpdb;
		$student_attendance = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT sa.attendance_date) as total_attendance, '
				. 'SUM(sa.status = "p") as total_present, SUM(sa.status = "a") as total_absent, '
				. 'SUM(sa.status = "h") as total_holiday, SUM(sa.status = "l") as total_late '
				. 'FROM ' . WLSM_ATTENDANCE . ' as sa '
				. 'WHERE sa.student_record_id = %d',
				$student_id
			)
		);
		return $student_attendance;
	}
}
