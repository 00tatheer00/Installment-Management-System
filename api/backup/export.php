<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';

require_login();

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="backup.sql"');

$tables = ['users','customers','products','plans','payments'];

echo "SET FOREIGN_KEY_CHECKS=0;\n";

foreach ($tables as $table) {
    $res = $conn->query("SELECT * FROM `$table`");
    $columns = [];
    while ($f = $res->fetch_field()) {
        $columns[] = '`' . $f->name . '`';
    }
    echo "TRUNCATE TABLE `$table`;\n";
    while ($row = $res->fetch_assoc()) {
        $vals = [];
        foreach ($row as $v) {
            if ($v === null) $vals[] = 'NULL';
            else $vals[] = "'" . $conn->real_escape_string($v) . "'";
        }
        echo "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $vals) . ");\n";
    }
}
echo "SET FOREIGN_KEY_CHECKS=1;\n";
