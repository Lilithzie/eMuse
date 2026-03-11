<?php
require_once '../config/config.php';
checkStaffAuth('manager');

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Visitor feedback stats
$fbStats = $pdo->prepare("
    SELECT
        ROUND(AVG(rating),2)            as avg_overall,
        ROUND(AVG(exhibition_rating),2) as avg_exhibit,
        ROUND(AVG(staff_rating),2)      as avg_staff,
        ROUND(AVG(facilities_rating),2) as avg_facilities,
        COUNT(*) as total_feedback
    FROM visitor_feedback
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$fbStats->execute([$dateFrom, $dateTo]); $fb = $fbStats->fetch();

// Rating distribution
$distribution = $pdo->prepare("
    SELECT rating as overall_rating, COUNT(*) as count
    FROM visitor_feedback
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY rating ORDER BY rating DESC
");
$distribution->execute([$dateFrom, $dateTo]); $dist = $distribution->fetchAll();

// Tour performance
$tourPerf = $pdo->prepare("
    SELECT t.title, t.tour_date,
           COUNT(tb.booking_id) as bookings,
           t.max_capacity,
           ROUND(COUNT(tb.booking_id)/t.max_capacity*100,0) as fill_rate
    FROM tours t
    LEFT JOIN tour_bookings tb ON t.tour_id = tb.tour_id AND tb.status='confirmed'
    WHERE t.tour_date BETWEEN ? AND ?
    GROUP BY t.tour_id, t.title, t.tour_date, t.max_capacity
    ORDER BY t.tour_date DESC LIMIT 10
");
$tourPerf->execute([$dateFrom, $dateTo]); $tourPerf = $tourPerf->fetchAll();

// Maintenance efficiency: completed vs avg days
$maintStats = $pdo->prepare("
    SELECT status, COUNT(*) as count FROM maintenance_records
    WHERE created_at BETWEEN ? AND ?
    GROUP BY status
");
$maintStats->execute([$dateFrom.' 00:00:00', $dateTo.' 23:59:59']); $maintStats = $maintStats->fetchAll();
$maintByStatus = [];
foreach ($maintStats as $ms) $maintByStatus[$ms['status']] = $ms['count'];

// Recent feedback comments
$recentFeedback = $pdo->prepare("
    SELECT *, rating as overall_rating, feedback_text as comments FROM visitor_feedback
    WHERE DATE(created_at) BETWEEN ? AND ? AND feedback_text IS NOT NULL AND feedback_text != ''
    ORDER BY created_at DESC LIMIT 10
");
$recentFeedback->execute([$dateFrom, $dateTo]); $recentFeedback = $recentFeedback->fetchAll();

include 'includes/header.php';

function ratingBar($val, $label) {
    $pct = $val ? ($val / 5) * 100 : 0;
    $color = $val >= 4 ? '#2e7d32' : ($val >= 3 ? '#f57c00' : '#c62828');
    echo "<div style='margin-bottom:1rem;'>
        <div style='display:flex;justify-content:space-between;margin-bottom:.3rem;'>
            <span style='font-size:.9rem;'>$label</span>
            <strong style='color:$color;'>".($val?$val.' / 5':'N/A')."</strong>
        </div>
        <div style='background:#e0f2f1;height:16px;border-radius:999px;'>
            <div style='width:{$pct}%;background:$color;height:16px;border-radius:999px;'></div>
        </div>
    </div>";
}
?>

<!-- Print-only report header -->
<div class="print-report-header">
    <div>
        <div class="museum-name">eMuse &mdash; Museum Management System</div>
        <div class="report-subtitle">Performance Reports &mdash; Visitor Feedback, Tour &amp; Maintenance</div>
    </div>
    <div class="report-meta">
        <div>Period: <strong><?= date('M j, Y', strtotime($dateFrom)) ?> &ndash; <?= date('M j, Y', strtotime($dateTo)) ?></strong></div>
        <div>Generated: <?= date('F j, Y \a\t g:i A') ?></div>
        <div>Prepared by: <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong> &mdash; Manager</div>
    </div>
</div>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1>Performance Reports</h1>
        <p style="color:#666;">Visitor feedback, tour performance &amp; maintenance efficiency</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary no-print">🖨 Print / Export</button>
</div>

<form method="GET" class="card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;"><label>From</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
        <div class="form-group" style="margin:0;"><label>To</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
        <button type="submit" class="btn btn-primary" style="background:#004d40;">Apply</button>
        <a href="performance-reports.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<!-- Feedback KPI -->
<div style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:1.5rem;margin-bottom:1.5rem;">
    <div class="card">
        <div class="card-header"><h3>Visitor Satisfaction Ratings</h3></div>
        <div style="padding:1.5rem;">
            <?php
            ratingBar($fb['avg_overall'],    'Overall Experience');
            ratingBar($fb['avg_exhibit'],    'Exhibition Quality');
            ratingBar($fb['avg_staff'],      'Staff Helpfulness');
            ratingBar($fb['avg_facilities'], 'Facilities & Amenities');
            ?>
            <div style="text-align:center;color:#777;font-size:.85rem;margin-top:.5rem;"><?= $fb['total_feedback'] ?> responses</div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <div class="card">
        <div class="card-header"><h3>Rating Distribution</h3></div>
        <div style="padding:1.5rem;">
            <?php
            $totalFb = $fb['total_feedback'] ?: 1;
            $distMap = [];
            foreach ($dist as $d) $distMap[$d['overall_rating']] = $d['count'];
            for ($star = 5; $star >= 1; $star--):
                $cnt = $distMap[$star] ?? 0;
                $pct = round($cnt/$totalFb*100);
                $color = $star >= 4 ? '#2e7d32' : ($star >= 3 ? '#f57c00' : '#c62828');
            ?>
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
                <span style="min-width:30px;font-weight:700;color:<?= $color ?>"><?= $star ?>★</span>
                <div style="flex:1;background:#f0f0f0;height:14px;border-radius:999px;">
                    <div style="width:<?= $pct ?>%;background:<?= $color ?>;height:14px;border-radius:999px;"></div>
                </div>
                <span style="min-width:30px;font-size:.85rem;color:#666;"><?= $cnt ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Maintenance Stats -->
    <div class="card">
        <div class="card-header"><h3>Maintenance Efficiency</h3></div>
        <div style="padding:1.5rem;display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;text-align:center;">
            <?php
            $statDefs = [
                ['scheduled','Scheduled','#f57c00'],
                ['in_progress','In Progress','#1565c0'],
                ['completed','Completed','#2e7d32'],
                ['cancelled','Cancelled','#757575'],
            ];
            foreach ($statDefs as [$key,$label,$color]): $count = $maintByStatus[$key] ?? 0; ?>
            <div style="padding:1rem;border-radius:8px;background:#f5f5f5;">
                <div style="font-size:1.8rem;font-weight:700;color:<?= $color ?>"><?= $count ?></div>
                <div style="font-size:.85rem;color:#555;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tour Performance -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><h3>Tour Performance (Fill Rate)</h3></div>
    <table class="data-table">
        <thead><tr><th>Tour</th><th>Date</th><th>Bookings</th><th>Capacity</th><th>Fill Rate</th></tr></thead>
        <tbody>
            <?php if (!$tourPerf): ?>
            <tr><td colspan="5" style="text-align:center;padding:2rem;color:#999;">No tours in this period.</td></tr>
            <?php endif; ?>
            <?php foreach ($tourPerf as $t):
                $fillColor = $t['fill_rate'] >= 80 ? '#2e7d32' : ($t['fill_rate'] >= 50 ? '#f57c00' : '#c62828');
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($t['title']) ?></strong></td>
                <td><?= date('M j, Y', strtotime($t['tour_date'])) ?></td>
                <td><?= $t['bookings'] ?></td>
                <td><?= $t['max_capacity'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <div style="flex:1;background:#f0f0f0;height:12px;border-radius:999px;min-width:80px;">
                            <div style="width:<?= min($t['fill_rate'],100) ?>%;background:<?= $fillColor ?>;height:12px;border-radius:999px;"></div>
                        </div>
                        <span style="color:<?= $fillColor ?>;font-weight:700;"><?= $t['fill_rate'] ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Feedback Comments -->
<div class="card">
    <div class="card-header"><h3>Recent Feedback Comments</h3></div>
    <div style="padding:1.25rem;">
        <?php foreach ($recentFeedback as $f): ?>
        <div style="border-left:3px solid <?= $f['overall_rating']>=4?'#2e7d32':($f['overall_rating']>=3?'#f57c00':'#c62828') ?>;padding:.75rem 1rem;margin-bottom:.75rem;background:#fafafa;border-radius:4px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;">
                <strong style="color:<?= $f['overall_rating']>=4?'#2e7d32':($f['overall_rating']>=3?'#f57c00':'#c62828') ?>"><?= $f['overall_rating'] ?>/5 ★</strong>
                <span style="color:#999;font-size:.85rem;"><?= date('M j, Y', strtotime($f['created_at'])) ?></span>
            </div>
            <p style="margin:0;color:#444;font-size:.9rem;"><?= htmlspecialchars($f['comments']) ?></p>
        </div>
        <?php endforeach; ?>
        <?php if (!$recentFeedback): ?><p style="color:#999;text-align:center;">No feedback comments for this period.</p><?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
