<?php
require_once 'backend/config.php';
$res = $conn->query("DESCRIBE security_logs");
$columns = [];
while($row = $res->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo json_encode($columns);
