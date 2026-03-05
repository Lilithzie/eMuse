<?php
require_once '../config/config.php';
checkStaffAuth('tour_guide');

// Get this guide's record
$guide = $pdo->prepare("SELECT g.* FROM tour_guides g JOIN admin_users a ON g.admin_id = a.admin_id WHERE a.admin_id = ?");
$guide->execute([$_SESSION['admin_id']]); $guide = $guide->fetch();

$today = date('Y-m-d');
$upcomingCount = $completedCount = $totalParticipants = 0;

if ($guide) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE guide_id=? AND tour_date>=? AND status='scheduled'");
    $stmt->execute([$guide['guide_id'],$today]); $upcomingCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE guide_id=? AND status='completed'");
    $stmt->execute([$guide['guide_id']]); $completedCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(tb.number_of_people),0) FROM tour_bookings tb JOIN tours t ON tb.tour_id=t.tour_id WHERE t.guide_id=? AND tb.status='confirmed'");
    $stmt->execute([$guide['guide_id']]); $totalParticipants = $stmt->fetchColumn();

    // Today's tours
    $todayTours = $pdo->prepare("SELECT t.*, (SELECT COALESCE(SUM(tb2.number_of_people),0) FROM tour_bookings tb2 WHERE tb2.tour_id=t.tour_id AND tb2.status='confirmed') as booked FROM tours t WHERE t.guide_id=? AND t.tour_date=? ORDER BY t.start_time");
    $todayTours->execute([$guide['guide_id'], $today]);
    $todayList = $todayTours->fetchAll();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Guide Dashboard</h1>
    <p style="color:#666;">Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</p>
</div>

<?php if (!$guide): ?>
<div class="alert alert-warning">⚠ Your account is not linked to a tour guide profile yet. Please contact an administrator.</div>
<?php else: ?>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-content"><h3><?= $upcomingCount ?></h3><p>Upcoming Tours</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="stat-content"><h3><?= $completedCount ?></h3><p>Tours Completed</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-content"><h3><?= $totalParticipants ?></h3><p>Total Participants</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce4ec;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div>
        <div class="stat-content">
            <?php $openIssues = $pdo->prepare("SELECT COUNT(*) FROM tour_issues ti JOIN tours t ON ti.tour_id=t.tour_id WHERE t.guide_id=? AND ti.status='open'"); $openIssues->execute([$guide['guide_id']]); ?>
            <h3><?= $openIssues->fetchColumn() ?></h3><p>Open Issues</p>
        </div>
    </div>
</div>

<!-- Today's Tours -->
<div class="card" style="margin-bottom:2rem;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3>Today's Tours</h3>
        <a href="my-tours.php" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    <?php if ($todayList): ?>
    <table class="data-table">
        <thead><tr><th>Title</th><th>Time</th><th>Capacity</th><th>Booked</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($todayList as $t): ?>
            <tr>
                <td><strong><?= htmlspecialchars($t['title']) ?></strong></td>
                <td><?= date('g:i A', strtotime($t['start_time'])) ?> – <?= date('g:i A', strtotime($t['end_time'])) ?></td>
                <td><?= $t['max_capacity'] ?></td>
                <td><?= $t['booked'] ?> / <?= $t['max_capacity'] ?></td>
                <td>
                    <?php $sc=['scheduled'=>'badge-warning','ongoing'=>'badge-primary','completed'=>'badge-success','cancelled'=>'badge-danger']; ?>
                    <span class="badge <?= $sc[$t['status']] ?? 'badge-warning' ?>"><?= ucfirst($t['status']) ?></span>
                </td>
                <td>
                    <a href="tour-participants.php?tour_id=<?= $t['tour_id'] ?>" class="btn btn-sm btn-secondary">Participants</a>
                    <a href="update-status.php?tour_id=<?= $t['tour_id'] ?>" class="btn btn-sm btn-primary">Update</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No tours scheduled for today.</p>
    <?php endif; ?>
</div>

<!-- Profile Info -->
<div class="card">
    <div class="card-header"><h3>My Guide Profile</h3></div>
    <div style="padding:1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div><label style="color:#999;font-size:.85rem;">Full Name</label><p><?= htmlspecialchars($guide['full_name']) ?></p></div>
        <div><label style="color:#999;font-size:.85rem;">Email</label><p><?= htmlspecialchars($guide['email'] ?? '—') ?></p></div>
        <div><label style="color:#999;font-size:.85rem;">Phone</label><p><?= htmlspecialchars($guide['phone'] ?? '—') ?></p></div>
        <div><label style="color:#999;font-size:.85rem;">Specialization</label><p><?= htmlspecialchars($guide['specialization'] ?? '—') ?></p></div>
        <div><label style="color:#999;font-size:.85rem;">Status</label><p><span class="badge badge-success"><?= ucfirst($guide['status']) ?></span></p></div>
    </div>
</div>

<?php endif; ?>
<?php include 'includes/footer.php'; ?>
