<?php
require_once '../config/config.php';
checkStaffAuth('tour_guide');

$guide = $pdo->prepare("SELECT g.* FROM tour_guides g JOIN admin_users a ON g.admin_id=a.admin_id WHERE a.admin_id=?");
$guide->execute([$_SESSION['admin_id']]); $guide = $guide->fetch();
if (!$guide) { header('Location: index.php'); exit(); }

$success = $error = '';
$preselected_tour = (int)($_GET['tour_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tour_id    = (int)$_POST['tour_id'];
    $issue_type = sanitize($_POST['issue_type']);
    $description= trim($_POST['description']);
    $severity   = sanitize($_POST['severity']);

    if (!$tour_id || !$description) {
        $error = "Please fill in all required fields.";
    } else {
        // Verify tour belongs to guide
        $check = $pdo->prepare("SELECT 1 FROM tours WHERE tour_id=? AND guide_id=?");
        $check->execute([$tour_id, $guide['guide_id']]);
        if ($check->fetchColumn()) {
            $pdo->prepare("INSERT INTO tour_issues (tour_id, reported_by, issue_type, description, severity) VALUES (?,?,?,?,?)")
                ->execute([$tour_id, $_SESSION['admin_id'], $issue_type, $description, $severity]);
            $success = "Issue reported successfully. The admin team has been notified.";
        } else {
            $error = "You cannot report an issue for a tour not assigned to you.";
        }
    }
}

// Get tours for dropdown
$tours = $pdo->prepare("SELECT * FROM tours WHERE guide_id=? ORDER BY tour_date DESC LIMIT 30");
$tours->execute([$guide['guide_id']]);
$tourList = $tours->fetchAll();

// Get past issues
$pastIssues = $pdo->prepare("
    SELECT ti.*, t.title as tour_title, t.tour_date
    FROM tour_issues ti
    JOIN tours t ON ti.tour_id = t.tour_id
    WHERE ti.reported_by = ?
    ORDER BY ti.created_at DESC
    LIMIT 20
");
$pastIssues->execute([$_SESSION['admin_id']]);
$issues = $pastIssues->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Report Tour Issue</h1>
    <p style="color:#666;">Document and escalate any issues encountered during tours</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
    <!-- Report Form -->
    <div class="card">
        <div class="card-header"><h3>Submit Issue Report</h3></div>
        <div style="padding:1.5rem;">
            <form method="POST">
                <div class="form-group">
                    <label>Tour *</label>
                    <select name="tour_id" class="form-control" required>
                        <option value="">— Select Tour —</option>
                        <?php foreach ($tourList as $t): ?>
                        <option value="<?= $t['tour_id'] ?>" <?= $preselected_tour==$t['tour_id']?'selected':'' ?>>
                            <?= htmlspecialchars($t['title']) ?> (<?= date('M j, Y', strtotime($t['tour_date'])) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Issue Type</label>
                    <select name="issue_type" class="form-control">
                        <option value="Safety Concern">Safety Concern</option>
                        <option value="Equipment Problem">Equipment Problem</option>
                        <option value="Visitor Conduct">Visitor Conduct</option>
                        <option value="Route Issue">Route Issue</option>
                        <option value="Scheduling Conflict">Scheduling Conflict</option>
                        <option value="Health Emergency">Health Emergency</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Severity</label>
                    <select name="severity" class="form-control">
                        <option value="low">🟢 Low – Minor inconvenience</option>
                        <option value="medium" selected>🟡 Medium – Needs attention</option>
                        <option value="high">🔴 High – Urgent / Safety risk</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" class="form-control" rows="5" required
                              placeholder="Describe the issue in detail: what happened, when, who was involved, and any immediate actions taken..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="background:#2e7d32;">Submit Report</button>
            </form>
        </div>
    </div>

    <!-- Severity Guide -->
    <div>
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header"><h3>Severity Guide</h3></div>
            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
                <div style="background:#e8f5e9;padding:.75rem;border-radius:8px;border-left:4px solid #2e7d32;">
                    <strong>🟢 Low</strong><br>
                    <small>Minor issues that don't affect the tour flow. Examples: audio guide glitch, minor delay, visitor question needing follow-up.</small>
                </div>
                <div style="background:#fff8e1;padding:.75rem;border-radius:8px;border-left:4px solid #f9a825;">
                    <strong>🟡 Medium</strong><br>
                    <small>Issues that need attention but can be managed. Examples: route obstruction, visitor complaint, equipment malfunction.</small>
                </div>
                <div style="background:#ffebee;padding:.75rem;border-radius:8px;border-left:4px solid #c62828;">
                    <strong>🔴 High – URGENT</strong><br>
                    <small>Immediate admin notification required. Examples: visitor medical emergency, security threat, major safety hazard.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Past Issues -->
<div class="card">
    <div class="card-header"><h3>My Past Issue Reports</h3></div>
    <?php if ($issues): ?>
    <table class="data-table">
        <thead>
            <tr><th>Tour</th><th>Date</th><th>Type</th><th>Severity</th><th>Status</th><th>Reported</th></tr>
        </thead>
        <tbody>
            <?php foreach ($issues as $i):
                $sev_colors=['low'=>'#2e7d32','medium'=>'#f9a825','high'=>'#c62828'];
                $stat_badges=['open'=>'badge-warning','in_progress'=>'badge-primary','resolved'=>'badge-success'];
            ?>
            <tr>
                <td><?= htmlspecialchars($i['tour_title']) ?></td>
                <td><?= date('M j, Y', strtotime($i['tour_date'])) ?></td>
                <td><?= htmlspecialchars($i['issue_type']) ?></td>
                <td><span style="color:<?= $sev_colors[$i['severity']] ?? '#333' ?>;font-weight:600;"><?= ucfirst($i['severity']) ?></span></td>
                <td><span class="badge <?= $stat_badges[$i['status']] ?? 'badge-warning' ?>"><?= ucfirst(str_replace('_',' ',$i['status'])) ?></span></td>
                <td><?= formatDateTime($i['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="padding:2rem;text-align:center;color:#999;">No issue reports submitted yet.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
