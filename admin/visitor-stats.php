<?php
require_once '../config/config.php';
checkAuth();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get visitor statistics
$stats = $pdo->prepare("
    SELECT 
        visit_date,
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as visitors,
        SUM(price) as revenue
    FROM tickets
    WHERE visit_date BETWEEN ? AND ?
    GROUP BY visit_date
    ORDER BY visit_date DESC
");
$stats->execute([$start_date, $end_date]);
$dailyStats = $stats->fetchAll();

// Overall totals
$totals = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as total_visitors,
        SUM(price) as total_revenue
    FROM tickets
    WHERE visit_date BETWEEN ? AND ?
");
$totals->execute([$start_date, $end_date]);
$summary = $totals->fetch();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Visitor Statistics</h1>
</div>

<div class="card">
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $summary['total_tickets']; ?></h3>
        <p>Total Tickets Sold</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $summary['total_visitors']; ?></h3>
        <p>Total Visitors</p>
    </div>
    <div class="stat-card">
        <h3><?php echo formatCurrency($summary['total_revenue']); ?></h3>
        <p>Total Revenue</p>
    </div>
</div>

<div class="card">
    <h2>Daily Statistics</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Tickets Sold</th>
                    <th>Visitors</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyStats as $stat): ?>
                <tr>
                    <td><?php echo formatDate($stat['visit_date']); ?></td>
                    <td><?php echo $stat['total_tickets']; ?></td>
                    <td><?php echo $stat['visitors']; ?></td>
                    <td><?php echo formatCurrency($stat['revenue']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
