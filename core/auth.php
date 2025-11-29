<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/response.php';

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        json_response(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }
}
