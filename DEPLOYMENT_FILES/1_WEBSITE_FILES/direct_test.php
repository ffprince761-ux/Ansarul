<?php
require_once 'config.php';

echo "<h2>Direct Database Query Test</h2>";

// Test 1: Check all users
echo "<h3>All Users in Database:</h3>";
$result = $conn->query("SELECT id, name, email, is_blocked FROM users");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>is_blocked</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['is_blocked']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No users found!</p>";
}

echo "<hr>";

// Test 2: Direct API call simulation
echo "<h3>Simulating API Call for User ID 1:</h3>";
$userId = 1;
$stmt = $conn->prepare("SELECT id, COALESCE(is_blocked, 0) as is_blocked FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>Rows found: " . $result->num_rows . "</p>";

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ User found!</p>";
    echo "<p>User ID: {$user['id']}</p>";
    echo "<p>is_blocked: {$user['is_blocked']}</p>";
    
    if ($user['is_blocked'] == 1) {
        echo "<p style='color: red; font-size: 18px;'>🔒 USER IS BLOCKED</p>";
        echo "<pre>" . json_encode([
            'success' => false,
            'blocked' => true,
            'error' => 'Your account has been blocked'
        ], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: green; font-size: 18px;'>✅ USER IS ACTIVE</p>";
        echo "<pre>" . json_encode([
            'success' => true,
            'blocked' => false
        ], JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ User ID {$userId} not found in database</p>";
    echo "<p>This is why API returns 'user not found'</p>";
}

echo "<hr>";

// Test 3: Check what user IDs exist
echo "<h3>Available User IDs:</h3>";
$result = $conn->query("SELECT id FROM users ORDER BY id");
$ids = [];
while ($row = $result->fetch_assoc()) {
    $ids[] = $row['id'];
}
echo "<p>User IDs in database: " . implode(", ", $ids) . "</p>";
echo "<p>Use one of these IDs to test the API</p>";
?>
