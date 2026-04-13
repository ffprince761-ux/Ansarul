<?php
require_once 'config.php';

echo "<h2>Check User Status</h2>";

// Check all users
$result = $conn->query("SELECT id, name, email, is_blocked FROM users ORDER BY id");

if ($result->num_rows > 0) {
    echo "<h3>All Users in Database:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['is_blocked'] == 1 ? '<span style="color: red;">BLOCKED</span>' : '<span style="color: green;">ACTIVE</span>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No users found in database!</p>";
}

// Check specifically for admin@gmail.com
echo "<hr>";
echo "<h3>Check for admin@gmail.com:</h3>";
$stmt = $conn->prepare("SELECT id, name, email, is_blocked FROM users WHERE email = ?");
$email = 'admin@gmail.com';
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ User EXISTS</p>";
    echo "<p>ID: {$user['id']}</p>";
    echo "<p>Name: {$user['name']}</p>";
    echo "<p>Email: {$user['email']}</p>";
    echo "<p>Status: " . ($user['is_blocked'] == 1 ? 'BLOCKED' : 'ACTIVE') . "</p>";
} else {
    echo "<p style='color: red;'>❌ User NOT FOUND - Was DELETED from database</p>";
    echo "<p>User ko dobara register karna padega ya owner panel se recreate karna padega</p>";
}
?>
