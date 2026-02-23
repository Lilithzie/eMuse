<?php
require_once '../config/config.php';
checkAuth();

$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$equipment = null;
$isEdit = false;

// Get locations for dropdown
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

if ($equipment_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
    $stmt->execute([$equipment_id]);
    $equipment = $stmt->fetch();
    $isEdit = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $equipment_type = sanitize($_POST['equipment_type']);
    $location_id = $_POST['location_id'] ? (int)$_POST['location_id'] : null;
    $purchase_date = $_POST['purchase_date'] ?: null;
    $warranty_expiry = $_POST['warranty_expiry'] ?: null;
    $status = $_POST['status'];
    $serial_number = sanitize($_POST['serial_number']);
    $description = sanitize($_POST['description']);
    
    if ($isEdit) {
        $stmt = $pdo->prepare("
            UPDATE equipment 
            SET name = ?, equipment_type = ?, location_id = ?, purchase_date = ?, 
                warranty_expiry = ?, status = ?, serial_number = ?, description = ?
            WHERE equipment_id = ?
        ");
        $stmt->execute([$name, $equipment_type, $location_id, $purchase_date, $warranty_expiry, 
                       $status, $serial_number, $description, $equipment_id]);
        header('Location: equipment.php?success=update');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO equipment (name, equipment_type, location_id, purchase_date, 
                                 warranty_expiry, status, serial_number, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $equipment_type, $location_id, $purchase_date, $warranty_expiry, 
                       $status, $serial_number, $description]);
        header('Location: equipment.php?success=create');
    }
    exit();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $isEdit ? 'Edit Equipment' : 'Add New Equipment'; ?></h1>
    <a href="equipment.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Equipment
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" class="form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Equipment Name *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo $equipment['name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="equipment_type">Equipment Type *</label>
                    <input type="text" id="equipment_type" name="equipment_type" class="form-control" 
                           value="<?php echo $equipment['equipment_type'] ?? ''; ?>" required
                           placeholder="e.g., HVAC, Security, Lighting">
                </div>
                
                <div class="form-group">
                    <label for="location_id">Location</label>
                    <select id="location_id" name="location_id" class="form-control">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['location_id']; ?>"
                                <?php echo (isset($equipment['location_id']) && $equipment['location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="operational" <?php echo (isset($equipment['status']) && $equipment['status'] == 'operational') ? 'selected' : ''; ?>>Operational</option>
                        <option value="maintenance" <?php echo (isset($equipment['status']) && $equipment['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="repair" <?php echo (isset($equipment['status']) && $equipment['status'] == 'repair') ? 'selected' : ''; ?>>Repair</option>
                        <option value="retired" <?php echo (isset($equipment['status']) && $equipment['status'] == 'retired') ? 'selected' : ''; ?>>Retired</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="serial_number">Serial Number</label>
                    <input type="text" id="serial_number" name="serial_number" class="form-control" 
                           value="<?php echo $equipment['serial_number'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>
                    <input type="date" id="purchase_date" name="purchase_date" class="form-control" 
                           value="<?php echo $equipment['purchase_date'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="warranty_expiry">Warranty Expiry</label>
                    <input type="date" id="warranty_expiry" name="warranty_expiry" class="form-control" 
                           value="<?php echo $equipment['warranty_expiry'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php echo $equipment['description'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $isEdit ? 'Update Equipment' : 'Add Equipment'; ?>
                </button>
                <a href="equipment.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
