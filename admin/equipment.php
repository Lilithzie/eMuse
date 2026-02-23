<?php
require_once '../config/config.php';
checkAuth();

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] != '') {
    $equipment_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
    $stmt->execute([$equipment_id]);
    header('Location: equipment.php?success=delete');
    exit();
}

// Get all equipment
$stmt = $pdo->query("
    SELECT e.*, l.name as location_name 
    FROM equipment e 
    LEFT JOIN locations l ON e.location_id = l.location_id 
    ORDER BY e.created_at DESC
");
$equipment_list = $stmt->fetchAll();

// Get statistics
$total_equipment = $pdo->query("SELECT COUNT(*) as total FROM equipment")->fetch()['total'];
$operational = $pdo->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'operational'")->fetch()['total'];
$maintenance = $pdo->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'maintenance'")->fetch()['total'];
$repair = $pdo->query("SELECT COUNT(*) as total FROM equipment WHERE status = 'repair'")->fetch()['total'];

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Equipment Management</h1>
    <a href="equipment-form.php" class="btn btn-primary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Equipment
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php 
        if ($_GET['success'] == 'create') echo 'Equipment added successfully!';
        elseif ($_GET['success'] == 'update') echo 'Equipment updated successfully!';
        elseif ($_GET['success'] == 'delete') echo 'Equipment deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_equipment; ?></h3>
            <p>Total Equipment</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $operational; ?></h3>
            <p>Operational</p>
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
            <h3><?php echo $maintenance; ?></h3>
            <p>In Maintenance</p>
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
            <h3><?php echo $repair; ?></h3>
            <p>Needs Repair</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Equipment</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Purchase Date</th>
                        <th>Status</th>
                        <th>Serial Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($equipment_list)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No equipment found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipment_list as $equipment): ?>
                            <tr>
                                <td><?php echo $equipment['equipment_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($equipment['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($equipment['equipment_type']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['location_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $equipment['purchase_date'] ? formatDate($equipment['purchase_date']) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $equipment['status'] == 'operational' ? 'success' : 
                                            ($equipment['status'] == 'maintenance' ? 'warning' : 
                                            ($equipment['status'] == 'repair' ? 'danger' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($equipment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($equipment['serial_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="equipment-form.php?id=<?php echo $equipment['equipment_id']; ?>" class="btn-icon" title="Edit">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <a href="maintenance-records.php?equipment_id=<?php echo $equipment['equipment_id']; ?>" class="btn-icon" title="Maintenance Records">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                                <line x1="12" y1="18" x2="12" y2="12"/>
                                                <line x1="9" y1="15" x2="15" y2="15"/>
                                            </svg>
                                        </a>
                                        <a href="?delete=<?php echo $equipment['equipment_id']; ?>" 
                                           class="btn-icon btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this equipment?')"
                                           title="Delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
