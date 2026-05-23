<?php
require_once __DIR__ . '/includes/init.php';
$auth = new Auth();
$auth->logout();
header('Location: ' . SITE_URL . '/login.php');
exit;
