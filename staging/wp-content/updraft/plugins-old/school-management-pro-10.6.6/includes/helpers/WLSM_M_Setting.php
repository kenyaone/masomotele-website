<?php
defined('ABSPATH') || die();

class WLSM_M_Setting
{
	public static function get_settings_general($school_id)
	{
		global $wpdb;
		$school_logo                 		= NULL;
		$school_signature            		= NULL;
		$student_logout_redirect_url 		= NULL;
		$school_app_url              		= NULL;
		$hide_library                		= 1;
		$hide_transport              		= 1;
		$school_currency             		= 1;
		$invoice_copies              		= false;
		$invoice_auto                		= false;
		$fee_on_promotion            		= false;
		$invoices_on_promotion       		= false;
		$invoices_history            		= false;
		$auto_invoice_allow_partial_payment = 0;
		$before_due_date = 0;
		$after_due_date  = 0;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "general"', $school_id));
		if ($settings) {
			$settings                    		= unserialize($settings->setting_value);
			$school_logo                 		= isset($settings['school_logo']) ? $settings['school_logo'] : '';
			$school_signature            		= isset($settings['school_signature']) ? $settings['school_signature'] : '';
			$student_logout_redirect_url 		= isset($settings['student_logout_redirect_url']) ? $settings['student_logout_redirect_url'] : '';
			$school_app_url              		= isset($settings['school_app_url']) ? $settings['school_app_url'] : '';
			$hide_library                		= isset($settings['hide_library']) ? $settings['hide_library'] : '';
			$hide_transport              		= isset($settings['hide_transport']) ? $settings['hide_transport'] : '';
			$school_currency             		= isset($settings['school_currency']) ? $settings['school_currency'] : '';
			$invoice_copies              		= isset($settings['invoice_copies']) ? (bool) $settings['invoice_copies'] : false;
			$invoice_auto                		= isset($settings['invoice_auto']) ? (bool) $settings['invoice_auto'] : false;
			$fee_on_promotion            		= isset($settings['fee_on_promotion']) ? (bool) $settings['fee_on_promotion'] : false;
			$invoices_on_promotion       		= isset($settings['invoices_on_promotion']) ? (bool) $settings['invoices_on_promotion'] : false;
			$invoices_history            		= isset($settings['invoices_history']) ? (bool) $settings['invoices_history'] : false;
			$before_due_date             		= isset($settings['before_due_date']) ? intval($settings['before_due_date']) : 0;
			$after_due_date              		= isset($settings['after_due_date']) ? intval($settings['after_due_date']) : 0;
			$auto_invoice_allow_partial_payment = isset($settings['auto_invoice_allow_partial_payment']) ? (bool) $settings['auto_invoice_allow_partial_payment'] : 0;
		}

		return array(
			'school_logo'                 			=> $school_logo,
			'school_signature'            			=> $school_signature,
			'student_logout_redirect_url' 			=> $student_logout_redirect_url,
			'school_app_url'              			=> $school_app_url,
			'hide_library'                			=> $hide_library,
			'hide_transport'              			=> $hide_transport,
			'school_currency'             			=> $school_currency,
			'invoice_copies'              			=> $invoice_copies,
			'invoice_auto'                			=> $invoice_auto,
			'fee_on_promotion'            			=> $fee_on_promotion,
			'invoices_on_promotion'       			=> $invoices_on_promotion,
			'invoices_history'            			=> $invoices_history,
			'before_due_date'             			=> $before_due_date,
			'after_due_date'              			=> $after_due_date,
			'auto_invoice_allow_partial_payment'    => $auto_invoice_allow_partial_payment,
		);
	}

	public static function get_settings_firebase($school_id)
	{
		global $wpdb;
		$enable_firebase = false;
		$json_file = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "firebase"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$json_file = isset($settings['json_file_path']) ? $settings['json_file_path'] : '';
			$enable_firebase = isset($settings['enable_firebase']) ? (bool) $settings['enable_firebase'] : false;
		}

