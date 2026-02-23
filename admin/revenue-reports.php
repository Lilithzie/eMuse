<?php
require_once '../config/config.php';
checkAuth();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get revenue tracking data
$stmt = $pdo->prepare("
    SELECT * FROM revenue_tracking
    WHERE transaction_date BETWEEN ? AND ?
    ORDER BY transaction_date DESC, created_at DESC
");
$stmt->execute([$start_date, $end_date]);
$transactions = $stmt->fetchAll();

// Calculate summary
$total_revenue = array_sum(array_column($transactions, 'amount'));
$ticket_revenue = array_sum(array_map(fn($t) => $t['revenue_type'] == 'ticket' ? $t['amount'] : 0, $transactions));
$tour_revenue = array_sum(array_map(fn($t) => $t['revenue_type'] == 'tour' ? $t['amount'] : 0, $transactions));
$shop_revenue = array_sum(array_map(fn($t) => $t['revenue_type'] == 'shop' ? $t['amount'] : 0, $transactions));
$other_revenue = array_sum(array_map(fn($t) => in_array($t['revenue_type'], ['event', 'other']) ? $t['amount'] : 0, $transactions));

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Revenue Tracking</h1>
    <a href="reports-dashboard.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Dashboard
    </a>
</div>

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
            <a href="revenue-reports.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
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
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($ticket_revenue); ?></h3>
            <p>Ticket Sales</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e5f5;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($tour_revenue); ?></h3>
            <p>Tour Bookings</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($shop_revenue); ?></h3>
            <p>Shop Sales</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Revenue Breakdown by Type</h2>
    </div>
    <div class="card-body">
        <div class="revenue-breakdown">
            <div class="breakdown-item">
                <div class="breakdown-label">Ticket Sales</div>
                <div class="breakdown-bar">
                    <div class="breakdown-fill" style="width: <?php echo $total_revenue > 0 ? ($ticket_revenue / $total_revenue * 100) : 0; ?>%; background: #2196f3;"></div>
                </div>
                <div class="breakdown-amount"><?php echo formatCurrency($ticket_revenue); ?></div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Tour Bookings</div>
                <div class="breakdown-bar">
                    <div class="breakdown-fill" style="width: <?php echo $total_revenue > 0 ? ($tour_revenue / $total_revenue * 100) : 0; ?>%; background: #9c27b0;"></div>
                </div>
                <div class="breakdown-amount"><?php echo formatCurrency($tour_revenue); ?></div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Shop Sales</div>
                <div class="breakdown-bar">
                    <div class="breakdown-fill" style="width: <?php echo $total_revenue > 0 ? ($shop_revenue / $total_revenue * 100) : 0; ?>%; background: #ff9800;"></div>
                </div>
                <div class="breakdown-amount"><?php echo formatCurrency($shop_revenue); ?></div>
            </div>
            <div class="breakdown-item">
                <div class="breakdown-label">Other Revenue</div>
                <div class="breakdown-bar">
                    <div class="breakdown-fill" style="width: <?php echo $total_revenue > 0 ? ($other_revenue / $total_revenue * 100) : 0; %>%; background: #4caf50;"></div>
                </div>
                <div class="breakdown-amount"><?php echo formatCurrency($other_revenue); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 30px;">
    <div class="card-header">
        <h2>Recent Transactions</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No transactions found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $transaction['revenue_type'] == 'ticket' ? 'primary' : 
                                            ($transaction['revenue_type'] == 'tour' ? 'info' : 
                                            ($transaction['revenue_type'] == 'shop' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($transaction['revenue_type']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo formatCurrency($transaction['amount']); ?></strong></td>
                                <td><?php echo htmlspecialchars($transaction['payment_method'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.revenue-breakdown {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px 0;
}

.breakdown-item {
    display: grid;
    grid-template-columns: 150px 1fr 150px;
    align-items: center;
    gap: 15px;
}

.breakdown-label {
    font-weight: 500;
    color: #424242;
}

.breakdown-bar {
    height: 32px;
    background: #e0e0e0;
    border-radius: 16px;
    overflow: hidden;
}

.breakdown-fill {
    height: 100%;
    border-radius: 16px;
    transition: width 0.3s ease;
}

.breakdown-amount {
    font-weight: bold;
    color: #424242;
    text-align: right;
}
</style>

<?php include 'includes/footer.php'; ?>
