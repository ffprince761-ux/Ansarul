<?php
/**
 * Owner Panel - All Products View
 * Stock overview across all businesses
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'All Products';

try {
    // All products with user info
    $stmt = $pdo->query("
        SELECT p.*, u.business_name, u.name as owner_name
        FROM products p LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.updated_at DESC LIMIT 300
    ");
    $allProducts = $stmt->fetchAll();

    // Stats
    $totalProducts = count($allProducts);
    $totalStock = 0;
    $totalValue = 0;
    $lowStockCount = 0;
    $outOfStockCount = 0;
    $categories = [];

    foreach ($allProducts as $p) {
        $totalStock += $p['stock'];
        $totalValue += $p['price'] * $p['stock'];
        if ($p['stock'] <= 0) $outOfStockCount++;
        elseif ($p['stock'] <= ($p['low_stock_threshold'] ?? 10)) $lowStockCount++;
        $cat = $p['category'] ?: 'Uncategorized';
        if (!isset($categories[$cat])) $categories[$cat] = ['count' => 0, 'value' => 0];
        $categories[$cat]['count']++;
        $categories[$cat]['value'] += $p['price'] * $p['stock'];
    }
    arsort($categories);

    // Products per business
    $byBusiness = [];
    foreach ($allProducts as $p) {
        $biz = $p['business_name'] ?: $p['owner_name'] ?: 'Unknown';
        if (!isset($byBusiness[$biz])) $byBusiness[$biz] = 0;
        $byBusiness[$biz]++;
    }
    arsort($byBusiness);

} catch(PDOException $e) {
    error_log("All products error: " . $e->getMessage());
    $allProducts = []; $totalProducts = $totalStock = $totalValue = $lowStockCount = $outOfStockCount = 0;
    $categories = []; $byBusiness = [];
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-ok{background:var(--green-light);color:var(--green)}.sb-low{background:var(--amber-light);color:var(--amber)}.sb-out{background:var(--red-light);color:var(--red)}
.cat-chip{display:inline-block;padding:4px 10px;margin:3px;border-radius:6px;font-size:12px;font-weight:500;background:#F3F4F6;color:var(--text-secondary);cursor:pointer;border:1px solid var(--border)}
.cat-chip:hover,.cat-chip.active{background:var(--primary-light);color:var(--primary);border-color:var(--primary)}
</style>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Products</div><div class="stat-value"><?= number_format($totalProducts) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Total Stock</div><div class="stat-value" style="color:var(--green)"><?= number_format($totalStock) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Stock Value</div><div class="stat-value" style="font-size:20px"><?= formatCurrency($totalValue) ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Low Stock</div><div class="stat-value" style="color:var(--amber)"><?= $lowStockCount ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Out of Stock</div><div class="stat-value" style="color:var(--red)"><?= $outOfStockCount ?></div></div></div>
    <div class="col-md-2 col-6"><div class="stat-card"><div class="stat-label">Categories</div><div class="stat-value"><?= count($categories) ?></div></div></div>
</div>

<!-- Category Chips -->
<?php if (!empty($categories)): ?>
<div class="table-container mb-4">
    <h6 style="font-weight:700;font-size:13px;margin-bottom:10px">Categories</h6>
    <span class="cat-chip active" onclick="filterCategory('')">All</span>
    <?php foreach (array_slice($categories, 0, 15, true) as $cat => $data): ?>
        <span class="cat-chip" onclick="filterCategory('<?= e($cat) ?>', this)"><?= e($cat) ?> <small style="color:var(--text-muted)">(<?= $data['count'] ?>)</small></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tabs-clean">
    <button class="tab-item active" onclick="switchTab('all',this)">All <span class="tab-count"><?= $totalProducts ?></span></button>
    <button class="tab-item" onclick="switchTab('low',this)">Low Stock <span class="tab-count"><?= $lowStockCount ?></span></button>
    <button class="tab-item" onclick="switchTab('out',this)">Out of Stock <span class="tab-count"><?= $outOfStockCount ?></span></button>
</div>

<!-- Search -->
<div class="mb-3 d-flex justify-content-between align-items-center">
    <small style="color:var(--text-muted);font-size:12px" id="resultCount"><?= $totalProducts ?> products</small>
    <input type="text" class="search-input" id="searchInput" placeholder="Search products...">
</div>

<!-- ALL PRODUCTS TAB -->
<div class="tp active" id="tab-all">
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="productsTable">
                <thead><tr><th>Product</th><th>Category</th><th>Business</th><th>Price</th><th>Stock</th><th>Level</th><th>Value</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                    <?php foreach ($allProducts as $p):
                        $stock = intval($p['stock']);
                        $threshold = intval($p['low_stock_threshold'] ?? 10);
                        $maxStock = max($stock, $threshold * 3, 50);
                        $stockPercent = min(100, round(($stock / $maxStock) * 100));
                        $stockColor = $stock <= 0 ? 'var(--red)' : ($stock <= $threshold ? 'var(--amber)' : 'var(--green)');
                        $statusClass = $stock <= 0 ? 'sb-out' : ($stock <= $threshold ? 'sb-low' : 'sb-ok');
                        $statusText = $stock <= 0 ? 'Out' : ($stock <= $threshold ? 'Low' : 'OK');
                        $value = $p['price'] * $stock;
                    ?>
                        <tr class="prod-row" data-category="<?= e($p['category'] ?: '') ?>" data-stock="<?= $stock ?>" data-threshold="<?= $threshold ?>">
                            <td><strong><?= e($p['name']) ?></strong></td>
                            <td><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= e($p['category'] ?: '-') ?></span></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= e($p['business_name'] ?: $p['owner_name'] ?: '-') ?></td>
                            <td><strong><?= formatCurrency($p['price']) ?></strong></td>
                            <td><strong style="color:<?= $stockColor ?>"><?= number_format($stock) ?></strong> <small style="color:var(--text-muted)"><?= e($p['unit'] ?: 'Nos') ?></small></td>
                            <td style="min-width:60px"><div class="progress-sm"><div class="fill" style="width:<?= $stockPercent ?>%;background:<?= $stockColor ?>"></div></div></td>
                            <td style="font-size:12px"><?= formatCurrency($value) ?></td>
                            <td><span class="sb <?= $statusClass ?>"><?= $statusText ?></span></td>
                            <td style="font-size:11px;color:var(--text-muted)"><?= date('M d', strtotime($p['updated_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- LOW STOCK TAB -->
<div class="tp" id="tab-low">
    <div class="table-container">
        <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Low Stock (<?= $lowStockCount ?>)</h6>
        <?php if ($lowStockCount == 0): ?>
            <div class="text-center py-4" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:24px"></i><p class="mt-2 mb-0" style="font-weight:600;font-size:13px">All stocks healthy</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Product</th><th>Business</th><th>Stock</th><th>Threshold</th><th>Price</th><th>Restock</th></tr></thead>
                    <tbody>
                        <?php foreach ($allProducts as $p):
                            $stock = intval($p['stock']);
                            $threshold = intval($p['low_stock_threshold'] ?? 10);
                            if ($stock > 0 && $stock <= $threshold):
                                $needed = $threshold - $stock;
                        ?>
                            <tr style="background:var(--amber-light)">
                                <td><strong><?= e($p['name']) ?></strong></td>
                                <td style="font-size:12px;color:var(--text-secondary)"><?= e($p['business_name'] ?: $p['owner_name'] ?: '-') ?></td>
                                <td><strong style="color:var(--amber)"><?= $stock ?> <?= e($p['unit'] ?: 'Nos') ?></strong></td>
                                <td style="color:var(--text-muted)"><?= $threshold ?></td>
                                <td><?= formatCurrency($p['price']) ?></td>
                                <td><strong style="color:var(--red)">+<?= $needed ?></strong></td>
                            </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- OUT OF STOCK TAB -->
<div class="tp" id="tab-out">
    <div class="table-container">
        <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Out of Stock (<?= $outOfStockCount ?>)</h6>
        <?php if ($outOfStockCount == 0): ?>
            <div class="text-center py-4" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:24px"></i><p class="mt-2 mb-0" style="font-weight:600;font-size:13px">No products out of stock</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Product</th><th>Category</th><th>Business</th><th>Price</th><th>Updated</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($allProducts as $p):
                            if (intval($p['stock']) <= 0):
                        ?>
                            <tr style="background:var(--red-light)">
                                <td><strong><?= e($p['name']) ?></strong></td>
                                <td><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= e($p['category'] ?: '-') ?></span></td>
                                <td style="font-size:12px;color:var(--text-secondary)"><?= e($p['business_name'] ?: $p['owner_name'] ?: '-') ?></td>
                                <td><?= formatCurrency($p['price']) ?></td>
                                <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($p['updated_at'])) ?></td>
                                <td><span class="sb sb-out">OUT OF STOCK</span></td>
                            </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<?php if (!empty($categories)): ?>
<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">By Category</h6>
            <canvas id="categoryChart" height="250"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">By Business</h6>
            <canvas id="businessChart" height="250"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

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
    document.querySelectorAll('.prod-row').forEach(row => {
        const show = row.textContent.toLowerCase().includes(v);
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });
    document.getElementById('resultCount').textContent = count + ' products';
});

function filterCategory(cat, el) {
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    else document.querySelector('.cat-chip').classList.add('active');
    document.querySelectorAll('.prod-row').forEach(row => {
        if (!cat) { row.style.display = ''; return; }
        row.style.display = row.dataset.category === cat ? '' : 'none';
    });
    document.querySelectorAll('.tp').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-all').classList.add('active');
    document.querySelector('.tab-item').classList.add('active');
}

<?php if (!empty($categories)): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_keys(array_slice($categories, 0, 10, true))) ?>, datasets: [{ data: <?= json_encode(array_values(array_map(fn($c) => $c['count'], array_slice($categories, 0, 10, true)))) ?>, backgroundColor: ['#4F46E5','#059669','#D97706','#DC2626','#7C3AED','#0891B2','#DB2777','#EA580C','#0D9488','#4338CA'], borderWidth: 0 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } } } }
});

new Chart(document.getElementById('businessChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_keys(array_slice($byBusiness, 0, 8, true))) ?>, datasets: [{ data: <?= json_encode(array_values(array_slice($byBusiness, 0, 8, true))) ?>, backgroundColor: '#4F46E5', borderRadius: 4, barThickness: 20 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks:{font:{size:11}} }, x: { ticks: { font: { size: 10 } } } } }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
