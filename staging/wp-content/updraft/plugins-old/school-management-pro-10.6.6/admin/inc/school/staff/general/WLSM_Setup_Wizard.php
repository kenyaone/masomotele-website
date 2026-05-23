<?php
defined( 'ABSPATH' ) || die();

// Load required helper classes
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';

class WLSM_Setup_Wizard {

	public static function handle_subjects_step() {
		$current_user = WLSM_M_Role::can( 'manage_school' );
		
		if ( ! $current_user ) {
			die();
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'setup_wizard_step' ) ) {
			die();
		}

		global $wpdb;
		$school_id = $current_user['school']['id'];
		
		$subjects_data = isset( $_POST['subjects'] ) ? $_POST['subjects'] : array();
		
		if ( empty( $subjects_data ) ) {
			wp_send_json_error( __( 'Please add at least one subject.', 'school-management' ) );
		}
		
		try {
			$wpdb->query( 'START TRANSACTION' );
			
			$subjects_created = 0;
			
			foreach ( $subjects_data as $subject_data ) {
				$subject_label = sanitize_text_field( $subject_data['label'] );
				$subject_code = sanitize_text_field( $subject_data['code'] );
				$subject_type = sanitize_text_field( $subject_data['type'] );
				$class_id = absint( $subject_data['class_id'] );
				
				if ( empty( $subject_label ) || empty( $class_id ) ) {
					continue;
				}
				
				// Check if subject already exists for this class
				$existing_subject = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM " . WLSM_SUBJECTS . " WHERE label = %s AND class_id = %d AND school_id = %d",
						$subject_label,
						$class_id,
						$school_id
					)
				);
				
