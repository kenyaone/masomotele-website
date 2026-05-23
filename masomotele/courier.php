<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'save_settings') {
    $auth = new Auth(); $auth->requireRole(['admin']);
    foreach (['courier_email','courier_sheets_webhook','courier_custom_server','courier_auto_sync'] as $k) {
        $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key=?", [$k]);
        $val = $_POST[$k] ?? '';
        if ($exists) $db->update('settings', ['setting_value'=>$val], 'setting_key=?', [$k]);
        else $db->insert('settings', ['setting_key'=>$k,'setting_value'=>$val]);
    }
    header('Location: ' . SITE_URL . '/datapost.php?msg=settings_saved'); exit;
}

// Redirect to datapost
header('Location: ' . SITE_URL . '/datapost.php');
