<?php
/**
 * Owner Panel - App Error Logs
 * Monitor app crashes and errors across all users
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'Error Logs';

try {
    // Ensure enhanced table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_error_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT DEFAULT NULL,
        error_type VARCHAR(100) DEFAULT 'general',
        error_message TEXT NOT NULL,
        stack_trace TEXT,
        file_path VARCHAR(500),
        line_number INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        request_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_error_type (error_type),
        INDEX idx_user_id (user_id)
    )");

    // All errors with user info
    $stmt = $pdo->query("
        SELECT e.*, u.name as user_name, u.business_name
        FROM app_error_logs e
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY e.created_at DESC
        LIMIT 200
    ");
    $errors = $stmt->fetchAll();

    $totalErrors = count($errors);
    $todayErrors = 0;
    $weekErrors = 0;
    $errorTypes = [];
    $byUser = [];
    $byDay = [];
    $byDevice = [];

    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));

    foreach ($errors as $err) {
        $day = date('Y-m-d', strtotime($err['created_at']));
        if ($day === $today) $todayErrors++;
        if ($day >= $weekAgo) $weekErrors++;

        // Use enhanced error type if available, otherwise extract from message
        $type = $err['error_type'] ?? 'Unknown';
        if ($type === 'general' || $type === 'Unknown') {
            $msg = $err['error_message'] ?? '';
            if (stripos($msg, 'network') !== false || stripos($msg, 'fetch') !== false) $type = 'Network';
            elseif (stripos($msg, 'null') !== false || stripos($msg, 'undefined') !== false) $type = 'Null Reference';
            elseif (stripos($msg, 'timeout') !== false) $type = 'Timeout';
            elseif (stripos($msg, 'permission') !== false) $type = 'Permission';
            elseif (stripos($msg, 'memory') !== false) $type = 'Memory';
            elseif (stripos($msg, 'render') !== false || stripos($msg, 'component') !== false) $type = 'UI/Render';
            elseif (stripos($msg, 'database') !== false || stripos($msg, 'sql') !== false) $type = 'Database';
            elseif (stripos($msg, 'auth') !== false || stripos($msg, 'login') !== false) $type = 'Auth';
            elseif (stripos($msg, 'email') !== false || stripos($msg, 'mail') !== false) $type = 'Email';
            else $type = 'Other';
        }

        if (!isset($errorTypes[$type])) $errorTypes[$type] = 0;
        $errorTypes[$type]++;

        $userName = $err['user_name'] ?: 'Unknown User';
        if (!isset($byUser[$userName])) $byUser[$userName] = 0;
        $byUser[$userName]++;

        if (!isset($byDay[$day])) $byDay[$day] = 0;
        $byDay[$day]++;

        // Use enhanced user_agent if available, fallback to device_info
        $device = $err['user_agent'] ?: $err['device_info'] ?: 'Unknown';
        // Extract device model if possible
        if (preg_match('/(iPhone|iPad|Samsung|Xiaomi|Redmi|OnePlus|Pixel|Realme|Oppo|Vivo|Huawei)/i', $device, $m)) {
            $deviceType = $m[1];
        } elseif (preg_match('/(Android|iOS|Windows|Mac|Linux)/i', $device, $m)) {
            $deviceType = $m[1];
        } else {
            $deviceType = strlen($device) > 20 ? substr($device, 0, 20) : $device;
        }
        if (!isset($byDevice[$deviceType])) $byDevice[$deviceType] = 0;
        $byDevice[$deviceType]++;
    }
    arsort($errorTypes);
    arsort($byUser);
    arsort($byDevice);

    // Trend last 14 days
    $trendDays = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trendDays[$d] = $byDay[$d] ?? 0;
    }

    // Health score
    $healthScore = 100;
    if ($todayErrors > 0) $healthScore -= min(30, $todayErrors * 5);
    if ($weekErrors > 10) $healthScore -= 20;
    if ($weekErrors > 30) $healthScore -= 20;
    $healthScore = max(0, $healthScore);
    $healthColor = $healthScore >= 80 ? '#10b981' : ($healthScore >= 50 ? '#f59e0b' : '#ef4444');
    $healthLabel = $healthScore >= 80 ? 'Healthy' : ($healthScore >= 50 ? 'Warning' : 'Critical');

} catch(PDOException $e) {
    error_log("Error logs page: " . $e->getMessage());
    $errors = []; $totalErrors = $todayErrors = $weekErrors = 0;
    $errorTypes = []; $byUser = []; $trendDays = []; $byDevice = [];
    $healthScore = 100; $healthColor = '#10b981'; $healthLabel = 'Healthy';
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.health-ring{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-direction:column;margin:0 auto}
.error-msg{font-family:monospace;font-size:12px;color:var(--red);background:var(--red-light);padding:6px 10px;border-radius:6px;word-break:break-all;max-height:60px;overflow:hidden;cursor:pointer;transition:max-height .3s}
.error-msg.expanded{max-height:400px;overflow:auto}
.stack-trace{font-family:monospace;font-size:10px;color:var(--text-muted);background:var(--bg);padding:8px;border-radius:6px;max-height:100px;overflow:auto;margin-top:6px;display:none;word-break:break-all}
.type-badge{padding:3px 10px;border-radius:6px;font-size:10px;font-weight:600}
.tb-network{background:var(--blue-light);color:var(--blue)}.tb-null{background:#FCE7F3;color:#9D174D}.tb-timeout{background:var(--amber-light);color:var(--amber)}
.tb-permission{background:var(--red-light);color:var(--red)}.tb-memory{background:#EDE9FE;color:#5B21B6}.tb-ui{background:var(--green-light);color:var(--green)}
.tb-database{background:var(--blue-light);color:#075985}.tb-auth{background:#FFF7ED;color:#9A3412}.tb-email{background:#FEF3C7;color:#92400E}.tb-other{background:#F3F4F6;color:var(--text-secondary)}
.bar-row{display:flex;align-items:center;margin-bottom:6px}
.bar-label{width:100px;font-size:12px;font-weight:500;flex-shrink:0;color:var(--text-secondary)}
.bar-track{flex:1;height:20px;background:#F3F4F6;border-radius:4px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;display:flex;align-items:center;padding:0 8px;font-size:10px;font-weight:600;color:#fff}
</style>

<!-- Health + Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card text-center">
            <div class="health-ring" style="border:4px solid <?= $healthColor ?>">
                <div style="font-size:22px;font-weight:700;color:<?= $healthColor ?>"><?= $healthScore ?></div>
                <div style="font-size:9px;font-weight:600;color:<?= $healthColor ?>"><?= $healthLabel ?></div>
            </div>
            <div class="stat-label mt-2">Health Score</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">Total Errors</div><div class="stat-value" style="color:var(--red)"><?= $totalErrors ?></div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">Today</div><div class="stat-value"><?= $todayErrors ?></div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">This Week</div><div class="stat-value"><?= $weekErrors ?></div></div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Error Trend (14 Days)</h6>
            <canvas id="trendChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Error Types</h6>
            <canvas id="typeChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Error Types + By User -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Error Categories</h6>
            <?php
            $typeColors = ['Network'=>'#2563EB','Null Reference'=>'#DB2777','Timeout'=>'#D97706','Permission'=>'#DC2626','Memory'=>'#7C3AED','UI/Render'=>'#059669','Database'=>'#0891B2','Auth'=>'#EA580C','Other'=>'#6B7280','Unknown'=>'#6B7280'];
            foreach ($errorTypes as $type => $count):
                $color = $typeColors[$type] ?? '#6B7280';
                $maxCount = max(array_values($errorTypes));
                $width = $maxCount > 0 ? round(($count / $maxCount) * 100) : 0;
            ?>
                <div class="bar-row">
                    <div class="bar-label"><?= $type ?></div>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= max($width,8) ?>%;background:<?= $color ?>"><?= $count ?></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">By User</h6>
            <?php foreach (array_slice($byUser, 0, 8, true) as $user => $count):
                $maxU = max(array_values($byUser));
                $width = $maxU > 0 ? round(($count / $maxU) * 100) : 0;
            ?>
                <div class="bar-row">
                    <div class="bar-label" style="width:120px"><?= e($user) ?></div>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= max($width,8) ?>%;background:#4F46E5"><?= $count ?></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Error Log Table -->
<div class="mb-3 d-flex justify-content-between align-items-center">
    <h6 style="font-weight:700;font-size:14px;margin:0">Error Log (<?= $totalErrors ?>)</h6>
    <input type="text" class="search-input" id="searchInput" placeholder="Search errors...">
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>User</th><th>Error Message</th><th>Type</th><th>Device</th><th>Time</th></tr></thead>
            <tbody>
                <?php if (empty($errors)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:32px;color:var(--green)">
                        <i class="fas fa-check-circle" style="font-size:24px"></i>
                        <p class="mt-2 mb-0" style="font-weight:600;font-size:13px">No errors found</p>
                    </td></tr>
                <?php else: foreach ($errors as $err):
                    $msg = $err['error_message'] ?? '';
                    $type = 'Other';
                    if (stripos($msg, 'network') !== false || stripos($msg, 'fetch') !== false) $type = 'Network';
                    elseif (stripos($msg, 'null') !== false || stripos($msg, 'undefined') !== false) $type = 'Null Reference';
                    elseif (stripos($msg, 'timeout') !== false) $type = 'Timeout';
                    elseif (stripos($msg, 'permission') !== false) $type = 'Permission';
                    elseif (stripos($msg, 'memory') !== false) $type = 'Memory';
                    elseif (stripos($msg, 'render') !== false || stripos($msg, 'component') !== false) $type = 'UI/Render';
                    elseif (stripos($msg, 'database') !== false || stripos($msg, 'sql') !== false) $type = 'Database';
                    elseif (stripos($msg, 'auth') !== false || stripos($msg, 'login') !== false) $type = 'Auth';
                    $typeLower = strtolower(str_replace(['/', ' '], ['', '-'], $type));
                ?>
                    <tr class="err-row">
                        <td style="font-size:11px;color:var(--text-muted)">#<?= $err['id'] ?></td>
                        <td>
                            <strong style="font-size:12px"><?= e($err['user_name'] ?: 'Unknown') ?></strong>
                            <br><small style="color:var(--text-muted)"><?= e($err['business_name'] ?? '') ?></small>
                        </td>
                        <td style="max-width:400px">
                            <div class="error-msg" onclick="this.classList.toggle('expanded')"><?= e($msg) ?></div>
                            <?php if ($err['stack_trace']): ?>
                                <div class="stack-trace" id="stack-<?= $err['id'] ?>"><?= e($err['stack_trace']) ?></div>
                                <small><a href="#" onclick="event.preventDefault();var s=document.getElementById('stack-<?= $err['id'] ?>');s.style.display=s.style.display==='block'?'none':'block'" style="font-size:10px;color:var(--primary)">Stack Trace</a></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="type-badge tb-<?= $typeLower ?>"><?= $type ?></span></td>
                        <td style="font-size:10px;max-width:120px;word-break:break-all;color:var(--text-muted)"><?= e($err['device_info'] ?: '-') ?></td>
                        <td style="font-size:11px;white-space:nowrap;color:var(--text-muted)"><?= date('M d, H:i', strtotime($err['created_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('.err-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: { labels: <?= json_encode(array_map(fn($d) => date('M d', strtotime($d)), array_keys($trendDays))) ?>, datasets: [{ data: <?= json_encode(array_values($trendDays)) ?>, borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.06)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font:{size:11} } }, x:{ticks:{font:{size:10}}} } }
});

new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys($errorTypes)) ?>, datasets: [{ data: <?= json_encode(array_values($errorTypes)) ?>, backgroundColor: ['#2563EB','#DB2777','#D97706','#DC2626','#7C3AED','#059669','#0891B2','#EA580C','#6B7280','#6B7280'], borderWidth: 0 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font:{size:11}, padding:12 } } } }
});
</script>

<?php include 'includes/footer.php'; ?>
