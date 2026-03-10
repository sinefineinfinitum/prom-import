<?php
// Robust router for PHP built-in server to properly serve WordPress and REST routes
// Ensures pretty permalinks like /wp-json/... are routed to index.php

$root = realpath(__DIR__ . '/..');
chdir($root);

if (PHP_SAPI === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $path = $url['path'] ?? '/';

    // Serve existing files directly (assets, uploads, etc.)
    $file = $root . '/' . ltrim($path, '/');
    if (is_file($file)) {
        return false;
    }
}

// Fallback to WordPress front controller
$_SERVER['DOCUMENT_ROOT']  = $root;
$_SERVER['SCRIPT_NAME']    = '/index.php';
$_SERVER['SCRIPT_FILENAME']= $root . '/index.php';
require $root . '/index.php';
