<?php
require_once '../config/config.php';
checkStaffAuth('shop_staff');

$products = $pdo->query("
    SELECT p.*, pc.name as category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    ORDER BY p.stock_quantity ASC, p.name ASC
")->fetchAll();

$totalProducts    = count($products);
$outOfStockCount  = 0;
$lowStockCount    = 0;
$inStockCount     = 0;
$totalStockValue  = 0;

foreach ($products as $p) {
    $totalStockValue += $p['stock_quantity'] * $p['price'];
    if ($p['stock_quantity'] == 0) $outOfStockCount++;
    elseif ($p['stock_quantity'] <= $p['reorder_level']) $lowStockCount++;
    else $inStockCount++;
}

include 'includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1>Stock Report</h1>
        <p style="color:#666;">Generated: <?= date('F j, Y, g:i A') ?></p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">🖨 Print Report</button>
</div>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-content"><h3><?= $inStockCount ?></h3><p>In Stock</p></div>
    </div>
    <div class="stat-card" style="border-left:4px solid #f57c00;">
        <div class="stat-content"><h3><?= $lowStockCount ?></h3><p>Low Stock</p></div>
    </div>
    <div class="stat-card" style="border-left:4px solid #c62828;">
        <div class="stat-content"><h3><?= $outOfStockCount ?></h3><p>Out of Stock</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($totalStockValue, 2) ?></h3><p>Total Inventory Value</p></div>
    </div>
</div>

<!-- Out of Stock Alert -->
<?php $outOfStock = array_filter($products, fn($p) => $p['stock_quantity'] == 0); ?>
<?php if ($outOfStock): ?>
<div class="alert alert-error" style="margin-bottom:1.5rem;">
    <strong>⚠ Out of Stock Items (<?= count($outOfStock) ?>):</strong>
    <?= implode(', ', array_map(fn($p) => htmlspecialchars($p['name']), $outOfStock)) ?>
</div>
<?php endif; ?>

<!-- Low Stock Alert -->
<?php $lowStock = array_filter($products, fn($p) => $p['stock_quantity'] > 0 && $p['stock_quantity'] <= $p['reorder_level']); ?>
<?php if ($lowStock): ?>
<div class="alert alert-error" style="background:#fff3cd;border-color:#f57c00;color:#795548;margin-bottom:1.5rem;">
    <strong>⚡ Low Stock Items (<?= count($lowStock) ?>):</strong>
    <?= implode(', ', array_map(fn($p) => htmlspecialchars($p['name']).' ('.$p['stock_quantity'].' left)', $lowStock)) ?>
</div>
<?php endif; ?>

<!-- Full Report Table -->
<div class="card">
    <div class="card-header"><h3>Full Inventory Report</h3></div>
    <table class="data-table">
        <thead>
            <tr><th>Product</th><th>SKU</th><th>Category</th><th>Unit Price</th><th>Stock Qty</th><th>Reorder Level</th><th>Stock Value</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p):
                if ($p['stock_quantity'] == 0) {
                    $status = 'out_of_stock'; $badge = 'badge-danger'; $label = 'Out of Stock';
                } elseif ($p['stock_quantity'] <= $p['reorder_level']) {
                    $status = 'low_stock'; $badge = 'badge-warning'; $label = 'Low Stock';
                } else {
                    $status = 'ok'; $badge = 'badge-success'; $label = 'In Stock';
                }
                $stockValue = $p['stock_quantity'] * $p['price'];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td>₱<?= number_format($p['price'],2) ?></td>
                <td style="font-weight:700;color:<?= $p['stock_quantity']==0?'#c62828':($p['stock_quantity']<=$p['reorder_level']?'#f57c00':'#2e7d32') ?>;">
                    <?= $p['stock_quantity'] ?>
                </td>
                <td><?= $p['reorder_level'] ?></td>
                <td>₱<?= number_format($stockValue,2) ?></td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:700;">
                <td colspan="6">Total Inventory Value:</td>
                <td>₱<?= number_format($totalStockValue, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Category Breakdown -->
    <?php
    $byCategory = [];
    foreach ($products as $p) {
        $cat = $p['category_name'] ?? 'Uncategorized';
        if (!isset($byCategory[$cat])) $byCategory[$cat] = ['count'=>0,'value'=>0];
        $byCategory[$cat]['count']++;
        $byCategory[$cat]['value'] += $p['stock_quantity'] * $p['price'];
    }
    arsort($byCategory);
    ?>
    <div style="padding:1.5rem;border-top:1px solid #eee;">
        <h4 style="margin-bottom:1rem;">Inventory Value by Category</h4>
        <?php foreach ($byCategory as $cat => $data): ?>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.75rem;">
            <div style="width:160px;font-size:.9rem;"><?= htmlspecialchars($cat) ?></div>
            <div style="flex:1;background:#f0f0f0;border-radius:999px;height:20px;">
                <?php $pct = $totalStockValue > 0 ? ($data['value']/$totalStockValue)*100 : 0; ?>
                <div style="width:<?= number_format($pct,1) ?>%;background:#bf360c;height:20px;border-radius:999px;"></div>
            </div>
            <div style="width:120px;font-size:.9rem;text-align:right;">₱<?= number_format($data['value'],2) ?> (<?= $data['count'] ?> items)</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
