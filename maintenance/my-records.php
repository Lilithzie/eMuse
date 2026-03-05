<?php
require_once '../config/config.php';
checkStaffAuth('maintenance_staff');

$staff = sanitize($_SESSION['admin_name']);
$success = $error = '';

// Handle close (mark completed)
if (isset($_GET['close'])) {
    $mid = (int)$_GET['close'];
    $pdo->prepare("UPDATE maintenance_records SET status='completed', completed_date=CURDATE() WHERE maintenance_id=? AND performed_by=?")
        ->execute([$mid, $staff]);
    // Update equipment status back to operational
    $eq = $pdo->prepare("SELECT equipment_id FROM maintenance_records WHERE maintenance_id=?");
    $eq->execute([$mid]); $eqId = $eq->fetchColumn();
    if ($eqId) $pdo->prepare("UPDATE equipment SET status='operational' WHERE equipment_id=? AND status='maintenance'")->execute([$eqId]);
    $success = "Record closed – equipment marked as operational.";
}

// Handle update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $mid     = (int)$_POST['maintenance_id'];
    $status  = sanitize($_POST['status']);
    $notes   = trim($_POST['notes']);
    $cost    = (float)($_POST['cost'] ?? 0);
    $completed = ($status === 'completed') ? date('Y-m-d') : null;

    $pdo->prepare("UPDATE maintenance_records SET status=?, notes=?, cost=?, completed_date=? WHERE maintenance_id=? AND performed_by=?")
        ->execute([$status, $notes, $cost, $completed, $mid, $staff]);

    if ($status === 'completed') {
        $eq = $pdo->prepare("SELECT equipment_id FROM maintenance_records WHERE maintenance_id=?");
        $eq->execute([$mid]); $eqId = $eq->fetchColumn();
        if ($eqId) $pdo->prepare("UPDATE equipment SET status='operational' WHERE equipment_id=?")->execute([$eqId]);
    }
    $success = "Record updated successfully.";
}

$filterStatus = $_GET['status'] ?? '';
$where = $filterStatus ? "AND mr.status = " . $pdo->quote($filterStatus) : '';

$records = $pdo->prepare("
    SELECT mr.*, e.name as equipment_name, e.equipment_type, e.location_id,
           l.name as location_name
    FROM maintenance_records mr
    JOIN equipment e ON mr.equipment_id = e.equipment_id
    LEFT JOIN locations l ON e.location_id = l.location_id
    WHERE mr.performed_by = ? $where
    ORDER BY mr.scheduled_date ASC, mr.created_at DESC
");
$records->execute([$staff]);
$recList = $records->fetchAll();

// Selected record for inline update
$editId   = (int)($_GET['edit'] ?? 0);
$editRec  = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT mr.*, e.name as equipment_name FROM maintenance_records mr JOIN equipment e ON mr.equipment_id=e.equipment_id WHERE mr.maintenance_id=? AND mr.performed_by=?");
    $stmt->execute([$editId, $staff]); $editRec = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1>My Maintenance Records</h1>
        <p style="color:#666;">Track and update your assigned maintenance tasks</p>
    </div>
    <a href="record-issue.php" class="btn btn-primary" style="background:#4a148c;">+ New Record</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<?php if ($editRec): ?>
<!-- Inline Edit Form -->
<div class="card" style="margin-bottom:1.5rem;border-left:4px solid #4a148c;">
    <div class="card-header"><h3>Update Record: <?= htmlspecialchars($editRec['equipment_name']) ?></h3></div>
    <div style="padding:1.5rem;">
        <form method="POST">
            <input type="hidden" name="maintenance_id" value="<?= $editRec['maintenance_id'] ?>">
            <input type="hidden" name="update_status" value="1">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="scheduled"   <?= $editRec['status']=='scheduled'?  'selected':'' ?>>Scheduled</option>
                        <option value="in_progress" <?= $editRec['status']=='in_progress'?'selected':'' ?>>In Progress</option>
                        <option value="completed"   <?= $editRec['status']=='completed'?  'selected':'' ?>>Completed ✓</option>
                        <option value="cancelled"   <?= $editRec['status']=='cancelled'?  'selected':'' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Repair Cost (₱)</label>
                    <input type="number" name="cost" class="form-control" step="0.01" value="<?= $editRec['cost'] ?? 0 ?>">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;">
                    <button type="submit" class="btn btn-primary" style="background:#4a148c;width:100%;">Save Changes</button>
                </div>
            </div>
            <div class="form-group">
                <label>Notes / Resolution Details</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($editRec['notes'] ?? '') ?></textarea>
            </div>
        </form>
        <a href="my-records.php" style="font-size:.9rem;color:#666;">← Cancel</a>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <?php foreach ([''      =>'All',
                    'scheduled'  =>'Scheduled',
                    'in_progress'=>'In Progress',
                    'completed'  =>'Completed',
                    'cancelled'  =>'Cancelled'] as $k => $v): ?>
    <a href="?status=<?= $k ?>" class="btn <?= $filterStatus===$k?'btn-primary':'btn-secondary' ?>" style="<?= $filterStatus===$k?'background:#4a148c;':'' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <?php if ($recList): ?>
    <table class="data-table">
        <thead>
            <tr><th>Equipment</th><th>Type</th><th>Location</th><th>Scheduled</th><th>Completed</th><th>Cost</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($recList as $r):
                $sc=['scheduled'=>'badge-warning','in_progress'=>'badge-primary','completed'=>'badge-success','cancelled'=>'badge-danger'];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['equipment_name']) ?></strong><br><small style="color:#999;"><?= htmlspecialchars($r['equipment_type']) ?></small></td>
                <td><?= ucfirst($r['maintenance_type']) ?></td>
                <td><?= htmlspecialchars($r['location_name'] ?? '—') ?></td>
                <td><?= formatDate($r['scheduled_date']) ?></td>
                <td><?= $r['completed_date'] ? formatDate($r['completed_date']) : '—' ?></td>
                <td><?= $r['cost'] ? '₱'.number_format($r['cost'],2) : '—' ?></td>
                <td><span class="badge <?= $sc[$r['status']] ?? '' ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
                <td>
                    <?php if (!in_array($r['status'], ['completed','cancelled'])): ?>
                    <a href="?edit=<?= $r['maintenance_id'] ?>&status=<?= $filterStatus ?>" class="btn btn-sm btn-secondary">Update</a>
                    <a href="?close=<?= $r['maintenance_id'] ?>&status=<?= $filterStatus ?>" class="btn btn-sm" style="background:#2e7d32;color:white;" onclick="return confirm('Mark as completed and close?')">Close</a>
                    <?php else: ?>
                    <span style="color:#999;font-size:.85rem;">Closed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No records found.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
