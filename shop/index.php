<?php
require_once '../config/config.php';
checkStaffAuth('shop_staff');

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM product_sales WHERE sale_date=?"); $stmt->execute([$today]); $todaySales = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE sale_date=?"); $stmt->execute([$today]); $todayRevenue = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= reorder_level AND status='active'"); $lowStock = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0 AND status='active'"); $outOfStock = $stmt->fetchColumn();

// Recent transactions
$recent = $pdo->query("
    SELECT ps.*, au.full_name as served_by_name
    FROM product_sales ps
    LEFT JOIN admin_users au ON ps.served_by = au.admin_id
    ORDER BY ps.created_at DESC LIMIT 8
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Shop Dashboard</h1>
    <p style="color:#666;">Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</p>
</div>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fbe9e7;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#bf360c" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>
        <div class="stat-content"><h3><?= $todaySales ?></h3><p>Sales Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div class="stat-content"><h3>₱<?= number_format($todayRevenue,2) ?></h3><p>Revenue Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff8e1;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
        <div class="stat-content"><h3><?= $lowStock ?></h3><p>Low Stock Items</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ffebee;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
        <div class="stat-content"><h3><?= $outOfStock ?></h3><p>Out of Stock</p></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:2rem;">
    <a href="pos.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#bf360c;color:white;text-align:center;padding:2rem;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <h3 style="margin:1rem 0 .25rem;color:white;">New Sale</h3>
            <p style="color:rgba(255,255,255,.7);margin:0;">Open the Point of Sale</p>
        </div>
    </a>
    <a href="inventory.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#d84315;color:white;text-align:center;padding:2rem;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
            <h3 style="margin:1rem 0 .25rem;color:white;">Update Inventory</h3>
            <p style="color:rgba(255,255,255,.7);margin:0;">Adjust stock quantities</p>
        </div>
    </a>
</div>

<!-- Recent Sales -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3>Recent Transactions</h3>
        <a href="sales-history.php" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    <?php if ($recent): ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Date</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Served By</th></tr></thead>
        <tbody>
            <?php foreach ($recent as $s): ?>
            <tr>
                <td>#<?= $s['sale_id'] ?></td>
                <td><?= formatDate($s['sale_date']) ?></td>
                <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
                <td>₱<?= number_format($s['total_amount'],2) ?></td>
                <td><?= ucfirst($s['payment_method']) ?></td>
                <td><?= htmlspecialchars($s['served_by_name'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No sales recorded yet today.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
