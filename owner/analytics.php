<?php
/**
 * Owner Panel - Advanced Analytics
 */
require_once 'config/db.php';
require_once 'config/functions.php';

requireOwnerLogin();

$pageTitle = 'Analytics';

// Get analytics data
try {
    // User engagement metrics
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT id) as total_users,
            COUNT(DISTINCT CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN id END) as active_7d,
            COUNT(DISTINCT CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN id END) as active_30d
        FROM users
    ");
    $engagement = $stmt->fetch() ?: ['total_users' => 0, 'active_7d' => 0, 'active_30d' => 0];
    
    // Feature usage statistics
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM bills) as total_bills,
            (SELECT COUNT(*) FROM products) as total_products,
            (SELECT COUNT(*) FROM customers) as total_customers,
            (SELECT COUNT(*) FROM expenses) as total_expenses,
            (SELECT COUNT(*) FROM stock_adjustments) as total_stock_adjustments
    ");
    $featureUsage = $stmt->fetch() ?: [
        'total_bills' => 0,
        'total_products' => 0,
        'total_customers' => 0,
        'total_expenses' => 0,
        'total_stock_adjustments' => 0
    ];
    
    // Daily activity (last 30 days)
    $stmt = $pdo->query("
        SELECT 
            DATE(date) as activity_date,
            COUNT(*) as bill_count,
            COUNT(DISTINCT user_id) as active_users
        FROM bills
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(date)
        ORDER BY activity_date ASC
    ");
    $dailyActivity = $stmt->fetchAll() ?: [];
    
    // User retention (cohort analysis)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as cohort_month,
            COUNT(*) as user_count,
            COUNT(CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as retained_users
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY cohort_month ASC
    ");
    $retention = $stmt->fetchAll() ?: [];
    
    // Product categories distribution
    $stmt = $pdo->query("
        SELECT 
            category,
            COUNT(*) as product_count
        FROM products
        WHERE category IS NOT NULL AND category != ''
        GROUP BY category
        ORDER BY product_count DESC
        LIMIT 10
    ");
    $categories = $stmt->fetchAll() ?: [];
    
    // Average metrics per user
    $stmt = $pdo->query("
        SELECT 
            AVG(product_count) as avg_products,
            AVG(customer_count) as avg_customers,
            AVG(bill_count) as avg_bills,
            AVG(revenue) as avg_revenue
        FROM (
            SELECT 
                u.id,
                COUNT(DISTINCT p.id) as product_count,
                COUNT(DISTINCT c.id) as customer_count,
                COUNT(DISTINCT b.id) as bill_count,
                COALESCE(SUM(b.grand_total), 0) as revenue
            FROM users u
            LEFT JOIN products p ON u.id = p.user_id
            LEFT JOIN customers c ON u.id = c.user_id
            LEFT JOIN bills b ON u.id = b.user_id
            GROUP BY u.id
        ) as user_stats
    ");
    $avgMetrics = $stmt->fetch() ?: [
        'avg_products' => 0,
        'avg_customers' => 0,
        'avg_bills' => 0,
        'avg_revenue' => 0
    ];
    
} catch(PDOException $e) {
    error_log("Analytics page error: " . $e->getMessage());
    $engagement = ['total_users' => 0, 'active_7d' => 0, 'active_30d' => 0];
    $featureUsage = ['total_bills' => 0, 'total_products' => 0, 'total_customers' => 0, 'total_expenses' => 0, 'total_stock_adjustments' => 0];
    $avgMetrics = ['avg_products' => 0, 'avg_customers' => 0, 'avg_bills' => 0, 'avg_revenue' => 0];
    $dailyActivity = $retention = $categories = [];
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
</style>

<?php
$dau_rate = $engagement['total_users'] > 0 ? round(($engagement['active_7d'] / $engagement['total_users']) * 100, 1) : 0;
$mau_rate = $engagement['total_users'] > 0 ? round(($engagement['active_30d'] / $engagement['total_users']) * 100, 1) : 0;
?>

<!-- Engagement Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= formatNumber($engagement['total_users'] ?? 0) ?></div>
            <small style="color:var(--text-muted)">Registered businesses</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">7-Day Active</div>
            <div class="stat-value" style="color:var(--green)"><?= formatNumber($engagement['active_7d'] ?? 0) ?></div>
            <small style="color:var(--green)"><?= $dau_rate ?>% of total</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">30-Day Active</div>
            <div class="stat-value" style="color:var(--blue)"><?= formatNumber($engagement['active_30d'] ?? 0) ?></div>
            <small style="color:var(--blue)"><?= $mau_rate ?>% of total</small>
        </div>
    </div>
</div>

<!-- Feature Usage -->
<div class="table-container mb-4">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Feature Usage</h6>
    <canvas id="featureUsageChart" height="80"></canvas>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Daily Activity (30 Days)</h6>
            <canvas id="dailyActivityChart" height="80"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Product Categories</h6>
            <canvas id="categoriesChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Average Metrics & Retention -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Avg Metrics Per User</h6>
            <table class="table mb-0">
                <tbody>
                    <tr><td>Products</td><td class="text-end"><?= number_format($avgMetrics['avg_products'] ?? 0, 1) ?></td></tr>
                    <tr><td>Customers</td><td class="text-end"><?= number_format($avgMetrics['avg_customers'] ?? 0, 1) ?></td></tr>
                    <tr><td>Bills</td><td class="text-end"><?= number_format($avgMetrics['avg_bills'] ?? 0, 1) ?></td></tr>
                    <tr><td>Revenue</td><td class="text-end"><strong style="color:var(--green)"><?= formatCurrency($avgMetrics['avg_revenue'] ?? 0) ?></strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">User Retention by Cohort</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Month</th><th>New</th><th>Retained</th><th>Rate</th></tr></thead>
                    <tbody>
                        <?php if (empty($retention)): ?>
                            <tr><td colspan="4" class="text-center" style="color:var(--text-muted);padding:20px">No data</td></tr>
                        <?php else: foreach ($retention as $cohort):
                            $rate = $cohort['user_count'] > 0 ? round(($cohort['retained_users'] / $cohort['user_count']) * 100, 1) : 0;
                            $rColor = $rate >= 50 ? 'var(--green)' : ($rate >= 30 ? 'var(--amber)' : 'var(--red)');
                            $rBg = $rate >= 50 ? 'var(--green-light)' : ($rate >= 30 ? 'var(--amber-light)' : 'var(--red-light)');
                        ?>
                            <tr>
                                <td><?= e($cohort['cohort_month']) ?></td>
                                <td><?= number_format($cohort['user_count']) ?></td>
                                <td><?= number_format($cohort['retained_users']) ?></td>
                                <td><span class="sb" style="background:<?=$rBg?>;color:<?=$rColor?>"><?= $rate ?>%</span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('featureUsageChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Bills', 'Products', 'Customers', 'Expenses', 'Stock Adj.'],
        datasets: [{
            label: 'Count',
            data: [<?= $featureUsage['total_bills'] ?? 0 ?>,<?= $featureUsage['total_products'] ?? 0 ?>,<?= $featureUsage['total_customers'] ?? 0 ?>,<?= $featureUsage['total_expenses'] ?? 0 ?>,<?= $featureUsage['total_stock_adjustments'] ?? 0 ?>],
            backgroundColor: ['rgba(99,102,241,0.15)','rgba(16,185,129,0.15)','rgba(59,130,246,0.15)','rgba(239,68,68,0.15)','rgba(139,92,246,0.15)'],
            borderColor: ['rgb(99,102,241)','rgb(16,185,129)','rgb(59,130,246)','rgb(239,68,68)','rgb(139,92,246)'],
            borderWidth: 1, borderRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 } } }, x: { grid: { display: false }, ticks: { font: { size: 11 } } } }
    }
});

