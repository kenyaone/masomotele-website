<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Session.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/class-bigbluebutton-api.php';

class WLSM_P_General {
	public static function get_school_classes() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-classes' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id  = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;

			// Registration settings.
			$settings_registration          	= WLSM_M_Setting::get_settings_registration( $school_id );
			$school_registration_dob[]            = $settings_registration['dob'];
			$school_registration_religion[]       = $settings_registration['religion'];
			$school_registration_caste[]          = $settings_registration['caste'];
			$school_registration_blood_group[]    = $settings_registration['blood_group'];
			$school_registration_phone[]          = $settings_registration['phone'];
			$school_registration_city[]           = $settings_registration['city'];
			$school_registration_state[]          = $settings_registration['state'];
			$school_registration_country[]        = $settings_registration['country'];
			$school_registration_transport[]      = $settings_registration['transport'];
			$school_registration_parent_detail[]  = $settings_registration['parent_detail'];
			$school_registration_student_login[]  = $settings_registration['student_login'];
			$school_registration_parent_login[]   = $settings_registration['parent_login'];
			$school_registration_id_number[]      = $settings_registration['id_number'];
			$school_registration_survey[]         = $settings_registration['survey'];
			$school_registration_medium[]         = $settings_registration['medium'];
			$school_registration_dob_in_words[]   = $settings_registration['dob_in_words'];
			$school_registration_mother_tongue[]  = $settings_registration['mother_tongue'];
			$school_registration_birth_place[]    = $settings_registration['birth_place'];
			$school_registration_school_details[] = $settings_registration['school_details'];
			$school_registration_student_type[]   = $settings_registration['student_type'];
			$school_registration_student_photo[]  = $settings_registration['student_photo'];

			$school_registration_pen[]  			 = $settings_registration['pen'];
			$school_registration_apaar[] 	 		 = $settings_registration['apaar'];
			$school_registration_parent_id_number[]  = $settings_registration['parent_id_number'];

			$school_registration_activity[]       = $settings_registration['activity'];
			$school_registration_address[]        = $settings_registration['address'];

			// var_dump($school_registration_school_details); die;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school( $school_id );

			if ( ! $school ) {
				throw new Exception( esc_html__( 'School not found.', 'school-management' ) );
			}

			if ( $session_id ) {
				// Check if session exists.
				$session = WLSM_M_Session::get_session( $session_id );

				if ( ! $session ) {
					throw new Exception( esc_html__( 'Session not found.', 'school-management' ) );
				}
			}

			$classes = WLSM_M_Staff_General::fetch_school_classes( $school_id );

			// Ensure all arrays have the same length
			$length = count($classes);
			$school_registration_dob 			= array_pad($school_registration_dob, $length, null);
			$school_registration_religion 		= array_pad($school_registration_religion, $length, null);
			$school_registration_caste 			= array_pad($school_registration_caste, $length, null);
			$school_registration_blood_group 	= array_pad($school_registration_blood_group, $length, null);
			$school_registration_phone 			= array_pad($school_registration_phone, $length, null);
			$school_registration_city 			= array_pad($school_registration_city, $length, null);
			$school_registration_state 			= array_pad($school_registration_state, $length, null);
			$school_registration_country 		= array_pad($school_registration_country, $length, null);
			$school_registration_transport 		= array_pad($school_registration_transport, $length, null);
			$school_registration_parent_detail 	= array_pad($school_registration_parent_detail, $length, null);
			$school_registration_student_login 	= array_pad($school_registration_student_login, $length, null);
			$school_registration_parent_login 	= array_pad($school_registration_parent_login, $length, null);
			$school_registration_id_number 		= array_pad($school_registration_id_number, $length, null);
			$school_registration_survey 		= array_pad($school_registration_survey, $length, null);
			$school_registration_medium 		= array_pad($school_registration_medium, $length, null);
			$school_registration_dob_in_words 	= array_pad($school_registration_dob_in_words, $length, null);
			$school_registration_mother_tongue 	= array_pad($school_registration_mother_tongue, $length, null);
			$school_registration_birth_place 	= array_pad($school_registration_birth_place, $length, null);
			$school_registration_school_details = array_pad($school_registration_school_details, $length, null);
			$school_registration_student_type 	= array_pad($school_registration_student_type, $length, null);
			$school_registration_activity 		= array_pad($school_registration_activity, $length, null);
			$school_registration_address	 	= array_pad($school_registration_address, $length, null);
			$school_registration_student_photo 	= array_pad($school_registration_student_photo, $length, null);

