<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/response.php';

require_login();

$sql = "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
        FROM payments
        GROUP BY ym
        ORDER BY ym ASC";

$res = $conn->query($sql);
$out = [];
while ($row = $res->fetch_assoc()) {
    $out[$row['ym']] = (float)$row['total'];
}

json_response(['status' => 'success', 'data' => $out]);
