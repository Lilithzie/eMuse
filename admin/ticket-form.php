<?php
require_once '../config/config.php';
checkAuth();

// Tickets are created by visitors via the user portal — redirect admin back.
header('Location: tickets.php?notice=visitor_only');
exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = sanitize($_POST['visitor_name']);
    $visitor_email = sanitize($_POST['visitor_email']);
    $visitor_phone = sanitize($_POST['visitor_phone']);
    $ticket_type = $_POST['ticket_type'];
    $visit_date = $_POST['visit_date'];

    // Look up price from ticket_types (3NF: price lives in ticket_types, not tickets)
    $tt_stmt = $pdo->prepare("SELECT price FROM ticket_types WHERE ticket_type = ?");
    $tt_stmt->execute([$ticket_type]);
    $tt = $tt_stmt->fetch();
    $price = $tt ? (float)$tt['price'] : 0;

    // Generate unique ticket code
    $ticket_code = 'TKT-' . strtoupper(substr(uniqid(), -8));
    
    $stmt = $pdo->prepare("INSERT INTO tickets (ticket_code, visitor_name, visitor_email, visitor_phone, ticket_type, visit_date, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$ticket_code, $visitor_name, $visitor_email, $visitor_phone, $ticket_type, $visit_date]);
    
    header('Location: tickets.php?success=saved');
    exit();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Create New Ticket</h1>
    <a href="tickets.php" class="btn btn-secondary">Back to Tickets</a>
</div>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="visitor_name">Visitor Name *</label>
                <input type="text" id="visitor_name" name="visitor_name" required>
            </div>
            
            <div class="form-group">
                <label for="visitor_email">Email *</label>
                <input type="email" id="visitor_email" name="visitor_email" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="visitor_phone">Phone</label>
                <input type="tel" id="visitor_phone" name="visitor_phone">
            </div>
            
            <div class="form-group">
                <label for="ticket_type">Ticket Type *</label>
                <select id="ticket_type" name="ticket_type" required>
                    <option value="adult">Adult</option>
                    <option value="child">Child</option>
                    <option value="senior">Senior</option>
                    <option value="student">Student</option>
                    <option value="group">Group</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="visit_date">Visit Date *</label>
                <input type="date" id="visit_date" name="visit_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Ticket</button>
            <a href="tickets.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
