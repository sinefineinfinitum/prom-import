  <?php
// Utility endpoint for CI to fetch a REST nonce bound to the current logged-in user session
// This file is served directly by PHP built-in server (router bypassed for existing files)

$root = realpath(__DIR__ . '/..');
chdir($root);
require_once $root . '/wp-load.php';

if (!function_exists('is_user_logged_in')) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'WordPress not loaded';
    exit;
}

if (!is_user_logged_in()) {
    http_response_code(401);
    header('Content-Type: text/plain');
    echo 'Not logged in';
    exit;
}

header('Content-Type: text/plain');
echo wp_create_nonce('wp_rest');
