<?php
require_once '../config/config.php';
checkStaffAuth('manager');

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Ticket revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM tickets WHERE purchase_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom,$dateTo]); $ticketRev = $stmt->fetchColumn();

// Tour revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM tour_bookings WHERE booking_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom,$dateTo]); $tourRev = $stmt->fetchColumn();

// Shop revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE sale_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom,$dateTo]); $shopRev = $stmt->fetchColumn();

$totalRev = $ticketRev + $tourRev + $shopRev;

// Daily breakdown
$daily = $pdo->prepare("
    SELECT d.d,
        COALESCE(t.rev,0) as ticket_rev,
        COALESCE(tb.rev,0) as tour_rev,
        COALESCE(ps.rev,0) as shop_rev
    FROM (
        SELECT DISTINCT date_val as d FROM (
            SELECT purchase_date as date_val FROM tickets WHERE purchase_date BETWEEN :f1 AND :t1
            UNION SELECT booking_date FROM tour_bookings WHERE booking_date BETWEEN :f2 AND :t2
            UNION SELECT sale_date FROM product_sales WHERE sale_date BETWEEN :f3 AND :t3
        ) x
    ) d
    LEFT JOIN (SELECT purchase_date, SUM(amount_paid) as rev FROM tickets WHERE purchase_date BETWEEN :f4 AND :t4 GROUP BY purchase_date) t ON t.purchase_date=d.d
    LEFT JOIN (SELECT booking_date, SUM(amount_paid) as rev FROM tour_bookings WHERE booking_date BETWEEN :f5 AND :t5 GROUP BY booking_date) tb ON tb.booking_date=d.d
    LEFT JOIN (SELECT sale_date, SUM(total_amount) as rev FROM product_sales WHERE sale_date BETWEEN :f6 AND :t6 GROUP BY sale_date) ps ON ps.sale_date=d.d
    ORDER BY d.d DESC
");
$daily->execute([':f1'=>$dateFrom,':t1'=>$dateTo,':f2'=>$dateFrom,':t2'=>$dateTo,':f3'=>$dateFrom,':t3'=>$dateTo,':f4'=>$dateFrom,':t4'=>$dateTo,':f5'=>$dateFrom,':t5'=>$dateTo,':f6'=>$dateFrom,':t6'=>$dateTo]);
$dailyRows = $daily->fetchAll();

// Discounts
$stmt = $pdo->prepare("SELECT COALESCE(SUM(discount_amount),0) FROM tickets WHERE purchase_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom,$dateTo]); $ticketDisc = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(discount_amount),0) FROM product_sales WHERE sale_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom,$dateTo]); $shopDisc = $stmt->fetchColumn();
$totalDisc = $ticketDisc + $shopDisc;

include 'includes/header.php';
?>

<!-- Print-only report header -->
<div class="print-report-header">
    <div>
        <div class="museum-name">eMuse &mdash; Museum Management System</div>
        <div class="report-subtitle">Revenue Reports &mdash; Breakdown by Category &amp; Date Range</div>
    </div>
    <div class="report-meta">
        <div>Period: <strong><?= date('M j, Y', strtotime($dateFrom)) ?> &ndash; <?= date('M j, Y', strtotime($dateTo)) ?></strong></div>
        <div>Generated: <?= date('F j, Y \a\t g:i A') ?></div>
        <div>Prepared by: <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong> &mdash; Manager</div>
    </div>
</div>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1>Revenue Reports</h1>
        <p style="color:#666;">Revenue breakdown by category and date range</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary no-print">🖨 Print / Export</button>
</div>

<form method="GET" class="card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;"><label>From</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
        <div class="form-group" style="margin:0;"><label>To</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
        <button type="submit" class="btn btn-primary" style="background:#004d40;">Apply</button>
        <a href="revenue-reports.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<!-- Summary -->
<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card"><div class="stat-content"><h3>₱<?= number_format($ticketRev,2) ?></h3><p>Ticket Revenue</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3>₱<?= number_format($tourRev,2) ?></h3><p>Tour Revenue</p></div></div>
    <div class="stat-card"><div class="stat-content"><h3>₱<?= number_format($shopRev,2) ?></h3><p>Shop Revenue</p></div></div>
    <div class="stat-card" style="border-left-color:#004d40;"><div class="stat-content"><h3>₱<?= number_format($totalRev,2) ?></h3><p>Total Revenue</p></div></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <!-- Daily Revenue Table -->
    <div class="card">
        <div class="card-header"><h3>Daily Revenue Breakdown</h3></div>
        <table class="data-table">
            <thead><tr><th>Date</th><th>Tickets</th><th>Tours</th><th>Shop</th><th>Daily Total</th></tr></thead>
            <tbody>
                <?php if (!$dailyRows): ?>
                <tr><td colspan="5" style="text-align:center;padding:2rem;color:#999;">No revenue data for the selected period.</td></tr>
                <?php endif; ?>
                <?php foreach ($dailyRows as $d):
                    $dayTotal = $d['ticket_rev'] + $d['tour_rev'] + $d['shop_rev'];
                ?>
                <tr>
                    <td><?= date('D, M j, Y', strtotime($d['d'])) ?></td>
                    <td>₱<?= number_format($d['ticket_rev'],2) ?></td>
                    <td>₱<?= number_format($d['tour_rev'],2) ?></td>
                    <td>₱<?= number_format($d['shop_rev'],2) ?></td>
                    <td><strong>₱<?= number_format($dayTotal,2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if ($dailyRows): ?>
            <tfoot>
                <tr style="font-weight:700;"><td>TOTAL</td><td>₱<?= number_format($ticketRev,2) ?></td><td>₱<?= number_format($tourRev,2) ?></td><td>₱<?= number_format($shopRev,2) ?></td><td>₱<?= number_format($totalRev,2) ?></td></tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <!-- Revenue Share -->
    <div class="card">
        <div class="card-header"><h3>Revenue Share</h3></div>
        <div style="padding:1.5rem;">
            <?php
            $categories = [
                ['Ticket Sales', $ticketRev, '#004d40'],
                ['Tour Bookings', $tourRev, '#00695c'],
                ['Shop Sales',    $shopRev, '#1de9b6'],
            ];
            foreach ($categories as [$cat, $rev, $color]):
                $pct = $totalRev > 0 ? ($rev/$totalRev)*100 : 0;
            ?>
            <div style="margin-bottom:1.25rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
                    <span><?= $cat ?></span>
                    <strong>₱<?= number_format($rev,2) ?> (<?= number_format($pct,1) ?>%)</strong>
                </div>
                <div style="background:#e0f2f1;height:16px;border-radius:999px;">
                    <div style="width:<?= $pct ?>%;background:<?= $color ?>;height:16px;border-radius:999px;"></div>
                </div>
            </div>
            <?php endforeach; ?>

            <div style="border-top:1px solid #eee;padding-top:1rem;margin-top:1rem;">
                <div style="display:flex;justify-content:space-between;color:#c62828;">
                    <span>Total Discounts Given:</span>
                    <strong>–₱<?= number_format($totalDisc,2) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:.5rem;font-size:1.1rem;font-weight:700;">
                    <span>Net Revenue:</span>
                    <span>₱<?= number_format($totalRev,2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
