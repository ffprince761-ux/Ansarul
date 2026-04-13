<?php
/**
 * OTP Management API
 * Handle OTP generation, sending, and verification
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config.php';
require_once '../email/EmailService.php';

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'send':
        sendOTP($conn, $data);
        break;
    case 'verify':
        verifyOTP($conn, $data);
        break;
    case 'resend':
        resendOTP($conn, $data);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function sendOTP($conn, $data) {
    $email = $data['email'] ?? '';
    $purpose = $data['purpose'] ?? 'registration'; // registration or password_reset
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        return;
    }
    
    try {
        // Check if user exists (for password reset)
        if ($purpose === 'password_reset') {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'Email not found']);
                return;
            }
        }
        
        // Check if user already exists (for registration)
        if ($purpose === 'registration') {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Email already registered']);
                return;
            }
        }
        
        // Generate 6-digit OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set expiry time (10 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', time() + 600);
        
        // Delete old OTPs for this email
        $stmt = $conn->prepare("DELETE FROM email_otps WHERE email = ? AND purpose = ?");
        $stmt->bind_param("ss", $email, $purpose);
        $stmt->execute();
        
        // Insert new OTP
        $stmt = $conn->prepare("INSERT INTO email_otps (email, otp, purpose, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $otp, $purpose, $expiresAt);
        $stmt->execute();
        
        // Send email
        $emailService = new EmailService();
        $emailSent = $emailService->sendOTP($email, $otp, $purpose);
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'OTP sent successfully to ' . $email,
                'expiresIn' => 600 // seconds
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to send email. Please try again.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("OTP send error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error']);
    }
}

function verifyOTP($conn, $data) {
    $email = $data['email'] ?? '';
    $otp = $data['otp'] ?? '';
    $purpose = $data['purpose'] ?? 'registration';
    
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'error' => 'Email and OTP required']);
        return;
    }
    
    try {
        // Check OTP
        $stmt = $conn->prepare("
            SELECT id, expires_at, verified 
            FROM email_otps 
            WHERE email = ? AND otp = ? AND purpose = ?
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("sss", $email, $otp, $purpose);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid OTP']);
            return;
        }
        
        $otpData = $result->fetch_assoc();
        
        // Check if already verified
        if ($otpData['verified'] == 1) {
            echo json_encode(['success' => false, 'error' => 'OTP already used']);
            return;
        }
        
        // Check if expired
        if (strtotime($otpData['expires_at']) < time()) {
            echo json_encode(['success' => false, 'error' => 'OTP expired. Please request a new one.']);
            return;
        }
        
        // Mark as verified
        $stmt = $conn->prepare("UPDATE email_otps SET verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $otpData['id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully',
            'email' => $email
        ]);
        
    } catch (Exception $e) {
        error_log("OTP verify error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error']);
    }
}

function resendOTP($conn, $data) {
    // Same as sendOTP but with rate limiting
    $email = $data['email'] ?? '';
    $purpose = $data['purpose'] ?? 'registration';
    
    // Check last OTP sent time (prevent spam)
    $stmt = $conn->prepare("
        SELECT created_at 
        FROM email_otps 
        WHERE email = ? AND purpose = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $purpose);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lastOTP = $result->fetch_assoc();
        $timeSinceLastOTP = time() - strtotime($lastOTP['created_at']);
        
        if ($timeSinceLastOTP < 60) { // 1 minute cooldown
            $waitTime = 60 - $timeSinceLastOTP;
            echo json_encode([
                'success' => false,
                'error' => "Please wait {$waitTime} seconds before requesting a new OTP"
            ]);
            return;
        }
    }
    
    // Send new OTP
    sendOTP($conn, $data);
}
