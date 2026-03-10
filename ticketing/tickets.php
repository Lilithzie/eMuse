<?php
require_once '../config/config.php';
checkStaffAuth('ticketing_staff');

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $ticket_id  = (int)$_POST['ticket_id'];
    $new_status = $_POST['new_status'];
    if (in_array($new_status, ['confirmed', 'pending'])) {
        $pdo->prepare("UPDATE tickets SET status=? WHERE ticket_id=?")->execute([$new_status, $ticket_id]);
    }
    header('Location: tickets.php?success=updated&filter=' . ($_POST['filter'] ?? 'all'));
    exit;
}

$filter = $_GET['filter'] ?? 'pending';
$search = trim($_GET['search'] ?? '');

$query  = "SELECT t.*, tt.price as type_price FROM tickets t LEFT JOIN ticket_types tt ON t.ticket_type = tt.ticket_type WHERE 1=1";
$params = [];

if ($filter !== 'all') {
    $query   .= " AND t.status = ?";
    $params[] = $filter;
}

if ($search) {
    $query   .= " AND (t.ticket_code LIKE ? OR t.visitor_name LIKE ? OR t.visitor_email LIKE ?)";
    $term     = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}

$query .= " ORDER BY t.visit_date ASC, t.purchase_date DESC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Counts
$pendingCount   = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='pending'")->fetchColumn();
$confirmedCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='confirmed'")->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Ticket Payment Management</h1>
    <p style="color:#666;">Collect cash from visitors and mark their tickets as paid before entry.</p>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">Payment status updated successfully.</div>
<?php endif; ?>

<div style="display:flex;gap:1rem;margin-bottom:1.5rem;">
    <div class="stat-card" style="flex:1;border-left:4px solid #f57f17;">
        <div class="stat-content">
            <h3><?= $pendingCount ?></h3>
            <p>Pending Payment</p>
        </div>
    </div>
    <div class="stat-card" style="flex:1;border-left:4px solid #2e7d32;">
        <div class="stat-content">
            <h3><?= $confirmedCount ?></h3>
            <p>Paid / Ready</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search ticket code, visitor name or email..."
                   value="<?= htmlspecialchars($search) ?>">
            <select name="filter">
                <option value="pending"   <?= $filter === 'pending'   ? 'selected' : '' ?>>Pending Payment (<?= $pendingCount ?>)</option>
                <option value="confirmed" <?= $filter === 'confirmed' ? 'selected' : '' ?>>Paid / Confirmed</option>
                <option value="all"       <?= $filter === 'all'       ? 'selected' : '' ?>>All Tickets</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket Code</th>
                    <th>Visitor</th>
                    <th>Type</th>
                    <th>Amount Due</th>
                    <th>Visit Date</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><strong style="font-size:.85rem;letter-spacing:1px;"><?= htmlspecialchars($t['ticket_code']) ?></strong><br>
                        <small style="color:#888;">Booked: <?= date('M d, Y', strtotime($t['purchase_date'])) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($t['visitor_name']) ?><br>
                        <small style="color:#888;"><?= htmlspecialchars($t['visitor_email']) ?></small>
                        <?php if (!empty($t['visitor_phone'])): ?>
                        <br><small style="color:#888;"><?= htmlspecialchars($t['visitor_phone']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge"><?= ucfirst($t['ticket_type']) ?></span></td>
                    <td style="font-weight:700;color:#1565c0;"><?= formatCurrency($t['amount_paid'] ?? $t['price'] ?? 0) ?></td>
                    <td><?= formatDate($t['visit_date']) ?></td>
                    <td>
                        <?php if ($t['status'] === 'pending'): ?>
                        <span style="display:inline-block;padding:.2rem .65rem;border-radius:12px;font-size:.75rem;font-weight:700;color:#fff;background:#f57f17;">
                            Awaiting Cash
                        </span>
                        <?php elseif ($t['status'] === 'confirmed'): ?>
                        <span style="display:inline-block;padding:.2rem .65rem;border-radius:12px;font-size:.75rem;font-weight:700;color:#fff;background:#2e7d32;">
                            Paid ✓
                        </span>
                        <?php elseif ($t['status'] === 'used'): ?>
                        <span style="display:inline-block;padding:.2rem .65rem;border-radius:12px;font-size:.75rem;font-weight:700;color:#fff;background:#1565c0;">
                            Used / Entered
                        </span>
                        <?php else: ?>
                        <span style="display:inline-block;padding:.2rem .65rem;border-radius:12px;font-size:.75rem;font-weight:700;color:#fff;background:#555;">
                            <?= ucfirst($t['status']) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_payment">
                            <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
                            <input type="hidden" name="new_status" value="confirmed">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="btn btn-primary"
                                style="font-size:.8rem;padding:.35rem .8rem;background:#2e7d32;border-color:#2e7d32;"
                                onclick="return confirm('Confirm cash received for ₱<?= number_format($t['amount_paid'] ?? $t['price'] ?? 0, 2) ?> from <?= addslashes(htmlspecialchars($t['visitor_name'])) ?>?')">
                                ✓ Collect Cash
                            </button>
                        </form>
                        <?php elseif ($t['status'] === 'confirmed'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_payment">
                            <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
                            <input type="hidden" name="new_status" value="pending">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="btn btn-secondary"
                                style="font-size:.8rem;padding:.35rem .8rem;"
                                onclick="return confirm('Revert payment status to pending?')">
                                ✕ Undo
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:#aaa;font-size:.82rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tickets)): ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:2rem;">No tickets found for selected filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
