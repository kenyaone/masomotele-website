<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Tickets.php';

class WLSM_Tickets
{
    // Fetch tickets
    public static function fetch_tickets()
    {
        global $wpdb;

		$current_user = WLSM_M_Role::can('view_tickets');
		$school_id = absint( $current_user['school']['id'] );
		$session_id = absint( $current_user['session']['ID'] );

        $page_url = WLSM_M_Staff_Tickets::get_tickets_page_url();
        $query = WLSM_M_Staff_Tickets::fetch_tickets_query($school_id, $session_id);

        // Get current user details
        $is_admin = current_user_can('administrator');
        $current_user = WLSM_M_Role::can('assigned_tickets');
        $show_assigned_only = !$is_admin && !empty($current_user);

        if ($show_assigned_only) {

            $user_role_id = $current_user['school']['role_id'];
            $admin_id = $current_user['school']['admin_id'];
            $staff_id = get_current_user_id();

            // Filter tickets by role or direct assignment
            $query .= $wpdb->prepare(
                " AND t.assigned_to = %d ",
                
                $admin_id
            );
        }

        $query_filter = $query;
        $group_by = WLSM_M_Staff_Tickets::fetch_tickets_query_group_by();
        $query .= $group_by;
        $query_filter .= $group_by;

        // Searching
        $condition = '';
        if (isset($_POST['search']['value'])) {
            $search_value = sanitize_text_field($_POST['search']['value']);
            if ('' !== $search_value) {
                $search_pattern = '%' . $wpdb->esc_like($search_value) . '%';

                $condition .= $wpdb->prepare(
                    ' HAVING (t.title LIKE %s)
                    OR (t.priority LIKE %s)
                    OR (t.status LIKE %s)
                    OR (t.description LIKE %s)',
                    $search_pattern,
                    $search_pattern,
                    $search_pattern,
                    $search_pattern
                );
                $query_filter .= $condition;
            }
        }

        // Ordering
        $columns = array(
            't.ID',
            't.title',
            't.priority',
            't.status',
            't.role_id',
            't.assigned_to',
            't.due_date',
            't.created_at'
        );

        if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
            $order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
            $order_dir = sanitize_text_field($_POST['order']['0']['dir']);
            $query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
        } else {
            $query_filter .= ' ORDER BY t.ID DESC';
        }

        // Limiting
        $limit = '';
        if (-1 != $_POST['length']) {
            $start  = absint($_POST['start']);
            $length = absint($_POST['length']);
            $limit  = ' LIMIT ' . $start . ', ' . $length;
        }

        // Total query
        $rows_query = WLSM_M_Staff_Tickets::fetch_tickets_query_count($school_id, $admin_id);

        // Total rows count
        $total_rows_count = $wpdb->get_var($rows_query);

        // Filtered rows count
        if ($condition) {
            $filter_rows_count = $wpdb->get_var($query . $condition);
        } else {
            $filter_rows_count = $total_rows_count;
        }

        // Filtered limit rows
        $filter_rows_limit = $wpdb->get_results($query_filter . $limit);

        $data = array();
        if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {
				// Get staff name from assigned_to ID
				$staff_name = '';
				if ($row->assigned_to) {
					$staff = WLSM_M_Staff_General::fetch_staff_name($row->assigned_to);
					$staff_name = $staff ? $staff->name : '';
					$staff_id = $staff ? $staff->ID : '';
				}

				$student_name = '';
				$class_label = '';
				if ($row->student_id) {
					$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $row->student_id);
					if ($student) {
						$student_name = $student->student_name;
						$class_label = WLSM_M_Class::get_label_text($student->class_label);
					}
				}

				// Get role name from role_id
				$role_name = '';
				if ($row->role_id) {
					$role = $wpdb->get_row($wpdb->prepare("SELECT name FROM " . WLSM_ROLES . " WHERE ID = %d", $row->role_id));
					$role_name = $role ? $role->name : '';
				}

				// Edit button with permission check
				$edit_button = WLSM_M_Role::can('edit_tickets') ?
					'<a class="btn btn-sm btn-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '">
						<i class="fas fa-edit"></i>
					</a>' :
					'<button class="btn btn-sm btn-secondary" disabled title="' . esc_attr__('No permission to edit', 'school-management') . '">
						<i class="fas fa-edit"></i>
					</button>';

