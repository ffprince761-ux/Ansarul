<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

require_once '../config.php';
require_once '../security/RateLimiter.php';
require_once '../security/InputValidator.php';

// Initialize security
$rateLimiter = new RateLimiter($conn, 5, 300); // 5 attempts per 5 minutes
$clientIP = RateLimiter::getClientIP();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        registerUser();
        break;
    case 'verifyAndRegister':
        verifyAndRegister();
        break;
    case 'login':
        loginUser();
        break;
    case 'logout':
        logoutUser();
        break;
    case 'changePassword':
        changePassword();
        break;
    case 'resetPassword':
        resetPassword();
        break;
    default:
        echo json_encode(['success' => true, 'message' => 'Auth API is working', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
}

function registerUser() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $businessName = $data['businessName'] ?? '';
    $mobile = $data['mobile'] ?? '';
    $address = $data['address'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Name, email and password are required']);
        return;
    }
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'User already exists']);
        return;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, business_name, mobile, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $businessName, $mobile, $address);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'businessName' => $businessName,
                'mobile' => $mobile,
                'address' => $address
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Registration failed']);
    }
}

function loginUser() {
    global $conn, $rateLimiter, $clientIP;
    
    // Check if IP is blocked
    if ($rateLimiter->isIPBlocked($clientIP)) {
        echo json_encode(['success' => false, 'error' => 'Too many failed attempts. Please try again later.']);
        return;
    }
    
    // Check rate limit
    if ($rateLimiter->isRateLimited($clientIP, 'login')) {
        $rateLimiter->blockIP($clientIP, 1800); // Block for 30 minutes
        echo json_encode(['success' => false, 'error' => 'Too many login attempts. Account temporarily locked.']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    // Validate and sanitize email
    $email = InputValidator::validateEmail($email);
    if (!$email) {
        $rateLimiter->logAttempt($clientIP, 'login', false);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        return;
    }
    
    // Check for suspicious input
    if (InputValidator::detectSuspiciousInput($email) || InputValidator::detectSuspiciousInput($password)) {
        $rateLimiter->logAttempt($clientIP, 'login_suspicious', false);
        $rateLimiter->blockIP($clientIP, 3600); // Block for 1 hour
        echo json_encode(['success' => false, 'error' => 'Suspicious activity detected']);
        return;
    }
    
    if (empty($email) || empty($password)) {
        $rateLimiter->logAttempt($clientIP, 'login', false);
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if user is blocked
        if (isset($user['is_blocked']) && $user['is_blocked'] == 1) {
            $rateLimiter->logAttempt($clientIP, 'login_blocked_user', false);
            echo json_encode(['success' => false, 'error' => 'Your account has been blocked. Please contact support.']);
            return;
        }
        
        if (password_verify($password, $user['password'])) {
            // Log successful login
            $rateLimiter->logAttempt($clientIP, 'login', true, $user['id']);
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'businessName' => $user['business_name'],
                    'mobile' => $user['mobile'],
                    'address' => $user['address']
                ]
            ]);
        } else {
            // Log failed login attempt
            $rateLimiter->logAttempt($clientIP, 'login', false);
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
        }
    } else {
        // Log failed login attempt (user not found)
        $rateLimiter->logAttempt($clientIP, 'login', false);
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
}

function logoutUser() {
    // In a real app, you would invalidate the session/token
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

function changePassword() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $userId = $data['userId'] ?? '';
    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    
    // Validate input
    if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        return;
    }
    
    // Validate new password length
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long']);
        return;
    }
    
    // Get user's current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        return;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        return;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to change password']);
    }
}

function verifyAndRegister() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $businessName = $data['businessName'] ?? '';
    $mobile = $data['mobile'] ?? '';
    $address = $data['address'] ?? '';
    $otp = $data['otp'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($otp)) {
        echo json_encode(['success' => false, 'error' => 'All fields including OTP are required']);
        return;
    }
    
    // Verify OTP
    $stmt = $conn->prepare("
        SELECT id, expires_at, verified 
        FROM email_otps 
        WHERE email = ? AND otp = ? AND purpose = 'registration'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP']);
        return;
    }
    
    $otpData = $result->fetch_assoc();
    
    if (strtotime($otpData['expires_at']) < time()) {
        echo json_encode(['success' => false, 'error' => 'OTP expired']);
        return;
    }
    
    // Mark OTP as verified (optional, or just delete it later)
    $stmt = $conn->prepare("UPDATE email_otps SET verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $otpData['id']);
    $stmt->execute();
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'User already exists']);
        return;
    }
    
    // Create User
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, business_name, mobile, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $businessName, $mobile, $address);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'businessName' => $businessName,
                'mobile' => $mobile,
                'address' => $address
            ],
            'token' => 'logged_in' // Simple token for now
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
    }
}

function resetPassword() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $data['email'] ?? '';
    $otp = $data['otp'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    
    if (empty($email) || empty($otp) || empty($newPassword)) {
        echo json_encode(['success' => false, 'error' => 'Email, OTP and new password are required']);
        return;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
        return;
    }
    
    // Verify OTP
    $stmt = $conn->prepare("
        SELECT id, expires_at, verified 
        FROM email_otps 
        WHERE email = ? AND otp = ? AND purpose = 'password_reset'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP']);
        return;
    }
    
    $otpData = $result->fetch_assoc();
    
    if (strtotime($otpData['expires_at']) < time()) {
        echo json_encode(['success' => false, 'error' => 'OTP expired']);
        return;
    }
    
    // Mark OTP as verified
    $stmt = $conn->prepare("UPDATE email_otps SET verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $otpData['id']);
    $stmt->execute();
    
    // Update Password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to reset password']);
    }
}
