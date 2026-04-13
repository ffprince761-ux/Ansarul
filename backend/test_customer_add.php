<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'bizinote_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test data
    $testData = [
        'userId' => 1,
        'name' => 'Test Customer',
        'mobile' => '9999999999',
        'email' => 'test@test.com',
        'address' => 'Test Address'
    ];
    
    $stmt = $conn->prepare("INSERT INTO customers (userId, name, mobile, email, address, createdAt) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $testData['userId'],
        $testData['name'],
        $testData['mobile'],
        $testData['email'],
        $testData['address']
    ]);
    
    $customerId = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Test customer added successfully',
        'customerId' => $customerId,
        'data' => $testData
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
