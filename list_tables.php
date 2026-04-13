<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli('127.0.0.1', 'root', '', 'bizinote_db');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => $conn->connect_error]));
}

$res = $conn->query("SHOW TABLES");
$tables = [];
while($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
echo json_encode(['success' => true, 'tables' => $tables]);
