<?php
require_once '../config/config.php';
checkAuth();

$tour = null;
$editMode = false;

if (isset($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM tours WHERE tour_id = ?");
    $stmt->execute([$_GET['id']]);
    $tour = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $guide_id = $_POST['guide_id'] ? (int)$_POST['guide_id'] : null;
    $tour_date = $_POST['tour_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_capacity = (int)$_POST['max_capacity'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'];
    
    if ($editMode) {
        $stmt = $pdo->prepare("UPDATE tours SET title = ?, description = ?, guide_id = ?, tour_date = ?, start_time = ?, end_time = ?, max_capacity = ?, price = ?, status = ? WHERE tour_id = ?");
        $stmt->execute([$title, $description, $guide_id, $tour_date, $start_time, $end_time, $max_capacity, $price, $status, $_GET['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tours (title, description, guide_id, tour_date, start_time, end_time, max_capacity, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $guide_id, $tour_date, $start_time, $end_time, $max_capacity, $price, $status]);
    }
    
    header('Location: tours.php?success=saved');
    exit();
}

$guides = $pdo->query("SELECT * FROM tour_guides WHERE status = 'active' ORDER BY full_name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $editMode ? 'Edit Tour' : 'Create New Tour'; ?></h1>
    <a href="tours.php" class="btn btn-secondary">Back to Tours</a>
</div>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group full-width">
                <label for="title">Tour Title *</label>
                <input type="text" id="title" name="title" required value="<?php echo $tour['title'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="guide_id">Assign Guide *</label>
                <select id="guide_id" name="guide_id" required>
                    <option value="">Select Guide</option>
                    <?php foreach ($guides as $guide): ?>
                        <option value="<?php echo $guide['guide_id']; ?>" 
                            <?php echo ($tour['guide_id'] ?? '') == $guide['guide_id'] ? 'selected' : ''; ?>>
                            <?php echo $guide['full_name']; ?> - <?php echo $guide['specialization']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="scheduled" <?php echo ($tour['status'] ?? '') == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="ongoing" <?php echo ($tour['status'] ?? '') == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="completed" <?php echo ($tour['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($tour['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="tour_date">Tour Date *</label>
                <input type="date" id="tour_date" name="tour_date" required value="<?php echo $tour['tour_date'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="start_time">Start Time *</label>
                <input type="time" id="start_time" name="start_time" required value="<?php echo $tour['start_time'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="end_time">End Time *</label>
                <input type="time" id="end_time" name="end_time" required value="<?php echo $tour['end_time'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="max_capacity">Maximum Capacity *</label>
                <input type="number" id="max_capacity" name="max_capacity" required value="<?php echo $tour['max_capacity'] ?? '20'; ?>" min="1">
            </div>
            
            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" id="price" name="price" step="0.01" required value="<?php echo $tour['price'] ?? '25.00'; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo $tour['description'] ?? ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Tour</button>
            <a href="tours.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
