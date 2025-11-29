<?php
date_default_timezone_set('Asia/Karachi');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'installment_system');

define('BASE_URL', 'http://localhost/installment_api_clean/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
