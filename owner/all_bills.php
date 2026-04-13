<?php
/**
 * Owner Panel - All Bills View
 * Due Bills + Paid Bills sections with payment history
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'All Bills';

try {
    // All bills with user info
    $stmt = $pdo->query("
        SELECT b.*, u.business_name, u.name as owner_name
        FROM bills b LEFT JOIN users u ON b.user_id = u.id
        ORDER BY b.date DESC LIMIT 200
    ");
    $allBills = $stmt->fetchAll();

    // Separate due and paid
    $dueBills = [];
    $paidBills = [];
    $totalRevenue = 0;
    $totalDueAmount = 0;
    $totalPaidAmount = 0;

    foreach ($allBills as $b) {
        $totalRevenue += $b['grand_total'];
        $ds = $b['due_status'] ?? 'paid';
        if ($ds === 'unpaid' || $ds === 'partial' || $ds === 'due') {
            $dueBills[] = $b;
            $remaining = $b['grand_total'] - ($b['paid_amount'] ?? 0);
            $totalDueAmount += $remaining;
        } else {
            $paidBills[] = $b;
            $totalPaidAmount += $b['grand_total'];
        }
    }

    // Get all payment history for due/partial bills
    $paymentHistory = [];
    try {
        $stmt = $pdo->query("SELECT * FROM udhari_payments ORDER BY payment_date DESC, created_at DESC");
        $payments = $stmt->fetchAll();
        foreach ($payments as $p) {
            $paymentHistory[$p['bill_id']][] = $p;
        }
    } catch(Exception $e) {}

    // Get paid-via-due bills (were Due, now paid) - for showing their payment journey
    $paidDueBills = array_filter($paidBills, fn($b) => ($b['payment_mode'] ?? '') === 'Due');

} catch(PDOException $e) {
    error_log("All bills error: " . $e->getMessage());
    $allBills = []; $dueBills = []; $paidBills = []; $paymentHistory = [];
    $totalRevenue = 0; $totalDueAmount = 0; $totalPaidAmount = 0; $paidDueBills = [];
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-paid{background:var(--green-light);color:var(--green)}.sb-due{background:var(--red-light);color:var(--red)}.sb-partial{background:var(--amber-light);color:var(--amber)}
.pay-hist{margin-top:8px;padding:10px 14px;background:var(--green-light);border-radius:8px;border:1px solid #D1FAE5}
.pay-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px;border-bottom:1px solid var(--border)}
.pay-row:last-child{border:0}
</style>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Bills</div>
            <div class="stat-value"><?= count($allBills) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Paid Amount</div>
            <div class="stat-value" style="color:var(--green)"><?= formatCurrency($totalPaidAmount) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Pending Due</div>
            <div class="stat-value" style="color:var(--red)"><?= formatCurrency($totalDueAmount) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs-clean">
    <button class="tab-item active" onclick="switchTab('due',this)">Due Bills <span class="tab-count"><?= count($dueBills) ?></span></button>
    <button class="tab-item" onclick="switchTab('paid',this)">Paid Bills <span class="tab-count"><?= count($paidBills) ?></span></button>
    <button class="tab-item" onclick="switchTab('all',this)">All Bills <span class="tab-count"><?= count($allBills) ?></span></button>
</div>

<!-- Search -->
<div class="mb-3 d-flex justify-content-between align-items-center">
    <button class="btn btn-sm btn-outline-secondary" onclick="exportCSV()" style="border-radius:8px;font-weight:600"><i class="fas fa-download"></i> Export CSV</button>
    <input type="text" class="search-input" id="searchInput" placeholder="Search bills...">
</div>

<!-- DUE BILLS TAB -->
<div class="tp active" id="tab-due">
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 style="font-weight:700;font-size:14px;margin:0">Due Bills</h6>
            <span style="font-size:12px;color:var(--text-muted)">Pending: <strong style="color:var(--red)"><?= formatCurrency($totalDueAmount) ?></strong></span>
        </div>
        <?php if (empty($dueBills)): ?>
            <div class="text-center py-4" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:24px"></i><p class="mt-2 mb-0" style="font-weight:600;font-size:13px">All cleared</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Business</th><th>Customer</th><th>Date</th><th>Total</th><th>Paid</th><th>Remaining</th><th>Progress</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($dueBills as $b):
                            $paid = floatval($b['paid_amount'] ?? 0);
                            $total = floatval($b['grand_total']);
                            $remaining = $total - $paid;
                            $percent = $total > 0 ? round(($paid / $total) * 100) : 0;
                            $ds = $b['due_status'] ?? 'unpaid';
                            $isOverdue = $b['due_date'] && strtotime($b['due_date']) < time();
                        ?>
                            <tr class="bill-row" style="<?= $isOverdue ? 'background:var(--red-light)' : '' ?>">
                                <td><strong><?= e($b['invoice_number'] ?: '#'.$b['id']) ?></strong></td>
                                <td style="font-size:12px;color:var(--text-secondary)"><?= e($b['business_name'] ?: $b['owner_name'] ?: '-') ?></td>
                                <td><strong><?= e($b['customer_name']) ?></strong><br><small style="color:var(--text-muted)"><?= e($b['customer_mobile'] ?? '') ?></small></td>
                                <td style="font-size:12px"><?= date('M d, Y', strtotime($b['date'])) ?></td>
                                <td><strong><?= formatCurrency($total) ?></strong></td>
                                <td style="color:var(--green)"><?= formatCurrency($paid) ?></td>
                                <td style="color:var(--red);font-weight:600"><?= formatCurrency($remaining) ?></td>
                                <td style="min-width:80px">
                                    <small style="font-size:11px;color:var(--text-muted)"><?= $percent ?>%</small>
                                    <div class="progress-sm"><div class="fill" style="width:<?=$percent?>%;background:<?=$percent>60?'var(--green)':($percent>30?'var(--amber)':'var(--red)')?>"></div></div>
                                </td>
                                <td>
                                    <?php if ($b['due_date']): ?>
                                        <span style="color:<?=$isOverdue?'var(--red)':'var(--text-muted)'?>;font-size:12px;font-weight:<?=$isOverdue?'600':'400'?>">
                                            <?= date('M d', strtotime($b['due_date'])) ?>
                                            <?= $isOverdue ? '<br><span class="sb sb-due" style="font-size:9px;padding:1px 6px">OVERDUE</span>' : '' ?>
                                        </span>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td><span class="sb <?=$ds=='partial'?'sb-partial':'sb-due'?>"><?= ucfirst($ds) ?></span></td>
                                <td><button class="btn btn-sm btn-outline-primary" onclick="viewBill(<?=$b['id']?>)" style="border-radius:6px"><i class="fas fa-eye"></i></button></td>
                            </tr>
                            <?php if (!empty($paymentHistory[$b['id']])): ?>
                            <tr><td colspan="11" style="padding:0 12px 10px;border:0">
                                <div class="pay-hist">
                                    <strong style="font-size:11px;color:var(--green)"><i class="fas fa-history"></i> Payment History</strong>
                                    <?php foreach ($paymentHistory[$b['id']] as $ph): ?>
                                        <div class="pay-row"><span><strong style="color:var(--green)">+<?= formatCurrency($ph['amount']) ?></strong></span><span style="color:var(--text-muted)"><?= date('M d, Y', strtotime($ph['payment_date'])) ?></span><span style="color:var(--text-muted)"><?= e($ph['note'] ?: '-') ?></span></div>
                                    <?php endforeach; ?>
                                </div>
                            </td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- PAID BILLS TAB -->
<div class="tp" id="tab-paid">
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 style="font-weight:700;font-size:14px;margin:0">Paid Bills</h6>
            <span style="font-size:12px;color:var(--text-muted)">Total: <strong style="color:var(--green)"><?= formatCurrency($totalPaidAmount) ?></strong></span>
        </div>
        <?php if (empty($paidBills)): ?>
            <div class="text-center py-4" style="color:var(--text-muted)">No paid bills found</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Invoice</th><th>Business</th><th>Customer</th><th>Date</th><th>Amount</th><th>Payment</th><th>Paid Date</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($paidBills as $b):
                            $wasDue = ($b['payment_mode'] ?? '') === 'Due';
                            $paidDate = $b['due_paid_date'] ?? $b['date'];
                        ?>
                            <tr class="bill-row">
                                <td><strong><?= e($b['invoice_number'] ?: '#'.$b['id']) ?></strong></td>
                                <td style="font-size:12px;color:var(--text-secondary)"><?= e($b['business_name'] ?: $b['owner_name'] ?: '-') ?></td>
                                <td><strong><?= e($b['customer_name']) ?></strong></td>
                                <td style="font-size:12px"><?= date('M d, Y', strtotime($b['date'])) ?></td>
                                <td><strong style="color:var(--green)"><?= formatCurrency($b['grand_total']) ?></strong></td>
                                <td><?php if($wasDue):?><span class="sb sb-partial">Due → Paid</span><?php else:?><span class="sb sb-paid"><?= e($b['payment_mode']) ?></span><?php endif;?></td>
                                <td style="font-size:12px"><?= date('M d, Y', strtotime($paidDate)) ?></td>
                                <td><span class="sb sb-paid">Paid</span></td>
                                <td><button class="btn btn-sm btn-outline-primary" onclick="viewBill(<?=$b['id']?>)" style="border-radius:6px"><i class="fas fa-eye"></i></button></td>
                            </tr>
                            <?php if ($wasDue && !empty($paymentHistory[$b['id']])): ?>
                            <tr><td colspan="9" style="padding:0 12px 10px;border:0">
                                <div class="pay-hist">
                                    <strong style="font-size:11px;color:var(--green)"><i class="fas fa-history"></i> Payment Journey</strong>
                                    <?php foreach ($paymentHistory[$b['id']] as $ph): ?>
                                        <div class="pay-row"><span><strong style="color:var(--green)">+<?= formatCurrency($ph['amount']) ?></strong></span><span style="color:var(--text-muted)"><?= date('M d, Y', strtotime($ph['payment_date'])) ?></span><span style="color:var(--text-muted)"><?= e($ph['note'] ?: '-') ?></span></div>
                                    <?php endforeach; ?>
                                    <div class="pay-row" style="border:0;padding-top:4px"><span><strong style="color:var(--green)">Total: <?= formatCurrency($b['grand_total']) ?></strong></span><span class="sb sb-paid">CLEARED</span></div>
                                </div>
                            </td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ALL BILLS TAB -->
<div class="tp" id="tab-all">
    <div class="table-container">
        <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">All Bills (<?= count($allBills) ?>)</h6>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Invoice</th><th>Business</th><th>Customer</th><th>Date</th><th>Items</th><th>Amount</th><th>Payment</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php if (empty($allBills)): ?>
                        <tr><td colspan="9" class="text-center" style="color:var(--text-muted);padding:24px">No bills found</td></tr>
                    <?php else: foreach ($allBills as $b):
                        $ds = $b['due_status'] ?? 'paid';
                    ?>
                        <tr class="bill-row">
                            <td><strong><?= e($b['invoice_number'] ?: '#'.$b['id']) ?></strong></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= e($b['business_name'] ?: $b['owner_name'] ?: '-') ?></td>
                            <td><?= e($b['customer_name']) ?></td>
                            <td style="font-size:12px"><?= date('M d', strtotime($b['date'])) ?></td>
                            <td><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= count(json_decode($b['items'], true) ?: []) ?></span></td>
                            <td><strong><?= formatCurrency($b['grand_total']) ?></strong></td>
                            <td><span class="sb <?=$b['payment_mode']==='Due'?'sb-due':'sb-paid'?>"><?= e($b['payment_mode']) ?></span></td>
                            <td><span class="sb <?=$ds==='paid'?'sb-paid':($ds==='partial'?'sb-partial':'sb-due')?>"><?= ucfirst($ds) ?></span></td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewBill(<?=$b['id']?>)" style="border-radius:6px"><i class="fas fa-eye"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bill Detail Modal -->
<div class="modal fade" id="billModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" style="font-weight:700">Bill Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="billDetails">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"></div></div>
            </div>
        </div>
    </div>
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
    document.querySelectorAll('.tp.active .bill-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});

function viewBill(billId) {
    const modal = new bootstrap.Modal(document.getElementById('billModal'));
    modal.show();
    fetch('api/get_bill.php?id=' + billId)
        .then(r => r.json())
        .then(data => {
            if (data.success) displayBillDetails(data.bill, data.payments || []);
            else document.getElementById('billDetails').innerHTML = '<div class="alert alert-danger">Failed to load</div>';
        })
        .catch(() => { document.getElementById('billDetails').innerHTML = '<div class="alert alert-danger">Error loading bill</div>'; });
}

function displayBillDetails(bill, payments) {
    const items = JSON.parse(bill.items || '[]');
    const ds = bill.due_status || 'paid';
    const paid = parseFloat(bill.paid_amount || 0);
    const total = parseFloat(bill.grand_total);
    const remaining = total - paid;
    const isDue = bill.payment_mode === 'Due';

    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Invoice: <strong>${bill.invoice_number || '#' + bill.id}</strong></h6>
                <p class="mb-1">Date: ${bill.date}</p>
                <p class="mb-1">Payment: <span class="sb ${isDue ? 'sb-due' : 'sb-paid'}">${bill.payment_mode}</span>
                    <span class="sb ${ds==='paid'?'sb-paid':ds==='partial'?'sb-partial':'sb-due'}" style="margin-left:6px">${ds.toUpperCase()}</span></p>
                ${bill.due_date ? '<p class="mb-1">Due Date: ' + bill.due_date + '</p>' : ''}
            </div>
            <div class="col-md-6 text-end">
                <h6>Customer</h6>
                <p class="mb-1"><strong>${bill.customer_name}</strong></p>
                <p class="mb-1">${bill.customer_mobile || ''}</p>
            </div>
        </div>
        <table class="table table-sm tbl">
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>`;
    items.forEach(item => {
        html += `<tr><td>${item.productName || item.name}</td><td>${item.quantity}</td><td>₹${parseFloat(item.price).toFixed(2)}</td><td>₹${(item.quantity * item.price).toFixed(2)}</td></tr>`;
    });
    html += `</tbody></table>
        <div class="row"><div class="col-md-6 offset-md-6">
            <table class="table table-sm">
                <tr><th>Subtotal:</th><td class="text-end">₹${parseFloat(bill.subtotal).toFixed(2)}</td></tr>
                <tr><th>Discount:</th><td class="text-end">₹${parseFloat(bill.discount).toFixed(2)}</td></tr>
                <tr><th>Tax:</th><td class="text-end">₹${parseFloat(bill.tax).toFixed(2)}</td></tr>
                <tr style="background:#eff6ff"><th>Grand Total:</th><th class="text-end">₹${total.toFixed(2)}</th></tr>`;
    if (isDue) {
        html += `<tr><th>Paid:</th><td class="text-end" style="color:#10b981">₹${paid.toFixed(2)}</td></tr>
                 <tr style="background:${ds!=='paid'?'#fef2f2':'#f0fdf4'}"><th>Remaining:</th><th class="text-end" style="color:${ds!=='paid'?'#ef4444':'#10b981'}">₹${remaining.toFixed(2)}</th></tr>`;
    }
    html += `</table></div></div>`;

    if (payments && payments.length > 0) {
        html += `<div class="pay-hist mt-3"><strong style="font-size:13px;color:#065f46"><i class="fas fa-history"></i> Payment History</strong>`;
        payments.forEach(p => {
            html += `<div class="pay-row"><span><strong style="color:#10b981">+₹${parseFloat(p.amount).toFixed(2)}</strong></span><span>${p.payment_date}</span><span style="color:#64748b">${p.note || '-'}</span></div>`;
        });
        html += `</div>`;
    }
    document.getElementById('billDetails').innerHTML = html;
}

function exportCSV() {
    const bills = <?= json_encode(array_map(fn($b) => [
        'Invoice' => $b['invoice_number'] ?: '#'.$b['id'],
        'Business' => $b['business_name'] ?: $b['owner_name'] ?: '-',
        'Customer' => $b['customer_name'],
        'Date' => $b['date'],
        'Amount' => $b['grand_total'],
        'Payment' => $b['payment_mode'],
        'Due_Status' => $b['due_status'] ?? 'paid',
        'Paid_Amount' => $b['paid_amount'] ?? 0,
        'Remaining' => $b['grand_total'] - ($b['paid_amount'] ?? 0)
    ], $allBills)) ?>;
    if (!bills.length) return alert('No data to export');
    const headers = Object.keys(bills[0]);
    let csv = headers.join(',') + '\n';
    bills.forEach(row => { csv += headers.map(h => '"' + String(row[h]).replace(/"/g,'""') + '"').join(',') + '\n'; });
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'bills_export_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}
</script>

<?php include 'includes/footer.php'; ?>
