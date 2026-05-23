<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/navigation.php';

$student_id = $student->ID;
$session_id = $student->session_id;
?>

<?php
require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/tickets.php';
