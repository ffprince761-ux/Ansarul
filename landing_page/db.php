<?php
// ─── Landing Page — Shared Database Connection ──────────────────
// Same DB as Owner Panel. All LP data stored in lp_* tables.

date_default_timezone_set('Asia/Kolkata');

$_LP_HOST = 'localhost';
$_LP_DB   = 'u946320467_binest';
$_LP_USER = 'u946320467_binest';
$_LP_PASS = 'Binest@28';

try {
    $lpPdo = new PDO(
        "mysql:host=$_LP_HOST;dbname=$_LP_DB;charset=utf8mb4",
        $_LP_USER, $_LP_PASS,
        [PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $lpPdo->exec("SET time_zone='+05:30'");

    // ── Create tables ───────────────────────────────────────────
    $lpPdo->exec("CREATE TABLE IF NOT EXISTS lp_requests (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(255) DEFAULT '',
        phone      VARCHAR(50)  DEFAULT '',
        email      VARCHAR(255) DEFAULT '',
        type       ENUM('form','whatsapp','email','apk') DEFAULT 'form',
        message    TEXT,
        status     ENUM('new','done') DEFAULT 'new',
        ip         VARCHAR(50)  DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status(status),
        INDEX idx_type(type),
        INDEX idx_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $lpPdo->exec("CREATE TABLE IF NOT EXISTS lp_settings (
        setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT DEFAULT '',
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $lpPdo->exec("CREATE TABLE IF NOT EXISTS lp_stats (
        stat_key   VARCHAR(100) NOT NULL PRIMARY KEY,
        stat_value BIGINT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Default settings (INSERT IGNORE = don't overwrite existing) ─
    $defaults = [
        'whatsapp'       => '',
        'email'          => 'binestmanage@gmail.com',
        'apk_version'    => '1.0.0',
        'play_store'     => '',
        'instagram'      => '',
        'facebook'       => '',
        'youtube'        => '',
        'twitter'        => '',
        'playstore_mode' => 'coming_soon',
        'show_apk'       => '1',
        'show_windows'   => '0',
        'windows_url'    => '',
        'lp_api_secret'  => 'binest_' . substr(md5('binest_secret_2025'), 0, 16),
        'lp_page_url'    => 'https://lightgrey-sparrow-526806.hostingersite.com/landing_page',
        'admin_username' => 'admin',
        'admin_password' => password_hash('admin123', PASSWORD_DEFAULT),
    ];
    $ins = $lpPdo->prepare("INSERT IGNORE INTO lp_settings (setting_key,setting_value) VALUES(?,?)");
    foreach ($defaults as $k => $v) $ins->execute([$k, $v]);

    // Ensure admin credentials exist even in older databases
    foreach (['admin_username','admin_password'] as $adminKey) {
        $check = $lpPdo->prepare("SELECT 1 FROM lp_settings WHERE setting_key=?");
        $check->execute([$adminKey]);
        if (!$check->fetch()) {
            $lpPdo->prepare("INSERT INTO lp_settings (setting_key,setting_value) VALUES(?,?)")
                ->execute([$adminKey, $defaults[$adminKey]]);
        }
    }

} catch (PDOException $e) {
    $lpPdo = null;
}

// ════════════════════════════════════════════════════════════════
//  SETTINGS
// ════════════════════════════════════════════════════════════════

function lpGetSettings(): array {
    global $lpPdo;
    if (!$lpPdo) return [];
    $rows = $lpPdo->query("SELECT setting_key,setting_value FROM lp_settings")->fetchAll();
    $out  = [];
    foreach ($rows as $r) $out[$r['setting_key']] = $r['setting_value'];
    return $out;
}

function lpSaveSettings(array $data): void {
    global $lpPdo;
    if (!$lpPdo) return;
    $stmt = $lpPdo->prepare(
        "INSERT INTO lp_settings (setting_key,setting_value) VALUES(?,?)
         ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
    );
    foreach ($data as $k => $v) $stmt->execute([$k, $v]);
}

// ════════════════════════════════════════════════════════════════
//  STATS
// ════════════════════════════════════════════════════════════════

function lpGetStats(): array {
    global $lpPdo;
    $base = ['page_views' => 0, 'apk_downloads' => 0, 'total_requests' => 0];
    if (!$lpPdo) return $base;
    $rows = $lpPdo->query("SELECT stat_key,stat_value FROM lp_stats")->fetchAll();
    foreach ($rows as $r) $base[$r['stat_key']] = (int)$r['stat_value'];
    return $base;
}

function lpIncStat(string $key): void {
    global $lpPdo;
    if (!$lpPdo) return;
    $lpPdo->prepare(
        "INSERT INTO lp_stats (stat_key,stat_value) VALUES(?,1)
         ON DUPLICATE KEY UPDATE stat_value=stat_value+1"
    )->execute([$key]);
}

// ════════════════════════════════════════════════════════════════
//  REQUESTS
// ════════════════════════════════════════════════════════════════

function lpGetRequests(string $filter = ''): array {
    global $lpPdo;
    if (!$lpPdo) return [];
    if (in_array($filter, ['new','done'])) {
        $s = $lpPdo->prepare("SELECT * FROM lp_requests WHERE status=? ORDER BY created_at DESC");
        $s->execute([$filter]);
    } elseif (in_array($filter, ['form','whatsapp','email','apk'])) {
        $s = $lpPdo->prepare("SELECT * FROM lp_requests WHERE type=? ORDER BY created_at DESC");
        $s->execute([$filter]);
    } else {
        $s = $lpPdo->query("SELECT * FROM lp_requests ORDER BY created_at DESC");
    }
    return $s->fetchAll();
}

function lpAddRequest(array $data): bool {
    global $lpPdo;
    if (!$lpPdo) return false;
    $lpPdo->prepare(
        "INSERT INTO lp_requests (name,phone,email,type,message,ip) VALUES(?,?,?,?,?,?)"
    )->execute([
        $data['name']    ?? '',
        $data['phone']   ?? '',
        $data['email']   ?? '',
        $data['type']    ?? 'form',
        $data['message'] ?? '',
        $data['ip']      ?? '',
    ]);
    lpIncStat('total_requests');
    return true;
}

function lpMarkRequest(int $id): void {
    global $lpPdo;
    if ($lpPdo) $lpPdo->prepare("UPDATE lp_requests SET status='done' WHERE id=?")->execute([$id]);
}

function lpMarkAllDone(): void {
    global $lpPdo;
    if ($lpPdo) $lpPdo->exec("UPDATE lp_requests SET status='done'");
}

function lpDeleteRequest(int $id): void {
    global $lpPdo;
    if ($lpPdo) $lpPdo->prepare("DELETE FROM lp_requests WHERE id=?")->execute([$id]);
}

function lpClearRequests(): void {
    global $lpPdo;
    if (!$lpPdo) return;
    $lpPdo->exec("DELETE FROM lp_requests");
    $lpPdo->exec("UPDATE lp_stats SET stat_value=0 WHERE stat_key='total_requests'");
}

function lpCountNew(): int {
    global $lpPdo;
    if (!$lpPdo) return 0;
    return (int)$lpPdo->query("SELECT COUNT(*) FROM lp_requests WHERE status='new'")->fetchColumn();
}
