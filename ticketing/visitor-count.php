<?php
require_once '../config/config.php';
checkStaffAuth('ticketing_staff');

$today = date('Y-m-d');

// Today's breakdown
$stmt = $pdo->prepare("
    SELECT ticket_type, COUNT(*) as cnt
    FROM tickets
    WHERE visit_date = ? AND status IN ('confirmed','used')
    GROUP BY ticket_type
");
$stmt->execute([$today]);
$breakdown = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE visit_date=? AND status='used'");
$stmt->execute([$today]);  $entered = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE visit_date=? AND status='confirmed'");
$stmt->execute([$today]);  $waiting = $stmt->fetchColumn();

// Last 7 days
$week = $pdo->query("
    SELECT visit_date, COUNT(*) as total, 
           SUM(status='used') as entered
    FROM tickets
    WHERE visit_date BETWEEN CURDATE()-INTERVAL 6 DAY AND CURDATE()
    GROUP BY visit_date
    ORDER BY visit_date ASC
")->fetchAll();

// Hourly entry today
$hourly = $pdo->prepare("
    SELECT HOUR(scan_time) as hr, COUNT(*) as cnt
    FROM entry_log
    WHERE DATE(scan_time)=? AND entry_type='entry'
    GROUP BY HOUR(scan_time)
    ORDER BY hr ASC
");
$hourly->execute([$today]);
$hourlyData = $hourly->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Visitor Count Monitor</h1>
    <p style="color:#666;">Real-time visitor count and entry statistics</p>
</div>

<!-- Live Counter -->
<div style="background:linear-gradient(135deg,#1565c0,#0277bd);border-radius:12px;padding:2.5rem;text-align:center;margin-bottom:2rem;color:white;">
    <p style="font-size:1rem;opacity:.8;margin-bottom:.5rem;">VISITORS CURRENTLY INSIDE (TODAY)</p>
    <?php
        $currentlyInside = (int)$entered;
        $exited = (int)($pdo->prepare("SELECT COUNT(*) FROM entry_log WHERE DATE(scan_time)=? AND entry_type='exit'")
            ->execute([$today]) ? $pdo->prepare("SELECT COUNT(*) FROM entry_log WHERE DATE(scan_time)=? AND entry_type='exit'")->execute([$today]) : 0);
        $stmtE = $pdo->prepare("SELECT COUNT(*) FROM entry_log WHERE DATE(scan_time)=? AND entry_type='exit'");
        $stmtE->execute([$today]); $exitedToday = $stmtE->fetchColumn();
        $inside = max(0, $currentlyInside - $exitedToday);
    ?>
    <div style="font-size:5rem;font-weight:800;line-height:1;"><?= $inside ?></div>
    <p style="opacity:.7;font-size:.9rem;margin-top:.5rem;">Updated on page load – refresh for live count</p>
    <button onclick="location.reload();" style="margin-top:1rem;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.5);color:white;padding:.5rem 1.5rem;border-radius:6px;cursor:pointer;">↺ Refresh</button>
</div>

<!-- Today's Stats -->
<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e3f2fd;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-content"><h3><?= $entered ?></h3><p>Entered Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff3e0;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-content"><h3><?= $waiting ?></h3><p>Yet to Enter</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f5e9;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/></svg></div>
        <div class="stat-content"><h3><?= $exitedToday ?></h3><p>Exited Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce4ec;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c62828" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
        <div class="stat-content"><h3><?= $entered + $waiting ?></h3><p>Total Tickets Today</p></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
    <!-- Ticket Type Breakdown -->
    <div class="card">
        <div class="card-header"><h3>Today's Ticket Breakdown</h3></div>
        <div style="padding:1.25rem;">
            <?php
            $types = ['adult'=>'Adult','child'=>'Child','senior'=>'Senior','student'=>'Student','group'=>'Group'];
            foreach ($types as $k => $label):
                $cnt = $breakdown[$k] ?? 0;
                $pct = ($entered+$waiting) > 0 ? round($cnt/($entered+$waiting)*100) : 0;
            ?>
            <div style="margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;">
                    <span><?= $label ?></span>
                    <strong><?= $cnt ?> <small style="color:#999;">(<?= $pct ?>%)</small></strong>
                </div>
                <div style="background:#eee;border-radius:4px;height:8px;">
                    <div style="background:#1565c0;width:<?= $pct ?>%;height:8px;border-radius:4px;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Hourly Breakdown -->
    <div class="card">
        <div class="card-header"><h3>Hourly Entry Count (Today)</h3></div>
        <div style="padding:1.25rem;">
            <?php
            $peakHour = $hourlyData ? array_search(max($hourlyData), $hourlyData) : null;
            $maxHourly = $hourlyData ? max($hourlyData) : 1;
            for ($h = 8; $h <= 17; $h++):
                $cnt = $hourlyData[$h] ?? 0;
                $pct = $maxHourly > 0 ? round($cnt/$maxHourly*100) : 0;
                $isPeak = ($h === $peakHour && $cnt > 0);
            ?>
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
                <span style="width:60px;font-size:.85rem;color:#666;"><?= date('g A', mktime($h,0,0)) ?></span>
                <div style="flex:1;background:#eee;border-radius:4px;height:16px;">
                    <div style="background:<?= $isPeak?'#1565c0':'#90caf9' ?>;width:<?= $pct ?>%;height:16px;border-radius:4px;transition:width .3s;"></div>
                </div>
                <span style="width:30px;font-size:.85rem;text-align:right;"><?= $cnt ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- 7-day Trend -->
<div class="card">
    <div class="card-header"><h3>Last 7 Days Visitor Trend</h3></div>
    <table class="data-table">
        <thead><tr><th>Date</th><th>Total Tickets</th><th>Entered</th><th>Entry Rate</th></tr></thead>
        <tbody>
            <?php foreach ($week as $row): ?>
            <tr>
                <td><?= date('D, M j', strtotime($row['visit_date'])) ?></td>
                <td><?= $row['total'] ?></td>
                <td><?= $row['entered'] ?></td>
                <td>
                    <?php $rate = $row['total'] > 0 ? round($row['entered']/$row['total']*100) : 0; ?>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <div style="background:#eee;border-radius:4px;height:8px;width:80px;">
                            <div style="background:#1565c0;width:<?= $rate ?>%;height:8px;border-radius:4px;"></div>
                        </div>
                        <?= $rate ?>%
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$week): ?>
            <tr><td colspan="4" style="text-align:center;color:#999;padding:2rem;">No data available</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