				if ( ! $existing_subject ) {
					$wpdb->insert(
						WLSM_SUBJECTS,
						array(
							'label' => $subject_label,
							'code' => $subject_code,
							'type' => $subject_type,
							'class_id' => $class_id,
							'school_id' => $school_id,
							'is_active' => 1,
						)
					);
					$subjects_created++;
				}
			}
			
			$wpdb->query( 'COMMIT' );
			
			if ( $subjects_created > 0 ) {
				wp_send_json_success( array( 'message' => sprintf( __( '%d subjects added successfully.', 'school-management' ), $subjects_created ) ) );
			} else {
				wp_send_json_success( array( 'message' => __( 'No new subjects were added.', 'school-management' ) ) );
			}
			
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function handle_student_types_step() {
		$current_user = WLSM_M_Role::can( 'manage_school' );
		
		if ( ! $current_user ) {
			die();
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'setup_wizard_step' ) ) {
			die();
		}

		global $wpdb;
		$school_id = $current_user['school']['id'];
		
		$student_types = isset( $_POST['student_types'] ) ? $_POST['student_types'] : array();
		
		if ( empty( $student_types ) ) {
			wp_send_json_error( __( 'Please add at least one student type.', 'school-management' ) );
		}
		
		try {
			$wpdb->query( 'START TRANSACTION' );
			
			$types_created = 0;
			
			foreach ( $student_types as $type_data ) {
				$type_label = sanitize_text_field( $type_data['label'] );
				
				if ( empty( $type_label ) ) {
					continue;
				}
				
				// Check if student type already exists
				$existing_type = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM " . WLSM_STUDENT_TYPE . " WHERE label = %s AND school_id = %d",
						$type_label,
						$school_id
					)
				);
				
				if ( ! $existing_type ) {
					$wpdb->insert(
						WLSM_STUDENT_TYPE,
						array(
							'label' => $type_label,
							'school_id' => $school_id,
							'is_active' => 1,
						)
					);
					$types_created++;
				}
			}
			
			$wpdb->query( 'COMMIT' );
			
			if ( $types_created > 0 ) {
				wp_send_json_success( array( 'message' => sprintf( __( '%d student types added successfully.', 'school-management' ), $types_created ) ) );
			} else {
				wp_send_json_success( array( 'message' => __( 'No new student types were added.', 'school-management' ) ) );
			}
			
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function handle_fee_types_step() {
		$current_user = WLSM_M_Role::can( 'manage_school' );
		
		if ( ! $current_user ) {
			die();
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'setup_wizard_step' ) ) {
			die();
		}

		global $wpdb;
		$school_id = $current_user['school']['id'];
		
		$fee_types = isset( $_POST['fee_types'] ) ? $_POST['fee_types'] : array();
		
		if ( empty( $fee_types ) ) {
			wp_send_json_error( __( 'Please add at least one fee type.', 'school-management' ) );
		}
		
		try {
			$wpdb->query( 'START TRANSACTION' );
			
			$types_created = 0;
			
			foreach ( $fee_types as $type_data ) {
				$type_label = sanitize_text_field( $type_data['label'] );
				$type_amount = floatval( $type_data['amount'] );
				$type_period = isset( $type_data['period'] ) ? sanitize_text_field( $type_data['period'] ) : 'monthly';
				
				if ( empty( $type_label ) ) {
					continue;
				}
				
				// Check if fee type already exists
				$existing_type = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM " . WLSM_FEES . " WHERE label = %s AND school_id = %d",
						$type_label,
						$school_id
					)
				);
				
				if ( ! $existing_type ) {
					$wpdb->insert(
						WLSM_FEES,
						array(
							'label' => $type_label,
							'amount' => $type_amount,
							'period' => $type_period,
							'school_id' => $school_id,
							'is_active' => 1,
						)
					);
					$types_created++;
				}
			}
			
			$wpdb->query( 'COMMIT' );
			
			if ( $types_created > 0 ) {
				wp_send_json_success( array( 'message' => sprintf( __( '%d fee types added successfully.', 'school-management' ), $types_created ) ) );
			} else {
				wp_send_json_success( array( 'message' => __( 'No new fee types were added.', 'school-management' ) ) );
			}
			
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function handle_general_settings_step() {
		$current_user = WLSM_M_Role::can( 'manage_school' );
		
		if ( ! $current_user ) {
			die();
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'setup_wizard_step' ) ) {
			die();
		}

		wp_send_json_success( array( 'message' => __( 'General settings saved successfully.', 'school-management' ) ) );
	}

	public static function handle_registration_settings_step() {
		$current_user = WLSM_M_Role::can( 'manage_school' );
		
		if ( ! $current_user ) {
			die();
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'setup_wizard_step' ) ) {
			die();
		}

		global $wpdb;
		$school_id = $current_user['school']['id'];
		
		try {
			$wpdb->query( 'START TRANSACTION' );
			
			// Basic registration settings
			$registration_form_title = isset( $_POST['registration_form_title'] ) ? sanitize_text_field( $_POST['registration_form_title'] ) : '';
			$registration_admin_email = isset( $_POST['registration_admin_email'] ) ? sanitize_email( $_POST['registration_admin_email'] ) : '';
			$registration_admin_phone = isset( $_POST['registration_admin_phone'] ) ? sanitize_text_field( $_POST['registration_admin_phone'] ) : '';
			$redirect_url = isset( $_POST['redirect_url'] ) ? esc_url_raw( $_POST['redirect_url'] ) : '';
			$registration_success_message = isset( $_POST['registration_success_message'] ) ? sanitize_textarea_field( $_POST['registration_success_message'] ) : '';
			
			// Registration options (checkboxes)
			$registration_login_user = isset( $_POST['registration_login_user'] ) ? 1 : 0;
			$student_aprove = isset( $_POST['student_aprove'] ) ? 1 : 0;
			$registration_create_invoice = isset( $_POST['registration_create_invoice'] ) ? 1 : 0;
			$registration_auto_admission_number = isset( $_POST['registration_auto_admission_number'] ) ? 1 : 0;
			$registration_auto_roll_number = isset( $_POST['registration_auto_roll_number'] ) ? 1 : 0;
			
			// Required information fields (checkboxes)
			$registration_dob = isset( $_POST['registration_dob'] ) ? 1 : 0;
			$registration_religion = isset( $_POST['registration_religion'] ) ? 1 : 0;
			$registration_caste = isset( $_POST['registration_caste'] ) ? 1 : 0;
			$registration_blood_group = isset( $_POST['registration_blood_group'] ) ? 1 : 0;
			$registration_id_number = isset( $_POST['registration_id_number'] ) ? 1 : 0;
			$registration_student_photo = isset( $_POST['registration_student_photo'] ) ? 1 : 0;
			$registration_phone = isset( $_POST['registration_phone'] ) ? 1 : 0;
			$registration_city = isset( $_POST['registration_city'] ) ? 1 : 0;
			$registration_state = isset( $_POST['registration_state'] ) ? 1 : 0;
			$registration_country = isset( $_POST['registration_country'] ) ? 1 : 0;
			$registration_address = isset( $_POST['registration_address'] ) ? 1 : 0;
			$registration_parent_detail = isset( $_POST['registration_parent_detail'] ) ? 1 : 0;
			$registration_parent_login = isset( $_POST['registration_parent_login'] ) ? 1 : 0;
			$registration_student_login = isset( $_POST['registration_student_login'] ) ? 1 : 0;
			$registration_transport = isset( $_POST['registration_transport'] ) ? 1 : 0;
			$registration_fees = isset( $_POST['registration_fees'] ) ? 1 : 0;
			$registration_survey = isset( $_POST['registration_survey'] ) ? 1 : 0;
			
			// Prepare registration settings data
			$registration_settings = array(
				'form_title' => $registration_form_title,
				'admin_email' => $registration_admin_email,
				'admin_phone' => $registration_admin_phone,
				'redirect_url' => $redirect_url,
				'success_message' => $registration_success_message,
				'login_user' => $registration_login_user,
				'student_aprove' => $student_aprove,
				'create_invoice' => $registration_create_invoice,
				'auto_admission_number' => $registration_auto_admission_number,
				'auto_roll_number' => $registration_auto_roll_number,
				'dob' => $registration_dob,
				'religion' => $registration_religion,
				'caste' => $registration_caste,
				'blood_group' => $registration_blood_group,
				'id_number' => $registration_id_number,
				'student_photo' => $registration_student_photo,
				'phone' => $registration_phone,
				'city' => $registration_city,
				'state' => $registration_state,
				'country' => $registration_country,
				'address' => $registration_address,
				'parent_detail' => $registration_parent_detail,
				'parent_login' => $registration_parent_login,
				'student_login' => $registration_student_login,
				'transport' => $registration_transport,
				'fees' => $registration_fees,
				'survey' => $registration_survey,
			);
			
			// Check if registration settings already exist
			$existing_settings = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT ID FROM " . WLSM_SETTINGS . " WHERE school_id = %d AND setting_key = 'registration'",
					$school_id
				)
			);
			
			if ( $existing_settings ) {
				// Update existing settings
				$wpdb->update(
					WLSM_SETTINGS,
					array(
						'setting_value' => serialize( $registration_settings ),
					),
					array(
						'ID' => $existing_settings->ID,
					)
				);
			} else {
				// Insert new settings
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key' => 'registration',
						'setting_value' => serialize( $registration_settings ),
						'school_id' => $school_id,
					)
				);
			}
			
			$wpdb->query( 'COMMIT' );
			
			wp_send_json_success( array( 'message' => __( 'Registration settings saved successfully.', 'school-management' ) ) );
			
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function handle_complete_step() {
		$current_user = WLSM_M_Role::can( 'manage_school' );
		
		if ( ! $current_user ) {
			die();
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'setup_wizard_step' ) ) {
			die();
		}

		wp_send_json_success( array( 'message' => __( 'Setup wizard completed successfully!', 'school-management' ) ) );
	}
}
