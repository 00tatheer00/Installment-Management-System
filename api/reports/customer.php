<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';

require_login();

$cid = (int)($_GET['cid'] ?? 0);
if ($cid <= 0) {
    json_response(['status' => 'error', 'message' => 'Customer ID required'], 400);
}

$sql = "SELECT pay.payment_date, pay.amount, pay.note, pay.plan_id
        FROM payments pay
        JOIN plans pl ON pl.id = pay.plan_id
        WHERE pl.customer_id = $cid
        ORDER BY pay.payment_date DESC, pay.id DESC";

$res  = $conn->query($sql);
$rows = $res->fetch_all(MYSQLI_ASSOC);

json_response(['status' => 'success', 'data' => $rows]);
