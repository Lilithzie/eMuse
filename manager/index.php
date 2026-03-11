<?php
require_once '../config/config.php';
checkStaffAuth('manager');

$today = date('Y-m-d');
$month = date('Y-m');

// Today's visitors
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_visitors),0) FROM visitor_stats WHERE visit_date=?"); $stmt->execute([$today]); $todayVisitors = $stmt->fetchColumn();
// Today's revenue from tickets
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM tickets WHERE purchase_date=?"); $stmt->execute([$today]); $ticketRevToday = $stmt->fetchColumn();
// Today's revenue from shop
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE sale_date=?"); $stmt->execute([$today]); $shopRevToday = $stmt->fetchColumn();
// Today's revenue from tours
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM tour_bookings WHERE booking_date=?"); $stmt->execute([$today]); $tourRevToday = $stmt->fetchColumn();
$totalRevToday = $ticketRevToday + $shopRevToday + $tourRevToday;

// Active tours today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tours WHERE tour_date=? AND status IN ('scheduled','ongoing')"); $stmt->execute([$today]); $activeTours = $stmt->fetchColumn();

// Outstanding maintenance alerts
$stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_alerts WHERE is_acknowledged=FALSE"); $maintAlerts = $stmt->fetchColumn();

// Average feedback rating this month
$stmt = $pdo->prepare("SELECT ROUND(AVG(rating),1) FROM visitor_feedback WHERE YEAR(created_at)=YEAR(?) AND MONTH(created_at)=MONTH(?)"); $stmt->execute([$today, $today]); $avgRating = $stmt->fetchColumn();

// Month revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM tickets WHERE DATE_FORMAT(purchase_date,'%Y-%m')=?"); $stmt->execute([$month]); $ticketRevMonth = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE DATE_FORMAT(sale_date,'%Y-%m')=?"); $stmt->execute([$month]); $shopRevMonth = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM tour_bookings WHERE DATE_FORMAT(booking_date,'%Y-%m')=?"); $stmt->execute([$month]); $tourRevMonth = $stmt->fetchColumn();
$totalRevMonth = $ticketRevMonth + $shopRevMonth + $tourRevMonth;

// Recent feedback
$recentFeedback = $pdo->query("SELECT *, rating as overall_rating, feedback_text as comments FROM visitor_feedback ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Pending maintenance alerts
$alerts = $pdo->query("SELECT ma.*, e.name as equip_name FROM maintenance_alerts ma LEFT JOIN equipment e ON ma.equipment_id = e.equipment_id WHERE ma.is_acknowledged=FALSE ORDER BY ma.created_at DESC LIMIT 5")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Manager Dashboard</h1>
    <p style="color:#666;">Mabuhay, <?= htmlspecialchars($_SESSION['admin_name']) ?>! &nbsp;<?= date('l, F j, Y') ?></p>
</div>

<!-- Today -->
<h4 style="color:#004d40;margin-bottom:1rem;border-left:4px solid #004d40;padding-left:.75rem;">Today's Summary</h4>
<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-content"><h3><?= number_format($todayVisitors) ?></h3><p>Visitors Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($totalRevToday, 2) ?></h3><p>Revenue Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3><?= $activeTours ?></h3><p>Active Tours</p></div>
    </div>
    <div class="stat-card" style="<?= $maintAlerts > 0 ? 'border-left-color:#c62828;' : '' ?>">
        <div class="stat-content"><h3 style="<?= $maintAlerts?'color:#c62828;':'' ?>"><?= $maintAlerts ?></h3><p>Maintenance Alerts</p></div>
    </div>
</div>

<!-- Monthly -->
<h4 style="color:#004d40;margin-bottom:1rem;border-left:4px solid #004d40;padding-left:.75rem;">Monthly Revenue — <?= date('F Y') ?></h4>
<div class="stats-grid" style="margin-bottom:2rem;">
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($ticketRevMonth,2) ?></h3><p>Ticket Sales</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($tourRevMonth,2) ?></h3><p>Tour Bookings</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-content"><h3>₱<?= number_format($shopRevMonth,2) ?></h3><p>Shop Sales</p></div>
    </div>
    <div class="stat-card" style="border-left-color:#004d40;">
        <div class="stat-content"><h3>₱<?= number_format($totalRevMonth,2) ?></h3><p>Total Revenue</p></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
    <!-- Feedback Avg -->
    <div class="card">
        <div class="card-header"><h3>Recent Visitor Feedback</h3></div>
        <div style="padding:1rem;">
            <?php if ($avgRating): ?>
            <div style="font-size:2.5rem;font-weight:700;color:#004d40;text-align:center;margin-bottom:.5rem;">
                <?= $avgRating ?> / 5
                <div style="font-size:.9rem;font-weight:400;color:#666;">Average Rating this Month</div>
            </div>
            <?php endif; ?>
            <?php foreach ($recentFeedback as $fb): ?>
            <div style="border-left:3px solid #e0f2f1;padding:.5rem .75rem;margin-bottom:.5rem;font-size:.88rem;">
                <div style="display:flex;justify-content:space-between;">
                    <strong><?= $fb['overall_rating'] ?? $fb['rating'] ?>/5 ★</strong>
                    <span style="color:#999;"><?= date('M j', strtotime($fb['created_at'])) ?></span>
                </div>
                <?php if ($fb['comments']): ?>
                <div style="color:#555;margin-top:.25rem;"><?= htmlspecialchars(substr($fb['comments'],0,100)) ?><?= strlen($fb['comments'])>100?'…':'' ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!$recentFeedback): ?><p style="color:#999;text-align:center;">No feedback yet.</p><?php endif; ?>
        </div>
    </div>

    <!-- Active Alerts -->
    <div class="card">
        <div class="card-header"><h3>Maintenance Alerts</h3></div>
        <div style="padding:1rem;">
            <?php foreach ($alerts as $a): ?>
            <div class="alert alert-error" style="margin-bottom:.75rem;padding:.75rem;">
                <strong><?= htmlspecialchars($a['equip_name'] ?? 'Unknown Equipment') ?></strong>
                <div style="font-size:.85rem;margin-top:.2rem;"><?= htmlspecialchars($a['message'] ?? $a['alert_type']) ?></div>
                <div style="font-size:.8rem;color:#666;margin-top:.2rem;"><?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (!$alerts): ?><p style="color:#999;text-align:center;padding:1rem;">No active alerts.</p><?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
    <?php $links = [['Visitor Statistics','visitor-stats.php'],['Revenue Reports','revenue-reports.php'],['Sales Reports','sales-reports.php'],['Performance Reports','performance-reports.php']]; ?>
    <?php foreach ($links as [$label, $href]): ?>
    <a href="<?= $href ?>" style="text-decoration:none;">
        <div class="stat-card" style="text-align:center;padding:1.5rem;cursor:pointer;transition:background .2s;" onmouseover="this.style.background='#e0f2f1'" onmouseout="this.style.background=''">
            <strong style="color:#004d40;"><?= $label ?> →</strong>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
