<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Transport.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Invoice.php';

class WLSM_Transport_Invoice_Manager {
	
	/**
	 * Generate monthly transport invoices (called by existing monthly cron)
	 */
	public static function generate_monthly_transport_invoices() {
		// Add safety check to ensure class dependencies are loaded
		if (!self::check_dependencies()) {
			return;
		}
		
		self::generate_transport_invoices_by_period('monthly');
	}
	
	/**
	 * Generate quarterly transport invoices (called by existing 3-month cron)
	 */
	public static function generate_quarterly_transport_invoices() {
		if (!self::check_dependencies()) {
			return;
		}
		self::generate_transport_invoices_by_period('quarterly');
	}
	
	/**
	 * Generate half-yearly transport invoices (called by existing 6-month cron)
	 */
	public static function generate_half_yearly_transport_invoices() {
		if (!self::check_dependencies()) {
			return;
		}
		self::generate_transport_invoices_by_period('half_yearly');
	}
	
	/**
	 * Generate yearly transport invoices (called by existing yearly cron)
	 */
	public static function generate_yearly_transport_invoices() {
		if (!self::check_dependencies()) {
			return;
		}
		self::generate_transport_invoices_by_period('yearly');
	}
	
	/**
	 * Main function to generate transport invoices by period
	 */
	private static function generate_transport_invoices_by_period($period) {
		global $wpdb;
		
		
		try {
			// First check: Get all students with route_vehicle_id (before any filtering)
			$all_students_with_transport = $wpdb->get_results(
				"SELECT sr.ID, sr.name, sr.route_vehicle_id, sr.is_active 
				 FROM " . WLSM_STUDENT_RECORDS . " sr
				 WHERE sr.route_vehicle_id IS NOT NULL 
				   AND sr.route_vehicle_id > 0"
			);
			
			
			// Get all students with active transport assignments for this period
			$students_with_transport = $wpdb->get_results($wpdb->prepare(
				"SELECT DISTINCT sr.ID as student_id, cs.school_id, sr.session_id, sr.route_vehicle_id, sr.name as student_name,
				        ro.fare, ro.period, ro.name as route_name, v.vehicle_number, s.label as school_name
				 FROM " . WLSM_STUDENT_RECORDS . " sr
				 INNER JOIN " . WLSM_SECTIONS . " se ON se.ID = sr.section_id
				 INNER JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = se.class_school_id
				 INNER JOIN " . WLSM_SCHOOLS . " s ON s.ID = cs.school_id
				 INNER JOIN " . WLSM_ROUTE_VEHICLE . " rv ON rv.ID = sr.route_vehicle_id
				 INNER JOIN " . WLSM_ROUTES . " ro ON ro.ID = rv.route_id
				 INNER JOIN " . WLSM_VEHICLES . " v ON v.ID = rv.vehicle_id
				 WHERE sr.route_vehicle_id IS NOT NULL 
				   AND sr.route_vehicle_id > 0 
				   AND sr.is_active = 1
				   AND ro.period = %s",
				$period
			));
			
			if (empty($students_with_transport)) {
				return;
			}
			
			$generated_count = 0;
			$skipped_count = 0;
			$current_month = date('Y-m');
			
			foreach ($students_with_transport as $student) {
				// Check if invoice for this period already exists this month
				$existing_invoice = $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(*) FROM " . WLSM_INVOICES . " 
					 WHERE student_record_id = %d 
					   AND label LIKE %s
					   AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
					$student->student_id,
					'%Transport Fee%',
					$current_month
				));
				
				// Only create if no invoice exists for this month
				if ($existing_invoice == 0) {
					$invoice_id = self::create_recurring_transport_invoice($student);
					if ($invoice_id) {
						$generated_count++;
						
						// Log the generation
						if (class_exists('WLSM_Log') && method_exists('WLSM_Log', 'add_log')) {
							WLSM_Log::add_log($student->school_id, 'transport_invoice', 'recurring_generated', "Recurring transport invoice generated for student: {$student->student_name}, Invoice ID: {$invoice_id}");
						}
					}
				} else {
					$skipped_count++;
				}
			}
			
			
			// Log batch completion
			if ($generated_count > 0 && class_exists('WLSM_Log') && method_exists('WLSM_Log', 'add_log')) {
				WLSM_Log::add_log(0, 'transport_invoice', 'batch_completed', "Generated {$generated_count} recurring transport invoices for period: {$period}");
			}
			
		} catch (Exception $e) {
			// Log error
			if (class_exists('WLSM_Log') && method_exists('WLSM_Log', 'add_log')) {
				WLSM_Log::add_log(0, 'transport_invoice', 'error', 'Recurring transport invoice generation failed: ' . $e->getMessage());
			}
		}
	}
	
	/**
	 * Create recurring transport invoice
	 */
	private static function create_recurring_transport_invoice($student) {
		global $wpdb;
		
		try {
			$wpdb->query('BEGIN;');
			
			// Generate invoice label with period info
			$invoice_label = sprintf(
				'Transport Fee - %s (%s)',
				$student->route_name,
				ucfirst($student->period)
			);
			
			// Calculate due date based on period
			$due_date = self::calculate_due_date($student->period);
			
			// Create transport fee record
			$transport_fee_data = array(
				'label'             => $invoice_label,
				'amount'            => $student->fare,
				'period'            => $student->period,
				'fee_order'         => 1,
				'student_record_id' => $student->student_id,
				'created_at'        => current_time('Y-m-d H:i:s'),
			);
			
			$fee_success = $wpdb->insert(WLSM_STUDENT_FEES, $transport_fee_data);
			
			if (!$fee_success) {
				throw new Exception('Failed to create transport fee record');
			}
			
			// Get school settings for partial payment
			$settings_general = WLSM_M_Setting::get_settings_general($student->school_id);
			$allow_partial_payment = isset($settings_general['auto_invoice_allow_partial_payment']) ? $settings_general['auto_invoice_allow_partial_payment'] : 1;
			
			// Get invoice number
			$invoice_number = WLSM_M_Invoice::get_invoice_number($student->school_id);
			
			// Create transport invoice
			$transport_invoice_data = array(
				'label'             => $invoice_label,
				'amount'            => $student->fare,
				'invoice_number'    => $invoice_number,
				'date_issued'       => current_time('Y-m-d H:i:s'),
				'due_date'          => $due_date,
				'student_record_id' => $student->student_id,
				'partial_payment'   => $allow_partial_payment,
				'added_by'          => 1, // System generated
				'created_at'        => current_time('Y-m-d H:i:s'),
			);
			
			$invoice_success = $wpdb->insert(WLSM_INVOICES, $transport_invoice_data);
			
			if (!$invoice_success) {
				throw new Exception('Failed to create transport invoice');
			}
			
			$invoice_id = $wpdb->insert_id;
			$wpdb->query('COMMIT;');
			
			return $invoice_id;
			
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK;');
			if (class_exists('WLSM_Log') && method_exists('WLSM_Log', 'add_log')) {
				WLSM_Log::add_log($student->school_id, 'transport_invoice', 'error', 'Failed to create recurring transport invoice for student ' . $student->student_id . ': ' . $e->getMessage());
			}
			return false;
		}
	}
	
	/**
	 * Calculate due date based on transport period
	 */
	private static function calculate_due_date($period) {
		$date = new DateTime(current_time('Y-m-d'));
		
		switch (strtolower($period)) {
			case 'monthly':
				$date->add(new DateInterval('P30D'));
				break;
			case 'quarterly':
				$date->add(new DateInterval('P90D'));
				break;
			case 'half_yearly':
				$date->add(new DateInterval('P180D'));
				break;
			case 'yearly':
				$date->add(new DateInterval('P365D'));
				break;
			default:
				$date->add(new DateInterval('P30D')); // Default 30 days
				break;
		}
		
		return $date->format('Y-m-d');
	}
	
	/**
	 * Manual test function to trigger transport invoice generation (for debugging)
	 */
	public static function test_generate_transport_invoices() {
		error_log("TRANSPORT TEST: Manual test triggered");
		
		// Test all periods
		self::generate_monthly_transport_invoices();
		self::generate_quarterly_transport_invoices();
		self::generate_half_yearly_transport_invoices();
		self::generate_yearly_transport_invoices();
		
		error_log("TRANSPORT TEST: Manual test completed");
		
		return array(
			'status' => 'completed',
			'message' => 'Transport invoice generation test completed. Check error logs for details.',
			'timestamp' => current_time('Y-m-d H:i:s')
		);
	}
	
	/**
	 * Get transport invoice summary for admin dashboard
	 */
	public static function get_transport_invoice_summary($school_id) {
		global $wpdb;
		
		$summary = $wpdb->get_row($wpdb->prepare(
			"SELECT 
				COUNT(DISTINCT sr.ID) as students_with_transport,
				COUNT(i.ID) as total_transport_invoices,
				SUM(CASE WHEN i.status = 'paid' THEN i.amount ELSE 0 END) as paid_amount,
				SUM(CASE WHEN i.status = 'unpaid' THEN i.amount ELSE 0 END) as pending_amount
			 FROM " . WLSM_STUDENT_RECORDS . " sr
			 INNER JOIN " . WLSM_SECTIONS . " se ON se.ID = sr.section_id
			 INNER JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = se.class_school_id
			 LEFT JOIN " . WLSM_INVOICES . " i ON i.student_record_id = sr.ID AND i.label LIKE %s
			 WHERE cs.school_id = %d 
			   AND sr.route_vehicle_id IS NOT NULL 
			   AND sr.route_vehicle_id > 0 
			   AND sr.is_active = 1",
			'%Transport%',
			$school_id
		));
		
		return $summary;
	}
	
	/**
	 * Check if student should receive recurring transport invoices
	 * (Called to validate before generating recurring invoices)
	 */
	public static function should_generate_recurring_invoice($student_id, $period) {
		global $wpdb;
		
		// Check if student still has active transport with this period
		$student_transport = $wpdb->get_row($wpdb->prepare(
			"SELECT sr.ID, ro.period, ro.fare
			 FROM " . WLSM_STUDENT_RECORDS . " sr
			 INNER JOIN " . WLSM_ROUTE_VEHICLE . " rv ON rv.ID = sr.route_vehicle_id
			 INNER JOIN " . WLSM_ROUTES . " ro ON ro.ID = rv.route_id
			 WHERE sr.ID = %d 
			   AND sr.is_active = 1 
			   AND sr.route_vehicle_id IS NOT NULL 
			   AND sr.route_vehicle_id > 0
			   AND ro.period = %s",
			$student_id,
			$period
		));
		
		return !empty($student_transport);
	}
	
	/**
	 * Check if all required dependencies are available
	 */
	private static function check_dependencies() {
		global $wpdb;
		
		// Check if required classes are loaded
		$required_classes = ['WLSM_M_Setting', 'WLSM_M_Invoice'];
		foreach ($required_classes as $class) {
			if (!class_exists($class)) {
				return false;
			}
		}
		
		// Check if required constants are defined
		$required_constants = ['WLSM_STUDENT_RECORDS', 'WLSM_INVOICES', 'WLSM_ROUTES', 'WLSM_ROUTE_VEHICLE', 'WLSM_VEHICLES', 'WLSM_SECTIONS', 'WLSM_CLASS_SCHOOL', 'WLSM_SCHOOLS', 'WLSM_SETTINGS'];
		
		foreach ($required_constants as $constant) {
			if (!defined($constant)) {
				return false;
			}
		}
		
		// Check if required tables exist
		$required_tables = [
			WLSM_STUDENT_RECORDS,
			WLSM_INVOICES,
			WLSM_ROUTES,
			WLSM_ROUTE_VEHICLE,
			WLSM_SETTINGS
		];
		
		foreach ($required_tables as $table) {
			$exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
			if (!$exists) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Force reschedule all transport invoice cron jobs
	 * Call this function to fix broken cron schedules
	 */
	public static function fix_cron_schedules() {
		// Clear existing schedules
		$hooks = ['wlsm_monthly_invoices', 'wlsm_three_month', 'wlsm_six_month', 'wlsm_year'];
		
		foreach ($hooks as $hook) {
			$timestamp = wp_next_scheduled($hook);
			if ($timestamp) {
				wp_unschedule_event($timestamp, $hook);
			}
		}
		
		// Reschedule with proper timing
		if (!wp_next_scheduled('wlsm_monthly_invoices')) {
			$next_month = strtotime('first day of next month 02:00:00');
			wp_schedule_event($next_month, 'monthly', 'wlsm_monthly_invoices');
		}
		
		if (!wp_next_scheduled('wlsm_three_month')) {
			$next_quarter = strtotime('+3 months first day of month 02:15:00');
			wp_schedule_event($next_quarter, '3month', 'wlsm_three_month');
		}
		
		if (!wp_next_scheduled('wlsm_six_month')) {
			$next_half_year = strtotime('+6 months first day of month 02:45:00');
			wp_schedule_event($next_half_year, '6month', 'wlsm_six_month');
		}
		
		if (!wp_next_scheduled('wlsm_year')) {
			$next_year = strtotime('+1 year first day of month 03:00:00');
			wp_schedule_event($next_year, '12month', 'wlsm_year');
		}
		
		return true;
	}
}