			$school_registration_pen 				= array_pad($school_registration_pen, $length, null);
			$school_registration_apaar 				= array_pad($school_registration_apaar, $length, null);
			$school_registration_parent_id_number 	= array_pad($school_registration_parent_id_number, $length, null);

			$classes = array_map( function( $class, $school_registration_dob, $school_registration_religion, $school_registration_caste, $school_registration_blood_group, $school_registration_phone, $school_registration_city, $school_registration_state, $school_registration_country, $school_registration_transport,  $school_registration_parent_detail, $school_registration_student_login, $school_registration_parent_login, $school_registration_id_number, $school_registration_survey,  $school_registration_medium , $school_registration_dob_in_words, $school_registration_mother_tongue, $school_registration_birth_place, $school_registration_school_details, $school_registration_student_type, $school_registration_activity, $school_registration_address, $school_registration_student_photo, $school_registration_pen, $school_registration_apaar, $school_registration_parent_id_number ) {
				$class->label = WLSM_M_Class::get_label_text( $class->label );
				return [
					'class'          	=> $class,
					'dob'            	=> $school_registration_dob,
					'religion'       	=> $school_registration_religion,
					'caste'          	=> $school_registration_caste,
					'blood_group'    	=> $school_registration_blood_group,
					'phone'          	=> $school_registration_phone,
					'city'           	=> $school_registration_city,
					'state'          	=> $school_registration_state,
					'country'        	=> $school_registration_country,
					'transport'      	=> $school_registration_transport,
					'parent_detail'  	=> $school_registration_parent_detail,
					'student_login'   	=> $school_registration_student_login,
					'parent_login'   	=> $school_registration_parent_login,
					'id_number'      	=> $school_registration_id_number,
					'survey'         	=> $school_registration_survey,
					'medium'         	=> $school_registration_medium,
					'dob_in_words'   	=> $school_registration_dob_in_words,
					'mother_tongue'  	=> $school_registration_mother_tongue,
					'birth_place'    	=> $school_registration_birth_place,
					'school_details' 	=> $school_registration_school_details,
					'student_type'   	=> $school_registration_student_type,
					'activity'       	=> $school_registration_activity,
					'address'        	=> $school_registration_address,
					'student_photo'  	=> $school_registration_student_photo,
					'pen'  			 	=> $school_registration_pen,
					'apaar'  		 	=> $school_registration_apaar,
					'parent_id_number'  => $school_registration_parent_id_number,
				];
			}, $classes, $school_registration_dob, $school_registration_religion, $school_registration_caste, $school_registration_blood_group , $school_registration_phone, $school_registration_city, $school_registration_state, $school_registration_country, $school_registration_transport, $school_registration_parent_detail, $school_registration_student_login, $school_registration_parent_login, $school_registration_id_number, $school_registration_survey, $school_registration_medium, $school_registration_dob_in_words, $school_registration_mother_tongue, $school_registration_birth_place, $school_registration_school_details, $school_registration_student_type, $school_registration_activity, $school_registration_address, $school_registration_student_photo, $school_registration_pen, $school_registration_apaar, $school_registration_parent_id_number );

			wp_send_json( $classes );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_class_sections() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-class-sections' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$class_id  = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;

			$all_sections = isset( $_POST['all_sections'] ) ? absint( $_POST['all_sections'] ) : 0;

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class( $school_id, $class_id );

			if ( ! $class_school ) {
				throw new Exception( esc_html__( 'Class not found.', 'school-management' ) );
			}

			$class_school_id = $class_school->ID;

			$sections = WLSM_M_Staff_General::fetch_class_sections( $class_school_id );

			if ( $all_sections ) {
				$all_sections = (object) array( 'ID' => '', 'label' => esc_html__( 'All Sections', 'school-management' ) );
				array_unshift( $sections , $all_sections );
			}

			$sections = array_map( function( $section ) {
				$section->label = WLSM_M_Staff_Class::get_section_label_text( $section->label );
				return $section;
			}, $sections );

			wp_send_json( $sections );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_student_types() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-classes' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$class_id  = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;


			$types = WLSM_M_Staff_General::fetch_student_types( $school_id );

			$types = array_map( function( $type ) {
				$type->label = WLSM_M_Staff_Class::get_section_label_text( $type->label );
				return $type;
			}, $types );

			wp_send_json( $types );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_class_fees() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-class-sections' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$class_id  = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;
			$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 1;
			$student_type = isset( $_POST['student_type'] ) ? sanitize_text_field( $_POST['student_type'] ) : '';

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class( $school_id, $class_id );

