<?php
require_once '../config/config.php';
checkStaffAuth('tour_guide');

$guide = $pdo->prepare("SELECT g.* FROM tour_guides g JOIN admin_users a ON g.admin_id=a.admin_id WHERE a.admin_id=?");
$guide->execute([$_SESSION['admin_id']]); $guide = $guide->fetch();
if (!$guide) { header('Location: index.php'); exit(); }

$success = $error = '';
$tour_id = (int)($_GET['tour_id'] ?? $_POST['tour_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $new_status = sanitize($_POST['new_status']);
    $notes      = sanitize($_POST['notes'] ?? '');
    $tour_id_p  = (int)$_POST['tour_id'];

    $valid = ['ongoing','completed','cancelled'];
    if (in_array($new_status, $valid)) {
        $pdo->prepare("UPDATE tours SET status=? WHERE tour_id=? AND guide_id=?")
            ->execute([$new_status, $tour_id_p, $guide['guide_id']]);
        $success = "Tour status updated to: " . ucfirst($new_status);
        $tour_id = $tour_id_p;
    } else {
        $error = "Invalid status selected.";
    }
}

// Fetch tours for this guide (active/upcoming/today)
$tours = $pdo->prepare("
    SELECT * FROM tours 
    WHERE guide_id=? AND status NOT IN ('cancelled') 
    ORDER BY tour_date ASC, start_time ASC
");
$tours->execute([$guide['guide_id']]);
$tourList = $tours->fetchAll();

// Pre-select tour if passed via URL
$selectedTour = null;
if ($tour_id) {
    foreach ($tourList as $t) { if ($t['tour_id'] == $tour_id) { $selectedTour = $t; break; } }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Update Tour Status</h1>
    <p style="color:#666;">Mark tours as ongoing, completed, or cancelled</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
    <!-- Tour List -->
    <div class="card">
        <div class="card-header"><h3>My Tours</h3></div>
        <div style="padding:.5rem;">
            <?php if ($tourList): ?>
            <?php foreach ($tourList as $t):
                $sc=['scheduled'=>'badge-warning','ongoing'=>'badge-primary','completed'=>'badge-success','cancelled'=>'badge-danger'];
            ?>
            <a href="?tour_id=<?= $t['tour_id'] ?>" style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;border-bottom:1px solid #eee;text-decoration:none;color:inherit;background:<?= $tour_id==$t['tour_id']?'#f0fff0':'transparent' ?>;">
                <div>
                    <strong><?= htmlspecialchars($t['title']) ?></strong>
                    <div style="font-size:.85rem;color:#666;"><?= date('M j, Y', strtotime($t['tour_date'])) ?> &bull; <?= date('g:i A', strtotime($t['start_time'])) ?></div>
                </div>
                <span class="badge <?= $sc[$t['status']] ?? '' ?>"><?= ucfirst($t['status']) ?></span>
            </a>
            <?php endforeach; ?>
            <?php else: ?>
            <p style="padding:1.5rem;color:#999;text-align:center;">No tours assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Form -->
    <div class="card">
        <div class="card-header"><h3><?= $selectedTour ? 'Update: '.htmlspecialchars($selectedTour['title']) : 'Select a Tour' ?></h3></div>
        <div style="padding:1.5rem;">
        <?php if ($selectedTour): ?>
            <div style="background:#f9f9f9;border-radius:8px;padding:1rem;margin-bottom:1.5rem;font-size:.9rem;">
                <div style="display:flex;gap:2rem;flex-wrap:wrap;">
                    <span>📅 <?= date('F j, Y', strtotime($selectedTour['tour_date'])) ?></span>
                    <span>⏰ <?= date('g:i A', strtotime($selectedTour['start_time'])) ?> – <?= date('g:i A', strtotime($selectedTour['end_time'])) ?></span>
                    <span>👥 Max: <?= $selectedTour['max_capacity'] ?></span>
                </div>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="tour_id" value="<?= $selectedTour['tour_id'] ?>">
                <div class="form-group">
                    <label>New Status *</label>
                    <select name="new_status" class="form-control" required>
                        <option value="">— Select —</option>
                        <option value="ongoing"   <?= $selectedTour['status']=='ongoing'  ?'selected':'' ?>>🟡 Ongoing (In Progress)</option>
                        <option value="completed" <?= $selectedTour['status']=='completed'?'selected':'' ?>>✅ Completed</option>
                        <option value="cancelled" >❌ Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes (optional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about this status update..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#2e7d32;">Update Status</button>
                <a href="report-issue.php?tour_id=<?= $selectedTour['tour_id'] ?>" class="btn btn-secondary" style="margin-left:.5rem;">Report Issue</a>
            </form>
        <?php else: ?>
            <div style="text-align:center;color:#999;padding:3rem;">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <p style="margin-top:1rem;">Select a tour from the list to update its status.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
