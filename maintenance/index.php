<?php
require_once '../config/config.php';
checkStaffAuth('maintenance_staff');

$staff = sanitize($_SESSION['admin_name']);

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status='scheduled'"); $scheduled = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status='in_progress'"); $inProgress = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status='completed' AND completed_date >= CURDATE() - INTERVAL 30 DAY"); $completedRecent = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_alerts WHERE is_acknowledged=0"); $alerts = $stmt->fetchColumn();

// My recent records (by name match - use performed_by)
$myRecords = $pdo->prepare("
    SELECT mr.*, e.name as equipment_name, e.equipment_type
    FROM maintenance_records mr
    JOIN equipment e ON mr.equipment_id = e.equipment_id
    WHERE mr.performed_by = ?
    ORDER BY mr.created_at DESC LIMIT 8
");
$myRecords->execute([$staff]);
$myList = $myRecords->fetchAll();

// Active alerts
$activeAlerts = $pdo->query("
    SELECT ma.*, e.name as equipment_name
    FROM maintenance_alerts ma
    JOIN equipment e ON ma.equipment_id = e.equipment_id
    WHERE ma.is_acknowledged = 0
    ORDER BY ma.created_at DESC LIMIT 5
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Maintenance Dashboard</h1>
    <p style="color:#666;">Mabuhay, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</p>
</div>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e5f5;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6a1b9a" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="stat-content"><h3><?= $scheduled ?></h3><p>Scheduled</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
        <div class="stat-content"><h3><?= $inProgress ?></h3><p>In Progress</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="stat-content"><h3><?= $completedRecent ?></h3><p>Completed (30d)</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ffebee;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
        <div class="stat-content"><h3><?= $alerts ?></h3><p>Active Alerts</p></div>
    </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
    <a href="record-issue.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#4a148c;color:white;text-align:center;padding:1.5rem;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            <p style="margin:.5rem 0 0;color:white;font-weight:600;">Record New Issue</p>
        </div>
    </a>
    <a href="repair-schedule.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#6a1b9a;color:white;text-align:center;padding:1.5rem;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <p style="margin:.5rem 0 0;color:white;font-weight:600;">Repair Schedule</p>
        </div>
    </a>
    <a href="equipment.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#7b1fa2;color:white;text-align:center;padding:1.5rem;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <p style="margin:.5rem 0 0;color:white;font-weight:600;">Equipment List</p>
        </div>
    </a>
</div>

<?php if ($activeAlerts): ?>
<div class="card" style="margin-bottom:1.5rem;border-left:4px solid #c62828;">
    <div class="card-header" style="background:#ffebee;"><h3 style="color:#c62828;">🚨 Active Alerts</h3></div>
    <?php foreach ($activeAlerts as $al): ?>
    <div style="padding:.75rem 1rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <strong><?= htmlspecialchars($al['equipment_name']) ?></strong>
            <span class="badge badge-danger" style="margin-left:.5rem;"><?= ucfirst($al['alert_type']) ?></span>
            <p style="margin:.25rem 0 0;font-size:.85rem;color:#666;"><?= htmlspecialchars($al['message']) ?></p>
        </div>
        <a href="record-issue.php?equipment_id=<?= $al['equipment_id'] ?>" class="btn btn-sm" style="background:#c62828;color:white;">Handle</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- My Recent Records -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3>My Recent Records</h3>
        <a href="my-records.php" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    <?php if ($myList): ?>
    <table class="data-table">
        <thead><tr><th>Equipment</th><th>Type</th><th>Scheduled</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($myList as $r):
                $sc=['scheduled'=>'badge-warning','in_progress'=>'badge-primary','completed'=>'badge-success','cancelled'=>'badge-danger'];
            ?>
            <tr>
                <td><?= htmlspecialchars($r['equipment_name']) ?></td>
                <td><?= ucfirst($r['maintenance_type']) ?></td>
                <td><?= formatDate($r['scheduled_date']) ?></td>
                <td><span class="badge <?= $sc[$r['status']] ?? '' ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
                <td>
                    <?php if ($r['status'] !== 'completed' && $r['status'] !== 'cancelled'): ?>
                    <a href="my-records.php?close=<?= $r['maintenance_id'] ?>" class="btn btn-sm" style="background:#2e7d32;color:white;" onclick="return confirm('Mark as completed?')">Close</a>
                    <?php else: ?>
                    <span style="color:#999;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No maintenance records found assigned to you.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
