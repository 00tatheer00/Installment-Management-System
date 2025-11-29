<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/response.php';

session_unset();
session_destroy();
json_response(['status' => 'success', 'message' => 'Logged out']);
