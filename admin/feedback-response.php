<?php
require_once '../config/config.php';
checkAuth();

$feedback_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$feedback_id) {
    header('Location: feedback.php');
    exit();
}

// Get feedback
$stmt = $pdo->prepare("
    SELECT vf.*, fc.name as category_name
    FROM visitor_feedback vf
    LEFT JOIN feedback_categories fc ON vf.category_id = fc.category_id
    WHERE vf.feedback_id = ?
");
$stmt->execute([$feedback_id]);
$feedback = $stmt->fetch();

if (!$feedback) {
    header('Location: feedback.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_response = sanitize($_POST['admin_response']);
    $responded_by = $_SESSION['admin_id'];
    
    $stmt = $pdo->prepare("
        UPDATE visitor_feedback 
        SET admin_response = ?, responded_by = ?, responded_at = NOW(), status = 'reviewed'
        WHERE feedback_id = ?
    ");
    $stmt->execute([$admin_response, $responded_by, $feedback_id]);
    
    header('Location: feedback.php?success=responded');
    exit();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Respond to Feedback</h1>
    <a href="feedback.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Feedback
    </a>
</div>

<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h2>Original Feedback</h2>
    </div>
    <div class="card-body">
        <div class="feedback-detail">
            <div class="detail-row">
                <strong>Visitor:</strong>
                <span><?php echo htmlspecialchars($feedback['visitor_name'] ?? 'Anonymous'); ?></span>
            </div>
            <?php if ($feedback['visitor_email']): ?>
                <div class="detail-row">
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($feedback['visitor_email']); ?></span>
                </div>
            <?php endif; ?>
            <div class="detail-row">
                <strong>Category:</strong>
                <span><?php echo htmlspecialchars($feedback['category_name'] ?? 'Uncategorized'); ?></span>
            </div>
            <div class="detail-row">
                <strong>Date:</strong>
                <span><?php echo date('F d, Y H:i', strtotime($feedback['created_at'])); ?></span>
            </div>
            <?php if ($feedback['visit_date']): ?>
                <div class="detail-row">
                    <strong>Visit Date:</strong>
                    <span><?php echo formatDate($feedback['visit_date']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin: 20px 0;">
            <strong>Ratings:</strong>
            <div class="rating-grid">
                <?php if ($feedback['rating']): ?>
                    <div>Overall: <?php echo $feedback['rating']; ?>/5</div>
                <?php endif; ?>
                <?php if ($feedback['exhibition_rating']): ?>
                    <div>Exhibition: <?php echo $feedback['exhibition_rating']; ?>/5</div>
                <?php endif; ?>
                <?php if ($feedback['staff_rating']): ?>
                    <div>Staff: <?php echo $feedback['staff_rating']; ?>/5</div>
                <?php endif; ?>
                <?php if ($feedback['facilities_rating']): ?>
                    <div>Facilities: <?php echo $feedback['facilities_rating']; ?>/5</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="feedback-content">
            <strong>Feedback:</strong>
            <p><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></p>
        </div>
        
        <?php if ($feedback['recommend']): ?>
            <div class="detail-row">
                <strong>Would Recommend:</strong>
                <span><?php echo ucfirst($feedback['recommend']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($feedback['admin_response']): ?>
            <div class="existing-response">
                <strong>Previous Response:</strong>
                <p><?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><?php echo $feedback['admin_response'] ? 'Update Response' : 'Add Response'; ?></h2>
    </div>
    <div class="card-body">
        <form method="POST" class="form">
            <div class="form-group">
                <label for="admin_response">Response *</label>
                <textarea id="admin_response" name="admin_response" class="form-control" rows="8" required><?php echo $feedback['admin_response'] ?? ''; ?></textarea>
                <small class="form-text">This response will be associated with this feedback record.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Response</button>
                <a href="feedback.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.feedback-detail {
    display: grid;
    gap: 15px;
}

.detail-row {
    display: flex;
    gap: 15px;
}

.detail-row strong {
    min-width: 120px;
    color: #424242;
}

.rating-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 4px;
    margin-top: 10px;
}

.feedback-content {
    margin: 20px 0;
    padding: 20px;
    background: #f5f5f5;
    border-radius: 4px;
}

.feedback-content p {
    margin: 10px 0 0 0;
}

.existing-response {
    margin: 20px 0;
    padding: 15px;
    background: #fff3e0;
    border-radius: 4px;
    border-left: 3px solid #ff9800;
}

.existing-response p {
    margin: 10px 0 0 0;
}

.form-text {
    display: block;
    margin-top: 8px;
    color: #757575;
    font-size: 14px;
}
</style>

<?php include 'includes/footer.php'; ?>