		return array(
			'json_file' => $json_file,
			'enable_firebase' => $enable_firebase,
		);
	}

	public static function get_settings_background($school_id)
	{
		global $wpdb;
		$id_card_background     = NULL;
		$invoice_card_background  = NULL;
		$result_card_background = NULL;


		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "background"', $school_id));
		if ($settings) {
			$settings               = unserialize($settings->setting_value);
			$id_card_background     = isset($settings['id_card_background']) ? $settings['id_card_background'] : '';
			$invoice_card_background  = isset($settings['invoice_card_background']) ? $settings['invoice_card_background'] : '';
			$result_card_background = isset($settings['result_card_background']) ? $settings['result_card_background'] : '';
		}

		return array(
			'id_card_background'     => $id_card_background,
			'invoice_card_background'  => $invoice_card_background,
			'result_card_background' => $result_card_background,
		);
	}

	public static function get_settings_logs($school_id)
	{
		global $wpdb;
		$activity_logs     = false;
		$delete_after_days = 20;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "logs"', $school_id));
		if ($settings) {
			$settings          = unserialize($settings->setting_value);
			$activity_logs     = isset($settings['activity_logs']) ? (bool) $settings['activity_logs'] : false;
			$delete_after_days = isset($settings['delete_after_days']) ? absint($settings['delete_after_days']) : 20;
		}

		return array(
			'activity_logs'     => $activity_logs,
			'delete_after_days' => $delete_after_days,
		);
	}

	public static function get_settings_lessons($school_id)
	{
		global $wpdb;
		$student_login_required     = false;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "lessons"', $school_id));
		if ($settings) {
			$settings          = unserialize($settings->setting_value);
			$student_login_required     = isset($settings['student_login_required']) ? (bool) $settings['student_login_required'] : false;
		}

		return array(
			'student_login_required'     => $student_login_required,
		);
	}

	public static function get_settings_email($school_id)
	{
		global $wpdb;
		$carrier = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$carrier  = isset($settings['carrier']) ? $settings['carrier'] : '';
		}

		return array(
			'carrier' => $carrier,
		);
	}

	public static function get_settings_wp_mail($school_id)
	{
		global $wpdb;
		$from_name  = NULL;
		$from_email = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "wp_mail"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$from_name = isset($settings['from_name']) ? $settings['from_name'] : '';
			$from_email = isset($settings['from_email']) ? $settings['from_email'] : '';
		}

		return array(
			'from_name'  => $from_name,
			'from_email' => $from_email,
		);
	}

	public static function get_settings_smtp($school_id)
	{
		global $wpdb;
		$from_name  = NULL;
		$host       = NULL;
		$username   = NULL;
		$password   = NULL;
		$encryption = NULL;
		$port       = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smtp"', $school_id));

		if ($settings) {
			$settings   = unserialize($settings->setting_value);
			$from_name  = isset($settings['from_name']) ? $settings['from_name'] : '';
			$host       = isset($settings['host']) ? $settings['host'] : '';
			$username   = isset($settings['username']) ? $settings['username'] : '';
			$password   = isset($settings['password']) ? $settings['password'] : '';
			$encryption = isset($settings['encryption']) ? $settings['encryption'] : '';
			$port       = isset($settings['port']) ? $settings['port'] : '';
		}

		return array(
			'from_name'  => $from_name,
			'host'       => $host,
			'username'   => $username,
			'password'   => $password,
			'encryption' => $encryption,
			'port'       => $port,
		);
	}

	public static function get_settings_email_student_admission($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_admission"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_student_registration_to_student($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_registration_to_student"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_student_absent_to_student($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_absent_to_student"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_student_invoice_due_date_student($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_invoice_due_date_student"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_student_registration_to_admin($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_registration_to_admin"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_invoice_generated($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_invoice_generated"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_online_fee_submission($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_online_fee_submission"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_offline_fee_submission($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_offline_fee_submission"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_inquiry_received_to_inquisitor($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_inquiry_received_to_inquisitor"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_inquiry_received_to_admin($school_id)
	{
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_inquiry_received_to_admin"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$subject  = isset($settings['subject']) ? $settings['subject'] : '';
			$body     = isset($settings['body']) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_sms($school_id)
	{
		global $wpdb;
		$carrier = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$carrier  = isset($settings['carrier']) ? $settings['carrier'] : '';
		}

		return array(
			'carrier' => $carrier,
		);
	}

	public static function get_settings_smsstriker($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smsstriker"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_nextsms($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "nextsms"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_whatsapp($school_id)
	{
		global $wpdb;

		$username  = '';
		$password  = '';
		$api_key   = '';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "whatsapp"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$api_key   = isset($settings['api_key']) ? $settings['api_key'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'api_key'   => $api_key
		);
	}

	public static function get_settings_logixsms($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "logixsms"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_futuresol($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "futuresol"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_gatewaysms($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;
		$gwid = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "gatewaysms"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$gwid      = isset($settings['gwid']) ? $settings['gwid'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
			'gwid'      => $gwid,
		);
	}

	public static function get_settings_sms_ir($school_id)
	{
		global $wpdb;

		$username    = null;
		$password    = null;
		$sender_id   = null;
		$line_number = null;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_ir"', $school_id));
		if ($settings) {
			$settings    = unserialize($settings->setting_value);
			$username    = isset($settings['username']) ? $settings['username'] : '';
			$password    = isset($settings['password']) ? $settings['password'] : '';
			$sender_id   = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$line_number = isset($settings['line_number']) ? $settings['line_number'] : '';
		}

		return array(
			'username'    => $username,
			'password'    => $password,
			'sender_id'   => $sender_id,
			'line_number' => $line_number,
		);
	}

	public static function get_settings_bulksmsgateway($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bulksmsgateway"', $school_id));
		if ($settings) {
			$settings    = unserialize($settings->setting_value);
			$username    = isset($settings['username']) ? $settings['username'] : '';
			$password    = isset($settings['password']) ? $settings['password'] : '';
			$sender_id   = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'username'    => $username,
			'password'    => $password,
			'sender_id'   => $sender_id,
			'template_id' => $template_id,
		);
	}


	public static function get_settings_msgclub($school_id)
	{
		global $wpdb;

		$auth_key         = NULL;
		$sender_id        = NULL;
		$route_id         = 1;
		$sms_content_type = 'Unicode';
		$tmid             = null;
		$entityid         = null;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "msgclub"', $school_id));
		if ($settings) {
			$settings         = unserialize($settings->setting_value);
			$auth_key         = isset($settings['auth_key']) ? $settings['auth_key'] : '';
			$sender_id        = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$route_id         = isset($settings['route_id']) ? $settings['route_id'] : '';
			$sms_content_type = isset($settings['sms_content_type']) ? $settings['sms_content_type'] : '';
			$tmid             = isset($settings['tmid']) ? $settings['tmid'] : '';
			$entityid         = isset($settings['entityid']) ? $settings['entityid'] : '';
		}

		return array(
			'auth_key'         => $auth_key,
			'sender_id'        => $sender_id,
			'route_id'         => $route_id,
			'sms_content_type' => $sms_content_type,
			'tmid'             => $tmid,
			'entityid'         => $entityid,
		);
	}

	public static function get_settings_pointsms($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;
		$channel   = NULL;
		$route     = NULL;
		$peid      = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pointsms"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$channel   = isset($settings['channel']) ? $settings['channel'] : '';
			$route     = isset($settings['route']) ? $settings['route'] : '';
			$peid      = isset($settings['peid']) ? $settings['peid'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
			'channel'   => $channel,
			'route'     => $route,
			'peid'      => $peid,
		);
	}

	public static function get_settings_indiatext($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;
		$channel   = NULL;
		$route     = NULL;


		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "indiatext"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$channel   = isset($settings['channel']) ? $settings['channel'] : '';
			$route     = isset($settings['route']) ? $settings['route'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
			'channel'   => $channel,
			'route'     => $route,
		);
	}

	public static function get_settings_vinuthan($school_id)
	{
		global $wpdb;

		$username = NULL;
		$sender_id = NULL;
		$channel = NULL;
		$route = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "vinuthan"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$username = isset($settings['username']) ? $settings['username'] : '';
			$password = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$channel = isset($settings['channel']) ? $settings['channel'] : '';
			$route = isset($settings['route']) ? $settings['route'] : '';
		}

		return array(
			'username' => $username,
			'sender_id' => $sender_id,
			'channel' => $channel,
			'route' => $route,
		);
	}

	public static function get_settings_pob($school_id)
	{
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pob"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,

		);
	}

	public static function get_settings_nexmo($school_id)
	{
		global $wpdb;

		$api_key    = NULL;
		$api_secret = NULL;
		$from       = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "nexmo"', $school_id));
		if ($settings) {
			$settings   = unserialize($settings->setting_value);
			$api_key    = isset($settings['api_key']) ? $settings['api_key'] : '';
			$api_secret = isset($settings['api_secret']) ? $settings['api_secret'] : '';
			$from       = isset($settings['from']) ? $settings['from'] : '';
		}

		return array(
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
			'from'       => $from,
		);
	}

	public static function get_settings_nimratext($school_id)
	{
		global $wpdb;

		$username  		= NULL;
		$sender_name  	= NULL;
		$sms_type 		= NULL;
		$api_key 		= NULL;
		$peid 			= NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "nimratext"', $school_id));
		if ($settings) {
			$settings  		= unserialize($settings->setting_value);
			$username  		= isset($settings['username']) ? $settings['username'] : '';
			$sender_name  	= isset($settings['sender_name']) ? $settings['sender_name'] : '';
			$sms_type 		= isset($settings['sms_type']) ? $settings['sms_type'] : '';
			$api_key 		= isset($settings['api_key']) ? $settings['api_key'] : '';
			$peid 			= isset($settings['peid']) ? $settings['peid'] : '';
		}

		return array(
			'username'  	=> $username,
			'sender_name'  	=> $sender_name,
			'sms_type' 		=> $sms_type,
			'api_key' 		=> $api_key,
			'peid' 			=> $peid,
		);
	}

	public static function get_settings_smartsms($school_id)
	{
		global $wpdb;

		$api_key    = NULL;
		$api_secret = NULL;
		$from       = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smartsms"', $school_id));
		if ($settings) {
			$settings   = unserialize($settings->setting_value);
			$api_key    = isset($settings['api_key']) ? $settings['api_key'] : '';
			$api_secret = isset($settings['api_secret']) ? $settings['api_secret'] : '';
			$from       = isset($settings['from']) ? $settings['from'] : '';
		}

		return array(
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
			'from'       => $from,
		);
	}

	public static function get_settings_twilio($school_id)
	{
		global $wpdb;

		$sid   = NULL;
		$token = NULL;
		$from  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "twilio"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$sid      = isset($settings['sid']) ? $settings['sid'] : '';
			$token    = isset($settings['token']) ? $settings['token'] : '';
			$from     = isset($settings['from']) ? $settings['from'] : '';
		}

		return array(
			'sid'   => $sid,
			'token' => $token,
			'from'  => $from,
		);
	}

	public static function get_settings_msg91($school_id)
	{
		global $wpdb;

		$authkey = NULL;
		$route   = 4;
		$sender  = NULL;
		$country = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "msg91"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$authkey  = isset($settings['authkey']) ? $settings['authkey'] : '';
			$route    = isset($settings['route']) ? $settings['route'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
			$country  = isset($settings['country']) ? $settings['country'] : '';
		}

		return array(
			'authkey' => $authkey,
			'route'   => $route,
			'sender'  => $sender,
			'country' => $country,
		);
	}

	public static function get_settings_textlocal($school_id)
	{
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "textlocal"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_tecxsms($school_id)
	{
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "tecxsms"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_smslive247($school_id)
	{
		global $wpdb;

		$api_key   = '';
		$sender_id = '';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smslive247"', $school_id));
		if ($settings) {
			$settings   = unserialize($settings->setting_value);
			$api_key    = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender_id  = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'api_key'   => $api_key,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_arkesel($school_id)
	{
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "arkesel"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_bulksms_sa($school_id)
	{
		global $wpdb;

		$username  = '';
		$password  = '';
		$sender_id = '';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bulksms_sa"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$username  = isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_switchportlimited($school_id)
	{
		global $wpdb;

		$api_key   = NULL;
		$sender    = NULL;
		$client_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "switchportlimited"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$api_key   = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender    = isset($settings['sender']) ? $settings['sender'] : '';
			$client_id = isset($settings['client_id']) ? $settings['client_id'] : '';
		}

		return array(
			'api_key'   => $api_key,
			'sender'    => $sender,
			'client_id' => $client_id,
		);
	}

	public static function get_settings_bdbsms($school_id)
	{
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bdbsms"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_bulksmsbd($school_id)
	{
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bulksmsbd"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_kivalosolutions($school_id)
	{
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "kivalosolutions"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_egosms($school_id)
	{
		global $wpdb;

		$username = NULL;
		$password = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "egosms"', $school_id));
		if ($settings) {
			$settings 	= unserialize($settings->setting_value);
			$username  	= isset($settings['username']) ? $settings['username'] : '';
			$password  = isset($settings['password']) ? $settings['password'] : '';
			$sender   	= isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'username' => $username,
			'password'  => $password,
			'sender'   => $sender,
		);
	}

	public static function get_settings_ebulksms($school_id)
	{
		global $wpdb;

		$username = NULL;
		$api_key  = NULL;
		$sender   = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "ebulksms"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$username = isset($settings['username']) ? $settings['username'] : '';
			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'username' => $username,
			'api_key'  => $api_key,
			'sender'   => $sender,
		);
	}

	public static function get_settings_sendpk($school_id)
	{
		global $wpdb;


		$api_key  = NULL;
		$sender   = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sendpk"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);

			$api_key  = isset($settings['api_key']) ? $settings['api_key'] : '';
			$sender   = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(

			'api_key'  => $api_key,
			'sender'   => $sender,
		);
	}

	public static function get_settings_pindo($school_id)
	{
		global $wpdb;

		$api_token = NULL;
		$sender    = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pindo"', $school_id));
		if ($settings) {
			$settings  = unserialize($settings->setting_value);
			$api_token = isset($settings['api_token']) ? $settings['api_token'] : '';
			$sender    = isset($settings['sender']) ? $settings['sender'] : '';
		}

		return array(
			'api_token' => $api_token,
			'sender'    => $sender,
		);
	}

	public static function get_settings_charts($school_id)
	{
		global $wpdb;
		$chart_types  = array();
		$chart_enable = array();

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "charts"', $school_id));
		if ($settings) {
			$settings     = unserialize($settings->setting_value);
			$chart_types  = isset($settings['chart_types']) ? $settings['chart_types'] : array();
			$chart_enable = isset($settings['chart_enable']) ? $settings['chart_enable'] : array();
		}

		if (! is_array($chart_types)) {
			$chart_types = array();
		}

		if (! is_array($chart_enable)) {
			$chart_enable = array();
		}

		foreach (WLSM_Helper::charts() as $key => $value) {
			if (! isset($chart_types[$key]) || (! in_array($chart_types[$key], WLSM_Helper::chart_types()))) {
				$chart_types[$key] = WLSM_Helper::default_chart_types()[$key];
			}

			if (! isset($chart_enable[$key])) {
				$chart_enable[$key] = false;
			} else {
				$chart_enable[$key] = (bool) $chart_enable[$key];
			}
		}

		return array(
			'chart_types'  => $chart_types,
			'chart_enable' => $chart_enable,
		);
	}

	public static function get_settings_bbb($school_id)
	{
		global $wpdb;
		$api_key    = NULL;
		$api_secret = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "zoom"', $school_id));
		if ($settings) {
			$settings   = unserialize($settings->setting_value);
			$api_key    = isset($settings['api_key']) ? $settings['api_key'] : '';
			$api_secret = isset($settings['api_secret']) ? $settings['api_secret'] : '';
		}

		return array(
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
		);
	}

	public static function get_settings_certificate_qcode_url($school_id)
	{
		global $wpdb;
		$certificate_url = NULL;
		$result_url      = NULL;
		$admin_card_url  = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "url"', $school_id));
		if ($settings) {
			$settings        = unserialize($settings->setting_value);
			$certificate_url = isset($settings['certificate_url']) ? $settings['certificate_url'] : '';
			$result_url      = isset($settings['result_url']) ? $settings['result_url'] : '';
			$admin_card_url  = isset($settings['admin_card_url']) ? $settings['admin_card_url'] : '';
		}

		return array(
			'certificate_url' => $certificate_url,
			'result_url'      => $result_url,
			'admin_card_url'  => $admin_card_url,
		);
	}

	public static function get_settings_inquiry($school_id)
	{
		global $wpdb;

		$form_title      = esc_html__('Admission Inquiry', 'school-management');
		$phone_required  = true;
		$email_required  = false;
		$admin_email     = '';
		$admin_phone     = '';
		$success_message = '';
		$inquiry_redirect_url = '';

		$default_success_message = esc_html__('Your inquiry has been submitted successfully.', 'school-management');

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "inquiry"', $school_id));
		if ($settings) {
			$settings        = unserialize($settings->setting_value);
			$form_title      = isset($settings['form_title']) ? $settings['form_title'] : '';
			$phone_required  = isset($settings['phone_required']) ? (bool) $settings['phone_required'] : false;
			$email_required  = isset($settings['email_required']) ? (bool) $settings['email_required'] : false;
			$admin_email     = isset($settings['admin_email']) ? $settings['admin_email'] : '';
			$admin_phone     = isset($settings['admin_phone']) ? $settings['admin_phone'] : '';
			$success_message = isset($settings['success_message']) ? $settings['success_message'] : '';
			$inquiry_redirect_url = isset($settings['inquiry_redirect_url']) ? $settings['inquiry_redirect_url'] : '';
		}

		if (empty($success_message)) {
			$success_message = $default_success_message;
		}

		return array(
			'form_title'      => $form_title,
			'phone_required'  => $phone_required,
			'email_required'  => $email_required,
			'admin_email'     => $admin_email,
			'admin_phone'     => $admin_phone,
			'success_message' => $success_message,
			'inquiry_redirect_url' => $inquiry_redirect_url
		);
	}

	public static function get_settings_registration($school_id)
	{
		global $wpdb;

		$form_title            = esc_html__('Online Registration', 'school-management');
		$login_user            = 0;
		$redirect_url          = '';
		$create_invoice        = 1;
		$auto_admission_number = 0;                                                         // Auto generate admission number when registering student from back-end.
		$auto_roll_number      = 0;                                                         // Auto generate roll nubmer
		$admin_email           = '';
		$admin_phone           = '';
		$success_message       = '';
		$category              = '';
		$student_type          = 1;
		$student_photo         = '';
		$address               = '';
		$activity              = '';

		$dob               = 1;
		$student_aprove    = 0;
		$gender            = 1;
		$religion          = 1;
		$caste             = 1;
		$blood_group       = 1;
		$phone             = 1;
		$city              = 1;
		$state             = 1;
		$country           = 1;
		$transport         = 1;
		$parent_detail     = 1;
		$student_login     = 1;
		$parent_login      = 1;
		$id_number         = 1;
		$id_number         = 0;
		$parent_occupation = 1;
		$survey            = 0;
		$fees              = 0;
		$medium            = 0;
		$pan               = 0;
		$student_auto_subjects 	= 0;

		$dob_in_words   = 0;
		$birth_place    = 0;
		$mother_tongue  = 0;
		$school_details = 0;

		$pen 				= 0;
		$apaar 				= 0;
		$parent_id_number 	= 0;
		$add_sibling_button = 0;
		$registration_declaration_message = '';

		// Default gender list
		$gender_list = array(
			'male'   => esc_html__('Male', 'school-management'),
			'female' => esc_html__('Female', 'school-management')
		);

		$default_success_message = esc_html__('Your registration has been submitted. Please check your email.', 'school-management');

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "registration"', $school_id));
		if ($settings) {
			$settings              = unserialize($settings->setting_value);
			$form_title            = isset($settings['form_title']) ? $settings['form_title'] : '';
			$registration_declaration_message            = isset($settings['registration_declaration_message']) ? $settings['registration_declaration_message'] : '';
			$login_user            = isset($settings['login_user']) ? $settings['login_user'] : '';
			$redirect_url          = isset($settings['redirect_url']) ? $settings['redirect_url'] : '';
			$create_invoice        = isset($settings['create_invoice']) ? $settings['create_invoice'] : '';
			$auto_admission_number = isset($settings['auto_admission_number']) ? $settings['auto_admission_number'] : '';
			$auto_roll_number      = isset($settings['auto_roll_number']) ? $settings['auto_roll_number'] : '';
			$admin_email           = isset($settings['admin_email']) ? $settings['admin_email'] : '';
			$admin_phone           = isset($settings['admin_phone']) ? $settings['admin_phone'] : '';
			$success_message       = isset($settings['success_message']) ? $settings['success_message'] : '';

			$dob               = isset($settings['dob']) ? $settings['dob'] : '';
			$student_aprove    = isset($settings['student_aprove']) ? $settings['student_aprove'] : '';
			$gender            = isset($settings['gender']) ? $settings['gender'] : '';
			$religion          = isset($settings['religion']) ? $settings['religion'] : '';
			$caste             = isset($settings['caste']) ? $settings['caste'] : '';
			$blood_group       = isset($settings['blood_group']) ? $settings['blood_group'] : '';
			$phone             = isset($settings['phone']) ? $settings['phone'] : '';
			$city              = isset($settings['city']) ? $settings['city'] : '';
			$state             = isset($settings['state']) ? $settings['state'] : '';
			$country           = isset($settings['country']) ? $settings['country'] : '';
			$transport         = isset($settings['transport']) ? $settings['transport'] : '';
			$parent_detail     = isset($settings['parent_detail']) ? $settings['parent_detail'] : '';
			$parent_occupation = isset($settings['parent_occupation']) ? $settings['parent_occupation'] : '';
			$student_login     = isset($settings['student_login']) ? $settings['student_login'] : '';
			$parent_login      = isset($settings['parent_login']) ? $settings['parent_login'] : '';
			$id_number         = isset($settings['id_number']) ? $settings['id_number'] : '';
			$survey            = isset($settings['survey']) ? $settings['survey'] : '';
			$fees              = isset($settings['fees']) ? $settings['fees'] : '';
			$medium            = isset($settings['medium']) ? $settings['medium'] : '';
			$pan               = isset($settings['pan']) ? $settings['pan'] : '';

			$dob_in_words   = isset($settings['dob_in_words']) ? $settings['dob_in_words'] : '';
			$birth_place    = isset($settings['birth_place']) ? $settings['birth_place'] : '';
			$mother_tongue  = isset($settings['mother_tongue']) ? $settings['mother_tongue'] : '';
			$school_details = isset($settings['school_details']) ? $settings['school_details'] : '';
			$category       = isset($settings['category']) ? $settings['category'] : '';
			$student_type   = isset($settings['student_type']) ? $settings['student_type'] : '';
			$address        = isset($settings['address']) ? $settings['address'] : '';
			$activity       = isset($settings['activity']) ? $settings['activity'] : '';
			$student_photo  = isset($settings['student_photo']) ? $settings['student_photo'] : '';
			$student_auto_subjects  = isset($settings['student_auto_subjects']) ? $settings['student_auto_subjects'] : '';

			$pen  				= isset($settings['pen']) ? $settings['pen'] : '';
			$apaar  			= isset($settings['apaar']) ? $settings['apaar'] : '';
			$parent_id_number  	= isset($settings['parent_id_number']) ? $settings['parent_id_number'] : '';
			$add_sibling_button = isset($settings['add_sibling_button']) ? $settings['add_sibling_button'] : '';
			$gender_list        = isset($settings['gender_list']) && is_array($settings['gender_list']) ? $settings['gender_list'] : $gender_list;
		}

		if (empty($success_message)) {
			$success_message = $default_success_message;
		}

		return array(
			'form_title'            => $form_title,
			'registration_declaration_message'            => $registration_declaration_message,
			'login_user'            => (bool) $login_user,
			'redirect_url'          => $redirect_url,
			'create_invoice'        => (bool) $create_invoice,
			'auto_admission_number' => (bool) $auto_admission_number,
			'auto_roll_number'      => (bool) $auto_roll_number,
			'admin_email'           => $admin_email,
			'admin_phone'           => $admin_phone,
			'success_message'       => $success_message,
			'dob'                   => (bool)$dob,
			'student_aprove'        => (bool)$student_aprove,
			'gender'                => (bool)$gender,
			'religion'              => (bool)$religion,
			'caste'                 => (bool)$caste,
			'blood_group'           => (bool)$blood_group,
			'phone'                 => (bool)$phone,
			'city'                  => (bool)$city,
			'state'                 => (bool)$state,
			'country'               => (bool)$country,
			'transport'             => (bool)$transport,
			'parent_detail'         => (bool)$parent_detail,
			'parent_occupation'     => (bool)$parent_occupation,
			'id_number'             => (bool)$id_number,
			'survey'                => (bool)$survey,
			'fees'                  => (bool)$fees,
			'medium'                => (bool)$medium,
			'pan'                   => (bool)$pan,
			'student_login'         => (bool)$student_login,
			'parent_login'          => (bool)$parent_login,
			'dob_in_words'          => (bool)$dob_in_words,
			'birth_place'           => (bool)$birth_place,
			'mother_tongue'         => (bool)$mother_tongue,
			'school_details'        => (bool)$school_details,
			'category'              => (bool)$category,
			'activity'              => (bool)$activity,
			'address'               => (bool)$address,
			'student_type'          => (bool)$student_type,
			'student_photo'         => (bool)$student_photo,
			'student_auto_subjects' => (bool)$student_auto_subjects,
			'pen'     				=> (bool)$pen,
			'apaar'     			=> (bool)$apaar,
			'parent_id_number'     	=> (bool)$parent_id_number,
			'add_sibling_button'    => (bool)$add_sibling_button,
			'gender_list'           => $gender_list,
		);
	}

	public static function get_settings_dashboard($school_id)
	{
		global $wpdb;

		$school_invoice           = 1;
		$school_payment_history   = 1;
		$school_study_material    = 1;
		$school_home_work         = 1;
		$school_noticeboard       = 1;
		$school_events            = 1;
		$school_class_time_table  = 1;
		$school_live_classes      = 1;
		$school_books_issues      = 1;
		$school_exam_time_table   = 1;
		$school_admit_card        = 1;
		$school_exam_result       = 1;
		$school_certificate       = 1;
		$school_attendance        = 1;
		$school_leave_request     = 1;
		$school_enrollment_number = 0;
		$school_admission_number  = 1;
		$school_fee_structure     = 1;
		$school_tickets           = 1;

		$school_parent_id_card          = 1;
		$school_parent_fee_invoice      = 1;
		$school_parent_payement_history = 1;
		$school_parent_noticeboard      = 1;
		$school_parent_class_time_table = 1;
		$school_parent_exam_results     = 1;
		$school_parent_attendance       = 1;



		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "dashboard"', $school_id));
		if ($settings) {
			$settings                 = unserialize($settings->setting_value);
			$school_invoice           = isset($settings['school_invoice']) ? $settings['school_invoice'] : '';
			$school_payment_history   = isset($settings['school_payment_history']) ? $settings['school_payment_history'] : '';
			$school_study_material    = isset($settings['school_study_material']) ? $settings['school_study_material'] : '';
			$school_home_work         = isset($settings['school_home_work']) ? $settings['school_home_work'] : '';
			$school_noticeboard       = isset($settings['school_noticeboard']) ? $settings['school_noticeboard'] : '';
			$school_events            = isset($settings['school_events']) ? $settings['school_events'] : '';
			$school_class_time_table  = isset($settings['school_class_time_table']) ? $settings['school_class_time_table'] : '';
			$school_live_classes      = isset($settings['school_live_classes']) ? $settings['school_live_classes'] : '';
			$school_books_issues      = isset($settings['school_books_issues']) ? $settings['school_books_issues'] : '';
			$school_exam_time_table   = isset($settings['school_exam_time_table']) ? $settings['school_exam_time_table'] : '';
			$school_admit_card        = isset($settings['school_admit_card']) ? $settings['school_admit_card'] : '';
			$school_exam_result       = isset($settings['school_exam_result']) ? $settings['school_exam_result'] : '';
			$school_certificate       = isset($settings['school_certificate']) ? $settings['school_certificate'] : '';
			$school_attendance        = isset($settings['school_attendance']) ? $settings['school_attendance'] : '';
			$school_leave_request     = isset($settings['school_leave_request']) ? $settings['school_leave_request'] : '';
			$school_enrollment_number = isset($settings['school_enrollment_number']) ? $settings['school_enrollment_number'] : '';
			$school_admission_number  = isset($settings['school_admission_number']) ? $settings['school_admission_number'] : '';
			$school_fee_structure     = isset($settings['school_fee_structure']) ? $settings['school_fee_structure'] : '';
			$school_tickets           = isset($settings['school_tickets']) ? $settings['school_tickets'] : '';

			$school_parent_id_card          = isset($settings['parent_id_card']) ? $settings['parent_id_card'] : '';
			$school_parent_fee_invoice      = isset($settings['parent_fee_invoice']) ? $settings['parent_fee_invoice'] : '';
			$school_parent_payement_history = isset($settings['parent_payement_history']) ? $settings['parent_payement_history'] : '';
			$school_parent_noticeboard      = isset($settings['parent_noticeboard']) ? $settings['parent_noticeboard'] : '';
			$school_parent_class_time_table = isset($settings['parent_class_time_table']) ? $settings['parent_class_time_table'] : '';
			$school_parent_exam_results     = isset($settings['parent_exam_results']) ? $settings['parent_exam_results'] : '';
			$school_parent_attendance       = isset($settings['parent_attendance']) ? $settings['parent_attendance'] : '';
		}

		return array(
			'school_invoice'           => (bool)$school_invoice,
			'school_payment_history'   => (bool)$school_payment_history,
			'school_study_material'    => (bool)$school_study_material,
			'school_home_work'         => (bool)$school_home_work,
			'school_noticeboard'       => (bool)$school_noticeboard,
			'school_events'            => (bool)$school_events,
			'school_class_time_table'  => (bool)$school_class_time_table,
			'school_live_classes'      => (bool)$school_live_classes,
			'school_books_issues'      => (bool)$school_books_issues,
			'school_exam_time_table'   => (bool)$school_exam_time_table,
			'school_admit_card'        => (bool)$school_admit_card,
			'school_exam_result'       => (bool)$school_exam_result,
			'school_certificate'       => (bool)$school_certificate,
			'school_attendance'        => (bool)$school_attendance,
			'school_leave_request'     => (bool)$school_leave_request,
			'school_enrollment_number' => (bool)$school_enrollment_number,
			'school_admission_number'  => (bool)$school_admission_number,
			'school_fee_structure'     => (bool)$school_fee_structure,
			'school_tickets'           => (bool)$school_tickets,

			'parent_id_card'          => (bool)$school_parent_id_card,
			'parent_fee_invoice'      => (bool)$school_parent_fee_invoice,
			'parent_payement_history' => (bool)$school_parent_payement_history,
			'parent_noticeboard'      => (bool)$school_parent_noticeboard,
			'parent_class_time_table' => (bool)$school_parent_class_time_table,
			'parent_exam_results'     => (bool)$school_parent_exam_results,
			'parent_attendance'       => (bool)$school_parent_attendance,


		);
	}



	public static function get_settings_sms_student_admission($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_admission"', $school_id));

		if ($settings) {
			$settings    = unserialize($settings->setting_value);
			$enable      = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message     = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'      => $enable,
			'message'     => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_student_registration_to_student($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_registration_to_student"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_student_invoice_due_date_student($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_invoice_due_date_student"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_student_registration_to_admin($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_registration_to_admin"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_invoice_generated($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;



		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_invoice_generated"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_student_homework($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_homework"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_exam_result_report($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_exam_result_report"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_online_fee_submission($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_online_fee_submission"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_offline_fee_submission($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_offline_fee_submission"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_student_admission_to_parent($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_admission_to_parent"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_invoice_generated_to_parent($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_invoice_generated_to_parent"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_online_fee_submission_to_parent($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_online_fee_submission_to_parent"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_sms_offline_fee_submission_to_parent($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_offline_fee_submission_to_parent"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_absent_student($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_absent_student"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_inquiry_received_to_inquisitor($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_inquiry_received_to_inquisitor"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,

		);
	}

	public static function get_settings_sms_inquiry_received_to_admin($school_id)
	{
		global $wpdb;

		$enable  = false;
		$message = NULL;
		$template_id = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_inquiry_received_to_admin"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$message  = isset($settings['message']) ? $settings['message'] : '';
			$template_id = isset($settings['template_id']) ? $settings['template_id'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
			'template_id' => $template_id,
		);
	}

	public static function get_settings_razorpay($school_id)
	{
		global $wpdb;

		$enable          = false;
		$razorpay_key    = NULL;
		$razorpay_secret = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "razorpay"', $school_id));

		if ($settings) {
			$settings        = unserialize($settings->setting_value);
			$enable          = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$razorpay_key    = isset($settings['razorpay_key']) ? $settings['razorpay_key'] : '';
			$razorpay_secret = isset($settings['razorpay_secret']) ? $settings['razorpay_secret'] : '';
		}

		return array(
			'enable'          => $enable,
			'razorpay_key'    => $razorpay_key,
			'razorpay_secret' => $razorpay_secret,
		);
	}

	public static function get_settings_paytm($school_id)
	{
		global $wpdb;

		$enable           = false;
		$merchant_id      = NULL;
		$merchant_key     = NULL;
		$industry_type_id = 'Retail';
		$website          = 'WEBSTAGING';
		$mode             = 'staging'; // or "production".

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paytm"', $school_id));

		if ($settings) {
			$settings         = unserialize($settings->setting_value);
			$enable           = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$merchant_id      = isset($settings['merchant_id']) ? $settings['merchant_id'] : '';
			$merchant_key     = isset($settings['merchant_key']) ? $settings['merchant_key'] : '';
			$industry_type_id = isset($settings['industry_type_id']) ? $settings['industry_type_id'] : '';
			$website          = isset($settings['website']) ? $settings['website'] : '';
			$mode             = isset($settings['mode']) ? $settings['mode'] : '';
		}

		return array(
			'enable'           => $enable,
			'merchant_id'      => $merchant_id,
			'merchant_key'     => $merchant_key,
			'industry_type_id' => $industry_type_id,
			'website'          => $website,
			'mode'             => $mode,
		);
	}

	public static function get_settings_sslcommerz($school_id)
	{
		global $wpdb;

		$enable       = false;
		$store_id     = NULL;
		$store_passwd = NULL;
		$notify_url   = '';
		$mode         = 'sandbox';  // or "live".

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sslcommerz"', $school_id));

		if ($settings) {
			$settings     = unserialize($settings->setting_value);
			$enable       = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$store_id     = isset($settings['store_id']) ? $settings['store_id'] : '';
			$store_passwd = isset($settings['store_passwd']) ? $settings['store_passwd'] : '';
			$notify_url   = isset($settings['notify_url']) ? $settings['notify_url'] : '';
			$mode         = isset($settings['mode']) ? $settings['mode'] : 'sandbox';
		}

		return array(
			'enable'       => $enable,
			'store_id'     => $store_id,
			'store_passwd' => $store_passwd,
			'notify_url'   => admin_url('admin-ajax.php') . '?action=wlsm-p-pay-with-sslcommerz',
			'mode'         => $mode,
		);
	}

	public static function get_settings_stripe($school_id)
	{
		global $wpdb;

		$enable          = false;
		$publishable_key = NULL;
		$secret_key      = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "stripe"', $school_id));

		if ($settings) {
			$settings        = unserialize($settings->setting_value);
			$enable          = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$publishable_key = isset($settings['publishable_key']) ? $settings['publishable_key'] : '';
			$secret_key      = isset($settings['secret_key']) ? $settings['secret_key'] : '';
		}

		return array(
			'enable'          => $enable,
			'publishable_key' => $publishable_key,
			'secret_key'      => $secret_key,
		);
	}

	public static function get_settings_payu($school_id)
	{
		global $wpdb;

		$enable       = false;
		$merchant_key = '';
		$merchant_salt = '';
		$mode         = 'test';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "payu"', $school_id));

		if ($settings) {
			$settings       = unserialize($settings->setting_value);
			$enable         = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$merchant_key   = isset($settings['merchant_key']) ? $settings['merchant_key'] : '';
			$merchant_salt  = isset($settings['merchant_salt']) ? $settings['merchant_salt'] : '';
			$mode           = isset($settings['mode']) ? $settings['mode'] : 'test';
		}

		$payment_url = ('live' === $mode) ? 'https://secure.payu.in/_payment' : 'https://test.payu.in/_payment';

		return array(
			'enable'        => $enable,
			'merchant_key'  => $merchant_key,
			'merchant_salt' => $merchant_salt,
			'mode'          => $mode,
			'payment_url'   => $payment_url,
		);
	}

	public static function get_settings_paypal($school_id)
	{
		global $wpdb;

		$enable         = false;
		$business_email = '';
		$mode           = 'sandbox';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paypal"', $school_id));

		if ($settings) {
			$settings       = unserialize($settings->setting_value);
			$enable         = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$business_email = isset($settings['business_email']) ? $settings['business_email'] : '';
			$mode           = isset($settings['mode']) ? $settings['mode'] : '';
		}

		if ('live' === $mode) {
			$payment_url = 'https://www.paypal.com/cgi-bin/webscr';
		} else {
			$payment_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}

		return array(
			'enable'         => $enable,
			'business_email' => $business_email,
			'mode'           => $mode,
			'payment_url'    => $payment_url,
			'notify_url'     => admin_url('admin-ajax.php') . '?action=wlsm-p-pay-with-paypal',
		);
	}

	public static function get_settings_amberpay($school_id)
	{
		global $wpdb;

		$enable        = false;
		$client_id     = '';
		$signature_url = '';
		$payment_url   = '';
		$api_key       = '';
		$mode          = 'sandbox';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "amberpay"', $school_id));

		if ($settings) {
			$settings      = unserialize($settings->setting_value);
			$enable        = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$client_id     = isset($settings['client_id']) ? $settings['client_id'] : '';
			$api_key       = isset($settings['api_key']) ? $settings['api_key'] : '';
			$signature_url = isset($settings['signature_url']) ? $settings['signature_url'] : '';
			$payment_url   = isset($settings['payment_url']) ? $settings['payment_url'] : '';
			$api_key       = isset($settings['api_key']) ? $settings['api_key'] : '';
			$mode          = isset($settings['mode']) ? $settings['mode'] : '';
		}

		return array(
			'enable'        => $enable,
			'client_id'     => $client_id,
			'api_key'       => $api_key,
			'signature_url' => $signature_url,
			'payment_url'   => $payment_url,
			'mode'          => $mode,
		);
	}

	public static function get_settings_pesapal($school_id)
	{
		global $wpdb;

		$enable          = false;
		$consumer_key    = '';
		$consumer_secret = '';
		$mode            = 'sandbox';

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pesapal"', $school_id));

		if ($settings) {
			$settings        = unserialize($settings->setting_value);
			$enable          = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$consumer_key    = isset($settings['consumer_key']) ? $settings['consumer_key'] : '';
			$consumer_secret = isset($settings['consumer_secret']) ? $settings['consumer_secret'] : '';
			$mode            = isset($settings['mode']) ? $settings['mode'] : '';
		}

		if ('live' === $mode) {
			$payment_url = 'https://www.pesapal.com/api/PostPesapalDirectOrderV4';
			$status_url  = 'https://www.pesapal.com/api/querypaymentstatus';
		} else {
			$payment_url = 'https://demo.pesapal.com/api/PostPesapalDirectOrderV4';
			$status_url  = 'https://demo.pesapal.com/api/querypaymentstatus';
		}

		return array(
			'enable'          => $enable,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'mode'            => $mode,
			'payment_url'     => $payment_url,
			'status_url'      => $status_url,
			'notify_url'      => admin_url('admin-ajax.php') . '?action=wlsm-p-pay-with-pesapal',
		);
	}

	public static function get_settings_paystack($school_id)
	{
		global $wpdb;

		$enable              = false;
		$paystack_public_key = NULL;
		$paystack_secret_key = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paystack"', $school_id));

		if ($settings) {
			$settings            = unserialize($settings->setting_value);
			$enable              = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$paystack_public_key = isset($settings['paystack_public_key']) ? $settings['paystack_public_key'] : '';
			$paystack_secret_key = isset($settings['paystack_secret_key']) ? $settings['paystack_secret_key'] : '';
		}

		return array(
			'enable'              => $enable,
			'paystack_public_key' => $paystack_public_key,
			'paystack_secret_key' => $paystack_secret_key,
		);
	}

	public static function get_settings_authorize($school_id)
	{
		global $wpdb;

		$enable              = false;
		$authorize_public_key = NULL;
		$authorize_secret_key = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "authorize"', $school_id));

		if ($settings) {
			$settings            = unserialize($settings->setting_value);
			$enable              = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$authorize_public_key = isset($settings['authorize_public_key']) ? $settings['authorize_public_key'] : '';
			$authorize_secret_key = isset($settings['authorize_secret_key']) ? $settings['authorize_secret_key'] : '';
		}

		return array(
			'enable'              => $enable,
			'authorize_public_key' => $authorize_public_key,
			'authorize_secret_key' => $authorize_secret_key,
		);
	}

	public static function get_settings_bank_transfer($school_id)
	{
		global $wpdb;

		$enable  = false;
		$branch  = NULL;
		$account = NULL;
		$name    = NULL;
		$message = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bank_transfer"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$branch   = isset($settings['branch']) ? $settings['branch'] : '';
			$account  = isset($settings['account']) ? $settings['account'] : '';
			$name     = isset($settings['name']) ? $settings['name'] : '';
			$message  = isset($settings['message']) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'branch'  => $branch,
			'account' => $account,
			'name'    => $name,
			'message' => $message,
		);
	}

	/**
	 * Get cash payment settings
	 *
	 * @param int $school_id School ID
	 * @return array Cash payment settings
	 */
	public static function get_settings_cash($school_id)
	{
		global $wpdb;

		$enable = false;
		$instructions = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "cash"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$instructions = isset($settings['instructions']) ? $settings['instructions'] : '';
		}

		return array(
			'enable' => $enable,
			'instructions' => $instructions,
		);
	}

	/**
	 * Get check/cheque payment settings
	 *
	 * @param int $school_id School ID
	 * @return array Check payment settings
	 */
	public static function get_settings_check($school_id)
	{
		global $wpdb;

		$enable = false;
		$instructions = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "check"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$instructions = isset($settings['instructions']) ? $settings['instructions'] : '';
		}

		return array(
			'enable' => $enable,
			'instructions' => $instructions,
		);
	}

	/**
	 * Get demand draft payment settings
	 *
	 * @param int $school_id School ID
	 * @return array Demand draft payment settings
	 */
	public static function get_settings_demand_draft($school_id)
	{
		global $wpdb;

		$enable = false;
		$instructions = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "demand_draft"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$instructions = isset($settings['instructions']) ? $settings['instructions'] : '';
		}

		return array(
			'enable' => $enable,
			'instructions' => $instructions,
		);
	}

	/**
	 * Get card payment settings (for in-person card payments)
	 *
	 * @param int $school_id School ID
	 * @return array Card payment settings
	 */
	public static function get_settings_card($school_id)
	{
		global $wpdb;

		$enable = false;
		$instructions = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "card"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$instructions = isset($settings['instructions']) ? $settings['instructions'] : '';
		}

		return array(
			'enable' => $enable,
			'instructions' => $instructions,
		);
	}

	public static function get_settings_upi_transfer($school_id)
	{
		global $wpdb;

		$enable  = false;
		$qr      = NULL;
		$id      = NULL;
		$name    = NULL;
		$message = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "upi_transfer"', $school_id));

		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$enable   = isset($settings['enable']) ? (bool) $settings['enable'] : false;
			$qr       = isset($settings['qr']) ? $settings['qr'] : '';
			$id       = isset($settings['id']) ? $settings['id'] : '';
			$name     = isset($settings['name']) ? $settings['name'] : '';
			$message  = isset($settings['message']) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'qr'      => $qr,
			'id'      => $id,
			'name'    => $name,
			'message' => $message,
		);
	}

	public static function get_dash($invoices)
	{
		foreach ($invoices as $row) {
			$display = $row->status;
			return $display;
		}
	}
}
