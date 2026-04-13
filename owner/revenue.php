<?php
/**
 * Owner Panel - Revenue Analytics
 */
require_once 'config/db.php';
require_once 'config/functions.php';

requireOwnerLogin();

$pageTitle = 'Revenue Analytics';

// Get revenue statistics
try {
    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills");
    $totalRevenue = $stmt->fetch()['total'];
    
    // This month revenue
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(grand_total), 0) as total 
        FROM bills 
        WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())
    ");
    $monthRevenue = $stmt->fetch()['total'];
    
    // Last month revenue
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(grand_total), 0) as total 
        FROM bills 
        WHERE YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
        AND MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    ");
    $lastMonthRevenue = $stmt->fetch()['total'];
    
    // Today revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills WHERE DATE(date) = CURDATE()");
    $todayRevenue = $stmt->fetch()['total'];
    
    // Average bill value
    $stmt = $pdo->query("SELECT COALESCE(AVG(grand_total), 0) as avg FROM bills");
    $avgBillValue = $stmt->fetch()['avg'];
    
    // Revenue by payment method
    $stmt = $pdo->query("
        SELECT 
            payment_mode,
            COUNT(*) as count,
            COALESCE(SUM(grand_total), 0) as total
        FROM bills
        GROUP BY payment_mode
        ORDER BY total DESC
    ");
    $paymentMethods = $stmt->fetchAll();
    
    // Monthly revenue trend (last 12 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COALESCE(SUM(grand_total), 0) as revenue,
            COUNT(*) as bill_count
        FROM bills
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyRevenue = $stmt->fetchAll();
    
    // Revenue by business
    $stmt = $pdo->query("
        SELECT 
            u.business_name,
            u.name,
            COALESCE(SUM(b.grand_total), 0) as revenue,
            COUNT(b.id) as bill_count,
            COALESCE(AVG(b.grand_total), 0) as avg_bill
        FROM users u
        LEFT JOIN bills b ON u.id = b.user_id
        GROUP BY u.id
        HAVING revenue > 0
        ORDER BY revenue DESC
        LIMIT 20
    ");
    $revenueByBusiness = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Revenue page error: " . $e->getMessage());
    $totalRevenue = $monthRevenue = $todayRevenue = $avgBillValue = 0;
    $paymentMethods = $monthlyRevenue = $revenueByBusiness = [];
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.bar-row{display:flex;align-items:center;gap:8px}
.bar-track{flex:1;height:18px;background:#F3F4F6;border-radius:4px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;display:flex;align-items:center;padding:0 8px;font-size:10px;font-weight:600;color:#fff;background:var(--primary)}
</style>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color:var(--green)"><?= formatCurrency($totalRevenue) ?></div>
            <small style="color:var(--text-muted)">All time</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">This Month</div>
            <div class="stat-value"><?= formatCurrency($monthRevenue) ?></div>
            <small style="color:var(--text-muted)"><?= getTrendIndicator($monthRevenue, $lastMonthRevenue) ?> vs last month</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Today</div>
            <div class="stat-value" style="color:var(--blue)"><?= formatCurrency($todayRevenue) ?></div>
            <small style="color:var(--text-muted)">Current day</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Avg Bill Value</div>
            <div class="stat-value"><?= formatCurrency($avgBillValue) ?></div>
            <small style="color:var(--text-muted)">Per transaction</small>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Monthly Revenue Trend</h6>
            <canvas id="monthlyRevenueChart" height="80"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Payment Methods</h6>
            <canvas id="paymentMethodChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Revenue by Business -->
<div class="table-container">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Revenue by Business</h6>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Business</th><th>Revenue</th><th>Bills</th><th>Avg Bill</th><th>Share</th></tr></thead>
            <tbody>
                <?php if (empty($revenueByBusiness)): ?>
                    <tr><td colspan="6" class="text-center" style="color:var(--text-muted);padding:20px">No revenue data</td></tr>
                <?php else: foreach ($revenueByBusiness as $i => $biz): $pct = $totalRevenue > 0 ? ($biz['revenue'] / $totalRevenue) * 100 : 0; ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                        <td><strong><?= e($biz['business_name'] ?: $biz['name']) ?></strong></td>
                        <td><strong style="color:var(--green)"><?= formatCurrency($biz['revenue']) ?></strong></td>
                        <td><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= number_format($biz['bill_count']) ?></span></td>
                        <td style="font-size:13px"><?= formatCurrency($biz['avg_bill']) ?></td>
                        <td style="min-width:150px">
                            <div class="bar-row">
                                <div class="bar-track"><div class="bar-fill" style="width:<?= max(2, $pct) ?>%"><?= number_format($pct, 1) ?>%</div></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
new Chart(monthlyRevenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyRevenue, 'month')) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_column($monthlyRevenue, 'revenue')) ?>,
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderColor: 'rgb(99,102,241)',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { callback: v => '₹' + v.toLocaleString(), font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
new Chart(paymentMethodCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($paymentMethods, 'payment_mode')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($paymentMethods, 'total')) ?>,
            backgroundColor: ['rgba(99,102,241,0.7)','rgba(16,185,129,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(139,92,246,0.7)'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
