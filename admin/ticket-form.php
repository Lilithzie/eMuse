<?php
require_once '../config/config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_name = sanitize($_POST['visitor_name']);
    $visitor_email = sanitize($_POST['visitor_email']);
    $visitor_phone = sanitize($_POST['visitor_phone']);
    $ticket_type = $_POST['ticket_type'];
    $price = (float)$_POST['price'];
    $visit_date = $_POST['visit_date'];
    
    // Generate unique ticket code
    $ticket_code = 'TKT-' . strtoupper(substr(uniqid(), -8));
    
    $stmt = $pdo->prepare("INSERT INTO tickets (ticket_code, visitor_name, visitor_email, visitor_phone, ticket_type, price, visit_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
    $stmt->execute([$ticket_code, $visitor_name, $visitor_email, $visitor_phone, $ticket_type, $price, $visit_date]);
    
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
                <select id="ticket_type" name="ticket_type" required onchange="updatePrice()">
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
                <label for="price">Price ($) *</label>
                <input type="number" id="price" name="price" step="0.01" required value="15.00">
            </div>
            
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

<script>
function updatePrice() {
    const type = document.getElementById('ticket_type').value;
    const priceInput = document.getElementById('price');
    
    const prices = {
        'adult': 15.00,
        'child': 8.00,
        'senior': 10.00,
        'student': 12.00,
        'group': 40.00
    };
    
    priceInput.value = prices[type];
}
</script>

<?php include 'includes/footer.php'; ?>
