<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/response.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['status' => 'error', 'user' => null]);
}

json_response([
    'status' => 'success',
    'user' => [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role']
    ]
]);
