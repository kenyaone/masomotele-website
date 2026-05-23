<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/global.php';
?>
<div class="wlsm container-fluid">
	<?php
	$disallow_session_change = true;

	require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/partials/header.php';

	require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/general/unassign-class/index.php';
	?>
</div>
