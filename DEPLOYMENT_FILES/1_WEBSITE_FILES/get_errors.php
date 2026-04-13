<?php
require_once 'config.php';
$sql = "SELECT * FROM app_error_logs ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ERROR [" . $row['created_at'] . "]: " . $row['error_message'] . "\n";
        echo "STACK: " . substr($row['stack_trace'], 0, 200) . "...\n\n";
    }
} else {
    echo "No errors found.\n";
}
$conn->close();
?>
