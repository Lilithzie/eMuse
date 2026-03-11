<?php
require '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: login.php?redirect=my-account.php');
    exit;
}
$uid = $_SESSION['user_id'];

// ── My Tickets ────────────────────────────────────────────────────────────
$tickets = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY purchase_date DESC");
$tickets->execute([$uid]); $tickets = $tickets->fetchAll();

// ── My Tour Bookings ──────────────────────────────────────────────────────
$bookings = $pdo->prepare("
    SELECT tb.*, t.title AS tour_title, t.tour_date, t.start_time, t.end_time, t.price AS tour_price
    FROM tour_bookings tb
    JOIN tours t ON tb.tour_id = t.tour_id
    WHERE tb.user_id = ?
    ORDER BY tb.booking_date DESC
");
$bookings->execute([$uid]); $bookings = $bookings->fetchAll();

// ── My Feedback ───────────────────────────────────────────────────────────
$feedbacks = $pdo->prepare("
    SELECT vf.*, fc.name AS category_name
    FROM visitor_feedback vf
    LEFT JOIN feedback_categories fc ON vf.category_id = fc.category_id
    WHERE vf.user_id = ?
    ORDER BY vf.created_at DESC
");
$feedbacks->execute([$uid]); $feedbacks = $feedbacks->fetchAll();

// ── My Shop Purchases ─────────────────────────────────────────────────────
$purchases = $pdo->prepare("
    SELECT ps.*, GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS item_names,
           SUM(si.quantity) AS total_items
    FROM product_sales ps
    LEFT JOIN sale_items si ON ps.sale_id = si.sale_id
    LEFT JOIN products p ON si.product_id = p.product_id
    WHERE ps.user_id = ?
    GROUP BY ps.sale_id
    ORDER BY ps.created_at DESC
");
$purchases->execute([$uid]); $purchases = $purchases->fetchAll();

include 'includes/header.php';

$statusColor = [
    'confirmed' => '#1565c0', 'used' => '#2e7d32', 'cancelled' => '#c62828',
    'pending' => '#e65100', 'reviewed' => '#6a1b9a', 'resolved' => '#2e7d32',
    'scheduled' => '#1565c0', 'completed' => '#2e7d32',
];

function starRating(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
?>

<style>
.account-tabs { display:flex; gap:.5rem; margin-bottom:2rem; flex-wrap:wrap; }
.tab-btn {
    padding:.55rem 1.3rem; border:2px solid var(--border-color);
    background:#fff; border-radius:6px; cursor:pointer; font-weight:600;
    font-size:.88rem; color:var(--text-dark); transition:.2s;
}
.tab-btn.active, .tab-btn:hover {
    background:var(--chestnut-grove); color:var(--cream-harvest); border-color:var(--chestnut-grove);
}
.tab-panel { display:none; }
.tab-panel.active { display:block; }

.history-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.history-table th {
    background:var(--chestnut-grove); color:var(--cream-harvest);
    padding:.65rem .85rem; text-align:left; font-weight:600;
}
.history-table td { padding:.65rem .85rem; border-bottom:1px solid #eee; vertical-align:middle; }
.history-table tr:last-child td { border-bottom:none; }
.history-table tr:hover td { background:#f9f7f2; }

.badge-status {
    display:inline-block; padding:.2rem .65rem; border-radius:12px;
    font-size:.72rem; font-weight:700; text-transform:uppercase; color:#fff; letter-spacing:.5px;
}
.stars { color:#c4a35a; letter-spacing:1px; }
.empty-state {
    text-align:center; padding:3rem 1rem; color:#999;
    border:2px dashed #ddd; border-radius:8px;
}
.profile-card {
    background: var(--chestnut-grove);
    color:var(--cream-harvest); border-radius:12px; padding:2rem;
    display:flex; align-items:center; gap:1.5rem; margin-bottom:2rem; flex-wrap:wrap;
}
.avatar {
    width:70px; height:70px; background:var(--golden-sand); border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; font-weight:700; color:var(--cocoa-bark); flex-shrink:0;
}
.stat-pills { display:flex; gap:1rem; flex-wrap:wrap; margin-top:.75rem; }
.stat-pill {
    background:rgba(255,255,255,.15); padding:.35rem .9rem; border-radius:20px;
    font-size:.82rem; font-weight:600;
}
</style>

<div class="container" style="padding-top:2rem;padding-bottom:3rem;">

    <!-- Profile Card -->
    <div class="profile-card">
        <div class="avatar"><?= strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) ?></div>
        <div>
            <h2 style="margin:0 0 .25rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
            <p style="opacity:.75;margin:0 0 .75rem;font-size:.9rem;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
            <div class="stat-pills">
                <span class="stat-pill">🎫 <?= count($tickets) ?> Ticket<?= count($tickets)!=1?'s':'' ?></span>
                <span class="stat-pill">🗺️ <?= count($bookings) ?> Booking<?= count($bookings)!=1?'s':'' ?></span>
                <span class="stat-pill">🛒 <?= count($purchases) ?> Order<?= count($purchases)!=1?'s':'' ?></span>
                <span class="stat-pill">💬 <?= count($feedbacks) ?> Feedback<?= count($feedbacks)!=1?'s':'' ?></span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="account-tabs">
        <button class="tab-btn active" onclick="showTab('tickets', this)">🎫 My Tickets</button>
        <button class="tab-btn" onclick="showTab('bookings', this)">🗺️ My Tour Bookings</button>
        <button class="tab-btn" onclick="showTab('purchases', this)">🛒 My Shop Orders</button>
        <button class="tab-btn" onclick="showTab('feedback', this)">💬 My Feedback</button>
    </div>

    <!-- ── TICKETS ─────────────────────────────────────────────────────── -->
    <div id="tab-tickets" class="tab-panel active">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h2 style="margin:0;">My Tickets</h2>
            <a href="tickets.php" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.1rem;">+ Buy Tickets</a>
        </div>
        <?php if ($tickets): ?>
        <div style="overflow-x:auto;">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Code</th><th>Type</th><th>Visit Date</th><th>Amount Paid</th>
                    <th>Payment</th><th>Status</th><th>Purchased</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td><code style="font-size:.82rem;letter-spacing:1px;"><?= htmlspecialchars($t['ticket_code']) ?></code></td>
                <td><?= ucfirst($t['ticket_type']) ?></td>
                <td><?= date('M d, Y', strtotime($t['visit_date'])) ?></td>
                <td>₱<?= number_format($t['amount_paid'] ?? $t['price'], 2) ?></td>
                <td><?= ucfirst($t['payment_method'] ?? '—') ?></td>
                <td><span class="badge-status" style="background:<?= $statusColor[$t['status']] ?? '#555' ?>;"><?= ucfirst($t['status']) ?></span></td>
                <td style="color:#888;font-size:.8rem;"><?= date('M d, Y', strtotime($t['purchase_date'])) ?></td>
                <td>
                    <a href="ticket-print.php?code=<?= urlencode($t['ticket_code']) ?>" target="_blank"
                       style="font-size:.78rem;color:var(--chestnut-grove);text-decoration:none;font-weight:600;">🖨 View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p style="font-size:2rem;margin-bottom:.5rem;">🎫</p>
            <p>You haven't purchased any tickets yet.</p>
            <a href="tickets.php" class="btn btn-primary" style="margin-top:1rem;display:inline-block;">Book a Ticket</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── TOUR BOOKINGS ──────────────────────────────────────────────── -->
    <div id="tab-bookings" class="tab-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h2 style="margin:0;">My Tour Bookings</h2>
            <a href="tours.php" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.1rem;">+ Book a Tour</a>
        </div>
        <?php if ($bookings): ?>
        <div style="overflow-x:auto;">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Tour</th><th>Date</th><th>Time</th><th>People</th>
                    <th>Amount</th><th>Status</th><th>Booked On</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
                <td><strong><?= htmlspecialchars($b['tour_title']) ?></strong></td>
                <td><?= date('M d, Y', strtotime($b['tour_date'])) ?></td>
                <td style="font-size:.82rem;"><?= date('g:i A', strtotime($b['start_time'])) ?> – <?= date('g:i A', strtotime($b['end_time'])) ?></td>
                <td><?= $b['number_of_people'] ?></td>
                <td><?= $b['amount_paid'] !== null ? '₱'.number_format($b['amount_paid'],2) : '—' ?></td>
                <td><span class="badge-status" style="background:<?= $statusColor[$b['status']] ?? '#555' ?>;"><?= ucfirst($b['status']) ?></span></td>
                <td style="color:#888;font-size:.8rem;"><?= date('M d, Y', strtotime($b['booking_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p style="font-size:2rem;margin-bottom:.5rem;">🗺️</p>
            <p>You haven't booked any tours yet.</p>
            <a href="tours.php" class="btn btn-primary" style="margin-top:1rem;display:inline-block;">Explore Tours</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── SHOP PURCHASES ─────────────────────────────────────────────── -->
    <div id="tab-purchases" class="tab-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h2 style="margin:0;">My Shop Orders</h2>
            <a href="shop.php" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.1rem;">+ Visit Shop</a>
        </div>
        <?php if ($purchases): ?>
        <div style="overflow-x:auto;">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Order #</th><th>Items</th><th>Total</th>
                    <th>Payment</th><th>Promo</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($purchases as $p): ?>
            <tr>
                <td><strong>#<?= $p['sale_id'] ?></strong></td>
                <td style="max-width:240px;font-size:.82rem;"><?= htmlspecialchars($p['item_names'] ?? '—') ?></td>
                <td>₱<?= number_format($p['total_amount'], 2) ?>
                    <?php if ($p['discount_amount'] > 0): ?>
                    <br><small style="color:#888;">-₱<?= number_format($p['discount_amount'],2) ?> off</small>
                    <?php endif; ?>
                </td>
                <td><?= ucfirst($p['payment_method']) ?></td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($p['promo_code'] ?? '—') ?></td>
                <td style="color:#888;font-size:.8rem;"><?= date('M d, Y', strtotime($p['sale_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p style="font-size:2rem;margin-bottom:.5rem;">🛒</p>
            <p>You haven't made any shop purchases yet.</p>
            <a href="shop.php" class="btn btn-primary" style="margin-top:1rem;display:inline-block;">Browse the Shop</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── FEEDBACK ───────────────────────────────────────────────────── -->
    <div id="tab-feedback" class="tab-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h2 style="margin:0;">My Feedback</h2>
            <a href="feedback.php" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.1rem;">+ Submit Feedback</a>
        </div>
        <?php if ($feedbacks): ?>
        <div style="display:flex;flex-direction: column;gap:1rem;">
        <?php foreach ($feedbacks as $f): ?>
        <div style="background:#fff;border:1px solid var(--border-color);border-radius:8px;padding:1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem;">
                <div>
                    <span class="stars" title="Overall rating"><?= starRating((int)$f['rating']) ?></span>
                    <strong style="margin-left:.5rem;"><?= number_format($f['rating'],1) ?> / 5</strong>
                    <?php if ($f['category_name']): ?>
                    <span style="margin-left:.75rem;font-size:.78rem;color:#666;background:#f0f0f0;padding:.15rem .5rem;border-radius:4px;"><?= htmlspecialchars($f['category_name']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <span class="badge-status" style="background:<?= $statusColor[$f['status']] ?? '#555' ?>;"><?= ucfirst($f['status']) ?></span>
                    <span style="font-size:.78rem;color:#999;"><?= date('M d, Y', strtotime($f['created_at'])) ?></span>
                </div>
            </div>
            <?php if ($f['visit_date']): ?>
            <p style="font-size:.8rem;color:#888;margin-bottom:.5rem;">Visit date: <?= date('M d, Y', strtotime($f['visit_date'])) ?></p>
            <?php endif; ?>
            <p style="color:#444;margin-bottom:.75rem;"><?= nl2br(htmlspecialchars($f['feedback_text'])) ?></p>
            <?php if ($f['exhibition_rating'] || $f['staff_rating'] || $f['facilities_rating']): ?>
            <div style="display:flex;gap:1.25rem;flex-wrap:wrap;font-size:.8rem;color:#666;border-top:1px solid #eee;padding-top:.6rem;margin-top:.5rem;">
                <?php if ($f['exhibition_rating']): ?><span><span class="stars"><?= starRating((int)$f['exhibition_rating']) ?></span> Exhibits</span><?php endif; ?>
                <?php if ($f['staff_rating']): ?><span><span class="stars"><?= starRating((int)$f['staff_rating']) ?></span> Staff</span><?php endif; ?>
                <?php if ($f['facilities_rating']): ?><span><span class="stars"><?= starRating((int)$f['facilities_rating']) ?></span> Facilities</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($f['admin_response']): ?>
            <div style="margin-top:.75rem;background:#f0f4e8;border-left:3px solid var(--smoky-oak);padding:.75rem 1rem;border-radius:4px;font-size:.86rem;">
                <strong style="color:var(--chestnut-grove);">Museum Response:</strong>
                <p style="margin:.25rem 0 0;color:#444;"><?= nl2br(htmlspecialchars($f['admin_response'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p style="font-size:2rem;margin-bottom:.5rem;">💬</p>
            <p>You haven't submitted any feedback yet.</p>
            <a href="feedback.php" class="btn btn-primary" style="margin-top:1rem;display:inline-block;">Share Your Experience</a>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
// Open tab from URL hash
const hash = location.hash.replace('#','');
if (['tickets','bookings','purchases','feedback'].includes(hash)) {
    const btn = document.querySelector('.tab-btn[onclick*="' + hash + '"]');
    if (btn) showTab(hash, btn);
}
</script>
