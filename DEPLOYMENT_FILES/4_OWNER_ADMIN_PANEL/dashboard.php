<?php
/**
 * Owner Panel - Main Dashboard
 */
require_once 'config/db.php';
require_once 'config/functions.php';

requireOwnerLogin();

$pageTitle = 'Dashboard';

// Get time period filter
$period = $_GET['period'] ?? 'all';

// Set date filter based on period
$dateFilter = '';
switch($period) {
    case 'daily':
        $dateFilter = "AND DATE(date) = CURDATE()";
        break;
    case 'monthly':
        $dateFilter = "AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
        break;
    case 'yearly':
        $dateFilter = "AND YEAR(date) = YEAR(CURDATE())";
        break;
    default:
        $dateFilter = '';
}

// Get dashboard statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // Active users (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $activeUsers = $stmt->fetch()['total'];
    
    // Total revenue (with date filter)
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills WHERE 1=1 $dateFilter");
    $totalRevenue = $stmt->fetch()['total'];
    
    // Total expenses (with date filter)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE 1=1 $dateFilter");
    $totalExpenses = $stmt->fetch()['total'];
    
    // Total profit
    $totalProfit = $totalRevenue - $totalExpenses;
    $profitMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;
    
    // Today's revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills WHERE DATE(date) = CURDATE()");
    $todayRevenue = $stmt->fetch()['total'] ?? 0;
    
    // Today's expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(date) = CURDATE()");
    $todayExpenses = $stmt->fetch()['total'] ?? 0;
    
    // Today's profit
    $todayProfit = $todayRevenue - $todayExpenses;
    
    // Yesterday's revenue for comparison
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills WHERE DATE(date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $yesterdayRevenue = $stmt->fetch()['total'] ?? 0;
    
    // Total products (no date filter - always show all)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch()['total'];
    
    // Total bills (with date filter)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bills WHERE 1=1 $dateFilter");
    $totalBills = $stmt->fetch()['total'];
    
    // Today's bills
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bills WHERE DATE(date) = CURDATE()");
    $todayBills = $stmt->fetch()['total'];
    
    // Revenue trend (last 30 days)
    $stmt = $pdo->query("
        SELECT DATE(date) as date, COALESCE(SUM(grand_total), 0) as revenue
        FROM bills
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(date)
        ORDER BY date ASC
    ");
    $revenueTrend = $stmt->fetchAll();
    
    // Top 10 businesses by revenue
    $stmt = $pdo->query("
        SELECT 
            u.business_name,
            u.name,
            COUNT(b.id) as bill_count,
            COALESCE(SUM(b.grand_total), 0) as total_revenue
        FROM users u
        LEFT JOIN bills b ON u.id = b.user_id
        GROUP BY u.id
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $topBusinesses = $stmt->fetchAll();
    
    // Recent user registrations
    $stmt = $pdo->query("
        SELECT name, business_name, email, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentUsers = $stmt->fetchAll();
    
    // User growth (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as user_count
        FROM users
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $userGrowth = $stmt->fetchAll();

    // Live online users
    $liveOnline = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM active_sessions WHERE last_ping >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        $liveOnline = $stmt->fetch()['c'] ?? 0;
    } catch(Exception $e) {}

    // Due bills summary
    $stmt = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(grand_total - COALESCE(paid_amount,0)),0) as pending FROM bills WHERE due_status IN ('unpaid','partial')");
    $dueData = $stmt->fetch();
    $dueBillCount = $dueData['cnt'] ?? 0;
    $duePending = $dueData['pending'] ?? 0;

    // Low stock products
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM products WHERE stock <= low_stock_threshold AND stock > 0");
    $lowStockCount = $stmt->fetch()['c'] ?? 0;

    // Out of stock
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM products WHERE stock <= 0");
    $outOfStockCount = $stmt->fetch()['c'] ?? 0;

    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM customers");
    $totalCustomers = $stmt->fetch()['c'] ?? 0;

    // App errors today
    $errorCountToday = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM app_error_logs WHERE DATE(created_at) = CURDATE()");
        $errorCountToday = $stmt->fetch()['c'] ?? 0;
    } catch(Exception $e) {}
    
} catch(PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalUsers = $activeUsers = $totalRevenue = $todayRevenue = 0;
    $totalProducts = $totalBills = $todayBills = 0;
    $revenueTrend = $topBusinesses = $recentUsers = $userGrowth = [];
    $liveOnline = $dueBillCount = $duePending = $lowStockCount = $outOfStockCount = $totalCustomers = $errorCountToday = 0;
}

include 'includes/header.php';
?>

<!-- Period Filter -->
<div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
    <a href="?period=daily" class="btn btn-sm btn-<?= $period=='daily'?'primary':'outline-secondary' ?>" style="border-radius:8px">Today</a>
    <a href="?period=monthly" class="btn btn-sm btn-<?= $period=='monthly'?'primary':'outline-secondary' ?>" style="border-radius:8px">This Month</a>
    <a href="?period=yearly" class="btn btn-sm btn-<?= $period=='yearly'?'primary':'outline-secondary' ?>" style="border-radius:8px">This Year</a>
    <a href="?period=all" class="btn btn-sm btn-<?= $period=='all'?'primary':'outline-secondary' ?>" style="border-radius:8px">All Time</a>
    <span style="font-size:12px;color:var(--text-muted);margin-left:8px">
        <?php switch($period){ case 'daily': echo 'Today'; break; case 'monthly': echo date('F Y'); break; case 'yearly': echo date('Y'); break; default: echo 'All Time'; } ?>
    </span>
