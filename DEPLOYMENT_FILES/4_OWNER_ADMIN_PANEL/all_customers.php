<?php
/**
 * Owner Panel - All Customers View
 * Customer overview across all businesses
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'All Customers';

try {
    // All customers with user info and bill stats
    $stmt = $pdo->query("
        SELECT 
            c.*,
            u.business_name, u.name as owner_name,
            COUNT(DISTINCT b.id) as bill_count,
            COALESCE(SUM(b.grand_total), 0) as total_spent,
            COALESCE(SUM(CASE WHEN b.due_status IN ('unpaid','partial') THEN b.grand_total - COALESCE(b.paid_amount,0) ELSE 0 END), 0) as due_amount,
            MAX(b.date) as last_bill_date
        FROM customers c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN bills b ON b.customer_id = c.id AND b.user_id = c.user_id
        GROUP BY c.id
        ORDER BY total_spent DESC
        LIMIT 300
    ");
    $allCustomers = $stmt->fetchAll();

    $totalCustomers = count($allCustomers);
    $totalSpent = 0;
    $totalDue = 0;
    $activeCustomers = 0;
    $byBusiness = [];

    foreach ($allCustomers as $c) {
        $totalSpent += $c['total_spent'];
        $totalDue += $c['due_amount'];
        if ($c['bill_count'] > 0) $activeCustomers++;
        $biz = $c['business_name'] ?: $c['owner_name'] ?: 'Unknown';
        if (!isset($byBusiness[$biz])) $byBusiness[$biz] = 0;
        $byBusiness[$biz]++;
    }
    arsort($byBusiness);

    // Top 10 by spending
    $topCustomers = array_slice($allCustomers, 0, 10);

    // Customers with due
    $dueCustomers = array_filter($allCustomers, fn($c) => $c['due_amount'] > 0);

} catch(PDOException $e) {
    error_log("All customers error: " . $e->getMessage());
    $allCustomers = []; $totalCustomers = $totalSpent = $totalDue = $activeCustomers = 0;
    $byBusiness = []; $topCustomers = []; $dueCustomers = [];
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-active{background:var(--green-light);color:var(--green)}.sb-inactive{background:#F3F4F6;color:var(--text-muted)}.sb-due{background:var(--red-light);color:var(--red)}
.cust-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px;transition:box-shadow .2s;cursor:pointer;text-decoration:none;color:inherit;display:block}
.cust-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.06);border-color:#D1D5DB}
.cust-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:17px;color:#fff;flex-shrink:0}
.cust-meta{font-size:11px;color:var(--text-muted);margin-top:2px}
.cust-stats{display:flex;gap:16px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border)}
.cust-stat-item{text-align:center;flex:1}
.cust-stat-val{font-size:14px;font-weight:700;color:var(--text-primary)}
.cust-stat-lbl{font-size:10px;color:var(--text-muted)}
</style>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Total Customers</div><div class="stat-value"><?= number_format($totalCustomers) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Active</div><div class="stat-value" style="color:var(--green)"><?= number_format($activeCustomers) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Total Spent</div><div class="stat-value" style="font-size:20px"><?= formatCurrency($totalSpent) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Total Due</div><div class="stat-value" style="color:var(--red);font-size:20px"><?= formatCurrency($totalDue) ?></div></div></div>
</div>

<!-- Tabs -->
<div class="tabs-clean">
    <button class="tab-item active" onclick="switchTab('all',this)">All <span class="tab-count"><?= $totalCustomers ?></span></button>
    <button class="tab-item" onclick="switchTab('top',this)">Top Spenders <span class="tab-count"><?= min(10, count($topCustomers)) ?></span></button>
    <button class="tab-item" onclick="switchTab('due',this)">Due <span class="tab-count"><?= count($dueCustomers) ?></span></button>
</div>

<!-- Search -->
<div class="mb-3 d-flex justify-content-between align-items-center">
    <small style="color:var(--text-muted);font-size:12px" id="resultCount"><?= $totalCustomers ?> customers</small>
    <input type="text" class="search-input" id="searchInput" placeholder="Search customers...">
</div>

<!-- ALL CUSTOMERS TAB -->
<div class="tp active" id="tab-all">
    <div class="row g-3">
        <?php foreach ($allCustomers as $c):
            $colors = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0891B2','#DB2777','#EA580C'];
            $color = $colors[$c['id'] % count($colors)];
            $initial = strtoupper(substr($c['name'], 0, 1));
            $hasDue = $c['due_amount'] > 0;
        ?>
            <div class="col-md-4 col-sm-6 cust-item">
                <a href="customer_detail.php?id=<?= $c['id'] ?>&user_id=<?= $c['user_id'] ?>" class="cust-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="cust-avatar" style="background:<?= $color ?>"><?= $initial ?></div>
                        <div style="min-width:0;flex:1">
                            <div class="d-flex align-items-center gap-2">
                                <strong style="font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($c['name']) ?></strong>
                                <?php if($hasDue):?><span class="sb sb-due">Due</span><?php elseif($c['bill_count']>0):?><span class="sb sb-active">Active</span><?php else:?><span class="sb sb-inactive">New</span><?php endif;?>
                            </div>
                            <div class="cust-meta"><?= e($c['mobile'] ?: 'No mobile') ?> &middot; <?= e($c['business_name'] ?: $c['owner_name'] ?: 'Unknown') ?></div>
                        </div>
                    </div>
                    <div class="cust-stats">
                        <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--blue)"><?= $c['bill_count'] ?></div><div class="cust-stat-lbl">Bills</div></div>
                        <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--green)"><?= formatCurrency($c['total_spent']) ?></div><div class="cust-stat-lbl">Spent</div></div>
                        <div class="cust-stat-item"><div class="cust-stat-val" style="color:<?= $hasDue ? 'var(--red)' : 'var(--text-muted)' ?>"><?= $hasDue ? formatCurrency($c['due_amount']) : '-' ?></div><div class="cust-stat-lbl">Due</div></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- TOP SPENDERS TAB -->
<div class="tp" id="tab-top">
    <div class="row g-3">
        <?php foreach ($topCustomers as $i => $c):
            $colors = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0891B2','#DB2777','#EA580C'];
            $color = $colors[$c['id'] % count($colors)];
            $initial = strtoupper(substr($c['name'], 0, 1));
            $avg = $c['bill_count'] > 0 ? $c['total_spent'] / $c['bill_count'] : 0;
        ?>
            <div class="col-md-4 col-sm-6">
                <a href="customer_detail.php?id=<?= $c['id'] ?>&user_id=<?= $c['user_id'] ?>" class="cust-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="cust-avatar" style="background:<?= $color ?>;position:relative">
                            <?= $initial ?>
                            <span style="position:absolute;top:-4px;right:-4px;background:var(--amber);color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;font-weight:700"><?= $i + 1 ?></span>
                        </div>
                        <div style="min-width:0;flex:1">
                            <strong style="font-size:14px"><?= e($c['name']) ?></strong>
                            <div class="cust-meta"><?= e($c['business_name'] ?: $c['owner_name'] ?: '-') ?> &middot; Avg: <?= formatCurrency($avg) ?></div>
                        </div>
                    </div>
                    <div class="cust-stats">
                        <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--blue)"><?= $c['bill_count'] ?></div><div class="cust-stat-lbl">Bills</div></div>
                        <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--green)"><?= formatCurrency($c['total_spent']) ?></div><div class="cust-stat-lbl">Total</div></div>
                        <div class="cust-stat-item"><div class="cust-stat-val"><?= $c['last_bill_date'] ? date('M d', strtotime($c['last_bill_date'])) : '-' ?></div><div class="cust-stat-lbl">Last Bill</div></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- DUE CUSTOMERS TAB -->
<div class="tp" id="tab-due">
    <?php if (empty($dueCustomers)): ?>
        <div class="text-center py-4" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:24px"></i><p class="mt-2 mb-0" style="font-weight:600;font-size:13px">No pending dues</p></div>
    <?php else: ?>
        <div class="mb-2" style="text-align:right"><small style="color:var(--text-muted)">Total Due: <strong style="color:var(--red)"><?= formatCurrency($totalDue) ?></strong></small></div>
        <div class="row g-3">
            <?php foreach ($dueCustomers as $c):
                $colors = ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0891B2','#DB2777','#EA580C'];
                $color = $colors[$c['id'] % count($colors)];
                $initial = strtoupper(substr($c['name'], 0, 1));
            ?>
                <div class="col-md-4 col-sm-6">
                    <a href="customer_detail.php?id=<?= $c['id'] ?>&user_id=<?= $c['user_id'] ?>" class="cust-card" style="border-left:3px solid var(--red)">
                        <div class="d-flex align-items-center gap-3">
                            <div class="cust-avatar" style="background:<?= $color ?>"><?= $initial ?></div>
                            <div style="min-width:0;flex:1">
                                <div class="d-flex align-items-center gap-2">
                                    <strong style="font-size:14px"><?= e($c['name']) ?></strong>
                                    <span class="sb sb-due">Due</span>
                                </div>
                                <div class="cust-meta"><?= e($c['mobile'] ?: 'No mobile') ?> &middot; <?= e($c['business_name'] ?: $c['owner_name'] ?: '-') ?></div>
                            </div>
                        </div>
                        <div class="cust-stats">
                            <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--blue)"><?= $c['bill_count'] ?></div><div class="cust-stat-lbl">Bills</div></div>
                            <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--green)"><?= formatCurrency($c['total_spent']) ?></div><div class="cust-stat-lbl">Spent</div></div>
                            <div class="cust-stat-item"><div class="cust-stat-val" style="color:var(--red)"><?= formatCurrency($c['due_amount']) ?></div><div class="cust-stat-lbl">Due</div></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tp').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    let count = 0;
    document.querySelectorAll('.cust-item').forEach(item => {
        const show = item.textContent.toLowerCase().includes(v);
        item.style.display = show ? '' : 'none';
        if (show) count++;
    });
    document.getElementById('resultCount').textContent = count + ' customers';
});
</script>

<?php include 'includes/footer.php'; ?>