						// get current session difference in months.
			$session = WLSM_M_Session::fetch_session( $session_id );
			$session_start_date = $session->start_date;
			$session_end_date = $session->end_date;
			$session_start_date = new DateTime($session_start_date);
			$session_end_date = new DateTime($session_end_date);
			$interval = $session_start_date->diff($session_end_date);
			// Calculate total months including years
			$months_in_session = ($interval->y * 12) + $interval->m;

			// If you want to consider partial months (days)
			if ($interval->d > 0) {
				$months_in_session++; // Add one more month if there are remaining days
			}


			if ( ! $class_school ) {
				throw new Exception( esc_html__( 'Class not found.', 'school-management' ) );
			}

			$class_school_id = $class_school->ID;

			$fees = WLSM_M_Staff_General::fetch_fees_by_class_student_type( $school_id, $class_id, $student_type);
			$data = array('fees'=>$fees, 'session_months'=> $months_in_session);
			wp_send_json( $data);
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_class_activity() {
		try {
			ob_start();
			global $wpdb;
			if (!wp_verify_nonce($_POST['nonce'], 'get-class-sections')) {
				die;
			}

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$class_id  = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;

			$all_activity = isset( $_POST['all_activity'] ) ? absint( $_POST['all_activity'] ) : 0;

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

			if (!$class_school) {
				throw new Exception(esc_html__('Class not found.', 'school-management'));
			}

			$activity = WLSM_M_Staff_General::fetch_class_activity($class_id);

			// if ($all_activity) {
			// 	$all_activity = (object) array('ID' => '', 'label' => esc_html__('All Activity', 'school-management'));
			// 	array_unshift($activity, $all_activity);
			// }

			// $activity = array_map(function ($activity) {
			// 	return [ 'activity' => $activity];
			// }, $activity, $activity);

			wp_send_json($activity);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_class_subjects() {
		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$class_id  = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;

			$all_sections = isset( $_POST['all_sections'] ) ? absint( $_POST['all_sections'] ) : 0;

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

			if (!$class_school) {
				throw new Exception(esc_html__('Class not found.', 'school-management'));
			}

			$class_school_id = $class_school->ID;

			$sections = WLSM_M_Staff_General::fetch_class_sections($class_school_id);


			$subjects = WLSM_M_Staff_Class::get_class_subjects($school_id, $class_id);

			$settings_registration                     = WLSM_M_Setting::get_settings_registration($school_id);
			$school_registration_auto_select_subjects = $settings_registration['student_auto_subjects'];

			if ($all_sections) {
				$all_sections = (object) array('ID' => '', 'label' => esc_html__('All Sections', 'school-management'));
				array_unshift($sections, $all_sections);
			}

			$subjects = array_map(function ($subject, $sections) use ($school_registration_auto_select_subjects) {
				return [
					'subject' => $subject,
					'section' => $sections,
					'auto_select_subjects' => $school_registration_auto_select_subjects
				];
			}, $subjects, $sections);

			wp_send_json($subjects);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_subject_chapters() {
		try {
			ob_start();
			global $wpdb;
			if (!wp_verify_nonce($_POST['nonce'], 'get-subject-chapter')) {
				die;
			}


			// $school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$subject_id  = isset( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : 0;

			$chapters = WLSM_M_Staff_Class::get_chapters($subject_id);


			// if ($all_sections) {
			// 	$all_sections = (object) array('ID' => '', 'label' => esc_html__('All Sections', 'school-management'));
			// 	array_unshift($sections, $all_sections);
			// }

			// $subjects = array_map(function ($subject, $sections) {
			// 	return [ 'subject' => $subject, 'section' => $sections ];
			// }, $subjects, $sections);

			wp_send_json($chapters);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_lessons(){
		try {
			ob_start();
			global $wpdb;
			if (!wp_verify_nonce($_POST['nonce'], 'lessons')) {
				die;
			}

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;
			$class_id = isset( $_POST['class_id'] ) ? absint( $_POST['class_id'] ) : 0;
			$subject_id  = isset( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : 0;
			$chapter_id  = isset( $_POST['chapter_id'] ) ? absint( $_POST['chapter_id'] ) : null;

			if ($chapter_id != 0) {

				$lessons = WLSM_M_Staff_Class::get_lessons_wit_chapter_id($class_id, $subject_id, $chapter_id);
			} else {
				$lessons = WLSM_M_Staff_Class::get_lessons($class_id, $subject_id);
			}

		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}

		ob_start();
		$from_front = true;
		require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/partials/lessons.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );

	}

	public static function get_school_routes_vehicles() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-routes-vehicles' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id  = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school( $school_id );

			if ( ! $school ) {
				throw new Exception( esc_html__( 'School not found.', 'school-management' ) );
			}

			$routes_vehicles = WLSM_M_Staff_Transport::fetch_routes_vehicles( $school_id );

			$routes = array();
			foreach ( $routes_vehicles as $route_vehicle ) {
				if ( array_key_exists( $route_vehicle->route_id, $routes ) ) {
					array_push(
						$routes[ $route_vehicle->route_id ]['vehicles'],
						array( 'vehicle_number' => $route_vehicle->vehicle_number, 'ID' => $route_vehicle->ID )
					);
				} else {
					$routes[ $route_vehicle->route_id ] = array(
						'route_name' => $route_vehicle->route_name,
						'vehicles'   => array( array( 'vehicle_number' => $route_vehicle->vehicle_number, 'ID' => $route_vehicle->ID ) )
					);
				}
			}

			ob_start();
			foreach ( $routes as $key => $route ) {
			?>
			<optgroup label="<?php echo esc_attr( $route['route_name'] ); ?>">
				<?php foreach ( $route['vehicles'] as $route_vehicle ) { ?>
				<option value="<?php echo esc_attr( $route_vehicle['ID'] ); ?>">
					<?php echo esc_html( $route_vehicle['vehicle_number'] ); ?>
				</option>
				<?php } ?>
			</optgroup>
			<?php }

			wp_send_json( array( 'html' => ob_get_clean() ) );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array( 'html' => '' ) );
		}
	}

	public static function get_school_exams_time_table() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-exams' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school( $school_id );

			if ( ! $school ) {
				throw new Exception( esc_html__( 'School not found.', 'school-management' ) );
			}

			$exams = WLSM_M_Staff_Examination::get_school_published_exams_time_table( $school_id );

			$exams = array_map( function( $exam ) {
				$exam->label = WLSM_M_Staff_Examination::get_exam_label_text( $exam->exam_title );
				return $exam;
			}, $exams );

			wp_send_json( $exams );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_school_exams_admit_card() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-exams' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school( $school_id );

			if ( ! $school ) {
				throw new Exception( esc_html__( 'School not found.', 'school-management' ) );
			}

			$exams = WLSM_M_Staff_Examination::get_school_published_exams_admit_card( $school_id );

			$exams = array_map( function( $exam ) {
				$exam->label = WLSM_M_Staff_Examination::get_exam_label_text( $exam->exam_title );
				return $exam;
			}, $exams );

			wp_send_json( $exams );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_school_exams_result() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-exams' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school( $school_id );

			if ( ! $school ) {
				throw new Exception( esc_html__( 'School not found.', 'school-management' ) );
			}

			$exams = WLSM_M_Staff_Examination::get_school_published_exams_result( $school_id );

			$exams = array_map( function( $exam ) {
				$exam->label = WLSM_M_Staff_Examination::get_exam_label_text( $exam->exam_title );
				return $exam;
			}, $exams );

			wp_send_json( $exams );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function get_school_certificates() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'get-school-certificates' ) ) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id = isset( $_POST['school_id'] ) ? absint( $_POST['school_id'] ) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school( $school_id );

