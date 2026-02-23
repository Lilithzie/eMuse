<?php
require_once '../config/config.php';
checkAuth();

// Handle status update
if (isset($_GET['update_status'])) {
    $feedback_id = (int)$_GET['feedback_id'];
    $status = $_GET['update_status'];
    $stmt = $pdo->prepare("UPDATE visitor_feedback SET status = ? WHERE feedback_id = ?");
    $stmt->execute([$status, $feedback_id]);
    header('Location: feedback.php?success=status_updated');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $feedback_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM visitor_feedback WHERE feedback_id = ?");
    $stmt->execute([$feedback_id]);
    header('Location: feedback.php?success=delete');
    exit();
}

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Get all feedback
$sql = "
    SELECT vf.*, fc.name as category_name, au.full_name as responded_by_name
    FROM visitor_feedback vf
    LEFT JOIN feedback_categories fc ON vf.category_id = fc.category_id
    LEFT JOIN admin_users au ON vf.responded_by = au.admin_id
";
if ($status_filter != 'all') {
    $sql .= " WHERE vf.status = ?";
}
$sql .= " ORDER BY vf.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter != 'all') {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}
$feedback_list = $stmt->fetchAll();

// Get statistics
$total_feedback = count($feedback_list);
$pending = count(array_filter($feedback_list, fn($f) => $f['status'] == 'pending'));
$reviewed = count(array_filter($feedback_list, fn($f) => $f['status'] == 'reviewed'));
$avg_rating = count($feedback_list) > 0 ? 
    array_sum(array_map(fn($f) => $f['rating'] ?? 0, $feedback_list)) / count($feedback_list) : 0;

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Visitor Feedback</h1>
    <div style="display: flex; gap: 10px;">
        <a href="feedback-categories.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            Categories
        </a>
        <a href="feedback-reports.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="20" x2="12" y2="10"/>
                <line x1="18" y1="20" x2="18" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="16"/>
            </svg>
            Reports
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php 
        if ($_GET['success'] == 'status_updated') echo 'Feedback status updated successfully!';
        elseif ($_GET['success'] == 'responded') echo 'Response added successfully!';
        elseif ($_GET['success'] == 'delete') echo 'Feedback deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_feedback; ?></h3>
            <p>Total Feedback</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $pending; ?></h3>
            <p>Pending Review</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $reviewed; ?></h3>
            <p>Reviewed</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff9c4;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($avg_rating, 1); ?> / 5</h3>
            <p>Average Rating</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Feedback</h2>
        <div class="card-actions">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="status" class="form-control" style="width: 200px;" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="reviewed" <?php echo $status_filter == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                    <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($feedback_list)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <h3>No Feedback Yet</h3>
                <p>Visitor feedback will appear here.</p>
            </div>
        <?php else: ?>
            <div class="feedback-list">
                <?php foreach ($feedback_list as $feedback): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <div>
                                <h3><?php echo htmlspecialchars($feedback['visitor_name'] ?? 'Anonymous'); ?></h3>
                                <div class="feedback-meta">
                                    <?php if ($feedback['visitor_email']): ?>
                                        <span><?php echo htmlspecialchars($feedback['visitor_email']); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?></span>
                                    <?php if ($feedback['category_name']): ?>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($feedback['category_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-<?php 
                                    echo $feedback['status'] == 'resolved' ? 'success' : 
                                        ($feedback['status'] == 'reviewed' ? 'info' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="rating-display">
                            <?php if ($feedback['rating']): ?>
                                <div class="rating-item">
                                    <strong>Overall:</strong>
                                    <?php echo renderStars($feedback['rating']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($feedback['exhibition_rating']): ?>
                                <div class="rating-item">
                                    <strong>Exhibition:</strong>
                                    <?php echo renderStars($feedback['exhibition_rating']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($feedback['staff_rating']): ?>
                                <div class="rating-item">
                                    <strong>Staff:</strong>
                                    <?php echo renderStars($feedback['staff_rating']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($feedback['facilities_rating']): ?>
                                <div class="rating-item">
                                    <strong>Facilities:</strong>
                                    <?php echo renderStars($feedback['facilities_rating']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="feedback-text">
                            <p><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></p>
                        </div>
                        
                        <?php if ($feedback['recommend']): ?>
                            <div class="recommend-badge">
                                Would recommend: 
                                <strong><?php echo ucfirst($feedback['recommend']); ?></strong>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($feedback['admin_response']): ?>
                            <div class="admin-response">
                                <strong>Response:</strong>
                                <p><?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?></p>
                                <small class="text-muted">
                                    By <?php echo htmlspecialchars($feedback['responded_by_name']); ?> 
                                    on <?php echo date('M d, Y H:i', strtotime($feedback['responded_at'])); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="feedback-actions">
                            <a href="feedback-response.php?id=<?php echo $feedback['feedback_id']; ?>" 
                               class="btn btn-sm btn-primary">
                                Respond
                            </a>
                            <?php if ($feedback['status'] == 'pending'): ?>
                                <a href="?update_status=reviewed&feedback_id=<?php echo $feedback['feedback_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    Mark Reviewed
                                </a>
                            <?php endif; ?>
                            <?php if ($feedback['status'] != 'resolved'): ?>
                                <a href="?update_status=resolved&feedback_id=<?php echo $feedback['feedback_id']; ?>" 
                                   class="btn btn-sm btn-success">
                                    Mark Resolved
                                </a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $feedback['feedback_id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this feedback?')">
                                Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<svg class="star filled" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>';
        } else {
            $stars .= '<svg class="star" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>';
        }
    }
    return $stars;
}
?>

<style>
.feedback-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.feedback-item {
    padding: 20px;
    background: #fafafa;
    border-radius: 8px;
    border-left: 4px solid #2196f3;
}

.feedback-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.feedback-header h3 {
    margin: 0 0 5px 0;
}

.feedback-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #757575;
    flex-wrap: wrap;
}

.rating-display {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.rating-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.star {
    color: #e0e0e0;
}

.star.filled {
    color: #ffc107;
}

.feedback-text {
    margin: 15px 0;
    padding: 15px;
    background: white;
    border-radius: 4px;
}

.recommend-badge {
    display: inline-block;
    padding: 8px 12px;
    background: #e8f5e9;
    border-radius: 4px;
    font-size: 14px;
    margin-bottom: 15px;
}

.admin-response {
    margin: 15px 0;
    padding: 15px;
    background: #e3f2fd;
    border-radius: 4px;
    border-left: 3px solid #2196f3;
}

.admin-response strong {
    display: block;
    margin-bottom: 8px;
    color: #1976d2;
}

.admin-response p {
    margin: 0 0 10px 0;
}

.feedback-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #757575;
}

.empty-state svg {
    margin-bottom: 20px;
    color: #2196f3;
}
</style>

<?php include 'includes/footer.php'; ?>
