<?php
require_once __DIR__ . '/../../core/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

require_once __DIR__ . '/../../core/db.php';

$tmp = $_FILES['file']['tmp_name'];
$sql = file_get_contents($tmp);

if ($conn->multi_query($sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Database restored successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
