<?php
require_once '../config/config.php';
checkStaffAuth('shop_staff');

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$search   = trim(sanitize($_GET['search'] ?? ''));

$whereParts = ["ps.sale_date BETWEEN :from AND :to"];
$params = [':from' => $dateFrom, ':to' => $dateTo];
if ($search) { $whereParts[] = "(ps.customer_name LIKE :s OR ps.customer_email LIKE :s OR CAST(ps.sale_id AS CHAR) LIKE :s)"; $params[':s'] = "%$search%"; }

$where = implode(' AND ', $whereParts);
$sales = $pdo->prepare("
    SELECT ps.*, au.full_name as served_by_name
    FROM product_sales ps
    LEFT JOIN admin_users au ON ps.served_by = au.admin_id
    WHERE $where
    ORDER BY ps.created_at DESC
");
$sales->execute($params); $sales = $sales->fetchAll();

// Total stats
$totals = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount),0) as revenue, COALESCE(SUM(discount_amount),0) as discounts FROM product_sales ps WHERE $where");
$totals->execute($params); $totals = $totals->fetch();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Sales History</h1>
</div>

<!-- Filters -->
<form method="GET" class="card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:0 0 160px;">
            <label>Date From</label>
            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
        </div>
        <div class="form-group" style="margin:0;flex:0 0 160px;">
            <label>Date To</label>
            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:180px;">
            <label>Search</label>
            <input type="text" name="search" class="form-control" placeholder="Customer name, email, ID…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="background:#bf360c;">Filter</button>
        <a href="sales-history.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<!-- Summary Stats -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-content"><h3><?= $totals['count'] ?></h3><p>Transactions</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($totals['revenue'],2) ?></h3><p>Total Revenue</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($totals['discounts'],2) ?></h3><p>Discounts Given</p></div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <table class="data-table">
        <thead><tr><th>Sale ID</th><th>Date</th><th>Customer</th><th>Items</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Payment</th><th>Promo</th><th>Served By</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (!$sales): ?>
            <tr><td colspan="11" style="text-align:center;color:#999;padding:2rem;">No sales found for selected filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($sales as $s):
                // Count items
                $ic = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE sale_id=?"); $ic->execute([$s['sale_id']]); $itemCount = $ic->fetchColumn();
            ?>
            <tr>
                <td><strong>#<?= $s['sale_id'] ?></strong></td>
                <td><?= $s['sale_date'] ?></td>
                <td><?= htmlspecialchars($s['customer_name'] ?: 'Walk-in') ?></td>
                <td><?= $itemCount ?> item<?= $itemCount!=1?'s':'' ?></td>
                <td>₱<?= number_format($s['total_amount'] + $s['discount_amount'], 2) ?></td>
                <td><?= $s['discount_amount'] > 0 ? '–₱'.number_format($s['discount_amount'],2) : '—' ?></td>
                <td><strong>₱<?= number_format($s['total_amount'],2) ?></strong></td>
                <td><span class="badge badge-primary"><?= ucfirst($s['payment_method']) ?></span></td>
                <td><?= $s['promo_code'] ? '<code>'.$s['promo_code'].'</code>' : '—' ?></td>
                <td><?= htmlspecialchars($s['served_by_name'] ?? '—') ?></td>
                <td>
                    <button type="button" onclick="toggleItems(<?= $s['sale_id'] ?>)" class="btn btn-sm btn-secondary">Items</button>
                </td>
            </tr>
            <!-- Item sub-row -->
            <tr id="items-<?= $s['sale_id'] ?>" style="display:none;background:#fafafa;">
                <td colspan="11" style="padding:0;">
                    <div style="padding:.75rem 1.5rem;">
                        <?php
                        $items = $pdo->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.product_id WHERE si.sale_id = ?");
                        $items->execute([$s['sale_id']]); $items = $items->fetchAll();
                        ?>
                        <table style="width:100%;font-size:.85rem;border-collapse:collapse;">
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td style="padding:.3rem 1rem .3rem 0;"><?= htmlspecialchars($item['name']) ?></td>
                                <td style="padding:.3rem 1rem .3rem 0;">Qty: <?= $item['quantity'] ?></td>
                                <td style="padding:.3rem 1rem .3rem 0;">@ ₱<?= number_format($item['unit_price'],2) ?></td>
                                <td style="padding:.3rem 0;">= ₱<?= number_format($item['subtotal'],2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function toggleItems(id) {
    const r = document.getElementById('items-' + id);
    r.style.display = r.style.display === 'none' ? 'table-row' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
