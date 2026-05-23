<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/constants.php';

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Helper.php';

class WLSM_Database
{
	public static function activation()
	{
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->query("ALTER TABLE " . WLSM_USERS . " ENGINE = InnoDB");
		$wpdb->query("ALTER TABLE " . WLSM_POSTS . " ENGINE = InnoDB");

		/* Create schools table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_SCHOOLS . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label varchar(191) DEFAULT NULL,
			phone varchar(255) DEFAULT NULL,
			email varchar(255) DEFAULT NULL,
			address text DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT '1',
			last_enrollment_count bigint(20) NOT NULL DEFAULT '0',
			last_invoice_count bigint(20) NOT NULL DEFAULT '0',
			last_payment_count bigint(20) NOT NULL DEFAULT '0',
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			UNIQUE (label)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add description columns if not exists to schools table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SCHOOLS . "' AND COLUMN_NAME = 'description'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD description text DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SCHOOLS . "' AND COLUMN_NAME = 'registration_number'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD registration_number text DEFAULT NULL");
		}

		/* Add description columns if not exists to schools table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SCHOOLS . "' AND COLUMN_NAME = 'category_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD category_id bigint(20) NOT NULL DEFAULT '0'");
		}

		/* Add last_admission_count, admission_prefix, admission_base, admission_padding columns if not exists to schools table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SCHOOLS . "' AND COLUMN_NAME = 'last_admission_count'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD last_admission_count bigint(20) NOT NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD admission_prefix varchar(15) DEFAULT ''");
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD admission_base int(11) UNSIGNED DEFAULT '0'");
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD admission_padding smallint(4) UNSIGNED DEFAULT '6'");
		}

		/* Create settings table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_SETTINGS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				setting_key varchar(191) DEFAULT NULL,
				setting_value text DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (school_id, setting_key),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create classes table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CLASSES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(191) DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (label)
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create category table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CATEGORY . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label varchar(191) DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			UNIQUE (label)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		// Insert default category if there is no class.
		$category_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_CATEGORY);
		if (!$category_count) {
			self::insert_default_category();
		}

		// Insert default school if there is no school.
		$schools_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_SCHOOLS);
		if (!$schools_count) {
			$default_school_id = self::insert_default_school();
		}

		// Insert default classes if there is no class.
		$classes_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_CLASSES);
		if (!$classes_count) {
			self::insert_default_classes();
		}

		/* Create class_school table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CLASS_SCHOOL . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				class_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				default_section_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (class_id, school_id),
				INDEX (class_id),
				INDEX (school_id),
				FOREIGN KEY (class_id) REFERENCES " . WLSM_CLASSES . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create sessions table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_SESSIONS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(191) DEFAULT NULL,
				start_date date NULL DEFAULT NULL,
				end_date date NULL DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (label, start_date, end_date)
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$session_id     = NULL;
		$sessions_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_SESSIONS);
		if (!$sessions_count) {
			/* Insert Default Session */
			$session_years = 1;
			$current_session_exists = false;
			for ($i = 0; $i <= $session_years; $i++) {
				$current_year = absint(date('Y')) + $i;
				$next_year    = $current_year + 1;
				$start_date   = $current_year . '-4-1';
				$end_date     = $next_year . '-3-31';

				$data = array(
					'label'      => $current_year . '-' . $next_year,
					'start_date' => $start_date,
					'end_date'   => $end_date,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$wpdb->insert(WLSM_SESSIONS, $data);

				if (!$current_session_exists) {
					$session_id = $wpdb->insert_id;

					$current_session_exists = true;
				}
			}
		}

		/* Create inquiries table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_INQUIRIES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(60) DEFAULT NULL,
				phone varchar(40) DEFAULT NULL,
				email varchar(60) DEFAULT NULL,
				message text DEFAULT NULL,
				note text DEFAULT NULL,
				next_follow_up date NULL DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '1',
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (class_school_id),
				INDEX (school_id),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add gdpr_agreed column if not exists to inquiries table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INQUIRIES . "' AND COLUMN_NAME = 'gdpr_agreed'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INQUIRIES . " ADD gdpr_agreed tinyint(1) NOT NULL DEFAULT '0'");
		}

		/* Add section_id column if not exists to inquiries table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INQUIRIES . "' AND COLUMN_NAME = 'section_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INQUIRIES . " ADD section_id tinyint(1) NOT NULL DEFAULT '0'");
		}

		/* Add reference column if not exists to inquiries table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INQUIRIES . "' AND COLUMN_NAME = 'reference'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INQUIRIES . " ADD reference varchar(60) DEFAULT NULL");
		}

		/* Create roles table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ROLES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(60) NOT NULL,
				permissions text NOT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (name, school_id),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create staff table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STAFF . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				role varchar(40) NOT NULL,
				permissions text NOT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				user_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (school_id, user_id),
				INDEX (school_id),
				INDEX (user_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);



		/* Create admins table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ADMINS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(60) DEFAULT NULL,
				gender varchar(10) DEFAULT NULL,
				dob date NULL DEFAULT NULL,
				phone varchar(40) DEFAULT NULL,
				email varchar(60) DEFAULT NULL,
				address text DEFAULT NULL,
				salary decimal(12,2) UNSIGNED DEFAULT NULL,
				designation varchar(80) DEFAULT NULL,
				joining_date date NULL DEFAULT NULL,
				role_id bigint(20) UNSIGNED DEFAULT NULL,
				staff_id bigint(20) UNSIGNED DEFAULT NULL,
				assigned_by_manager tinyint(1) NOT NULL DEFAULT '0',
				is_active tinyint(1) NOT NULL DEFAULT '1',
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (staff_id),
				FOREIGN KEY (role_id) REFERENCES " . WLSM_ROLES . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (staff_id) REFERENCES " . WLSM_STAFF . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ADMINS . "' AND COLUMN_NAME = 'photo_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD photo_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Add note column if not exists to admins table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ADMINS . "' AND COLUMN_NAME = 'note'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD note text DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ADMINS . "' AND COLUMN_NAME = 'qualification'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD qualification text DEFAULT NULL");
		}

		/* Create sections table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_SECTIONS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(191) DEFAULT NULL,
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (label, class_school_id),
				INDEX (class_school_id),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);


		/* Create medium table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_MEDIUM . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(191) DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create student_type table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDENT_TYPE . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label varchar(191) DEFAULT NULL,
			school_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		// Insert default student_type if there is no student_type. english, hindi.
		$student_type_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_STUDENT_TYPE);
		if (!$student_type_count) {
			self::insert_default_student_type();
		}

		// Insert default medium if there is no medium. english, hindi.
		$medium_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_MEDIUM);
		if (!$medium_count) {
			self::insert_default_medium();
		}

		/* Create subject_types table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_SUBJECT_TYPES . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label varchar(191) DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			UNIQUE (label)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		// Insert default subject types if there is no subject type.
		$subject_types_count = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_SUBJECT_TYPES);
		if (!$subject_types_count) {
			self::insert_default_subject_types();
		}

		/* Create student_records table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDENT_RECORDS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				enrollment_number varchar(60) DEFAULT NULL,
				admission_number varchar(60) DEFAULT NULL,
				name varchar(255) DEFAULT NULL,
				gender varchar(10) DEFAULT NULL,
				dob date NULL DEFAULT NULL,
				phone varchar(40) DEFAULT NULL,
				email varchar(60) DEFAULT NULL,
				address text DEFAULT NULL,
				admission_date date NULL DEFAULT NULL,
				religion varchar(40) DEFAULT NULL,
				caste varchar(40) DEFAULT NULL,
				blood_group varchar(5) DEFAULT NULL,
				father_name varchar(60) DEFAULT NULL,
				mother_name varchar(60) DEFAULT NULL,
				father_phone varchar(40) DEFAULT NULL,
				mother_phone varchar(40) DEFAULT NULL,
				father_occupation varchar(60) DEFAULT NULL,
				mother_occupation varchar(60) DEFAULT NULL,
				roll_number varchar(30) DEFAULT NULL,
				photo_id bigint(20) UNSIGNED DEFAULT NULL,
				section_id bigint(20) UNSIGNED DEFAULT NULL,
				session_id bigint(20) UNSIGNED DEFAULT NULL,
				user_id bigint(20) UNSIGNED DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '1',
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (user_id),
				INDEX (section_id),
				INDEX (session_id),
				INDEX (user_id),
				FOREIGN KEY (section_id) REFERENCES " . WLSM_SECTIONS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (session_id) REFERENCES " . WLSM_SESSIONS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (photo_id) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add parent_user_id column if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'parent_user_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD parent_user_id bigint(20) UNSIGNED DEFAULT NULL AFTER user_id");
			$wpdb->query("CREATE INDEX parent_user_id ON " . WLSM_STUDENT_RECORDS . " (parent_user_id)");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD FOREIGN KEY (parent_user_id) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");
		}

		/* Add city, state, country columns if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'city'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD city varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD state varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD country varchar(60) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'medium'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD medium varchar(60) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'category'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD category varchar(60) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'dob_in_words'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD dob_in_words varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD birth_place varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD mother_tongue varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD school_name varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD school_address varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD school_class varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD pass_out_year varchar(200) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'pan'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD pan varchar(200) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'activities'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD activities text DEFAULT NULL");
		}

		/* Add city, state, country columns if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'student_type'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD student_type varchar(60) DEFAULT NULL");
		}

		/* Add id_number, id_proof, parent_id_proof, note columns if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'id_number'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD id_number text DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD id_proof bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD parent_id_proof bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD FOREIGN KEY (id_proof) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD FOREIGN KEY (parent_id_proof) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD note text DEFAULT NULL");
		}

		/* Add gdpr_agreed, from_front columns if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'gdpr_agreed'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD gdpr_agreed tinyint(1) NOT NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD from_front tinyint(1) NOT NULL DEFAULT '0'");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'survey'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD survey varchar(60) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'pen'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD pen varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD apaar varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD father_id_number varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD mother_id_number varchar(60) DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'parent_signature'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD parent_signature bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create promotions table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_PROMOTIONS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				from_student_record bigint(20) UNSIGNED DEFAULT NULL,
				to_student_record bigint(20) UNSIGNED DEFAULT NULL,
				note varchar(255) DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (from_student_record),
				INDEX (to_student_record),
				FOREIGN KEY (from_student_record) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (to_student_record) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create transfers table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_TRANSFERS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				from_student_record bigint(20) UNSIGNED DEFAULT NULL,
				to_student_record bigint(20) UNSIGNED DEFAULT NULL,
				to_school varchar(255) DEFAULT NULL,
				note varchar(255) DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (from_student_record),
				INDEX (to_student_record),
				FOREIGN KEY (from_student_record) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (to_student_record) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create certificates table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CERTIFICATES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(191) DEFAULT NULL,
				fields text DEFAULT NULL,
				image_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (image_id) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_CERTIFICATES . "' AND COLUMN_NAME = 'exam_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_CERTIFICATES . " ADD exam_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create certificate_student table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CERTIFICATE_STUDENT . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				certificate_number varchar(60) DEFAULT NULL,
				date_issued date NULL DEFAULT NULL,
				certificate_id bigint(20) UNSIGNED DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (certificate_id),
				INDEX (student_record_id),
				FOREIGN KEY (certificate_id) REFERENCES " . WLSM_CERTIFICATES . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add last_certificate_count column if not exists to schools table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SCHOOLS . "' AND COLUMN_NAME = 'last_certificate_count'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD last_certificate_count bigint(20) NOT NULL DEFAULT '0'");
		}

		/* Create transfer_certificates table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_TRANSFER_CERTIFICATES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				student_record_id bigint(20) UNSIGNED NOT NULL,
				certificate_id bigint(20) UNSIGNED NOT NULL,
				school_id bigint(20) UNSIGNED NOT NULL,
				session_id bigint(20) UNSIGNED NOT NULL,
				certificate_number varchar(60) DEFAULT NULL,
				student_status tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
				issued_date date NULL DEFAULT NULL,
				remarks text DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (student_record_id),
				INDEX (certificate_id),
				INDEX (school_id),
				INDEX (session_id),
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (certificate_id) REFERENCES " . WLSM_CERTIFICATES . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (session_id) REFERENCES " . WLSM_SESSIONS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create invoices table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_INVOICES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				invoice_number varchar(60) DEFAULT NULL,
				label varchar(255) DEFAULT NULL,
				description varchar(255) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				discount decimal(12,2) UNSIGNED DEFAULT '0.00',
				date_issued date NULL DEFAULT NULL,
				due_date date NULL DEFAULT NULL,
				partial_payment tinyint(1) NOT NULL DEFAULT '0',
				status varchar(15) DEFAULT 'unpaid',
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (student_record_id),
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add show_on_dashboard column if not exists to invoice table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INVOICES . "' AND COLUMN_NAME = 'show_on_dashboard'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD show_on_dashboard tinyint(1) NOT NULL DEFAULT '0'");
		}

		/* Add fee_list columns if not exists to WLSM_INVOICES table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INVOICES . "' AND COLUMN_NAME = 'fee_list'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD fee_list text DEFAULT NULL");
		}

		/* Add invoice_amount_total columns if not exists to WLSM_INVOICES table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INVOICES . "' AND COLUMN_NAME = 'invoice_amount_total'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD invoice_amount_total decimal(12,2) UNSIGNED DEFAULT '0.00'");
		}

		/* Add due_date_amount, due_date_period columns if not exists to WLSM_INVOICES table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INVOICES . "' AND COLUMN_NAME = 'due_date_amount'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD due_date_amount text DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD due_date_period text DEFAULT NULL");
		}

		/* Create payments table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_PAYMENTS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				receipt_number varchar(60) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				payment_method varchar(50) DEFAULT NULL,
				transaction_id varchar(80) DEFAULT NULL,
				note text DEFAULT NULL,
				invoice_label varchar(100) DEFAULT NULL,
				invoice_payable decimal(12,2) UNSIGNED DEFAULT '0.00',
				invoice_id bigint(20) UNSIGNED DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				payment_created_on timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (invoice_id),
				INDEX (student_record_id),
				INDEX (school_id),
				FOREIGN KEY (invoice_id) REFERENCES " . WLSM_INVOICES . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add payment_created_on column if not exists to payments table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_PAYMENTS . "' AND COLUMN_NAME = 'payment_created_on'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD payment_created_on timestamp NULL DEFAULT CURRENT_TIMESTAMP AFTER school_id");
		}

		/* Add attachment column if not exists to payments table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_PAYMENTS . "' AND COLUMN_NAME = 'attachment'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD attachment bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD FOREIGN KEY (attachment) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL");
		}

		/* Add bank name, cheque number, cheque date and authorized by column if not exists to payments table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_PAYMENTS . "' AND COLUMN_NAME = 'bank_name'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD bank_name varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD cheque_number varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD cheque_date date NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD authorized_by varchar(200) DEFAULT NULL");
		}

		/* Create pending_payments table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_PENDING_PAYMENTS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				receipt_number varchar(60) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				payment_method varchar(50) DEFAULT NULL,
				transaction_id varchar(80) DEFAULT NULL,
				attachment bigint(20) UNSIGNED DEFAULT NULL,
				note text DEFAULT NULL,
				invoice_label varchar(100) DEFAULT NULL,
				invoice_payable decimal(12,2) UNSIGNED DEFAULT '0.00',
				invoice_id bigint(20) UNSIGNED DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (invoice_id),
				INDEX (student_record_id),
				INDEX (school_id),
				FOREIGN KEY (attachment) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (invoice_id) REFERENCES " . WLSM_INVOICES . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create expense_categories table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EXPENSE_CATEGORIES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create expenses table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EXPENSES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				invoice_number varchar(80) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				expense_date date NULL DEFAULT NULL,
				note text DEFAULT NULL,
				expense_category_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (expense_category_id),
				INDEX (school_id),
				FOREIGN KEY (expense_category_id) REFERENCES " . WLSM_EXPENSE_CATEGORIES . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXPENSES . "' AND COLUMN_NAME = 'attachment'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXPENSES . " ADD attachment bigint(20) UNSIGNED DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXPENSES . "' AND COLUMN_NAME = 'session_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXPENSES . " ADD session_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXPENSES . "' AND COLUMN_NAME = 'supplier_name'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXPENSES . " ADD supplier_name varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_EXPENSES . " ADD receiver_signature bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create income_categories table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_INCOME_CATEGORIES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create income table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_INCOME . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				invoice_number varchar(80) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				income_date date NULL DEFAULT NULL,
				note text DEFAULT NULL,
				income_category_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (income_category_id),
				INDEX (school_id),
				FOREIGN KEY (income_category_id) REFERENCES " . WLSM_INCOME_CATEGORIES . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INCOME . "' AND COLUMN_NAME = 'attachment'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INCOME . " ADD attachment bigint(20) UNSIGNED DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_INCOME . "' AND COLUMN_NAME = 'doner_name'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_INCOME . " ADD doner_name varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_INCOME . " ADD receiver_signature bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create attendance table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ATTENDANCE . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				attendance_date date NOT NULL,
				status varchar(2) DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (student_record_id),
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ATTENDANCE . "' AND COLUMN_NAME = 'reason'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ATTENDANCE . " ADD reason text DEFAULT NULL");
		}


		/* Add subject_id column if not exists to exams table */
		// $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
		// WHERE CONSTRAINT_NAME ='student_record_id'");
		// if (empty($row)) {
		// 	$wpdb->query("ALTER TABLE " . WLSM_ATTENDANCE . " DROP CONSTRAINT student_record_id");
		// }

		/* Add subject_id column if not exists to exams table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ATTENDANCE . "' AND COLUMN_NAME = 'subject_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ATTENDANCE . " ADD subject_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		// /* Remove UNIQUE attendance_date column if exists to attendance table */
		// $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ATTENDANCE . "' AND COLUMN_NAME = 'attendance_date'");
		// if (!empty($row)) {
		// 	$wpdb->query("ALTER TABLE " . WLSM_ATTENDANCE . " DROP INDEX attendance_date");
		// }

		/* Create staff_attendance table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STAFF_ATTENDANCE . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				attendance_date date NOT NULL,
				status varchar(2) DEFAULT NULL,
				admin_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (attendance_date, admin_id),
				INDEX (admin_id),
				FOREIGN KEY (admin_id) REFERENCES " . WLSM_ADMINS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STAFF_ATTENDANCE . "' AND COLUMN_NAME = 'reason'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STAFF_ATTENDANCE . " ADD reason text DEFAULT NULL");
		}


		/* Create subjects table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_SUBJECTS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				code varchar(40) DEFAULT NULL,
				type varchar(40) DEFAULT NULL,
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (label, class_school_id),
				INDEX (code, class_school_id),
				INDEX (class_school_id),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SUBJECTS . "' AND COLUMN_NAME = 'session_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SUBJECTS . " ADD COLUMN session_id bigint(20) DEFAULT NULL");
		}
		/* Create subjects table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDENTS_SUBJECTS . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			student_id bigint(20) UNSIGNED DEFAULT NULL,
			subject_id bigint(20) DEFAULT NULL,
			PRIMARY KEY (ID)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);


		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENTS_SUBJECTS . "' AND COLUMN_NAME = 'session_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENTS_SUBJECTS . " ADD COLUMN session_id bigint(20) DEFAULT NULL");
		}

		$subject_student = $wpdb->get_var('SELECT COUNT(*) FROM ' . WLSM_STUDENTS_SUBJECTS);
		if (!$subject_student) {
			$default_school_id = self::insert_default_subjects();
		}

		/* Create exams table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EXAMS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(191) DEFAULT NULL,
				exam_center varchar(255) DEFAULT NULL,
				grade_criteria text DEFAULT NULL,
				start_date date NULL DEFAULT NULL,
				end_date date NULL DEFAULT NULL,
				enable_room_numbers tinyint(1) NOT NULL DEFAULT '0',
				results_published tinyint(1) NOT NULL DEFAULT '0',
				admit_cards_published tinyint(1) NOT NULL DEFAULT '0',
				time_table_published tinyint(1) NOT NULL DEFAULT '0',
				is_active tinyint(1) NOT NULL DEFAULT '1',
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add exam_group column if not exists to exams table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'psychomotor_analysis'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD exam_group varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD psychomotor_analysis tinyint(1) NOT NULL DEFAULT '1'");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'psychomotor_analysis'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD exam_group varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD psychomotor_analysis tinyint(1) NOT NULL DEFAULT '1'");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'enable_total_marks'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD enable_total_marks varchar(60) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD results_obtained_marks varchar(60) DEFAULT NULL");
		}

		/* Add psychomotor column if not exists to exams table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'psychomotor'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD psychomotor text DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'teacher_signature'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD teacher_signature text DEFAULT NULL");
		}

		/* Add show_in_assessment column if not exists to exams table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'show_in_assessment'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD show_in_assessment tinyint(1) NOT NULL DEFAULT '1'");
		}

		// exam_group table
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EXAMS_GROUP . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label varchar(191) DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT '1',
			school_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			INDEX (school_id),
			FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

//		Show ranks
		/* Add show_rank, show_remark column if not exists to exams table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAMS . "' AND COLUMN_NAME = 'show_rank'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD show_rank tinyint(1) NOT NULL DEFAULT '1'");
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD show_remark tinyint(1) NOT NULL DEFAULT '1'");
			$wpdb->query("ALTER TABLE " . WLSM_EXAMS . " ADD show_eremark tinyint(1) NOT NULL DEFAULT '1'");
		}

		/* Create class_school_exam table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CLASS_SCHOOL_EXAM . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				exam_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (class_school_id, exam_id),
				INDEX (class_school_id),
				INDEX (exam_id),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (exam_id) REFERENCES " . WLSM_EXAMS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create exam_papers table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EXAM_PAPERS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				subject_label varchar(100) DEFAULT NULL,
				subject_type varchar(40) DEFAULT NULL,
				paper_code varchar(40) DEFAULT NULL,
				paper_date date NULL DEFAULT NULL,
				paper_order smallint(4) UNSIGNED DEFAULT '10',
				start_time time DEFAULT NULL,
				end_time time DEFAULT NULL,
				room_number varchar(40) DEFAULT NULL,
				maximum_marks smallint(4) UNSIGNED DEFAULT NULL,
				exam_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (paper_code, exam_id),
				INDEX (exam_id),
				FOREIGN KEY (exam_id) REFERENCES " . WLSM_EXAMS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAM_PAPERS . "' AND COLUMN_NAME = 'subject_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAM_PAPERS . " ADD subject_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create admit_cards table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ADMIT_CARDS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				roll_number varchar(40) DEFAULT NULL,
				exam_id bigint(20) UNSIGNED DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (exam_id, roll_number),
				UNIQUE (exam_id, student_record_id),
				INDEX (exam_id),
				INDEX (student_record_id),
				FOREIGN KEY (exam_id) REFERENCES " . WLSM_EXAMS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create exam_results table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EXAM_RESULTS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				obtained_marks smallint(4) UNSIGNED DEFAULT NULL,
				exam_paper_id bigint(20) UNSIGNED DEFAULT NULL,
				admit_card_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (exam_paper_id, admit_card_id),
				INDEX (exam_paper_id),
				INDEX (admit_card_id),
				FOREIGN KEY (exam_paper_id) REFERENCES " . WLSM_EXAM_PAPERS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (admit_card_id) REFERENCES " . WLSM_ADMIT_CARDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$wpdb->query("ALTER TABLE " . WLSM_EXAM_RESULTS . " MODIFY obtained_marks text DEFAULT NULL");

		/* Add remark column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAM_RESULTS . "' AND COLUMN_NAME = 'remark'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAM_RESULTS . " ADD remark text DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAM_RESULTS . "' AND COLUMN_NAME = 'answer_key'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAM_RESULTS . " ADD answer_key text DEFAULT NULL");
		}

		/* Add scale column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAM_RESULTS . "' AND COLUMN_NAME = 'scale'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAM_RESULTS . " ADD scale text DEFAULT NULL");
		}

		/* Add teacher_remark, school_remark  column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_EXAM_RESULTS . "' AND COLUMN_NAME = 'teacher_remark'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_EXAM_RESULTS . " ADD teacher_remark text DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_EXAM_RESULTS . " ADD school_remark text DEFAULT NULL");
		}

		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ACADEMIC_REPORTS . " (
			`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`label` VARCHAR(191) NOT NULL,
			`class_id` INT(11) UNSIGNED NOT NULL,
			`exam_group` VARCHAR(191) NOT NULL,
			`exams` TEXT NOT NULL,
			`is_active` TINYINT(1) NOT NULL DEFAULT '1',
			`school_id` INT(11) UNSIGNED NOT NULL,
			`created_at` DATETIME NOT NULL,
			`updated_at` DATETIME NOT NULL,
			PRIMARY KEY (`ID`)
		    ) ENGINE=InnoDB " . $charset_collate;
		  dbDelta($sql);

		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ACADEMIC_MULTI_GROUP_REPORTS . " (
			`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`label` VARCHAR(191) NOT NULL,
			`class_id` INT(11) UNSIGNED NOT NULL,
			`session_id` INT(11) UNSIGNED NOT NULL,
			`exam_groups` TEXT NOT NULL,
			`is_active` TINYINT(1) NOT NULL DEFAULT '1',
			`school_id` INT(11) UNSIGNED NOT NULL,
			`created_at` DATETIME NOT NULL,
			`updated_at` DATETIME NOT NULL,
			PRIMARY KEY (`ID`)
		    ) ENGINE=InnoDB " . $charset_collate;
		  dbDelta($sql);

		/* Add session_id column if not exists to academic_multi_group_reports table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ACADEMIC_MULTI_GROUP_REPORTS . "' AND COLUMN_NAME = 'session_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ACADEMIC_MULTI_GROUP_REPORTS . " ADD session_id INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER class_id");
		}

		/* Create notices table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_NOTICES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title text DEFAULT NULL,
				attachment bigint(20) UNSIGNED DEFAULT NULL,
				url text DEFAULT NULL,
				link_to varchar(15) DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '1',
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				INDEX (added_by),
				FOREIGN KEY (attachment) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add description column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_NOTICES . "' AND COLUMN_NAME = 'description'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_NOTICES . " ADD description text DEFAULT NULL");
		}

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_NOTICES . "' AND COLUMN_NAME = 'notice_data'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_NOTICES . " ADD notice_data text DEFAULT NULL");
		}

		/* Create class_school_notice table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CLASS_SCHOOL_NOTICE . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				notice_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (class_school_id, notice_id),
				INDEX (class_school_id),
				INDEX (notice_id),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (notice_id) REFERENCES " . WLSM_NOTICES . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);


		/* Add remark column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_CLASS_SCHOOL_NOTICE . "' AND COLUMN_NAME = 'class_school_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_CLASS_SCHOOL_NOTICE . " ADD student_school_id bigint(20) UNSIGNED DEFAULT NULL");
		}
		/* Add student_school_id column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_CLASS_SCHOOL_NOTICE . "' AND COLUMN_NAME = 'student_school_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_CLASS_SCHOOL_NOTICE . " ADD student_school_id bigint(20) UNSIGNED DEFAULT NULL");
		}

						/* Create HOSTELS table */
			$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_HOSTELS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				hostel_name varchar(200) DEFAULT NULL,
				hostel_type varchar(200) DEFAULT NULL,
				hostel_address varchar(200) DEFAULT NULL,
				hostel_intake varchar(200) DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				fees varchar(200) DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id)
			) ENGINE=InnoDB " . $charset_collate;
			dbDelta($sql);

			/* Add fees column if not exists to student_records table */
			$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_HOSTELS . "' AND COLUMN_NAME = 'fees'");
			if (empty($row)) {
				$wpdb->query("ALTER TABLE " . WLSM_HOSTELS . " ADD fees varchar(200) DEFAULT NULL");
			}

			/* Create hostel_room table */
			$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ROOMS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				hostel_id bigint(20) UNSIGNED DEFAULT NULL,
				room_name varchar(200) DEFAULT NULL,
				number_of_beds varchar(200) DEFAULT NULL,
				note text DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (hostel_id)
			) ENGINE=InnoDB " . $charset_collate;
			dbDelta($sql);

		/* Create study_materials table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDY_MATERIALS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				description text DEFAULT NULL,
				attachments text DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add url column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDY_MATERIALS . "' AND COLUMN_NAME = 'url'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDY_MATERIALS . " ADD url text DEFAULT NULL");
		}

		/* Add downloadable column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDY_MATERIALS . "' AND COLUMN_NAME = 'downloadable'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDY_MATERIALS . " ADD downloadable bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create class_school_study_material table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				study_material_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (class_school_id, study_material_id),
				INDEX (class_school_id),
				INDEX (study_material_id),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (study_material_id) REFERENCES " . WLSM_STUDY_MATERIALS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add study_material_section_id, study_material_subject_id column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . "' AND COLUMN_NAME = 'study_material_section_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . " ADD study_material_section_id bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . " ADD study_material_subject_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Create homework table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_HOMEWORK . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title varchar(255) DEFAULT NULL,
				description text DEFAULT NULL,
				attachments text DEFAULT NULL,
				homework_date date NULL DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				session_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (session_id) REFERENCES " . WLSM_SESSIONS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add downloadable column if not exists to WLSM_HOMEWORK table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_HOMEWORK . "' AND COLUMN_NAME = 'downloadable'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_HOMEWORK . " ADD downloadable text DEFAULT NULL");
		}

		/* Add url column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_HOMEWORK . "' AND COLUMN_NAME = 'attachments'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_HOMEWORK . " ADD attachments text DEFAULT NULL");
		}
		/* Add subject column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_HOMEWORK . "' AND COLUMN_NAME = 'subject'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_HOMEWORK . " ADD subject varchar(255) DEFAULT NULL");
		}
		/* Add homework_due_date column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_HOMEWORK . "' AND COLUMN_NAME = 'homework_due_date'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_HOMEWORK . " ADD homework_due_date date DEFAULT NULL");
		}

		/* Add attachment_url column if not exists to WLSM_HOMEWORK table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_HOMEWORK . "' AND COLUMN_NAME = 'attachment_url'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_HOMEWORK . " ADD attachment_url text DEFAULT NULL");
		}

		/* Create homework_section table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_HOMEWORK_SECTION . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				homework_id bigint(20) UNSIGNED DEFAULT NULL,
				section_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (homework_id, section_id),
				INDEX (homework_id),
				INDEX (section_id),
				FOREIGN KEY (homework_id) REFERENCES " . WLSM_HOMEWORK . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (section_id) REFERENCES " . WLSM_SECTIONS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);


		/* Create homework_submission table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_HOMEWORK_SUBMISSION . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				description text DEFAULT NULL,
				attachments text DEFAULT NULL,
				student_id bigint(20) UNSIGNED DEFAULT NULL,
				session_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				submission_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (student_id)     REFERENCES " . WLSM_STUDENT_RECORDS  . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (session_id)     REFERENCES " . WLSM_SESSIONS         . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (submission_id)     REFERENCES " . WLSM_HOMEWORK      . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id)      REFERENCES " . WLSM_SCHOOLS          . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create admin_subject table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ADMIN_SUBJECT . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				admin_id bigint(20) UNSIGNED DEFAULT NULL,
				subject_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (admin_id, subject_id),
				INDEX (admin_id),
				INDEX (subject_id),
				FOREIGN KEY (admin_id) REFERENCES " . WLSM_ADMINS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (subject_id) REFERENCES " . WLSM_SUBJECTS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create fees table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_FEES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				label varchar(100) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				period varchar(30) DEFAULT 'one-time',
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add active_on_admission column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_FEES . "' AND COLUMN_NAME = 'active_on_admission'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_FEES . " ADD active_on_admission tinyint(1) NOT NULL DEFAULT '1'");
		}
		/* Add active_on_dashboard column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_FEES . "' AND COLUMN_NAME = 'active_on_dashboard'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_FEES . " ADD active_on_dashboard tinyint(1) NOT NULL DEFAULT '0'");
		}

		/* Add student_type column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_FEES . "' AND COLUMN_NAME = 'student_type'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_FEES . " ADD student_type TEXT DEFAULT NULL");
		}

	// 	/* Create WLSM_STUDENT_ASSIGNED_FEES table */
	// 	$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDENT_ASSIGNED_FEES . " (
	// 		ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	// 		student_record_id bigint(20) UNSIGNED DEFAULT NULL,
	// 		fee_type_id bigint(20) UNSIGNED DEFAULT NULL,
	// 		PRIMARY KEY (ID),
	// 		FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
	// 		) ENGINE=InnoDB " . $charset_collate;
	// dbDelta($sql);

		/* Create student_fees table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDENT_FEES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				label varchar(100) DEFAULT NULL,
				amount decimal(12,2) UNSIGNED DEFAULT '0.00',
				period varchar(30) DEFAULT 'one-time',
				fee_order smallint(4) UNSIGNED DEFAULT '10',
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (student_record_id),
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add class_id column if not exists to fees table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_FEES . "' AND COLUMN_NAME = 'class_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_FEES . " ADD class_id varchar(60) NULL DEFAULT NULL");
		}

		/* Create routines table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ROUTINES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				start_time time DEFAULT NULL,
				end_time time DEFAULT NULL,
				room_number varchar(40) DEFAULT NULL,
				day tinyint(1) DEFAULT NULL,
				subject_id bigint(20) UNSIGNED DEFAULT NULL,
				section_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (subject_id),
				INDEX (section_id),
				FOREIGN KEY (subject_id) REFERENCES " . WLSM_SUBJECTS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (section_id) REFERENCES " . WLSM_SECTIONS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add admin_id column if not exists to routines table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ROUTINES . "' AND COLUMN_NAME = 'admin_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ROUTINES . " ADD admin_id bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX admin_id ON " . WLSM_ROUTINES . " (admin_id)");
			$wpdb->query("ALTER TABLE " . WLSM_ROUTINES . " ADD FOREIGN KEY (admin_id) REFERENCES " . WLSM_ADMINS . " (ID) ON DELETE SET NULL");
		}

		/* Add enrollment_prefix, enrollment_base, enrollment_padding column if not exists to schools table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_SCHOOLS . "' AND COLUMN_NAME = 'enrollment_prefix'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD enrollment_prefix varchar(15) DEFAULT ''");
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD enrollment_base int(11) UNSIGNED DEFAULT '0'");
			$wpdb->query("ALTER TABLE " . WLSM_SCHOOLS . " ADD enrollment_padding smallint(4) UNSIGNED DEFAULT '6'");
		}

		/* Create books table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_BOOKS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title varchar(100) DEFAULT NULL,
				author varchar(60) DEFAULT NULL,
				subject varchar(100) DEFAULT NULL,
				description text DEFAULT NULL,
				rack_number varchar(40) DEFAULT NULL,
				book_number varchar(100) DEFAULT NULL,
				isbn_number varchar(100) DEFAULT NULL,
				price decimal(12,2) UNSIGNED DEFAULT NULL,
				quantity smallint(4) UNSIGNED DEFAULT '0',
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create books_issued table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_BOOKS_ISSUED . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				book_id bigint(20) UNSIGNED DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				quantity smallint(4) UNSIGNED DEFAULT '1',
				date_issued date NULL DEFAULT NULL,
				return_date date NULL DEFAULT NULL,
				returned_at timestamp NULL DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (book_id),
				INDEX (student_record_id),
				FOREIGN KEY (book_id) REFERENCES " . WLSM_BOOKS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create library_cards table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_LIBRARY_CARDS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				card_number varchar(60) DEFAULT NULL,
				date_issued date NULL DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (student_record_id),
				INDEX (student_record_id),
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create vehicles table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_VEHICLES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				vehicle_number varchar(60) DEFAULT NULL,
				vehicle_model varchar(60) DEFAULT NULL,
				driver_name varchar(60) DEFAULT NULL,
				driver_phone varchar(40) DEFAULT NULL,
				note text DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create routes table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ROUTES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(100) DEFAULT NULL,
				period varchar(100) DEFAULT NULL,
				fare decimal(12,2) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ROUTES . "' AND COLUMN_NAME = 'period'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ROUTES . " ADD period varchar(200) DEFAULT NULL");
		}

		/* Create route_vehicle table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ROUTE_VEHICLE . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				route_id bigint(20) UNSIGNED DEFAULT NULL,
				vehicle_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (route_id, vehicle_id),
				INDEX (route_id),
				INDEX (vehicle_id),
				FOREIGN KEY (vehicle_id) REFERENCES " . WLSM_VEHICLES . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (route_id) REFERENCES " . WLSM_ROUTES . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add route_vehicle_id column if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'route_vehicle_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD route_vehicle_id bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX route_vehicle_id ON " . WLSM_STUDENT_RECORDS . " (route_vehicle_id)");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD FOREIGN KEY (route_vehicle_id) REFERENCES " . WLSM_ROUTE_VEHICLE . " (ID) ON DELETE SET NULL");
		}

		/* Add room_id column if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'room_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD room_id bigint(20) UNSIGNED DEFAULT NULL");

		}

		/* Create logs table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_LOGS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				log_key text NOT NULL,
				log_value text NOT NULL,
				log_group text NOT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (ID),
				INDEX (school_id),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add section_id column if not exists to logs table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_LOGS . "' AND COLUMN_NAME = 'section_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_LOGS . " ADD section_id bigint(20) UNSIGNED DEFAULT NULL");
		}

		/* Add added_by column if not exists to student_records, invoices, payments, attendance, staff_attendance, expenses, income tables */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_RECORDS . "' AND COLUMN_NAME = 'added_by'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_STUDENT_RECORDS . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_RECORDS . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_INVOICES . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_INVOICES . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_PAYMENTS . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_PAYMENTS . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_ATTENDANCE . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_ATTENDANCE . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_ATTENDANCE . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_STAFF_ATTENDANCE . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_STAFF_ATTENDANCE . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_STAFF_ATTENDANCE . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_EXPENSES . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_EXPENSES . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_EXPENSES . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_INCOME . " ADD added_by bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX added_by ON " . WLSM_INCOME . " (added_by)");
			$wpdb->query("ALTER TABLE " . WLSM_INCOME . " ADD FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL");
		}

		/* Add section_id, vehicle_id columns if not exists to admins table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_ADMINS . "' AND COLUMN_NAME = 'section_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD section_id bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX section_id ON " . WLSM_ADMINS . " (section_id)");
			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD FOREIGN KEY (section_id) REFERENCES " . WLSM_SECTIONS . " (ID) ON DELETE SET NULL");

			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD vehicle_id bigint(20) UNSIGNED DEFAULT NULL");
			$wpdb->query("CREATE INDEX vehicle_id ON " . WLSM_ADMINS . " (vehicle_id)");
			$wpdb->query("ALTER TABLE " . WLSM_ADMINS . " ADD FOREIGN KEY (vehicle_id) REFERENCES " . WLSM_VEHICLES . " (ID) ON DELETE SET NULL");
		}

		/* Create leaves table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_LEAVES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				description text DEFAULT NULL,
				start_date date NULL DEFAULT NULL,
				end_date date NULL DEFAULT NULL,
				is_approved tinyint(1) NOT NULL DEFAULT '0',
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				admin_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				approved_by bigint(20) UNSIGNED DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (student_record_id),
				INDEX (admin_id),
				INDEX (school_id),
				INDEX (added_by),
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (admin_id) REFERENCES " . WLSM_ADMINS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (approved_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

			/* Create activities table */
			$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_ACTIVITIES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title text DEFAULT NULL,
				fees text DEFAULT NULL,
				description text DEFAULT NULL,
				is_approved tinyint(1) NOT NULL DEFAULT '0',
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				class_id bigint(20) UNSIGNED DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				INDEX (added_by),
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create events table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EVENTS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title text DEFAULT NULL,
				description text DEFAULT NULL,
				image_id bigint(20) UNSIGNED DEFAULT NULL,
				event_date date NULL DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '1',
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (school_id),
				INDEX (added_by),
				FOREIGN KEY (image_id) REFERENCES " . WLSM_POSTS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create event_responses table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_EVENT_RESPONSES . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id bigint(20) UNSIGNED DEFAULT NULL,
				student_record_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (event_id, student_record_id),
				INDEX (event_id),
				INDEX (student_record_id),
				FOREIGN KEY (event_id) REFERENCES " . WLSM_EVENTS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (student_record_id) REFERENCES " . WLSM_STUDENT_RECORDS . " (ID) ON DELETE CASCADE
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Chapter Table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_CHAPTER . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title varchar(191) DEFAULT NULL,
			class_id bigint(20) UNSIGNED DEFAULT NULL,
			subject_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_LECTURE . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title varchar(191) DEFAULT NULL,
			description text DEFAULT NULL,
			attachment text DEFAULT NULL,
			link_to text DEFAULT NULL,
			url text DEFAULT NULL,
			class_id bigint(20) DEFAULT NULL,
			chapter_id bigint(20) DEFAULT NULL,
			section_id bigint(20) DEFAULT NULL,
			subject_id bigint(20) DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/** Ratting */

		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_RATTING . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`message` text DEFAULT NULL,
			student_id bigint(20) UNSIGNED DEFAULT NULL,
			live_class_id bigint(20) DEFAULT NULL,
			ratting varchar(200) DEFAULT NULL,
			added_by varchar(200) DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID)
			) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Add moderator_code column if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_RATTING . "' AND COLUMN_NAME = 'live_class_id'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_RATTING . " ADD live_class_id bigint(20) DEFAULT NULL");
		}

		/* Create meetings table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_MEETINGS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				host varchar(191) DEFAULT NULL,
				host_id text DEFAULT NULL,
				alternative_hosts text DEFAULT NULL,
				meeting_id varchar(191) DEFAULT NULL,
				topic text DEFAULT NULL,
				agenda text DEFAULT NULL,
				duration smallint(4) UNSIGNED DEFAULT NULL,
				start_at timestamp NULL DEFAULT NULL,
				type smallint(6) UNSIGNED DEFAULT NULL,
				recurrence_type smallint(6) UNSIGNED DEFAULT NULL,
				repeat_interval smallint(6) UNSIGNED DEFAULT NULL,
				weekly_days varchar(255) DEFAULT NULL,
				monthly_day smallint(4) UNSIGNED DEFAULT NULL,
				end_times smallint(6) UNSIGNED DEFAULT NULL,
				end_at timestamp NULL DEFAULT NULL,
				approval_type smallint(6) UNSIGNED DEFAULT NULL,
				registration_type smallint(6) UNSIGNED DEFAULT NULL,
				password varchar(255) DEFAULT NULL,
				join_before_host tinyint(1) NOT NULL DEFAULT '1',
				host_video tinyint(1) NOT NULL DEFAULT '0',
				participant_video tinyint(1) NOT NULL DEFAULT '0',
				mute_upon_entry tinyint(1) NOT NULL DEFAULT '0',
				start_url text DEFAULT NULL,
				join_url text DEFAULT NULL,
				class_school_id bigint(20) UNSIGNED DEFAULT NULL,
				admin_id bigint(20) UNSIGNED DEFAULT NULL,
				subject_id bigint(20) UNSIGNED DEFAULT NULL,
				school_id bigint(20) UNSIGNED DEFAULT NULL,
				added_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				INDEX (class_school_id),
				INDEX (admin_id),
				INDEX (subject_id),
				INDEX (school_id),
				INDEX (added_by),
				FOREIGN KEY (class_school_id) REFERENCES " . WLSM_CLASS_SCHOOL . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (admin_id) REFERENCES " . WLSM_ADMINS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (subject_id) REFERENCES " . WLSM_SUBJECTS . " (ID) ON DELETE SET NULL,
				FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . " (ID) ON DELETE CASCADE,
				FOREIGN KEY (added_by) REFERENCES " . WLSM_USERS . " (ID) ON DELETE SET NULL
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);



		/* Add moderator_code column if not exists to student_records table */
		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_MEETINGS . "' AND COLUMN_NAME = 'moderator_code'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_MEETINGS . " ADD moderator_code varchar(200) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_MEETINGS . " ADD recordable tinyint(1) NOT NULL DEFAULT '0'");
			$wpdb->query("ALTER TABLE " . WLSM_MEETINGS . " ADD class_type varchar(90) DEFAULT NULL");
			$wpdb->query("ALTER TABLE " . WLSM_MEETINGS . " ADD section_id smallint(6) UNSIGNED DEFAULT NULL");
		}


		/* Create student_total_marks table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_STUDENT_TOTAL_MARKS . " (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				student_id bigint(20) UNSIGNED DEFAULT NULL,
				total_marks bigint(20) UNSIGNED DEFAULT NULL,
				report_id bigint(20) UNSIGNED DEFAULT NULL,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at timestamp NULL DEFAULT NULL,
				PRIMARY KEY (ID),
				UNIQUE (student_id, report_id),
				INDEX (student_id),
				INDEX (report_id)
				) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);


		// Create tickets table
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_TICKETS . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description longtext DEFAULT NULL,
			priority enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
			status enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
			school_id bigint(20) UNSIGNED NOT NULL,
			role_id bigint(20) UNSIGNED DEFAULT NULL,
			assigned_to bigint(20) UNSIGNED DEFAULT NULL,
			student_id bigint(20) UNSIGNED DEFAULT NULL,
			class_id bigint(20) UNSIGNED DEFAULT NULL,
			section_id bigint(20) UNSIGNED DEFAULT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			due_date date DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			INDEX (school_id),
			INDEX (assigned_to),
			INDEX (student_id),
			INDEX (status),
			INDEX (priority),
			FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . "(ID) ON DELETE CASCADE
		) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		// Create ticket history table
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_TICKET_HISTORY . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) UNSIGNED NOT NULL,
			status enum('open','in_progress','resolved','closed') NOT NULL,
			comment text DEFAULT NULL,
			changed_by bigint(20) UNSIGNED NOT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ID),
			INDEX (ticket_id),
			INDEX (changed_by),
			FOREIGN KEY (ticket_id) REFERENCES " . WLSM_TICKETS . "(ID) ON DELETE CASCADE
		) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create discounts table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_DISCOUNTS . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			amount decimal(10, 2) NOT NULL,
			discount_percent decimal(5,2) DEFAULT NULL,
			note text DEFAULT NULL,
			type varchar(20) NOT NULL,
			student_id bigint(20) UNSIGNED DEFAULT NULL,
			invoice_id bigint(20) UNSIGNED DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			KEY `student_id_index` (`student_id`),
			CONSTRAINT `fk_student_discount` FOREIGN KEY (`student_id`)
			REFERENCES " . WLSM_STUDENT_RECORDS . " (`ID`)
			ON DELETE CASCADE
		) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		/* Create invoice discount changes table */
		$sql = "CREATE TABLE IF NOT EXISTS " . WLSM_INVOICE_DISCOUNT_CHANGES . " (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			invoice_id bigint(20) UNSIGNED NOT NULL,
			discount_id bigint(20) UNSIGNED NOT NULL,
			old_amount decimal(10, 2) NOT NULL,
			new_amount decimal(10, 2) NOT NULL,
			change_note text DEFAULT NULL,
			change_date timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			staff_id bigint(20) UNSIGNED NOT NULL,
			PRIMARY KEY (ID),
			KEY `idx_invoice_id` (`invoice_id`),
			KEY `idx_discount_id` (`discount_id`),
			KEY `idx_staff_id` (`staff_id`),
			CONSTRAINT `fk_invoice_discount_change_invoice`
				FOREIGN KEY (`invoice_id`) REFERENCES " . WLSM_INVOICES . " (`ID`)
				ON DELETE CASCADE,
			CONSTRAINT `fk_invoice_discount_change_discount`
				FOREIGN KEY (`discount_id`) REFERENCES " . WLSM_DISCOUNTS . " (`ID`)
				ON DELETE CASCADE,
			CONSTRAINT `fk_invoice_discount_change_staff`
				FOREIGN KEY (`staff_id`) REFERENCES " . WLSM_STAFF . " (`ID`)
				ON DELETE CASCADE
		) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$sql = "CREATE TABLE IF NOT EXISTS ".WLSM_CONCESSION_TYPES." (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			concession_name varchar(100) NOT NULL,
			concession_type enum('percentage', 'fixed_amount') NOT NULL,
			percentage_value decimal(5,2) DEFAULT NULL,
			fixed_amount decimal(10,2) DEFAULT NULL,
			eligibility_criteria text DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT '1',
			school_id bigint(20) UNSIGNED NOT NULL,
			class_id bigint(20) UNSIGNED NOT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			INDEX (school_id),
			INDEX (is_active),
			FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . "(ID) ON DELETE CASCADE
			) ENGINE=InnoDB " . $charset_collate;
				dbDelta($sql);

		// create student_concessions table
		$sql = "CREATE TABLE IF NOT EXISTS ".WLSM_STUDENT_CONCESSION." (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			student_record_id bigint(20) UNSIGNED NOT NULL,
			concession_type_id bigint(20) UNSIGNED NOT NULL,
			session_id bigint(20) UNSIGNED NOT NULL,
			school_id bigint(20) UNSIGNED NOT NULL,
			applied_by bigint(20) UNSIGNED DEFAULT NULL,
			application_date timestamp NULL DEFAULT NULL,
			approved_by bigint(20) UNSIGNED DEFAULT NULL,
			approval_date timestamp NULL DEFAULT NULL,
			status enum('pending', 'approved', 'rejected', 'expired') NOT NULL DEFAULT 'pending',
			remarks text DEFAULT NULL,
			concession_amount decimal(10,2) DEFAULT NULL,
			rejection_reason text DEFAULT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			UNIQUE KEY unique_student_concession (student_record_id, session_id),
			INDEX (student_record_id),
			INDEX (concession_type_id),
			INDEX (session_id),
			INDEX (school_id),
			INDEX (applied_by),
			INDEX (approved_by),
			INDEX (status),
			FOREIGN KEY (student_record_id) REFERENCES ".WLSM_STUDENT_RECORDS." (ID) ON DELETE CASCADE,
			FOREIGN KEY (concession_type_id) REFERENCES ".WLSM_CONCESSION_TYPES." (ID) ON DELETE CASCADE,
			FOREIGN KEY (session_id) REFERENCES ".WLSM_SESSIONS." (ID) ON DELETE CASCADE,
			FOREIGN KEY (school_id) REFERENCES " . WLSM_SCHOOLS . "(ID) ON DELETE CASCADE,
			FOREIGN KEY (applied_by) REFERENCES ".WLSM_STAFF." (ID) ON DELETE SET NULL,
			FOREIGN KEY (approved_by) REFERENCES ".WLSM_STAFF." (ID) ON DELETE SET NULL
		) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '" . WLSM_STUDENT_CONCESSION . "' AND COLUMN_NAME = 'concession_amount'");
		if (empty($row)) {
			$wpdb->query("ALTER TABLE " . WLSM_STUDENT_CONCESSION . " ADD concession_amount decimal(10,2) DEFAULT NULL AFTER remarks");
		}

		/* Create concession fee mappings table */
		$sql = "CREATE TABLE IF NOT EXISTS ".WLSM_CONCESSION_FEE_MAPPINGS." (
			ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			concession_type_id bigint(20) UNSIGNED NOT NULL,
			fee_type_id bigint(20) UNSIGNED NOT NULL,
			created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NULL DEFAULT NULL,
			PRIMARY KEY (ID),
			UNIQUE KEY unique_concession_fee_mapping (concession_type_id, fee_type_id),
			INDEX (concession_type_id),
			INDEX (fee_type_id),
			FOREIGN KEY (concession_type_id) REFERENCES ".WLSM_CONCESSION_TYPES." (ID) ON DELETE CASCADE,
			FOREIGN KEY (fee_type_id) REFERENCES ".WLSM_FEES." (ID) ON DELETE CASCADE
		) ENGINE=InnoDB " . $charset_collate;
		dbDelta($sql);

		self::set_default_options($session_id);

		// Set default school for super admin.
		if (isset($default_school_id)) {
			$user_id = get_current_user_id();

			// Data to update or insert.
			$data = array(
				'school_id' => $default_school_id,
				'user_id'   => $user_id,
				'role'      => WLSM_M_Role::get_admin_key(),
			);

			$data['created_at'] = current_time('Y-m-d H:i:s');

			$wpdb->insert(WLSM_STAFF, $data);

			update_user_meta($user_id, 'wlsm_school_id', $default_school_id);
		}
	}

	public static function deactivation()
	{
		delete_option('wlsm-key');
		delete_option('wlsm-valid');
		delete_option('wlsm-code');
		delete_option('wlsm-cache');
		delete_option('wlsm-updation-detail');


		// Clear the demo data generation cron job
		wp_clear_scheduled_hook('wlsm_generate_demo_data');
	}

	public static function uninstall()
	{
		delete_option('wlsm-key');
		// delete_option('wlsm-valid');
		delete_option('wlsm-cache');
		delete_option('wlsm-updation-detail');
		if (get_option('wlsm_delete_on_uninstall')) {
			// Drop all tables and delete options.
			self::remove_data();
		}
	}

	private static function insert_default_school()
	{
		global $wpdb;

		// Check if 'Default School' already exists
		$existing_school_id = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . WLSM_SCHOOLS . " WHERE label = %s",
			esc_html__('Default School', 'school-management')
		));

		if ($existing_school_id) {
			// Return the existing school's ID
			return $existing_school_id;
		}

		// Prepare data for insertion
		$default_school_data = array(
			'label' => esc_html__('Default School', 'school-management'),
			'created_at' => current_time('Y-m-d H:i:s')
		);

		// Insert the new school
		$wpdb->insert(WLSM_SCHOOLS, $default_school_data);

		// Return the new school's ID
		return $wpdb->insert_id;
	}

	private static function insert_default_subjects() {
		global $wpdb;

		// Get all students with their session_id
		$students = $wpdb->get_results("SELECT ID, session_id FROM " . WLSM_STUDENT_RECORDS);

		foreach ($students as $student) {
			$student_id = $student->ID;
			$session_id = $student->session_id;
			$subjects = self::get_student_class_subjects($student_id);

			// Assign subjects to the student
			if (!empty($subjects)) {
				foreach ($subjects as $subject) {
					$subject_id = $subject->ID;

					// Check if this student-subject-session relationship already exists
					$existing = $wpdb->get_var($wpdb->prepare(
						"SELECT ID FROM " . WLSM_STUDENTS_SUBJECTS . "
						WHERE student_id = %d AND subject_id = %d AND session_id = %d",
						$student_id, $subject_id, $session_id
					));

					if (!$existing) {
						// Insert the student-subject relationship into WLSM_STUDENTS_SUBJECTS table
						$wpdb->insert( WLSM_STUDENTS_SUBJECTS, array(
								'student_id' => $student_id,
								'subject_id' => $subject_id,
								'session_id' => $session_id
							),
							array('%d', '%d', '%d')
						);
					}
				}
			}
		}
	}

	public static function get_student_class_subjects($student_id) {
		global $wpdb;

		// Get the class ID of the student ID
		$class_id = $wpdb->get_var($wpdb->prepare('SELECT cs.class_id FROM ' . WLSM_STUDENT_RECORDS . ' as sr
		JOIN '. WLSM_SECTIONS .' as ss ON sr.section_id = ss.ID
		JOIN '. WLSM_CLASS_SCHOOL .' as cs ON cs.ID = ss.class_school_id
		WHERE sr.ID = %d', $student_id));

		// Get all subjects for the class
		$subjects = $wpdb->get_results($wpdb->prepare('SELECT sj.ID, sj.label, sj.code, sj.type FROM ' . WLSM_SUBJECTS . ' as sj
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = sj.class_school_id
			WHERE cs.class_id = %d', $class_id));

		return $subjects;
	}

	private static function enroll_students_in_subjects() {
		global $wpdb;

		// Get all students with their session_id and section information
		$students = $wpdb->get_results("
			SELECT sr.ID as student_id, sr.session_id, se.class_school_id
			FROM " . WLSM_STUDENT_RECORDS . " as sr
			JOIN " . WLSM_SECTIONS . " as se ON sr.section_id = se.ID
		");

		foreach ($students as $student) {
			// Get all subjects for this student's class
			$subjects = $wpdb->get_results($wpdb->prepare(
				"SELECT ID FROM " . WLSM_SUBJECTS . " WHERE class_school_id = %d",
				$student->class_school_id
			));

			// Enroll student in all class subjects
			foreach ($subjects as $subject) {
				// Check if enrollment already exists
				$existing = $wpdb->get_var($wpdb->prepare(
					"SELECT ID FROM " . WLSM_STUDENTS_SUBJECTS . "
					WHERE student_id = %d AND subject_id = %d AND session_id = %d",
					$student->student_id, $subject->ID, $student->session_id
				));

				if (!$existing) {
					$wpdb->insert(WLSM_STUDENTS_SUBJECTS, array(
						'student_id' => $student->student_id,
						'subject_id' => $subject->ID,
						'session_id' => $student->session_id
					), array('%d', '%d', '%d'));
				}
			}
		}
	}

	// Public method to manually enroll students in subjects (can be called independently)
	public static function enroll_all_students_in_subjects() {
		global $wpdb;

		try {
			$wpdb->query('START TRANSACTION');
			self::enroll_students_in_subjects();
			$wpdb->query('COMMIT');
			return array('success' => true, 'message' => 'Students enrolled in subjects successfully');
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
		}
	}

	public static function insert_default_subject_types(){
		global $wpdb;
		$wpdb->insert(WLSM_SUBJECT_TYPES, array('label' => 'Theory'));
		$wpdb->insert(WLSM_SUBJECT_TYPES, array('label' => 'Practical'));
		$wpdb->insert(WLSM_SUBJECT_TYPES, array('label' => 'Subjective'));
		$wpdb->insert(WLSM_SUBJECT_TYPES, array('label' => 'Objective'));
	}

	private static function insert_default_classes()
	{
		global $wpdb;

		$sql = "INSERT INTO `" . WLSM_CLASSES . "` (`label`) VALUES ('1st'),('2nd'),('3rd'),('4th'),('5th'),('6th'),('7th'),('8th'),('9th'),('10th'),('11th'),('12th');";
		$wpdb->query($sql);
	}

	private static function insert_default_category(){
		global $wpdb;
		$wpdb->insert(WLSM_CATEGORY, array('label' => 'central'));
		$wpdb->insert(WLSM_CATEGORY, array('label' => 'state'));
		$wpdb->insert(WLSM_CATEGORY, array('label' => 'private'));
	}

	public static function insert_default_medium(){
		global $wpdb;
		$school_id = self::insert_default_school();
		$wpdb->insert(WLSM_MEDIUM, array('label' => 'English', 'school_id' => $school_id));
		$wpdb->insert(WLSM_MEDIUM, array('label' => 'Hindi', 'school_id' => $school_id));
	}

	public static function insert_default_student_type(){
		global $wpdb;
		$school_id = self::insert_default_school();
		$wpdb->insert(WLSM_STUDENT_TYPE, array('label' => 'Regular', 'school_id' => $school_id));
		$wpdb->insert(WLSM_STUDENT_TYPE, array('label' => 'Private', 'school_id' => $school_id));
		$wpdb->insert(WLSM_STUDENT_TYPE, array('label' => 'RTE', 'school_id' => $school_id));
		$wpdb->insert(WLSM_STUDENT_TYPE, array('label' => 'Other', 'school_id' => $school_id));
	}

	public static function set_default_options($session_id = NULL)
	{
		$current_session = get_option('wlsm_current_session');
		if (!$current_session && $session_id) {
			add_option('wlsm_current_session', $session_id);
		}

		$currency = get_option('wlsm_currency');
		if (!$currency) {
			add_option('wlsm_currency', WLSM_Config::get_default_currency());
		}

		$date_format = get_option('wlsm_date_format');
		if (!$date_format) {
			add_option('wlsm_date_format', WLSM_Config::get_default_date_format());
		}
	}

	public static function remove_data()
	{
		global $wpdb;

		$wpdb->query('SET FOREIGN_KEY_CHECKS=0');
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_MEETINGS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EVENT_RESPONSES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EVENTS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_LEAVES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_LOGS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_LIBRARY_CARDS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_BOOKS_ISSUED);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_BOOKS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ROUTINES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_STUDENT_FEES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_FEES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ADMIN_SUBJECT);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_HOMEWORK_SECTION);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_HOMEWORK);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_HOMEWORK_SUBMISSION);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CLASS_SCHOOL_STUDY_MATERIAL);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_STUDY_MATERIALS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CLASS_SCHOOL_NOTICE);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_NOTICES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EXAM_RESULTS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ADMIT_CARDS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EXAM_PAPERS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CLASS_SCHOOL_EXAM);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EXAMS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_SUBJECTS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_STAFF_ATTENDANCE);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ATTENDANCE);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_INCOME);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_INCOME_CATEGORIES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EXPENSES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_EXPENSE_CATEGORIES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_PENDING_PAYMENTS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_PAYMENTS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_INVOICES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CERTIFICATE_STUDENT);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CERTIFICATES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_TRANSFERS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_PROMOTIONS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_STUDENT_RECORDS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ADMINS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ROUTE_VEHICLE);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ROUTES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_VEHICLES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_SECTIONS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_STAFF);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_ROLES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_INQUIRIES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_SESSIONS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CLASS_SCHOOL);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_CLASSES);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_SETTINGS);
		$wpdb->query('DROP TABLE IF EXISTS ' . WLSM_SCHOOLS);
		$wpdb->query('SET FOREIGN_KEY_CHECKS=1');

		delete_metadata('user', 0, 'wlsm_school_id', '', true);
		delete_metadata('user', 0, 'wlsm_current_session', '', true);

		delete_option('wlsm_current_session');
		delete_option('wlsm_date_format');
		delete_option('wlsm_currency');
		delete_option('wlsm_gdpr_enable');
		delete_option('wlsm_gdpr_text_inquiry');
		delete_option('wlsm_gdpr_text_registration');

		delete_option('wlsm_delete_on_uninstall');
	}

	/**
	 * Clean up existing demo data
	 */
	public static function cleanup_demo_data() {
		global $wpdb;

		// Get photo IDs from demo student records before deleting them
		$demo_photo_ids = $wpdb->get_col("SELECT photo_id FROM " . WLSM_STUDENT_RECORDS . " WHERE enrollment_number LIKE 'DEMO%' AND photo_id IS NOT NULL");

		// Get school logo and signature IDs from demo schools before deleting them
		$demo_school_settings = $wpdb->get_results("
			SELECT setting_value FROM " . WLSM_SETTINGS . "
			WHERE school_id IN (SELECT ID FROM " . WLSM_SCHOOLS . " WHERE label LIKE '%Demo%')
			AND setting_key = 'general'
		");

		$demo_logo_signature_ids = array();
		foreach ($demo_school_settings as $setting) {
			$settings_data = unserialize($setting->setting_value);
			if (isset($settings_data['school_logo']) && $settings_data['school_logo']) {
				$demo_logo_signature_ids[] = $settings_data['school_logo'];
			}
			if (isset($settings_data['school_signature']) && $settings_data['school_signature']) {
				$demo_logo_signature_ids[] = $settings_data['school_signature'];
			}
		}

		// Define demo school IDs SQL for reuse
		$demo_school_ids_sql = "SELECT ID FROM " . WLSM_SCHOOLS . " WHERE label LIKE '%Demo%'";

		// === EXAM RELATED CLEANUP (hierarchical order) ===
		// Delete exam results linked to exams in demo schools
		$wpdb->query("DELETE er FROM " . WLSM_EXAM_RESULTS . " er JOIN " . WLSM_ADMIT_CARDS . " ac ON ac.ID = er.admit_card_id JOIN " . WLSM_EXAMS . " ex ON ex.ID = ac.exam_id WHERE ex.school_id IN (" . $demo_school_ids_sql . ")");
		// Delete admit cards for exams in demo schools
		$wpdb->query("DELETE ac FROM " . WLSM_ADMIT_CARDS . " ac JOIN " . WLSM_EXAMS . " ex ON ex.ID = ac.exam_id WHERE ex.school_id IN (" . $demo_school_ids_sql . ")");
		// Delete exam papers for exams in demo schools
		$wpdb->query("DELETE ep FROM " . WLSM_EXAM_PAPERS . " ep JOIN " . WLSM_EXAMS . " ex ON ex.ID = ep.exam_id WHERE ex.school_id IN (" . $demo_school_ids_sql . ")");
		// Delete class-school exam links for exams in demo schools
		$wpdb->query("DELETE csex FROM " . WLSM_CLASS_SCHOOL_EXAM . " csex JOIN " . WLSM_EXAMS . " ex ON ex.ID = csex.exam_id WHERE ex.school_id IN (" . $demo_school_ids_sql . ")");
		// Delete exams
		$wpdb->query("DELETE FROM " . WLSM_EXAMS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");
		// Delete exam groups
		$wpdb->query("DELETE FROM " . WLSM_EXAMS_GROUP . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === STUDENT SUBJECT ASSIGNMENTS ===
		// Delete student-subject assignments for demo students
		$wpdb->query("DELETE ss FROM " . WLSM_STUDENTS_SUBJECTS . " ss JOIN " . WLSM_STUDENT_RECORDS . " sr ON sr.ID = ss.student_id WHERE sr.enrollment_number LIKE 'DEMO%'");

		// === ATTENDANCE AND LEAVE CLEANUP ===
		// Delete demo student attendance records
		$wpdb->query("DELETE att FROM " . WLSM_ATTENDANCE . " att JOIN " . WLSM_STUDENT_RECORDS . " sr ON sr.ID = att.student_record_id WHERE sr.enrollment_number LIKE 'DEMO%'");
		// Clean up orphaned attendance records
		$wpdb->query("DELETE FROM " . WLSM_ATTENDANCE . " WHERE student_record_id NOT IN (SELECT ID FROM " . WLSM_STUDENT_RECORDS . ")");

		// Delete demo student leave records
		$wpdb->query("DELETE l FROM " . WLSM_LEAVES . " l JOIN " . WLSM_STUDENT_RECORDS . " sr ON sr.ID = l.student_record_id WHERE sr.enrollment_number LIKE 'DEMO%'");
		// Delete demo staff leave records (will be cleaned up when admin records are deleted due to foreign keys)
		$wpdb->query("DELETE FROM " . WLSM_LEAVES . " WHERE admin_id NOT IN (SELECT ID FROM " . WLSM_ADMINS . ")");

		// Delete demo staff attendance records
		$wpdb->query("DELETE FROM " . WLSM_STAFF_ATTENDANCE . " WHERE admin_id NOT IN (SELECT ID FROM " . WLSM_ADMINS . ")");

		// === CONTENT CLEANUP ===
		// Delete demo notice class assignments first
		$wpdb->query("DELETE FROM " . WLSM_CLASS_SCHOOL_NOTICE . " WHERE notice_id IN (SELECT ID FROM " . WLSM_NOTICES . " WHERE school_id IN (" . $demo_school_ids_sql . "))");
		// Delete demo notices
		$wpdb->query("DELETE FROM " . WLSM_NOTICES . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === INCOME AND EXPENSE CLEANUP ===
		// Delete demo income records
		$wpdb->query("DELETE FROM " . WLSM_INCOME . " WHERE school_id IN (" . $demo_school_ids_sql . ")");
		// Delete demo expense records
		$wpdb->query("DELETE FROM " . WLSM_EXPENSES . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo study material class assignments first (to avoid foreign key issues)
		$wpdb->query("DELETE FROM " . WLSM_CLASS_SCHOOL_STUDY_MATERIAL . " WHERE study_material_id IN (SELECT ID FROM " . WLSM_STUDY_MATERIALS . " WHERE school_id IN (" . $demo_school_ids_sql . "))");
		// Delete demo study materials
		$wpdb->query("DELETE FROM " . WLSM_STUDY_MATERIALS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo homework section assignments first
		$wpdb->query("DELETE FROM " . WLSM_HOMEWORK_SECTION . " WHERE homework_id IN (SELECT ID FROM " . WLSM_HOMEWORK . " WHERE school_id IN (" . $demo_school_ids_sql . "))");
		// Delete demo homework submissions
		$wpdb->query("DELETE hs FROM " . WLSM_HOMEWORK_SUBMISSION . " hs JOIN " . WLSM_HOMEWORK . " h ON h.ID = hs.submission_id WHERE h.school_id IN (" . $demo_school_ids_sql . ")");
		// Clean up orphaned homework submissions
		$wpdb->query("DELETE FROM " . WLSM_HOMEWORK_SUBMISSION . " WHERE submission_id NOT IN (SELECT ID FROM " . WLSM_HOMEWORK . ")");
		// Delete demo homework
		$wpdb->query("DELETE FROM " . WLSM_HOMEWORK . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === EVENTS CLEANUP ===
		// Delete demo event responses
		$wpdb->query("DELETE er FROM " . WLSM_EVENT_RESPONSES . " er JOIN " . WLSM_EVENTS . " e ON e.ID = er.event_id WHERE e.school_id IN (" . $demo_school_ids_sql . ")");
		// Clean up orphaned event responses
		$wpdb->query("DELETE FROM " . WLSM_EVENT_RESPONSES . " WHERE event_id NOT IN (SELECT ID FROM " . WLSM_EVENTS . ")");
		// Delete demo events
		$wpdb->query("DELETE FROM " . WLSM_EVENTS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === STAFF AND ADMIN CLEANUP ===
		// Delete all demo admin subject assignments including school administrator
		$wpdb->query("DELETE FROM " . WLSM_ADMIN_SUBJECT . " WHERE admin_id IN (SELECT ID FROM " . WLSM_ADMINS . " WHERE email LIKE '%.admin.demo@example.com' OR email LIKE '%.staff@example.com' OR email = 'school_administrator@example.com')");

		// Delete all demo admins including school administrator
		$wpdb->query("DELETE FROM " . WLSM_ADMINS . " WHERE email LIKE '%.admin.demo@example.com' OR email LIKE '%.staff@example.com' OR email = 'school_administrator@example.com'");

		// Delete demo staff (will be deleted when demo schools are deleted due to foreign key constraints)
		$wpdb->query("DELETE FROM " . WLSM_STAFF . " WHERE role LIKE '%demo%'");

		// === TIMETABLE CLEANUP ===
		// Delete demo routines/timetables for demo sections
		$wpdb->query("DELETE r FROM " . WLSM_ROUTINES . " r JOIN " . WLSM_SECTIONS . " sec ON sec.ID = r.section_id JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = sec.class_school_id WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");

		// === CHAPTERS AND LECTURES CLEANUP ===
		// Delete demo lectures first (to handle foreign key constraints)
		$wpdb->query("DELETE l FROM " . WLSM_LECTURE . " l
			JOIN " . WLSM_SUBJECTS . " s ON s.ID = l.subject_id
			JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
			WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo chapters
		$wpdb->query("DELETE c FROM " . WLSM_CHAPTER . " c
			JOIN " . WLSM_SUBJECTS . " s ON s.ID = c.subject_id
			JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
			WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");

		// === ACTIVITIES CLEANUP ===
		// Delete demo activities
		$wpdb->query("DELETE FROM " . WLSM_ACTIVITIES . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === TICKETS CLEANUP ===
		// Delete demo tickets
		$wpdb->query("DELETE FROM " . WLSM_TICKETS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === CERTIFICATES CLEANUP ===
		// First delete student certificate assignments
		$wpdb->query("DELETE cs FROM " . $wpdb->prefix . "wlsm_certificate_student cs
			JOIN " . $wpdb->prefix . "wlsm_certificates c ON c.ID = cs.certificate_id
			WHERE c.school_id IN (" . $demo_school_ids_sql . ")");

		// Then delete certificate templates
		$wpdb->query("DELETE FROM " . $wpdb->prefix . "wlsm_certificates WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === TRANSPORT CLEANUP ===
		// Reset student transport assignments
		$wpdb->query("UPDATE " . WLSM_STUDENT_RECORDS . " sr
			JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
			JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
			SET sr.route_vehicle_id = NULL
			WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo route vehicles first
		$wpdb->query("DELETE rv FROM " . WLSM_ROUTE_VEHICLE . " rv
			JOIN " . WLSM_ROUTES . " r ON r.ID = rv.route_id
			WHERE r.school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo routes
		$wpdb->query("DELETE FROM " . WLSM_ROUTES . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo vehicles
		$wpdb->query("DELETE FROM " . WLSM_VEHICLES . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === HOSTEL CLEANUP ===
		// Delete demo rooms first (to handle foreign key constraints)
		$wpdb->query("DELETE r FROM " . WLSM_ROOMS . " r
			JOIN " . WLSM_HOSTELS . " h ON h.ID = r.hostel_id
			WHERE h.school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo hostels
		$wpdb->query("DELETE FROM " . WLSM_HOSTELS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === LIBRARY CLEANUP ===
		// Delete demo library cards first
		$wpdb->query("DELETE lc FROM " . WLSM_LIBRARY_CARDS . " lc
			JOIN " . WLSM_STUDENT_RECORDS . " sr ON sr.ID = lc.student_record_id
			JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
			JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
			WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");
		// Delete demo book issues next (to avoid foreign key issues)
		$wpdb->query("DELETE bi FROM " . WLSM_BOOKS_ISSUED . " bi JOIN " . WLSM_BOOKS . " b ON b.ID = bi.book_id WHERE b.school_id IN (" . $demo_school_ids_sql . ")");
		// Clean up orphaned book issues
		$wpdb->query("DELETE FROM " . WLSM_BOOKS_ISSUED . " WHERE book_id NOT IN (SELECT ID FROM " . WLSM_BOOKS . ")");
		// Delete demo books
		$wpdb->query("DELETE FROM " . WLSM_BOOKS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === ACADEMIC STRUCTURE CLEANUP ===
		// Delete demo subjects for demo class_schools
		$wpdb->query("DELETE s FROM " . WLSM_SUBJECTS . " s JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo sections for demo class_schools
		$wpdb->query("DELETE sec FROM " . WLSM_SECTIONS . " sec JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = sec.class_school_id WHERE cs.school_id IN (" . $demo_school_ids_sql . ")");

		// Delete demo fee types for demo classes
		$wpdb->query("DELETE f FROM " . WLSM_FEES . " f JOIN " . WLSM_CLASSES . " c ON c.ID = f.class_id WHERE c.label LIKE '%Demo%'");

		// Delete demo class_school relationships for demo schools
		$wpdb->query("DELETE FROM " . WLSM_CLASS_SCHOOL . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// === MAIN ENTITIES CLEANUP ===
		// Delete demo student records
		$wpdb->query("DELETE FROM " . WLSM_STUDENT_RECORDS . " WHERE enrollment_number LIKE 'DEMO%'");

		// Delete demo sessions
		$wpdb->query("DELETE FROM " . WLSM_SESSIONS . " WHERE label LIKE '%Demo%'");

		// Delete demo classes
		$wpdb->query("DELETE FROM " . WLSM_CLASSES . " WHERE label LIKE '%Demo%'");

		// Delete demo roles
		$wpdb->query("DELETE FROM " . WLSM_ROLES . " WHERE name LIKE '%Demo%'");

		// Delete demo medium data
		$wpdb->query("DELETE FROM " . WLSM_MEDIUM . " WHERE label LIKE '%Demo%'");

		// Delete demo student type data
		$wpdb->query("DELETE FROM " . WLSM_STUDENT_TYPE . " WHERE label LIKE '%Demo%'");

		// Delete demo settings for demo schools
		$wpdb->query("DELETE FROM " . WLSM_SETTINGS . " WHERE school_id IN (" . $demo_school_ids_sql . ")");

		// Delete all demo schools including "Intechno Academy Demo"
		$wpdb->query("DELETE FROM " . WLSM_SCHOOLS . " WHERE label LIKE '%Demo%'");

		// === USER ACCOUNT CLEANUP ===
		// Delete specific demo users and their associated data
		$demo_usernames = array('demo_teacher', 'demo_accountant', 'demo_receptionist', 'demo_librarian');
		foreach ($demo_usernames as $demo_username) {
			$demo_user = get_user_by('login', $demo_username);
			if ($demo_user) {
				$user_id = $demo_user->ID;

				// Delete staff records
				$wpdb->delete(WLSM_STAFF, array('user_id' => $user_id));

				// Delete admin records
				$staff_records = $wpdb->get_results($wpdb->prepare(
					"SELECT ID FROM " . WLSM_STAFF . " WHERE user_id = %d",
					$user_id
				));
				foreach ($staff_records as $staff_record) {
					$wpdb->delete(WLSM_ADMINS, array('staff_id' => $staff_record->ID));
				}

				// Delete WordPress user
				wp_delete_user($user_id);
			}
		}

		// Delete all WordPress users created for demo staff including school administrator
		$wpdb->query("DELETE FROM " . WLSM_USERS . " WHERE user_email LIKE '%.staff.demo@example.com' OR user_email LIKE '%.admin.demo@example.com' OR user_login = 'school_administrator'");

		// === MEDIA CLEANUP ===
		// Delete uploaded demo student photos
		if (!empty($demo_photo_ids)) {
			foreach ($demo_photo_ids as $photo_id) {
				if ($photo_id) {
					// Delete the WordPress attachment and its file
					wp_delete_attachment($photo_id, true);
				}
			}
		}

		// Delete uploaded demo school logos and signatures
		if (!empty($demo_logo_signature_ids)) {
			foreach ($demo_logo_signature_ids as $attachment_id) {
				if ($attachment_id) {
					// Delete the WordPress attachment and its file
					wp_delete_attachment($attachment_id, true);
				}
			}
		}

		// Clean up any orphaned demo attachments (fallback)
		$wpdb->query("DELETE FROM " . WLSM_POSTS . " WHERE post_title LIKE 'Demo Student Photo -%' AND post_type = 'attachment'");
		$wpdb->query("DELETE FROM " . WLSM_POSTS . " WHERE post_title LIKE 'Demo School Logo -%' AND post_type = 'attachment'");
		$wpdb->query("DELETE FROM " . WLSM_POSTS . " WHERE post_title LIKE 'Demo School Signature -%' AND post_type = 'attachment'");

		// === ADDITIONAL CLEANUP FOR NEWER FEATURES ===
		// Clean up any additional demo data that might have been missed
		// This provides a safety net for any orphaned records

		// Clean up orphaned records that might exist due to foreign key constraints
		$wpdb->query("DELETE FROM " . WLSM_ATTENDANCE . " WHERE student_record_id NOT IN (SELECT ID FROM " . WLSM_STUDENT_RECORDS . ")");
		$wpdb->query("DELETE FROM " . WLSM_LEAVES . " WHERE student_record_id IS NOT NULL AND student_record_id NOT IN (SELECT ID FROM " . WLSM_STUDENT_RECORDS . ")");
		$wpdb->query("DELETE FROM " . WLSM_LEAVES . " WHERE admin_id IS NOT NULL AND admin_id NOT IN (SELECT ID FROM " . WLSM_ADMINS . ")");
		$wpdb->query("DELETE FROM " . WLSM_STAFF_ATTENDANCE . " WHERE admin_id NOT IN (SELECT ID FROM " . WLSM_ADMINS . ")");
		$wpdb->query("DELETE FROM " . WLSM_HOMEWORK_SUBMISSION . " WHERE submission_id NOT IN (SELECT ID FROM " . WLSM_HOMEWORK . ")");
		$wpdb->query("DELETE FROM " . WLSM_EVENT_RESPONSES . " WHERE event_id NOT IN (SELECT ID FROM " . WLSM_EVENTS . ")");
		$wpdb->query("DELETE FROM " . WLSM_STUDENTS_SUBJECTS . " WHERE student_id NOT IN (SELECT ID FROM " . WLSM_STUDENT_RECORDS . ")");
		$wpdb->query("DELETE FROM " . WLSM_ADMIN_SUBJECT . " WHERE admin_id NOT IN (SELECT ID FROM " . WLSM_ADMINS . ")");
	}

	/**
	 * Create fixed demo login credentials for consistent testing
	 */
	private static function create_fixed_demo_credentials($school_id, $session_id) {
		global $wpdb;

		try {
			// Clean up any existing users with these usernames
			$existing_student_user = get_user_by('login', 'student1');
			if ($existing_student_user) {
				wp_delete_user($existing_student_user->ID);
			}

			$existing_parent_user = get_user_by('login', 'parent1');
			if ($existing_parent_user) {
				wp_delete_user($existing_parent_user->ID);
			}

			// Get the first student record from the demo data to link with student1 account
			$student_record = $wpdb->get_row($wpdb->prepare(
				"SELECT sr.*, cs.class_id
				 FROM " . WLSM_STUDENT_RECORDS . " sr
				 JOIN " . WLSM_SECTIONS . " sec ON sec.ID = sr.section_id
				 JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = sec.class_school_id
				 WHERE cs.school_id = %d AND sr.session_id = %d
				 ORDER BY sr.ID ASC LIMIT 1",
				$school_id, $session_id
			));

			if (!$student_record) {
				// If no student record found, skip creating fixed credentials
				return;
			}

			// Create student1 WordPress user account
			$student_user_data = array(
				'user_login' => 'student1',
				'user_email' => 'student1@example.com',
				'user_pass'  => '123456',
				'first_name' => explode(' ', $student_record->name)[0],
				'last_name'  => isset(explode(' ', $student_record->name)[1]) ? explode(' ', $student_record->name)[1] : '',
				'display_name' => $student_record->name,
				'role'       => 'subscriber'
			);

			$student_user_id = wp_insert_user($student_user_data);
			if (is_wp_error($student_user_id)) {
				throw new Exception('Failed to create student1 user: ' . $student_user_id->get_error_message());
			}

			// Create parent1 WordPress user account
			$parent_user_data = array(
				'user_login' => 'parent1',
				'user_email' => 'parent1@example.com',
				'user_pass'  => '123456',
				'first_name' => explode(' ', $student_record->father_name)[0],
				'last_name'  => isset(explode(' ', $student_record->father_name)[1]) ? explode(' ', $student_record->father_name)[1] : '',
				'display_name' => $student_record->father_name,
				'role'       => 'subscriber'
			);

			$parent_user_id = wp_insert_user($parent_user_data);
			if (is_wp_error($parent_user_id)) {
				throw new Exception('Failed to create parent1 user: ' . $parent_user_id->get_error_message());
			}

			// Update the student record to link with both student and parent WordPress users
			$wpdb->update(
				WLSM_STUDENT_RECORDS,
				array(
					'user_id' => $student_user_id,
					'parent_user_id' => $parent_user_id,
					'updated_at' => current_time('mysql')
				),
				array('ID' => $student_record->ID)
			);

			// Store school and session info in parent user meta
			update_user_meta($parent_user_id, 'wlsm_school_id', $school_id);
			update_user_meta($parent_user_id, 'wlsm_session_id', $session_id);
			update_user_meta($parent_user_id, 'wlsm_student_id', $student_record->ID);

			// Ensure uniqueness by removing these credentials from any other student records
			$wpdb->query($wpdb->prepare(
				"UPDATE " . WLSM_STUDENT_RECORDS . " sr
				 JOIN " . WLSM_SECTIONS . " sec ON sec.ID = sr.section_id
				 JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = sec.class_school_id
				 SET sr.user_id = NULL, sr.parent_user_id = NULL, sr.updated_at = %s
				 WHERE (sr.user_id IS NOT NULL OR sr.parent_user_id IS NOT NULL)
				 AND sr.ID != %d
				 AND cs.school_id = %d",
				current_time('mysql'), $student_record->ID, $school_id
			));

		} catch (Exception $e) {
			// Log error but don't fail the entire demo generation
			error_log('Failed to create fixed demo credentials: ' . $e->getMessage());
		}
	}

	/**
	 * Generate demo data for testing purposes
	 */
	public static function generate_demo_data() {
		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			// Clean up existing demo data first
			self::cleanup_demo_data();

			// Sample data arrays
			$school_names = array(
				'Intechno Academy Demo',
			);

			$class_names = array(
				'Grade 1 Demo', 'Grade 2 Demo', 'Grade 3 Demo',
				'Grade 4 Demo', 'Grade 5 Demo', 'Grade 6 Demo', 'Grade 7 Demo', 'Grade 8 Demo'
			);

			$section_names = array('A', 'B', 'C', 'D', 'E');

			$subjects = array(
				'Mathematics', 'English', 'Science', 'History', 'Geography', 'Physics', 'Chemistry',
				'Biology', 'Computer Science', 'Art', 'Music', 'Physical Education'
			);

			$fee_type_names = array(
				'Tuition Fee', 'Library Fee', 'Sports Fee', 'Laboratory Fee', 'Transport Fee',
				'Examination Fee', 'Activity Fee', 'Uniform Fee', 'Books Fee', 'Miscellaneous Fee'
			);

			$first_names = array(
				'John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Robert', 'Emma', 'William', 'Olivia',
				'James', 'Sophia', 'Benjamin', 'Isabella', 'Lucas', 'Mia', 'Henry', 'Charlotte', 'Alexander', 'Amelia'
			);

			$last_names = array(
				'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
				'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'
			);

			$medium_names = array(
				'English Demo', 'Hindi Demo', 'Spanish Demo', 'French Demo', 'German Demo', 'Urdu Demo'
			);

			$student_type_names = array(
				'Regular Demo', 'Private Demo', 'RTE Demo', 'Scholarship Demo', 'Transfer Demo', 'Other Demo'
			);

			$role_names = array(
				'Teacher Demo', 'Accountant Demo', 'Librarian Demo', 'receptionist Demo'
			);

			$staff_designations = array(
				'Principal', 'Vice Principal', 'Head Teacher', 'Senior Teacher', 'Junior Teacher', 'Accountant',
				'Librarian', 'Lab Assistant', 'Administrative Assistant', 'receptionist'
			);

			// Library demo data
			$book_titles = array(
				'The Art of Mathematics', 'Science in Daily Life', 'World History: A Complete Guide',
				'English Literature Classics', 'Introduction to Programming', 'Biology Fundamentals',
				'Chemistry Made Easy', 'Physics in Action', 'Environmental Science Today',
				'Computer Science Basics'
			);

			$book_authors = array(
				'Dr. Robert Smith', 'Prof. Sarah Johnson', 'Dr. Michael Brown',
				'Prof. Emily Williams', 'Dr. James Wilson', 'Prof. Jennifer Davis',
				'Dr. William Thompson', 'Prof. Elizabeth Taylor'
			);

			$book_subjects = array(
				'Mathematics', 'Science', 'History', 'Literature', 'Computer Science',
				'Biology', 'Chemistry', 'Physics', 'Environmental Science'
			);

			$rack_numbers = array('A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1', 'D2');


			$expense_categories = array(
				'Office Supplies', 'Utilities', 'Teaching Materials', 'Maintenance',
				'Furniture', 'Technology Equipment', 'Building Repairs', 'Staff Development',
				'Sports Equipment', 'Library Resources'
			);

			$income_categories = array(
				'Donations', 'Facility Rental', 'Book Sales', 'Uniform Sales',
				'Event Tickets', 'Cafeteria Income', 'Transportation Fees', 'Library Fines',
				'Miscellaneous Income', 'Activity Fees'
			);

			$created_schools = array();
			$created_sessions = array();
			$created_classes = array();
			$created_subjects = array();
			$created_sections = array();
			// Track class_school IDs per school to scope exams to current session only
			$current_session_class_schools_by_school = array();

			// Generate 1 school
			for ($i = 0; $i < 1; $i++) {
				$school_name = $school_names[$i % count($school_names)];
				$school_data = array(
					'label'       => $school_name,
					'phone'       => '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
					'email'       => 'school' . ($i + 1) . '@intechno.edu.com',
					'address'     => rand(100, 999) . ' 124 Lakeview Road, Brookfield, New City 458201',
					'created_at'  => current_time('mysql')
				);

				// Reuse existing protected demo school if present to avoid duplicate label errors
				$existing_school_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM " . WLSM_SCHOOLS . " WHERE label = %s",
						$school_name
					)
				);

				if ($existing_school_id) {
					$school_id = (int) $existing_school_id;
				} else {
					$result = $wpdb->insert(WLSM_SCHOOLS, $school_data);
					if ($result === false) {
						throw new Exception('Failed to insert school: ' . $wpdb->last_error);
					}
					$school_id = $wpdb->insert_id;

					if (!$school_id) {
						throw new Exception('Failed to get school ID after insert');
					}
				}

				$created_schools[] = $school_id;

				// Create expense categories for this school
				foreach ($expense_categories as $category) {
					$expense_category_data = array(
						'label'      => $category . ' Demo',
						'school_id'  => $school_id,
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_EXPENSE_CATEGORIES, $expense_category_data);
					if ($result === false) {
						throw new Exception('Failed to insert expense category: ' . $wpdb->last_error);
					}
					$expense_category_id = $wpdb->insert_id;

					// Add 3 sample expenses for this category in last 3 months
					for($j = 1; $j <= 3; $j++) {
						$expense_amount = rand(1000, 50000);
						$expense_date = date('Y-m-d', strtotime("-$j month"));

						$expense_data = array(
							'label'               => $category . ' Expense ' . $j . ' Demo',
							'invoice_number'      => 'EXP' . rand(100, 999) . date('Y'),
							'amount'              => $expense_amount,
							'expense_date'        => $expense_date,
							'note'                => 'Demo expense record for ' . $category,
							'expense_category_id' => $expense_category_id,
							'school_id'          => $school_id,
							'created_at'         => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_EXPENSES, $expense_data);
						if ($result === false) {
							throw new Exception('Failed to insert expense: ' . $wpdb->last_error);
						}
					}
				}

				// Create income categories for this school
				foreach ($income_categories as $category) {
					$income_category_data = array(
						'label'      => $category . ' Demo',
						'school_id'  => $school_id,
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_INCOME_CATEGORIES, $income_category_data);
					if ($result === false) {
						throw new Exception('Failed to insert income category: ' . $wpdb->last_error);
					}
					$income_category_id = $wpdb->insert_id;

					// Add 3 sample income records for this category in last 3 months
					for($j = 1; $j <= 3; $j++) {
						$income_amount = rand(5000, 100000);
						$income_date = date('Y-m-d', strtotime("-$j month"));

						$income_data = array(
							'label'               => $category . ' Income ' . $j . ' Demo',
							'invoice_number'      => 'INC' . rand(100, 999) . date('Y'),
							'amount'              => $income_amount,
							'income_date'         => $income_date,
							'note'                => 'Demo income record for ' . $category,
							'income_category_id'  => $income_category_id,
							'school_id'          => $school_id,
							'created_at'         => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_INCOME, $income_data);
						if ($result === false) {
							throw new Exception('Failed to insert income: ' . $wpdb->last_error);
						}
					}
				}

				// Upload demo school logo and signature
				$school_logo_id = null;
				$school_signature_id = null;

				// Upload school logo
				$demo_logo_path = WLSM_PLUGIN_DIR_PATH . 'assets/demo/school logo.jpg';
				if (file_exists($demo_logo_path)) {
					$upload_dir = wp_upload_dir();
					$upload_path = $upload_dir['path'];
					$upload_url = $upload_dir['url'];

					$logo_filename = 'demo_school_logo_' . $school_id . '.jpg';
					$logo_file_path = $upload_path . '/' . $logo_filename;
					$logo_file_url = $upload_url . '/' . $logo_filename;

					if (copy($demo_logo_path, $logo_file_path)) {
						$wp_filetype = wp_check_filetype($logo_filename, null);

						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => 'Demo School Logo - ' . $school_name,
							'post_content'   => 'Demo school logo for ' . $school_name,
							'post_status'    => 'inherit'
						);

						$school_logo_id = wp_insert_attachment($attachment, $logo_file_path);

						if (!is_wp_error($school_logo_id)) {
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$attach_data = wp_generate_attachment_metadata($school_logo_id, $logo_file_path);
							wp_update_attachment_metadata($school_logo_id, $attach_data);
						} else {
							$school_logo_id = null;
						}
					}
				}

				// Upload school signature
				$demo_signature_path = WLSM_PLUGIN_DIR_PATH . 'assets/demo/signature.png';
				if (file_exists($demo_signature_path)) {
					$upload_dir = wp_upload_dir();
					$upload_path = $upload_dir['path'];
					$upload_url = $upload_dir['url'];

					$signature_filename = 'demo_school_signature_' . $school_id . '.png';
					$signature_file_path = $upload_path . '/' . $signature_filename;
					$signature_file_url = $upload_url . '/' . $signature_filename;

					if (copy($demo_signature_path, $signature_file_path)) {
						$wp_filetype = wp_check_filetype($signature_filename, null);

						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => 'Demo School Signature - ' . $school_name,
							'post_content'   => 'Demo school signature for ' . $school_name,
							'post_status'    => 'inherit'
						);

						$school_signature_id = wp_insert_attachment($attachment, $signature_file_path);

						if (!is_wp_error($school_signature_id)) {
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$attach_data = wp_generate_attachment_metadata($school_signature_id, $signature_file_path);
							wp_update_attachment_metadata($school_signature_id, $attach_data);
						} else {
							$school_signature_id = null;
						}
					}
				}

				// Save school logo and signature to settings (upsert to avoid duplicate key)
				if ($school_logo_id || $school_signature_id) {
					$general_settings = array(
						'school_logo'     => $school_logo_id,
						'school_signature' => $school_signature_id,
						'school_currency' => 'USD',
						'date_format'     => 'Y-m-d',
						'time_format'     => 'H:i'
					);

					$serialized_settings = serialize($general_settings);

					// Check if a 'general' settings row already exists for this school
					$existing_setting_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT ID FROM " . WLSM_SETTINGS . " WHERE school_id = %d AND setting_key = %s",
							$school_id,
							'general'
						)
					);

					if ($existing_setting_id) {
						$result = $wpdb->update(
							WLSM_SETTINGS,
							array('setting_value' => $serialized_settings),
							array('ID' => $existing_setting_id)
						);
						if ($result === false) {
							throw new Exception('Failed to update general settings: ' . $wpdb->last_error);
						}
					} else {
						$setting_data = array(
							'school_id'     => $school_id,
							'setting_key'   => 'general',
							'setting_value' => $serialized_settings
						);

						$result = $wpdb->insert(WLSM_SETTINGS, $setting_data);
						if ($result === false) {
							throw new Exception('Failed to insert general settings: ' . $wpdb->last_error);
						}
					}
				}

				// Generate 3 medium types for each school
				for ($m = 0; $m < 3; $m++) {
					$medium_name = $medium_names[$m % count($medium_names)];
					$medium_data = array(
						'label'      => $medium_name,
						'school_id'  => $school_id,
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_MEDIUM, $medium_data);
					if ($result === false) {
						throw new Exception('Failed to insert medium: ' . $wpdb->last_error);
					}
				}

				// Generate 3 student types for each school
				for ($st = 0; $st < 3; $st++) {
					$student_type_name = $student_type_names[$st % count($student_type_names)];
					$student_type_data = array(
						'label'      => $student_type_name,
						'school_id'  => $school_id,
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_STUDENT_TYPE, $student_type_data);
					if ($result === false) {
						throw new Exception('Failed to insert student type: ' . $wpdb->last_error);
					}
				}

				// Generate 4 roles for each school
				$basic_permissions = array(
					'view_students', 'view_attendance', 'view_notices', 'view_events'
				);
				$teacher_permissions = array(
					'view_students', 'view_attendance', 'add_attendance', 'edit_attendance',
					'view_notices', 'add_notices', 'edit_notices', 'view_events', 'add_events',
					'view_subjects', 'add_subjects', 'edit_subjects', 'view_exams', 'add_exams',
					'view_study_materials', 'add_study_materials', 'edit_study_materials'
				);
				$accountant_permissions = array(
					'view_students', 'view_fees', 'add_fees', 'edit_fees', 'delete_fees',
					'view_expenses', 'add_expenses', 'edit_expenses', 'delete_expenses',
					'view_invoices', 'add_invoices', 'edit_invoices', 'delete_invoices',
					'view_payments', 'add_payments', 'edit_payments', 'delete_payments'
				);
				$librarian_permissions = array(
					'view_students', 'view_books', 'add_books', 'edit_books', 'delete_books',
					'view_book_issues', 'add_book_issues', 'edit_book_issues', 'return_books',
					'view_notices', 'add_notices', 'edit_notices'
				);
				$receptionist_permissions = array(
					'view_students', 'view_inquiries', 'add_inquiries', 'edit_inquiries',
					'view_notices', 'add_notices', 'edit_notices', 'view_events', 'add_events',
					'view_attendance', 'manage_admissions'
				);

				$permissions_by_role = array(
					$teacher_permissions,      // Teacher Demo
					$accountant_permissions,   // Accountant Demo
					$librarian_permissions,    // Librarian Demo
					$receptionist_permissions  // receptionist Demo
				);

				for ($r = 0; $r < 4; $r++) {
					$role_name = $role_names[$r % count($role_names)];
					$permissions = $permissions_by_role[$r % count($permissions_by_role)];

					$role_data = array(
						'name'        => $role_name,
						'permissions' => serialize($permissions),
						'school_id'   => $school_id,
						'created_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_ROLES, $role_data);
					if ($result === false) {
						throw new Exception('Failed to insert role: ' . $wpdb->last_error);
					}
				}

				// Create specific demo users with predefined credentials
				$demo_users = array(
					array(
						'username' => 'demo_teacher',
						'password' => '123456',
						'email' => 'demo_teacher@example.com',
						'first_name' => 'Demo',
						'last_name' => 'Teacher',
						'display_name' => 'Demo Teacher',
						'role_type' => 'teacher',
						'permissions' => $teacher_permissions
					),
					array(
						'username' => 'demo_accountant',
						'password' => '123456',
						'email' => 'demo_accountant@example.com',
						'first_name' => 'Demo',
						'last_name' => 'Accountant',
						'display_name' => 'Demo Accountant',
						'role_type' => 'accountant',
						'permissions' => $accountant_permissions
					),
					array(
						'username' => 'demo_receptionist',
						'password' => '123456',
						'email' => 'demo_receptionist@example.com',
						'first_name' => 'Demo',
						'last_name' => 'Receptionist',
						'display_name' => 'Demo Receptionist',
						'role_type' => 'receptionist',
						'permissions' => $receptionist_permissions
					),
					array(
						'username' => 'demo_librarian',
						'password' => '123456',
						'email' => 'demo_librarian@example.com',
						'first_name' => 'Demo',
						'last_name' => 'Librarian',
						'display_name' => 'Demo Librarian',
						'role_type' => 'librarian',
						'permissions' => $librarian_permissions
					)
				);

				// Process each demo user
				foreach ($demo_users as $demo_user) {
					// Check if user already exists
					$existing_user = get_user_by('login', $demo_user['username']);

					if ($existing_user) {
						// Delete existing user and all associated data
						$user_id = $existing_user->ID;

						// Delete staff records
						$wpdb->delete(WLSM_STAFF, array('user_id' => $user_id));

						// Delete admin records
						$staff_records = $wpdb->get_results($wpdb->prepare(
							"SELECT ID FROM " . WLSM_STAFF . " WHERE user_id = %d",
							$user_id
						));
						foreach ($staff_records as $staff_record) {
							$wpdb->delete(WLSM_ADMINS, array('staff_id' => $staff_record->ID));
						}

						// Delete WordPress user
						wp_delete_user($user_id);
					}

					// Create new WordPress user
					$user_data = array(
						'user_login' => $demo_user['username'],
						'user_email' => $demo_user['email'],
						'user_pass'  => $demo_user['password'],
						'first_name' => $demo_user['first_name'],
						'last_name'  => $demo_user['last_name'],
						'display_name' => $demo_user['display_name'],
						'role'       => 'subscriber'
					);

					$user_id = wp_insert_user($user_data);
					if (is_wp_error($user_id)) {
						throw new Exception('Failed to create demo user ' . $demo_user['username'] . ': ' . $user_id->get_error_message());
					}

					// Create staff record
					$staff_data = array(
						'role'        => 'employee',
						'permissions' => serialize($demo_user['permissions']),
						'school_id'   => $school_id,
						'user_id'     => $user_id,
						'created_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_STAFF, $staff_data);
					$staff_id = $wpdb->insert_id;
					if ($result === false) {
						throw new Exception('Failed to insert staff record for ' . $demo_user['username'] . ': ' . $wpdb->last_error);
					}

					// Create admin record for each demo user
					$admin_data = array(
						'name'        => $demo_user['display_name'],
						'designation' => ucfirst($demo_user['role_type']),
						'phone'       => '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
						'email'       => $demo_user['email'],
						'address'     => 'Demo ' . ucfirst($demo_user['role_type']) . ' Office',
						'salary'      => rand(3000, 8000),
						'joining_date' => date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')),
						'staff_id'     => $staff_id,
						'created_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_ADMINS, $admin_data);
					if ($result === false) {
						throw new Exception('Failed to insert admin record for ' . $demo_user['username'] . ': ' . $wpdb->last_error);
					}

					// Assign school and session to demo user
					update_user_meta($user_id, 'wlsm_school_id', $school_id);
					// Note: Session will be assigned after sessions are created below
				}

				// Check for current session first
				$first_session_id = null;
				$current_demo_session_id = null;
				$current_date = current_time('Y-m-d');

				// Calculate appropriate session years based on current date
				$current_year = intval(date('Y'));
				$current_month = intval(date('m'));
				$start_year = ($current_month < 4) ? $current_year - 1 : $current_year;
				$end_year = $start_year + 1;

				// Try to get existing current session
				$current_session_id = get_option('wlsm_current_session');
				if ($current_session_id) {
					$session = $wpdb->get_row($wpdb->prepare(
						'SELECT ID, label, start_date, end_date FROM ' . WLSM_SESSIONS . ' WHERE ID = %d',
						$current_session_id
					));
					if ($session) {
						$session_id = $session->ID;
						$first_session_id = $session_id;
						$current_demo_session_id = $session_id;
						if (!in_array($session_id, $created_sessions)) {
							$created_sessions[] = $session_id;
						}
					}
				}

				// If no current session found or invalid, create a new one
				if (!isset($session_id)) {
					$session_start_date = $start_year . '-04-01';
					$session_end_date = $end_year . '-03-31';

					$session_data = array(
						'label'       => $start_year . '-' . $end_year . ' Demo S' . $school_id,
						'start_date'  => $session_start_date,
						'end_date'    => $session_end_date,
						'created_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_SESSIONS, $session_data);
					if ($result === false) {
						throw new Exception('Failed to insert session: ' . $wpdb->last_error);
					}
					$session_id = $wpdb->insert_id;

					if (!$session_id) {
						throw new Exception('Failed to get session ID after insert');
					}

					// Capture the session ID and update option
					$first_session_id = $session_id;
					$current_demo_session_id = $session_id;
					update_option('wlsm_current_session', $session_id);
					$created_sessions[] = $session_id;
				}

				// Generate 5 classes for each session if they don't exist
				$existing_classes = $wpdb->get_var($wpdb->prepare(
					'SELECT COUNT(*) FROM ' . WLSM_CLASS_SCHOOL . ' WHERE school_id = %d',
					$school_id
				));

				if ($existing_classes == 0) {
					for ($k = 0; $k < 5; $k++) {
						$class_name = $class_names[$k % count($class_names)] . ' S' . $session_id;
						$class_data = array(
							'label'      => $class_name,
							'created_at' => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_CLASSES, $class_data);
						if ($result === false) {
							throw new Exception('Failed to insert class: ' . $wpdb->last_error);
						}
						$class_id = $wpdb->insert_id;

						if (!$class_id) {
							throw new Exception('Failed to get class ID after insert');
						}

						// Create class school relationship
						$class_school_data = array(
							'class_id'   => $class_id,
							'school_id'  => $school_id,
							'created_at' => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_CLASS_SCHOOL, $class_school_data);
						if ($result === false) {
							throw new Exception('Failed to insert class school relationship: ' . $wpdb->last_error);
						}
						$class_school_id = $wpdb->insert_id;

						if (!$class_school_id) {
							throw new Exception('Failed to get class school ID after insert');
						}

						$created_classes[] = $class_school_id;
						// Record class_school IDs for the current demo session only to use in exam linking
						if ($current_demo_session_id && $session_id == $current_demo_session_id) {
							if (!isset($current_session_class_schools_by_school[$school_id])) {
								$current_session_class_schools_by_school[$school_id] = array();
							}
						$current_session_class_schools_by_school[$school_id][] = $class_school_id;
						}

						// Generate expense categories if not already created
						if (empty($created_expense_categories)) {
							$expense_categories = array(
								'Salary',
								'Maintenance',
								'Utilities',
								'Supplies',
								'Equipment'
							);

							foreach ($expense_categories as $category) {
								$wpdb->insert(
									WLSM_EXPENSE_CATEGORIES,
									array(
										'label' => $category,
										'school_id' => $school_id,
										'created_at' => current_time('mysql')
									)
								);
								$created_expense_categories[] = $wpdb->insert_id;
							}
						}

						// Generate income categories if not already created
						if (empty($created_income_categories)) {
							$income_categories = array(
								'Donations',
								'Fundraising',
								'Grants',
								'Rental',
								'Other Income'
							);

							foreach ($income_categories as $category) {
								$wpdb->insert(
									WLSM_INCOME_CATEGORIES,
									array(
										'label' => $category,
										'school_id' => $school_id,
										'created_at' => current_time('mysql')
									)
								);
								$created_income_categories[] = $wpdb->insert_id;
							}

							// Generate demo expenses for the last 6 months
							$current_date = current_time('Y-m-d');
							$last_6_months = strtotime('-6 months', strtotime($current_date));

							for ($i = 0; $i < 20; $i++) {
								$random_date = date('Y-m-d', rand($last_6_months, strtotime($current_date)));
								$category_id = $created_expense_categories[array_rand($created_expense_categories)];
								$category = $wpdb->get_var($wpdb->prepare(
									'SELECT label FROM ' . WLSM_EXPENSE_CATEGORIES . ' WHERE ID = %d',
									$category_id
								));

								$wpdb->insert(
									WLSM_EXPENSES,
									array(
										'label' => $category . ' Expense - ' . date('M Y', strtotime($random_date)),
										'invoice_number' => 'EXP' . rand(1000, 9999),
										'amount' => rand(1000, 50000),
										'expense_date' => $random_date,
										'note' => 'Demo expense entry for ' . $category,
										'expense_category_id' => $category_id,
										'school_id' => $school_id,
										'session_id' => $session_id,
										'created_at' => current_time('mysql')
									)
								);
							}

							// Generate demo income entries
							for ($i = 0; $i < 15; $i++) {
								$random_date = date('Y-m-d', rand($last_6_months, strtotime($current_date)));
								$category_id = $created_income_categories[array_rand($created_income_categories)];
								$category = $wpdb->get_var($wpdb->prepare(
									'SELECT label FROM ' . WLSM_INCOME_CATEGORIES . ' WHERE ID = %d',
									$category_id
								));

								$wpdb->insert(
									WLSM_INCOME,
									array(
										'label' => $category . ' Income - ' . date('M Y', strtotime($random_date)),
										'invoice_number' => 'INC' . rand(1000, 9999),
										'amount' => rand(5000, 100000),
										'income_date' => $random_date,
										'note' => 'Demo income entry for ' . $category,
										'income_category_id' => $category_id,
										'school_id' => $school_id,
										'created_at' => current_time('mysql')
									)
								);
							}
						}

						// Generate 3 fee types for each class
						for ($ft = 0; $ft < 3; $ft++) {
							$fee_type_name = $fee_type_names[$ft % count($fee_type_names)];
							$periods = array('one-time', 'monthly', 'quarterly', 'annually');

							// Get student types from database for this school
							$student_types = $wpdb->get_results($wpdb->prepare(
								"SELECT label FROM " . WLSM_STUDENT_TYPE . " WHERE school_id = %d",
								$school_id
							));
							$student_type_labels = array();
							foreach ($student_types as $type) {
								$student_type_labels[] = $type->label;
							}

							$fee_type_data = array(
								'label'               => $fee_type_name,
								'amount'              => rand(100, 1000),
								'period'              => $periods[array_rand($periods)],
								'school_id'           => $school_id,
								'class_id'            => $class_id,
								'student_type'        => serialize($student_type_labels),
								'active_on_admission' => ($ft % 2 == 0) ? 1 : 0,
								'active_on_dashboard' => ($ft % 3 == 0) ? 1 : 0,
								'created_at'          => current_time('mysql')
							);

							$result = $wpdb->insert(WLSM_FEES, $fee_type_data);
							if ($result === false) {
								throw new Exception('Failed to insert fee type: ' . $wpdb->last_error);
							}
							$fee_type_id = $wpdb->insert_id;

							// Generate concession types if not already created for this school
							if (empty($created_concession_types_by_school[$school_id])) {
								$created_concession_types_by_school[$school_id] = array();
								$concession_types = array(
									array('name' => 'Sibling Discount', 'type' => 'percentage', 'amount' => 15),
									array('name' => 'Staff Ward', 'type' => 'percentage', 'amount' => 50),
									array('name' => 'Merit Scholarship', 'type' => 'percentage', 'amount' => 25),
									array('name' => 'Financial Aid', 'type' => 'fixed_amount', 'amount' => 2000),
									array('name' => 'Sports Quota', 'type' => 'percentage', 'amount' => 20)
								);

								foreach ($concession_types as $concession) {
									$concession_data = array(
										'concession_name' => $concession['name'],
										'concession_type' => $concession['type'],
										'percentage_value' => $concession['type'] === 'percentage' ? $concession['amount'] : null,
										'fixed_amount' => $concession['type'] === 'fixed_amount' ? $concession['amount'] : null,
										'is_active' => 1,
										'school_id' => $school_id,
										'class_id' => $class_id, // Add class ID for the concession
										'created_at' => current_time('mysql')
									);

									$result = $wpdb->insert(WLSM_CONCESSION_TYPES, $concession_data);
									if ($result === false) {
										throw new Exception('Failed to insert concession type: ' . $wpdb->last_error);
									}
									$concession_type_id = $wpdb->insert_id;
									$created_concession_types_by_school[$school_id][] = $concession_type_id;

									// Map concession type to fee type
									$concession_fee_mapping = array(
										'concession_type_id' => $concession_type_id,
										'fee_type_id' => $fee_type_id,
										'created_at' => current_time('mysql')
									);

									$result = $wpdb->insert(WLSM_CONCESSION_FEE_MAPPINGS, $concession_fee_mapping);
									if ($result === false) {
										throw new Exception('Failed to insert concession fee mapping: ' . $wpdb->last_error);
									}
								}
							} else {
								// Map existing concession types to new fee type
								foreach ($created_concession_types_by_school[$school_id] as $concession_type_id) {
									$concession_fee_mapping = array(
										'concession_type_id' => $concession_type_id,
										'fee_type_id' => $fee_type_id,
										'created_at' => current_time('mysql')
									);

									$result = $wpdb->insert(WLSM_CONCESSION_FEE_MAPPINGS, $concession_fee_mapping);
									if ($result === false) {
										throw new Exception('Failed to insert concession fee mapping: ' . $wpdb->last_error);
									}
								}
							}
						}

						$first_section_id = null;

						// Generate 2 sections for each class
						for ($s = 0; $s < 2; $s++) {
							$section_name = $section_names[$s % count($section_names)];
							$section_data = array(
								'label'           => $section_name,
								'class_school_id' => $class_school_id,
								'created_at'      => current_time('mysql')
							);

							$result = $wpdb->insert(WLSM_SECTIONS, $section_data);
							if ($result === false) {
								throw new Exception('Failed to insert section: ' . $wpdb->last_error);
							}
							$section_id = $wpdb->insert_id;

							if (!$section_id) {
								throw new Exception('Failed to get section ID after insert');
							}

							// Store section ID with school ID for later routine assignment
							$created_sections[] = array(
								'id' => $section_id,
								'school_id' => $school_id,
								'class_school_id' => $class_school_id,
								'name' => $section_name
							);

							// Store the first section ID to set as default
							if ($s == 0) {
								$first_section_id = $section_id;
							}

							// Generate 5 students for each section
							for ($st = 0; $st < 5; $st++) {
								$first_name = $first_names[array_rand($first_names)];
								$last_name = $last_names[array_rand($last_names)];
								$enrollment_number = 'DEMO' . str_pad(($i * 1000 + $k * 100 + $s * 10 + $st), 6, '0', STR_PAD_LEFT);

								// Generate comprehensive student data
								$genders = array('male', 'female');
								$blood_groups = array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
								$religions = array('Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist', 'Jain', 'Other');
								$castes = array('General', 'OBC', 'SC', 'ST', 'Other');
								$cities = array('New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose');
								$states = array('California', 'Texas', 'Florida', 'New York', 'Pennsylvania', 'Illinois', 'Ohio', 'Georgia', 'North Carolina', 'Michigan');
								$countries = array('United States', 'Canada', 'United Kingdom', 'Australia', 'India', 'Germany', 'France', 'Japan', 'Brazil', 'Mexico');
								$occupations = array('Engineer', 'Doctor', 'Teacher', 'Business', 'Government Employee', 'Lawyer', 'Accountant', 'Manager', 'Consultant', 'Other');

								$gender = $genders[array_rand($genders)];
								$age = rand(6, 18); // School age
								$dob = date('Y-m-d', strtotime('-' . $age . ' years -' . rand(0, 365) . ' days'));

								// Copy demo profile image to uploads directory and create WordPress attachment
								$photo_id = null;
								$demo_photo_number = ($st % 12) + 1; // Cycle through available profile images (1-12)
								$demo_photo_path = WLSM_PLUGIN_DIR_PATH . 'assets/demo/profile (' . $demo_photo_number . ').jpeg';

								if (file_exists($demo_photo_path)) {
									// Get WordPress upload directory
									$upload_dir = wp_upload_dir();
									$upload_path = $upload_dir['path'];
									$upload_url = $upload_dir['url'];

									// Create unique filename
									$filename = 'demo_student_' . $enrollment_number . '_profile.jpeg';
									$file_path = $upload_path . '/' . $filename;
									$file_url = $upload_url . '/' . $filename;

									// Copy file to uploads directory
									if (copy($demo_photo_path, $file_path)) {
										// Get file type
										$wp_filetype = wp_check_filetype($filename, null);

										// Create attachment array
										$attachment = array(
											'post_mime_type' => $wp_filetype['type'],
											'post_title'     => 'Demo Student Photo - ' . $first_name . ' ' . $last_name,
											'post_content'   => 'Demo profile photo for ' . $first_name . ' ' . $last_name,
											'post_status'    => 'inherit'
										);

										// Insert attachment
										$photo_id = wp_insert_attachment($attachment, $file_path);

										if (!is_wp_error($photo_id)) {
											// Generate metadata for attachment
											require_once(ABSPATH . 'wp-admin/includes/image.php');
											$attach_data = wp_generate_attachment_metadata($photo_id, $file_path);
											wp_update_attachment_metadata($photo_id, $attach_data);
										} else {
											$photo_id = null;
										}
									}
								}

								$student_record_data = array(
									'session_id'        => $session_id,
									'section_id'        => $section_id,
									'roll_number'       => $st + 1,
									'name'              => $first_name . ' ' . $last_name,
									'enrollment_number' => $enrollment_number,
									'admission_number'  => $enrollment_number,
									'gender'            => $gender,
									'dob'               => $dob,
									'phone'             => '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
									'email'             => strtolower($first_name . '.' . $last_name) . '@example.com',
									'address'           => rand(100, 999) . ' Student Street',
									'city'              => $cities[array_rand($cities)],
									'state'             => $states[array_rand($states)],
									'country'           => $countries[array_rand($countries)],
									'admission_date'    => date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')),
									'religion'          => $religions[array_rand($religions)],
									'caste'             => $castes[array_rand($castes)],
									'blood_group'       => $blood_groups[array_rand($blood_groups)],
									'father_name'       => $first_names[array_rand($first_names)] . ' ' . $last_name,
									'mother_name'       => array('Mary', 'Sarah', 'Jennifer', 'Lisa', 'Karen', 'Nancy', 'Betty', 'Helen', 'Sandra', 'Maria')[array_rand(array('Mary', 'Sarah', 'Jennifer', 'Lisa', 'Karen', 'Nancy', 'Betty', 'Helen', 'Sandra', 'Maria'))] . ' ' . $last_name,
									'father_phone'      => '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
									'mother_phone'      => '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
									'father_occupation' => $occupations[array_rand($occupations)],
									'mother_occupation' => $occupations[array_rand($occupations)],
									'photo_id'          => $photo_id,
									'created_at'        => current_time('mysql')
								);

								$result = $wpdb->insert(WLSM_STUDENT_RECORDS, $student_record_data);
								if ($result === false) {
									throw new Exception('Failed to insert student record: ' . $wpdb->last_error);
								}

								$student_id = $wpdb->insert_id;
								if (!$student_id) {
									throw new Exception('Failed to get student ID after insert');
								}

								// Randomly assign concessions to ~30% of students
								if (rand(1, 100) <= 30 && isset($created_concession_types_by_school[$school_id])) {
									$concession_type_id = $created_concession_types_by_school[$school_id][array_rand($created_concession_types_by_school[$school_id])];

									$student_concession_data = array(
										'student_record_id' => $student_id,
										'concession_type_id' => $concession_type_id,
										'session_id' => $session_id,
										'school_id' => $school_id,
										'status' => 'approved',
										'application_date' => current_time('mysql'),
										'approval_date' => current_time('mysql'),
										'remarks' => 'Demo data concession',
										'created_at' => current_time('mysql')
									);

									$result = $wpdb->insert(WLSM_STUDENT_CONCESSION, $student_concession_data);
									if ($result === false) {
										throw new Exception('Failed to insert student concession: ' . $wpdb->last_error);
									}
								}

								// Generate admission invoice for the student
								self::generate_admission_invoice_for_student($student_id, $school_id, $current_demo_session_id);
							}
						}

						// Update the class_school record with the default section ID
						if ($first_section_id) {
							$wpdb->update(
								WLSM_CLASS_SCHOOL,
								array('default_section_id' => $first_section_id, 'updated_at' => current_time('mysql')),
								array('ID' => $class_school_id)
							);
						}

						// Generate 5 subjects for each class
						for ($sub = 0; $sub < 5; $sub++) {
							$subject_name = $subjects[$sub % count($subjects)];
							$subject_data = array(
								'class_school_id' => $class_school_id,
								'label'           => $subject_name,
								'code'            => strtoupper(substr($subject_name, 0, 3)),
								'type'            => rand(1, 3) == 1 ? 'theory' : 'practical',
								'created_at'      => current_time('mysql')
							);

							$result = $wpdb->insert(WLSM_SUBJECTS, $subject_data);
							if ($result === false) {
								throw new Exception('Failed to insert subject: ' . $wpdb->last_error);
							}
							$subject_id = $wpdb->insert_id;

							if (!$subject_id) {
								throw new Exception('Failed to get subject ID after insert');
							}

							// Store subject ID with school ID for later staff assignment
							$created_subjects[] = array(
								'id' => $subject_id,
								'school_id' => $school_id,
								'name' => $subject_name
							);
						}
					}
				}

				// Generate library demo data
				// Add 10 books for each school
				for ($b = 0; $b < 10; $b++) {
					$title = $book_titles[array_rand($book_titles)];
					$author = $book_authors[array_rand($book_authors)];
					$subject = $book_subjects[array_rand($book_subjects)];
					$rack = $rack_numbers[array_rand($rack_numbers)];

					// Generate ISBN-13 (demo format)
					$isbn = '978' . rand(0, 9) . rand(100000, 999999) . rand(100, 999);

					$book_data = array(
						'title' => $title . ' - Volume ' . ($b + 1),
						'author' => $author,
						'subject' => $subject,
						'description' => 'Demo book for ' . $subject . ' curriculum',
						'rack_number' => $rack,
						'book_number' => 'BK-' . str_pad($b + 1, 3, '0', STR_PAD_LEFT),
						'isbn_number' => $isbn,
						'price' => rand(10, 100) * 0.99,
						'quantity' => rand(5, 20),
						'school_id' => $school_id,
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_BOOKS, $book_data);
					if ($result === false) {
						throw new Exception('Failed to insert book: ' . $wpdb->last_error);
					}
					$book_id = $wpdb->insert_id;

					// Create 2-3 book issues for each book
					$num_issues = rand(2, 3);

					// Get random student IDs from this school
					$student_ids = $wpdb->get_col($wpdb->prepare(
						"SELECT sr.ID FROM " . WLSM_STUDENT_RECORDS . " sr
						JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
						JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
						WHERE cs.school_id = %d ORDER BY RAND() LIMIT %d",
						$school_id,
						$num_issues
					));

					foreach ($student_ids as $student_id) {
						// Generate dates for book issue
						$issue_date = date('Y-m-d', strtotime('-' . rand(1, 30) . ' days'));
						$return_date = date('Y-m-d', strtotime($issue_date . ' +' . rand(7, 14) . ' days'));

						$book_issue_data = array(
							'book_id' => $book_id,
							'student_record_id' => $student_id,
							'quantity' => 1,
							'date_issued' => $issue_date,
							'return_date' => $return_date,
							'returned_at' => rand(0, 1) ? $return_date : null,
							'created_at' => $issue_date,
							'updated_at' => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_BOOKS_ISSUED, $book_issue_data);
						if ($result === false) {
							throw new Exception('Failed to insert book issue: ' . $wpdb->last_error);
						}
					}
				}

				// Generate transport demo data
                // Define transport arrays
                $vehicle_models = array(
                    'Volvo School Bus', 'Ashok Leyland Bus', 'Tata School Bus',
                    'Force Traveller', 'Mahindra Tourister', 'Eicher Skyline',
                    'Swaraj Mazda Bus', 'Blue Bird Vision'
                );

                $route_names = array(
                    'North Route', 'South Route', 'East Route', 'West Route',
                    'Central Route', 'Northwest Loop', 'Southeast Loop', 'Downtown Express'
                );

                $route_periods = array(
                    'Morning & Evening', 'Morning Only', 'Evening Only', 'Afternoon Only'
                );

                // Add 5 vehicles per school
                for ($v = 0; $v < 5; $v++) {
                    $vehicle_model = $vehicle_models[$v % count($vehicle_models)];
                    $vehicle_number = 'SCH-' . str_pad($v + 1, 2, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999);

                    $vehicle_data = array(
                        'vehicle_number' => $vehicle_number,
                        'vehicle_model' => $vehicle_model,
                        'driver_name' => 'Driver ' . ($v + 1),
                        'driver_phone' => '+1' . rand(100, 999) . rand(100, 999) . rand(1000, 9999),
                        'note' => 'Demo vehicle for school transport',
                        'school_id' => $school_id,
                        'created_at' => current_time('mysql')
                    );

                    $wpdb->insert(WLSM_VEHICLES, $vehicle_data);
                    $vehicle_id = $wpdb->insert_id;

                    // Create a route for each vehicle
                    $route_data = array(
                        'name' => $route_names[$v % count($route_names)],
                        'period' => $route_periods[rand(0, count($route_periods) - 1)],
                        'fare' => rand(50, 200) * 10,
                        'school_id' => $school_id,
                        'created_at' => current_time('mysql')
                    );

                    $wpdb->insert(WLSM_ROUTES, $route_data);
                    $route_id = $wpdb->insert_id;

                    // Link route and vehicle
                    $route_vehicle_data = array(
                        'route_id' => $route_id,
                        'vehicle_id' => $vehicle_id,
                        'created_at' => current_time('mysql')
                    );

                    $wpdb->insert(WLSM_ROUTE_VEHICLE, $route_vehicle_data);
                }

                // Assign random students to transport routes
                $student_records = $wpdb->get_results($wpdb->prepare(
                    "SELECT sr.ID FROM " . WLSM_STUDENT_RECORDS . " sr
                    JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
                    JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
                    WHERE cs.school_id = %d",
                    $school_id
                ));

                // Get all route-vehicle combinations for this school
                $route_vehicles = $wpdb->get_results($wpdb->prepare(
                    "SELECT rv.ID FROM " . WLSM_ROUTE_VEHICLE . " rv
                    JOIN " . WLSM_ROUTES . " r ON r.ID = rv.route_id
                    WHERE r.school_id = %d",
                    $school_id
                ));

                // Generate hostel demo data
                $hostel_names = array(
                    'Sunshine Hostel',
                    'Green View Residence',
                    'Unity House',
                    'Peace Haven',
                    'Scholar\'s Home'
                );

                $hostel_types = array(
                    'Boys',
                    'Girls',
                    'Co-Ed'
                );

                // Add 3 hostels per school
                for ($h = 0; $h < 3; $h++) {
                    $hostel_data = array(
                        'hostel_name' => $hostel_names[$h % count($hostel_names)],
                        'hostel_type' => $hostel_types[array_rand($hostel_types)],
                        'hostel_address' => rand(100, 999) . ' Education Street, Block ' . chr(65 + $h),
                        'hostel_intake' => rand(50, 200),
                        'fees' => rand(1000, 5000) * 10,
                        'school_id' => $school_id,
                        'created_at' => current_time('mysql')
                    );

                    $result = $wpdb->insert(WLSM_HOSTELS, $hostel_data);
                    if ($result === false) {
                        throw new Exception('Failed to insert hostel: ' . $wpdb->last_error);
                    }
                    $hostel_id = $wpdb->insert_id;

                    // Add 5-8 rooms per hostel
                    $num_rooms = rand(5, 8);
                    for ($r = 0; $r < $num_rooms; $r++) {
                        $room_data = array(
                            'hostel_id' => $hostel_id,
                            'room_name' => 'Room ' . ($r + 1) . chr(65 + ($r % 3)),
                            'number_of_beds' => rand(2, 4),
                            'note' => 'Demo room with basic facilities',
                            'created_at' => current_time('mysql')
                        );

                        $result = $wpdb->insert(WLSM_ROOMS, $room_data);
                        if ($result === false) {
                            throw new Exception('Failed to insert room: ' . $wpdb->last_error);
                        }
                    }
                }

                // Generate certificates demo data
                $certificate_templates = array(
                    array(
                        'label' => 'Academic Excellence Award',
                        'enabled_fields' => array('certificate-title', 'session-label', 'school-name'),
                        'field_positions' => array(
                            'certificate-title' => array(
                                'top' => '150',
                                'left' => '120',
                                'font-size' => '22'
                            )
                        )
                    ),
                    array(
                        'label' => 'Sports Achievement Certificate',
                        'enabled_fields' => array('certificate-title', 'session-label', 'class'),
                        'field_positions' => array(
                            'certificate-title' => array(
                                'top' => '135',
                                'left' => '135',
                                'font-size' => '22'
                            )
                        )
                    ),
                    array(
                        'label' => 'Perfect Attendance Award',
                        'enabled_fields' => array('certificate-title', 'session-label', 'session-start-date', 'session-end-date'),
                        'field_positions' => array(
                            'certificate-title' => array(
                                'top' => '140',
                                'left' => '110',
                                'font-size' => '22'
                            )
                        )
                    )
                );

                if (!empty($created_schools)) {
                    foreach ($created_schools as $demo_school_id) {
                        foreach ($certificate_templates as $template) {
                            // Avoid duplicate demo templates per school
                            $certificate_id = $wpdb->get_var(
                                $wpdb->prepare(
                                    'SELECT ID FROM ' . WLSM_CERTIFICATES . ' WHERE school_id = %d AND label = %s',
                                    $demo_school_id,
                                    $template['label']
                                )
                            );

                            if (!$certificate_id) {
                                $fields_layout = WLSM_Helper::get_certificate_dynamic_fields();

                                if (!empty($template['enabled_fields'])) {
                                    foreach ($template['enabled_fields'] as $field_key) {
                                        if (isset($fields_layout[$field_key])) {
                                            $fields_layout[$field_key]['enable'] = 1;
                                        }
                                    }
                                }

                                if (!empty($template['field_positions'])) {
                                    foreach ($template['field_positions'] as $field_key => $props) {
                                        if (!isset($fields_layout[$field_key])) {
                                            continue;
                                        }
                                        foreach ($props as $prop_key => $prop_value) {
                                            if (isset($fields_layout[$field_key]['props'][$prop_key])) {
                                                $fields_layout[$field_key]['props'][$prop_key]['value'] = $prop_value;
                                            }
                                        }
                                    }
                                }

                                $certificate_inserted = $wpdb->insert(
                                    WLSM_CERTIFICATES,
                                    array(
                                        'label' => $template['label'],
                                        'fields' => NULL,
                                        'exam_id' => 0,
                                        'school_id' => $demo_school_id,
                                        'created_at' => current_time('mysql')
                                    )
                                );

                                if (false === $certificate_inserted) {
                                    throw new Exception('Failed to insert certificate template: ' . $wpdb->last_error);
                                }

                                $certificate_id = (int) $wpdb->insert_id;
                            }

                            if (!$certificate_id) {
                                continue;
                            }

                            $students = $wpdb->get_results(
                                $wpdb->prepare(
                                    "SELECT sr.ID
                                    FROM " . WLSM_STUDENT_RECORDS . " sr
                                    JOIN " . WLSM_SECTIONS . " se ON se.ID = sr.section_id
                                    JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = se.class_school_id
                                    WHERE cs.school_id = %d AND sr.is_active = 1
                                    ORDER BY RAND() LIMIT 10",
                                    $demo_school_id
                                )
                            );

                            if (empty($students)) {
                                continue;
                            }

                            foreach ($students as $student) {
                                $already_assigned = $wpdb->get_var(
                                    $wpdb->prepare(
                                        'SELECT ID FROM ' . WLSM_CERTIFICATE_STUDENT . ' WHERE certificate_id = %d AND student_record_id = %d',
                                        $certificate_id,
                                        $student->ID
                                    )
                                );

                                if ($already_assigned) {
                                    continue;
                                }

                                $certificate_number = self::get_next_certificate_number($demo_school_id);

                                $issue_timestamp = current_time('timestamp') - (rand(0, 45) * DAY_IN_SECONDS);
                                $date_issued = gmdate('Y-m-d', $issue_timestamp);

                                $certificate_student_inserted = $wpdb->insert(
                                    WLSM_CERTIFICATE_STUDENT,
                                    array(
                                        'certificate_number' => $certificate_number,
                                        'student_record_id' => $student->ID,
                                        'certificate_id' => $certificate_id,
                                        'date_issued' => $date_issued,
                                        'created_at' => current_time('mysql')
                                    )
                                );

                                if (false === $certificate_student_inserted) {
                                    throw new Exception('Failed to assign certificate to student: ' . $wpdb->last_error);
                                }
                            }
                        }
                    }
                }

                // Generate activities demo data
                $activity_titles = array(
                    'Annual Sports Day',
                    'Science Exhibition',
                    'Cultural Festival',
                    'Debate Competition',
                    'Art Workshop',
                    'Music Concert',
                    'Chess Tournament',
                    'Drama Club',
                    'Robotics Workshop',
                    'Environmental Club'
                );

                // Get class IDs for this school
                $class_school_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT cs.class_id FROM " . WLSM_CLASS_SCHOOL . " cs
                    WHERE cs.school_id = %d",
                    $school_id
                ));

                // Add 5 activities per school
                for ($a = 0; $a < 5; $a++) {
                    $title = $activity_titles[$a % count($activity_titles)];

                    $activity_data = array(
                        'title' => $title . ' ' . date('Y'),
                        'fees' => rand(0, 5) * 100, // Some activities are free, others have fees
                        'description' => 'Join us for the exciting ' . $title . '. This activity helps students develop their skills and confidence.',
                        'is_approved' => 1,
                        'school_id' => $school_id,
                        'class_id' => $class_school_ids[array_rand($class_school_ids)],
                        'created_at' => current_time('mysql')
                    );

                    $result = $wpdb->insert(WLSM_ACTIVITIES, $activity_data);
                    if ($result === false) {
                        throw new Exception('Failed to insert activity: ' . $wpdb->last_error);
                    }
                }

                // Generate tickets demo data
                $ticket_titles = array(
                    'Computer Lab Internet Issue',
                    'Library Access Card Problem',
                    'Sports Equipment Request',
                    'Classroom Projector Malfunction',
                    'Cafeteria Feedback',
                    'Bus Route Query',
                    'Student ID Card Issue',
                    'Fee Payment Portal Problem',
                    'Academic Certificate Request',
                    'Extra-curricular Activity Suggestion'
                );

                $ticket_descriptions = array(
                    'The internet connection in computer lab %d is not working properly.',
                    'Unable to access library with my student card. Need immediate assistance.',
                    'Requesting new sports equipment for %s team practice.',
                    'The projector in classroom %s is showing flickering images.',
                    'Suggestion for improving cafeteria menu and service.',
                    'Request to add a new stop in route %s for better convenience.',
                    'My student ID card is damaged and needs replacement.',
                    'Having trouble accessing the online fee payment portal.',
                    'Requesting duplicate academic certificates for last year.',
                    'Suggestion to start a new %s club in our school.'
                );

                $priorities = array('low', 'normal', 'high', 'urgent');
                $statuses = array('open', 'in_progress', 'resolved', 'closed');

                // Get staff IDs for ticket assignment
                $staff_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT a.ID FROM " . WLSM_ADMINS . " a
                    JOIN " . WLSM_STAFF . " s ON s.ID = a.staff_id
                    WHERE s.school_id = %d",
                    $school_id
                ));

                // Get student IDs for ticket creation
                $student_ids = $wpdb->get_results($wpdb->prepare(
                    "SELECT sr.ID, sr.section_id, s.class_school_id
                    FROM " . WLSM_STUDENT_RECORDS . " sr
                    JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
                    JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
                    WHERE cs.school_id = %d",
                    $school_id
                ));

                // Add 10 tickets per school
                for ($t = 0; $t < 10; $t++) {
                    $student = $student_ids[array_rand($student_ids)];
                    $title = $ticket_titles[$t % count($ticket_titles)];
                    $description = sprintf(
                        $ticket_descriptions[$t % count($ticket_descriptions)],
                        rand(1, 5),
                        array('Cricket', 'Football', 'Basketball', 'Volleyball')[array_rand(array('Cricket', 'Football', 'Basketball', 'Volleyball'))],
                        chr(65 + rand(0, 5))
                    );

                    $ticket_data = array(
                        'title' => $title,
                        'description' => $description,
                        'priority' => $priorities[array_rand($priorities)],
                        'status' => $statuses[array_rand($statuses)],
                        'school_id' => $school_id,
                        'assigned_to' => $staff_ids ? $staff_ids[array_rand($staff_ids)] : null,
                        'student_id' => $student->ID,
                        'class_id' => $student->class_school_id,
                        'section_id' => $student->section_id,
                        'created_by' => $student->ID, // Student created ticket
                        'due_date' => date('Y-m-d', strtotime('+' . rand(1, 14) . ' days')),
                        'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                    );

                    $result = $wpdb->insert(WLSM_TICKETS, $ticket_data);
                    if ($result === false) {
                        throw new Exception('Failed to insert ticket: ' . $wpdb->last_error);
                    }
                }

                // Generate chapter and lecture demo data
                $chapter_titles = array(
                    'Introduction to %s',
                    'Fundamentals of %s',
                    'Advanced Topics in %s',
                    'Modern %s Concepts',
                    'Applied %s',
                    'Understanding %s Basics',
                    '%s Theory and Practice',
                    '%s Applications',
                    'Contemporary %s',
                    'Exploring %s'
                );

                $lecture_titles = array(
                    'Overview of %s',
                    'Understanding %s in Detail',
                    'Working with %s',
                    'Practical Applications of %s',
                    'Case Studies in %s',
                    '%s in Real World',
                    'Deep Dive into %s',
                    'Mastering %s Concepts',
                    '%s Advanced Topics',
                    'Future of %s'
                );

                // Get subjects for each class
                $class_subjects = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.ID as subject_id, s.label as subject_name,
                            s.code as subject_code, s.type as subject_type,
                            cs.ID as class_school_id, cs.class_id
                     FROM " . WLSM_SUBJECTS . " s
                     JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
                     WHERE cs.school_id = %d",
                    $school_id
                ));

                // Add chapters and lectures for each subject
                foreach ($class_subjects as $subject) {
                    // Add 3-5 chapters per subject
                    $num_chapters = rand(3, 5);
                    for ($c = 0; $c < $num_chapters; $c++) {
                        $chapter_data = array(
                            'title' => sprintf($chapter_titles[$c % count($chapter_titles)], $subject->subject_name),
                            'class_id' => $subject->class_id,
                            'subject_id' => $subject->subject_id,
                            'created_at' => current_time('mysql')
                        );

                        $result = $wpdb->insert(WLSM_CHAPTER, $chapter_data);
                        if ($result === false) {
                            throw new Exception('Failed to insert chapter: ' . $wpdb->last_error);
                        }
                        $chapter_id = $wpdb->insert_id;

                        // Add 2-4 lectures per chapter
                        $num_lectures = rand(2, 4);
                        for ($l = 0; $l < $num_lectures; $l++) {
                            $lecture_title = sprintf($lecture_titles[$l % count($lecture_titles)], $subject->subject_name);

                            $lecture_data = array(
                                'title' => $lecture_title,
                                'description' => 'Detailed lecture on ' . $lecture_title . ' covering key concepts and practical examples.',
                                'attachment' => null,
                                'link_to' => 'url', // or 'attachment'
                                'url' => 'https://example.com/sample-lecture-' . rand(1, 999),
                                'class_id' => $subject->class_id,
                                'chapter_id' => $chapter_id,
                                'section_id' => null, // Optional: Could be assigned to specific sections
                                'subject_id' => $subject->subject_id,
                                'created_at' => current_time('mysql')
                            );

                            $result = $wpdb->insert(WLSM_LECTURE, $lecture_data);
                            if ($result === false) {
                                throw new Exception('Failed to insert lecture: ' . $wpdb->last_error);
                            }
                        }
                    }
                }

                // Assign 60% of students to transport
                foreach ($student_records as $index => $student) {
                    if (rand(1, 100) <= 60) {
                        $route_vehicle = $route_vehicles[array_rand($route_vehicles)];
                        $wpdb->update(
                            WLSM_STUDENT_RECORDS,
                            array('route_vehicle_id' => $route_vehicle->ID),
                            array('ID' => $student->ID)
                        );
                    }
                }

                // Generate library cards for students
				$student_records = $wpdb->get_results($wpdb->prepare(
					"SELECT sr.ID FROM " . WLSM_STUDENT_RECORDS . " sr
					JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
					JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
					WHERE cs.school_id = %d",
					$school_id
				));

				foreach ($student_records as $student) {
					// Generate a unique library card number
					$card_number = 'LIB-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

					// Issue date within last 6 months
					$date_issued = date('Y-m-d', strtotime('-' . rand(1, 180) . ' days'));

					$library_card_data = array(
						'card_number' => $card_number,
						'student_record_id' => $student->ID,
						'date_issued' => $date_issued,
						'created_at' => $date_issued,
						'updated_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_LIBRARY_CARDS, $library_card_data);
					if ($result === false) {
						throw new Exception('Failed to insert library card: ' . $wpdb->last_error);
					}
				}

				// Fallback: if no current demo session found based on dates, use the most recent demo session
				if (!$current_demo_session_id && !empty($created_sessions)) {
					$current_demo_session_id = end($created_sessions); // Get the last (most recent) demo session
				}

				// Ensure we always have a demo session for exam generation
				if (!$current_demo_session_id) {
					$current_demo_session_id = $first_session_id; // Fallback to first session
				}

				// Immediately set the current user's session to the demo session (force override)
				$current_user_id = get_current_user_id();
				if ($current_user_id && $current_demo_session_id) {
					// Clear any existing session data first
					delete_user_meta($current_user_id, 'wlsm_current_session');
					delete_user_meta($current_user_id, 'wlsm_school_id');

					// Set to demo session and school
					update_user_meta($current_user_id, 'wlsm_current_session', $current_demo_session_id);
					update_user_meta($current_user_id, 'wlsm_school_id', $school_id);

					// Force WordPress to clear user meta cache
					wp_cache_delete($current_user_id, 'user_meta');
				}

				// Assign the current demo session (based on dates, fallback to first session) to all demo users
				$session_to_assign = $current_demo_session_id ? $current_demo_session_id : $first_session_id;
				if ($session_to_assign) {
					$demo_usernames = array('demo_teacher', 'demo_accountant', 'demo_receptionist', 'demo_librarian');
					foreach ($demo_usernames as $demo_username) {
						$demo_user = get_user_by('login', $demo_username);
						if ($demo_user) {
							update_user_meta($demo_user->ID, 'wlsm_current_session', $session_to_assign);
						}
					}
				}
			}

			// Assign staff to subjects
			$staff_subject_assignments = 0;

			// Get the demo teacher admin ID for subject assignments
			$demo_teacher_admin_id = $wpdb->get_var($wpdb->prepare(
				"SELECT a.ID FROM " . WLSM_ADMINS . " a
				JOIN " . WLSM_STAFF . " s ON s.ID = a.staff_id
				JOIN " . WLSM_USERS . " u ON u.ID = s.user_id
				WHERE u.user_login = 'demo_teacher' AND s.school_id = %d",
				$school_id
			));

			// First, ensure every subject has at least one teacher
			foreach ($created_subjects as $subject) {
				if ($demo_teacher_admin_id) {
					$admin_subject_data = array(
						'admin_id'   => $demo_teacher_admin_id,
						'subject_id' => $subject['id'],
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_ADMIN_SUBJECT, $admin_subject_data);
					if ($result === false) {
						throw new Exception('Failed to assign subject to demo teacher: ' . $wpdb->last_error);
					}
					$staff_subject_assignments++;
				}
			}

			// Generate class timetables (routines)
			$routine_assignments = 0;
			$time_slots = array(
				array('start' => '08:00:00', 'end' => '09:00:00'),
				array('start' => '09:00:00', 'end' => '10:00:00'),
				array('start' => '10:30:00', 'end' => '11:30:00'), // 30 min break
				array('start' => '11:30:00', 'end' => '12:30:00'),
				array('start' => '13:30:00', 'end' => '14:30:00'), // Lunch break
				array('start' => '14:30:00', 'end' => '15:30:00'),
			);

			$room_numbers = array('101', '102', '103', '201', '202', '203', 'Lab-1', 'Lab-2', 'Library', 'Computer Lab');
			$days = array(1, 2, 3, 4, 5); // Monday to Friday (1-7 where 1=Monday)

			foreach ($created_sections as $section) {
				// Get subjects for this section's class
				$section_subjects = array_filter($created_subjects, function($subject) use ($section) {
					return $subject['school_id'] === $section['school_id'];
				});

				if (empty($section_subjects)) {
					continue; // Skip if no subjects found
				}

				// Generate routines for each day of the week
				foreach ($days as $day) {
					$used_time_slots = array();
					$subjects_for_day = array_slice($section_subjects, 0, min(4, count($section_subjects))); // Max 4 subjects per day

					foreach ($subjects_for_day as $index => $subject) {
						// Get a teacher assigned to this subject
						$teacher = $wpdb->get_var($wpdb->prepare(
							"SELECT admin_id FROM " . WLSM_ADMIN_SUBJECT . " WHERE subject_id = %d LIMIT 1",
							$subject['id']
						));

						// Select a time slot that hasn't been used for this day/section
						$available_slots = array_filter($time_slots, function($slot, $key) use ($used_time_slots) {
							return !in_array($key, $used_time_slots);
						}, ARRAY_FILTER_USE_BOTH);

						if (empty($available_slots)) {
							break; // No more time slots available for this day
						}

						$time_slot_index = array_rand($available_slots);
						$time_slot = $available_slots[$time_slot_index];
						$used_time_slots[] = $time_slot_index;

						$routine_data = array(
							'start_time'  => $time_slot['start'],
							'end_time'    => $time_slot['end'],
							'day'         => $day,
							'room_number' => $room_numbers[array_rand($room_numbers)],
							'subject_id'  => $subject['id'],
							'admin_id'    => $teacher, // Can be NULL if no teacher assigned
							'section_id'  => $section['id'],
							'created_at'  => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_ROUTINES, $routine_data);
						if ($result === false) {
							throw new Exception('Failed to insert routine: ' . $wpdb->last_error);
						}
						$routine_assignments++;
					}
				}
			}

			// Generate student attendance for last 3 months (demo data)
			$attendance_records = 0;
			$attendance_statuses = array('p', 'a', 'l', 'h'); // Present, Absent, Late, Half-day
			$attendance_weights = array(75, 15, 5, 5); // Weighted probability (75% present, 15% absent, etc.)

			// Calculate from 3 months ago to today (ensures comprehensive demo data)
			$three_months_ago = date('Y-m-d', strtotime('-3 months'));
			$today = date('Y-m-d');

			// Get all student records that were created
			$all_student_records = $wpdb->get_results(
				"SELECT sr.ID, sr.session_id, sr.section_id, sr.name
				FROM " . WLSM_STUDENT_RECORDS . " sr
				JOIN " . WLSM_SECTIONS . " sec ON sr.section_id = sec.ID
				JOIN " . WLSM_CLASS_SCHOOL . " cs ON sec.class_school_id = cs.ID
				WHERE cs.school_id IN (" . implode(',', array_map('intval', $created_schools)) . ")"
			);

			// Generate attendance for each student
			foreach ($all_student_records as $student) {
				// Generate attendance for each day from 3 months ago to today (only weekdays)
				$current_date = $three_months_ago;

				while (strtotime($current_date) <= strtotime($today)) {
					$day_of_week = date('N', strtotime($current_date)); // 1 (Monday) to 7 (Sunday)

					// Only generate attendance for weekdays (Monday to Friday)
					if ($day_of_week >= 1 && $day_of_week <= 5) {
						// Generate weighted random attendance status
						$rand = mt_rand(1, 100);
						$status = 'P'; // Default to Present
						$cumulative_weight = 0;

						for ($i = 0; $i < count($attendance_statuses); $i++) {
							$cumulative_weight += $attendance_weights[$i];
							if ($rand <= $cumulative_weight) {
								$status = $attendance_statuses[$i];
								break;
							}
						}

						// Add realistic reasons for non-present statuses
						$reason = null;
						if ($status === 'A') {
							$absent_reasons = array(
								'Illness', 'Family emergency', 'Transportation issues',
								'Medical appointment', 'Personal family matter'
							);
							$reason = $absent_reasons[array_rand($absent_reasons)];
						} elseif ($status === 'L') {
							$late_reasons = array(
								'Traffic delay', 'Public transport delay', 'Personal delay',
								'Family matters', 'Medical appointment'
							);
							$reason = $late_reasons[array_rand($late_reasons)];
						} elseif ($status === 'H') {
							$reason = 'Half day - Medical appointment';
						}

						$attendance_data = array(
							'attendance_date'   => $current_date,
							'status'           => $status,
							'student_record_id' => $student->ID,
							'subject_id'       => null, // Attendance by "all" - no specific subject
							'reason'           => $reason,
							'created_at'       => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_ATTENDANCE, $attendance_data);
						if ($result === false) {
							throw new Exception('Failed to insert attendance: ' . $wpdb->last_error);
						}
						$attendance_records++;
					}

					// Move to next day
					$current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
				}
			}

			// Generate student leave requests for demo data
			$leave_records = 0;
			$leave_types = array(
				'Sick Leave', 'Family Emergency', 'Medical Appointment', 'Personal Leave',
				'Religious Holiday', 'Transportation Issues', 'Weather Emergency', 'School Event Participation'
			);

			$leave_statuses = array(true, false); // true = approved, false = pending
			$leave_status_weights = array(70, 30); // 70% approved, 30% pending

			// Get all student records for leave generation
			$all_student_records = $wpdb->get_results(
				"SELECT sr.ID, sr.session_id, sr.section_id, sr.name
				FROM " . WLSM_STUDENT_RECORDS . " sr
				JOIN " . WLSM_SECTIONS . " sec ON sr.section_id = sec.ID
				JOIN " . WLSM_CLASS_SCHOOL . " cs ON sec.class_school_id = cs.ID
				WHERE cs.school_id IN (" . implode(',', array_map('intval', $created_schools)) . ")"
			);

			// Generate leave requests for past 60 days and future 30 days
			$sixty_days_ago = date('Y-m-d', strtotime('-60 days'));
			$thirty_days_future = date('Y-m-d', strtotime('+30 days'));

			// Generate leave requests for random students (about 30% of students will have leaves)
			$students_with_leaves = array_slice($all_student_records, 0, intval(count($all_student_records) * 0.3));

			foreach ($students_with_leaves as $student) {
				// Each student gets 1-3 leave requests in the time period
				$leave_count = rand(1, 3);

				for ($i = 0; $i < $leave_count; $i++) {
					// Generate random leave dates within the range
					$start_timestamp = strtotime($sixty_days_ago);
					$end_timestamp = strtotime($thirty_days_future);
					$random_timestamp = rand($start_timestamp, $end_timestamp);
					$leave_start_date = date('Y-m-d', $random_timestamp);

					// Leave duration: 1-5 days
					$leave_duration = rand(1, 5);
					$leave_end_date = date('Y-m-d', strtotime($leave_start_date . ' +' . ($leave_duration - 1) . ' days'));

					// Generate weighted random approval status
					$rand = rand(1, 100);
					$cumulative = 0;
					$is_approved = false; // Default to pending

					for ($j = 0; $j < count($leave_statuses); $j++) {
						$cumulative += $leave_status_weights[$j];
						if ($rand <= $cumulative) {
							$is_approved = $leave_statuses[$j];
							break;
						}
					}

					// Select random leave type and create description
					$leave_type = $leave_types[array_rand($leave_types)];
					$descriptions = array(
						'Sick Leave' => array(
							'Student is suffering from fever and doctor advised rest.',
							'Medical condition requires home rest for recovery.',
							'Student has flu symptoms and needs to recover.',
							'Doctor recommended bed rest due to illness.'
						),
						'Family Emergency' => array(
							'Family emergency requires immediate attention.',
							'Urgent family matter needs student presence.',
							'Family medical emergency requires student support.',
							'Unexpected family situation requires absence.'
						),
						'Medical Appointment' => array(
							'Scheduled medical check-up appointment.',
							'Specialist doctor consultation required.',
							'Regular health check-up with family doctor.',
							'Dental appointment for treatment.'
						),
						'Personal Leave' => array(
							'Personal family commitment requires absence.',
							'Important personal matter to attend.',
							'Family function requires student presence.',
							'Personal reasons require time off.'
						),
						'Religious Holiday' => array(
							'Religious festival celebration with family.',
							'Important religious ceremony to attend.',
							'Traditional religious observance day.',
							'Cultural religious event participation.'
						),
						'Transportation Issues' => array(
							'Transportation breakdown, unable to attend.',
							'Public transport strike affecting attendance.',
							'Vehicle breakdown preventing school attendance.',
							'Road closure due to construction work.'
						),
						'Weather Emergency' => array(
							'Severe weather conditions preventing attendance.',
							'Heavy rainfall causing transportation issues.',
							'Storm warning advised to stay home.',
							'Weather alert preventing safe travel.'
						),
						'School Event Participation' => array(
							'Representing school in inter-school competition.',
							'Selected for educational trip with school.',
							'Participating in district level academic event.',
							'School sports team participation in tournament.'
						)
					);

					$description = $descriptions[$leave_type][array_rand($descriptions[$leave_type])];

					// Get a random admin for approval (if approved)
					$approved_by = null;
					if ($is_approved) {
						$random_admin = $wpdb->get_var($wpdb->prepare(
							"SELECT s.user_id FROM " . WLSM_ADMINS . " a
							JOIN " . WLSM_STAFF . " s ON a.staff_id = s.ID
							WHERE s.school_id = %d ORDER BY RAND() LIMIT 1",
							$school_id
						));
						$approved_by = $random_admin;
					}

					// Get student's section to determine school_id
					$student_section = $wpdb->get_row($wpdb->prepare(
						"SELECT cs.school_id FROM " . WLSM_SECTIONS . " sec
						JOIN " . WLSM_CLASS_SCHOOL . " cs ON sec.class_school_id = cs.ID
						WHERE sec.ID = %d",
						$student->section_id
					));

					if ($student_section) {
						$leave_data = array(
							'description'        => $description,
							'start_date'         => $leave_start_date,
							'end_date'           => $leave_end_date,
							'is_approved'        => $is_approved ? 1 : 0,
							'student_record_id'  => $student->ID,
							'school_id'          => $student_section->school_id,
							'approved_by'        => $approved_by,
							'created_at'         => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_LEAVES, $leave_data);
						if ($result === false) {
							throw new Exception('Failed to insert leave record: ' . $wpdb->last_error);
						}
						$leave_records++;
					}
				}
			}

			// Generate staff attendance for past 3 months (demo data)
			$staff_attendance_records = 0;
			$staff_attendance_statuses = array('p', 'a', 'l', 'h'); // Present, Absent, Late, Half-day
			$staff_attendance_weights = array(80, 10, 5, 5); // Weighted probability (80% present, 10% absent, etc.)

			// Calculate from 3 months ago to today (ensures comprehensive demo data)
			$three_months_ago = date('Y-m-d', strtotime('-3 months'));
			$today = date('Y-m-d');

			// Get all staff/admin records that were created
			$all_staff_records = $wpdb->get_results(
				"SELECT a.ID, a.staff_id, s.user_id, s.school_id, u.display_name
				FROM " . WLSM_ADMINS . " a
				JOIN " . WLSM_STAFF . " s ON a.staff_id = s.ID
				JOIN " . WLSM_USERS . " u ON s.user_id = u.ID
				WHERE s.school_id IN (" . implode(',', array_map('intval', $created_schools)) . ")
				AND s.role != 'school_administrator'"
			);

			// Generate attendance for each staff member
			foreach ($all_staff_records as $staff) {
				// Generate attendance for each day from 3 months ago to today (only weekdays)
				$current_date = $three_months_ago;

				while (strtotime($current_date) <= strtotime($today)) {
					$day_of_week = date('N', strtotime($current_date)); // 1 (Monday) to 7 (Sunday)

					// Only generate attendance for weekdays (Monday to Friday)
					if ($day_of_week >= 1 && $day_of_week <= 5) {
						// Generate weighted random attendance status
						$rand = mt_rand(1, 100);
						$status = 'P'; // Default to Present
						$cumulative_weight = 0;

						for ($i = 0; $i < count($staff_attendance_statuses); $i++) {
							$cumulative_weight += $staff_attendance_weights[$i];
							if ($rand <= $cumulative_weight) {
								$status = $staff_attendance_statuses[$i];
								break;
							}
						}

						// Add realistic reasons for non-present statuses
						$reason = null;
						if ($status === 'A') {
							$absent_reasons = array(
								'Illness', 'Family emergency', 'Transportation issues',
								'Medical appointment', 'Personal family matter', 'Official work'
							);
							$reason = $absent_reasons[array_rand($absent_reasons)];
						} elseif ($status === 'L') {
							$late_reasons = array(
								'Traffic delay', 'Public transport delay', 'Personal delay',
								'Family matters', 'Medical appointment', 'Meeting delay'
							);
							$reason = $late_reasons[array_rand($late_reasons)];
						} elseif ($status === 'H') {
							$reason = 'Half day - Official work';
						}

						// Check if staff attendance already exists for this date and admin
						$existing_attendance = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT ID FROM " . WLSM_STAFF_ATTENDANCE . " WHERE attendance_date = %s AND admin_id = %d",
								$current_date,
								$staff->ID
							)
						);

						if (!$existing_attendance) {
							$staff_attendance_data = array(
								'attendance_date' => $current_date,
								'status'         => $status,
								'admin_id'       => $staff->ID,
								'reason'         => $reason,
								'created_at'     => current_time('mysql'),
								'updated_at'     => current_time('mysql')
							);

							$result = $wpdb->insert(WLSM_STAFF_ATTENDANCE, $staff_attendance_data);
							if ($result === false) {
								throw new Exception('Failed to insert staff attendance: ' . $wpdb->last_error);
							}
							$staff_attendance_records++;
						}
					}

					// Move to next day
					$current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
				}
			}

			// Generate staff leave requests for demo data
			$staff_leave_records = 0;
			$staff_leave_types = array(
				'Sick Leave', 'Family Emergency', 'Medical Appointment', 'Personal Leave',
				'Religious Holiday', 'Transportation Issues', 'Weather Emergency', 'Official Work',
				'Professional Development', 'Maternity Leave', 'Paternity Leave', 'Casual Leave'
			);

			$staff_leave_statuses = array(true, false); // true = approved, false = pending
			$staff_leave_status_weights = array(75, 25); // 75% approved, 25% pending

			// Generate leave requests for all demo staff (5 leaves per staff member)
			foreach ($all_staff_records as $staff) {
				// Each staff member gets exactly 5 leave requests
				$leave_count = 5;

				for ($i = 0; $i < $leave_count; $i++) {
					// Generate random leave dates within the past 60 days and future 30 days
					$sixty_days_ago = date('Y-m-d', strtotime('-60 days'));
					$thirty_days_future = date('Y-m-d', strtotime('+30 days'));

					$start_timestamp = strtotime($sixty_days_ago);
					$end_timestamp = strtotime($thirty_days_future);
					$random_timestamp = rand($start_timestamp, $end_timestamp);
					$leave_start_date = date('Y-m-d', $random_timestamp);

					// Leave duration: 1-5 days
					$leave_duration = rand(1, 5);
					$leave_end_date = date('Y-m-d', strtotime($leave_start_date . ' +' . ($leave_duration - 1) . ' days'));

					// Generate weighted random approval status
					$rand = rand(1, 100);
					$cumulative = 0;
					$is_approved = false; // Default to pending

					for ($j = 0; $j < count($staff_leave_statuses); $j++) {
						$cumulative += $staff_leave_status_weights[$j];
						if ($rand <= $cumulative) {
							$is_approved = $staff_leave_statuses[$j];
							break;
						}
					}

					// Select random leave type and create description
					$leave_type = $staff_leave_types[array_rand($staff_leave_types)];
					$staff_descriptions = array(
						'Sick Leave' => array(
							'Staff member is suffering from illness and requires medical rest.',
							'Medical condition requires home rest for recovery.',
							'Doctor advised rest due to health condition.',
							'Illness prevents attendance at work.'
						),
						'Family Emergency' => array(
							'Urgent family emergency requires immediate attention.',
							'Family medical emergency requires staff presence.',
							'Unexpected family situation requires absence.',
							'Critical family matter needs immediate handling.'
						),
						'Medical Appointment' => array(
							'Scheduled medical check-up appointment.',
							'Specialist doctor consultation required.',
							'Regular health screening appointment.',
							'Dental treatment appointment.'
						),
						'Personal Leave' => array(
							'Personal family commitment requires absence.',
							'Important personal matter to attend.',
							'Family function requires staff presence.',
							'Personal reasons require time off.'
						),
						'Religious Holiday' => array(
							'Religious festival celebration with family.',
							'Important religious ceremony to attend.',
							'Traditional religious observance day.',
							'Cultural religious event participation.'
						),
						'Transportation Issues' => array(
							'Transportation breakdown, unable to attend.',
							'Public transport strike affecting attendance.',
							'Vehicle breakdown preventing work attendance.',
							'Road closure due to construction work.'
						),
						'Weather Emergency' => array(
							'Severe weather conditions preventing attendance.',
							'Heavy rainfall causing transportation issues.',
							'Storm warning advised to stay home.',
							'Weather alert preventing safe travel.'
						),
						'Official Work' => array(
							'Official duty requires absence from regular work.',
							'Representing institution at official event.',
							'Government office work requires presence.',
							'Official meeting outside the institution.'
						),
						'Professional Development' => array(
							'Attending professional development workshop.',
							'Participating in educational seminar.',
							'Training program for skill enhancement.',
							'Conference participation for professional growth.'
						),
						'Maternity Leave' => array(
							'Maternity leave for childbirth and childcare.',
							'Prenatal care and delivery preparation.',
							'Postnatal care and newborn childcare.',
							'Maternal health and family care.'
						),
						'Paternity Leave' => array(
							'Paternity leave for newborn childcare.',
							'Family support during childbirth period.',
							'Newborn care and family bonding time.',
							'Parental responsibility for newborn.'
						),
						'Casual Leave' => array(
							'Casual leave for personal reasons.',
							'Short duration personal commitment.',
							'Unexpected personal matter.',
							'Brief absence for personal affairs.'
						)
					);

					$description = $staff_descriptions[$leave_type][array_rand($staff_descriptions[$leave_type])];

					// Get a random admin for approval (if approved)
					$approved_by = null;
					if ($is_approved) {
						$random_admin = $wpdb->get_var($wpdb->prepare(
							"SELECT s.user_id FROM " . WLSM_ADMINS . " a
							JOIN " . WLSM_STAFF . " s ON a.staff_id = s.ID
							WHERE s.school_id = %d AND s.user_id != %d ORDER BY RAND() LIMIT 1",
							$staff->school_id, $staff->user_id
						));
						$approved_by = $random_admin;
					}

					$staff_leave_data = array(
						'description' => $description,
						'start_date'  => $leave_start_date,
						'end_date'    => $leave_end_date,
						'is_approved' => $is_approved ? 1 : 0,
						'admin_id'    => $staff->ID,
						'school_id'   => $staff->school_id,
						'approved_by' => $approved_by,
						'added_by'    => $staff->user_id,
						'created_at'  => current_time('mysql'),
						'updated_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_LEAVES, $staff_leave_data);
					if ($result === false) {
						throw new Exception('Failed to insert staff leave record: ' . $wpdb->last_error);
					}
					$staff_leave_records++;
				}
			}

			// Generate noticeboard entries for demo data
			$notice_records = 0;

			// Notice categories and templates
			$notice_templates = array(
				'Academic' => array(
					array(
						'title' => 'Mid-Term Examination Schedule',
						'description' => 'Dear Students and Parents, The mid-term examinations will be conducted from [DATE]. Please prepare accordingly and ensure all syllabus is covered. Exam timetable is attached. Contact the office for any queries.',
						'link_to' => 'attachment'
					),
					array(
						'title' => 'Parent-Teacher Meeting',
						'description' => 'We are pleased to invite all parents for the Parent-Teacher Meeting scheduled on [DATE]. This is an important opportunity to discuss your child\'s academic progress and performance. Please confirm your attendance.',
						'link_to' => 'url'
					),
					array(
						'title' => 'Assignment Submission Deadline',
						'description' => 'All pending assignments for the current semester must be submitted by [DATE]. Late submissions will attract penalty marks. Please contact your respective subject teachers for clarification.',
						'link_to' => 'none'
					),
					array(
						'title' => 'Science Project Exhibition',
						'description' => 'The annual Science Project Exhibition will be held on [DATE]. All students from grades 6-12 are encouraged to participate. Registration forms are available at the school office.',
						'link_to' => 'attachment'
					)
				),
				'Administrative' => array(
					array(
						'title' => 'School Timing Change Notice',
						'description' => 'Due to seasonal changes, the school timing will be modified from [DATE]. New timing: 8:00 AM to 2:00 PM. Transportation schedule will be adjusted accordingly.',
						'link_to' => 'none'
					),
					array(
						'title' => 'Fee Payment Reminder',
						'description' => 'This is to remind all parents that the quarterly fee payment is due by [DATE]. Please clear all outstanding dues to avoid any inconvenience. Online payment options are available.',
						'link_to' => 'url'
					),
					array(
						'title' => 'School Uniform Guidelines',
						'description' => 'All students are required to wear proper school uniform as per the guidelines. The dress code policy document is attached for your reference. Violations will be strictly dealt with.',
						'link_to' => 'attachment'
					),
					array(
						'title' => 'Identity Card Renewal',
						'description' => 'Students who have lost their identity cards or need renewal must apply at the administrative office with required documents and fee. New cards will be issued within 7 working days.',
						'link_to' => 'none'
					)
				),
				'Events' => array(
					array(
						'title' => 'Annual Sports Day',
						'description' => 'The Annual Sports Day will be celebrated on [DATE] at the school playground. All students are required to participate in various sporting events. Participation forms must be submitted by [DATE].',
						'link_to' => 'url'
					),
					array(
						'title' => 'Cultural Program Auditions',
						'description' => 'Auditions for the upcoming cultural program will be held on [DATE]. Students interested in dance, music, drama, and other performances are welcome to participate.',
						'link_to' => 'none'
					),
					array(
						'title' => 'Educational Trip to Science Museum',
						'description' => 'An educational trip to the Science Museum has been organized for grades 8-10 on [DATE]. Interested students should register with their class teachers and submit the consent form.',
						'link_to' => 'attachment'
					),
					array(
						'title' => 'Inter-School Competition',
						'description' => 'Our school will participate in the Inter-School Academic Competition on [DATE]. Selected students will represent the school. Coaching sessions will be conducted in the coming weeks.',
						'link_to' => 'url'
					)
				),
				'Important' => array(
					array(
						'title' => 'School Holiday Notice',
						'description' => 'The school will remain closed from [DATE] to [DATE] due to public holidays. Regular classes will resume from [DATE]. Emergency contact information is available at the school office.',
						'link_to' => 'none'
					),
					array(
						'title' => 'Health and Safety Guidelines',
						'description' => 'In view of the current health situation, all students and staff are requested to follow the safety guidelines. Hand sanitizers are available throughout the campus.',
						'link_to' => 'attachment'
					),
					array(
						'title' => 'Bus Route Modification',
						'description' => 'Due to road construction work, Bus Route #3 will be temporarily modified from [DATE]. New pickup and drop points are listed in the attached schedule.',
						'link_to' => 'attachment'
					),
					array(
						'title' => 'Library New Books Arrival',
						'description' => 'The school library has received new books in various subjects. Students are encouraged to visit the library and issue books for their academic and general reading.',
						'link_to' => 'none'
					)
				)
			);

			// Get all classes for class-specific notices
			$school_classes = $wpdb->get_results($wpdb->prepare(
				"SELECT cs.ID, c.label FROM " . WLSM_CLASS_SCHOOL . " cs
				JOIN " . WLSM_CLASSES . " c ON cs.class_id = c.ID
				WHERE cs.school_id = %d",
				$school_id
			));

			// Get admin users for notice creation
			$admin_users = $wpdb->get_results($wpdb->prepare(
				"SELECT s.user_id FROM " . WLSM_ADMINS . " a
				JOIN " . WLSM_STAFF . " s ON a.staff_id = s.ID
				WHERE s.school_id = %d",
				$school_id
			));

			// Generate notices for past 90 days and future 30 days
			$ninety_days_ago = date('Y-m-d', strtotime('-90 days'));
			$thirty_days_future = date('Y-m-d', strtotime('+30 days'));

			// Generate 15-20 notices per school
			$notice_count = rand(15, 20);

			for ($i = 0; $i < $notice_count; $i++) {
				// Select random category and notice template
				$categories = array_keys($notice_templates);
				$selected_category = $categories[array_rand($categories)];
				$selected_template = $notice_templates[$selected_category][array_rand($notice_templates[$selected_category])];

				// Generate random date within range
				$start_timestamp = strtotime($ninety_days_ago);
				$end_timestamp = strtotime($thirty_days_future);
				$random_timestamp = rand($start_timestamp, $end_timestamp);
				$notice_date = date('Y-m-d H:i:s', $random_timestamp);

				// Replace placeholder dates in description
				$future_date = date('F j, Y', strtotime('+' . rand(7, 30) . ' days'));
				$description = str_replace('[DATE]', $future_date, $selected_template['description']);

				// Select random admin as creator
				$added_by = null;
				if (!empty($admin_users)) {
					$random_admin = $admin_users[array_rand($admin_users)];
					$added_by = $random_admin->user_id;
				}

				// Determine URL or attachment based on link_to
				$url = null;
				$attachment = null;

				if ($selected_template['link_to'] === 'url') {
					$urls = array(
						'https://school-portal.example.com/forms',
						'https://school-portal.example.com/payments',
						'https://school-portal.example.com/events',
						'https://school-portal.example.com/academics'
					);
					$url = $urls[array_rand($urls)];
				}
				// Note: For attachment, we would need to create actual file attachments
				// For demo purposes, we'll keep attachment as NULL

				$notice_data = array(
					'title'       => $selected_template['title'],
					'description' => $description,
					'url'         => $url,
					'link_to'     => $selected_template['link_to'],
					'is_active'   => 1,
					'school_id'   => $school_id,
					'added_by'    => $added_by,
					'created_at'  => $notice_date
				);

				$result = $wpdb->insert(WLSM_NOTICES, $notice_data);
				if ($result === false) {
					throw new Exception('Failed to insert notice: ' . $wpdb->last_error);
				}

				$notice_id = $wpdb->insert_id;
				$notice_records++;

				// Assign notice to classes (70% chance for each notice to be class-specific)
				if (rand(1, 100) <= 70 && !empty($school_classes)) {
					// Select 1-3 random classes for this notice
					$class_count = rand(1, min(3, count($school_classes)));
					$selected_classes = array_slice($school_classes, 0, $class_count);

					foreach ($selected_classes as $class) {
						$class_notice_data = array(
							'class_school_id' => $class->ID,
							'notice_id'       => $notice_id,
							'created_at'      => $notice_date
						);

						$result = $wpdb->insert(WLSM_CLASS_SCHOOL_NOTICE, $class_notice_data);
						if ($result === false) {
							throw new Exception('Failed to insert class notice assignment: ' . $wpdb->last_error);
						}
					}
				}
			}

			// Generate study materials for demo data (4-5 materials per class)
			$study_material_records = 0;
			$study_material_types = array(
				'PDF Document', 'Video Lecture', 'Presentation', 'Assignment',
				'Practice Test', 'Reference Material', 'Tutorial', 'Notes'
			);

			$study_material_descriptions = array(
				'Comprehensive study guide covering all key concepts',
				'Interactive video tutorial with step-by-step explanations',
				'PowerPoint presentation with visual aids and examples',
				'Practice assignment to reinforce learning objectives',
				'Sample test questions with detailed solutions',
				'Additional reference material for advanced learners',
				'Quick tutorial video for complex topics',
				'Handwritten notes with important formulas and concepts'
			);

			// Create school_administrator user first before creating study materials
			$admin_username = 'school_administrator';
			$admin_password = '123456';

			// Check if school_administrator user exists
			$admin_user = get_user_by('login', $admin_username);

			if (!$admin_user) {
				// Create the school_administrator user
				$user_data = array(
					'user_login' => $admin_username,
					'user_email' => $admin_username . '@example.com',
					'user_pass'  => $admin_password,
					'first_name' => 'School',
					'last_name'  => 'Administrator',
					'display_name' => 'School Administrator',
					'role'       => 'subscriber'
				);

				$admin_user_id = wp_insert_user($user_data);
				if (is_wp_error($admin_user_id)) {
					throw new Exception('Failed to create school_administrator user: ' . $admin_user_id->get_error_message());
				}
			} else {
				$admin_user_id = $admin_user->ID;
			}

			// Create school_administrator data for study material creation
			$school_admin_data = array(
				'id' => null, // Will be set after admin record is created
				'school_id' => $school_id,
				'designation' => 'School Administrator',
				'user_id' => $admin_user_id
			);

			// Get all class-school combinations for study material assignment
			$class_schools = $wpdb->get_results(
				"SELECT cs.ID, cs.class_id, cs.school_id, c.label as class_label, s.label as school_label
				FROM " . WLSM_CLASS_SCHOOL . " cs
				JOIN " . WLSM_CLASSES . " c ON cs.class_id = c.ID
				JOIN " . WLSM_SCHOOLS . " s ON cs.school_id = s.ID
				WHERE cs.school_id IN (" . implode(',', array_map('intval', $created_schools)) . ")"
			);

			foreach ($class_schools as $class_school) {
				// Get sections for this class
				$class_sections = array_filter($created_sections, function($section) use ($class_school) {
					return $section['class_school_id'] == $class_school->ID;
				});

				// Get subjects for this school
				$class_subjects = array_filter($created_subjects, function($subject) use ($class_school) {
					return $subject['school_id'] == $class_school->school_id;
				});

				// Generate 4-5 study materials per class
				$materials_per_class = rand(4, 5);

				for ($i = 0; $i < $materials_per_class; $i++) {
					$material_type = $study_material_types[array_rand($study_material_types)];
					$description = $study_material_descriptions[array_rand($study_material_descriptions)];

					// Get demo_teacher user for added_by field, fallback to admin user
					$demo_teacher = get_user_by('login', 'demo_teacher');
					$added_by_user_id = $demo_teacher ? $demo_teacher->ID : 1; // fallback to admin user ID 1

					// Ensure we have a valid school_id from the class_school record
					$material_school_id = (int) $class_school->school_id;
					if (!$material_school_id) {
						throw new Exception('Invalid school_id for study material creation');
					}

					// Create study material
					$study_material_data = array(
						'label'       => $material_type . ' - ' . $class_school->class_label . ' Chapter ' . ($i + 1),
						'description' => $description,
						'url'         => 'https://example.com/materials/' . strtolower(str_replace(' ', '-', $material_type)) . '-' . ($i + 1) . '.pdf',
						'school_id'   => $material_school_id,
						'added_by'    => $added_by_user_id,
						'created_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_STUDY_MATERIALS, $study_material_data);
					if ($result === false) {
						throw new Exception('Failed to insert study material: ' . $wpdb->last_error);
					}

					$study_material_id = $wpdb->insert_id;

					// Get random section and subject for this study material
					$section_id = null;
					$subject_id = null;

					if (!empty($class_sections)) {
						$random_section = $class_sections[array_rand($class_sections)];
						$section_id = $random_section['id'];
					}

					if (!empty($class_subjects)) {
						$random_subject = $class_subjects[array_rand($class_subjects)];
						$subject_id = $random_subject['id'];
					}

					// Assign study material to class with section and subject
					$class_material_data = array(
						'class_school_id' => $class_school->ID,
						'study_material_id' => $study_material_id,
						'study_material_section_id' => $section_id,
						'study_material_subject_id' => $subject_id,
						'created_at' => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_CLASS_SCHOOL_STUDY_MATERIAL, $class_material_data);
					if ($result === false) {
						throw new Exception('Failed to assign study material to class: ' . $wpdb->last_error);
					}

					$study_material_records++;
				}
			}

			// Generate homework demo data
			$homework_records = 0;
			$homework_titles = array(
				'Complete Math Exercises 1-20',
				'Write English Essay on Environment',
				'Science Project: Solar System Model',
				'History Timeline Assignment',
				'Geography Map Work',
				'Physics Lab Report',
				'Chemistry Experiment Analysis',
				'Biology Cell Structure Diagram',
				'Computer Science Programming Task',
				'Art: Nature Sketching',
				'Music: Rhythm Practice',
				'Physical Education: Fitness Log',
				'Read Chapter 5 and Answer Questions',
				'Grammar Practice Worksheet',
				'Mathematics Problem Solving',
				'Social Studies Research Project'
			);

			$homework_descriptions = array(
				'Please complete exercises 1 through 20 from the textbook. Show all working steps clearly.',
				'Write a 500-word essay on the importance of environmental conservation in our daily lives.',
				'Create a 3D model of the solar system using recycled materials. Include all planets and key features.',
				'Create a timeline of important historical events from the last century. Include dates and descriptions.',
				'Complete the world map with major countries, capitals, and geographical features.',
				'Write a detailed report on the pendulum experiment conducted in class. Include observations and conclusions.',
				'Analyze the results of yesterday\'s chemistry experiment. Explain the chemical reactions observed.',
				'Draw and label the structure of a plant and animal cell. Include all major organelles.',
				'Write a simple program to calculate the area of different geometric shapes.',
				'Create a sketch of your favorite natural landscape. Use colored pencils and shading techniques.',
				'Practice rhythm patterns on the keyboard. Record yourself playing a simple melody.',
				'Maintain a daily fitness log for one week. Include exercises, duration, and progress notes.',
				'Read chapter 5 carefully and answer the review questions at the end of the chapter.',
				'Complete the grammar worksheet focusing on tenses, prepositions, and sentence structure.',
				'Solve the word problems using appropriate mathematical formulas and show all calculations.',
				'Research a famous historical figure and prepare a presentation on their contributions to society.'
			);

			$homework_subjects = array(
				'Mathematics', 'English', 'Science', 'History', 'Geography',
				'Physics', 'Chemistry', 'Biology', 'Computer Science', 'Art',
				'Music', 'Physical Education', 'Literature', 'Grammar', 'Problem Solving', 'Social Studies'
			);

			// Get all sections for homework assignment
			$all_sections = $wpdb->get_results(
				"SELECT sec.ID, sec.label as section_label, cs.ID as class_school_id, c.label as class_label, cs.school_id
				FROM " . WLSM_SECTIONS . " sec
				JOIN " . WLSM_CLASS_SCHOOL . " cs ON sec.class_school_id = cs.ID
				JOIN " . WLSM_CLASSES . " c ON cs.class_id = c.ID
				WHERE cs.school_id IN (" . implode(',', array_map('intval', $created_schools)) . ")"
			);

			foreach ($all_sections as $section) {
				// Generate 2-3 homework assignments per section
				$homework_per_section = rand(2, 3);

				for ($i = 0; $i < $homework_per_section; $i++) {
					$title_index = array_rand($homework_titles);
					$title = $homework_titles[$title_index] . ' - ' . $section->class_label . ' ' . $section->section_label;
					$description = $homework_descriptions[$title_index];
					$subject = $homework_subjects[$title_index];

					// Generate homework date (past or future)
					$days_offset = rand(-30, 30); // 30 days in past to 30 days in future
					$homework_date = date('Y-m-d', strtotime($days_offset . ' days'));

					// Generate due date (3-7 days after homework date)
					$due_days = rand(3, 7);
					$due_date = date('Y-m-d', strtotime($homework_date . ' +' . $due_days . ' days'));

					// Use demo_teacher as the creator, with fallback
					$demo_teacher = get_user_by('login', 'demo_teacher');
					$added_by = $demo_teacher ? $demo_teacher->ID : 1; // fallback to admin user ID 1

					// Create homework
					$homework_data = array(
						'title'            => $title,
						'description'      => $description,
						'subject'          => $subject,
						'homework_date'    => $homework_date,
						'homework_due_date' => $due_date,
						'attachment_url'   => 'https://example.com/homework/' . strtolower(str_replace(' ', '-', $title)) . '.pdf',
						'added_by'         => $added_by,
						'session_id'       => $current_demo_session_id,
						'school_id'        => $section->school_id,
						'created_at'       => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_HOMEWORK, $homework_data);
					if ($result === false) {
						throw new Exception('Failed to insert homework: ' . $wpdb->last_error);
					}

					$homework_id = $wpdb->insert_id;

					// Assign homework to section
					$homework_section_data = array(
						'homework_id' => $homework_id,
						'section_id'  => $section->ID,
						'created_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_HOMEWORK_SECTION, $homework_section_data);
					if ($result === false) {
						throw new Exception('Failed to assign homework to section: ' . $wpdb->last_error);
					}

					$homework_records++;
				}
			}

			// Generate events for each school
			$event_records = 0;
			$event_titles = array(
				'Annual Sports Day',
				'Parent-Teacher Meeting',
				'Science Exhibition',
				'Cultural Program',
				'Independence Day Celebration',
				'Teachers Day Celebration',
				'Annual Function',
				'Career Guidance Seminar',
				'Art and Craft Exhibition',
				'Music Competition',
				'Dance Competition',
				'Debate Competition',
				'Quiz Competition',
				'School Picnic',
				'Educational Trip'
			);

			$event_descriptions = array(
				'The Annual Sports Day will be celebrated at the school playground. All students are required to participate in various sporting events including track and field, team sports, and individual competitions.',
				'A meeting for parents and teachers to discuss student progress, academic performance, and future planning. Parents are requested to attend with their ward\'s report cards.',
				'Students will showcase their science projects and experiments. This is an opportunity for young scientists to demonstrate their creativity and scientific knowledge.',
				'A cultural program featuring dance, music, drama, and other performing arts. Students will present traditional and contemporary performances.',
				'Independence Day will be celebrated with flag hoisting, patriotic songs, speeches, and cultural programs to commemorate our national heritage.',
				'Teachers Day celebration to honor and appreciate the dedication of our teaching staff. Students will organize special programs and presentations.',
				'The Annual Function will feature academic awards, cultural performances, and prize distribution ceremony for outstanding students.',
				'A seminar on career guidance for senior students, featuring guest speakers from various professions sharing their experiences and advice.',
				'An exhibition of student artwork and craftwork. Students will display paintings, sculptures, handicrafts, and other creative works.',
				'A music competition featuring vocal and instrumental performances. Students will compete in classical, folk, and contemporary music categories.',
				'A dance competition showcasing various dance forms including classical, folk, and contemporary styles. Participants will perform solo and group dances.',
				'A debate competition on current topics and social issues. Students will participate in English and regional language debates.',
				'An inter-class quiz competition covering various subjects including general knowledge, science, mathematics, and literature.',
				'An annual picnic for students to enjoy outdoor activities, games, and bonding time with classmates and teachers.',
				'An educational trip to a museum or historical site to enhance learning through real-world experiences and field visits.'
			);

			foreach ($created_schools as $school_id) {
				// Generate 20 events per school
				$events_per_school = 20;

				for ($i = 0; $i < $events_per_school; $i++) {
					$title_index = array_rand($event_titles);
					$title = $event_titles[$title_index];
					$description = $event_descriptions[$title_index];

					// Generate event date (next 3-6 months)
					$days_offset = rand(30, 180); // 1-6 months in future
					$event_date = date('Y-m-d', strtotime('+' . $days_offset . ' days'));

					// Use demo_teacher as the creator, with fallback
					$demo_teacher = get_user_by('login', 'demo_teacher');
					$added_by = $demo_teacher ? $demo_teacher->ID : 1; // fallback to admin user ID 1

					// Create event
					$event_data = array(
						'title'       => $title,
						'description' => $description,
						'event_date'  => $event_date,
						'is_active'   => 1,
						'school_id'   => $school_id,
						'added_by'    => $added_by,
						'created_at'  => current_time('mysql'),
						'updated_at'  => current_time('mysql')
					);

					$result = $wpdb->insert(WLSM_EVENTS, $event_data);
					if ($result === false) {
						throw new Exception('Failed to insert event: ' . $wpdb->last_error);
					}

					$event_records++;
				}
			}

			// ===== Generate exam groups and exams =====
			$exam_group_records = 0;
			$exam_records = 0;

			if (!empty($created_schools)) {
				// Build current-session class_school map and preload their subjects only
				$class_school_by_school = array();
				$cs_ids = array();
				foreach ($created_schools as $sid) {
					if (!empty($current_session_class_schools_by_school[$sid])) {
						$class_school_by_school[$sid] = array_map('intval', $current_session_class_schools_by_school[$sid]);
						$cs_ids = array_merge($cs_ids, $class_school_by_school[$sid]);
					}
				}

				$subjects_by_class_school = array();
				if (!empty($cs_ids)) {
					$cs_ids_sql = implode(',', array_map('intval', $cs_ids));
					$subjects = $wpdb->get_results(
						"SELECT ID, label, type, class_school_id FROM " . WLSM_SUBJECTS . " WHERE class_school_id IN (" . $cs_ids_sql . ")"
					);
					foreach ($subjects as $subj) {
						$subjects_by_class_school[(int)$subj->class_school_id][] = $subj;
					}
				}

				$exam_group_labels = array(
					'Term 1', 'Term 2', 'Quarterly', 'Annual Exam'
				);

				$exam_titles = array(
					'Periodic Test', 'Unit Test', 'Mid Term Examination', 'Final Examination',
					'Class Test', 'Assessment', 'Quarterly Examination', 'Half Yearly Exam', 'Annual Examination'
				);

				foreach ($created_schools as $school_id) {
					$group_ids = array();
					// Create 4 exam groups per school
					foreach ($exam_group_labels as $egl) {
						$result = $wpdb->insert(WLSM_EXAMS_GROUP, array(
							'label'      => $egl,
							'is_active'  => 1,
							'school_id'  => $school_id,
							'created_at' => current_time('mysql'),
							'updated_at' => current_time('mysql')
						));
						if ($result === false) {
							throw new Exception('Failed to insert exam group: ' . $wpdb->last_error);
						}
						$group_ids[] = (string)$wpdb->insert_id; // stored as varchar in exams table
						$exam_group_records++;
					}

					// Create 8 exams per school
					$exams_to_create = 8;
					$school_cs_ids = isset($class_school_by_school[$school_id]) ? $class_school_by_school[$school_id] : array();

					for ($ei = 0; $ei < $exams_to_create; $ei++) {
						$exam_label = $exam_titles[array_rand($exam_titles)];
						$start_offset_days = rand(10, 90);
						$exam_start = date('Y-m-d', strtotime('+' . $start_offset_days . ' days'));
						$duration_days = rand(5, 12);
						$exam_end = date('Y-m-d', strtotime($exam_start . ' +' . $duration_days . ' days'));

						$exam_group = !empty($group_ids) ? $group_ids[array_rand($group_ids)] : null;

						// Default grade criteria (enable overall grade) and psychomotor setup
						$grade_criteria = WLSM_Config::get_default_grade_criteria();
						$grade_criteria['enable_overall_grade'] = true; // enforce overall grade enabled

						// Consistent psychomotor headings and definitions across all exams
						$default_psychomotor = array(
							'psych' => array(
								'Neatness',
								'Handwriting',
								'Punctuality',
								'Discipline',
								'Teamwork',
								'Creativity',
								'Participation',
								'Homework',
							),
							// Scale definitions shown in print (1..5)
							'def' => array(
								'Needs Improvement',
								'Satisfactory',
								'Good',
								'Very Good',
								'Excellent'
							),
							// Reserved for future use at exam level
							'scale' => array()
						);

						$exam_insert = array(
							'label'                 => $exam_label,
							'exam_center'           => 'Main Campus',
							'start_date'            => $exam_start,
							'end_date'              => $exam_end,
							'is_active'             => 1,
							'show_in_assessment'    => 1,
							'enable_room_numbers'   => rand(0, 1),
							'admit_cards_published' => 1,
							'time_table_published'  => 1,
							'results_published'     => 1,
							'enable_total_marks'    => 'yes',
							'results_obtained_marks' => 'yes',
							// Persist default grade criteria and psychomotor settings
							'grade_criteria'        => serialize($grade_criteria),
							'psychomotor_analysis'  => 1,
							'psychomotor'           => serialize($default_psychomotor),
							'exam_group'            => $exam_group,
							'school_id'             => $school_id,
							'created_at'            => current_time('mysql'),
							'updated_at'            => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_EXAMS, $exam_insert);
						if ($result === false) {
							throw new Exception('Failed to insert exam: ' . $wpdb->last_error);
						}

						$exam_id = (int) $wpdb->insert_id;
						$exam_records++;

						// Link exam to only one class for cleaner demo data
						if (!empty($school_cs_ids)) {
							// Select one random class for this exam
							$selected_cs_id = $school_cs_ids[array_rand($school_cs_ids)];

							$wpdb->insert(WLSM_CLASS_SCHOOL_EXAM, array(
								'class_school_id' => $selected_cs_id,
								'exam_id'        => $exam_id,
								'created_at'      => current_time('mysql'),
								'updated_at'      => current_time('mysql')
							));

							// Use this single class for subject pool
							$subject_pool = array();
							if (isset($subjects_by_class_school[$selected_cs_id])) {
								$subject_pool = $subjects_by_class_school[$selected_cs_id];
							}

							// Create 3-5 exam papers using available subjects (fallback to generic if none)
							$papers_count = rand(3, 5);
							for ($pi = 1; $pi <= $papers_count; $pi++) {
								$paper_date = date('Y-m-d', strtotime($exam_start . ' +' . ($pi - 1) . ' days'));
								$start_time = '09:00:00';
								$end_time   = '12:00:00';
								$room       = 'R-' . rand(101, 120);
								$paper_order = $pi * 10;

								$subject_label = 'Paper ' . $pi;
								$subject_type  = 'theory';
								$subject_id    = null;
								if (!empty($subject_pool)) {
									$chosen = $subject_pool[array_rand($subject_pool)];
									$subject_label = $chosen->label;
									$subject_type  = $chosen->type ? $chosen->type : 'theory';
									$subject_id    = (int) $chosen->ID;
								}

								$paper_code = 'EX-' . $exam_id . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $subject_label), 0, 3)) . '-' . $pi;

								$paper_insert = array(
									'subject_label' => $subject_label,
									'subject_type'  => $subject_type,
									'paper_code'    => $paper_code,
									'paper_date'    => $paper_date,
									'paper_order'   => $paper_order,
									'start_time'    => $start_time,
									'end_time'      => $end_time,
									'room_number'   => $room,
									'maximum_marks' => 100,
									'exam_id'       => $exam_id,
									'subject_id'    => $subject_id,
									'created_at'    => current_time('mysql'),
									'updated_at'    => current_time('mysql')
								);

								$wpdb->insert(WLSM_EXAM_PAPERS, $paper_insert);
							}
						}
					}
				}
			}

			// Generate demo admit cards and exam results
			if ($exam_records > 0) {
				$admit_card_records = 0;
				$exam_result_records = 0;

				// Define consistent psychomotor headings for results
				$default_psychomotor = array(
					'psych' => array(
						'Neatness',
						'Handwriting',
						'Punctuality',
						'Discipline',
						'Teamwork',
						'Creativity',
						'Participation',
						'Homework',
					),
					'def' => array(
						'Needs Improvement',
						'Satisfactory',
						'Good',
						'Very Good',
						'Excellent'
					),
					'scale' => array()
				);

				// Get all demo exams with their assigned classes for this school
				$demo_exams_with_classes = $wpdb->get_results($wpdb->prepare(
					"SELECT e.ID as exam_id, e.label, cse.class_school_id
					FROM " . WLSM_EXAMS . " e
					JOIN " . WLSM_CLASS_SCHOOL_EXAM . " cse ON cse.exam_id = e.ID
					WHERE e.school_id = %d",
					$school_id
				));

				foreach ($demo_exams_with_classes as $exam_class) {
					// Get students only from the class that has this exam
					$session_for_students = $current_demo_session_id ? $current_demo_session_id : $first_session_id;
					$demo_students = $wpdb->get_results($wpdb->prepare(
						"SELECT sr.ID as student_record_id, sr.name as student_name
						FROM " . WLSM_STUDENT_RECORDS . " sr
						JOIN " . WLSM_SECTIONS . " s ON s.ID = sr.section_id
						JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = s.class_school_id
						WHERE sr.session_id = %d AND cs.ID = %d
						ORDER BY sr.name
						LIMIT 10",
						$session_for_students, $exam_class->class_school_id
					));
					foreach ($demo_students as $student) {
						// Generate admit card for each student-exam combination
						$roll_number = 'ROLL' . str_pad($student->student_record_id, 4, '0', STR_PAD_LEFT);

						$admit_card_insert = array(
							'roll_number'        => $roll_number,
							'exam_id'           => $exam_class->exam_id,
							'student_record_id' => $student->student_record_id,
							'created_at'        => current_time('mysql'),
							'updated_at'        => current_time('mysql')
						);

						$result = $wpdb->insert(WLSM_ADMIT_CARDS, $admit_card_insert);
						if ($result !== false) {
							$admit_card_id = (int) $wpdb->insert_id;
							$admit_card_records++;

							// Get all exam papers for this exam with subject information
							$exam_papers = $wpdb->get_results($wpdb->prepare(
								"SELECT ep.ID as paper_id, ep.maximum_marks, ep.subject_id,
								        s.label as subject_name, s.code as subject_code
								FROM " . WLSM_EXAM_PAPERS . " ep
								LEFT JOIN " . WLSM_SUBJECTS . " s ON s.ID = ep.subject_id
								WHERE ep.exam_id = %d",
								$exam_class->exam_id
							));

							// Generate results for each paper
							foreach ($exam_papers as $paper) {
								$max_marks = (int) $paper->maximum_marks;
								// Generate realistic marks (60-95% of maximum)
								$obtained_marks = rand(round($max_marks * 0.6), round($max_marks * 0.95));

								// Default psychomotor scale per student (consistent length with headings)
								$default_scale = array_fill(0, count($default_psychomotor['psych']), 3); // default to 3 = Good

								// Derive simple remark text based on performance
								$percent = $max_marks > 0 ? ($obtained_marks / $max_marks) * 100 : 0;
								if ($percent >= 85) {
									$remark_text = 'Excellent';
									$teacher_remark_text = 'Outstanding work.';
									$school_remark_text = 'Keep striving for excellence.';
								} elseif ($percent >= 70) {
									$remark_text = 'Very Good';
									$teacher_remark_text = 'Good effort, keep it up.';
									$school_remark_text = 'Consistent performance noted.';
								} elseif ($percent >= 55) {
									$remark_text = 'Good';
									$teacher_remark_text = 'Can do even better with practice.';
									$school_remark_text = 'Continue improving steadily.';
								} elseif ($percent >= 40) {
									$remark_text = 'Satisfactory';
									$teacher_remark_text = 'Needs more practice.';
									$school_remark_text = 'We encourage additional study.';
								} else {
									$remark_text = 'Needs Improvement';
									$teacher_remark_text = 'Please meet your teacher for guidance.';
									$school_remark_text = 'Support will be provided to improve.';
								}

								$result_insert = array(
									'obtained_marks'   => $obtained_marks,
									'exam_paper_id'   => $paper->paper_id,
									'admit_card_id'   => $admit_card_id,
									'scale'           => serialize($default_scale),
									'remark'          => $remark_text,
									'teacher_remark'  => $teacher_remark_text,
									'school_remark'   => $school_remark_text,
									'created_at'      => current_time('mysql'),
									'updated_at'      => current_time('mysql')
								);

								$result_res = $wpdb->insert(WLSM_EXAM_RESULTS, $result_insert);
								if ($result_res !== false) {
									$exam_result_records++;
								}
							}
						}
					}
				}
			}

			// Enroll all students in their class subjects
			self::enroll_students_in_subjects();

			$wpdb->query('COMMIT;');

			// Now assign the school_administrator as the school admin (after study materials are created)
			if ($admin_user_id && $school_id) {
				// Check if staff record already exists for this user and school
				$existing_staff = $wpdb->get_row($wpdb->prepare(
					'SELECT ID FROM ' . WLSM_STAFF . ' WHERE school_id = %d AND user_id = %d',
					$school_id, $admin_user_id
				));

				if ($existing_staff) {
					// Update existing staff record
					$staff_data = array(
						'school_id' => $school_id,
						'user_id'   => $admin_user_id,
						'role'      => WLSM_M_Role::get_admin_key(),
						'updated_at' => current_time('Y-m-d H:i:s')
					);
					$wpdb->update(WLSM_STAFF, $staff_data, array('ID' => $existing_staff->ID));
					$staff_id = $existing_staff->ID;
				} else {
					// Create new staff record
					$staff_data = array(
						'school_id' => $school_id,
						'user_id'   => $admin_user_id,
						'role'      => WLSM_M_Role::get_admin_key(),
						'created_at' => current_time('Y-m-d H:i:s')
					);

					$result = $wpdb->insert(WLSM_STAFF, $staff_data);
					if ($result === false) {
						throw new Exception('Failed to create staff record for school_administrator: ' . $wpdb->last_error);
					}
					$staff_id = $wpdb->insert_id;
				}

				// Check if admin record already exists
				$existing_admin = $wpdb->get_row($wpdb->prepare(
					'SELECT ID FROM ' . WLSM_ADMINS . ' WHERE staff_id = %d',
					$staff_id
				));

				if (!$existing_admin) {
					// Create admin record
					$admin_data = array(
						'name' => 'School Administrator',
						'designation' => 'School Administrator',
						'phone' => '555-0100',
						'email' => $admin_username . '@example.com',
						'address' => 'School Administration Office',
						'salary' => 0,
						'joining_date' => current_time('Y-m-d'),
						'staff_id' => $staff_id,
						'assigned_by_manager' => 1,
						'created_at' => current_time('Y-m-d H:i:s')
					);

					$result = $wpdb->insert(WLSM_ADMINS, $admin_data);
					if ($result === false) {
						throw new Exception('Failed to create admin record for school_administrator: ' . $wpdb->last_error);
					}

					// Update the school_admin_data with the admin ID
					$school_admin_data['id'] = $wpdb->insert_id;
				}

				// Set user meta for school
				update_user_meta($admin_user_id, 'wlsm_school_id', $school_id);

				// Set user meta for session (using the current demo session based on dates, fallback to first session)
				$session_to_set = $current_demo_session_id ? $current_demo_session_id : $first_session_id;
				if ($session_to_set) {
					update_user_meta($admin_user_id, 'wlsm_current_session', $session_to_set);
				}

				// Also set the current user's session to the demo session
				$current_user_id = get_current_user_id();
				if ($current_user_id && $session_to_set) {
					update_user_meta($current_user_id, 'wlsm_current_session', $session_to_set);
					update_user_meta($current_user_id, 'wlsm_school_id', $school_id);
				}
			}

			$stats = array(
				'schools' => 1,
				'sessions' => 1, // 1 per school
				'classes' => 5, // 5 classes per session * 1 session
				'sections' => 10, // 2 sections per class * 5 classes
				'students' => 50, // 5 students per section * 10 sections
				'admin_staff' => 4, // 4 per school
				'subjects' => 25, // 5 subjects per class * 5 classes
				'fee_types' => 15, // 3 per class (5 classes * 3 fees)
				'medium_types' => 3, // 3 per school
				'student_types' => 3, // 3 per school
				'roles' => 4, // 4 per school
				'concession_types' => 5, // 5 types per school
				'student_concessions' => 25, // About 5 students per concession type
				'concession_fee_mappings' => 15, // 3 mappings per concession type
				'books_issued' => 50, // Average number of books issued
				'transport_users' => 100, // Students using transport
				'hostel_residents' => 75, // Students in hostels
				'active_activities' => 10, // Number of ongoing activities
				'tickets' => 25, // Support tickets
				'chapters' => 40, // Total chapters across subjects
				'lectures' => 120, // Total lectures across chapters
				'staff_subject_assignments' => $staff_subject_assignments, // Staff assigned to subjects
				'timetable_routines' => $routine_assignments, // Class timetable entries
				'attendance_records' => $attendance_records, // Student attendance records for last 3 months
				'leave_records' => $leave_records, // Student leave requests
				'staff_attendance_records' => $staff_attendance_records, // Staff attendance records for last 3 months
				'staff_leave_records' => $staff_leave_records, // Staff leave requests
				'notice_records' => $notice_records, // Noticeboard entries
				'study_material_records' => $study_material_records, // Study material records
				'homework_records' => $homework_records, // Homework records
				'event_records' => $event_records, // Event records
				'exam_group_records' => isset($exam_group_records) ? $exam_group_records : 0,
				'exam_records' => isset($exam_records) ? $exam_records : 0,
				'admit_card_records' => isset($admit_card_records) ? $admit_card_records : 0,
				'exam_result_records' => isset($exam_result_records) ? $exam_result_records : 0,
				'expense_categories' => isset($created_expense_categories) ? count($created_expense_categories) : 0, // Expense categories
				'expenses' => isset($created_expenses) ? count($created_expenses) : 0, // Expense records
				'income_categories' => isset($created_income_categories) ? count($created_income_categories) : 0, // Income categories
				'income' => isset($created_income) ? count($created_income) : 0, // Income records
				'concession_types' => isset($created_concession_types_by_school[$school_id]) ? count($created_concession_types_by_school[$school_id]) : 0, // Concession types
				'student_concessions' => (int)$wpdb->get_var("SELECT COUNT(*) FROM " . WLSM_STUDENT_CONCESSION), // Student concessions assigned
				'concession_fee_mappings' => (int)$wpdb->get_var("SELECT COUNT(*) FROM " . WLSM_CONCESSION_FEE_MAPPINGS) // Concession-fee mappings
			);

			// Create fixed demo login credentials for consistent testing
			self::create_fixed_demo_credentials($school_id, $current_demo_session_id);

			return $stats;

		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			throw $exception;
		}
	}

	/**
	 * Generate admission invoice for a newly created student.
	 * - Pulls class fee types (prefers active_on_admission)
	 * - Creates student fee rows
	 * - Builds fee_list with period + session_total
	 * - Creates invoice with proper invoice_number
	 * - Populates due_date (nearest) and serialized due_date_amount / due_date_period schedules
	 * - Optionally creates a demo payment with proper receipt_number
	 */
	private static function generate_admission_invoice_for_student( $student_id, $school_id, $session_id ) {
		global $wpdb;

		// 1) Figure out student's class (sr -> section -> class_school -> class_id)
		$student_class_info = $wpdb->get_row( $wpdb->prepare(
			"SELECT cs.class_id, sec.ID AS section_id
			FROM " . WLSM_STUDENT_RECORDS . " sr
			JOIN " . WLSM_SECTIONS . " sec ON sec.ID = sr.section_id
			JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = sec.class_school_id
			WHERE sr.ID = %d",
			$student_id
		) );

		if ( ! $student_class_info ) {
			throw new Exception('Could not find student class information for invoice generation');
		}

		$class_id = (int) $student_class_info->class_id;

		// 2) Pull fee types for this class
		$fee_types = $wpdb->get_results( $wpdb->prepare(
			"SELECT label, amount, period
			FROM " . WLSM_FEES . "
			WHERE school_id = %d AND class_id = %d AND active_on_admission = 1",
			$school_id, $class_id
		) );

		if ( empty( $fee_types ) ) {
			$fee_types = $wpdb->get_results( $wpdb->prepare(
				"SELECT label, amount, period
				FROM " . WLSM_FEES . "
				WHERE school_id = %d AND class_id = %d",
				$school_id, $class_id
			) );
		}

		if ( empty( $fee_types ) ) {
			// Nothing to invoice for this class
			return;
		}

		// 3) Prepare session boundaries (your demo session uses Apr 1 -> Mar 31)
		$session = $wpdb->get_row( $wpdb->prepare(
			"SELECT start_date, end_date FROM " . WLSM_SESSIONS . " WHERE ID = %d",
			$session_id
		) );

		$session_start = $session ? $session->start_date : date('Y-04-01');
		$session_end   = $session ? $session->end_date   : date('Y-03-31', strtotime('+1 year'));

		// Set basic invoice details
		$date_issued = current_time('mysql');
		$admission_date = date('Y-m-d'); // Keep this for session calculations

		// Use all fees for demo data
		$selected_fees = $fee_types;

		$total_amount = 0.0;
		$fee_list = array();

		// Get invoice number
		$invoice_number = WLSM_M_Invoice::get_invoice_number($school_id);

		foreach ( $selected_fees as $idx => $fee ) {
			$label  = $fee->label;
			$amount = (float) $fee->amount;
			$period = $fee->period ? strtolower(trim($fee->period)) : 'one-time';

			// Create student fee record
			$student_fee_data = array(
				'student_record_id' => $student_id,
				'label'             => $label,
				'amount'            => $amount,
				'period'            => $period,
				'fee_order'         => 10 + $idx,
				'created_at'        => current_time('mysql')
			);
			$res = $wpdb->insert( WLSM_STUDENT_FEES, $student_fee_data );
			if ( false === $res ) {
				throw new Exception( 'Failed to insert student fee: ' . $wpdb->last_error );
			}

			// Calculate session total based on period
			$session_total = $amount;
			if ($period === 'monthly') {
				$session_total = $amount * 12;
			} else if ($period === 'quarterly') {
				$session_total = $amount * 4;
			}

			// Add to fee list
			$fee_list[] = array(
				'label'          => $label,
				'amount'         => $amount,
				'period'         => $period,
				'session_total'  => $session_total,
				'partial_payment'=> 0
			);

			$total_amount += $amount; // invoice face amount is sum of base per-fee amounts
		}

		// 7) Create invoice
		$invoice_data = array(
			'invoice_number'       => $invoice_number,
			'student_record_id'    => $student_id,
			'label'                => 'Admission Invoice',
			'amount'               => $total_amount,
			'invoice_amount_total' => $total_amount,  // keep in sync with face amount
			'discount'             => 0,
			'fee_list'             => serialize( $fee_list ),
			'partial_payment'      => 0,
			'date_issued'          => $date_issued,
			'added_by'             => get_current_user_id() ?: 1,
			'created_at'           => current_time('mysql')
		);

		$res = $wpdb->insert( WLSM_INVOICES, $invoice_data );
		if ( false === $res ) {
			throw new Exception( 'Failed to insert invoice: ' . $wpdb->last_error );
		}
		$invoice_id = $wpdb->insert_id;

		// 8) (Optional) Create a demo payment for some invoices (full/partial) with a proper receipt number
		if ( rand(0, 1) ) {
			$pay_amount     = ( rand(0,1) ? $total_amount : round($total_amount * 0.50, 2) );
			$receipt_number = self::get_next_receipt_number( $school_id );

			$payment_data = array(
				'invoice_id'         => $invoice_id,
				'student_record_id'  => $student_id,
				'amount'             => $pay_amount,
				'school_id'			 => $school_id,
				'receipt_number'     => $receipt_number,
				'payment_method'     => array('cash', 'cheque', 'online', 'bank_transfer')[ array_rand(array('cash','cheque','online','bank_transfer')) ],
				'transaction_id'     => 'TXN' . str_pad( rand(1,999999), 6, '0', STR_PAD_LEFT ),
				'note'               => 'Demo payment for admission',
				'added_by'           => get_current_user_id() ?: 1,
				'created_at'         => current_time('mysql')
			);
			$wpdb->insert( WLSM_PAYMENTS, $payment_data );

			$status = ( $pay_amount >= $total_amount ) ? 'paid' : 'partially_paid';
			$wpdb->update(
				WLSM_INVOICES,
				array( 'status' => $status, 'updated_at' => current_time('mysql') ),
				array( 'ID' => $invoice_id )
			);
		} else {
			// leave as unpaid
			$wpdb->update(
				WLSM_INVOICES,
				array( 'status' => 'unpaid', 'updated_at' => current_time('mysql') ),
				array( 'ID' => $invoice_id )
			);
		}
	}

	/**
	 * Next receipt number per school: REC-000001, REC-000002, ...
	 */
	private static function get_next_receipt_number( $school_id ) {
		global $wpdb;

		$last = $wpdb->get_var( $wpdb->prepare(
			"SELECT receipt_number
			FROM " . WLSM_PAYMENTS . "
			WHERE invoice_id IN (
					SELECT inv.ID
					FROM " . WLSM_INVOICES . " inv
					WHERE inv.student_record_id IN (
						SELECT sr.ID
						FROM " . WLSM_STUDENT_RECORDS . " sr
						JOIN " . WLSM_SECTIONS . " sec ON sec.ID = sr.section_id
						JOIN " . WLSM_CLASS_SCHOOL . " cs ON cs.ID = sec.class_school_id
						WHERE cs.school_id = %d
					)
			)
		ORDER BY ID DESC LIMIT 1",
			$school_id
		) );

		if ( $last && preg_match('/(\d+)$/', $last, $m) ) {
			$next = intval($m[1]) + 1;
		} else {
			$next = 1;
		}
		return 'REC-' . str_pad($next, 6, '0', STR_PAD_LEFT);
	}

	/**
	 * Generate sequential certificate numbers per school while keeping counters in sync.
	 */
	private static function get_next_certificate_number( $school_id ) {
		global $wpdb;

		$last_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT last_certificate_count FROM ' . WLSM_SCHOOLS . ' WHERE ID = %d',
				$school_id
			)
		);

		$next_count = $last_count + 1;

		$updated = $wpdb->update(
			WLSM_SCHOOLS,
			array('last_certificate_count' => $next_count),
			array('ID' => $school_id),
			array('%d'),
			array('%d')
		);

		if (false === $updated) {
			throw new Exception('Failed to update certificate counter: ' . $wpdb->last_error);
		}

		return (string) $next_count;
	}

	/**
	 * Map period to a session-wide count of installments.
	 * For demo: one-time=1, monthly=12, quarterly=4, half-yearly=2, yearly=1 (default=1).
	 */
	private static function session_multiplier_for_period( $period, $session_start, $session_end, $admission_date ) {
		switch ( strtolower($period) ) {
			case 'monthly':     return 12;
			case 'quarterly':   return 4;
			case 'half-yearly': return 2;
			case 'yearly':
			case 'annual':      return 1;
			case 'one-time':
			default:            return 1;
		}
	}

	/**
	 * Build a due schedule for a fee based on period.
	 * Returns array of ['due_date' => Y-m-d, 'amount' => float].
	 * For demo we spread from admission date within the session window.
	 */
	private static function build_due_schedule_for_period( $period, $amount, $session_start, $session_end, $admission_date ) {
		$period = strtolower( $period ?: 'one-time' );
		$out = array();

		$start = strtotime( $admission_date ?: $session_start );
		$end   = strtotime( $session_end );

		$push = function( $ts, $amt ) use (&$out) {
			$out[] = array(
				'due_date' => date('Y-m-d', $ts),
				'amount'   => round( (float)$amt, 2 )
			);
		};

		switch ( $period ) {
			case 'monthly':
				for ( $i=0; $i<12; $i++ ) {
					$ts = strtotime("+$i month", $start);
					if ( $ts <= $end ) $push( $ts, $amount );
				}
				break;

			case 'quarterly':
				for ( $i=0; $i<4; $i++ ) {
					$ts = strtotime("+".(3*$i)." month", $start);
					if ( $ts <= $end ) $push( $ts, $amount );
				}
				break;

			case 'half-yearly':
				for ( $i=0; $i<2; $i++ ) {
					$ts = strtotime("+".(6*$i)." month", $start);
					if ( $ts <= $end ) $push( $ts, $amount );
				}
				break;

			case 'yearly':
			case 'annual':
				$push( $start, $amount );
				break;

			case 'one-time':
			default:
				// Due in 30 days by default
				$push( strtotime('+30 days', $start), $amount );
				break;
		}

		// If nothing made it into session range, keep at least one in 30 days:
		if ( empty($out) ) {
			$push( strtotime('+30 days', $start), $amount );
		}

		return $out;
	}


}
