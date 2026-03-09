<?php
// Simple router for PHP built-in server to properly serve WordPress and REST routes
// This ensures pretty permalinks like /wp-json/... are routed to index.php

if (PHP_SAPI === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $path = $url['path'] ?? '/';

    // Serve existing files directly (assets, uploads, etc.)
    $file = __DIR__ . '/../' . ltrim($path, '/');
    if (is_file($file)) {
        return false;
    }
}

// Fallback to WordPress front controller
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../index.php';
