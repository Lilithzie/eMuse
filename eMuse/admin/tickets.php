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

// Get all tickets
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM tickets WHERE 1=1";
$params = [];

if ($filter != 'all') {
    $query .= " AND status = ?";
    $params[] = $filter;
}

if ($search) {
    $query .= " AND (ticket_code LIKE ? OR visitor_name LIKE ? OR visitor_email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY purchase_date DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Ticket Management</h1>
    <a href="ticket-form.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Create Ticket
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'saved') echo 'Ticket created successfully!';
        if ($_GET['success'] == 'deleted') echo 'Ticket deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by ticket code, name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="filter">
                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="confirmed" <?php echo $filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="used" <?php echo $filter == 'used' ? 'selected' : ''; ?>>Used</option>
                <option value="cancelled" <?php echo $filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Visit Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><strong><?php echo $ticket['ticket_code']; ?></strong></td>
                    <td><?php echo $ticket['visitor_name']; ?></td>
                    <td>
                        <?php echo $ticket['visitor_email']; ?><br>
                        <small><?php echo $ticket['visitor_phone']; ?></small>
                    </td>
                    <td><span class="badge"><?php echo ucfirst($ticket['ticket_type']); ?></span></td>
                    <td><?php echo formatCurrency($ticket['price']); ?></td>
                    <td><?php echo formatDate($ticket['visit_date']); ?></td>
                    <td><span class="badge badge-<?php echo $ticket['status']; ?>"><?php echo ucfirst($ticket['status']); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="ticket-view.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn-icon" title="View">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                            <a href="?delete=<?php echo $ticket['ticket_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete this ticket?')" title="Delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
