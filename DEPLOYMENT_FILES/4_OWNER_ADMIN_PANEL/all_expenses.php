<?php
/**
 * Owner Panel - All Expenses View
 * Advanced expense analytics with categories, charts, and time filters
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'All Expenses';

$period = $_GET['period'] ?? 'all';
$dateFilter = '';
switch($period) {
    case 'daily': $dateFilter = "AND DATE(e.date) = CURDATE()"; break;
    case 'monthly': $dateFilter = "AND YEAR(e.date) = YEAR(CURDATE()) AND MONTH(e.date) = MONTH(CURDATE())"; break;
    case 'yearly': $dateFilter = "AND YEAR(e.date) = YEAR(CURDATE())"; break;
    default: $dateFilter = '';
}

try {
    $stmt = $pdo->query("
        SELECT e.*, u.business_name, u.name as owner_name
        FROM expenses e LEFT JOIN users u ON e.user_id = u.id
        WHERE 1=1 $dateFilter
        ORDER BY e.date DESC LIMIT 300
    ");
    $expenses = $stmt->fetchAll();

    $totalExpenses = 0;
    $todayExpenses = 0;
    $monthExpenses = 0;
    $categories = [];
    $byBusiness = [];
    $dailyData = [];

    foreach ($expenses as $ex) {
        $totalExpenses += $ex['amount'];
        $cat = $ex['category'] ?: 'Other';
        if (!isset($categories[$cat])) $categories[$cat] = ['count' => 0, 'amount' => 0];
        $categories[$cat]['count']++;
        $categories[$cat]['amount'] += $ex['amount'];

        $biz = $ex['business_name'] ?: $ex['owner_name'] ?: 'Unknown';
        if (!isset($byBusiness[$biz])) $byBusiness[$biz] = 0;
        $byBusiness[$biz] += $ex['amount'];

        $day = date('Y-m-d', strtotime($ex['date']));
        if (!isset($dailyData[$day])) $dailyData[$day] = 0;
        $dailyData[$day] += $ex['amount'];

        if (date('Y-m-d', strtotime($ex['date'])) === date('Y-m-d')) $todayExpenses += $ex['amount'];
        if (date('Y-m', strtotime($ex['date'])) === date('Y-m')) $monthExpenses += $ex['amount'];
    }
    arsort($categories);
    arsort($byBusiness);

    $avgExpense = count($expenses) > 0 ? $totalExpenses / count($expenses) : 0;
    $topCategory = !empty($categories) ? array_key_first($categories) : '-';

    // Last 30 days trend
    $trendDays = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trendDays[$d] = $dailyData[$d] ?? 0;
    }

} catch(PDOException $e) {
    error_log("All expenses error: " . $e->getMessage());
    $expenses = []; $totalExpenses = $todayExpenses = $monthExpenses = $avgExpense = 0;
    $categories = []; $byBusiness = []; $trendDays = []; $topCategory = '-';
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.cat-bar{display:flex;align-items:center;margin-bottom:8px}
.cat-bar-fill{height:24px;border-radius:4px;display:flex;align-items:center;padding:0 8px;font-size:11px;font-weight:600;color:#fff;min-width:30px}
.cat-bar-label{width:110px;font-size:12px;font-weight:500;flex-shrink:0;padding-right:10px;text-align:right;color:var(--text-secondary)}
.cat-bar-amount{font-size:12px;font-weight:600;padding-left:10px;flex-shrink:0}
</style>

<!-- Period Filter -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="?period=daily" class="btn btn-sm btn-<?= $period=='daily'?'primary':'outline-secondary' ?>" style="border-radius:8px">Today</a>
    <a href="?period=monthly" class="btn btn-sm btn-<?= $period=='monthly'?'primary':'outline-secondary' ?>" style="border-radius:8px">This Month</a>
    <a href="?period=yearly" class="btn btn-sm btn-<?= $period=='yearly'?'primary':'outline-secondary' ?>" style="border-radius:8px">This Year</a>
    <a href="?period=all" class="btn btn-sm btn-<?= $period=='all'?'primary':'outline-secondary' ?>" style="border-radius:8px">All Time</a>
</div>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Total Expenses</div><div class="stat-value" style="color:var(--red);font-size:20px"><?= formatCurrency($totalExpenses) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Today</div><div class="stat-value" style="font-size:20px"><?= formatCurrency($todayExpenses) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">This Month</div><div class="stat-value" style="font-size:20px"><?= formatCurrency($monthExpenses) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Entries</div><div class="stat-value"><?= count($expenses) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Avg / Entry</div><div class="stat-value" style="font-size:20px"><?= formatCurrency($avgExpense) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Top Category</div><div class="stat-value" style="font-size:16px"><?= e($topCategory) ?></div></div></div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Expense Trend (30 Days)</h6>
            <canvas id="trendChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">By Category</h6>
            <canvas id="catChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Category Breakdown -->
<?php if (!empty($categories)): ?>
<div class="table-container mb-4">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Category Breakdown</h6>
    <?php
    $catColors = ['#DC2626','#D97706','#7C3AED','#2563EB','#059669','#0891B2','#DB2777','#EA580C','#0D9488','#4338CA'];
    $maxCatAmount = max(array_column($categories, 'amount'));
    $ci = 0;
    foreach ($categories as $cat => $data):
        $width = $maxCatAmount > 0 ? round(($data['amount'] / $maxCatAmount) * 100) : 0;
        $color = $catColors[$ci % count($catColors)];
        $ci++;
    ?>
        <div class="cat-bar">
            <div class="cat-bar-label"><?= e($cat) ?></div>
            <div style="flex:1"><div class="cat-bar-fill" style="width:<?= max($width,5) ?>%;background:<?= $color ?>"><?= $data['count'] ?></div></div>
            <div class="cat-bar-amount" style="color:<?= $color ?>"><?= formatCurrency($data['amount']) ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Search + Table -->
<div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 style="font-weight:700;font-size:14px;margin:0">All Entries (<?= count($expenses) ?>)</h6>
    <div class="d-flex gap-2 align-items-center">
        <button class="btn btn-sm btn-outline-secondary" onclick="exportExpCSV()" style="border-radius:8px;font-weight:600"><i class="fas fa-download"></i> Export</button>
        <input type="text" class="search-input" id="searchInput" placeholder="Search expenses...">
    </div>
</div>

<div class="table-container mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Business</th><th>Category</th><th>Description</th><th>Date</th><th>Amount</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr><td colspan="6" class="text-center" style="color:var(--text-muted);padding:24px">No expenses found</td></tr>
                <?php else: foreach ($expenses as $expense): ?>
                    <tr class="exp-row">
                        <td style="font-size:12px;color:var(--text-secondary)"><?= e($expense['business_name'] ?: $expense['owner_name'] ?: '-') ?></td>
                        <td><span class="sb" style="background:var(--amber-light);color:var(--amber)"><?= e($expense['category'] ?? 'Other') ?></span></td>
                        <td><?= e($expense['description'] ?? '-') ?></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($expense['date'])) ?></td>
                        <td><strong style="color:var(--red)"><?= formatCurrency($expense['amount']) ?></strong></td>
                        <td><button class="btn btn-sm btn-outline-primary" onclick="viewExpense(<?= $expense['id'] ?>)" style="border-radius:6px"><i class="fas fa-eye"></i></button></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- By Business -->
<?php if (!empty($byBusiness)): ?>
<div class="chart-container">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">By Business</h6>
    <canvas id="bizChart" height="150"></canvas>
</div>
<?php endif; ?>

<!-- Expense Detail Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" style="font-weight:700">Expense Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="expenseDetails">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('.exp-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});

function viewExpense(id) {
    const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
    modal.show();
    fetch('api/get_expense.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const e = data.expense;
                document.getElementById('expenseDetails').innerHTML = `
                    <div class="text-center mb-3"><h3 style="color:var(--red);font-weight:700">₹${parseFloat(e.amount).toFixed(2)}</h3></div>
                    <table class="table table-sm">
                        <tr><th width="120" style="color:var(--text-muted)">Category</th><td><span class="sb" style="background:var(--amber-light);color:var(--amber)">${e.category || 'Other'}</span></td></tr>
                        <tr><th style="color:var(--text-muted)">Date</th><td>${e.date}</td></tr>
                        <tr><th style="color:var(--text-muted)">Description</th><td>${e.description || '-'}</td></tr>
                        ${e.notes ? '<tr><th style="color:var(--text-muted)">Notes</th><td>' + e.notes + '</td></tr>' : ''}
                    </table>`;
            } else {
                document.getElementById('expenseDetails').innerHTML = '<div class="alert alert-danger">Failed to load</div>';
            }
        }).catch(() => { document.getElementById('expenseDetails').innerHTML = '<div class="alert alert-danger">Error</div>'; });
}

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: { labels: <?= json_encode(array_map(fn($d) => date('M d', strtotime($d)), array_keys($trendDays))) ?>, datasets: [{ data: <?= json_encode(array_values($trendDays)) ?>, borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.06)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { font:{size:10}, maxTicksLimit:8 } }, y: { beginAtZero: true, ticks:{font:{size:11}} } } }
});

new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys(array_slice($categories, 0, 8, true))) ?>, datasets: [{ data: <?= json_encode(array_values(array_map(fn($c) => $c['amount'], array_slice($categories, 0, 8, true)))) ?>, backgroundColor: ['#DC2626','#D97706','#7C3AED','#2563EB','#059669','#0891B2','#DB2777','#EA580C'], borderWidth: 0 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font:{size:11}, padding:12 } } } }
});

function exportExpCSV() {
    const data = <?= json_encode(array_map(fn($e) => [
        'Business' => $e['business_name'] ?: $e['owner_name'] ?: '-',
        'Category' => $e['category'] ?? 'Other',
        'Description' => $e['description'] ?? '-',
        'Date' => $e['date'],
        'Amount' => $e['amount']
    ], $expenses)) ?>;
    if (!data.length) return alert('No data');
    const h = Object.keys(data[0]);
    let csv = h.join(',') + '\n';
    data.forEach(r => { csv += h.map(k => '"' + String(r[k]).replace(/"/g,'""') + '"').join(',') + '\n'; });
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'expenses_export_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

<?php if (!empty($byBusiness)): ?>
new Chart(document.getElementById('bizChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_keys(array_slice($byBusiness, 0, 8, true))) ?>, datasets: [{ data: <?= json_encode(array_values(array_slice($byBusiness, 0, 8, true))) ?>, backgroundColor: '#4F46E5', borderRadius: 4, barThickness: 20 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks:{font:{size:11}} }, x:{ticks:{font:{size:10}}} } }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
