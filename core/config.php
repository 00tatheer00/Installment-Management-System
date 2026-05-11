<?php
date_default_timezone_set('Asia/Karachi');

// Never print PHP warnings/notices as HTML inside API JSON responses
$imsIsApi = PHP_SAPI !== 'cli'
    && isset($_SERVER['SCRIPT_NAME'])
    && strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false;
if ($imsIsApi) {
    ini_set('display_errors', '0');
    ini_set('html_errors', '0');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/config.hostinger.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'installment_system');
}

if (!defined('BASE_URL')) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.+)/api/#', $script, $m)) {
        define('BASE_URL', $scheme . '://' . $host . $m[1] . '/');
    } else {
        define('BASE_URL', $scheme . '://' . $host . '/');
    }
}

/**
 * Optional cross-origin API access. Define in config.local.php, e.g.:
 * define('CORS_ALLOWED_ORIGINS', ['https://ims.tech4edges.com']);
 * Same-origin (/frontend → /api) does not need CORS.
 */
function apply_cors_headers(): void
{
    if (!defined('CORS_ALLOWED_ORIGINS') || !is_array(CORS_ALLOWED_ORIGINS) || CORS_ALLOWED_ORIGINS === []) {
        return;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '' || !in_array($origin, CORS_ALLOWED_ORIGINS, true)) {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(403);
            exit;
        }
        return;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

apply_cors_headers();

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
