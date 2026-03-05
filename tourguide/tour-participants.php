<?php
require_once '../config/config.php';
checkStaffAuth('tour_guide');

$guide = $pdo->prepare("SELECT g.* FROM tour_guides g JOIN admin_users a ON g.admin_id=a.admin_id WHERE a.admin_id=?");
$guide->execute([$_SESSION['admin_id']]); $guide = $guide->fetch();

$tour_id = (int)($_GET['tour_id'] ?? 0);
if (!$guide || !$tour_id) { header('Location: my-tours.php'); exit(); }

// Verify this tour belongs to this guide
$tour = $pdo->prepare("SELECT * FROM tours WHERE tour_id=? AND guide_id=?");
$tour->execute([$tour_id, $guide['guide_id']]); $tour = $tour->fetch();
if (!$tour) { header('Location: my-tours.php'); exit(); }

// Get participants
$participants = $pdo->prepare("
    SELECT tb.*, t.ticket_code
    FROM tour_bookings tb
    LEFT JOIN tickets t ON tb.ticket_id = t.ticket_id
    WHERE tb.tour_id = ?
    ORDER BY tb.booking_date ASC
");
$participants->execute([$tour_id]);
$pList = $participants->fetchAll();

$totalBooked = array_sum(array_column($pList, 'number_of_people'));
$confirmed   = array_filter($pList, fn($r) => $r['status'] === 'confirmed');
$cancelled   = array_filter($pList, fn($r) => $r['status'] === 'cancelled');

include 'includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1>Participant List</h1>
        <p style="color:#666;"><?= htmlspecialchars($tour['title']) ?> &bull; <?= date('F j, Y', strtotime($tour['tour_date'])) ?> &bull; <?= date('g:i A', strtotime($tour['start_time'])) ?></p>
    </div>
    <a href="my-tours.php" class="btn btn-secondary">← Back to Tours</a>
</div>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-content"><h3><?= array_sum(array_column(iterator_to_array($confirmed), 'number_of_people')) ?></h3><p>Confirmed Participants</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="stat-content"><h3><?= $tour['max_capacity'] ?></h3><p>Max Capacity</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div>
        <div class="stat-content"><h3><?= max(0, $tour['max_capacity'] - array_sum(array_column(iterator_to_array($confirmed), 'number_of_people'))) ?></h3><p>Spots Available</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce4ec;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
        <div class="stat-content"><h3><?= count($cancelled) ?></h3><p>Cancellations</p></div>
    </div>
</div>

<!-- Print button -->
<div style="margin-bottom:1rem;text-align:right;">
    <button onclick="window.print()" class="btn btn-secondary">🖨 Print List</button>
</div>

<div class="card">
    <div class="card-header">
        <h3>Bookings (<?= count($pList) ?> total)</h3>
    </div>
    <?php if ($pList): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Visitor Name</th>
                <th>Email</th>
                <th>People</th>
                <th>Ticket Code</th>
                <th>Booking Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $row_n = 1; foreach ($pList as $p): ?>
            <tr style="<?= $p['status']==='cancelled'?'opacity:.5;':''; ?>">
                <td><?= $row_n++ ?></td>
                <td><strong><?= htmlspecialchars($p['visitor_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['visitor_email'] ?? '—') ?></td>
                <td>
                    <span style="background:#2e7d32;color:white;padding:.2rem .6rem;border-radius:12px;font-size:.85rem;font-weight:600"><?= $p['number_of_people'] ?></span>
                </td>
                <td><code><?= htmlspecialchars($p['ticket_code'] ?? '—') ?></code></td>
                <td><?= formatDateTime($p['booking_date']) ?></td>
                <td>
                    <span class="badge <?= $p['status']==='confirmed'?'badge-success':'badge-danger' ?>">
                        <?= ucfirst($p['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f5f5f5;font-weight:600;">
                <td colspan="3" style="text-align:right;padding:.75rem 1rem;">Total Confirmed:</td>
                <td><?= array_sum(array_column(array_filter($pList, fn($r)=>$r['status']==='confirmed'), 'number_of_people')) ?></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No bookings yet for this tour.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
