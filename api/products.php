<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';

require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = $_GET['q'] ?? '';
    $q = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC");
    $stmt->bind_param('s', $q);
    $stmt->execute();
    $res = $stmt->get_result();
    json_response(['status' => 'success', 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $name = trim($data['name'] ?? '');
    $price = (float)($data['price'] ?? 0);
    $desc = $data['description'] ?? '';
    if ($name === '') json_response(['status' => 'error', 'message' => 'Name required'], 400);
    $stmt = $conn->prepare("INSERT INTO products(name, description, price) VALUES (?,?,?)");
    $stmt->bind_param('ssd', $name, $desc, $price);
    $stmt->execute();
    json_response(['status' => 'success']);
}

if ($method === 'PUT') {
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $price = (float)($data['price'] ?? 0);
    $desc = $data['description'] ?? '';
    if ($id <= 0 || $name === '') json_response(['status' => 'error', 'message' => 'Invalid data'], 400);
    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=? WHERE id=?");
    $stmt->bind_param('ssdi', $name, $desc, $price, $id);
    $stmt->execute();
    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) json_response(['status' => 'error', 'message' => 'Invalid ID'], 400);
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['status' => 'success']);
}

json_response(['status' => 'error', 'message' => 'Method not allowed'], 405);
