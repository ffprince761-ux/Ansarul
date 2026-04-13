<?php
/**
 * Owner Panel - Customer Detail View
 * Shows customer profile across ALL stores they've purchased from
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();

$custId = $_GET['id'] ?? 0;
$pageTitle = 'Customer Detail';

try {
    // Get primary customer record
    $stmt = $pdo->prepare("SELECT c.*, u.business_name, u.name as owner_name, u.email as owner_email, u.mobile as owner_mobile
        FROM customers c LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$custId]);
    $cust = $stmt->fetch();
    if (!$cust) { header('Location: all_customers.php'); exit(); }

    // Find same customer across ALL stores (match by mobile if available, else by name)
    $matchedIds = [$custId];
    if (!empty($cust['mobile'])) {
        $stmt = $pdo->prepare("SELECT c.id, c.user_id, u.business_name, u.name as owner_name, u.email as owner_email, u.mobile as owner_mobile
            FROM customers c LEFT JOIN users u ON c.user_id = u.id WHERE c.mobile = ? AND c.id != ?");
        $stmt->execute([$cust['mobile'], $custId]);
        $otherRecords = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT c.id, c.user_id, u.business_name, u.name as owner_name, u.email as owner_email, u.mobile as owner_mobile
            FROM customers c LEFT JOIN users u ON c.user_id = u.id WHERE c.name = ? AND c.id != ?");
        $stmt->execute([$cust['name'], $custId]);
        $otherRecords = $stmt->fetchAll();
    }
    foreach ($otherRecords as $r) $matchedIds[] = $r['id'];
    $allCustIds = array_unique($matchedIds);
    $inPlaceholders = implode(',', array_fill(0, count($allCustIds), '?'));

    // Per-store breakdown
    $stmt = $pdo->prepare("SELECT u.id as user_id, u.business_name, u.name as owner_name,
            COUNT(DISTINCT b.id) as bill_count, COALESCE(SUM(b.grand_total),0) as total_spent,
            COALESCE(SUM(CASE WHEN b.due_status IN ('unpaid','partial','due') THEN b.grand_total - COALESCE(b.paid_amount,0) ELSE 0 END),0) as due_amount,
            MAX(b.date) as last_bill, MIN(b.date) as first_bill
        FROM bills b
        JOIN users u ON b.user_id = u.id
        WHERE b.customer_id IN ($inPlaceholders)
        GROUP BY u.id ORDER BY total_spent DESC");
    $stmt->execute($allCustIds);
    $storeBreakdown = $stmt->fetchAll();

    // Overall bill summary across all stores
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as total,
        COALESCE(AVG(grand_total),0) as avg_bill, MAX(date) as last_date, MIN(date) as first_date
        FROM bills WHERE customer_id IN ($inPlaceholders)");
    $stmt->execute($allCustIds);
    $billSummary = $stmt->fetch();

    // Due amount across all stores
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total - COALESCE(paid_amount,0)),0) as due
        FROM bills WHERE customer_id IN ($inPlaceholders) AND due_status IN ('unpaid','partial','due')");
    $stmt->execute($allCustIds);
    $totalDue = $stmt->fetch()['due'];

    // All bills across all stores
    $stmt = $pdo->prepare("SELECT b.*, u.business_name FROM bills b
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.customer_id IN ($inPlaceholders) ORDER BY b.date DESC LIMIT 100");
    $stmt->execute($allCustIds);
    $bills = $stmt->fetchAll();

    // Due bills
    $dueBills = array_filter($bills, fn($b) => in_array($b['due_status'] ?? '', ['unpaid','partial','due']));

    // Payment method breakdown across all stores
    $stmt = $pdo->prepare("SELECT payment_mode, COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as total
        FROM bills WHERE customer_id IN ($inPlaceholders) GROUP BY payment_mode ORDER BY total DESC");
    $stmt->execute($allCustIds);
    $paymentModes = $stmt->fetchAll();

    // Monthly spending trend (last 6 months)
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as total
        FROM bills WHERE customer_id IN ($inPlaceholders) AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY month ASC");
    $stmt->execute($allCustIds);
    $monthlyTrend = $stmt->fetchAll();

    // Most purchased products across all stores
    $topProducts = [];
    foreach ($bills as $b) {
        $items = json_decode($b['items'] ?? '[]', true) ?: [];
        foreach ($items as $item) {
            $pName = $item['name'] ?? $item['product_name'] ?? 'Unknown';
            if (!isset($topProducts[$pName])) $topProducts[$pName] = ['qty' => 0, 'total' => 0];
            $topProducts[$pName]['qty'] += ($item['quantity'] ?? $item['qty'] ?? 1);
            $topProducts[$pName]['total'] += ($item['total'] ?? ($item['price'] ?? 0) * ($item['quantity'] ?? $item['qty'] ?? 1));
        }
    }
    arsort($topProducts);
    $topProducts = array_slice($topProducts, 0, 10, true);

    // Days since first bill
    $daysSinceFirst = $billSummary['first_date'] ? max(1, floor((time() - strtotime($billSummary['first_date'])) / 86400)) : 0;

} catch(PDOException $e) {
    error_log("Customer detail error: " . $e->getMessage());
    header('Location: all_customers.php');
    exit();
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-on{background:var(--green-light);color:var(--green)}.sb-off{background:var(--red-light);color:var(--red)}.sb-warn{background:var(--amber-light);color:var(--amber)}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.info-row:last-child{border:0}.info-label{color:var(--text-muted);font-weight:500}.info-val{color:var(--text-primary);font-weight:500}
.profile-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:26px;color:#fff;flex-shrink:0}
.tab-panel{display:none}.tab-panel.active{display:block}
</style>

<?php
$colors = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0891B2','#DB2777','#EA580C'];
$color = $colors[$cust['id'] % count($colors)];
$initial = strtoupper(substr($cust['name'], 0, 1));
$hasDue = $totalDue > 0;
?>

<!-- Header -->
<div class="card mb-4" style="border:1px solid var(--border);border-radius:12px">
    <div class="card-body" style="padding:20px">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar" style="background:<?= $color ?>"><?= $initial ?></div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="mb-0" style="font-weight:700"><?= e($cust['name']) ?></h5>
                        <?php if($hasDue):?><span class="sb sb-off">Due</span><?php elseif($billSummary['cnt']>0):?><span class="sb sb-on">Active</span><?php else:?><span class="sb" style="background:#F3F4F6;color:var(--text-muted)">New</span><?php endif;?>
                        <?php if(count($storeBreakdown) > 1):?><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= count($storeBreakdown) ?> Stores</span><?php endif;?>
                    </div>
                    <p class="mb-0" style="font-size:13px;color:var(--text-secondary)">
                        <i class="fas fa-store" style="font-size:11px"></i>
                        <?= e(implode(', ', array_map(fn($s) => $s['business_name'] ?: $s['owner_name'], $storeBreakdown)) ?: 'Unknown Store') ?>
                    </p>
                    <small style="color:var(--text-muted)">
                        <?= e($cust['mobile'] ?: 'No mobile') ?>
                        <?php if($cust['email']):?> &middot; <?= e($cust['email']) ?><?php endif;?>
                    </small>
                </div>
            </div>
            <a href="all_customers.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Total Spent</div><div class="stat-value" style="color:var(--green);font-size:20px"><?= formatCurrency($billSummary['total']) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Total Bills</div><div class="stat-value"><?= $billSummary['cnt'] ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Avg Bill</div><div class="stat-value" style="font-size:18px"><?= formatCurrency($billSummary['avg_bill']) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Due Amount</div><div class="stat-value" style="color:<?= $hasDue ? 'var(--red)' : 'var(--green)' ?>;font-size:20px"><?= $hasDue ? formatCurrency($totalDue) : '₹0' ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Due Bills</div><div class="stat-value" style="color:var(--red)"><?= count($dueBills) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Customer For</div><div class="stat-value" style="font-size:18px"><?= $daysSinceFirst ?> days</div></div></div>
</div>

<!-- Row 2: Customer Info + Top Products -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Customer Info</h6>
            <div class="info-row"><span class="info-label">Name</span><span class="info-val"><?= e($cust['name']) ?></span></div>
            <div class="info-row"><span class="info-label">Mobile</span><span class="info-val"><?= e($cust['mobile'] ?: 'N/A') ?></span></div>
            <div class="info-row"><span class="info-label">Email</span><span class="info-val"><?= e($cust['email'] ?: 'N/A') ?></span></div>
            <div class="info-row"><span class="info-label">Address</span><span class="info-val"><?= e($cust['address'] ?: 'N/A') ?></span></div>
            <div class="info-row"><span class="info-label">Added</span><span class="info-val"><?= date('M d, Y', strtotime($cust['created_at'])) ?></span></div>
            <div class="info-row"><span class="info-label">First Bill</span><span class="info-val"><?= $billSummary['first_date'] ? date('M d, Y', strtotime($billSummary['first_date'])) : 'N/A' ?></span></div>
            <div class="info-row"><span class="info-label">Last Bill</span><span class="info-val"><?= $billSummary['last_date'] ? date('M d, Y', strtotime($billSummary['last_date'])) : 'N/A' ?></span></div>
            <?php if (!empty($paymentModes)): ?>
                <h6 style="font-weight:600;font-size:12px;margin:12px 0 8px;color:var(--text-muted)">Payment Methods</h6>
                <?php foreach ($paymentModes as $pm): ?>
                    <div class="info-row">
                        <span class="info-label"><?= e($pm['payment_mode']) ?></span>
                        <span class="info-val"><?= $pm['cnt'] ?> bills &middot; <?= formatCurrency($pm['total']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 style="font-weight:700;font-size:14px;margin:0">Stores (<?= count($storeBreakdown) ?>)</h6>
            </div>
            <?php if (empty($storeBreakdown)): ?>
                <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0">No store data</p>
            <?php else: foreach ($storeBreakdown as $si => $store):
                $storeColors = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0891B2','#DB2777','#EA580C'];
                $sColor = $storeColors[$store['user_id'] % count($storeColors)];
                $sInitial = strtoupper(substr($store['business_name'] ?: $store['owner_name'], 0, 1));
            ?>
                <div style="padding:10px;border:1px solid var(--border);border-radius:8px;<?= $si > 0 ? 'margin-top:8px' : '' ?>">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div style="width:30px;height:30px;border-radius:50%;background:<?= $sColor ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0"><?= $sInitial ?></div>
                        <div style="min-width:0;flex:1">
                            <a href="user_detail.php?id=<?= $store['user_id'] ?>" style="font-weight:700;font-size:13px;color:var(--text-primary);text-decoration:none"><?= e($store['business_name'] ?: $store['owner_name']) ?></a>
                            <div style="font-size:11px;color:var(--text-muted)">Owner: <?= e($store['owner_name']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex gap-3" style="font-size:12px">
                        <div><span style="color:var(--text-muted)">Bills:</span> <strong style="color:var(--blue)"><?= $store['bill_count'] ?></strong></div>
                        <div><span style="color:var(--text-muted)">Spent:</span> <strong style="color:var(--green)"><?= formatCurrency($store['total_spent']) ?></strong></div>
                        <?php if ($store['due_amount'] > 0): ?>
                            <div><span style="color:var(--text-muted)">Due:</span> <strong style="color:var(--red)"><?= formatCurrency($store['due_amount']) ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:4px">
                        <?= $store['first_bill'] ? date('M d, Y', strtotime($store['first_bill'])) : '' ?> — <?= $store['last_bill'] ? date('M d, Y', strtotime($store['last_bill'])) : '' ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Top Purchased Items</h6>
            <?php if (empty($topProducts)): ?>
                <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0">No item data</p>
            <?php else: $rank = 1; foreach ($topProducts as $pName => $pData): ?>
                <div class="d-flex justify-content-between align-items-center" style="padding:6px 0;border-bottom:1px solid var(--border);font-size:13px">
                    <div>
                        <span style="color:var(--text-muted);font-size:11px;margin-right:6px"><?= $rank++ ?></span>
                        <strong><?= e($pName) ?></strong>
                    </div>
                    <div style="text-align:right">
                        <span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= $pData['qty'] ?>x</span>
                        <strong style="color:var(--green);font-size:12px;margin-left:6px"><?= formatCurrency($pData['total']) ?></strong>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($monthlyTrend) && count($monthlyTrend) > 1): ?>
<div class="table-container mb-4">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Monthly Spending Trend</h6>
    <canvas id="trendChart" height="60"></canvas>
</div>
<?php endif; ?>

<!-- Bills Section -->
<div class="table-container">
    <div class="tabs-clean mb-3">
        <button class="tab-item active" onclick="showTab('all')">All Bills (<?= count($bills) ?>)</button>
        <button class="tab-item" onclick="showTab('due')">Due Bills (<?= count($dueBills) ?>)</button>
    </div>

    <div class="tab-panel active" id="tab-all">
        <div class="table-responsive" style="max-height:400px;overflow-y:auto">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Invoice</th><th>Store</th><th>Date</th><th>Items</th><th>Amount</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr><td colspan="7" class="text-center" style="color:var(--text-muted);padding:20px">No bills found</td></tr>
                    <?php else: foreach ($bills as $b):
                        $items = json_decode($b['items'] ?? '[]', true) ?: [];
                        $ds = $b['due_status'] ?? 'paid';
                        $dsClass = $ds === 'paid' ? 'sb-on' : ($ds === 'partial' ? 'sb-warn' : 'sb-off');
                    ?>
                        <tr>
                            <td><strong><?= e($b['invoice_number'] ?? '#'.$b['id']) ?></strong></td>
                            <td style="font-size:11px;color:var(--text-secondary)"><?= e($b['business_name'] ?: '-') ?></td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($b['date'])) ?></td>
                            <td style="font-size:12px">
                                <?php $itemNames = array_map(fn($i) => ($i['name'] ?? $i['product_name'] ?? '?') . ' x' . ($i['quantity'] ?? $i['qty'] ?? 1), array_slice($items, 0, 3));
                                echo e(implode(', ', $itemNames));
                                if (count($items) > 3) echo ' +' . (count($items) - 3) . ' more'; ?>
                            </td>
                            <td><strong><?= formatCurrency($b['grand_total']) ?></strong></td>
                            <td><span class="sb <?= $b['payment_mode'] === 'Due' ? 'sb-warn' : 'sb-on' ?>"><?= e($b['payment_mode']) ?></span></td>
                            <td>
                                <span class="sb <?= $dsClass ?>"><?= ucfirst($ds) ?></span>
                                <?php if ($ds !== 'paid' && isset($b['paid_amount'])): ?>
                                    <small style="color:var(--text-muted);display:block;font-size:10px">Paid: ₹<?= number_format($b['paid_amount'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-panel" id="tab-due">
        <?php if (empty($dueBills)): ?>
            <div class="text-center py-4" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:20px"></i><p class="mt-2 mb-0" style="font-size:13px;font-weight:600">No pending dues</p></div>
        <?php else: ?>
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Store</th><th>Date</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($dueBills as $b):
                            $remaining = $b['grand_total'] - ($b['paid_amount'] ?? 0);
                        ?>
                            <tr style="background:var(--red-light)">
                                <td><strong><?= e($b['invoice_number'] ?? '#'.$b['id']) ?></strong></td>
                                <td style="font-size:11px;color:var(--text-secondary)"><?= e($b['business_name'] ?: '-') ?></td>
                                <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($b['date'])) ?></td>
                                <td><?= formatCurrency($b['grand_total']) ?></td>
                                <td style="color:var(--green)">₹<?= number_format($b['paid_amount'] ?? 0, 2) ?></td>
                                <td><strong style="color:var(--red)">₹<?= number_format($remaining, 2) ?></strong></td>
                                <td><span class="sb <?= ($b['due_status'] ?? '') === 'partial' ? 'sb-warn' : 'sb-off' ?>"><?= ucfirst($b['due_status'] ?? 'due') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.closest('.tab-item').classList.add('active');
}

<?php if (!empty($monthlyTrend) && count($monthlyTrend) > 1): ?>
new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthlyTrend, 'month')) ?>,
        datasets: [{
            label: 'Spending',
            data: <?= json_encode(array_column($monthlyTrend, 'total')) ?>,
            borderColor: 'rgb(99,102,241)', backgroundColor: 'rgba(99,102,241,0.08)',
            tension: 0.4, fill: true, borderWidth: 2, pointRadius: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 }, callback: v => '₹' + v.toLocaleString() } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
