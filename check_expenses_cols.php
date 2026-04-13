<?php
require_once 'backend/config.php';
$res = $conn->query("SHOW COLUMNS FROM expenses");
$cols = [];
while($row = $res->fetch_assoc()) { $cols[] = $row['Field']; }
echo json_encode($cols);
