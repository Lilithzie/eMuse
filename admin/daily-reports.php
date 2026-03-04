<?php
require_once '../config/config.php';
checkAuth();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get daily reports
$stmt = $pdo->prepare("
    SELECT * FROM daily_reports
    WHERE report_date BETWEEN ? AND ?
    ORDER BY report_date DESC
");
$stmt->execute([$start_date, $end_date]);
$reports = $stmt->fetchAll();

// Calculate totals
$total_visitors = array_sum(array_column($reports, 'total_visitors'));
$total_revenue = array_sum(array_column($reports, 'total_revenue'));
$avg_visitors = count($reports) > 0 ? $total_visitors / count($reports) : 0;

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Daily Visitor Statistics</h1>
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
            <a href="daily-reports.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($total_visitors); ?></h3>
            <p>Total Visitors</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <line x1="12" y1="20" x2="12" y2="10"/>
                <line x1="18" y1="20" x2="18" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="16"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($avg_visitors, 1); ?></h3>
            <p>Avg. Daily Visitors</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
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
        <div class="stat-icon" style="background: #f3e5f5;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo count($reports); ?></h3>
            <p>Days Reported</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Daily Reports</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Visitors</th>
                        <th>Adult</th>
                        <th>Child</th>
                        <th>Senior</th>
                        <th>Student</th>
                        <th>Group</th>
                        <th>Revenue</th>
                        <th>Tours</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">No reports found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><strong><?php echo formatDate($report['report_date']); ?></strong></td>
                                <td><strong><?php echo number_format($report['total_visitors']); ?></strong></td>
                                <td><?php echo $report['adult_tickets']; ?></td>
                                <td><?php echo $report['child_tickets']; ?></td>
                                <td><?php echo $report['senior_tickets']; ?></td>
                                <td><?php echo $report['student_tickets']; ?></td>
                                <td><?php echo $report['group_tickets']; ?></td>
                                <td><?php echo formatCurrency($report['total_revenue']); ?></td>
                                <td><?php echo $report['tours_conducted']; ?></td>
                                <td>
                                    <?php if ($report['average_rating']): ?>
                                        <span class="badge badge-warning">
                                            <?php echo number_format($report['average_rating'], 1); ?> ★
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.text-muted {
    color: #9e9e9e;
}
</style>

<?php include 'includes/footer.php'; ?>
