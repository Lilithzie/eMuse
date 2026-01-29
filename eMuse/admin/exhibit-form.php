<?php
require_once '../config/config.php';
checkAuth();

$exhibit = null;
$editMode = false;

if (isset($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM exhibits WHERE exhibit_id = ?");
    $stmt->execute([$_GET['id']]);
    $exhibit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $classification_id = (int)$_POST['classification_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    if ($editMode) {
        $stmt = $pdo->prepare("UPDATE exhibits SET title = ?, description = ?, classification_id = ?, start_date = ?, end_date = ?, status = ? WHERE exhibit_id = ?");
        $stmt->execute([$title, $description, $classification_id, $start_date, $end_date, $status, $_GET['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO exhibits (title, description, classification_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $classification_id, $start_date, $end_date, $status]);
    }
    
    header('Location: exhibits.php?success=saved');
    exit();
}

$classifications = $pdo->query("SELECT * FROM exhibit_classifications ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $editMode ? 'Edit Exhibit' : 'Add New Exhibit'; ?></h1>
    <a href="exhibits.php" class="btn btn-secondary">Back to Exhibits</a>
</div>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group full-width">
                <label for="title">Exhibit Title *</label>
                <input type="text" id="title" name="title" required value="<?php echo $exhibit['title'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="classification_id">Classification *</label>
                <select id="classification_id" name="classification_id" required>
                    <option value="">Select Classification</option>
                    <?php foreach ($classifications as $class): ?>
                        <option value="<?php echo $class['classification_id']; ?>" 
                            <?php echo ($exhibit['classification_id'] ?? '') == $class['classification_id'] ? 'selected' : ''; ?>>
                            <?php echo $class['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="upcoming" <?php echo ($exhibit['status'] ?? '') == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="active" <?php echo ($exhibit['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="closed" <?php echo ($exhibit['status'] ?? '') == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date *</label>
                <input type="date" id="start_date" name="start_date" required value="<?php echo $exhibit['start_date'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date *</label>
                <input type="date" id="end_date" name="end_date" required value="<?php echo $exhibit['end_date'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo $exhibit['description'] ?? ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Exhibit</button>
            <a href="exhibits.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
