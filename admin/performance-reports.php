<?php
require_once '../config/config.php';
checkAuth();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get performance metrics
$stmt = $pdo->prepare("
    SELECT * FROM performance_metrics
    WHERE metric_date BETWEEN ? AND ?
    ORDER BY metric_date DESC, metric_type
");
$stmt->execute([$start_date, $end_date]);
$metrics = $stmt->fetchAll();

// Calculate KPIs
$kpi_data = [];
foreach ($metrics as $metric) {
    if (!isset($kpi_data[$metric['metric_type']])) {
        $kpi_data[$metric['metric_type']] = [
            'total_value' => 0,
            'total_target' => 0,
            'count' => 0,
            'excellent' => 0,
            'good' => 0,
            'needs_improvement' => 0,
            'critical' => 0
        ];
    }
    $kpi_data[$metric['metric_type']]['total_value'] += $metric['metric_value'];
    $kpi_data[$metric['metric_type']]['total_target'] += $metric['target_value'];
    $kpi_data[$metric['metric_type']]['count']++;
    $kpi_data[$metric['metric_type']][$metric['status']]++;
}

// Overall performance score
$total_metrics = count($metrics);
$excellent_count = count(array_filter($metrics, fn($m) => $m['status'] == 'excellent'));
$good_count = count(array_filter($metrics, fn($m) => $m['status'] == 'good'));
$needs_improvement = count(array_filter($metrics, fn($m) => $m['status'] == 'needs_improvement'));
$critical_count = count(array_filter($metrics, fn($m) => $m['status'] == 'critical'));

$performance_score = $total_metrics > 0 ? 
    (($excellent_count * 100 + $good_count * 75 + $needs_improvement * 50 + $critical_count * 25) / $total_metrics) : 0;

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Performance Analysis</h1>
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
            <button type="submit" class="btn btn-primary">Analyze</button>
            <a href="performance-reports.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($performance_score, 1); ?>%</h3>
            <p>Performance Score</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e1f5fe;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#03a9f4" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $excellent_count; ?></h3>
            <p>Excellent Metrics</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $needs_improvement; ?></h3>
            <p>Needs Improvement</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #ffebee;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $critical_count; ?></h3>
            <p>Critical Issues</p>
        </div>
    </div>
</div>

<?php if (!empty($kpi_data)): ?>
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>Key Performance Indicators</h2>
        </div>
        <div class="card-body">
            <div class="kpi-grid">
                <?php foreach ($kpi_data as $type => $data): ?>
                    <?php 
                    $avg_value = $data['count'] > 0 ? $data['total_value'] / $data['count'] : 0;
                    $avg_target = $data['count'] > 0 ? $data['total_target'] / $data['count'] : 0;
                    $achievement = $avg_target > 0 ? ($avg_value / $avg_target * 100) : 0;
                    $status_color = $achievement >= 100 ? '#4caf50' : ($achievement >= 75 ? '#ff9800' : '#f44336');
                    ?>
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <h3><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $type))); ?></h3>
                            <span class="kpi-achievement" style="color: <?php echo $status_color; ?>">
                                <?php echo number_format($achievement, 1); ?>%
                            </span>
                        </div>
                        <div class="kpi-progress">
                            <div class="kpi-bar">
                                <div class="kpi-fill" style="width: <?php echo min($achievement, 100); ?>%; background: <?php echo $status_color; ?>"></div>
                            </div>
                        </div>
                        <div class="kpi-details">
                            <div>
                                <span class="kpi-label">Actual:</span>
                                <span class="kpi-value"><?php echo number_format($avg_value, 2); ?></span>
                            </div>
                            <div>
                                <span class="kpi-label">Target:</span>
                                <span class="kpi-value"><?php echo number_format($avg_target, 2); ?></span>
                            </div>
                        </div>
                        <div class="kpi-status">
                            <span class="status-dot excellent"></span> <?php echo $data['excellent']; ?> Excellent
                            <span class="status-dot good"></span> <?php echo $data['good']; ?> Good
                            <span class="status-dot warning"></span> <?php echo $data['needs_improvement']; ?> Needs Work
                            <span class="status-dot critical"></span> <?php echo $data['critical']; ?> Critical
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>Performance Metrics Details</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Metric Type</th>
                        <th>Value</th>
                        <th>Target</th>
                        <th>Achievement</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($metrics)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No performance data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($metrics as $metric): ?>
                            <tr>
                                <td><?php echo formatDate($metric['metric_date']); ?></td>
                                <td><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $metric['metric_type']))); ?></strong></td>
                                <td><?php echo number_format($metric['metric_value'], 2); ?></td>
                                <td><?php echo number_format($metric['target_value'], 2); ?></td>
                                <td><?php echo number_format($metric['percentage'], 1); ?>%</td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $metric['status'] == 'excellent' ? 'success' : 
                                            ($metric['status'] == 'good' ? 'info' : 
                                            ($metric['status'] == 'needs_improvement' ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $metric['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($metric['notes'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.kpi-card {
    padding: 20px;
    background: #fafafa;
    border-radius: 8px;
    border-left: 4px solid #2196f3;
}

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.kpi-header h3 {
    margin: 0;
    font-size: 16px;
    color: #424242;
}

.kpi-achievement {
    font-size: 24px;
    font-weight: bold;
}

.kpi-progress {
    margin-bottom: 15px;
}

.kpi-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.kpi-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.kpi-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    padding: 10px;
    background: white;
    border-radius: 4px;
}

.kpi-label {
    font-size: 12px;
    color: #757575;
}

.kpi-value {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #424242;
}

.kpi-status {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #757575;
    flex-wrap: wrap;
}

.status-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 4px;
}

.status-dot.excellent {
    background: #4caf50;
}

.status-dot.good {
    background: #03a9f4;
}

.status-dot.warning {
    background: #ff9800;
}

.status-dot.critical {
    background: #f44336;
}
</style>

<?php include 'includes/footer.php'; ?>
