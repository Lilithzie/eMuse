<?php
require_once '../config/config.php';
checkStaffAuth('maintenance_staff');
$equipList = $pdo->query("SELECT e.*, l.name as location_name FROM equipment e LEFT JOIN locations l ON e.location_id=l.location_id ORDER BY e.status ASC, e.name ASC")->fetchAll();
include 'includes/header.php';
?>
<div class="page-header"><h1>Equipment List</h1><p style="color:#666;">Overview of all museum equipment and their current status</p></div>
<?php
$totals = ['operational'=>0,'maintenance'=>0,'repair'=>0,'retired'=>0];
foreach ($equipList as $e) { if (isset($totals[$e['status']])) $totals[$e['status']]++; }
?>
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card"><div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div class="stat-content"><h3><?= $totals['operational'] ?></h3><p>Operational</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#fff3e0;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="stat-content"><h3><?= $totals['maintenance'] ?></h3><p>In Maintenance</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#ffebee;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div><div class="stat-content"><h3><?= $totals['repair'] ?></h3><p>Needs Repair</p></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#f5f5f5;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div><div class="stat-content"><h3><?= $totals['retired'] ?></h3><p>Retired</p></div></div>
</div>
<div class="card">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Type</th><th>Location</th><th>Serial No.</th><th>Purchase Date</th><th>Warranty</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($equipList as $e):
                $sc=['operational'=>'badge-success','maintenance'=>'badge-warning','repair'=>'badge-danger','retired'=>''];
                $we = $e['warranty_expiry'];
                $warnColor = ($we && $we < date('Y-m-d')) ? 'color:#c62828;font-weight:600;' : ($we && $we < date('Y-m-d', strtotime('+90 days')) ? 'color:#f57c00;font-weight:600;' : '');
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($e['name']) ?></strong></td>
                <td><?= htmlspecialchars($e['equipment_type']) ?></td>
                <td><?= htmlspecialchars($e['location_name'] ?? '—') ?></td>
                <td><code><?= htmlspecialchars($e['serial_number'] ?? '—') ?></code></td>
                <td><?= $e['purchase_date'] ? formatDate($e['purchase_date']) : '—' ?></td>
                <td style="<?= $warnColor ?>"><?= $e['warranty_expiry'] ? formatDate($e['warranty_expiry']) : '—' ?></td>
                <td><span class="badge <?= $sc[$e['status']] ?? '' ?>"><?= ucfirst($e['status']) ?></span></td>
                <td><a href="record-issue.php?equipment_id=<?= $e['equipment_id'] ?>" class="btn btn-sm btn-secondary">Record Issue</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
