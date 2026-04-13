<?php
require_once 'config.php';

echo "<h2>Test Login Credentials</h2>";

// Get user details from owner panel
$result = $conn->query("SELECT id, name, email, password, is_blocked FROM users WHERE email LIKE '%admin%' OR email LIKE '%bizinote%'");

if ($result->num_rows > 0) {
    echo "<h3>Users Found:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password Hash</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['is_blocked'] == 1 ? '<span style="color: red;">BLOCKED</span>' : '<span style="color: green;">ACTIVE</span>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td><strong>{$row['email']}</strong></td>";
        echo "<td>" . substr($row['password'], 0, 30) . "...</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>📋 Login Instructions:</h3>";
    echo "<p>Mobile app mein <strong>EXACT EMAIL</strong> use karo jo upar table mein dikha hai.</p>";
    echo "<p>Agar password yaad nahi hai, toh owner panel se reset karo.</p>";
} else {
    echo "<p style='color: red;'>No users found with admin or bizinote in email</p>";
}

echo "<hr>";
echo "<h3>All Users:</h3>";
$result = $conn->query("SELECT id, name, email, is_blocked FROM users ORDER BY id");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $status = $row['is_blocked'] == 1 ? '<span style="color: red;">BLOCKED</span>' : '<span style="color: green;">ACTIVE</span>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td><strong>{$row['email']}</strong></td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>🔧 Test Login API:</h3>";
echo "<form method='POST'>";
echo "<p>Email: <input type='email' name='test_email' value='admin@gmail.com' style='width: 300px;'></p>";
echo "<p>Password: <input type='password' name='test_password' value='' style='width: 300px;'></p>";
echo "<button type='submit'>Test Login</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['test_email'] ?? '';
    $password = $_POST['test_password'] ?? '';
    
    echo "<hr>";
    echo "<h3>Login Test Result:</h3>";
    
    $stmt = $conn->prepare("SELECT id, name, email, password, is_blocked FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ User found in database</p>";
        echo "<p>ID: {$user['id']}</p>";
        echo "<p>Name: {$user['name']}</p>";
        echo "<p>Email: {$user['email']}</p>";
        
        if ($user['is_blocked'] == 1) {
            echo "<p style='color: red;'>❌ User is BLOCKED</p>";
        } else {
            echo "<p style='color: green;'>✅ User is ACTIVE</p>";
            
            if (password_verify($password, $user['password'])) {
                echo "<p style='color: green; font-size: 18px;'>✅✅ PASSWORD CORRECT - Login should work!</p>";
            } else {
                echo "<p style='color: red; font-size: 18px;'>❌ PASSWORD INCORRECT</p>";
                echo "<p>Password hash in DB: " . substr($user['password'], 0, 40) . "...</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ User NOT FOUND with email: {$email}</p>";
        echo "<p>Check spelling of email address</p>";
    }
}
?>
