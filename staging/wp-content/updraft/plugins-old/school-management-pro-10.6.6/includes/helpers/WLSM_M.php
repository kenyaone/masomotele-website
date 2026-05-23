<?php
defined('ABSPATH') || die();

class WLSM_M
{
	public static function get_student($user_id)
	{
		global $wpdb;
		$student = $wpdb->get_row(
			$wpdb->prepare('SELECT sr.ID, sr.name as student_name, sr.email, sr.phone, sr.father_name, sr.father_phone, sr.admission_number, sr.route_vehicle_id, sr.enrollment_number, sr.photo_id, c.ID as class_id, c.label as class_label, se.class_school_id, se.ID as section_id, se.label as section_label, sr.roll_number, u.user_email as login_email, u.user_login as username, sr.session_id, ss.label as session_label, s.ID as school_id, s.label as school_name FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				JOIN ' . WLSM_USERS . ' as u ON u.ID = sr.user_id
				LEFT OUTER JOIN ' . WLSM_TRANSFERS . ' as tf ON tf.from_student_record = sr.ID
				WHERE sr.is_active = 1 AND tf.ID IS NULL AND sr.user_id = %d', $user_id)
		);

		return $student;
	}

	public static function get_student_by_id($student_id)
	{
		global $wpdb;
		$student = $wpdb->get_row(
			$wpdb->prepare('SELECT sr.ID, sr.name as student_name, sr.email, sr.phone, sr.father_name, sr.father_phone, sr.admission_number, sr.route_vehicle_id, sr.enrollment_number, sr.photo_id, c.ID as class_id, c.label as class_label, se.class_school_id, se.ID as section_id, se.label as section_label, sr.roll_number, u.user_email as login_email, u.user_login as username, sr.session_id, ss.label as session_label, s.ID as school_id, s.label as school_name, sr.user_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = sr.user_id
				LEFT OUTER JOIN ' . WLSM_TRANSFERS . ' as tf ON tf.from_student_record = sr.ID
				WHERE sr.is_active = 1 AND tf.ID IS NULL AND sr.ID = %d', $student_id)
		);

		return $student;
	}

	public static function get_student_profile($user_id)
	{
		global $wpdb;
		$student = $wpdb->get_row(
			$wpdb->prepare('SELECT sr.ID, sr.name as student_name, sr.photo_id, c.label as class_label, se.label as section_label, ss.label as session_label, s.label as school_name, s.ID as school_id, se.ID as session_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				LEFT OUTER JOIN ' . WLSM_TRANSFERS . ' as tf ON tf.from_student_record = sr.ID
				WHERE sr.is_active = 1 AND tf.ID IS NULL AND sr.user_id = %d', $user_id)
		);

		return $student;
	}

	public static function notices_per_page()
	{
		return 10;
	}

	public static function lesson_per_page()
	{
		return 6;
	}

	public static function lesson_query()
	{
		return 'SELECT l.ID, l.title, l.description, l.attachment, l.url, l.link_to, l.created_at, c.label as class, s.label as `subject`, cp.title as chapter FROM ' . WLSM_LECTURE . ' as l
		JOIN ' . WLSM_CLASSES . ' as c ON l.class_id = c.ID
		LEFT OUTER JOIN ' . WLSM_SUBJECTS . ' as s ON s.ID = l.subject_id
		LEFT OUTER JOIN ' . WLSM_CHAPTER . ' as cp ON cp.ID = l.chapter_id';
	}

	public static function notices_query($school_id = null)
	{
		$where = 'WHERE n.is_active = 1';
		if ($school_id) {
			$where .= ' AND n.school_id = %d';
		}
		return 'SELECT n.ID, n.title, n.description, n.attachment, n.url, n.link_to, n.is_active, n.created_at, n.notice_data FROM ' . WLSM_NOTICES . ' as n ' . $where . ' GROUP BY n.ID';
	}

	public static function notices_query_api($school_id = null)
	{
		$where = 'WHERE n.is_active = 1';
		if ($school_id) {
			$where .= ' AND n.school_id = %d';
		}
		return 'SELECT n.ID, n.title, n.description, n.attachment, n.url, n.link_to, n.is_active, n.created_at, n.notice_data FROM ' . WLSM_NOTICES . ' as n ' . $where . ' GROUP BY n.ID';
	}

	public static function payments_per_page()
	{
		return 10;
	}

	public static function payments_query()
	{
		return 'SELECT sr.ID as student_id, sr.name as student_name, sr.admission_number, sr.phone, sr.father_name, sr.father_phone, p.ID, p.receipt_number, p.amount, p.payment_method, p.transaction_id, p.created_at, p.note, p.invoice_label, p.invoice_payable, p.invoice_id, i.label as invoice_title, c.label as class_label, se.label as section_label FROM ' . WLSM_PAYMENTS . ' as p
			JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = p.school_id
			JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = p.student_record_id
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT OUTER JOIN ' . WLSM_INVOICES . ' as i ON i.ID = p.invoice_id
			WHERE sr.ID = %d GROUP BY p.ID';
	}

	public static function events_per_page()
	{
		return 10;
	}

	public static function events_query()
	{
		return 'SELECT ev.ID, ev.title, ev.event_date, ev.image_id, ev.description, COUNT(sr.ID) as student_joined FROM ' . WLSM_EVENTS . ' as ev
		LEFT OUTER JOIN ' . WLSM_EVENT_RESPONSES . ' as evr ON evr.event_id = ev.ID
		LEFT OUTER JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON evr.student_record_id = sr.ID AND sr.ID = %d
		WHERE ev.school_id = %d AND ev.is_active = 1 GROUP BY ev.ID';
	}

	public static function books_issued_per_page()
	{
		return 10;
	}

	public static function books_issued_query()
	{
		return 'SELECT bki.ID, bki.quantity as issued_quantity, bki.date_issued, bki.return_date, bki.returned_at, bk.title, bk.author, bk.subject, bk.rack_number, bk.book_number, bk.isbn_number FROM ' . WLSM_BOOKS_ISSUED . ' as bki
		JOIN ' . WLSM_BOOKS . ' as bk ON bk.ID = bki.book_id
		JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = bki.student_record_id
		JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
		JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		WHERE cs.school_id = %d AND ss.ID = %d AND sr.ID = %d GROUP BY bki.ID';
	}

	public static function study_materials_per_page()
	{
		return 10;
	}

	public static function study_materials_query()
	{
		return 'SELECT sm.ID, sm.label as title, sm.description, sm.downloadable, sm.attachments, sm.created_at FROM ' . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . ' as cssm
		JOIN ' . WLSM_STUDY_MATERIALS . ' as sm ON sm.ID = cssm.study_material_id
		JOIN ' . WLSM_SUBJECTS . ' as wl ON wl.ID = cssm.study_material_subject_id
		LEFT OUTER JOIN ' . WLSM_SECTIONS . ' as ws ON ws.ID = cssm.study_material_section_id
		WHERE cssm.class_school_id = %d GROUP BY sm.ID';
	}

	public static function study_material_query()
	{
		return 'SELECT sm.ID, sm.label as title, sm.description, sm.downloadable, sm.url, sm.attachments, sm.created_at FROM ' . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . ' as cssm JOIN ' . WLSM_STUDY_MATERIALS . ' as sm ON sm.ID = cssm.study_material_id WHERE cssm.class_school_id = %d AND sm.ID = %d';
	}

	public static function leaves_per_page()
	{
		return 10;
	}

	public static function leaves_query()
	{
		return 'SELECT lv.ID, lv.description, lv.start_date, lv.end_date, lv.is_approved, lv.approved_by, c.label as class_label, se.label as section_label, sr.enrollment_number, sr.name as student_name FROM ' . WLSM_LEAVES . ' as lv
		JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = lv.student_record_id
		JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
		JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		WHERE cs.school_id = %d AND ss.ID = %d AND sr.ID = %d GROUP BY lv.ID';
	}

	public static function homeworks_per_page()
	{
		return 10;
	}

	public static function homeworks_query()
	{
		return 'SELECT hw.ID, hw.title, hw.description, hw.attachment_url, hw.homework_date, hw.homework_due_date FROM ' . WLSM_HOMEWORK . ' as hw
					JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = hw.school_id
					JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = hw.session_id
					LEFT OUTER JOIN ' . WLSM_HOMEWORK_SECTION . ' as hwse ON hwse.homework_id = hw.ID
					LEFT OUTER JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = hwse.section_id
					LEFT OUTER JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
					LEFT OUTER JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
					WHERE s.ID = %d AND ss.ID = %d AND se.ID = %d GROUP BY hw.ID';
	}


	public static function homework_query()
	{
		return 'SELECT hw.ID, hw.title, hw.subject, hw.description, hw.downloadable, hw.attachments, hw.attachment_url, hw.homework_date, hw.homework_due_date, c.ID as class_id, cs.ID as class_school_id FROM ' . WLSM_HOMEWORK . ' as hw
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = hw.school_id
				JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = hw.session_id
				LEFT OUTER JOIN ' . WLSM_HOMEWORK_SECTION . ' as hwse ON hwse.homework_id = hw.ID
				LEFT OUTER JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = hwse.section_id
				LEFT OUTER JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				LEFT OUTER JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				WHERE s.ID = %d AND ss.ID = %d AND se.ID = %d AND hw.ID = %d';
	}

	public static function homework_query_submission()
	{
		return 'SELECT hw.ID, hw.title, hw.subject, hw.description, hs.student_id, hw.attachments, hw.homework_date, hs.created_at, hs.updated_at, c.ID as class_id, cs.ID as class_school_id FROM ' . WLSM_HOMEWORK . ' as hw
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = hw.school_id
				JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = hw.session_id
				JOIN ' . WLSM_HOMEWORK_SUBMISSION . ' as hs ON hw.ID = hs.submission_id
				LEFT OUTER JOIN ' . WLSM_HOMEWORK_SECTION . ' as hwse ON hwse.homework_id = hw.ID
				LEFT OUTER JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = hwse.section_id
				LEFT OUTER JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				LEFT OUTER JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				WHERE s.ID = %d AND ss.ID = %d AND se.ID = %d AND hw.ID = %d AND hs.student_id = %d';
	}

	public static function meetings_per_page()
	{
		return 15;
	}

	public static function get_subject($id)
	{
		global $wpdb;
		return $wpdb->prepare('SELECT * FROM ' . WLSM_SUBJECTS . ' as sj
		WHERE sj.ID = %d', $id);
	}

	public static function meetings_query()
	{
		return 'SELECT mt.ID, mt.host_id, mt.meeting_id, mt.recordable, mt.topic, mt.duration, mt.start_at, mt.type, mt.class_type, mt.password, mt.join_url, sj.label as subject_name, se.ID as section_id, sj.code as subject_code, a.name as name, st.user_id FROM ' . WLSM_MEETINGS . ' as mt
		JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = mt.school_id
		LEFT OUTER JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = mt.class_school_id
		LEFT OUTER JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		LEFT OUTER JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = mt.section_id
		LEFT OUTER JOIN ' . WLSM_SUBJECTS . ' as sj ON sj.ID = mt.subject_id
		LEFT OUTER JOIN ' . WLSM_ADMINS . ' as a ON a.ID = mt.admin_id
		LEFT OUTER JOIN ' . WLSM_STAFF . ' as st ON st.ID = a.staff_id
		WHERE mt.school_id = %d AND mt.class_school_id = %d GROUP BY mt.ID';
	}

	public static function lessons_per_page()
	{
		return 6;
	}

	public static function lesson_by_lesson_id_query()
	{
		return 'SELECT l.ID, l.title, s.label as subject_label, l.description, l.attachment, l.url, l.link_to, l.created_at, c.label as class, s.label as subject, cp.title as chapter
		FROM ' . WLSM_LECTURE . ' as l
		JOIN ' . WLSM_CLASSES . ' as c ON l.class_id = c.ID
		LEFT OUTER JOIN ' . WLSM_SUBJECTS . ' as s ON s.ID = l.subject_id
		LEFT OUTER JOIN ' . WLSM_CHAPTER . ' as cp ON cp.ID = l.chapter_id
		WHERE s.class_school_id = %d AND l.ID = %d';
	}

	public static function lessons_only_query()
	{
		return 'SELECT l.ID, l.title, l.description, l.attachment, l.url, l.link_to, l.created_at,
        c.label as class_label, s.label as subject_label, cp.title as chapter_title,
        l.subject_id, l.chapter_id
        FROM ' . WLSM_LECTURE . ' as l
        LEFT JOIN ' . WLSM_CLASSES . ' as c ON l.class_id = c.ID
        LEFT JOIN ' . WLSM_SUBJECTS . ' as s ON s.ID = l.subject_id
        LEFT JOIN ' . WLSM_CHAPTER . ' as cp ON cp.ID = l.chapter_id
        WHERE s.class_school_id = %d';
	}

	public static function subject_wise_lesson_query()
	{
		return 'SELECT l.ID, l.title, s.label as subject_label,  l.description, l.attachment, l.url, l.link_to, l.created_at, c.label as class, s.label as subject, cp.title as chapter
		FROM ' . WLSM_LECTURE . ' as l
		JOIN ' . WLSM_CLASSES . ' as c ON l.class_id = c.ID
		LEFT OUTER JOIN ' . WLSM_SUBJECTS . ' as s ON s.ID = l.subject_id
		LEFT OUTER JOIN ' . WLSM_CHAPTER . ' as cp ON cp.ID = l.chapter_id
		WHERE s.class_school_id = %d AND s.ID = %d';
	}

	public static function get_staff($user_id)
	{
		global $wpdb;
		$staff = $wpdb->get_row(
			$wpdb->prepare('SELECT sf.ID, sf.role, sf.permissions, a.name as staff_name, a.gender, a.dob, a.phone, a.email, a.address, a.salary, a.designation, a.joining_date, a.qualification, a.section_id, s.ID as school_id, s.label as school_name, u.user_email as login_email, u.user_login as username FROM ' . WLSM_STAFF . ' as sf
				JOIN ' . WLSM_ADMINS . ' as a ON a.staff_id = sf.ID
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = sf.school_id
				JOIN ' . WLSM_USERS . ' as u ON u.ID = sf.user_id
				WHERE sf.user_id = %d', $user_id)
		);

		return $staff;
	}

	public static function get_staff_profile($user_id)
	{
		global $wpdb;
		$student = $wpdb->get_row(
			$wpdb->prepare('SELECT sf.ID, a.name as staff_name, a.gender, a.dob, a.phone, a.email, a.address, a.salary, a.designation, a.joining_date, a.qualification, a.section_id, s.ID as school_id, s.label as school_name FROM ' . WLSM_STAFF . ' as sf
				JOIN ' . WLSM_ADMINS . ' as a ON a.staff_id = sf.ID
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = sf.school_id
				JOIN ' . WLSM_USERS . ' as u ON u.ID = sf.user_id
				WHERE sf.user_id = %d', $user_id)
		);

		return $student;
	}

	public static function fetch_classes($school_id)
	{
		global $wpdb;
		$student = $wpdb->get_results(
			$wpdb->prepare('SELECT c.ID, c.label FROM ' . WLSM_CLASSES . ' as c
				LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.class_id = c.ID
				WHERE cs.school_id = %d', $school_id)
		);

		return $student;
	}

	public static function get_assigned_class($section_id)
	{
		global $wpdb;
		$student = $wpdb->get_results(
			$wpdb->prepare('SELECT c.ID, c.label FROM ' . WLSM_CLASSES . ' as c
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.class_id = c.ID
				JOIN ' . WLSM_SECTIONS . ' as se ON se.class_school_id = cs.ID
				JOIN ' . WLSM_ADMINS . ' as a ON a.section_id = se.ID
				WHERE a.section_id = %d', $section_id)
		);

		return $student;
	}

	public static function fetch_sections( $school_id, $class_id)
	{
		global $wpdb;
		$sections = $wpdb->get_results(
			$wpdb->prepare('SELECT se.ID, se.label FROM ' . WLSM_SECTIONS . ' as se
				LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				WHERE cs.school_id = %d AND cs.class_id = %d', $school_id, $class_id)
		);

		return $sections;
	}

	public static function fetch_students( $school_id, $class_id, $section_id, $session_id )
	{
		global $wpdb;
		$students = $wpdb->get_results(
			$wpdb->prepare('SELECT sr.ID, sr.name, sr.roll_number FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				WHERE sr.is_active = 1 AND s.ID = %d AND c.ID = %d AND sr.section_id = %d AND sr.session_id = %d', $school_id, $class_id, $section_id, $session_id)
		);

		return $students;
	}

	public static function fetch_class_students( $school_id, $session_id, $class_id )
	{
		global $wpdb;
		$students = $wpdb->get_results(
			$wpdb->prepare('SELECT sr.ID, sr.name, sr.enrollment_number, sr.roll_number, c.label as class_name, se.label as section_name FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				WHERE sr.is_active = 1 AND s.ID = %d AND sr.session_id = %d AND c.ID = %d', $school_id, $session_id, $class_id)
		);

		return $students;
	}

	public static function fetch_student_attendance( $student_id, $date )
	{
		global $wpdb;
		$student_attendance = $wpdb->get_row(
			$wpdb->prepare('SELECT sa.status, sa.reason FROM ' . WLSM_ATTENDANCE . ' as sa
				JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = sa.student_record_id
				WHERE sa.student_record_id = %d AND sa.attendance_date = %s', $student_id, $date )
		);
		return $student_attendance;
	}

	public static function fetch_class_subjects( $school_id, $class_id)
	{
		global $wpdb;
		$subjects = $wpdb->get_results(
			$wpdb->prepare('SELECT ss.ID, ss.label FROM ' . WLSM_SUBJECTS . ' as ss
				LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = ss.class_school_id
				WHERE cs.school_id = %d AND cs.class_id = %d', $school_id, $class_id)
		);

		return $subjects;
	}

	public static function fetch_class_subject_students( $school_id, $session_id, $class_id, $section_id, $subject_id)
	{
		global $wpdb;
		$subjects = $wpdb->get_results(
			$wpdb->prepare('SELECT sr.ID, sr.name, sr.roll_number FROM ' . WLSM_STUDENTS_SUBJECTS . ' as ss
				LEFT JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON ss.student_id = sr.ID
				LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.default_section_id = sr.section_id
				WHERE cs.school_id = %d AND sr.session_id = %d AND cs.class_id = %d AND cs.default_section_id = %d AND ss.subject_id = %d', $school_id, $session_id, $class_id, $section_id, $subject_id)
		);
		return $subjects;
	}

	public static function fetch_events( $school_id )
	{
		global $wpdb;
		$events = $wpdb->get_results(
			$wpdb->prepare('SELECT e.ID, e.title, e.description, e.image_id, e.event_date, u.display_name FROM ' . WLSM_EVENTS . ' as e
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = e.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = e.added_by
			WHERE e.is_active = 1 AND e.school_id = %d ORDER BY e.ID DESC', $school_id )
		);
		return $events;
	}

	public static function fetch_event( $school_id, $event_id ) {
		global $wpdb;
		$events = $wpdb->get_row(
			$wpdb->prepare('SELECT e.ID, e.title, e.description, e.image_id, e.event_date, u.display_name FROM ' . WLSM_EVENTS . ' as e
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = e.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = e.added_by
			WHERE e.is_active = 1 AND e.school_id = %d AND e.ID = %d', $school_id, $event_id )
		);
		return $events;
	}

	public static function fetch_notices( $school_id )
	{
		global $wpdb;
		$events = $wpdb->get_results(
			$wpdb->prepare('SELECT n.ID, n.title, n.attachment, n.url, n.link_to, n.school_id, n.is_active, n.added_by, n.description, n.notice_data, n.created_at, u.display_name FROM ' . WLSM_NOTICES . ' as n
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = n.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = n.added_by
			WHERE n.is_active = 1 AND n.school_id = %d ORDER BY n.ID DESC', $school_id )
		);
		return $events;
	}

	public static function fetch_notice( $school_id, $id )
	{
		global $wpdb;
		$events = $wpdb->get_row(
			$wpdb->prepare('SELECT n.ID, n.title, n.attachment, n.url, n.link_to, n.school_id, n.is_active, n.added_by, n.description, n.notice_data, n.created_at, u.display_name FROM ' . WLSM_NOTICES . ' as n
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = n.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = n.added_by
			WHERE n.is_active = 1 AND n.school_id = %d AND n.ID = %d ORDER BY n.ID DESC', $school_id, $id )
		);
		return $events;
	}

	public static function get_subject_teachers( $school_id, $subject_id )
	{
		global $wpdb;
		$subject_teachers = $wpdb->get_results(
			$wpdb->prepare('SELECT wa.ID, wa.name FROM ' . WLSM_ADMIN_SUBJECT . ' as was
			LEFT JOIN ' . WLSM_SUBJECTS . ' as s ON s.ID = was.subject_id
			LEFT JOIN ' . WLSM_ADMINS . ' as wa ON wa.ID = was.admin_id
			LEFT JOIN ' . WLSM_STAFF . ' as ws ON ws.ID = wa.staff_id
			WHERE ws.school_id = %d AND was.subject_id = %d', $school_id, $subject_id )
		);
		return $subject_teachers;
	}

	public static function fetch_routines( $school_id )
	{
		global $wpdb;
		$routine = $wpdb->get_results($wpdb->prepare('SELECT c.ID as class_id, c.label as class_label, rt.section_id, se.label as section_label FROM ' . WLSM_ROUTINES . ' as rt
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = rt.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			WHERE cs.school_id = %d GROUP BY rt.section_id ORDER BY c.label', $school_id ));
		return $routine;
	}

	public static function fetch_class_section_routine( $school_id, $class_id, $section_id )
	{
		global $wpdb;
		$routine = $wpdb->get_results($wpdb->prepare('SELECT rt.ID, rt.start_time, rt.end_time, rt.day, rt.room_number, rt.subject_id, rt.section_id, c.ID as class_id, sj.label as subject_label, c.label as class_label, se.label as section_label, rt.admin_id FROM ' . WLSM_ROUTINES . ' as rt
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = rt.section_id
			JOIN ' . WLSM_SUBJECTS . ' as sj ON sj.ID = rt.subject_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT OUTER JOIN ' . WLSM_ADMINS . ' as a ON a.ID = rt.admin_id
			WHERE cs.school_id = %d AND c.ID = %d AND se.ID = %d ORDER BY rt.day', $school_id, $class_id, $section_id ));
		return $routine;
	}

	public static function fetch_time_table_by_section_id($school_id, $section_id) {
		global $wpdb;
		$class_section_routine = $wpdb->get_results(
			$wpdb->prepare('SELECT se.ID, se.label AS section_label, c.label AS class_label, sj.label AS subject_label, rt.room_number, rt.start_time, rt.end_time, rt.day
			FROM ' . WLSM_ROUTINES . ' AS rt
			JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = rt.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.default_section_id = se.ID
			JOIN ' . WLSM_SUBJECTS . ' AS sj ON sj.ID = rt.subject_id
			JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			WHERE cs.school_id = %d AND se.ID = %d',$school_id, $section_id) );
			return $class_section_routine;
	}

	public static function get_time_table($school_id, $id) {
		global $wpdb;
		$class_section_routine = $wpdb->get_row(
			$wpdb->prepare('SELECT rt.ID FROM ' . WLSM_ROUTINES . ' AS rt
			JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = rt.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.default_section_id = se.ID
			JOIN ' . WLSM_SUBJECTS . ' AS sj ON sj.ID = rt.subject_id
			JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			WHERE cs.school_id = %d AND rt.ID = %d',$school_id, $id)
		);
		return $class_section_routine;
	}

	public static function fetch_homeworks( $school_id, $session_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND hwse.section_id = ' . absint( $restrict_to_section ) . ' ORDER BY hw.ID DESC';
		} else {
			$section_where = ' ORDER BY hw.ID DESC';
		}

		global $wpdb;
		$homeworks = $wpdb->get_results('SELECT hw.ID, hw.title, hw.description, c.label as class_label, hw.homework_date, hw.homework_due_date, u.display_name FROM ' . WLSM_HOMEWORK . ' as hw
			LEFT JOIN ' . WLSM_HOMEWORK_SECTION . ' as hwse ON hwse.homework_id = hw.ID
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = hwse.section_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = se.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = hw.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = hw.added_by
			WHERE hw.school_id = ' . absint( $school_id ) . ' AND hw.session_id = ' . absint( $session_id ) . $section_where );

		return $homeworks;
	}

	public static function fetch_homework($school_id, $session_id, $id)
	{
		global $wpdb;
		$homework = $wpdb->get_row($wpdb->prepare('SELECT hw.ID, hw.title, c.ID as class_id, se.ID as section_id, hw.subject as subject_id, c.label as class_label, se.label as section_label, sub.label as subject_label, hw.homework_date, hw.homework_due_date, hw.description, hw.attachments, hw.downloadable, cs.ID as class_school_id FROM ' . WLSM_HOMEWORK . ' as hw
			JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = hw.school_id
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = hw.session_id
			LEFT OUTER JOIN ' . WLSM_HOMEWORK_SECTION . ' as hwse ON hwse.homework_id = hw.ID
			LEFT OUTER JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = hwse.section_id
			LEFT OUTER JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			LEFT OUTER JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT OUTER JOIN ' . WLSM_SUBJECTS . ' as sub ON sub.ID = hw.subject
			WHERE s.ID = %d AND ss.ID = %d AND hw.ID = %d', $school_id, $session_id, $id));
		return $homework;
	}

	public static function fetch_homework_submissions( $school_id, $session_id, $homework_id )
	{
		global $wpdb;
		$homeworks = $wpdb->get_results(
			'SELECT st.name as student_name, st.roll_number, c.label as class_label, hs.created_at, hw.title, hw.description, hs.attachments, hs.description as response FROM ' . WLSM_HOMEWORK_SUBMISSION . ' as hs
			LEFT JOIN ' . WLSM_STUDENT_RECORDS . ' as st ON st.ID = hs.student_id
			LEFT JOIN ' . WLSM_HOMEWORK . ' as hw ON hw.ID = hs.submission_id
			LEFT JOIN ' . WLSM_HOMEWORK_SECTION . ' as hwse ON hwse.ID = hw.ID
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = hwse.section_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.default_section_id = se.ID
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = hw.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = hw.added_by
			WHERE hw.school_id = ' . absint( $school_id ) . ' AND hw.session_id = ' . absint( $session_id ) . ' AND hs.submission_id = ' . absint( $homework_id )
		);

		return $homeworks;
	}

	public static function fetch_submitted_homework( $school_id, $session_id, $homework_id, $student_id )
	{
		global $wpdb;
		$submitted_homework = $wpdb->get_var(
			'SELECT hs.ID FROM ' . WLSM_HOMEWORK_SUBMISSION . ' as hs
			WHERE hs.school_id = ' . absint( $school_id ) . ' AND hs.session_id = ' . absint( $session_id ) . ' AND hs.submission_id = ' . absint( $homework_id ) . ' AND hs.student_id = ' . absint( $student_id )
		);

		return $submitted_homework;
	}

	public static function fetch_submitted_homeworks( $school_id, $session_id, $homework_id, $student_id )
	{
		global $wpdb;
		$submitted_homeworks = $wpdb->get_results(
			'SELECT hs.ID, hs.description, hs.created_at FROM ' . WLSM_HOMEWORK_SUBMISSION . ' as hs
			WHERE hs.school_id = ' . absint( $school_id ) . ' AND hs.session_id = ' . absint( $session_id ) . ' AND hs.submission_id = ' . absint( $homework_id ) . ' AND hs.student_id = ' . absint( $student_id )
		);

		return $submitted_homeworks;
	}

	public static function get_submitted_homework( $school_id, $session_id, $homework_id, $submission_id, $student_id )
	{
		global $wpdb;
		$submitted_homework = $wpdb->get_row(
			'SELECT hs.ID, hs.attachments, hs.description FROM ' . WLSM_HOMEWORK_SUBMISSION . ' as hs
			WHERE hs.school_id = ' . absint( $school_id ) . ' AND hs.session_id = ' . absint( $session_id ) . ' AND hs.submission_id = ' . absint( $homework_id ) . ' AND hs.ID = ' . absint( $submission_id ) . ' AND hs.student_id = ' . absint( $student_id )
		);

		return $submitted_homework;
	}

	public static function get_book_issued( $school_id, $session_id, $id ) {
		global $wpdb;
		$book_issued = $wpdb->get_row( $wpdb->prepare( 'SELECT bki.ID, bk.title, bk.author, bk.subject, bk.rack_number, bk.book_number, bk.isbn_number, bki.quantity as issued_quantity, bki.date_issued, bki.return_date, bki.returned_at FROM ' . WLSM_BOOKS_ISSUED . ' as bki
			JOIN ' . WLSM_BOOKS . ' as bk ON bk.ID = bki.book_id
			JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = bki.student_record_id
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			WHERE cs.school_id = %d AND ss.ID = %d AND bki.ID = %d', $school_id, $session_id, $id ) );
		return $book_issued;
	}

	public static function fetch_study_materials( $school_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND se.ID = ' . absint( $restrict_to_section ) . ' ORDER BY sm.ID DESC';
		} else {
			$section_where = ' ORDER BY sm.ID DESC';
		}

		global $wpdb;
		$homeworks = $wpdb->get_results('SELECT sm.ID, sm.label, sm.attachments, sm.url, sm.downloadable, c.label as class_label, sub.label as subject_label, sm.description, sm.created_at, u.display_name FROM ' . WLSM_STUDY_MATERIALS . ' as sm
			LEFT JOIN ' . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . ' AS cssm ON cssm.study_material_id = sm.ID
			LEFT JOIN ' . WLSM_SUBJECTS . ' AS sub ON sub.ID = cssm.study_material_subject_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = cssm.class_school_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = cssm.study_material_section_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = sm.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = sm.added_by
			WHERE sm.school_id = ' . absint( $school_id ) . $section_where );

		return $homeworks;
	}

	public static function fetch_study_material( $school_id, $id )
	{
		global $wpdb;
		$homeworks = $wpdb->get_row('SELECT sm.ID, sm.label, sm.attachments, sm.url, sm.downloadable, c.label as class_label, sub.label as subject_label, sm.description, sm.created_at, u.display_name, cs.class_id, cssm.study_material_section_id as section_id, cssm.study_material_subject_id as subject_id FROM ' . WLSM_STUDY_MATERIALS . ' as sm
			LEFT JOIN ' . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . ' AS cssm ON cssm.study_material_id = sm.ID
			LEFT JOIN ' . WLSM_SUBJECTS . ' AS sub ON sub.ID = cssm.study_material_subject_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = cssm.class_school_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = cssm.study_material_section_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = sm.school_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = sm.added_by
			WHERE sm.school_id = ' . absint( $school_id ) . ' AND sm.ID = ' . absint( $id ) );

		return $homeworks;
	}

	public static function fetch_staff_routines( $school_id, $user_id )
	{
		global $wpdb;
		$routine = $wpdb->get_results($wpdb->prepare('SELECT rt.ID, rt.start_time, rt.end_time, rt.day, rt.room_number, rt.subject_id, rt.section_id, c.ID as class_id, sj.label as subject_label, c.label as class_label, se.label as section_label, rt.admin_id FROM ' . WLSM_ROUTINES . ' as rt
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = rt.section_id
			JOIN ' . WLSM_SUBJECTS . ' as sj ON sj.ID = rt.subject_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			JOIN ' . WLSM_ADMINS . ' as a ON a.ID = rt.admin_id
			JOIN ' . WLSM_STAFF . ' as sf ON sf.ID = a.staff_id
			WHERE cs.school_id = %d AND sf.user_id = %d', $school_id, $user_id ));
		return $routine;
	}

	public static function fetch_student_leaves( $school_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND se.ID = ' . absint( $restrict_to_section );
		} else {
			$section_where = '';
		}

		global $wpdb;
		$homeworks = $wpdb->get_results('SELECT lv.ID, sr.enrollment_number, sr.name, c.label as class_label, se.label as section_label, lv.description as reason, lv.start_date, lv.end_date, lv.is_approved, lv.student_record_id, lv.admin_id, lv.approved_by, lv.created_at, u.display_name FROM ' . WLSM_LEAVES . ' as lv
			LEFT JOIN ' . WLSM_STUDENT_RECORDS . ' AS sr ON sr.ID = lv.student_record_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.default_section_id = sr.section_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = sr.section_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = lv.added_by
			WHERE lv.student_record_id IS NOT NULL AND lv.school_id = ' . absint( $school_id ) . $section_where );

		return $homeworks;
	}

	public static function fetch_subject_types(){
		global $wpdb;

		$subject_types = $wpdb->get_results('SELECT * FROM ' . WLSM_SUBJECT_TYPES . ' ORDER BY ID ASC');

		return $subject_types;
	}

	public static function fetch_subjects( $school_id, $session_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND se.ID = ' . absint( $restrict_to_section );
		} else {
			$section_where = '';
		}

		global $wpdb;

		$subjects = $wpdb->get_results('SELECT ss.ID, ss.label, ss.code, ss.type, c.label as class_label, COUNT(a.admin_id) as teacher FROM ' . WLSM_SUBJECTS . ' as ss
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = ss.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SECTIONS . ' as se ON se.class_school_id = cs.ID
			LEFT JOIN ' . WLSM_ADMIN_SUBJECT . ' as a ON a.subject_id = ss.ID
			WHERE cs.school_id = ' . absint( $school_id ) . ' AND ss.session_id = ' . absint( $session_id ) . $section_where . ' GROUP BY ss.ID');

		return $subjects;
	}

	public static function fetch_staff_subjects( $user_id )
	{
		global $wpdb;

		$subjects = $wpdb->get_results('SELECT ss.ID, ss.label, ss.code, ss.type, c.label as class_label, COUNT(asj.admin_id) as teacher FROM ' . WLSM_SUBJECTS . ' as ss
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = ss.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SECTIONS . ' as se ON se.class_school_id = cs.ID

			LEFT JOIN ' . WLSM_ADMIN_SUBJECT . ' as asj ON asj.subject_id = ss.ID
			LEFT JOIN ' . WLSM_ADMINS . ' as ad ON ad.ID = asj.admin_id
			LEFT JOIN ' . WLSM_STAFF . ' as sf ON sf.ID = ad.staff_id
			LEFT JOIN ' . WLSM_USERS . ' as u ON u.ID = sf.user_id
			WHERE u.ID = ' . absint( $user_id ) . ' GROUP BY ss.ID');

		return $subjects;
	}

	public static function fetch_subject( $school_id, $subject_id )
	{
		global $wpdb;

		$subjects = $wpdb->get_results('SELECT ss.ID, ss.label, ss.code, ss.type FROM ' . WLSM_SUBJECTS . ' as ss
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = ss.class_school_id
			WHERE cs.school_id = ' . absint( $school_id ) . ' AND ss.ID = ' . absint( $subject_id ) );

		return $subjects;
	}

	public static function get_admin( $admin_id )
	{
		global $wpdb;
		$staff = $wpdb->get_row(
			$wpdb->prepare('SELECT a.ID, a.name, u.user_login as username FROM ' . WLSM_STAFF . ' as sf
				JOIN ' . WLSM_ADMINS . ' as a ON a.staff_id = sf.ID
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = sf.school_id
				JOIN ' . WLSM_USERS . ' as u ON u.ID = sf.user_id
				WHERE a.ID = %d', $admin_id)
		);

		return $staff;
	}

	public static function fetch_students_data( $school_id, $session_id, $restrict_to_section )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND sr.section_id = ' . absint( $restrict_to_section );
		} else {
			$section_where = '';
		}

		global $wpdb;
		$students = $wpdb->get_results(
			'SELECT sr.*, c.label as class_label, se.label as section_label, sr.ID as student_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				WHERE s.ID = ' . absint( $school_id ) . ' AND sr.session_id = ' . absint( $session_id ) . $section_where
		);
		return $students;
	}

	public static function fetch_assigned_class_section_student( $school_id, $session_id, $restrict_to_section, $student_id )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND sr.section_id = ' . absint( $restrict_to_section ) . ' ORDER BY sr.ID DESC';
		} else {
			$section_where = ' ORDER BY sr.ID DESC';
		}

		global $wpdb;
		$students = $wpdb->get_row(
			$wpdb->prepare('SELECT sr.*, c.label as class_label, se.label as section_label, sr.ID as student_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
				WHERE s.ID = %d AND sr.session_id = %d AND sr.ID = %d' . $section_where, $school_id, $session_id, $student_id)
		);
		return $students;
	}

	public static function fetch_inquiries( $school_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND iq.section_id = ' . absint( $restrict_to_section ) . ' ORDER BY iq.ID DESC';
		} else {
			$section_where = ' ORDER BY iq.ID DESC';
		}

		global $wpdb;
		$inquiries = $wpdb->get_results('SELECT iq.ID, iq.name, iq.phone, iq.email, iq.message, iq.note, iq.next_follow_up, iq.is_active, iq.reference, iq.section_id, c.ID as class_id, c.label as class_label, se.label as section_label, iq.created_at FROM ' . WLSM_INQUIRIES . ' as iq
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = iq.class_school_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = iq.section_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = iq.school_id
			WHERE iq.school_id = ' . absint( $school_id ) . $section_where );

		return $inquiries;
	}

	public static function fetch_inquiry( $school_id, $id )
	{
		global $wpdb;
		$inquiry = $wpdb->get_row('SELECT iq.ID, iq.name, iq.phone, iq.email, iq.message, iq.note, iq.next_follow_up, iq.is_active, iq.reference, iq.section_id, c.ID as class_id, c.label as class_label, se.label as section_label, iq.created_at FROM ' . WLSM_INQUIRIES . ' as iq
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = iq.class_school_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.ID = iq.section_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = iq.school_id
			WHERE iq.school_id = ' . absint( $school_id ) . ' AND iq.ID = ' . absint( $id ) );

		return $inquiry;
	}

	public static function fetch_exam_groups( $school_id )
	{
		global $wpdb;
		$inquiries = $wpdb->get_results('SELECT exg.ID, exg.label, exg.is_active FROM ' . WLSM_EXAMS_GROUP . ' as exg
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = exg.school_id
			WHERE exg.school_id = ' . absint( $school_id ));

		return $inquiries;
	}

	public static function fetch_exam_group( $school_id, $id )
	{
		global $wpdb;
		$exam_group = $wpdb->get_row('SELECT exg.ID, exg.label, exg.is_active FROM ' . WLSM_EXAMS_GROUP . ' as exg
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = exg.school_id
			WHERE exg.school_id = ' . absint( $school_id ) . ' AND exg.ID = ' . absint( $id ) );

		return $exam_group;
	}

	public static function fetch_exam_class_subjects( $school_id, $class_id)
	{
		global $wpdb;
		$subjects = $wpdb->get_results(
			$wpdb->prepare('SELECT ss.ID, ss.label, ss.code FROM ' . WLSM_SUBJECTS . ' as ss
				LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = ss.class_school_id
				WHERE cs.school_id = %d AND cs.class_id = %d', $school_id, $class_id)
		);

		return $subjects;
	}

	public static function fetch_exams( $school_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND csex.section_id = ' . absint( $restrict_to_section ) . ' ORDER BY ex.ID DESC';
		} else {
			$section_where = ' ORDER BY ex.ID DESC';
		}

		global $wpdb;
		$exams = $wpdb->get_results('SELECT ex.ID, ex.label, ex.exam_center, ex.grade_criteria, ex.start_date, ex.end_date, ex.enable_room_numbers, ex.results_published, ex.admit_cards_published, ex.time_table_published, ex.is_active, ex.exam_group, ex.psychomotor_analysis, ex.enable_total_marks, ex.results_obtained_marks, ex.psychomotor, ex.teacher_signature, ex.show_in_assessment, ex.show_rank, ex.show_remark, ex.show_eremark, c.ID as class_id, c.label as class_label, ex.created_at FROM ' . WLSM_EXAMS . ' as ex
			LEFT JOIN ' . WLSM_CLASS_SCHOOL_EXAM . ' AS csex ON csex.exam_id = ex.ID
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = csex.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = ex.school_id
			WHERE ex.school_id = ' . absint( $school_id ) . $section_where );

		return $exams;
	}

	public static function fetch_exam_class( $id )
	{
		global $wpdb;
		$exam = $wpdb->get_row('SELECT cs.ID, c.ID as class_id, c.label as class_label FROM ' . WLSM_EXAMS . ' as ex
			LEFT JOIN ' . WLSM_CLASS_SCHOOL_EXAM . ' AS csex ON csex.exam_id = ex.ID
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.ID = csex.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = ex.school_id
			WHERE ex.ID = ' . absint( $id ) );
		return $exam;
	}

	public static function get_students_with_admit_card( $school_id, $exam_id ) {
		global $wpdb;
		$student_ids = $wpdb->get_col('SELECT ac.student_record_id FROM ' . WLSM_ADMIT_CARDS . ' as ac
			LEFT JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = ac.student_record_id
			LEFT JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
			WHERE cs.school_id = ' . absint( $school_id ) . ' AND ac.exam_id = ' . absint( $exam_id ));
		return $student_ids;
	}

	public static function get_class_students( $school_id, $class_id ) {
		global $wpdb;
		$student_ids = $wpdb->get_results('SELECT sr.ID, sr.name, sr.enrollment_number, sr.phone, sr.email, c.label as class_label, se.label as section_label FROM ' . WLSM_STUDENT_RECORDS . ' as sr
			LEFT JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
			WHERE cs.school_id = ' . absint( $school_id ) . ' AND cs.class_id = ' . absint( $class_id ));
		return $student_ids;
	}

	public static function get_students_with_result( $school_id, $exam_id ) {
		global $wpdb;
		$student_ids = $wpdb->get_col('SELECT ac.student_record_id FROM ' . WLSM_EXAM_RESULTS . ' as re
			LEFT JOIN ' . WLSM_ADMIT_CARDS . ' as ac ON ac.ID = re.admit_card_id
			LEFT JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = ac.student_record_id
			LEFT JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			LEFT JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = cs.school_id
			WHERE cs.school_id = ' . absint( $school_id ) . ' AND ac.exam_id = ' . absint( $exam_id ) . ' GROUP BY ac.student_record_id');
		return $student_ids;
	}

	public static function fetch_academic_reports( $school_id, $restrict_to_section = false )
	{
		if ( $restrict_to_section ) {
			$section_where = ' AND se.ID = ' . absint( $restrict_to_section ) . ' GROUP BY ar.ID ORDER BY ar.ID DESC';
		} else {
			$section_where = '  GROUP BY ar.ID ORDER BY ar.ID DESC';
		}

		global $wpdb;
		$academic_reports = $wpdb->get_results('SELECT ar.ID, ar.label, ar.class_id, eg.label as exam_group_label, ar.exams, ar.is_active, c.label as class_label, se.label as section_label, ar.created_at FROM ' . WLSM_ACADEMIC_REPORTS . ' as ar
			LEFT JOIN ' . WLSM_EXAMS_GROUP . ' AS eg ON eg.ID = ar.exam_group
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.class_id = ar.class_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.class_school_id = cs.ID
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = ar.school_id
			WHERE ar.school_id = ' . absint( $school_id ) . $section_where );

		return $academic_reports;
	}

	public static function fetch_academic_report( $school_id, $id )
	{
		global $wpdb;
		$academic_report = $wpdb->get_row('SELECT ar.ID, ar.label, ar.class_id, ar.exam_group, eg.label as exam_group_label, ar.exams, ar.is_active, c.label as class_label, ar.created_at FROM ' . WLSM_ACADEMIC_REPORTS . ' as ar
			LEFT JOIN ' . WLSM_EXAMS_GROUP . ' AS eg ON eg.ID = ar.exam_group
			LEFT JOIN ' . WLSM_CLASS_SCHOOL . ' AS cs ON cs.class_id = ar.class_id
			LEFT JOIN ' . WLSM_SECTIONS . ' AS se ON se.class_school_id = cs.ID
			LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = cs.class_id
			LEFT JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = ar.school_id
			WHERE ar.school_id = ' . absint( $school_id ) . ' AND ar.ID = ' . absint( $id ) );

		return $academic_report;
	}

	public static function fetch_student_types( $school_id ){
		global $wpdb;

		$subject_types = $wpdb->get_results('SELECT * FROM ' . WLSM_STUDENT_TYPE . ' as st WHERE st.school_id = ' . $school_id . '  ORDER BY st.ID ASC');

		return $subject_types;
	}

	public static function fetch_mediums( $school_id ){
		global $wpdb;

		$subject_types = $wpdb->get_results('SELECT * FROM ' . WLSM_MEDIUM . ' as me WHERE me.school_id = ' . $school_id . '  ORDER BY me.ID ASC');

		return $subject_types;
	}

	public static function fetch_fees( $school_id ) 
	{
		global $wpdb;
		$fees = $wpdb->get_results( $wpdb->prepare('SELECT ft.ID, ft.label, ft.amount, ft.period, ft.active_on_dashboard, ft.active_on_admission, ft.class_id, c.label as class_label  FROM ' . WLSM_FEES . ' as ft
		LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = ft.class_id
		WHERE ft.school_id = %d ORDER BY ft.ID DESC', $school_id ) );
		return $fees;
	}

	public static function fetch_concessions( $school_id ) 
	{
		global $wpdb;
		$fees = $wpdb->get_results( $wpdb->prepare('SELECT ct.ID, ct.concession_name, ct.concession_type, ct.percentage_value, ct.fixed_amount, ct.eligibility_criteria, ct.is_active, ct.class_id, c.label as class_label  FROM ' . WLSM_CONCESSION_TYPES . ' as ct
		LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = ct.class_id
		WHERE ct.school_id = %d ORDER BY ct.ID DESC', $school_id ) );
		return $fees;
	}

	public static function fetch_concession_type( $school_id, $id ) 
	{
		global $wpdb;
		$fees = $wpdb->get_row( $wpdb->prepare('SELECT ct.ID, ct.concession_name, ct.concession_type, ct.percentage_value, ct.fixed_amount, ct.eligibility_criteria, ct.is_active, ct.class_id, c.label as class_label  FROM ' . WLSM_CONCESSION_TYPES . ' as ct
		LEFT JOIN ' . WLSM_CLASSES . ' AS c ON c.ID = ct.class_id
		WHERE ct.school_id = %d AND ct.ID = %d ORDER BY ct.ID DESC', $school_id, $id ) );
		return $fees;
	}

	public static function get_student_concessions( $school_id, $session_id ) {
		global $wpdb;
		$student_concessions = $wpdb->get_results( $wpdb->prepare( 
			'SELECT sc.*, sr.name as student_name, sr.admission_number, c.label as class_label, se.label as section_label, ct.concession_name
			FROM ' . WLSM_STUDENT_CONCESSION . ' as sc
			JOIN ' . WLSM_CONCESSION_TYPES . ' as ct ON ct.ID = sc.concession_type_id
			JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = sc.student_record_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			WHERE sc.school_id = %d AND sc.session_id = %d', 
			$school_id, $session_id
		) );
		return $student_concessions;
	}

	public static function fetch_invoices( $school_id, $session_id, $restrict_to_section = false ) {
		if ( $restrict_to_section ) {
			$section_where = ' AND sr.section_id = ' . absint( $restrict_to_section ) . ' ORDER BY i.ID DESC';
		} else {
			$section_where = ' ORDER BY i.ID DESC';
		}

		global $wpdb;
		$invoices = $wpdb->get_results( $wpdb->prepare( 
			'SELECT i.*, sr.name as student_name, sr.father_name, sr.admission_number, sr.phone, c.label as class_label, se.label as section_label
			FROM ' . WLSM_INVOICES . ' as i
			JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = i.student_record_id
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			WHERE cs.school_id = %d AND ss.ID = %d' . $section_where, 
			$school_id, $session_id
		) );
		return $invoices;
	}

	public static function fetch_pending_payments( $school_id, $session_id ) {
		global $wpdb;
		$payments = $wpdb->get_results( $wpdb->prepare( 'SELECT sr.ID as student_record_id, sr.name, sr.admission_number, sr.enrollment_number, sr.phone, sr.father_name, sr.father_phone, p.ID, p.receipt_number, p.amount, p.payment_method, p.transaction_id, p.attachment, p.created_at, p.note, p.invoice_label, p.invoice_payable, p.invoice_id, c.ID as class_id, c.label as class_label, se.ID as section_id, se.label as section_label FROM ' . WLSM_PENDING_PAYMENTS . ' as p
		JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = p.school_id
		JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = p.student_record_id
		JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
		JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		LEFT OUTER JOIN ' . WLSM_INVOICES . ' as i ON i.ID = p.invoice_id
		WHERE p.school_id = %d AND ss.ID = %d', $school_id, $session_id ) );
		return $payments;
	}

	public static function fetch_payments( $school_id, $session_id, $start_date, $end_date ) {

		if ( $start_date && $end_date ) {
			$filter_where = ' AND p.created_at BETWEEN "' . $start_date . '" AND  "' . $end_date .'" ORDER BY p.ID ASC';
		} else {
			$filter_where = ' ORDER BY p.ID DESC';
		}
		
		global $wpdb;
		$payments = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT p.ID, p.receipt_number, p.amount, p.payment_method, p.transaction_id, p.created_at, p.note, p.invoice_label, p.invoice_payable, p.invoice_id, sr.ID as student_id, sr.name, sr.admission_number, sr.enrollment_number, sr.phone, sr.father_name, sr.father_phone, c.ID as class_id, c.label as class_label, se.ID as section_id, se.label as section_label FROM ' . WLSM_PAYMENTS . ' as p
		JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = p.school_id
		JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = p.student_record_id
		JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
		JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		JOIN ' . WLSM_INVOICES . ' as i ON i.ID = p.invoice_id
		WHERE p.school_id = %d AND ss.ID = %d' . $filter_where, $school_id, $session_id ) );
		return $payments;
	}

	public static function fetch_total_payment_amount( $school_id, $session_id, $start_date, $end_date ) {

		if ( $start_date && $end_date ) {
			$filter_where = ' AND p.created_at BETWEEN "' . $start_date . '" AND  "' . $end_date .'" ORDER BY p.ID ASC';
		} else {
			$filter_where = ' ORDER BY p.ID DESC';
		}
		
		global $wpdb;
		$payments = $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(p.amount) as total_amount FROM ' . WLSM_PAYMENTS . ' as p
		JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = p.school_id
		JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = p.student_record_id
		JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
		JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		JOIN ' . WLSM_INVOICES . ' as i ON i.ID = p.invoice_id
		WHERE p.school_id = %d AND ss.ID = %d' . $filter_where, $school_id, $session_id ) );
		return $payments;
	}

	public static function fetch_collect_payment( $school_id, $session_id, $filter ) {
		$where    	= '';
		$class_id   = $filter['class_id'];
		$section_id = $filter['section_id'];
		$student_id = $filter['student_id'];
		$status     = $filter['status'];

		if ( $class_id ) {
			$where .= ' AND cs.class_id = ' . absint( $class_id );
			if ( $section_id ) {
				$where .= ' AND se.ID = ' . absint( $section_id );
			}
			if ( $student_id ) {
				$where .= ' AND sr.ID = ' . absint( $student_id );
			}
			if ( $status ) {
				$where .= ' AND i.status = ' . '"' . esc_sql( $status ) . '"';
			}

			$where .= ' GROUP BY i.ID';
		}

		global $wpdb;
		$invoices = $wpdb->get_results( $wpdb->prepare( 
		'SELECT i.ID, i.label as invoice_title, i.invoice_number, i.date_issued, i.due_date, i.amount, (i.amount) as payable, COALESCE(SUM(p.amount), 0) as paid, ((i.amount) - COALESCE(SUM(p.amount), 0)) as due, i.status, sr.name as student_name, sr.father_name,  sr.phone, sr.admission_number, sr.enrollment_number, c.label as class_label, se.label as section_label FROM ' . WLSM_INVOICES . ' as i
		JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = i.student_record_id
		JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
		JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
		LEFT OUTER JOIN ' . WLSM_PAYMENTS . ' as p ON p.invoice_id = i.ID
		WHERE cs.school_id = %d AND ss.ID = %d' . $where, $school_id, $session_id
		) );
		return $invoices;
	}

	public static function fetch_invoices_report( $school_id, $session_id, $filter ) {
		$where			= '';
		$class_id		= $filter['class_id'];
		$section_id		= $filter['section_id'];
		$payment_method	= $filter['payment_method'];
		$status			= $filter['status'];

		// Build WHERE filters that apply to the main query
		if ( $class_id ) {
			$where .= ' AND cs.class_id = ' . $class_id;
		}
		if ( $section_id ) {
			$where .= ' AND se.ID = ' . $section_id;
		}
		if ( $status ) {
			$where .= " AND i.status = '" . esc_sql( $status ) . "'";
		}

		// Payment method applies inside the payments subquery
		$payment_method_condition = '';
		if ( ! empty( $payment_method ) ) {
			$payment_method_condition = " AND payment_method = '" . esc_sql( $payment_method ) . "'";
		}

		global $wpdb;

		// Aggregate invoices and payments at student level
		$sql = 'SELECT sr.ID as student_id, sr.name as student_name, sr.father_name, sr.phone, sr.admission_number, sr.enrollment_number, c.label as class_label, se.label as section_label,
			COALESCE(SUM(i.amount),0) as payable,
			COALESCE(SUM(p.amount),0) as paid,
			(COALESCE(SUM(i.amount),0) - COALESCE(SUM(p.amount),0)) as due
			FROM ' . WLSM_STUDENT_RECORDS . ' as sr
			JOIN ' . WLSM_INVOICES . ' as i ON sr.ID = i.student_record_id
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT JOIN (SELECT invoice_id, SUM(amount) as amount FROM ' . WLSM_PAYMENTS . ' WHERE 1=1' . $payment_method_condition . ' GROUP BY invoice_id) as p ON p.invoice_id = i.ID
			WHERE cs.school_id = %d AND ss.ID = %d' . $where . ' GROUP BY sr.ID ORDER BY c.label ASC, se.label ASC, sr.name ASC';

		$invoices = $wpdb->get_results( $wpdb->prepare( $sql, $school_id, $session_id ) );
		return $invoices;
	}

	public static function fetch_incomes( $school_id, $start_date, $end_date ) {
		$filter = '';
		if( $start_date && $end_date ) {
			$filter = ' AND im.income_date BETWEEN "' . $start_date . '" AND "'. $end_date . '"';
		}

		global $wpdb;
		$incomes = $wpdb->get_results( $wpdb->prepare( 'SELECT im.ID, im.label, im.invoice_number, im.amount, im.income_date, im.note, im.attachment, im.income_category_id, im.doner_name, im.receiver_signature, ic.label as income_category FROM ' . WLSM_INCOME . ' as im
		LEFT JOIN ' . WLSM_INCOME_CATEGORIES . ' as ic ON ic.ID = im.income_category_id
		WHERE im.school_id = %d'. $filter, $school_id ) );
		return $incomes;
	}

	public static function fetch_expenses( $school_id, $start_date, $end_date ) {
		$filter = '';
		if( $start_date && $end_date ) {
			$filter = ' AND ep.expense_date BETWEEN "' . $start_date . '" AND "'. $end_date . '"';
		}

		global $wpdb;
		$expenses = $wpdb->get_results( $wpdb->prepare( 'SELECT ep.ID, ep.label, ep.invoice_number, ep.amount, ep.expense_date, ep.note, ep.attachment, ep.expense_category_id, ep.supplier_name, ep.receiver_signature, ec.label as expense_category FROM ' . WLSM_EXPENSES . ' as ep
		LEFT JOIN ' . WLSM_EXPENSE_CATEGORIES . ' as ec ON ec.ID = ep.expense_category_id
		WHERE ep.school_id = %d'. $filter, $school_id ) );
		return $expenses;
	}

	public static function fetch_chapters( $school_id ) {
		global $wpdb;
		$chapters = $wpdb->get_results($wpdb->prepare('SELECT cp.ID, cp.title, cp.subject_id, sb.label as subject_label, c.ID as class_id, c.label as class_label, cp.created_at FROM ' . WLSM_CHAPTER . ' as cp
		JOIN ' . WLSM_SUBJECTS . ' as sb ON sb.ID = cp.subject_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cp.class_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.class_id = c.ID
		WHERE cs.school_id = %d ORDER BY cp.ID DESC',$school_id
		));
		return $chapters;
	}

	public static function fetch_lessons( $school_id ) {
		global $wpdb;
		$lessons = $wpdb->get_results($wpdb->prepare('SELECT l.ID, l.title, l.created_at, c.label as class_label, s.label as subject_label, cp.title as chapter FROM ' . WLSM_LECTURE . ' as l
		JOIN ' . WLSM_CHAPTER . ' as cp ON cp.ID = l.chapter_id
		JOIN ' . WLSM_SUBJECTS . ' as s ON s.ID = l.subject_id
		JOIN ' . WLSM_CLASSES . ' as c ON c.ID = l.class_id
		JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.class_id = c.ID
		WHERE cs.school_id = %d ORDER BY l.ID DESC', $school_id
		));
		return $lessons;
	}

	public static function fetch_class_subject_chapters( $class_id, $subject_id) {
		global $wpdb;
		$chapters = $wpdb->get_results($wpdb->prepare('SELECT cp.ID, cp.title FROM ' . WLSM_CHAPTER . ' as cp
		JOIN ' . WLSM_SUBJECTS . ' as sb ON sb.ID = cp.subject_id
		WHERE cp.class_id = %d AND cp.subject_id = %d', $class_id, $subject_id));
		return $chapters;
	}
}