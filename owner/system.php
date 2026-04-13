<?php
/**
 * Owner Panel - System Health Monitoring
 */
require_once 'config/db.php';
require_once 'config/functions.php';

requireOwnerLogin();

$pageTitle = 'System Health';

// Get system statistics
try {
    // Database size - get current database name dynamically
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $stmt = $pdo->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ");
    $result = $stmt->fetch();
    $dbSize = $result['size_mb'] ?? 0;
    
    // Table counts
    $tables = ['users', 'products', 'customers', 'bills', 'expenses', 'stock_adjustments'];
    $tableCounts = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $tableCounts[$table] = $stmt->fetch()['count'];
    }
    
    // Recent errors from security logs (if any)
    $stmt = $pdo->query("
        SELECT action, ip_address, created_at 
        FROM security_logs 
        WHERE success = 0 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentErrors = $stmt->fetchAll();
    
    // Failed login attempts
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM failed_login_attempts 
        WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $failedLogins = $stmt->fetch()['count'];
    
    // Blocked IPs
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM blocked_ips 
        WHERE blocked_until > NOW()
    ");
    $blockedIPs = $stmt->fetch()['count'];
    
    // Blocked Users
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE is_blocked = 1
    ");
    $blockedUsers = $stmt->fetch()['count'];
    
    // Owner activity logs
    $stmt = $pdo->query("
        SELECT 
            oa.action,
            oa.details,
            oa.ip_address,
            oa.created_at,
            ou.username
        FROM owner_activity_logs oa
        JOIN owner_users ou ON oa.owner_id = ou.id
        ORDER BY oa.created_at DESC
        LIMIT 20
    ");
    $ownerLogs = $stmt->fetchAll();
    
    // System uptime (approximate based on oldest record)
    $stmt = $pdo->query("SELECT MIN(created_at) as first_record FROM users");
    $firstRecord = $stmt->fetch()['first_record'];
    if ($firstRecord) {
        $uptimeSeconds = time() - strtotime($firstRecord);
        $uptime = max(0, floor($uptimeSeconds / 86400)); // Ensure non-negative
    } else {
        $uptime = 0;
    }
    
} catch(PDOException $e) {
    error_log("System page error: " . $e->getMessage());
    $dbSize = 0;
    $tableCounts = [];
    $recentErrors = $ownerLogs = [];
    $failedLogins = $blockedIPs = $blockedUsers = $uptime = 0;
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
</style>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Database Size</div>
            <div class="stat-value"><?= $dbSize ?> MB</div>
            <small style="color:var(--text-muted)">Total storage</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">System Uptime</div>
            <div class="stat-value" style="color:var(--green)"><?= $uptime ?> Days</div>
            <small style="color:var(--green)">Running</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Failed Logins (24h)</div>
            <div class="stat-value" style="color:<?= $failedLogins > 0 ? 'var(--amber)' : 'var(--green)' ?>"><?= $failedLogins ?></div>
            <small style="color:var(--text-muted)">Security events</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Blocked IPs / Users</div>
            <div class="stat-value" style="color:<?= ($blockedIPs + $blockedUsers) > 0 ? 'var(--red)' : 'var(--green)' ?>"><?= $blockedIPs ?> / <?= $blockedUsers ?></div>
            <small style="color:var(--text-muted)">Active blocks</small>
        </div>
    </div>
</div>

<!-- Database Tables + Security Events -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Database Tables</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Table</th><th>Records</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($tableCounts as $table => $count): ?>
                            <tr>
                                <td><code style="font-size:12px"><?= e($table) ?></code></td>
                                <td><strong><?= number_format($count) ?></strong></td>
                                <td><span class="sb" style="background:var(--green-light);color:var(--green)">Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Recent Security Events</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Action</th><th>IP Address</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentErrors)): ?>
                            <tr><td colspan="3" class="text-center" style="color:var(--green);padding:20px"><i class="fas fa-check-circle"></i> No security issues</td></tr>
                        <?php else: foreach ($recentErrors as $error): ?>
                            <tr>
                                <td><?= e($error['action']) ?></td>
                                <td><code style="font-size:12px"><?= e($error['ip_address']) ?></code></td>
                                <td style="font-size:12px;color:var(--text-muted)"><?= timeAgo($error['created_at']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Owner Activity Logs -->
<div class="table-container">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Owner Activity Logs</h6>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Action</th><th>Details</th><th>IP</th><th>Time</th></tr></thead>
            <tbody>
                <?php if (empty($ownerLogs)): ?>
                    <tr><td colspan="5" class="text-center" style="color:var(--text-muted);padding:20px">No activity logs</td></tr>
                <?php else: foreach ($ownerLogs as $log): ?>
                    <tr>
                        <td><strong><?= e($log['username']) ?></strong></td>
                        <td><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= e($log['action']) ?></span></td>
                        <td style="font-size:12px;color:var(--text-secondary)"><?= e($log['details'] ?: '-') ?></td>
                        <td><code style="font-size:12px"><?= e($log['ip_address']) ?></code></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= timeAgo($log['created_at']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
