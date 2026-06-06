<?php
// ─── Landing Page — Secure APK Upload Endpoint ──────────────────
// Called by Owner Panel via cURL. Validates secret before saving.

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

// ── Validate method ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

// ── Validate secret key ──────────────────────────────────────────
$cfg    = lpGetSettings();
$secret = $cfg['lp_api_secret'] ?? '';

if (empty($secret) || ($_POST['secret'] ?? '') !== $secret) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized — invalid secret key']);
    exit;
}

// ── Validate file ────────────────────────────────────────────────
$file = $_FILES['apk_file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    ];
    $errCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['ok' => false, 'msg' => $errMap[$errCode] ?? 'Upload error ' . $errCode]);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'apk') {
    echo json_encode(['ok' => false, 'msg' => 'Only .apk files are allowed']);
    exit;
}

if ($file['size'] > 200 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'msg' => 'File too large — max 200 MB']);
    exit;
}

// ── Save file ────────────────────────────────────────────────────
$apkDir  = __DIR__ . '/apk/';
$apkDest = $apkDir . 'binest.apk';

if (!is_dir($apkDir)) mkdir($apkDir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $apkDest)) {
    echo json_encode(['ok' => false, 'msg' => 'Failed to save file — check folder write permissions']);
    exit;
}

// ── Update version if provided ───────────────────────────────────
if (!empty($_POST['apk_version'])) {
    lpSaveSettings(['apk_version' => trim($_POST['apk_version'])]);
}

$sizeFmt = $file['size'] >= 1048576
    ? round($file['size'] / 1048576, 2) . ' MB'
    : round($file['size'] / 1024, 2) . ' KB';

echo json_encode([
    'ok'      => true,
    'msg'     => 'APK uploaded successfully',
    'size'    => $sizeFmt,
    'version' => trim($_POST['apk_version'] ?? $cfg['apk_version'] ?? ''),
]);
