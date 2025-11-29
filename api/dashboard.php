<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';

require_login();

$total_customers = (int)$conn->query("SELECT COUNT(*) AS c FROM customers")->fetch_assoc()['c'];
$total_products  = (int)$conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$active_plans    = (int)$conn->query("SELECT COUNT(*) AS c FROM plans WHERE status='Active'")->fetch_assoc()['c'];
$completed_plans = (int)$conn->query("SELECT COUNT(*) AS c FROM plans WHERE status='Completed'")->fetch_assoc()['c'];
$overdue_plans   = (int)$conn->query("SELECT COUNT(*) AS c FROM plans WHERE status='Overdue'")->fetch_assoc()['c'];

$today = date('Y-m-d');
$today_collection = (float)($conn->query("SELECT IFNULL(SUM(amount),0) AS s FROM payments WHERE payment_date='$today'")->fetch_assoc()['s']);

$monthly = [];
$res = $conn->query("SELECT MONTH(payment_date) AS m, SUM(amount) AS s FROM payments GROUP BY m");
while ($row = $res->fetch_assoc()) {
    $monthly[(int)$row['m']] = (float)$row['s'];
}

$last = $conn->query("
    SELECT pay.payment_date, pay.amount, pay.note, c.name AS customer_name
    FROM payments pay
    JOIN plans p ON pay.plan_id = p.id
    JOIN customers c ON p.customer_id = c.id
    ORDER BY pay.payment_date DESC, pay.id DESC
    LIMIT 10
");
$last_rows = $last->fetch_all(MYSQLI_ASSOC);

json_response([
    'status' => 'success',
    'data' => [
        'total_customers'   => $total_customers,
        'total_products'    => $total_products,
        'active_plans'      => $active_plans,
        'completed_plans'   => $completed_plans,
        'overdue_plans'     => $overdue_plans,
        'today_collection'  => $today_collection,
        'monthly_collection'=> $monthly,
        'last_payments'     => $last_rows
    ]
]);
