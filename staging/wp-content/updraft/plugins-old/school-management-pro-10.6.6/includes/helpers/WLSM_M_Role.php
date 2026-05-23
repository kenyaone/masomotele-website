<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';

class WLSM_M_Role {
	private static $admin    = 'admin';
	private static $employee = 'employee';

	public static function get_user_info($user_id = '') {
		if ($data = wp_cache_get('wlsm_user_info')) {
			return $data;
		}

		global $wpdb;

		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$current_school_id = get_user_meta($user_id, 'wlsm_school_id', true);

		$schools = array();

		$staff_in_school = false;

		$staff = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT sf.role, a.role_id, sf.permissions, a.ID as admin_id, sf.school_id, s.label as school_name, a.section_id, s.is_active FROM ' . WLSM_STAFF . ' as sf
				JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = sf.school_id
				LEFT OUTER JOIN ' . WLSM_ADMINS . ' as a ON a.staff_id = sf.ID
				WHERE sf.user_id = %d',
				$user_id
			)
		);

		if (count($staff)) {
			foreach ($staff as $user) {
				if ($user->school_id === $current_school_id) {
					$staff_in_school = true;

					$school_id   = $user->school_id;
					$role        = $user->role;
					$role_id     = $user->role_id;
					$admin_id    = $user->admin_id;
					$permissions = $user->permissions ? unserialize($user->permissions) : array();
					$school_name = $user->school_name;
					$section_id  = $user->section_id;
					$is_active   = $user->is_active;

					// Fetch permissions from WLSM_ROLES if role_id exists
					if ($role_id) {
						$role_permissions = $wpdb->get_var(
							$wpdb->prepare(
								'SELECT permissions FROM ' . WLSM_ROLES . ' WHERE ID = %d',
								$role_id
							)
						);
						if ($role_permissions) {
							$permissions = unserialize($role_permissions);
						}
					}
				}

				array_push(
					$schools,
					array(
						'id'   => $user->school_id,
						'name' => $user->school_name,
					)
				);
			}
		}

		$data = array(
			'schools_assigned' => $schools,
		);

		if ($staff_in_school) {
			if (self::get_admin_key() == $role) {
				$permissions = array_keys(self::get_permissions());
			}

			$data['current_school'] = array(
				'id'          => $school_id,
				'role'        => $role,
				'role_id'     => $role_id,
				'admin_id'    => $admin_id,
				'permissions' => $permissions,
				'name'        => $school_name,
				'is_active'   => $is_active,
				'section_id'  => $section_id,
			);
		} else {
			$data['current_school'] = false;

			if (1 === count($staff)) {
				update_user_meta($user_id, 'wlsm_school_id', $staff[0]->school_id);
			}
		}

		wp_cache_add('wlsm_user_info', $data);

		return $data;
	}

	// Restrict staff to section.
	public static function restrict_to_section( $current_school ) {
		$role       = $current_school['role'];
		$section_id = $current_school['section_id'];

		$restrict_to_section = false;
		if ( self::get_employee_key() === $role ) {
			$restrict_to_section = $section_id;
		}

		return $restrict_to_section;
	}

	// Get if user is staff.
	public static function get_user_admin( $school_id, $user_id = '' ) {
		global $wpdb;

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$admin = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT a.ID FROM ' . WLSM_ADMINS . ' as a
			JOIN ' . WLSM_STAFF . ' as sf ON sf.ID = a.staff_id
			WHERE sf.school_id = %d AND sf.user_id = %d',
				$school_id,
				$user_id
			)
		);

		return $admin;
	}

	public static function get_roles() {
		return array(
			self::$admin    => esc_html__( 'Admin', 'school-management' ),
			self::$employee => esc_html__( 'Staff', 'school-management' ),
		);
	}

	public static function get_staff_roles( $school_id ) {
		global $wpdb;

		$staff_roles = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT r.ID, r.name FROM ' . WLSM_ROLES . ' as r
		WHERE r.school_id = %d',
				$school_id
			),
			OBJECT_K
		);

		return $staff_roles;
	}

	public static function get_role_text( $role ) {
		if ( array_key_exists( $role, self::get_roles() ) ) {
			return self::get_roles()[ $role ];
		}

		return '';
	}

	public static function get_admin_key() {
		return self::$admin;
	}

	public static function get_employee_key() {
		return self::$employee;
	}

	public static function get_permissions() {
		return array(
			'add_inquiries'               => esc_html__( 'Add Inquiries', 'school-management' ),
			'view_inquiries'              => esc_html__( 'View Inquiries', 'school-management' ),
			'edit_inquiries'              => esc_html__( 'Edit Inquiries', 'school-management' ),
			'delete_inquiries'            => esc_html__( 'Delete Inquiries', 'school-management' ),
			'add_subjects'                => esc_html__( 'Add Subjects', 'school-management' ),
			'view_subjects'               => esc_html__( 'View Subjects', 'school-management' ),
			'edit_subjects'               => esc_html__( 'Edit Subjects', 'school-management' ),
			'delete_subjects'             => esc_html__( 'Delete Subjects', 'school-management' ),
			'add_timetable'               => esc_html__( 'Add Timetable', 'school-management' ),
			'view_timetable'              => esc_html__( 'View Timetable', 'school-management' ),
			'edit_timetable'              => esc_html__( 'Edit Timetable', 'school-management' ),
			'delete_timetable'            => esc_html__( 'Delete Timetable', 'school-management' ),
			'add_transport'               => esc_html__( 'Add Transport', 'school-management' ),
			'view_transport'              => esc_html__( 'View Transport', 'school-management' ),
			'edit_transport'              => esc_html__( 'Edit Transport', 'school-management' ),
			'delete_transport'            => esc_html__( 'Delete Transport', 'school-management' ),
			'add_notices'                 => esc_html__( 'Add Noticeboard', 'school-management' ),
			'view_notices'                => esc_html__( 'View Noticeboard', 'school-management' ),
			'edit_notices'                => esc_html__( 'Edit Noticeboard', 'school-management' ),
			'delete_notices'              => esc_html__( 'Delete Noticeboard', 'school-management' ),
			'add_events'                  => esc_html__( 'Add Events', 'school-management' ),
			'view_events'                 => esc_html__( 'View Events', 'school-management' ),
			'edit_events'                 => esc_html__( 'Edit Events', 'school-management' ),
			'delete_events'               => esc_html__( 'Delete Events', 'school-management' ),
			'add_exams'                   => esc_html__( 'Add Exams', 'school-management' ),
			'view_exams'                  => esc_html__( 'View Exams', 'school-management' ),
			'edit_exams'                  => esc_html__( 'Edit Exams', 'school-management' ),
			'delete_exams'                => esc_html__( 'Delete Exams', 'school-management' ),
			'add_admit_cards'             => esc_html__( 'Add Admit Cards', 'school-management' ),
			'view_admit_cards'            => esc_html__( 'View Admit Cards', 'school-management' ),
			'edit_admit_cards'            => esc_html__( 'Edit Admit Cards', 'school-management' ),
			'delete_admit_cards'          => esc_html__( 'Delete Admit Cards', 'school-management' ),
			'add_exam_results'            => esc_html__( 'Add Exam Results', 'school-management' ),
			'view_exam_results'           => esc_html__( 'View Exam Results', 'school-management' ),
			'edit_exam_results'           => esc_html__( 'Edit Exam Results', 'school-management' ),
			'delete_exam_results'         => esc_html__( 'Delete Exam Results', 'school-management' ),
			'add_expenses'                => esc_html__( 'Add Expenses', 'school-management' ),
			'view_expenses'               => esc_html__( 'View Expenses', 'school-management' ),
			'edit_expenses'               => esc_html__( 'Edit Expenses', 'school-management' ),
			'delete_expenses'             => esc_html__( 'Delete Expenses', 'school-management' ),
			'add_income'                  => esc_html__( 'Add Donation', 'school-management' ),
			'view_income'                 => esc_html__( 'View Donation', 'school-management' ),
			'edit_income'                 => esc_html__( 'Edit Donation', 'school-management' ),
			'delete_income'               => esc_html__( 'Delete Donation', 'school-management' ),
			'add_student_leaves'          => esc_html__( 'Add Student Leaves', 'school-management' ),
			'view_student_leaves'         => esc_html__( 'View Student Leaves', 'school-management' ),
			'edit_student_leaves'         => esc_html__( 'Edit Student Leaves', 'school-management' ),
			'delete_student_leaves'       => esc_html__( 'Delete Student Leaves', 'school-management' ),
			'add_hostel'                  => esc_html__( 'Add Hostel', 'school-management' ),
			'view_hostel'                 => esc_html__( 'View Hostel', 'school-management' ),
			'edit_hostel'                 => esc_html__( 'Edit Hostel', 'school-management' ),
			'delete_hostel'               => esc_html__( 'Delete Hostel', 'school-management' ),
			'add_activities'              => esc_html__( 'Add Activities', 'school-management' ),
			'view_activities'             => esc_html__( 'View Activities', 'school-management' ),
			'edit_activities'             => esc_html__( 'Edit Activities', 'school-management' ),
			'delete_activities'           => esc_html__( 'Delete Activities', 'school-management' ),
			'add_lessons'                 => esc_html__( 'Add Lessons', 'school-management' ),
			'view_lessons'                => esc_html__( 'View Lessons', 'school-management' ),
			'edit_lessons'                => esc_html__( 'Edit Lessons', 'school-management' ),
			'delete_lessons'              => esc_html__( 'Delete Lessons', 'school-management' ),
			'add_tickets'                 => esc_html__( 'Add Tickets', 'school-management' ),
			'view_tickets'                => esc_html__( 'View Tickets', 'school-management' ),
			'edit_tickets'                => esc_html__( 'Edit Tickets', 'school-management' ),
			'delete_tickets'              => esc_html__( 'Delete Tickets', 'school-management' ),
			'manage_admissions'           => esc_html__( 'Add Admissions', 'school-management' ),
			'view_students'               => esc_html__( 'View Students', 'school-management' ),
			'edit_students'               => esc_html__( 'Edit Students', 'school-management' ),
			'delete_students'             => esc_html__( 'Delete Students', 'school-management' ),
			'add_fees'                    => esc_html__( 'Add Fee Types', 'school-management' ),
			'view_fees'                   => esc_html__( 'View Fee Types', 'school-management' ),
			'edit_fees'                   => esc_html__( 'Edit Fee Types', 'school-management' ),
			'delete_fees'                 => esc_html__( 'Delete Fee Types', 'school-management' ),
			'add_concession_types'                    => esc_html__( 'Add Concession Types', 'school-management' ),
			'view_concession_types'                   => esc_html__( 'View Concession Types', 'school-management' ),
			'edit_concession_types'                   => esc_html__( 'Edit Concession Types', 'school-management' ),
			'delete_concession_types'                 => esc_html__( 'Delete Concession Types', 'school-management' ),
			'view_students_concession'                => esc_html__( 'View Students with Concession', 'school-management' ),
			'edit_students_concession'                => esc_html__( 'Edit Students Concession', 'school-management' ),
			'add_live_classes'            => esc_html__( 'Add Live Classes', 'school-management' ),
			'view_live_classes'           => esc_html__( 'View Live Classes', 'school-management' ),
			'edit_live_classes'           => esc_html__( 'Edit Live Classes', 'school-management' ),
			'delete_live_classes'         => esc_html__( 'Delete Live Classes', 'school-management' ),
			'add_invoices'                => esc_html__( 'Add Invoices', 'school-management' ),
			'view_invoices'               => esc_html__( 'View Invoices', 'school-management' ),
			'edit_invoices'               => esc_html__( 'Edit Invoices', 'school-management' ),
			'delete_invoices'             => esc_html__( 'Delete Invoices', 'school-management' ),
			'add_certificates'            => esc_html__( 'Add Certificates', 'school-management' ),
			'view_certificates'           => esc_html__( 'View Certificates', 'school-management' ),
			'edit_certificates'           => esc_html__( 'Edit Certificates', 'school-management' ),
			'delete_certificates'         => esc_html__( 'Delete Certificates', 'school-management' ),
			'add_library'                 => esc_html__( 'Add Books', 'school-management' ),
			'view_library'                => esc_html__( 'View Library', 'school-management' ),
			'edit_library'                => esc_html__( 'Edit Library', 'school-management' ),
			'delete_library'              => esc_html__( 'Delete Library', 'school-management' ),
			'add_attendance'              => esc_html__( 'Add Student Attendance', 'school-management' ),
			'view_attendance'             => esc_html__( 'View Student Attendance', 'school-management' ),
			'edit_attendance'             => esc_html__( 'Edit Student Attendance', 'school-management' ),
			'delete_payments'             => esc_html__( 'Delete Payments', 'school-management' ),
			'stats_payments'              => esc_html__( 'View Stats - Payments', 'school-management' ),
			'stats_amount_fees_structure' => esc_html__( 'View Stats - Pending Amount', 'school-management' ),
			'stats_expense'               => esc_html__( 'View Stats - Expense', 'school-management' ),
			'stats_income'                => esc_html__( 'View Stats - Donation', 'school-management' ),
			'manage_promote'              => esc_html__( 'Manage Student Promotion', 'school-management' ),
			'manage_transfer_student'     => esc_html__( 'Manage Transfer Student', 'school-management' ),
			'manage_roles'                => esc_html__( 'Manage Roles', 'school-management' ),
			'issue_certificates'          => esc_html__( 'Issue Certificates', 'school-management' ),
			'manage_classes'              => esc_html__( 'Manage Classes & Sections', 'school-management' ),
			'delete_sections'             => esc_html__( 'Delete Class Sections', 'school-management' ),
			// 'manage_staff_attendance'     => esc_html__( 'Manage Staff Attendance', 'school-management' ),
			'view_staff_attendance'       => esc_html__( 'View Staff Attendance', 'school-management' ),
			'take_staff_attendance'       => esc_html__( 'Take Staff Attendance', 'school-management' ),
			'view_staff_leaves'           => esc_html__( 'View Staff Leaves', 'school-management' ),
			'edit_staff_leaves'           => esc_html__( 'Add/Edit Staff Leaves', 'school-management' ),
			'delete_staff_leaves'         => esc_html__( 'Delete Staff Leaves', 'school-management' ),
			'view_study_materials'        => esc_html__( 'View Study Materials', 'school-management' ),
			'edit_study_materials'        => esc_html__( 'Add/Edit Study Materials', 'school-management' ),
			'delete_study_materials'      => esc_html__( 'Delete Study Materials', 'school-management' ),
			'view_homework'               => esc_html__( 'View Homework', 'school-management' ),
			'edit_homework'               => esc_html__( 'Add/Edit Homework', 'school-management' ),
			'delete_homework'             => esc_html__( 'Delete Homework', 'school-management' ),
			'issue_books'                 => esc_html__( 'Issue Books', 'school-management' ),
			'issue_library_card'          => esc_html__( 'Issue Library Card', 'school-management' ),
			'send_notifications'          => esc_html__( 'Send Notifications', 'school-management' ),
			'manage_settings'             => esc_html__( 'Manage Settings', 'school-management' ),
			'manage_logs'                 => esc_html__( 'Manage Logs', 'school-management' ),
			'assigned_tickets'            => esc_html__( 'View Only Assigned Tickets', 'school-management' ),
			'manage_admins'               => esc_html__( 'Add/Remove Admins', 'school-management' ),
			'manage_employees'            => esc_html__( 'Add/Remove Staff', 'school-management' ),
			'assigned_class'              => esc_html__( 'Assigned Class(if class is assigned)', 'school-management' ),
			'assigned_subjects'           => esc_html__( 'Manage Assigned Subjects', 'school-management' ),
		);
	}

	public static function get_grouped_permissions() {
		$permissions = self::get_permissions();

		return array(
			esc_html__( 'Inquiries', 'school-management' ) => array(
				'add_inquiries'    => $permissions['add_inquiries'],
				'view_inquiries'   => $permissions['view_inquiries'],
				'edit_inquiries'   => $permissions['edit_inquiries'],
				'delete_inquiries' => $permissions['delete_inquiries'],
			),
			esc_html__( 'Subjects', 'school-management' ) => array(
				'add_subjects'    => $permissions['add_subjects'],
				'view_subjects'   => $permissions['view_subjects'],
				'edit_subjects'   => $permissions['edit_subjects'],
				'delete_subjects' => $permissions['delete_subjects'],
				'assigned_subjects' => $permissions['assigned_subjects'],
			),
			esc_html__( 'Timetable', 'school-management' ) => array(
				'add_timetable'    => $permissions['add_timetable'],
				'view_timetable'   => $permissions['view_timetable'],
				'edit_timetable'   => $permissions['edit_timetable'],
				'delete_timetable' => $permissions['delete_timetable'],
			),
			esc_html__( 'Transport', 'school-management' ) => array(
				'add_transport'    => $permissions['add_transport'],
				'view_transport'   => $permissions['view_transport'],
				'edit_transport'   => $permissions['edit_transport'],
				'delete_transport' => $permissions['delete_transport'],
			),
			esc_html__( 'Noticeboard', 'school-management' ) => array(
				'add_notices'    => $permissions['add_notices'],
				'view_notices'   => $permissions['view_notices'],
				'edit_notices'   => $permissions['edit_notices'],
				'delete_notices' => $permissions['delete_notices'],
			),
			esc_html__( 'Events', 'school-management' ) => array(
				'add_events'    => $permissions['add_events'],
				'view_events'   => $permissions['view_events'],
				'edit_events'   => $permissions['edit_events'],
				'delete_events' => $permissions['delete_events'],
			),
			esc_html__( 'Examinations', 'school-management' ) => array(
				'add_exams'           => $permissions['add_exams'],
				'view_exams'          => $permissions['view_exams'],
				'edit_exams'          => $permissions['edit_exams'],
				'delete_exams'        => $permissions['delete_exams'],
				'add_admit_cards'     => $permissions['add_admit_cards'],
				'view_admit_cards'    => $permissions['view_admit_cards'],
				'edit_admit_cards'    => $permissions['edit_admit_cards'],
				'delete_admit_cards'  => $permissions['delete_admit_cards'],
				'add_exam_results'    => $permissions['add_exam_results'],
				'view_exam_results'   => $permissions['view_exam_results'],
				'edit_exam_results'   => $permissions['edit_exam_results'],
				'delete_exam_results' => $permissions['delete_exam_results'],
			),
			esc_html__( 'Accounting', 'school-management' ) => array(
				'add_expenses'                => $permissions['add_expenses'],
				'view_expenses'               => $permissions['view_expenses'],
				'edit_expenses'               => $permissions['edit_expenses'],
				'delete_expenses'             => $permissions['delete_expenses'],
				'add_income'                  => $permissions['add_income'],
				'view_income'                 => $permissions['view_income'],
				'edit_income'                 => $permissions['edit_income'],
				'delete_income'               => $permissions['delete_income'],
				'add_fees'                    => $permissions['add_fees'],
				'view_fees'                   => $permissions['view_fees'],
				'edit_fees'                   => $permissions['edit_fees'],
				'delete_fees'                 => $permissions['delete_fees'],
				'add_invoices'                => $permissions['add_invoices'],
				'view_invoices'               => $permissions['view_invoices'],
				'edit_invoices'               => $permissions['edit_invoices'],
				'delete_invoices'             => $permissions['delete_invoices'],
				'delete_payments'             => $permissions['delete_payments'],
				'stats_payments'              => $permissions['stats_payments'],
				'stats_amount_fees_structure' => $permissions['stats_amount_fees_structure'],
				'stats_expense'               => $permissions['stats_expense'],
				'stats_income'                => $permissions['stats_income'],
			),
			esc_html__( 'Student Leaves', 'school-management' ) => array(
				'add_student_leaves'    => $permissions['add_student_leaves'],
				'view_student_leaves'   => $permissions['view_student_leaves'],
				'edit_student_leaves'   => $permissions['edit_student_leaves'],
				'delete_student_leaves' => $permissions['delete_student_leaves'],
			),
			esc_html__( 'Hostel', 'school-management' ) => array(
				'add_hostel'    => $permissions['add_hostel'],
				'view_hostel'   => $permissions['view_hostel'],
				'edit_hostel'   => $permissions['edit_hostel'],
				'delete_hostel' => $permissions['delete_hostel'],
			),
			esc_html__( 'Activities', 'school-management' ) => array(
				'add_activities'    => $permissions['add_activities'],
				'view_activities'   => $permissions['view_activities'],
				'edit_activities'   => $permissions['edit_activities'],
				'delete_activities' => $permissions['delete_activities'],
			),
			esc_html__( 'Lessons', 'school-management' ) => array(
				'add_lessons'    => $permissions['add_lessons'],
				'view_lessons'   => $permissions['view_lessons'],
				'edit_lessons'   => $permissions['edit_lessons'],
				'delete_lessons' => $permissions['delete_lessons'],
			),
			esc_html__( 'Tickets', 'school-management' ) => array(
				'add_tickets'      => $permissions['add_tickets'],
				'view_tickets'     => $permissions['view_tickets'],
				'edit_tickets'     => $permissions['edit_tickets'],
				'delete_tickets'   => $permissions['delete_tickets'],
				'assigned_tickets' => $permissions['assigned_tickets'],
			),
			esc_html__( 'Admissions', 'school-management' ) => array(
				'manage_admissions' => $permissions['manage_admissions'],
			),
			esc_html__( 'Students', 'school-management' ) => array(
				'view_students'           => $permissions['view_students'],
				'edit_students'           => $permissions['edit_students'],
				'delete_students'         => $permissions['delete_students'],
				'manage_promote'          => $permissions['manage_promote'],
				'manage_transfer_student' => $permissions['manage_transfer_student'],
			),
			esc_html__( 'Concession Types', 'school-management' ) => array(
				'add_concession_types'     => $permissions['add_concession_types'],
				'view_concession_types'    => $permissions['view_concession_types'],
				'edit_concession_types'    => $permissions['edit_concession_types'],
				'delete_concession_types'  => $permissions['delete_concession_types'],
				'view_students_concession' => $permissions['view_students_concession'],
				'edit_students_concession' => $permissions['edit_students_concession'],
			),
			esc_html__( 'Live Classes', 'school-management' ) => array(
				'add_live_classes'    => $permissions['add_live_classes'],
				'view_live_classes'   => $permissions['view_live_classes'],
				'edit_live_classes'   => $permissions['edit_live_classes'],
				'delete_live_classes' => $permissions['delete_live_classes'],
			),
			esc_html__( 'Certificates', 'school-management' ) => array(
				'add_certificates'    => $permissions['add_certificates'],
				'view_certificates'   => $permissions['view_certificates'],
				'edit_certificates'   => $permissions['edit_certificates'],
				'delete_certificates' => $permissions['delete_certificates'],
				'issue_certificates'  => $permissions['issue_certificates'],
			),
			esc_html__( 'Library', 'school-management' ) => array(
				'add_library'        => $permissions['add_library'],
				'view_library'       => $permissions['view_library'],
				'edit_library'       => $permissions['edit_library'],
				'delete_library'     => $permissions['delete_library'],
				'issue_books'        => $permissions['issue_books'],
				'issue_library_card' => $permissions['issue_library_card'],
			),
			esc_html__( 'Attendance', 'school-management' ) => array(
				'add_attendance'  => $permissions['add_attendance'],
				'view_attendance' => $permissions['view_attendance'],
				'edit_attendance' => $permissions['edit_attendance'],
			),
			esc_html__( 'Roles', 'school-management' ) => array(
				'manage_roles' => $permissions['manage_roles'],
			),
			esc_html__( 'Classes & Sections', 'school-management' ) => array(
				'manage_classes'  => $permissions['manage_classes'],
				'delete_sections' => $permissions['delete_sections'],
				'assigned_class'  => $permissions['assigned_class'],
			),
			esc_html__( 'Staff Attendance', 'school-management' ) => array(
				'view_staff_attendance' => $permissions['view_staff_attendance'],
				'take_staff_attendance' => $permissions['take_staff_attendance'],
			),
			esc_html__( 'Staff Leaves', 'school-management' ) => array(
				'view_staff_leaves'   => $permissions['view_staff_leaves'],
				'edit_staff_leaves'   => $permissions['edit_staff_leaves'],
				'delete_staff_leaves' => $permissions['delete_staff_leaves'],
			),
			esc_html__( 'Study Materials', 'school-management' ) => array(
				'view_study_materials'   => $permissions['view_study_materials'],
				'edit_study_materials'   => $permissions['edit_study_materials'],
				'delete_study_materials' => $permissions['delete_study_materials'],
			),
			esc_html__( 'Homework', 'school-management' ) => array(
				'view_homework'     => $permissions['view_homework'],
				'edit_homework'     => $permissions['edit_homework'],
				'delete_homework'   => $permissions['delete_homework'],

			),
			esc_html__( 'Notifications', 'school-management' ) => array(
				'send_notifications' => $permissions['send_notifications'],
			),
			esc_html__( 'Settings', 'school-management' ) => array(
				'manage_settings' => $permissions['manage_settings'],
			),
			esc_html__( 'Logs', 'school-management' ) => array(
				'manage_logs' => $permissions['manage_logs'],
			),
			esc_html__( 'Admins', 'school-management' ) => array(
				'manage_admins' => $permissions['manage_admins'],
			),
			esc_html__( 'Employees', 'school-management' ) => array(
				'manage_employees' => $permissions['manage_employees'],
			),
		);
	}

	public static function check_permission( $permissions_to_check, $user_permissions ) {
		return ! empty( array_intersect( $permissions_to_check, $user_permissions ) );
	}

	public static function get_role_permissions( $role, $permissions ) {
		$permissions_keys = array_keys( self::get_permissions() );

		if ( self::get_admin_key() == $role ) {
			$permissions = $permissions_keys;
		} else {
			if ( is_serialized( $permissions ) ) {
				$permissions = unserialize( $permissions );
			}
			return array_intersect( $permissions, $permissions_keys );
		}

		return $permissions;
	}

	public static function can( $permission ) {
		$user_info      = self::get_user_info();
		$current_school = $user_info['current_school'];


		if ( ! $current_school ) {
			return false;
		}

		$role = $current_school['role'];
		if ( in_array( $role, array_keys( self::get_roles() ) ) ) {
			$permissions = $current_school['permissions'];
			if ( ! is_array( $permission ) ) {
				$permission = array( $permission );
			}
			if ( self::check_permission( $permission, $permissions ) ) {
				$current_session = WLSM_Config::current_session();
				return array(
					'school'  => $current_school,
					'session' => $current_session,
				);
			}
		}

		return false;
	}

	public static function get_permission_text( $permission ) {
		if ( isset( self::get_permissions()[ $permission ] ) ) {
			return self::get_permissions()[ $permission ];
		}

		return '';
	}

	public static function get_admin_text() {
		return self::get_roles()[ self::$admin ];
	}

	public static function get_employee_text() {
		return self::get_roles()[ self::$employee ];
	}
}
