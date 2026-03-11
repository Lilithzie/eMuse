<?php
require_once '../config/config.php';
checkStaffAuth('manager');

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Daily breakdowns
$visitorStats = $pdo->prepare("
    SELECT vs.visit_date,
           vs.total_visitors,
           COUNT(DISTINCT t.ticket_id) as tickets_sold
    FROM visitor_stats vs
    LEFT JOIN tickets t ON DATE(t.purchase_date) = vs.visit_date
    WHERE vs.visit_date BETWEEN :from AND :to
    GROUP BY vs.visit_date, vs.total_visitors
    ORDER BY vs.visit_date DESC
");
$visitorStats->execute([':from'=>$dateFrom, ':to'=>$dateTo]);
$rows = $visitorStats->fetchAll();

// Ticket type breakdown
$ticketTypes = $pdo->prepare("
    SELECT ticket_type as type_name, COUNT(ticket_id) as count
    FROM tickets
    WHERE DATE(purchase_date) BETWEEN :from AND :to
    GROUP BY ticket_type
    ORDER BY count DESC
");
$ticketTypes->execute([':from'=>$dateFrom, ':to'=>$dateTo]);
$typeBreakdown = $ticketTypes->fetchAll();

// Totals
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_visitors),0), COUNT(*) as days FROM visitor_stats WHERE visit_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]); $totals = $stmt->fetch(PDO::FETCH_NUM);
[$totalEntries, $totalDays] = $totals;

// Tickets sold
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE DATE(purchase_date) BETWEEN ? AND ?"); $stmt->execute([$dateFrom, $dateTo]); $totalTickets = $stmt->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1>Visitor Statistics</h1>
        <p style="color:#666;">Visitor attendance and ticket reports</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">🖨 Print / Export</button>
</div>

<form method="GET" class="card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;"><label>From</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
        <div class="form-group" style="margin:0;"><label>To</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
        <button type="submit" class="btn btn-primary" style="background:#004d40;">Apply Filter</button>
        <a href="visitor-stats.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-content"><h3><?= number_format($totalEntries) ?></h3><p>Total Visitors</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3><?= number_format($totalTickets) ?></h3><p>Tickets Sold</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3><?= $totalDays > 0 ? number_format($totalEntries/$totalDays,1) : '—' ?></h3><p>Avg Daily Visitors</p></div></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <!-- Daily Table -->
    <div class="card">
        <div class="card-header"><h3>Daily Visitor Log</h3></div>
        <table class="data-table">
            <thead><tr><th>Date</th><th>Visitors</th><th>Tickets Sold</th></tr></thead>
            <tbody>
                <?php if (!$rows): ?>
                <tr><td colspan="3" style="text-align:center;padding:2rem;color:#999;">No visitor data for selected period.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= date('D, M j, Y', strtotime($r['visit_date'])) ?></td>
                    <td><?= number_format($r['total_visitors']) ?></td>
                    <td><?= number_format($r['tickets_sold']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Ticket Type Breakdown -->
    <div class="card">
        <div class="card-header"><h3>Ticket Types</h3></div>
        <div style="padding:1.25rem;">
            <?php if ($typeBreakdown): ?>
                <?php $maxCount = max(array_column($typeBreakdown,'count')); ?>
                <?php foreach ($typeBreakdown as $tt): ?>
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;">
                        <span style="font-size:.9rem;"><?= htmlspecialchars($tt['type_name']) ?></span>
                        <strong><?= $tt['count'] ?></strong>
                    </div>
                    <div style="background:#f0f0f0;border-radius:999px;height:12px;">
                        <div style="width:<?= $maxCount>0?round($tt['count']/$maxCount*100):0 ?>%;background:#004d40;height:12px;border-radius:999px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#999;text-align:center;">No ticket data.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
