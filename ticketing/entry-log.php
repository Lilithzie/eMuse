<?php
require_once '../config/config.php';
checkStaffAuth('ticketing_staff');

$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterType = $_GET['type'] ?? '';

$params = [$filterDate];
$typeWhere = '';
if ($filterType) { $typeWhere = 'AND el.entry_type = ?'; $params[] = $filterType; }

$logs = $pdo->prepare("
    SELECT el.*, t.visitor_name, t.ticket_type, t.ticket_code, t.visitor_email,
           au.full_name as scanned_by_name
    FROM entry_log el
    JOIN tickets t ON el.ticket_id = t.ticket_id
    JOIN admin_users au ON el.scanned_by = au.admin_id
    WHERE DATE(el.scan_time) = ? $typeWhere
    ORDER BY el.scan_time DESC
");
$logs->execute($params);
$entries = $logs->fetchAll();

// Counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM entry_log WHERE DATE(scan_time)=? AND entry_type='entry'");
$stmt->execute([$filterDate]); $entryCount = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM entry_log WHERE DATE(scan_time)=? AND entry_type='exit'");
$stmt->execute([$filterDate]); $exitCount = $stmt->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1>Entry Log</h1>
        <p style="color:#666;">Complete record of all ticket scans and visitor movements</p>
    </div>
    <a href="?date=<?= $filterDate ?>&export=1" class="btn btn-secondary" onclick="window.print();return false;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Log
    </a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:1.5rem;">
    <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;padding:1.25rem;">
        <div class="form-group" style="margin:0;flex:1;">
            <label>Date</label>
            <input type="date" name="date" value="<?= $filterDate ?>" class="form-control">
        </div>
        <div class="form-group" style="margin:0;flex:1;">
            <label>Entry Type</label>
            <select name="type" class="form-control">
                <option value="">All</option>
                <option value="entry" <?= $filterType=='entry'?'selected':'' ?>>Entry</option>
                <option value="exit"  <?= $filterType=='exit'?'selected':'' ?>>Exit</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
        <div class="stat-content"><h3><?= $entryCount ?></h3><p>Entries on <?= date('M j', strtotime($filterDate)) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/></svg></div>
        <div class="stat-content"><h3><?= $exitCount ?></h3><p>Exits on <?= date('M j', strtotime($filterDate)) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-content"><h3><?= max(0,$entryCount - $exitCount) ?></h3><p>Currently Inside</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce4ec;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></div>
        <div class="stat-content"><h3><?= count($entries) ?></h3><p>Total Log Records</p></div>
    </div>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-header"><h3>Scan Records – <?= date('F j, Y', strtotime($filterDate)) ?></h3></div>
    <?php if ($entries): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th><th>Time</th><th>Ticket Code</th><th>Visitor Name</th>
                <th>Email</th><th>Ticket Type</th><th>Entry Type</th><th>Scanned By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $i => $row): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= date('h:i:s A', strtotime($row['scan_time'])) ?></td>
                <td><code><?= htmlspecialchars($row['ticket_code']) ?></code></td>
                <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                <td><?= htmlspecialchars($row['visitor_email'] ?? '—') ?></td>
                <td><?= ucfirst($row['ticket_type']) ?></td>
                <td>
                    <span class="badge <?= $row['entry_type']=='entry'?'badge-success':'badge-warning' ?>">
                        <?= $row['entry_type']=='entry' ? '→ Entry' : '← Exit' ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($row['scanned_by_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="padding:2rem;text-align:center;color:#999;">No entry records found for this date / filter.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
