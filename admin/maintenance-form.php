<?php
require_once '../config/config.php';
checkAuth();

$maintenance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$record = null;
$isEdit = false;

$equipment_id_param = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;

// Get all equipment for dropdown
$equipment_list = $pdo->query("SELECT equipment_id, name, equipment_type FROM equipment ORDER BY name")->fetchAll();

if ($maintenance_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_records WHERE maintenance_id = ?");
    $stmt->execute([$maintenance_id]);
    $record = $stmt->fetch();
    $isEdit = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_id = (int)$_POST['equipment_id'];
    $maintenance_type = $_POST['maintenance_type'];
    $scheduled_date = $_POST['scheduled_date'];
    $completed_date = $_POST['completed_date'] ?: null;
    $performed_by = sanitize($_POST['performed_by']);
    $cost = $_POST['cost'] ? (float)$_POST['cost'] : null;
    $description = sanitize($_POST['description']);
    $notes = sanitize($_POST['notes']);
    $status = $_POST['status'];
    
    if ($isEdit) {
        $stmt = $pdo->prepare("
            UPDATE maintenance_records 
            SET equipment_id = ?, maintenance_type = ?, scheduled_date = ?, completed_date = ?,
                performed_by = ?, cost = ?, description = ?, notes = ?, status = ?
            WHERE maintenance_id = ?
        ");
        $stmt->execute([$equipment_id, $maintenance_type, $scheduled_date, $completed_date,
                       $performed_by, $cost, $description, $notes, $status, $maintenance_id]);
        header('Location: maintenance-records.php?success=update');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_records (equipment_id, maintenance_type, scheduled_date, completed_date,
                                           performed_by, cost, description, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$equipment_id, $maintenance_type, $scheduled_date, $completed_date,
                       $performed_by, $cost, $description, $notes, $status]);
        header('Location: maintenance-records.php?success=create');
    }
    exit();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $isEdit ? 'Edit Maintenance Record' : 'Schedule Maintenance'; ?></h1>
    <a href="maintenance-records.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Records
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" class="form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="equipment_id">Equipment *</label>
                    <select id="equipment_id" name="equipment_id" class="form-control" required>
                        <option value="">Select Equipment</option>
                        <?php foreach ($equipment_list as $eq): ?>
                            <option value="<?php echo $eq['equipment_id']; ?>"
                                <?php echo (($isEdit && $record['equipment_id'] == $eq['equipment_id']) || 
                                          (!$isEdit && $equipment_id_param == $eq['equipment_id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($eq['name']) . ' (' . $eq['equipment_type'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="maintenance_type">Maintenance Type *</label>
                    <select id="maintenance_type" name="maintenance_type" class="form-control" required>
                        <option value="routine" <?php echo (isset($record['maintenance_type']) && $record['maintenance_type'] == 'routine') ? 'selected' : ''; ?>>Routine</option>
                        <option value="preventive" <?php echo (isset($record['maintenance_type']) && $record['maintenance_type'] == 'preventive') ? 'selected' : ''; ?>>Preventive</option>
                        <option value="repair" <?php echo (isset($record['maintenance_type']) && $record['maintenance_type'] == 'repair') ? 'selected' : ''; ?>>Repair</option>
                        <option value="inspection" <?php echo (isset($record['maintenance_type']) && $record['maintenance_type'] == 'inspection') ? 'selected' : ''; ?>>Inspection</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="scheduled_date">Scheduled Date *</label>
                    <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" 
                           value="<?php echo $record['scheduled_date'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="completed_date">Completed Date</label>
                    <input type="date" id="completed_date" name="completed_date" class="form-control" 
                           value="<?php echo $record['completed_date'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="performed_by">Performed By</label>
                    <input type="text" id="performed_by" name="performed_by" class="form-control" 
                           value="<?php echo $record['performed_by'] ?? ''; ?>"
                           placeholder="Technician name or company">
                </div>
                
                <div class="form-group">
                    <label for="cost">Cost (₱)</label>
                    <input type="number" id="cost" name="cost" class="form-control" step="0.01" min="0"
                           value="<?php echo $record['cost'] ?? ''; ?>"
                           placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="scheduled" <?php echo (!isset($record['status']) || $record['status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="in_progress" <?php echo (isset($record['status']) && $record['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo (isset($record['status']) && $record['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo (isset($record['status']) && $record['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" class="form-control" rows="3" required><?php echo $record['description'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo $record['notes'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $isEdit ? 'Update Record' : 'Schedule Maintenance'; ?>
                </button>
                <a href="maintenance-records.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