			if ( ! $school ) {
				throw new Exception( esc_html__( 'School not found.', 'school-management' ) );
			}

			$certificates = WLSM_M_Staff_General::get_school_certificates( $school_id );

			$certificates = array_map( function( $certificate ) {
				$certificate->label = WLSM_M_Staff_Class::get_certificate_label_text( $certificate->label );
				return $certificate;
			}, $certificates );

			wp_send_json( $certificates );
		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json( array() );
		}
	}

	public static function save_account_settings() {
		if ( ! wp_verify_nonce( $_POST['save-account-settings'], 'save-account-settings' ) ) {
			die();
		}

		try {
			ob_start();

			$email            = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
			$password         = isset( $_POST['password'] ) ? $_POST['password'] : '';
			$password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';

			// Student profile fields
			$student_name = isset( $_POST['student_name'] ) ? sanitize_text_field( $_POST['student_name'] ) : '';
			$student_phone = isset( $_POST['student_phone'] ) ? sanitize_text_field( $_POST['student_phone'] ) : '';
			$student_email = isset( $_POST['student_email'] ) ? sanitize_email( $_POST['student_email'] ) : '';
			$student_address = isset( $_POST['student_address'] ) ? sanitize_textarea_field( $_POST['student_address'] ) : '';
			$student_city = isset( $_POST['student_city'] ) ? sanitize_text_field( $_POST['student_city'] ) : '';
			$student_state = isset( $_POST['student_state'] ) ? sanitize_text_field( $_POST['student_state'] ) : '';
			$student_country = isset( $_POST['student_country'] ) ? sanitize_text_field( $_POST['student_country'] ) : '';

			// Parent details
			$father_name = isset( $_POST['father_name'] ) ? sanitize_text_field( $_POST['father_name'] ) : '';
			$father_phone = isset( $_POST['father_phone'] ) ? sanitize_text_field( $_POST['father_phone'] ) : '';
			$father_occupation = isset( $_POST['father_occupation'] ) ? sanitize_text_field( $_POST['father_occupation'] ) : '';
			$mother_name = isset( $_POST['mother_name'] ) ? sanitize_text_field( $_POST['mother_name'] ) : '';
			$mother_phone = isset( $_POST['mother_phone'] ) ? sanitize_text_field( $_POST['mother_phone'] ) : '';
			$mother_occupation = isset( $_POST['mother_occupation'] ) ? sanitize_text_field( $_POST['mother_occupation'] ) : '';

			// Start validation.
			$errors = array();

			if ( empty( $email ) ) {
				$errors['email'] = esc_html__( 'Please provide email address.', 'school-management' );
			}

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$errors['email'] = esc_html__( 'Please provide a valid email.', 'school-management' );
			}

			if ( empty( $password ) ) {
				$errors['password'] = esc_html__( 'Please provide password.', 'school-management' );
			}

			if ( empty( $password_confirm ) ) {
				$errors['password_confirm'] = esc_html__( 'Please confirm password.', 'school-management' );
			}

			if ( $password !== $password_confirm ) {
				$errors['password'] = esc_html__( 'Passwords do not match.', 'school-management' );
			}

			// Validate student profile fields
			if ( ! empty( $student_email ) && ! filter_var( $student_email, FILTER_VALIDATE_EMAIL ) ) {
				$errors['student_email'] = esc_html__( 'Please provide a valid student email.', 'school-management' );
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

		$user = wp_get_current_user();

		if ( count( $errors ) < 1 ) {
			try {
				global $wpdb;

				// Start transaction
				$wpdb->query('START TRANSACTION');

				// Update WordPress user data
				$data = array(
					'ID'         => $user->ID,
					'user_email' => $email,
					'user_pass'  => $password,
				);

				$user_id = wp_update_user( $data );

				if ( is_wp_error( $user_id ) ) {
					throw new Exception( $user_id->get_error_message() );
				}

				// Update student profile if student fields are provided
				if ( ! empty( $student_name ) || ! empty( $student_phone ) || ! empty( $student_email ) ||
					 ! empty( $student_address ) || ! empty( $father_name ) || ! empty( $mother_name ) ) {

					// Get student record for current user
					$student_record = $wpdb->get_row($wpdb->prepare(
						'SELECT ID FROM ' . WLSM_STUDENT_RECORDS . ' WHERE user_id = %d AND is_active = 1',
						$user->ID
					));

					if ( $student_record ) {
						// Prepare student data for update
						$student_data = array();

						if ( ! empty( $student_name ) ) {
							$student_data['name'] = $student_name;
						}
						if ( ! empty( $student_phone ) ) {
							$student_data['phone'] = $student_phone;
						}
						if ( ! empty( $student_email ) ) {
							$student_data['email'] = $student_email;
						}
						if ( ! empty( $student_address ) ) {
							$student_data['address'] = $student_address;
						}
						if ( ! empty( $student_city ) ) {
							$student_data['city'] = $student_city;
						}
						if ( ! empty( $student_state ) ) {
							$student_data['state'] = $student_state;
						}
						if ( ! empty( $student_country ) ) {
							$student_data['country'] = $student_country;
						}
						if ( ! empty( $father_name ) ) {
							$student_data['father_name'] = $father_name;
						}
						if ( ! empty( $father_phone ) ) {
							$student_data['father_phone'] = $father_phone;
						}
						if ( ! empty( $father_occupation ) ) {
							$student_data['father_occupation'] = $father_occupation;
						}
						if ( ! empty( $mother_name ) ) {
							$student_data['mother_name'] = $mother_name;
						}
						if ( ! empty( $mother_phone ) ) {
							$student_data['mother_phone'] = $mother_phone;
						}
						if ( ! empty( $mother_occupation ) ) {
							$student_data['mother_occupation'] = $mother_occupation;
						}

						// Update student record if we have data to update
						if ( ! empty( $student_data ) ) {
							$updated = $wpdb->update(
								WLSM_STUDENT_RECORDS,
								$student_data,
								array( 'ID' => $student_record->ID ),
								array_fill( 0, count( $student_data ), '%s' ),
								array( '%d' )
							);

							if ( $updated === false ) {
								throw new Exception( esc_html__( 'Failed to update student profile.', 'school-management' ) );
							}
						}
					}
				}

				// Commit transaction
				$wpdb->query('COMMIT');

				wp_set_auth_cookie( $user->ID );
				wp_set_current_user( $user->ID );
				do_action('wp_login', $user->user_login, $user );

				$message = esc_html__( 'Account and profile settings updated successfully.', 'school-management' );

				wp_send_json_success( array( 'message' => $message, 'reload' => true ) );
			} catch ( Exception $exception ) {
				$wpdb->query('ROLLBACK');
				wp_send_json_error( $exception->getMessage() );
			}
		}
		wp_send_json_error( $errors );
	}

	public static function student_bbb_join_meeting() {
		if (!is_user_logged_in()) {
			wp_send_json_error(esc_html__('Please log in to access live classes.', 'school-management'));
		}

		try {
			// Add error logging for debugging
			error_log('BigBlueButton join meeting request started');

			$meeting_id = isset($_POST['meeting_id']) ? sanitize_text_field($_POST['meeting_id']) : '';
			$password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
			$recordable = isset($_POST['recordable']) ? absint($_POST['recordable']) : 0;
			$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

			error_log('Meeting ID: ' . $meeting_id);

			if (empty($meeting_id)) {
				throw new Exception(esc_html__('Meeting ID is required.', 'school-management'));
			}

			// Get current user and student record
			$user = wp_get_current_user();
			$student = WLSM_M::get_student($user->ID);

			error_log('User ID: ' . $user->ID);
			error_log('Student found: ' . ($student ? 'yes' : 'no'));

			if (!$student) {
				throw new Exception(esc_html__('Student record not found.', 'school-management'));
			}

			error_log('Student details - School ID: ' . $student->school_id . ', Section ID: ' . $student->section_id . ', Class School ID: ' . $student->class_school_id);

		// Verify the meeting exists and belongs to student's school/section
		global $wpdb;
		$meeting = $wpdb->get_row($wpdb->prepare(
			"SELECT mt.ID, mt.class_type, mt.section_id, mt.class_school_id
			FROM " . WLSM_MEETINGS . " mt
			WHERE mt.meeting_id = %s AND mt.school_id = %d
			AND (mt.section_id = %d OR mt.section_id IS NULL OR mt.class_school_id = %d)",
			$meeting_id,
			$student->school_id,
			$student->section_id,
			$student->class_school_id
		));			if (!$meeting) {
				throw new Exception(esc_html__('Meeting not found or access denied.', 'school-management'));
			}

			if (!wp_verify_nonce($nonce, 'student-bbb-join-' . $meeting->ID)) {
				throw new Exception(esc_html__('Security check failed.', 'school-management'));
			}

			// Ensure this is a BigBlueButton meeting
			if ($meeting->class_type !== 'bbb_class') {
				throw new Exception(esc_html__('Invalid meeting type.', 'school-management'));
			}

			error_log('Meeting verified. Class type: ' . $meeting->class_type);

			// Check if BigBlueButton API class exists
			if (!class_exists('SM_Bigbluebutton_Api')) {
				throw new Exception(esc_html__('BigBlueButton API not available.', 'school-management'));
			}

			$username = $user->display_name;
			error_log('Generating join URL for user: ' . $username);

			$join_url = SM_Bigbluebutton_Api::get_join_meeting_url(
				$username,
				'123', // viewer code for students
				$password,
				$recordable,
				$meeting_id,
				null,
				0 // 0 = viewer mode for students
			);

			error_log('Join URL generated: ' . ($join_url ? 'success' : 'failed'));

			wp_send_json_success(array('url' => $join_url));

		} catch (Exception $exception) {
			error_log('BigBlueButton join error: ' . $exception->getMessage());
			wp_send_json_error($exception->getMessage());
		}
	}
}
