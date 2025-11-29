<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';

require_login();

$date = $_GET['date'] ?? date('Y-m-d');

$sql = "SELECT pay.payment_date, pay.amount, pay.note, c.name AS customer
        FROM payments pay
        JOIN plans pl ON pl.id = pay.plan_id
        JOIN customers c ON c.id = pl.customer_id
        WHERE pay.payment_date = '$date'
        ORDER BY pay.id DESC";

$res  = $conn->query($sql);
$rows = $res->fetch_all(MYSQLI_ASSOC);

json_response([
    'status' => 'success',
    'data'   => $rows
]);
