<?php
require_once '../config/config.php';
checkStaffAuth('maintenance_staff');

$success = $error = '';
$preselect_eq = (int)($_GET['equipment_id'] ?? 0);

// GET equipment list
$eqList = $pdo->query("SELECT * FROM equipment WHERE status != 'retired' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id     = (int)$_POST['equipment_id'];
    $maintenance_type = sanitize($_POST['maintenance_type']);
    $scheduled_date   = sanitize($_POST['scheduled_date']);
    $description      = trim($_POST['description']);
    $is_urgent        = isset($_POST['is_urgent']);
    $performed_by     = sanitize($_SESSION['admin_name']);

    if (!$equipment_id || !$scheduled_date || !$description) {
        $error = "Please fill in all required fields.";
    } else {
        $pdo->prepare("
            INSERT INTO maintenance_records 
            (equipment_id, maintenance_type, scheduled_date, performed_by, description, status)
            VALUES (?,?,?,?,?,?)
        ")->execute([$equipment_id, $maintenance_type, $scheduled_date, $performed_by, $description, 'scheduled']);

        $new_id = $pdo->lastInsertId();

        // If urgent, create maintenance alert
        if ($is_urgent) {
            $eqName = $pdo->prepare("SELECT name FROM equipment WHERE equipment_id=?");
            $eqName->execute([$equipment_id]); $eqName = $eqName->fetchColumn();

            $pdo->prepare("INSERT INTO maintenance_alerts (equipment_id, alert_type, message, due_date) VALUES (?,?,?,?)")
                ->execute([$equipment_id, 'urgent', "URGENT: $description (reported by $performed_by)", $scheduled_date]);

            // Update equipment status to maintenance
            $pdo->prepare("UPDATE equipment SET status='maintenance' WHERE equipment_id=?")->execute([$equipment_id]);
        }

        $success = "Maintenance record created successfully!" . ($is_urgent ? " An urgent alert has been raised." : "");
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Record Equipment Issue</h1>
    <p style="color:#666;">Create a maintenance record for equipment problems</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;">
    <div class="card">
        <div class="card-header"><h3>New Maintenance Record</h3></div>
        <div style="padding:1.5rem;">
            <form method="POST">
                <div class="form-group">
                    <label>Equipment *</label>
                    <select name="equipment_id" class="form-control" required>
                        <option value="">— Select Equipment —</option>
                        <?php foreach ($eqList as $eq): ?>
                        <option value="<?= $eq['equipment_id'] ?>" <?= $preselect_eq==$eq['equipment_id']?'selected':'' ?>>
                            <?= htmlspecialchars($eq['name']) ?> [<?= ucfirst($eq['status']) ?>]
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Maintenance Type *</label>
                        <select name="maintenance_type" class="form-control" required>
                            <option value="repair">Repair</option>
                            <option value="inspection">Inspection</option>
                            <option value="routine">Routine Maintenance</option>
                            <option value="preventive">Preventive Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Scheduled Date *</label>
                        <input type="date" name="scheduled_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Issue Description *</label>
                    <textarea name="description" class="form-control" rows="5" required
                              placeholder="Describe the problem in detail: what is malfunctioning, when it was noticed, visible damage, error indicators..."></textarea>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="is_urgent" style="width:auto;">
                        <span>⚠ Mark as <strong>URGENT</strong> – requires immediate attention / creates alert</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="background:#4a148c;">Submit Record</button>
            </form>
        </div>
    </div>

    <!-- Equipment Status Summary -->
    <div class="card">
        <div class="card-header"><h3>Equipment Status</h3></div>
        <div style="padding:1rem;">
            <?php
            $statSummary = $pdo->query("SELECT status, COUNT(*) as cnt FROM equipment GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
            $colors = ['operational'=>'#2e7d32','maintenance'=>'#f57c00','repair'=>'#c62828','retired'=>'#999'];
            foreach ($statSummary as $s => $c):
            ?>
            <div style="display:flex;justify-content:space-between;padding:.6rem .25rem;border-bottom:1px solid #eee;">
                <span style="color:<?= $colors[$s]??'#333' ?>;font-weight:600;"><?= ucfirst($s) ?></span>
                <span style="background:<?= $colors[$s]??'#333' ?>;color:white;padding:.2rem .6rem;border-radius:12px;font-size:.85rem;"><?= $c ?></span>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:1rem;">
                <a href="equipment.php" class="btn btn-secondary btn-sm" style="width:100%;text-align:center;">View All Equipment →</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
