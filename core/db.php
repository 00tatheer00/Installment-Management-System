<?php
require_once __DIR__ . '/config.php';

function ims_db_error_message(bool $fromException): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = (bool) preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host);

    if ($isLocal) {
        return 'Cannot connect to MySQL. Start MySQL in XAMPP, import install.sql into the database, or set DB_* in core/config.local.php.';
    }

    $msg = 'Cannot connect to the database on this server. ';

    if (defined('IMS_USE_HOSTINGER_DB') && IMS_USE_HOSTINGER_DB === false) {
        $msg .= 'Open core/config.hostinger.php in File Manager, set IMS_USE_HOSTINGER_DB to true, and set DB_USER, DB_PASS, and DB_NAME to your MySQL user, password, and database from Hostinger hPanel (Websites → Databases). Then import install.sql into that database via phpMyAdmin.';
    } else {
        $msg .= 'Check DB_HOST, DB_USER, DB_PASS, and DB_NAME in core/config.hostinger.php or core/config.local.php so they match hPanel → MySQL Databases. Import install.sql if tables are missing.';
    }

    if ($fromException) {
        $msg .= ' (MySQL server unreachable or access denied.)';
    }

    return $msg;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Throwable $e) {
    require_once __DIR__ . '/response.php';
    json_response([
        'status' => 'error',
        'message' => ims_db_error_message(true),
    ], 500);
}

if ($conn->connect_error) {
    require_once __DIR__ . '/response.php';
    json_response([
        'status' => 'error',
        'message' => ims_db_error_message(false),
    ], 500);
}
$conn->set_charset('utf8mb4');
