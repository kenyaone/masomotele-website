<?php
/**
 * Front to the WordPress application.
 * Serves index.html for homepage, WordPress for everything else.
 *
 * @package WordPress
 */

// If this is the homepage request, serve index.html
$request_uri = $_SERVER['REQUEST_URI'];
if ($request_uri === '/' || $request_uri === '/index.php' || $request_uri === '/index.html') {
    if (file_exists(__DIR__ . '/index.html')) {
        readfile(__DIR__ . '/index.html');
        exit;
    }
}

// Everything else goes through WordPress
define('WP_USE_THEMES', true);
require __DIR__ . '/wp-blog-header.php';