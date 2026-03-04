<?php
require_once '../config/config.php';
checkAuth();

$equipment_filter = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] != '') {
    $maintenance_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM maintenance_records WHERE maintenance_id = ?");
    $stmt->execute([$maintenance_id]);
    header('Location: maintenance-records.php?success=delete');
    exit();
}

// Get all equipment for filter
$equipment_list = $pdo->query("SELECT equipment_id, name FROM equipment ORDER BY name")->fetchAll();

// Build query with optional filter
$sql = "
    SELECT mr.*, e.name as equipment_name, e.equipment_type
    FROM maintenance_records mr
    JOIN equipment e ON mr.equipment_id = e.equipment_id
";
if ($equipment_filter > 0) {
    $sql .= " WHERE mr.equipment_id = ?";
}
$sql .= " ORDER BY mr.scheduled_date DESC, mr.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($equipment_filter > 0) {
    $stmt->execute([$equipment_filter]);
} else {
    $stmt->execute();
}
$records = $stmt->fetchAll();

// Get statistics
$total_records = count($records);
$scheduled = count(array_filter($records, fn($r) => $r['status'] == 'scheduled'));
$in_progress = count(array_filter($records, fn($r) => $r['status'] == 'in_progress'));
$completed = count(array_filter($records, fn($r) => $r['status'] == 'completed'));

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Maintenance Records</h1>
    <a href="maintenance-form.php<?php echo $equipment_filter ? '?equipment_id='.$equipment_filter : ''; ?>" class="btn btn-primary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Schedule Maintenance
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php 
        if ($_GET['success'] == 'create') echo 'Maintenance scheduled successfully!';
        elseif ($_GET['success'] == 'update') echo 'Maintenance record updated successfully!';
        elseif ($_GET['success'] == 'delete') echo 'Maintenance record deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_records; ?></h3>
            <p>Total Records</p>
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
            <h3><?php echo $scheduled; ?></h3>
            <p>Scheduled</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e1f5fe;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#03a9f4" stroke-width="2">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $in_progress; ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $completed; ?></h3>
            <p>Completed</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Maintenance History</h2>
        <div class="card-actions">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="equipment_id" class="form-control" style="width: 250px;" onchange="this.form.submit()">
                    <option value="">All Equipment</option>
                    <?php foreach ($equipment_list as $eq): ?>
                        <option value="<?php echo $eq['equipment_id']; ?>" 
                            <?php echo $equipment_filter == $eq['equipment_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($eq['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($equipment_filter): ?>
                    <a href="maintenance-records.php" class="btn btn-secondary">Clear Filter</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Equipment</th>
                        <th>Type</th>
                        <th>Scheduled Date</th>
                        <th>Completed Date</th>
                        <th>Performed By</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No maintenance records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo $record['maintenance_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($record['equipment_name']); ?></strong></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($record['maintenance_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($record['scheduled_date']); ?></td>
                                <td><?php echo $record['completed_date'] ? formatDate($record['completed_date']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($record['performed_by'] ?? 'Not assigned'); ?></td>
                                <td><?php echo $record['cost'] ? formatCurrency($record['cost']) : '-'; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $record['status'] == 'completed' ? 'success' : 
                                            ($record['status'] == 'in_progress' ? 'primary' : 
                                            ($record['status'] == 'scheduled' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="maintenance-form.php?id=<?php echo $record['maintenance_id']; ?>" class="btn-icon" title="Edit">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <a href="?delete=<?php echo $record['maintenance_id']; ?><?php echo $equipment_filter ? '&equipment_id='.$equipment_filter : ''; ?>" 
                                           class="btn-icon btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this record?')"
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
