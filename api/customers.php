<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';

require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = $_GET['q'] ?? '';
    $q = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? OR cnic LIKE ? ORDER BY id DESC");
    $stmt->bind_param('sss', $q, $q, $q);
    $stmt->execute();
    $res = $stmt->get_result();
    json_response(['status' => 'success', 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $name = trim($data['name'] ?? '');
    if ($name === '') json_response(['status' => 'error', 'message' => 'Name required'], 400);
    $phone = $data['phone'] ?? '';
    $cnic = $data['cnic'] ?? '';
    $address = $data['address'] ?? '';
    $stmt = $conn->prepare("INSERT INTO customers(name, phone, cnic, address) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss', $name, $phone, $cnic, $address);
    $stmt->execute();
    json_response(['status' => 'success']);
}

if ($method === 'PUT') {
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    if ($id <= 0 || $name === '') json_response(['status' => 'error', 'message' => 'Invalid data'], 400);
    $phone = $data['phone'] ?? '';
    $cnic = $data['cnic'] ?? '';
    $address = $data['address'] ?? '';
    $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, cnic=?, address=? WHERE id=?");
    $stmt->bind_param('ssssi', $name, $phone, $cnic, $address, $id);
    $stmt->execute();
    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) json_response(['status' => 'error', 'message' => 'Invalid ID'], 400);
    $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['status' => 'success']);
}

json_response(['status' => 'error', 'message' => 'Method not allowed'], 405);
