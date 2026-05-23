<?php
require_once __DIR__ . '/includes/init.php';
$auth = new Auth();
header('Location: ' . SITE_URL . ($auth->isLoggedIn() ? '/dashboard.php' : '/login.php'));
exit;