				// Delete button with permission check
				$delete_button = WLSM_M_Role::can('delete_tickets') ?
					'<a class="btn btn-sm btn-danger wlsm-delete-ticket"
						data-nonce="' . esc_attr(wp_create_nonce('delete-ticket-' . $row->ID)) . '"
						data-ticket="' . esc_attr($row->ID) . '"
						href="#"
						data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '"
						data-message-content="' . esc_attr__('This will delete the ticket.', 'school-management') . '"
						data-cancel="' . esc_attr__('Cancel', 'school-management') . '"
						data-submit="' . esc_attr__('Confirm', 'school-management') . '">
						<i class="fas fa-trash"></i>
					</a>' :
					'<button class="btn btn-sm btn-secondary" disabled title="' . esc_attr__('No permission to delete', 'school-management') . '">
						<i class="fas fa-trash"></i>
					</button>';

				$data[] = array(
					esc_html($row->ID),
					esc_html($row->title),
					self::get_priority_badge($row->priority),
					self::get_status_badge($row->status),
					esc_html($student_name),
					esc_html($class_label),
					esc_html(ucwords($role_name)),
					esc_html(ucwords($staff_name)),
					esc_html(WLSM_Config::get_date_text($row->due_date)),
					esc_html(WLSM_Config::get_date_text($row->created_at)),
					'<div class="btn-group">' . $edit_button . '&nbsp;&nbsp;' . $delete_button . '</div>'
				);
			}
        }

        $output = array(
            'draw'            => intval($_POST['draw']),
            'recordsTotal'    => $total_rows_count,
            'recordsFiltered' => $filter_rows_count,
            'data'            => $data,
        );

        echo json_encode($output);
        die();
    }

    // Helper function for priority badge
    private static function get_priority_badge($priority)
    {
        $class = '';
        switch ($priority) {
            case 'low':
                $class = 'badge-info';
                break;
            case 'normal':
                $class = 'badge-success';
                break;
            case 'high':
                $class = 'badge-warning';
                break;
            case 'urgent':
                $class = 'badge-danger';
                break;
        }
        return '<span class="badge badge-lg ' . $class . ' px-3">' . ucfirst($priority) . '</span>';
    }

    // Helper function for status badge
    private static function get_status_badge($status)
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
        return '<span class="badge badge-lg ' . $class . ' px-3">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }

    // Save ticket
    public static function save_ticket()
    {
        $current_user = WLSM_M_Role::can('add_tickets');

        if (!$current_user) {
            die();
        }

        try {
            ob_start();
            global $wpdb;

            $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;

            if ($ticket_id) {
                if (!wp_verify_nonce($_POST['edit-ticket-' . $ticket_id], 'edit-ticket-' . $ticket_id)) {
                    die();
                }
            } else {
                if (!wp_verify_nonce($_POST['add-ticket'], 'add-ticket')) {
                    die();
                }
            }

            // Checks if ticket exists
            if ($ticket_id) {
                $ticket = WLSM_M_Staff_Tickets::fetch_ticket($ticket_id);
                if (!$ticket) {
                    throw new Exception(esc_html__('Ticket not found.', 'school-management'));
                }
            }

            $school_id = $current_user['school']['id'];

            // Parse inputs
            $title          = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $description    = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
            $priority       = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : 'normal';
            $status         = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'open';
            $assigned_to    = isset($_POST['assigned_to']) ? absint($_POST['assigned_to']) : '';
            $due_date       = isset($_POST['due_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['due_date'])) : NULL;
            $class_id       = isset($_POST['class_id']) ? absint($_POST['class_id']) : '';
            $section_id     = isset($_POST['section_id']) ? absint($_POST['section_id']) : '';
            $student_id     = isset($_POST['student_id']) ? absint($_POST['student_id']) : '';
            $role           = isset($_POST['role']) ? absint($_POST['role']) : '';

            // Validation
            $errors = array();

            if (empty($title)) {
                $errors['title'] = esc_html__('Please provide ticket title.', 'school-management');
            }

            if (empty($class_id)) {
                $errors['class_id'] = esc_html__('Please select a class.', 'school-management');
            }

            if (empty($section_id)) {
                $errors['section_id'] = esc_html__('Please select a section.', 'school-management');
            }

            if (empty($student_id)) {
                $errors['student_id'] = esc_html__('Please select a student.', 'school-management');
            }

            // Check priority enum
            if (!in_array($priority, array('low', 'normal', 'high', 'urgent'))) {
                $priority = 'normal';
            }

            // Check status enum
            if (!in_array($status, array('open', 'in_progress', 'resolved', 'closed'))) {
                $status = 'open';
            }

            if (empty($due_date)) {
                $errors['due_date'] = esc_html__('Please specify leave end date.', 'school-management');
            } else {
                $due_date = $due_date->format('Y-m-d');
            }

            if (count($errors) < 1) {
                try {
                    $wpdb->query('BEGIN;');

                    // Ticket data
                    $data = array(
                        'title'       => $title,
                        'description' => $description,
                        'priority'    => $priority,
                        'status'      => $status,
                        'assigned_to' => $assigned_to,
                        'due_date'    => $due_date,
                        'class_id'    => $class_id,
                        'section_id'  => $section_id,
                        'student_id'  => $student_id,
                        'school_id'   => $school_id,
                        'role_id'     => $role
                    );

                    if ($ticket_id) {
                        $data['updated_at'] = current_time('Y-m-d H:i:s');
                        $success            = $wpdb->update(WLSM_TICKETS, $data, array('ID' => $ticket_id));

                        // Add ticket history
                        $changed_by = get_current_user_id();
                        self::add_ticket_history($ticket_id, $status, $description, $changed_by);

                        $message = esc_html__('Ticket updated successfully.', 'school-management');
                        $reset = false;
                    } else {
                        $data['created_at'] = current_time('Y-m-d H:i:s');
                        $data['created_by'] = get_current_user_id();
                        $success = $wpdb->insert(WLSM_TICKETS, $data);

                        if ($success) {
                            $ticket_id = $wpdb->insert_id;
                            $changed_by = get_current_user_id();
                            self::add_ticket_history($ticket_id, $status, $description, $changed_by);
                        }
                        $message = esc_html__('Ticket created successfully.', 'school-management');
                        $reset = true;
                    }

                    $buffer = ob_get_clean();
                    if (!empty($buffer)) {
                        throw new Exception($buffer);
                    }

                    if (false === $success) {
                        throw new Exception($wpdb->last_error);
                    }

                    $wpdb->query('COMMIT;');

                    wp_send_json_success(array('message' => $message, 'reset' => $reset));
                } catch (Exception $exception) {
                    $wpdb->query('ROLLBACK;');
                    wp_send_json_error($exception->getMessage());
                }
            }
            wp_send_json_error($errors);
        } catch (Exception $exception) {
            $buffer = ob_get_clean();
            if (!empty($buffer)) {
                $response = $buffer;
            } else {
                $response = $exception->getMessage();
            }
            wp_send_json_error($response);
        }
    }

    // Delete ticket
    public static function delete_ticket()
    {
        $current_user = WLSM_M_Role::can('delete_tickets');

        if (!$current_user) {
            die();
        }

        WLSM_Helper::check_demo();

        $school_id = $current_user['school']['id'];

        try {
            ob_start();
            global $wpdb;

            $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;

            if (!wp_verify_nonce($_POST['delete-ticket-' . $ticket_id], 'delete-ticket-' . $ticket_id)) {
                die();
            }

            // Check if ticket exists
            $ticket = WLSM_M_Staff_Tickets::get_ticket($ticket_id);

            if (!$ticket) {
                throw new Exception(esc_html__('Ticket not found.', 'school-management'));
            }

            // Verify school
            if ($ticket->school_id !== $school_id) {
                throw new Exception(esc_html__('Invalid ticket.', 'school-management'));
            }

            try {
                $wpdb->query('BEGIN;');

                // Delete ticket
                $success = $wpdb->delete(
                    WLSM_TICKETS,
                    array('ID' => $ticket_id, 'school_id' => $school_id)
                );

                $message = esc_html__('Ticket deleted successfully.', 'school-management');

                $buffer = ob_get_clean();
                if (!empty($buffer)) {
                    throw new Exception($buffer);
                }

                if (false === $success) {
                    throw new Exception($wpdb->last_error);
                }

                $wpdb->query('COMMIT;');

                wp_send_json_success(array('message' => $message));
            } catch (Exception $exception) {
                $wpdb->query('ROLLBACK;');
                wp_send_json_error($exception->getMessage());
            }
        } catch (Exception $exception) {
            $buffer = ob_get_clean();
            if (!empty($buffer)) {
                $response = $buffer;
            } else {
                $response = $exception->getMessage();
            }
            wp_send_json_error($response);
        }
    }

    // Add ticket history
    public static function add_ticket_history($ticket_id, $status, $comment, $changed_by)
    {
        global $wpdb;

        $data = array(
            'ticket_id' => $ticket_id,
            'status' => $status,
            'comment' => $comment,
            'changed_by' => $changed_by,
            'created_at' => current_time('mysql', 1)
        );

        $wpdb->insert(WLSM_TICKET_HISTORY, $data);
    }

    // Get ticket history
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
}
