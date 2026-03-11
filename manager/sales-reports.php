<?php
require_once '../config/config.php';
checkStaffAuth('manager');

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Top products by revenue
$topProducts = $pdo->prepare("
    SELECT p.name, p.sku, pc.name as category,
           SUM(si.quantity) as units_sold, SUM(si.subtotal) as revenue
    FROM sale_items si
    JOIN product_sales ps ON si.sale_id = ps.sale_id
    JOIN products p ON si.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    WHERE ps.sale_date BETWEEN ? AND ?
    GROUP BY p.product_id, p.name, p.sku, pc.name
    ORDER BY revenue DESC
    LIMIT 20
");
$topProducts->execute([$dateFrom, $dateTo]);
$topProducts = $topProducts->fetchAll();

// By category
$byCategory = $pdo->prepare("
    SELECT pc.name as category, SUM(si.quantity) as units, SUM(si.subtotal) as revenue
    FROM sale_items si
    JOIN product_sales ps ON si.sale_id = ps.sale_id
    JOIN products p ON si.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    WHERE ps.sale_date BETWEEN ? AND ?
    GROUP BY pc.category_id, pc.name
    ORDER BY revenue DESC
");
$byCategory->execute([$dateFrom, $dateTo]);
$byCategory = $byCategory->fetchAll();

// Summary totals
$stmt = $pdo->prepare("SELECT COUNT(*) as txns, COALESCE(SUM(total_amount),0) as rev, COALESCE(SUM(discount_amount),0) as disc FROM product_sales WHERE sale_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom,$dateTo]); $summary = $stmt->fetch();

include 'includes/header.php';
?>

<!-- Print-only report header -->
<div class="print-report-header">
    <div>
        <div class="museum-name">eMuse &mdash; Museum Management System</div>
        <div class="report-subtitle">Sales Reports &mdash; Souvenir &amp; Product Sales Analysis</div>
    </div>
    <div class="report-meta">
        <div>Period: <strong><?= date('M j, Y', strtotime($dateFrom)) ?> &ndash; <?= date('M j, Y', strtotime($dateTo)) ?></strong></div>
        <div>Generated: <?= date('F j, Y \a\t g:i A') ?></div>
        <div>Prepared by: <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong> &mdash; Manager</div>
    </div>
</div>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1>Sales Reports</h1>
        <p style="color:#666;">Souvenir and product sales analysis</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary no-print">🖨 Print / Export</button>
</div>

<form method="GET" class="card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;"><label>From</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
        <div class="form-group" style="margin:0;"><label>To</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
        <button type="submit" class="btn btn-primary" style="background:#004d40;">Apply</button>
        <a href="sales-reports.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-content"><h3><?= $summary['txns'] ?></h3><p>Transactions</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3>₱<?= number_format($summary['rev'],2) ?></h3><p>Total Revenue</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3><?= array_sum(array_column($topProducts,'units_sold')) ?></h3><p>Units Sold</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3>₱<?= number_format($summary['disc'],2) ?></h3><p>Discounts Given</p></div></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <!-- Top Products -->
    <div class="card">
        <div class="card-header"><h3>Top Products</h3></div>
        <table class="data-table">
            <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Units Sold</th><th>Revenue</th></tr></thead>
            <tbody>
                <?php if (!$topProducts): ?>
                <tr><td colspan="5" style="text-align:center;padding:2rem;color:#999;">No sales data for this period.</td></tr>
                <?php endif; ?>
                <?php foreach ($topProducts as $i => $p): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong><div style="font-size:.8rem;color:#999;"><?= htmlspecialchars($p['sku']) ?></div></td>
                    <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                    <td><?= number_format($p['units_sold']) ?></td>
                    <td><strong>₱<?= number_format($p['revenue'],2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- By Category -->
    <div class="card">
        <div class="card-header"><h3>Sales by Category</h3></div>
        <div style="padding:1.25rem;">
            <?php
            $maxCatRev = $byCategory ? max(array_column($byCategory,'revenue')) : 0;
            foreach ($byCategory as $cat): $pct = $maxCatRev>0?($cat['revenue']/$maxCatRev)*100:0; ?>
            <div style="margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;font-size:.9rem;">
                    <span><?= htmlspecialchars($cat['category'] ?? 'Uncategorized') ?></span>
                    <strong>₱<?= number_format($cat['revenue'],2) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#999;margin-bottom:.3rem;">
                    <span><?= $cat['units'] ?> units sold</span>
                    <span><?= $summary['rev']>0?number_format($cat['revenue']/$summary['rev']*100,1).'%':'' ?></span>
                </div>
                <div style="background:#e0f2f1;height:10px;border-radius:999px;">
                    <div style="width:<?= $pct ?>%;background:#004d40;height:10px;border-radius:999px;"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$byCategory): ?><p style="color:#999;text-align:center;">No data.</p><?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
