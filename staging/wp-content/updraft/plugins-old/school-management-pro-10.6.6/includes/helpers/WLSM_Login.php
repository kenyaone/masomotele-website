<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';

class WLSM_Login
{
    public static function redirect_to_dashboard($redirect_to, $request, $user)
    {
        if ( WLSM_ADMIN_CAPABILITY !== 'manage_school_management' ) {
			return $redirect_to;
		}

        if (isset($user->roles) && is_array($user->roles)) {
            // Check if user is a staff member in any school.
            $user_info = WLSM_M_Role::get_user_info($user->ID);

            if (! empty($user_info['schools_assigned'])) {
                // User is assigned to at least one school.
                $redirect_to = admin_url('admin.php?page=' . WLSM_MENU_STAFF_SCHOOL);
            }
        }

        return $redirect_to;
    }
}
