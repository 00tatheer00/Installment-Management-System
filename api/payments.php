<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';

require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $res = $conn->query("
        SELECT pay.*, c.name AS customer_name
        FROM payments pay
        JOIN plans p ON pay.plan_id = p.id
        JOIN customers c ON p.customer_id = c.id
        ORDER BY pay.id DESC
    ");
    json_response(['status' => 'success', 'data' => $res->fetch_all(MYSQLI_ASSOC)]);
}

$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $plan_id = (int)($data['plan_id'] ?? 0);
    $amount  = (float)($data['amount'] ?? 0);
    $note    = $data['note'] ?? '';
    if ($plan_id <= 0 || $amount <= 0) {
        json_response(['status' => 'error', 'message' => 'Invalid data'], 400);
    }
    $today = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO payments(plan_id, amount, payment_date, note) VALUES (?,?,?,?)");
    $stmt->bind_param('idss', $plan_id, $amount, $today, $note);
    $stmt->execute();

    $stmt2 = $conn->prepare("UPDATE plans SET remaining_amount = remaining_amount - ? WHERE id = ?");
    $stmt2->bind_param('di', $amount, $plan_id);
    $stmt2->execute();

    $conn->query("UPDATE plans SET status='Completed' WHERE id=$plan_id AND remaining_amount<=0");

    json_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) json_response(['status' => 'error', 'message' => 'Invalid ID'], 400);
    
    // Get payment details to restore plan remaining amount
    $stmt = $conn->prepare("SELECT plan_id, amount FROM payments WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        // Restore the amount to plan
        $stmt2 = $conn->prepare("UPDATE plans SET remaining_amount = remaining_amount + ? WHERE id = ?");
        $stmt2->bind_param('di', $payment['amount'], $payment['plan_id']);
        $stmt2->execute();
        
        // Update plan status if needed
        $conn->query("UPDATE plans SET status='Active' WHERE id={$payment['plan_id']} AND remaining_amount>0");
    }
    
    // Delete the payment
    $stmt3 = $conn->prepare("DELETE FROM payments WHERE id=?");
    $stmt3->bind_param('i', $id);
    $stmt3->execute();
    json_response(['status' => 'success']);
}

json_response(['status' => 'error', 'message' => 'Method not allowed'], 405);
