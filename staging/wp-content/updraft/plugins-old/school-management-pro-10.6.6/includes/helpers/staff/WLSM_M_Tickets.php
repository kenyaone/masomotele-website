<?php
defined('ABSPATH') || die();

class WLSM_M_Staff_Tickets
{
	public static function get_tickets_page_url()
	{
		return admin_url('admin.php?page=' . WLSM_TICKETS);
	}

	public static function fetch_ticket($id)
	{
		global $wpdb;

		if (!$id || !is_numeric($id)) {
			return null;
		}

		$query = $wpdb->prepare(
			"SELECT * FROM " . WLSM_TICKETS . " WHERE ID = %d",
			absint($id)
		);

		return $wpdb->get_row($query);
	}

	public static function fetch_tickets_query($school_id, $session_id)
	{
		$query = "SELECT t.*,
			s.name as student_name,
			s.enrollment_number
		FROM " . WLSM_TICKETS . " as t
		LEFT JOIN " . WLSM_STUDENT_RECORDS . " as s ON s.ID = t.student_id
		WHERE t.school_id = %d
		AND s.session_id = %d ";

		return sprintf($query, $school_id, $session_id);
	}

	public static function fetch_tickets_query_group_by()
	{
		return 'GROUP BY t.ID';
	}

	public static function fetch_tickets_query_count( $school_id, $admin_id = null)
	{
		if ( $admin_id ) {
			$admin_where = ' AND t.assigned_to = ' . absint( $admin_id );
		} else {
			$admin_where = '';
		}

		return "SELECT COUNT(DISTINCT t.ID) FROM " . WLSM_TICKETS . " as t WHERE t.school_id = " . absint( $school_id ) . $admin_where;
	}

	public static function get_ticket($id)
	{
		global $wpdb;

		if (!$id || !is_numeric($id)) {
			return null;
		}

		$query = $wpdb->prepare(
			"SELECT t.* FROM " . WLSM_TICKETS . " as t WHERE t.ID = %d",
			absint($id)
		);

		return $wpdb->get_row($query);
	}

	public static function get_tickets_count($school_id)
	{
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . WLSM_TICKETS . " WHERE school_id = %d",
			$school_id
		));
	}

	public static function get_tickets_stats($school_id)
	{
		global $wpdb;
		$stats = $wpdb->get_results($wpdb->prepare(
			"SELECT status, COUNT(*) as count
             FROM " . WLSM_TICKETS . "
             WHERE school_id = %d
             GROUP BY status",
			$school_id
		), ARRAY_A);

		$counts = array(
			'open' => 0,
			'in_progress' => 0,
			'resolved' => 0,
			'closed' => 0
		);

		foreach ($stats as $stat) {
			$counts[$stat['status']] = $stat['count'];
		}

		return $counts;
	}

	public static function get_ticket_history($ticket_id)
	{
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT th.*, u.display_name as changed_by_name
			FROM " . WLSM_TICKET_HISTORY . " th
			LEFT JOIN " . $wpdb->users . " u ON th.changed_by = u.ID
			WHERE th.ticket_id = %d
			ORDER BY th.created_at DESC",
			$ticket_id
		);

		return $wpdb->get_results($query);
	}

	// Function to get status badge
	public static function get_status_badge($status)
	{
		$class = '';
		switch ($status) {
			case 'open':
				$class = 'badge-info';
				break;
			case 'in_progress':
				$class = 'badge-warning';
				break;
			case 'resolved':
				$class = 'badge-success';
				break;
			case 'closed':
				$class = 'badge-secondary';
				break;
		}
		return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
	}

	public static function get_student_tickets($student_id)
	{
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT t.*, u.display_name as assigned_to_name
			FROM " . WLSM_TICKETS . " as t
			LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
			WHERE t.student_id = %d
			ORDER BY t.created_at DESC",
			$student_id
		);

		return $wpdb->get_results($query);
	}
}
