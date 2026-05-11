<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';

require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = $_GET['q'] ?? '';
    $q = '%' . $q . '%';
    $stmt = $conn->prepare("
        SELECT p.*, c.name AS customer, pr.name AS product
        FROM plans p
        JOIN customers c ON p.customer_id = c.id
        JOIN products pr ON p.product_id = pr.id
        WHERE c.name LIKE ? OR pr.name LIKE ?
        ORDER BY p.id DESC
    ");
    $stmt->bind_param('ss', $q, $q);
    $stmt->execute();
    $res = $stmt->get_result();
    json_response(['status' => 'success', 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

$data = read_json_input();

if ($method === 'POST') {
    $customer = (int)($data['customer_id'] ?? 0);
    $product  = (int)($data['product_id'] ?? 0);
    $total    = (float)($data['total_amount'] ?? 0);
    $down     = (float)($data['down_payment'] ?? 0);
    $schedule = $data['schedule_type'] ?? 'monthly';
    $instAmt  = (float)($data['installment_amount'] ?? 0);

    if ($customer <= 0 || $product <= 0 || $total <= 0) {
        json_response(['status' => 'error', 'message' => 'Invalid data'], 400);
    }

    $remaining = $total - $down;
    $start = date('Y-m-d');
    $nextDue = $schedule === 'weekly'
        ? date('Y-m-d', strtotime('+7 days'))
        : date('Y-m-d', strtotime('+1 month'));

    $stmt = $conn->prepare("INSERT INTO plans
        (customer_id, product_id, total_amount, down_payment, remaining_amount,
         schedule_type, installment_amount, next_due_date, start_date, status)
        VALUES (?,?,?,?,?,?,?,?,?, 'Active')");
    $stmt->bind_param('iiddsdsss', $customer, $product, $total, $down, $remaining,
                      $schedule, $instAmt, $nextDue, $start);
    $stmt->execute();
    json_response(['status' => 'success']);
}

if ($method === 'PUT') {
    $id = (int)($data['id'] ?? 0);
    $customer = (int)($data['customer_id'] ?? 0);
    $product  = (int)($data['product_id'] ?? 0);
    $total    = (float)($data['total_amount'] ?? 0);
    $down     = (float)($data['down_payment'] ?? 0);
    $schedule = $data['schedule_type'] ?? 'monthly';
    $instAmt  = (float)($data['installment_amount'] ?? 0);

    if ($id <= 0 || $customer <= 0 || $product <= 0 || $total <= 0) {
        json_response(['status' => 'error', 'message' => 'Invalid data'], 400);
    }

    // Get sum of existing payments
    $payStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS paid FROM payments WHERE plan_id = ?");
    $payStmt->bind_param('i', $id);
    $payStmt->execute();
    $paidResult = $payStmt->get_result();
    $paid = (float)($paidResult->fetch_assoc()['paid'] ?? 0);

    // Calculate remaining: total - down - payments already made
    $remaining = $total - $down - $paid;
    if ($remaining < 0) $remaining = 0;

    $nextDue = $schedule === 'weekly'
        ? date('Y-m-d', strtotime('+7 days'))
        : date('Y-m-d', strtotime('+1 month'));

    $stmt = $conn->prepare("UPDATE plans SET
        customer_id=?, product_id=?, total_amount=?, down_payment=?, remaining_amount=?,
        schedule_type=?, installment_amount=?, next_due_date=?
        WHERE id=?");
    $stmt->bind_param('iiddsdssi', $customer, $product, $total, $down, $remaining,
                      $schedule, $instAmt, $nextDue, $id);
    $stmt->execute();

    // Update status based on remaining amount
    $conn->query("UPDATE plans SET status='Completed' WHERE id=$id AND remaining_amount<=0");
    $conn->query("UPDATE plans SET status='Active' WHERE id=$id AND remaining_amount>0");

    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) json_response(['status' => 'error', 'message' => 'Invalid ID'], 400);
    
    // Delete associated payments first
    $payStmt = $conn->prepare("DELETE FROM payments WHERE plan_id=?");
    $payStmt->bind_param('i', $id);
    $payStmt->execute();
    
    // Delete the plan
    $stmt = $conn->prepare("DELETE FROM plans WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['status' => 'success']);
}

json_response(['status' => 'error', 'message' => 'Method not allowed'], 405);
