<?php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit;
}

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$contact = trim($_POST['contact'] ?? ''); // legacy field fallback
$message = trim($_POST['message'] ?? '');
$type    = trim($_POST['type']    ?? 'form');

// Support legacy 'contact' field
if (empty($phone) && empty($email) && !empty($contact)) {
    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) $email = $contact;
    else $phone = $contact;
}

if (empty($name) || (empty($phone) && empty($email))) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Name and contact info are required.']); exit;
}

// Rate limiting — max 5 per IP per hour
global $lpPdo;
if ($lpPdo) {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $lpPdo->prepare("SELECT COUNT(*) FROM lp_requests WHERE ip=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() >= 5) {
        http_response_code(429);
        echo json_encode(['success'=>false,'message'=>'Too many requests. Please try again later.']); exit;
    }
}

lpAddRequest([
    'name'    => htmlspecialchars($name,    ENT_QUOTES),
    'phone'   => htmlspecialchars($phone,   ENT_QUOTES),
    'email'   => htmlspecialchars($email,   ENT_QUOTES),
    'message' => htmlspecialchars($message, ENT_QUOTES),
    'type'    => in_array($type, ['form','whatsapp','email','apk']) ? $type : 'form',
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
]);

if ($type === 'apk') lpIncStat('apk_downloads');

echo json_encode(['success'=>true,'message'=>'Request received! We will contact you soon.']);