</div>

<!-- Live Status Strip -->
<div class="stat-card mb-4" style="padding:14px 24px;background:#FAFAFA">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span style="width:8px;height:8px;border-radius:50%;background:var(--green);animation:pulse 1.5s infinite;display:inline-block"></span>
            <span style="font-size:13px;font-weight:600"><?= $liveOnline ?> Online</span>
        </div>
        <div style="font-size:13px"><span style="color:var(--text-muted)">Today's Profit</span> <strong style="color:<?= $todayProfit>=0?'var(--green)':'var(--red)' ?>;margin-left:4px"><?= formatCurrency($todayProfit) ?></strong></div>
        <div style="font-size:13px"><span style="color:var(--text-muted)">Due Pending</span> <strong style="color:var(--amber);margin-left:4px"><?= formatCurrency($duePending) ?></strong> <span style="color:var(--text-muted);font-size:11px">(<?= $dueBillCount ?>)</span></div>
        <div style="font-size:13px"><span style="color:var(--text-muted)">Low Stock</span> <strong style="color:<?= $lowStockCount>0?'var(--amber)':'var(--green)' ?>;margin-left:4px"><?= $lowStockCount ?></strong> <span style="color:var(--text-muted);font-size:11px">+<?= $outOfStockCount ?> out</span></div>
        <?php if($errorCountToday > 0): ?><div style="font-size:13px"><span style="color:var(--text-muted)">Errors</span> <strong style="color:var(--red);margin-left:4px"><?= $errorCountToday ?></strong></div><?php endif; ?>
    </div>
</div>

<!-- Financial Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
            <div class="stat-trend" style="color:var(--green)">₹<?= formatNumber($todayRevenue) ?> today</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Expenses</div>
            <div class="stat-value"><?= formatCurrency($totalExpenses) ?></div>
            <div class="stat-trend" style="color:var(--red)">₹<?= formatNumber($todayExpenses) ?> today</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Profit</div>
            <div class="stat-value"><?= formatCurrency($totalProfit) ?></div>
            <div class="stat-trend" style="color:<?= $profitMargin>0?'var(--green)':'var(--red)' ?>"><?= $profitMargin ?>% margin</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= formatNumber($totalUsers) ?></div>
            <div class="stat-trend"><?= $activeUsers ?> active this week</div>
        </div>
    </div>
</div>

<!-- Secondary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Bills</div>
            <div class="stat-value" style="font-size:20px"><?= formatNumber($totalBills) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Products</div>
            <div class="stat-value" style="font-size:20px"><?= formatNumber($totalProducts) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Customers</div>
            <div class="stat-value" style="font-size:20px"><?= formatNumber($totalCustomers) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Avg Revenue / User</div>
            <div class="stat-value" style="font-size:20px"><?= $totalUsers > 0 ? formatCurrency($totalRevenue / $totalUsers) : '₹0' ?></div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Revenue Trend (30 Days)</h6>
            <canvas id="revenueChart" height="80"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">User Growth (6 Months)</h6>
            <canvas id="userGrowthChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Tables -->
<div class="row g-3">
    <div class="col-md-7">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Top Businesses</h6>
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Business</th><th>Bills</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php if (empty($topBusinesses)): ?>
                        <tr><td colspan="4" class="text-center" style="color:var(--text-muted);padding:24px">No data</td></tr>
                    <?php else: foreach ($topBusinesses as $i => $biz): ?>
                        <tr>
                            <td style="color:var(--text-muted)"><?= $i+1 ?></td>
                            <td><strong><?= e($biz['business_name'] ?: $biz['name']) ?></strong></td>
                            <td><?= number_format($biz['bill_count']) ?></td>
                            <td><strong><?= formatCurrency($biz['total_revenue']) ?></strong></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Recent Registrations</h6>
            <table class="table table-hover mb-0">
                <thead><tr><th>Business</th><th>Joined</th></tr></thead>
                <tbody>
                    <?php if (empty($recentUsers)): ?>
                        <tr><td colspan="2" class="text-center" style="color:var(--text-muted);padding:24px">No users yet</td></tr>
                    <?php else: foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><strong><?= e($u['business_name'] ?: $u['name']) ?></strong><br><small style="color:var(--text-muted)"><?= e($u['email']) ?></small></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= timeAgo($u['created_at']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($revenueTrend, 'date')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($revenueTrend, 'revenue')) ?>,
            borderColor: '#4F46E5',
            backgroundColor: 'rgba(79,70,229,0.06)',
            tension: 0.4, fill: true, borderWidth: 2, pointRadius: 0
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '₹'+v.toLocaleString(), font:{size:11} } }, x: { ticks: { font:{size:10}, maxTicksLimit:8 } } } }
});

new Chart(document.getElementById('userGrowthChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($userGrowth, 'month')) ?>,
        datasets: [{ data: <?= json_encode(array_column($userGrowth, 'user_count')) ?>, backgroundColor: '#4F46E5', borderRadius: 4, barThickness: 20 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font:{size:11} } }, x: { ticks: { font:{size:10} } } } }
});
</script>

<?php include 'includes/footer.php'; ?>
