<?php
require_once '../config/config.php';
checkStaffAuth('tour_guide');

$guide = $pdo->prepare("SELECT g.* FROM tour_guides g JOIN admin_users a ON g.admin_id=a.admin_id WHERE a.admin_id=?");
$guide->execute([$_SESSION['admin_id']]); $guide = $guide->fetch();

$filter = $_GET['filter'] ?? 'upcoming';
$today  = date('Y-m-d');

if (!$guide) { include 'includes/header.php'; echo '<div class="alert alert-warning">Guide profile not linked.</div>'; include 'includes/footer.php'; exit(); }

$where = match($filter) {
    'today'     => "AND t.tour_date = '$today'",
    'completed' => "AND t.status = 'completed'",
    'cancelled' => "AND t.status = 'cancelled'",
    default     => "AND t.tour_date >= '$today' AND t.status NOT IN ('completed','cancelled')",
};

$tours = $pdo->prepare("
    SELECT t.*,
           (SELECT COALESCE(SUM(tb.number_of_people),0) FROM tour_bookings tb WHERE tb.tour_id=t.tour_id AND tb.status='confirmed') as booked
    FROM tours t
    WHERE t.guide_id = ? $where
    ORDER BY t.tour_date ASC, t.start_time ASC
");
$tours->execute([$guide['guide_id']]);
$tourList = $tours->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>My Assigned Tours</h1>
</div>

<!-- Filter Tabs -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;">
    <?php foreach (['upcoming'=>'Upcoming','today'=>'Today','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v): ?>
    <a href="?filter=<?= $k ?>" class="btn <?= $filter==$k?'btn-primary':'btn-secondary' ?>" style="<?= $filter==$k?'background:#2e7d32;':'' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tourList): ?>
<div style="display:grid;gap:1.25rem;">
    <?php foreach ($tourList as $t):
        $spotsLeft = $t['max_capacity'] - $t['booked'];
        $pct = $t['max_capacity'] > 0 ? round($t['booked']/$t['max_capacity']*100) : 0;
    ?>
    <div class="card" style="padding:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
            <div>
                <h3 style="margin:0 0 .25rem;"><?= htmlspecialchars($t['title']) ?></h3>
                <p style="color:#666;margin:0;"><?= date('l, F j, Y', strtotime($t['tour_date'])) ?> &bull; <?= date('g:i A', strtotime($t['start_time'])) ?> – <?= date('g:i A', strtotime($t['end_time'])) ?></p>
            </div>
            <?php $sc=['scheduled'=>'badge-warning','ongoing'=>'badge-primary','completed'=>'badge-success','cancelled'=>'badge-danger']; ?>
            <span class="badge <?= $sc[$t['status']] ?? '' ?>"><?= ucfirst($t['status']) ?></span>
        </div>

        <p style="color:#555;margin-bottom:1rem;"><?= nl2br(htmlspecialchars($t['description'] ?? '')) ?></p>

        <div style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;">
                <span style="font-size:.9rem;">Participants: <?= $t['booked'] ?> / <?= $t['max_capacity'] ?></span>
                <span style="font-size:.9rem;"><?= $spotsLeft ?> spot<?= $spotsLeft!=1?'s':'' ?> left</span>
            </div>
            <div style="background:#eee;border-radius:4px;height:8px;">
                <div style="background:#2e7d32;width:<?= $pct ?>%;height:8px;border-radius:4px;"></div>
            </div>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <a href="tour-participants.php?tour_id=<?= $t['tour_id'] ?>" class="btn btn-secondary btn-sm">View Participants</a>
            <?php if (in_array($t['status'],['scheduled','ongoing'])): ?>
            <a href="update-status.php?tour_id=<?= $t['tour_id'] ?>" class="btn btn-primary btn-sm" style="background:#2e7d32;">Update Status</a>
            <a href="report-issue.php?tour_id=<?= $t['tour_id'] ?>" class="btn btn-sm" style="background:#ff9800;color:white;">Report Issue</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div style="text-align:center;padding:3rem;color:#999;">
    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    <p style="margin-top:1rem;">No <?= $filter ?> tours found.</p>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
