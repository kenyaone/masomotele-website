<?php
/**
 * Front to the WordPress application.
 * Serves index.html for homepage, WordPress for everything else.
 *
 * @package WordPress
 */

// Anyone landing on /index.php or /index.html directly (an old bookmark, a
// stale search result, a typed-out URL) gets bounced to the clean bare
// domain so the address bar never shows "index.php" — only the true "/"
// request falls through to the fast static-file serve below.
$request_uri = $_SERVER['REQUEST_URI'];
if ($request_uri === '/index.php' || $request_uri === '/index.html') {
    header('Location: /', true, 301);
    exit;
}

// If this is the homepage request, serve index.html
if ($request_uri === '/') {
    if (file_exists(__DIR__ . '/index.html')) {
        readfile(__DIR__ . '/index.html');
        exit;
    }
}

// Everything else goes through WordPress
define('WP_USE_THEMES', true);
require __DIR__ . '/wp-blog-header.php';