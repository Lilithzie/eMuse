<?php
require_once '../config/config.php';
checkAuth();

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] != '') {
    $sale_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM product_sales WHERE sale_id = ?");
    $stmt->execute([$sale_id]);
    header('Location: sales.php?success=delete');
    exit();
}

// Get date filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get all sales with admin info
$stmt = $pdo->prepare("
    SELECT ps.*, au.full_name as served_by_name
    FROM product_sales ps
    LEFT JOIN admin_users au ON ps.served_by = au.admin_id
    WHERE ps.sale_date BETWEEN ? AND ?
    ORDER BY ps.sale_date DESC, ps.created_at DESC
");
$stmt->execute([$start_date, $end_date]);
$sales = $stmt->fetchAll();

// Get statistics for the period
$total_sales = count($sales);
$total_revenue = array_sum(array_map(fn($s) => $s['total_amount'], $sales));
$avg_transaction = $total_sales > 0 ? $total_revenue / $total_sales : 0;
$cash_sales = array_sum(array_map(fn($s) => $s['payment_method'] == 'cash' ? $s['total_amount'] : 0, $sales));

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Sales Management</h1>
    <a href="sale-form.php" class="btn btn-primary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        New Sale
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php 
        if ($_GET['success'] == 'create') echo 'Sale recorded successfully!';
        elseif ($_GET['success'] == 'delete') echo 'Sale deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 30px;">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="margin: 0;">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" 
                       value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" 
                       value="<?php echo $end_date; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="sales.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_sales; ?></h3>
            <p>Total Sales</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($total_revenue); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                <line x1="12" y1="20" x2="12" y2="10"/>
                <line x1="18" y1="20" x2="18" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="16"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($avg_transaction); ?></h3>
            <p>Avg. Transaction</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e5f5;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($cash_sales); ?></h3>
            <p>Cash Sales</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Sales History</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                        <th>Served By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No sales found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><span class="badge badge-info">#<?php echo $sale['sale_id']; ?></span></td>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                                <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                                <td>
                                    <span class="badge badge-secondary">
                                        <?php echo ucfirst($sale['payment_method']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sale['served_by_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="sale-details.php?id=<?php echo $sale['sale_id']; ?>" class="btn-icon" title="View Details">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        <a href="?delete=<?php echo $sale['sale_id']; ?>" 
                                           class="btn-icon btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this sale?')"
                                           title="Delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
