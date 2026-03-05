<?php
require_once '../config/config.php';
checkStaffAuth('ticketing_staff');

$today = date('Y-m-d');

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE visit_date=? AND status='used'");
$stmt->execute([$today]); $todayUsed = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE visit_date=? AND status='confirmed'");
$stmt->execute([$today]); $todayPending = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM entry_log WHERE DATE(scan_time)=?");
$stmt->execute([$today]); $todayScans = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE visit_date=? AND status IN ('confirmed','used')");
$stmt->execute([$today]); $todayTotal = $stmt->fetchColumn();

// Recent scans
$recentScans = $pdo->prepare("
    SELECT el.*, t.visitor_name, t.ticket_type, t.ticket_code, au.full_name as scanned_by_name
    FROM entry_log el
    JOIN tickets t ON el.ticket_id = t.ticket_id
    JOIN admin_users au ON el.scanned_by = au.admin_id
    WHERE DATE(el.scan_time) = ?
    ORDER BY el.scan_time DESC LIMIT 10
");
$recentScans->execute([$today]);
$recentList = $recentScans->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Ticketing Dashboard</h1>
    <p style="color:#666;">Monitor today's visitor entry – <?= date('l, F j, Y') ?></p>
</div>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-content"><h3><?= $todayUsed ?></h3><p>Entered Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/></svg>
        </div>
        <div class="stat-content"><h3><?= $todayPending ?></h3><p>Awaiting Entry</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-content"><h3><?= $todayScans ?></h3><p>QR Scans Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce4ec;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <div class="stat-content"><h3><?= $todayTotal ?></h3><p>Total Tickets Today</p></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
    <a href="scanner.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#1565c0;color:white;text-align:center;padding:2rem;cursor:pointer;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <h3 style="margin:1rem 0 0.25rem;color:white;">Scan / Validate Ticket</h3>
            <p style="color:rgba(255,255,255,.7);margin:0;">Scan QR code or enter ticket code</p>
        </div>
    </a>
    <a href="entry-log.php" style="text-decoration:none;">
        <div class="stat-card" style="background:#0277bd;color:white;text-align:center;padding:2rem;cursor:pointer;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <h3 style="margin:1rem 0 0.25rem;color:white;">View Entry Log</h3>
            <p style="color:rgba(255,255,255,.7);margin:0;">Full entry history for today</p>
        </div>
    </a>
</div>

<div class="card">
    <div class="card-header"><h3>Recent Scans (Today)</h3></div>
    <?php if ($recentList): ?>
    <table class="data-table">
        <thead><tr><th>Time</th><th>Ticket Code</th><th>Visitor</th><th>Type</th><th>Entry Type</th><th>Scanned By</th></tr></thead>
        <tbody>
            <?php foreach ($recentList as $row): ?>
            <tr>
                <td><?= date('h:i A', strtotime($row['scan_time'])) ?></td>
                <td><code><?= htmlspecialchars($row['ticket_code']) ?></code></td>
                <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                <td><?= ucfirst($row['ticket_type']) ?></td>
                <td><span class="badge <?= $row['entry_type']=='entry'?'badge-success':'badge-warning' ?>"><?= ucfirst($row['entry_type']) ?></span></td>
                <td><?= htmlspecialchars($row['scanned_by_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="padding:1rem;color:#666;">No scans recorded yet today.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
