<?php
require_once '../config/config.php';
checkStaffAuth('maintenance_staff');

$staff   = sanitize($_SESSION['admin_name']);
$success = $error = '';

// Handle assign / schedule repair via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id   = (int)$_POST['equipment_id'];
    $scheduled_date = sanitize($_POST['scheduled_date']);
    $assigned_to    = sanitize($_POST['assigned_to']);
    $description    = trim($_POST['description']);
    $mtype          = sanitize($_POST['maintenance_type'] ?? 'repair');

    if (!$equipment_id || !$scheduled_date || !$description) {
        $error = "Please fill in all required fields.";
    } else {
        $pdo->prepare("
            INSERT INTO maintenance_records (equipment_id, maintenance_type, scheduled_date, performed_by, description, status)
            VALUES (?,?,?,?,?,'scheduled')
        ")->execute([$equipment_id, $mtype, $scheduled_date, $assigned_to ?: $staff, $description]);
        $success = "Repair scheduled and assigned to: " . htmlspecialchars($assigned_to ?: $staff);
    }
}

// All upcoming scheduled records
$scheduled = $pdo->query("
    SELECT mr.*, e.name as equipment_name, e.equipment_type
    FROM maintenance_records mr
    JOIN equipment e ON mr.equipment_id = e.equipment_id
    WHERE mr.status = 'scheduled'
    ORDER BY mr.scheduled_date ASC
")->fetchAll();

// Equipment needing repair
$needsRepair = $pdo->query("SELECT * FROM equipment WHERE status IN ('maintenance','repair') ORDER BY name")->fetchAll();

// Other staff for assignment
$otherStaff = $pdo->query("SELECT full_name FROM admin_users WHERE role='maintenance_staff' ORDER BY full_name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Repair Schedule</h1>
    <p style="color:#666;">Schedule repairs and assign maintenance personnel</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">

    <!-- Schedule Form -->
    <div class="card">
        <div class="card-header"><h3>Schedule a Repair</h3></div>
        <div style="padding:1.5rem;">
            <form method="POST">
                <div class="form-group">
                    <label>Equipment Needing Repair *</label>
                    <select name="equipment_id" class="form-control" required>
                        <option value="">— Select Equipment —</option>
                        <?php
                        $allEq = $pdo->query("SELECT * FROM equipment WHERE status != 'retired' ORDER BY status DESC, name ASC")->fetchAll();
                        foreach ($allEq as $eq):
                            $bg = in_array($eq['status'],['maintenance','repair']) ? ' ⚠' : '';
                        ?>
                        <option value="<?= $eq['equipment_id'] ?>"><?= htmlspecialchars($eq['name']) ?><?= $bg ?> [<?= ucfirst($eq['status']) ?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Repair Type</label>
                        <select name="maintenance_type" class="form-control">
                            <option value="repair">Repair</option>
                            <option value="inspection">Inspection</option>
                            <option value="preventive">Preventive</option>
                            <option value="routine">Routine</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Scheduled Date *</label>
                        <input type="date" name="scheduled_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Assign To</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">— Assign to myself —</option>
                        <?php foreach ($otherStaff as $s): ?>
                        <option value="<?= htmlspecialchars($s['full_name']) ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Work Description *</label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="Describe the repair work to be performed..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#4a148c;">Schedule Repair</button>
            </form>
        </div>
    </div>

    <!-- Equipment Needing Repair -->
    <div class="card">
        <div class="card-header"><h3>🔧 Equipment Requiring Attention (<?= count($needsRepair) ?>)</h3></div>
        <div style="padding:.5rem;">
            <?php if ($needsRepair): ?>
            <?php foreach ($needsRepair as $eq):
                $c = $eq['status']==='repair'?'#c62828':'#f57c00';
            ?>
            <div style="padding:.75rem 1rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <strong><?= htmlspecialchars($eq['name']) ?></strong>
                    <div style="font-size:.85rem;color:#999;"><?= htmlspecialchars($eq['equipment_type']) ?></div>
                </div>
                <span style="background:<?= $c ?>;color:white;padding:.25rem .6rem;border-radius:12px;font-size:.8rem;"><?= ucfirst($eq['status']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p style="padding:1.5rem;text-align:center;color:#999;">All equipment is operational ✓</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scheduled Repairs Calendar View -->
<div class="card">
    <div class="card-header"><h3>Upcoming Repairs & Maintenance</h3></div>
    <?php if ($scheduled): ?>
    <table class="data-table">
        <thead>
            <tr><th>Equipment</th><th>Type</th><th>Scheduled Date</th><th>Assigned To</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($scheduled as $r): ?>
            <tr style="<?= $r['scheduled_date'] < date('Y-m-d') ? 'background:#fff8e1;' : '' ?>">
                <td><strong><?= htmlspecialchars($r['equipment_name']) ?></strong></td>
                <td><?= ucfirst($r['maintenance_type']) ?></td>
                <td>
                    <?= formatDate($r['scheduled_date']) ?>
                    <?php if ($r['scheduled_date'] < date('Y-m-d')): ?>
                    <span class="badge badge-danger" style="margin-left:.25rem;">Overdue</span>
                    <?php elseif ($r['scheduled_date'] === date('Y-m-d')): ?>
                    <span class="badge badge-warning" style="margin-left:.25rem;">Today</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['performed_by']) ?></td>
                <td style="max-width:200px;white-space:normal;font-size:.85rem;"><?= htmlspecialchars(substr($r['description'],0,80)) ?>...</td>
                <td>
                    <a href="my-records.php?edit=<?= $r['maintenance_id'] ?>" class="btn btn-sm btn-secondary">Update</a>
                    <a href="my-records.php?close=<?= $r['maintenance_id'] ?>" class="btn btn-sm" style="background:#2e7d32;color:white;" onclick="return confirm('Mark completed?')">Done</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No scheduled repairs.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
