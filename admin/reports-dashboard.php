<?php
require_once '../config/config.php';
checkAuth();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get summary statistics
$stmt = $pdo->prepare("
    SELECT 
        SUM(total_visitors) as total_visitors,
        SUM(total_revenue) as total_revenue,
        AVG(average_rating) as avg_rating,
        SUM(tours_conducted) as total_tours
    FROM daily_reports
    WHERE report_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$summary = $stmt->fetch();

// Get ticket breakdown
$ticketBreakdown = $pdo->prepare("
    SELECT 
        SUM(adult_tickets) as adult,
        SUM(child_tickets) as child,
        SUM(senior_tickets) as senior,
        SUM(student_tickets) as student,
        SUM(group_tickets) as groups
    FROM daily_reports
    WHERE report_date BETWEEN ? AND ?
");
$ticketBreakdown->execute([$start_date, $end_date]);
$tickets = $ticketBreakdown->fetch();

// Get revenue breakdown
$revenueBreakdown = $pdo->prepare("
    SELECT 
        SUM(ticket_revenue) as ticket_revenue,
        SUM(tour_revenue) as tour_revenue,
        SUM(shop_revenue) as shop_revenue
    FROM daily_reports
    WHERE report_date BETWEEN ? AND ?
");
$revenueBreakdown->execute([$start_date, $end_date]);
$revenue = $revenueBreakdown->fetch();

// Get daily data for chart
$dailyData = $pdo->prepare("
    SELECT * FROM daily_reports
    WHERE report_date BETWEEN ? AND ?
    ORDER BY report_date ASC
");
$dailyData->execute([$start_date, $end_date]);
$daily = $dailyData->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Reports & Analytics Dashboard</h1>
    <div style="display: flex; gap: 10px;">
        <a href="daily-reports.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Daily Reports
        </a>
        <a href="revenue-reports.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            Revenue
        </a>
        <a href="performance-reports.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Performance
        </a>
    </div>
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
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <a href="reports-dashboard.php" class="btn btn-secondary">Reset</a>
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
            <h3><?php echo number_format($summary['total_visitors'] ?? 0); ?></h3>
            <p>Total Visitors</p>
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
            <h3><?php echo formatCurrency($summary['total_revenue'] ?? 0); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff9c4;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($summary['avg_rating'] ?? 0, 1); ?> / 5</h3>
            <p>Average Rating</p>
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
            <h3><?php echo number_format($summary['total_tours'] ?? 0); ?></h3>
            <p>Tours Conducted</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h2>Ticket Type Distribution</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <?php 
                $total_tickets = ($tickets['adult'] ?? 0) + ($tickets['child'] ?? 0) + 
                                ($tickets['senior'] ?? 0) + ($tickets['student'] ?? 0) + 
                                ($tickets['groups'] ?? 0);
                ?>
                <div class="pie-chart-item">
                    <div class="pie-label">Adult</div>
                    <div class="pie-bar" style="background: #2196f3; width: <?php echo $total_tickets > 0 ? (($tickets['adult'] ?? 0) / $total_tickets * 100) : 0; ?>%">
                        <?php echo $tickets['adult'] ?? 0; ?>
                    </div>
                </div>
                <div class="pie-chart-item">
                    <div class="pie-label">Child</div>
                    <div class="pie-bar" style="background: #4caf50; width: <?php echo $total_tickets > 0 ? (($tickets['child'] ?? 0) / $total_tickets * 100) : 0; ?>%">
                        <?php echo $tickets['child'] ?? 0; ?>
                    </div>
                </div>
                <div class="pie-chart-item">
                    <div class="pie-label">Senior</div>
                    <div class="pie-bar" style="background: #ff9800; width: <?php echo $total_tickets > 0 ? (($tickets['senior'] ?? 0) / $total_tickets * 100) : 0; %>%">
                        <?php echo $tickets['senior'] ?? 0; ?>
                    </div>
                </div>
                <div class="pie-chart-item">
                    <div class="pie-label">Student</div>
                    <div class="pie-bar" style="background: #9c27b0; width: <?php echo $total_tickets > 0 ? (($tickets['student'] ?? 0) / $total_tickets * 100) : 0; ?>%">
                        <?php echo $tickets['student'] ?? 0; ?>
                    </div>
                </div>
                <div class="pie-chart-item">
                    <div class="pie-label">Group</div>
                    <div class="pie-bar" style="background: #f44336; width: <?php echo $total_tickets > 0 ? (($tickets['groups'] ?? 0) / $total_tickets * 100) : 0; ?>%">
                        <?php echo $tickets['groups'] ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Revenue Sources</h2>
        </div>
        <div class="card-body">
            <div class="revenue-chart">
                <div class="revenue-item">
                    <div class="revenue-icon" style="background: #e3f2fd;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                    </div>
                    <div class="revenue-details">
                        <span class="revenue-label">Ticket Sales</span>
                        <span class="revenue-amount"><?php echo formatCurrency($revenue['ticket_revenue'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="revenue-item">
                    <div class="revenue-icon" style="background: #e8f5e9;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="revenue-details">
                        <span class="revenue-label">Tour Bookings</span>
                        <span class="revenue-amount"><?php echo formatCurrency($revenue['tour_revenue'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="revenue-item">
                    <div class="revenue-icon" style="background: #fff3e0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <div class="revenue-details">
                        <span class="revenue-label">Shop Sales</span>
                        <span class="revenue-amount"><?php echo formatCurrency($revenue['shop_revenue'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($daily)): ?>
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>Daily Trends</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Visitors</th>
                            <th>Revenue</th>
                            <th>Tours</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily as $day): ?>
                            <tr>
                                <td><?php echo formatDate($day['report_date']); ?></td>
                                <td><strong><?php echo number_format($day['total_visitors']); ?></strong></td>
                                <td><?php echo formatCurrency($day['total_revenue']); ?></td>
                                <td><?php echo $day['tours_conducted']; ?></td>
                                <td>
                                    <?php if ($day['average_rating']): ?>
                                        <span class="badge badge-warning">
                                            <?php echo number_format($day['average_rating'], 1); ?> ★
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.chart-container {
    padding: 20px 0;
}

.pie-chart-item {
    display: grid;
    grid-template-columns: 100px 1fr auto;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.pie-label {
    font-weight: 500;
    color: #424242;
}

.pie-bar {
    height: 32px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    min-width: 40px;
}

.revenue-chart {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px 0;
}

.revenue-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}

.revenue-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.revenue-details {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.revenue-label {
    font-size: 14px;
    color: #757575;
}

.revenue-amount {
    font-size: 20px;
    font-weight: bold;
    color: #424242;
}

.text-muted {
    color: #9e9e9e;
}
</style>

<?php include 'includes/footer.php'; ?>
