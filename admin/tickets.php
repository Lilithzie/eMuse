<?php
require_once '../config/config.php';
checkAuth();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?")->execute([$id]);
    header('Location: tickets.php?success=deleted');
    exit();
}

// Handle payment status update (Mark Paid / Mark Unpaid)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $id         = (int)$_POST['ticket_id'];
    $new_status = $_POST['new_status'];
    if (in_array($new_status, ['confirmed', 'pending'])) {
        $pdo->prepare("UPDATE tickets SET status=? WHERE ticket_id=?")->execute([$new_status, $id]);
    }
    header('Location: tickets.php?success=payment_updated&filter=' . ($_POST['filter'] ?? 'all'));
    exit();
}

// Get all tickets
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

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

$query .= " ORDER BY t.purchase_date DESC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Pending count for badge
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='pending'")->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Ticket Management</h1>
    <span style="font-size:.85rem;color:#666;">Tickets are created by visitors online. Use this panel to manage payment and status.</span>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] === 'deleted')          echo 'Ticket deleted successfully.';
        if ($_GET['success'] === 'payment_updated')  echo 'Payment status updated successfully.';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['notice']) && $_GET['notice'] === 'visitor_only'): ?>
    <div class="alert" style="background:#fff3cd;border-left:4px solid #ffc107;color:#856404;padding:1rem;margin-bottom:1rem;border-radius:4px;">
        <strong>Notice:</strong> Tickets can only be created by visitors through the online booking portal. Admin/staff cannot create tickets directly.
    </div>
<?php endif; ?>

<div class="card">
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by code, name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="filter">
                <option value="all"       <?php echo $filter === 'all'       ? 'selected' : ''; ?>>All Status</option>
                <option value="pending"   <?php echo $filter === 'pending'   ? 'selected' : ''; ?>>Pending Payment <?php if ($pendingCount): ?>(<?= $pendingCount ?>)<?php endif; ?></option>
                <option value="confirmed" <?php echo $filter === 'confirmed' ? 'selected' : ''; ?>>Paid / Confirmed</option>
                <option value="used"      <?php echo $filter === 'used'      ? 'selected' : ''; ?>>Used (Entered)</option>
                <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><strong style="letter-spacing:1px;font-size:.85rem;"><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong><br>
                        <small style="color:#888;"><?php echo date('M d, Y H:i', strtotime($ticket['purchase_date'])); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($ticket['visitor_name']); ?><br>
                        <small style="color:#888;"><?php echo htmlspecialchars($ticket['visitor_email']); ?></small>
                    </td>
                    <td><span class="badge"><?php echo ucfirst($ticket['ticket_type']); ?></span></td>
                    <td><?php echo formatCurrency($ticket['amount_paid'] ?? $ticket['price'] ?? 0); ?></td>
                    <td><?php echo formatDate($ticket['visit_date']); ?></td>
                    <td>
                        <?php
                        $statusColors = ['pending'=>'#f57f17','confirmed'=>'#2e7d32','used'=>'#1565c0','cancelled'=>'#c62828'];
                        $statusLabels = ['pending'=>'Pending Payment','confirmed'=>'Paid','used'=>'Used','cancelled'=>'Cancelled'];
                        $sc = $statusColors[$ticket['status']] ?? '#555';
                        $sl = $statusLabels[$ticket['status']]  ?? ucfirst($ticket['status']);
                        ?>
                        <span style="display:inline-block;padding:.2rem .65rem;border-radius:12px;font-size:.75rem;font-weight:700;color:#fff;background:<?= $sc ?>;"><?= $sl ?></span>
                    </td>
                    <td>
                        <div class="ticket-actions">
                            <?php if ($ticket['status'] === 'pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                                <input type="hidden" name="new_status" value="confirmed">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <button type="submit" class="btn btn-primary btn-sm" title="Mark as Paid" onclick="return confirm('Mark this ticket as paid?')">
                                    &#10003; Mark Paid
                                </button>
                            </form>
                            <?php elseif ($ticket['status'] === 'confirmed'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                                <input type="hidden" name="new_status" value="pending">
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" title="Revert to Unpaid" onclick="return confirm('Revert to unpaid/pending?')">
                                    &#10005; Unpaid
                                </button>
                            </form>
                            <?php else: ?>
                            <span></span>
                            <?php endif; ?>
                            <div class="ticket-actions-icons">
                                <a href="ticket-view.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn-icon btn-view" title="View Details">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <a href="?delete=<?php echo $ticket['ticket_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete this ticket?')" title="Delete">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tickets)): ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:2rem;">No tickets found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
