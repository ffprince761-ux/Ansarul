<?php
/**
 * Owner Panel - Advanced User Detail View
 * Complete monitoring: usage, device, sessions, bills, products, customers, dues, errors
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();

$userId = $_GET['id'] ?? 0;
$period = $_GET['period'] ?? 'all';
$pageTitle = 'User Analytics';

$dateFilter = '';
$expDateFilter = '';
switch($period) {
    case 'daily': $dateFilter = "AND DATE(b.date) = CURDATE()"; $expDateFilter = "AND DATE(e.date) = CURDATE()"; break;
    case 'monthly': $dateFilter = "AND YEAR(b.date) = YEAR(CURDATE()) AND MONTH(b.date) = MONTH(CURDATE())"; $expDateFilter = "AND YEAR(e.date) = YEAR(CURDATE()) AND MONTH(e.date) = MONTH(CURDATE())"; break;
    case 'yearly': $dateFilter = "AND YEAR(b.date) = YEAR(CURDATE())"; $expDateFilter = "AND YEAR(e.date) = YEAR(CURDATE())"; break;
    default: $dateFilter = ''; $expDateFilter = '';
}

try {
    // User info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) { header('Location: users.php'); exit(); }

    // Counts
    $productCount = $pdo->prepare("SELECT COUNT(*) as c FROM products WHERE user_id = ?"); $productCount->execute([$userId]); $productCount = $productCount->fetch()['c'];
    $customerCount = $pdo->prepare("SELECT COUNT(*) as c FROM customers WHERE user_id = ?"); $customerCount->execute([$userId]); $customerCount = $customerCount->fetch()['c'];

    // Bills stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(b.grand_total),0) as revenue FROM bills b WHERE b.user_id = ? $dateFilter");
    $stmt->execute([$userId]); $billStats = $stmt->fetch();

    // Today bills
    $todayBills = $pdo->prepare("SELECT COUNT(*) as c, COALESCE(SUM(grand_total),0) as t FROM bills WHERE user_id = ? AND DATE(date) = CURDATE()"); $todayBills->execute([$userId]); $todayBills = $todayBills->fetch();

    // Expenses stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(e.amount),0) as total FROM expenses e WHERE e.user_id = ? $expDateFilter");
    $stmt->execute([$userId]); $expStats = $stmt->fetch();

    $profit = $billStats['revenue'] - $expStats['total'];
    $margin = $billStats['revenue'] > 0 ? round(($profit / $billStats['revenue']) * 100, 1) : 0;

    // Due bills
    $dueBills = $pdo->prepare("SELECT * FROM bills WHERE user_id = ? AND (due_status = 'unpaid' OR due_status = 'partial' OR due_status = 'due') ORDER BY date DESC");
    $dueBills->execute([$userId]); $dueBills = $dueBills->fetchAll();
    $totalDueAmount = 0;
    foreach ($dueBills as $db) { $totalDueAmount += ($db['grand_total'] - ($db['paid_amount'] ?? 0)); }

    // All bills
    $allBills = $pdo->prepare("SELECT * FROM bills b WHERE b.user_id = ? $dateFilter ORDER BY b.date DESC LIMIT 50");
    $allBills->execute([$userId]); $allBills = $allBills->fetchAll();

    // Products
    $products = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC"); $products->execute([$userId]); $products = $products->fetchAll();
    $lowStock = array_filter($products, fn($p) => $p['stock'] <= ($p['low_stock_threshold'] ?? 5));

    // Customers
    $customers = $pdo->prepare("SELECT * FROM customers WHERE user_id = ? ORDER BY created_at DESC"); $customers->execute([$userId]); $customers = $customers->fetchAll();

    // Expenses list
    $expenses = $pdo->prepare("SELECT * FROM expenses e WHERE e.user_id = ? $expDateFilter ORDER BY e.date DESC LIMIT 30");
    $expenses->execute([$userId]); $expenses = $expenses->fetchAll();

    // Error logs
    $errors = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM app_error_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$userId]); $errors = $stmt->fetchAll();
    } catch(Exception $e) {}

    // Active session (is user online right now?)
    $session = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM active_sessions WHERE user_id = ?");
        $stmt->execute([$userId]); $session = $stmt->fetch();
    } catch(Exception $e) {}

    // Bill trend (last 7 days)
    $billTrend = $pdo->prepare("SELECT DATE(date) as d, COUNT(*) as c, COALESCE(SUM(grand_total),0) as r FROM bills WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(date) ORDER BY d ASC");
    $billTrend->execute([$userId]); $billTrend = $billTrend->fetchAll();

    // Real last seen = most recent activity across all tables
    $lastSeenStmt = $pdo->prepare("
        SELECT GREATEST(
            COALESCE((SELECT MAX(created_at) FROM bills WHERE user_id = ?), '2000-01-01'),
            COALESCE((SELECT MAX(created_at) FROM products WHERE user_id = ?), '2000-01-01'),
            COALESCE((SELECT MAX(created_at) FROM customers WHERE user_id = ?), '2000-01-01'),
            COALESCE((SELECT MAX(created_at) FROM expenses WHERE user_id = ?), '2000-01-01'),
            COALESCE(?, '2000-01-01')
        ) as last_seen
    ");
    $lastSeenStmt->execute([$userId, $userId, $userId, $userId, $user['updated_at']]);
    $lastSeen = $lastSeenStmt->fetch()['last_seen'];

    // Registered days ago
    $regDays = max(1, floor((time() - strtotime($user['created_at'])) / 86400));
    $avgBillsPerDay = round($billStats['cnt'] / $regDays, 1);

    // Subscription data
    $masterEnabled = '0';
    $defaultLimit = 500;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_plans (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            plan_type ENUM('free','paid') DEFAULT 'free',
            bill_limit INT DEFAULT 500,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $pdo->exec("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES ('billing_limit_enabled','0'),('default_bill_limit','500')");
        $masterEnabled = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='billing_limit_enabled'")->fetch()['setting_value'] ?? '0';
        $defaultLimit = intval($pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='default_bill_limit'")->fetch()['setting_value'] ?? 500);
    } catch(Exception $e) {}

    $userPlan = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_plans WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userPlan = $stmt->fetch();
    } catch(Exception $e) {}

    $planType = $userPlan ? $userPlan['plan_type'] : 'free';
    $billLimit = $userPlan ? intval($userPlan['bill_limit']) : $defaultLimit;
    $totalBillCount = intval($billStats['cnt']);
    $usagePct = $billLimit > 0 ? min(100, round(($totalBillCount / $billLimit) * 100)) : 0;
    $limitReached = ($planType === 'free' && $totalBillCount >= $billLimit);

    // Monthly bill counts for tracking (last 6 months)
    $monthlyBills = $pdo->prepare("SELECT DATE_FORMAT(date,'%Y-%m') as month, COUNT(*) as cnt FROM bills WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY month ASC");
    $monthlyBills->execute([$userId]);
    $monthlyBills = $monthlyBills->fetchAll();

} catch(PDOException $e) {
    error_log("User detail error: " . $e->getMessage());
    header('Location: users.php');
    exit();
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-on{background:var(--green-light);color:var(--green)}.sb-off{background:var(--red-light);color:var(--red)}.sb-warn{background:var(--amber-light);color:var(--amber)}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border:0}.info-label{color:var(--text-muted);font-weight:500}.info-val{color:var(--text-primary);font-weight:500}
.mini-chart{height:40px;display:flex;align-items:flex-end;gap:3px}.mini-bar{flex:1;background:var(--primary);border-radius:2px 2px 0 0;min-height:3px}
.err-item{border-left:3px solid var(--red);padding:10px 12px;margin-bottom:8px;background:var(--red-light);border-radius:0 6px 6px 0;font-size:12px}
.tab-panel{display:none}.tab-panel.active{display:block}
.toggle-sm{position:relative;width:40px;height:22px;background:#D1D5DB;border-radius:11px;cursor:pointer;transition:all .3s;display:inline-block;vertical-align:middle}
.toggle-sm.active{background:var(--green)}
.toggle-sm::after{content:'';position:absolute;top:2px;left:2px;width:18px;height:18px;background:#fff;border-radius:50%;transition:all .3s;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.toggle-sm.active::after{left:20px}
.usage-bar{height:8px;border-radius:4px;background:#F3F4F6;overflow:hidden;width:100%}
.usage-fill{height:100%;border-radius:4px;transition:width .5s}
.plan-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;border:2px solid transparent;transition:all .2s}
.plan-badge.free{background:var(--blue-light);color:var(--blue);border-color:var(--blue)}
.plan-badge.paid{background:var(--green-light);color:var(--green);border-color:var(--green)}
.plan-badge.inactive{opacity:.4;border-color:transparent;cursor:pointer}
.track-bar{display:flex;align-items:flex-end;gap:4px;height:50px}.track-col{flex:1;border-radius:3px 3px 0 0;min-height:4px;position:relative}
.track-col .track-label{position:absolute;bottom:-16px;left:50%;transform:translateX(-50%);font-size:9px;color:var(--text-muted);white-space:nowrap}
.pulse-dot{width:8px;height:8px;border-radius:50%;display:inline-block;animation:pulse 2s infinite}
.pulse-green{background:var(--green)}.pulse-red{background:var(--red)}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}
</style>

<!-- Header -->
<div class="card mb-4" style="border:1px solid var(--border);border-radius:12px">
    <div class="card-body" style="padding:20px">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="mb-0" style="font-weight:700"><?= e($user['name']) ?></h5>
                    <?php if ($session): ?>
                        <span class="sb sb-on">Online</span>
                    <?php else: ?>
                        <span class="sb" style="background:#F3F4F6;color:var(--text-muted)">Offline</span>
                    <?php endif; ?>
                    <?php if ($user['is_blocked'] ?? false): ?>
                        <span class="sb sb-off">Blocked</span>
                    <?php endif; ?>
                </div>
                <p class="mb-0" style="font-size:13px;color:var(--text-secondary)"><?= e($user['business_name'] ?: 'No Business Name') ?></p>
                <small style="color:var(--text-muted)">ID: #<?= $userId ?> &middot; Registered: <?= date('M d, Y', strtotime($user['created_at'])) ?> (<?= $regDays ?> days ago)</small>
            </div>
            <a href="users.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
</div>

<!-- Period Filter -->
<div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
    <span style="font-size:12px;font-weight:600;color:var(--text-muted)">Period:</span>
    <a href="?id=<?=$userId?>&period=daily" class="btn btn-sm btn-<?=$period=='daily'?'primary':'outline-secondary'?>" style="border-radius:8px">Today</a>
    <a href="?id=<?=$userId?>&period=monthly" class="btn btn-sm btn-<?=$period=='monthly'?'primary':'outline-secondary'?>" style="border-radius:8px">This Month</a>
    <a href="?id=<?=$userId?>&period=yearly" class="btn btn-sm btn-<?=$period=='yearly'?'primary':'outline-secondary'?>" style="border-radius:8px">This Year</a>
    <a href="?id=<?=$userId?>&period=all" class="btn btn-sm btn-<?=$period=='all'?'primary':'outline-secondary'?>" style="border-radius:8px">All Time</a>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value" style="color:var(--green);font-size:20px">₹<?= formatNumber($billStats['revenue']) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Expenses</div><div class="stat-value" style="color:var(--red);font-size:20px">₹<?= formatNumber($expStats['total']) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Profit (<?=$margin?>%)</div><div class="stat-value" style="color:<?=$profit>=0?'var(--green)':'var(--red)'?>;font-size:20px">₹<?= formatNumber(abs($profit)) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Bills (<?=$avgBillsPerDay?>/day)</div><div class="stat-value"><?= $billStats['cnt'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Due Bills</div><div class="stat-value" style="color:var(--red)"><?= count($dueBills) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Today's Bills</div><div class="stat-value"><?= $todayBills['c'] ?></div></div></div>
</div>

<!-- Subscription & Limits -->
<?php
$barColor = $planType === 'paid' ? 'var(--green)' : ($usagePct >= 100 ? 'var(--red)' : ($usagePct >= 80 ? 'var(--amber)' : 'var(--blue)'));
?>
<div class="card mb-4" style="border:1px solid var(--border);border-radius:12px">
    <div class="card-body" style="padding:20px">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 style="font-weight:700;font-size:15px;margin:0"><i class="fas fa-crown" style="color:var(--amber)"></i> Subscription & Limits</h6>
            <div class="d-flex align-items-center gap-2">
                <small style="color:var(--text-muted);font-size:11px">Master:</small>
                <span class="sb <?= $masterEnabled === '1' ? 'sb-on' : '' ?>" style="<?= $masterEnabled !== '1' ? 'background:#F3F4F6;color:var(--text-muted)' : '' ?>"><?= $masterEnabled === '1' ? 'ON' : 'OFF' ?></span>
            </div>
        </div>

        <div class="row g-4">
            <!-- Plan & Controls -->
            <div class="col-md-4">
                <div style="padding:14px;background:var(--bg);border-radius:10px;border:1px solid var(--border)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:8px">PLAN TYPE</div>
                    <div class="d-flex gap-2 mb-3">
                        <span class="plan-badge free <?= $planType === 'free' ? '' : 'inactive' ?>" onclick="setPlan('free')">Free</span>
                        <span class="plan-badge paid <?= $planType === 'paid' ? '' : 'inactive' ?>" onclick="setPlan('paid')">Paid</span>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:6px">BILL LIMIT</div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <input type="number" id="billLimitInput" value="<?= $billLimit ?>" min="1" style="width:80px;padding:4px 8px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-weight:600;text-align:center" <?= $planType === 'paid' ? 'disabled' : '' ?>>
                        <button class="btn btn-sm btn-primary" style="border-radius:6px;font-size:11px;padding:4px 12px" onclick="updateLimit()">Save</button>
                    </div>

                    <div class="info-row" style="padding:6px 0;border:0">
                        <span class="info-label">Status</span>
                        <span class="info-val">
                            <?php if ($planType === 'paid'): ?>
                                <span class="sb sb-on">Unlimited</span>
                            <?php elseif ($limitReached): ?>
                                <span class="sb sb-off">LIMIT REACHED</span>
                            <?php elseif ($masterEnabled === '1'): ?>
                                <span class="sb sb-on">Active</span>
                            <?php else: ?>
                                <span class="sb" style="background:#F3F4F6;color:var(--text-muted)">Not Enforced</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($userPlan): ?>
                    <div class="info-row" style="padding:6px 0;border:0">
                        <span class="info-label">Plan Updated</span>
                        <span class="info-val" style="font-size:11px"><?= date('M d, Y h:i A', strtotime($userPlan['updated_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Usage -->
            <div class="col-md-4">
                <div style="padding:14px;background:var(--bg);border-radius:10px;border:1px solid var(--border)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:8px">USAGE</div>
                    <?php if ($planType === 'paid'): ?>
                        <div class="text-center py-3">
                            <div style="font-size:28px;font-weight:800;color:var(--green)"><?= number_format($totalBillCount) ?></div>
                            <div style="font-size:12px;color:var(--text-muted)">Bills Created</div>
                            <span class="sb sb-on" style="margin-top:6px">Unlimited Plan</span>
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-3">
                            <span style="font-size:28px;font-weight:800;color:<?= $barColor ?>"><?= number_format($totalBillCount) ?></span>
                            <span style="font-size:14px;color:var(--text-muted)"> / <?= number_format($billLimit) ?></span>
                        </div>
                        <div class="usage-bar mb-2">
                            <div class="usage-fill" style="width:<?= $usagePct ?>%;background:<?= $barColor ?>"></div>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:11px">
                            <span style="color:var(--text-muted)"><?= $usagePct ?>% used</span>
                            <span style="color:<?= $barColor ?>;font-weight:600"><?= number_format($billLimit - $totalBillCount) ?> remaining</span>
                        </div>
                        <?php if ($limitReached && $masterEnabled === '1'): ?>
                            <div class="text-center mt-2">
                                <span class="sb sb-off" style="font-size:12px"><i class="fas fa-ban"></i> User cannot create new bills</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Monthly Tracking -->
            <div class="col-md-4">
                <div style="padding:14px;background:var(--bg);border-radius:10px;border:1px solid var(--border)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:8px">MONTHLY BILL TRACKING</div>
                    <?php if (empty($monthlyBills)): ?>
                        <div class="text-center py-3" style="color:var(--text-muted);font-size:12px">No billing data</div>
                    <?php else:
                        $maxM = max(array_column($monthlyBills, 'cnt') ?: [1]);
                    ?>
                        <div class="track-bar" style="margin-bottom:20px">
                            <?php foreach ($monthlyBills as $mb):
                                $h = max(4, ($mb['cnt'] / $maxM) * 46);
                                $mLabel = date('M', strtotime($mb['month'] . '-01'));
                            ?>
                                <div class="track-col" style="height:<?= $h ?>px;background:var(--primary)" title="<?= $mLabel ?>: <?= $mb['cnt'] ?> bills">
                                    <span class="track-label"><?= $mLabel ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:8px">
                            <?php 
                                $thisMonth = date('Y-m');
                                $thisMonthBills = 0;
                                foreach ($monthlyBills as $mb) { if ($mb['month'] === $thisMonth) $thisMonthBills = $mb['cnt']; }
                            ?>
                            This Month: <strong style="color:var(--text-primary)"><?= $thisMonthBills ?> bills</strong>
                            &middot; Avg: <strong style="color:var(--text-primary)"><?= count($monthlyBills) > 0 ? round(array_sum(array_column($monthlyBills, 'cnt')) / count($monthlyBills)) : 0 ?>/month</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: User Info + Live Session + Activity -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">User Information</h6>
            <div class="info-row"><span class="info-label">Name</span><span class="info-val"><?= e($user['name']) ?></span></div>
            <div class="info-row"><span class="info-label">Business</span><span class="info-val"><?= e($user['business_name'] ?: 'N/A') ?></span></div>
            <div class="info-row"><span class="info-label">Email</span><span class="info-val"><?= e($user['email']) ?></span></div>
            <div class="info-row"><span class="info-label">Mobile</span><span class="info-val"><?= e($user['mobile'] ?: 'N/A') ?></span></div>
            <div class="info-row"><span class="info-label">Address</span><span class="info-val"><?= e($user['address'] ?: 'N/A') ?></span></div>
            <div class="info-row"><span class="info-label">Registered</span><span class="info-val"><?= date('M d, Y h:i A', strtotime($user['created_at'])) ?></span></div>
            <div class="info-row"><span class="info-label">Last Active</span><span class="info-val"><?= timeAgo($lastSeen) ?></span></div>
            <div class="info-row"><span class="info-label">Status</span><span class="info-val"><?= ($user['is_blocked'] ?? false) ? '<span class="sb sb-off">Blocked</span>' : '<span class="sb sb-on">Active</span>' ?></span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 style="font-weight:700;font-size:14px;margin:0">Live Session</h6>
                <?php if ($session): ?><span class="sb sb-on">Online</span><?php endif; ?>
            </div>
            <?php if ($session): ?>
                <div class="info-row"><span class="info-label">Status</span><span class="info-val"><span class="pulse-dot pulse-green"></span> Online Now</span></div>
                <div class="info-row"><span class="info-label">Device</span><span class="info-val"><?= e($session['device_info'] ?: 'Unknown') ?></span></div>
                <div class="info-row"><span class="info-label">Screen</span><span class="info-val"><?= e($session['app_screen'] ?: 'Home') ?></span></div>
                <div class="info-row"><span class="info-label">Session Start</span><span class="info-val"><?= date('h:i A', strtotime($session['session_start'])) ?></span></div>
                <div class="info-row"><span class="info-label">Last Ping</span><span class="info-val"><?= timeAgo($session['last_ping']) ?></span></div>
                <?php 
                    $sessionDuration = time() - strtotime($session['session_start']);
                    $mins = floor($sessionDuration / 60);
                    $hrs = floor($mins / 60);
                    $mins = $mins % 60;
                ?>
                <div class="info-row"><span class="info-label">Duration</span><span class="info-val" style="color:var(--green);font-weight:600"><?= $hrs ?>h <?= $mins ?>m</span></div>
            <?php else: ?>
                <div class="text-center py-4">
                    <span class="pulse-dot pulse-red" style="width:12px;height:12px"></span>
                    <p class="mt-2 mb-0" style="color:var(--text-muted);font-size:13px">User is Offline</p>
                    <small style="color:var(--text-muted)">Last seen: <?= timeAgo($lastSeen) ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Activity Summary</h6>
            <div class="info-row"><span class="info-label">Total Bills</span><span class="info-val"><strong><?= $billStats['cnt'] ?></strong></span></div>
            <div class="info-row"><span class="info-label">Today's Bills</span><span class="info-val" style="color:var(--green)"><strong><?= $todayBills['c'] ?></strong> (₹<?= formatNumber($todayBills['t']) ?>)</span></div>
            <div class="info-row"><span class="info-label">Products</span><span class="info-val"><?= $productCount ?></span></div>
            <div class="info-row"><span class="info-label">Customers</span><span class="info-val"><?= $customerCount ?></span></div>
            <div class="info-row"><span class="info-label">Low Stock</span><span class="info-val" style="color:var(--red)"><strong><?= count($lowStock) ?></strong> items</span></div>
            <div class="info-row"><span class="info-label">Avg Bill</span><span class="info-val"><?= $billStats['cnt'] > 0 ? formatCurrency($billStats['revenue'] / $billStats['cnt']) : '₹0' ?></span></div>
            <div class="info-row"><span class="info-label">Bills/Day</span><span class="info-val"><?= $avgBillsPerDay ?></span></div>
            <h6 style="font-weight:600;font-size:12px;margin:12px 0 8px;color:var(--text-muted)">7-Day Trend</h6>
            <div class="mini-chart">
                <?php $maxC = max(array_column($billTrend, 'c') ?: [1]); foreach ($billTrend as $t): $h = max(3, ($t['c'] / $maxC) * 36); ?>
                    <div class="mini-bar" style="height:<?=$h?>px" title="<?=$t['d']?>: <?=$t['c']?> bills, ₹<?=formatNumber($t['r'])?>"></div>
                <?php endforeach; ?>
                <?php if (empty($billTrend)): ?><span style="font-size:11px;color:var(--text-muted)">No data</span><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Section -->
<div class="table-container">
    <div class="tabs-clean mb-3">
        <button class="tab-item active" onclick="showTab('bills')">Bills (<?=count($allBills)?>)</button>
        <button class="tab-item" onclick="showTab('due')">Due (<?=count($dueBills)?>)</button>
        <button class="tab-item" onclick="showTab('products')">Products (<?=count($products)?>)</button>
        <button class="tab-item" onclick="showTab('customers')">Customers (<?=count($customers)?>)</button>
        <button class="tab-item" onclick="showTab('expenses')">Expenses (<?=count($expenses)?>)</button>
        <button class="tab-item" onclick="showTab('errors')">Errors (<?=count($errors)?>)</button>
    </div>

    <!-- Bills Tab -->
    <div class="tab-panel active" id="tab-bills">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Items</th><th>Amount</th><th>Payment</th><th>Due Status</th></tr></thead>
                <tbody>
                    <?php if (empty($allBills)): ?>
                        <tr><td colspan="7" class="text-center" style="color:var(--text-muted);padding:20px">No bills found</td></tr>
                    <?php else: foreach ($allBills as $b): $items = json_decode($b['items'], true) ?: []; ?>
                        <tr>
                            <td><strong><?= e($b['invoice_number']) ?></strong></td>
                            <td><?= e($b['customer_name'] ?: 'Walk-in') ?></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($b['date'])) ?></td>
                            <td><?= count($items) ?></td>
                            <td><strong><?= formatCurrency($b['grand_total']) ?></strong></td>
                            <td><span class="sb <?=$b['payment_mode']==='Due'?'sb-warn':'sb-on'?>"><?= e($b['payment_mode']) ?></span></td>
                            <td>
                                <?php $ds = $b['due_status'] ?? 'paid'; $dsClass = $ds === 'paid' ? 'sb-on' : ($ds === 'partial' ? 'sb-warn' : 'sb-off'); ?>
                                <span class="sb <?=$dsClass?>"><?= ucfirst($ds) ?></span>
                                <?php if ($ds !== 'paid' && isset($b['paid_amount'])): ?>
                                    <small style="color:var(--text-muted);display:block">Paid: ₹<?= number_format($b['paid_amount'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Due Bills Tab -->
    <div class="tab-panel" id="tab-due">
        <div class="row g-3 mb-3">
            <div class="col-md-4 text-center"><div class="stat-value" style="color:var(--red)"><?= count($dueBills) ?></div><div class="stat-label">Due Bills</div></div>
            <div class="col-md-4 text-center"><div class="stat-value" style="font-size:20px">₹<?= formatNumber($totalDueAmount) ?></div><div class="stat-label">Pending</div></div>
            <div class="col-md-4 text-center"><div class="stat-value"><?= $billStats['cnt'] > 0 ? round((count($dueBills) / $billStats['cnt']) * 100, 1) : 0 ?>%</div><div class="stat-label">Due Rate</div></div>
        </div>
        <div class="table-responsive" style="max-height:350px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Due Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($dueBills)): ?>
                        <tr><td colspan="7" class="text-center" style="color:var(--green);padding:20px"><i class="fas fa-check-circle"></i> No due bills</td></tr>
                    <?php else: foreach ($dueBills as $db): $remaining = $db['grand_total'] - ($db['paid_amount'] ?? 0); ?>
                        <tr>
                            <td><strong><?= e($db['invoice_number']) ?></strong></td>
                            <td><?= e($db['customer_name']) ?></td>
                            <td><?= formatCurrency($db['grand_total']) ?></td>
                            <td style="color:var(--green)">₹<?= number_format($db['paid_amount'] ?? 0, 2) ?></td>
                            <td style="color:var(--red);font-weight:600">₹<?= number_format($remaining, 2) ?></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= $db['due_date'] ? date('M d, Y', strtotime($db['due_date'])) : '-' ?></td>
                            <td><span class="sb <?=($db['due_status']??'')=='partial'?'sb-warn':'sb-off'?>"><?= ucfirst($db['due_status'] ?? 'due') ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Products Tab -->
    <div class="tab-panel" id="tab-products">
        <div class="row g-3 mb-3">
            <div class="col-md-4 text-center"><div class="stat-value"><?=$productCount?></div><div class="stat-label">Total</div></div>
            <div class="col-md-4 text-center"><div class="stat-value" style="color:var(--red)"><?=count($lowStock)?></div><div class="stat-label">Low Stock</div></div>
            <div class="col-md-4 text-center"><div class="stat-value" style="color:var(--blue)"><?=count(array_unique(array_column($products,'category')))?></div><div class="stat-label">Categories</div></div>
        </div>
        <div class="table-responsive" style="max-height:350px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Threshold</th><th>Unit</th><th>Created</th></tr></thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center" style="color:var(--text-muted);padding:20px">No products</td></tr>
                    <?php else: foreach ($products as $p): $isLow = $p['stock'] <= ($p['low_stock_threshold'] ?? 5); ?>
                        <tr style="<?=$isLow?'background:var(--red-light)':''?>">
                            <td><strong><?= e($p['name']) ?></strong></td>
                            <td style="font-size:12px"><?= e($p['category'] ?: 'N/A') ?></td>
                            <td><?= formatCurrency($p['price']) ?></td>
                            <td><span class="sb <?=$isLow?'sb-off':'sb-on'?>"><?= $p['stock'] ?></span></td>
                            <td><?= $p['low_stock_threshold'] ?? 5 ?></td>
                            <td><?= e($p['unit'] ?: 'Nos') ?></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customers Tab -->
    <div class="tab-panel" id="tab-customers">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Address</th><th>Bills</th><th>Added</th></tr></thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="6" class="text-center" style="color:var(--text-muted);padding:20px">No customers</td></tr>
                    <?php else: foreach ($customers as $c): 
                        $cBills = $pdo->prepare("SELECT COUNT(*) as c FROM bills WHERE user_id = ? AND customer_name = ?");
                        $cBills->execute([$userId, $c['name']]); $cBillCount = $cBills->fetch()['c'];
                    ?>
                        <tr>
                            <td><strong><?= e($c['name']) ?></strong></td>
                            <td style="font-size:12px"><?= e($c['mobile'] ?: 'N/A') ?></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= e($c['email'] ?: 'N/A') ?></td>
                            <td style="font-size:11px;color:var(--text-muted)"><?= e($c['address'] ?: 'N/A') ?></td>
                            <td><span class="sb sb-on"><?=$cBillCount?></span></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expenses Tab -->
    <div class="tab-panel" id="tab-expenses">
        <div class="row g-3 mb-3">
            <div class="col-md-6 text-center"><div class="stat-value" style="color:var(--red);font-size:20px">₹<?=formatNumber($expStats['total'])?></div><div class="stat-label">Total Expenses</div></div>
            <div class="col-md-6 text-center"><div class="stat-value"><?=$expStats['cnt']?></div><div class="stat-label">Count</div></div>
        </div>
        <div class="table-responsive" style="max-height:350px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Title</th><th>Category</th><th>Amount</th><th>Date</th><th>Note</th></tr></thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="5" class="text-center" style="color:var(--text-muted);padding:20px">No expenses</td></tr>
                    <?php else: foreach ($expenses as $ex): ?>
                        <tr>
                            <td><strong><?= e($ex['title'] ?? $ex['description'] ?? '-') ?></strong></td>
                            <td style="font-size:12px"><?= e($ex['category'] ?? 'General') ?></td>
                            <td style="color:var(--red);font-weight:600"><?= formatCurrency($ex['amount']) ?></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($ex['date'])) ?></td>
                            <td style="font-size:11px;color:var(--text-muted)"><?= e($ex['note'] ?? $ex['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Errors Tab -->
    <div class="tab-panel" id="tab-errors">
        <div class="row g-3 mb-3">
            <div class="col-md-6 text-center"><div class="stat-value" style="color:var(--red)"><?=count($errors)?></div><div class="stat-label">Total Errors</div></div>
            <div class="col-md-6 text-center"><div class="stat-value" style="color:<?=count($errors)==0?'var(--green)':'var(--amber)'?>"><?=count($errors)==0?'Healthy':'Issues'?></div><div class="stat-label">Health</div></div>
        </div>
        <?php if (empty($errors)): ?>
            <div class="text-center py-4" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:24px"></i><p class="mt-2 mb-0" style="font-weight:600;font-size:13px">No errors reported</p></div>
        <?php else: foreach ($errors as $err): ?>
            <div class="err-item">
                <div class="d-flex justify-content-between">
                    <strong style="color:var(--red)"><?= e($err['error_message'] ?? 'Unknown') ?></strong>
                    <small style="color:var(--text-muted)"><?= timeAgo($err['created_at']) ?></small>
                </div>
                <small style="color:var(--text-muted)"><?= e($err['device_info'] ?? '') ?></small>
                <?php if (!empty($err['stack_trace'])): ?>
                    <details class="mt-1"><summary style="font-size:11px;color:var(--primary);cursor:pointer">Stack Trace</summary>
                    <pre style="font-size:10px;background:var(--bg);padding:8px;border-radius:6px;max-height:150px;overflow:auto;margin-top:4px"><?= e($err['stack_trace']) ?></pre></details>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.closest('.tab-item').classList.add('active');
}

function setPlan(type) {
    const limit = document.getElementById('billLimitInput').value;
    fetch('api/update_plan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_user_plan&user_id=<?= $userId ?>&plan_type=' + type + '&bill_limit=' + limit
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('Error: ' + (data.error || 'Unknown'));
    });
}

function updateLimit() {
    const limit = document.getElementById('billLimitInput').value;
    if (!limit || limit < 1) { alert('Enter a valid limit'); return; }
    const currentPlan = '<?= $planType ?>';
    fetch('api/update_plan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_user_plan&user_id=<?= $userId ?>&plan_type=' + currentPlan + '&bill_limit=' + limit
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('Error: ' + (data.error || 'Unknown'));
    });
}
</script>

<?php include 'includes/footer.php'; ?>
