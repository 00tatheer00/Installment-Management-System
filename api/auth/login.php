<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/response.php';
require_once __DIR__ . '/../../core/dev_seed_admin.php';

ims_dev_seed_default_admin($conn);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

$input = read_json_input();
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    json_response(['status' => 'error', 'message' => 'Username and password required'], 400);
}

$stmt = $conn->prepare("SELECT id, name, username, password, role FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['status' => 'error', 'message' => 'Invalid credentials'], 401);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

json_response([
    'status' => 'success',
    'message' => 'Logged in successfully',
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role']
    ]
]);