new Chart(document.getElementById('dailyActivityChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($dailyActivity, 'activity_date')) ?>,
        datasets: [{
            label: 'Bills', data: <?= json_encode(array_column($dailyActivity, 'bill_count')) ?>,
            borderColor: 'rgb(99,102,241)', backgroundColor: 'rgba(99,102,241,0.08)', tension: 0.4, fill: true, borderWidth: 2
        }, {
            label: 'Active Users', data: <?= json_encode(array_column($dailyActivity, 'active_users')) ?>,
            borderColor: 'rgb(16,185,129)', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.4, fill: true, borderWidth: 2
        }]
    },
    options: { responsive: true, maintainAspectRatio: true,
        scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 } } }, x: { grid: { display: false }, ticks: { font: { size: 11 } } } },
        plugins: { legend: { labels: { font: { size: 11 }, padding: 12 } } }
    }
});

new Chart(document.getElementById('categoriesChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($categories, 'category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($categories, 'product_count')) ?>,
            backgroundColor: ['rgba(99,102,241,0.7)','rgba(16,185,129,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(139,92,246,0.7)','rgba(59,130,246,0.7)','rgba(236,72,153,0.7)','rgba(234,179,8,0.7)','rgba(20,184,166,0.7)','rgba(244,63,94,0.7)'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } } } }
});
</script>

<?php include 'includes/footer.php'; ?>
