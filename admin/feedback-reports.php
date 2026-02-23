<?php
require_once '../config/config.php';
checkAuth();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get feedback statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_feedback,
        AVG(rating) as avg_rating,
        AVG(exhibition_rating) as avg_exhibition,
        AVG(staff_rating) as avg_staff,
        AVG(facilities_rating) as avg_facilities,
        SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as neutral,
        SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negative,
        SUM(CASE WHEN recommend = 'yes' THEN 1 ELSE 0 END) as would_recommend,
        SUM(CASE WHEN admin_response IS NOT NULL THEN 1 ELSE 0 END) as responded
    FROM visitor_feedback
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$stats = $stmt->fetch();

// Get feedback by category
$categoryStats = $pdo->prepare("
    SELECT fc.name, COUNT(vf.feedback_id) as count, AVG(vf.rating) as avg_rating
    FROM visitor_feedback vf
    LEFT JOIN feedback_categories fc ON vf.category_id = fc.category_id
    WHERE vf.created_at BETWEEN ? AND ?
    GROUP BY fc.category_id, fc.name
    ORDER BY count DESC
");
$categoryStats->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$categoryData = $categoryStats->fetchAll();

// Get recent feedback
$recentFeedback = $pdo->prepare("
    SELECT * FROM visitor_feedback 
    WHERE created_at BETWEEN ? AND ?
    ORDER BY created_at DESC
    LIMIT 10
");
$recentFeedback->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$recent = $recentFeedback->fetchAll();

$response_rate = $stats['total_feedback'] > 0 ? 
    ($stats['responded'] / $stats['total_feedback']) * 100 : 0;

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Feedback Reports & Analytics</h1>
    <a href="feedback.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Feedback
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
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <a href="feedback-reports.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['total_feedback']; ?></h3>
            <p>Total Feedback</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff9c4;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['avg_rating'], 2); ?> / 5</h3>
            <p>Average Rating</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['would_recommend']; ?></h3>
            <p>Would Recommend</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e5f5;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($response_rate, 1); ?>%</h3>
            <p>Response Rate</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h2>Sentiment Distribution</h2>
        </div>
        <div class="card-body">
            <div class="sentiment-chart">
                <div class="sentiment-bar positive" style="width: <?php echo $stats['total_feedback'] > 0 ? ($stats['positive'] / $stats['total_feedback'] * 100) : 0; ?>%">
                    <span>Positive: <?php echo $stats['positive']; ?></span>
                </div>
                <div class="sentiment-bar neutral" style="width: <?php echo $stats['total_feedback'] > 0 ? ($stats['neutral'] / $stats['total_feedback'] * 100) : 0; ?>%">
                    <span>Neutral: <?php echo $stats['neutral']; ?></span>
                </div>
                <div class="sentiment-bar negative" style="width: <?php echo $stats['total_feedback'] > 0 ? ($stats['negative'] / $stats['total_feedback'] * 100) : 0; ?>%">
                    <span>Negative: <?php echo $stats['negative']; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Rating Breakdown</h2>
        </div>
        <div class="card-body">
            <div class="rating-breakdown">
                <div class="rating-item">
                    <span>Overall Experience</span>
                    <div class="rating-bar">
                        <div class="rating-fill" style="width: <?php echo ($stats['avg_rating'] / 5 * 100); ?>%"></div>
                    </div>
                    <span class="rating-value"><?php echo number_format($stats['avg_rating'], 2); ?></span>
                </div>
                <div class="rating-item">
                    <span>Exhibition Quality</span>
                    <div class="rating-bar">
                        <div class="rating-fill" style="width: <?php echo ($stats['avg_exhibition'] / 5 * 100); ?>%"></div>
                    </div>
                    <span class="rating-value"><?php echo number_format($stats['avg_exhibition'], 2); ?></span>
                </div>
                <div class="rating-item">
                    <span>Staff Service</span>
                    <div class="rating-bar">
                        <div class="rating-fill" style="width: <?php echo ($stats['avg_staff'] / 5 * 100); ?>%"></div>
                    </div>
                    <span class="rating-value"><?php echo number_format($stats['avg_staff'], 2); ?></span>
                </div>
                <div class="rating-item">
                    <span>Facilities</span>
                    <div class="rating-bar">
                        <div class="rating-fill" style="width: <?php echo ($stats['avg_facilities'] / 5 * 100); ?>%"></div>
                    </div>
                    <span class="rating-value"><?php echo number_format($stats['avg_facilities'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($categoryData)): ?>
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>Feedback by Category</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Count</th>
                            <th>Average Rating</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryData as $cat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['name'] ?? 'Uncategorized'); ?></strong></td>
                                <td><?php echo $cat['count']; ?></td>
                                <td><?php echo number_format($cat['avg_rating'], 2); ?> / 5</td>
                                <td>
                                    <?php 
                                    $percentage = $stats['total_feedback'] > 0 ? 
                                        ($cat['count'] / $stats['total_feedback'] * 100) : 0;
                                    echo number_format($percentage, 1); 
                                    ?>%
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

.sentiment-chart {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.sentiment-bar {
    padding: 15px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
    min-width: 100px;
    text-align: center;
}

.sentiment-bar.positive {
    background: linear-gradient(90deg, #4caf50 0%, #66bb6a 100%);
}

.sentiment-bar.neutral {
    background: linear-gradient(90deg, #ff9800 0%, #ffb74d 100%);
}

.sentiment-bar.negative {
    background: linear-gradient(90deg, #f44336 0%, #ef5350 100%);
}

.rating-breakdown {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.rating-item {
    display: grid;
    grid-template-columns: 150px 1fr 50px;
    gap: 15px;
    align-items: center;
}

.rating-bar {
    height: 24px;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}

.rating-fill {
    height: 100%;
    background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
    border-radius: 12px;
    transition: width 0.3s ease;
}

.rating-value {
    font-weight: bold;
    color: #424242;
    text-align: right;
}
</style>

<?php include 'includes/footer.php'; ?>
