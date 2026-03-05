<?php
require '../config/database.php';
include 'includes/header.php';

$message = '';
$message_type = '';
$receipt = null;

// Active ticket-applicable promo codes
$promoList = $pdo->query("SELECT * FROM promo_codes WHERE status='active' AND applicable_to IN ('tickets','all') AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY code")->fetchAll();

// Handle ticket purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'purchase_ticket') {
    try {
        $visitor_name   = trim($_POST['visitor_name']);
        $visitor_email  = trim($_POST['visitor_email']);
        $visitor_phone  = trim($_POST['visitor_phone']);
        $ticket_type    = trim($_POST['ticket_type']);
        $visit_date     = trim($_POST['visit_date']);
        $quantity       = intval($_POST['quantity']);
        $payment_method = trim($_POST['payment_method'] ?? 'cash');
        $promo_code_str = strtoupper(trim($_POST['promo_code'] ?? ''));

        if (empty($visitor_name) || empty($visitor_email) || empty($ticket_type) || empty($visit_date) || $quantity < 1) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            $price_map = ['adult'=>500.00,'child'=>400.00,'senior'=>350.00,'student'=>350.00,'group'=>400.00];
            $price = $price_map[$ticket_type] ?? 500.00;
            $subtotal = $price * $quantity;

            // Validate promo code
            $discountPer = 0;
            $promoRecord = null;
            if ($promo_code_str) {
                $ps = $pdo->prepare("SELECT * FROM promo_codes WHERE code=? AND status='active' AND applicable_to IN ('tickets','all') AND (valid_until IS NULL OR valid_until >= CURDATE()) AND (max_uses IS NULL OR uses_count < max_uses)");
                $ps->execute([$promo_code_str]); $promoRecord = $ps->fetch();
                if (!$promoRecord) {
                    $message = "Invalid or expired promo code: $promo_code_str";
                    $message_type = 'error';
                } else {
                    $discountPer = $promoRecord['discount_type'] === 'percentage'
                        ? $subtotal * ($promoRecord['discount_value']/100)
                        : min($promoRecord['discount_value'], $subtotal);
                }
            }

            if (!$message) {
                $discountEach = $quantity > 0 ? $discountPer / $quantity : 0;
                $priceEach    = $price - $discountEach;
                $amountPaid   = max(0, $subtotal - $discountPer);
                $ticketCodes  = [];

                for ($i = 0; $i < $quantity; $i++) {
                    $ticket_code = strtoupper('TK' . date('YmdHis') . rand(1000,9999));
                    $pdo->prepare("INSERT INTO tickets
                        (ticket_code, visitor_name, visitor_email, visitor_phone, ticket_type, price, discount_amount, promo_code, amount_paid, payment_method, visit_date, status)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,'confirmed')")
                        ->execute([$ticket_code,$visitor_name,$visitor_email,$visitor_phone,$ticket_type,$price,$discountEach,$promo_code_str?:null,$priceEach,$payment_method,$visit_date]);
                    $ticketCodes[] = $ticket_code;
                }

                if ($promoRecord) $pdo->prepare("UPDATE promo_codes SET uses_count=uses_count+1 WHERE promo_id=?")->execute([$promoRecord['promo_id']]);

                $receipt = ['codes'=>$ticketCodes,'type'=>$ticket_type,'qty'=>$quantity,'price'=>$price,'discount'=>$discountPer,'total'=>$amountPaid,'payment'=>$payment_method,'promo'=>$promo_code_str,'email'=>$visitor_email,'name'=>$visitor_name,'date'=>$visit_date];
                $message = "Success! $quantity ticket(s) purchased. Total: ₱".number_format($amountPaid,2).". Codes emailed to ".htmlspecialchars($visitor_email).".";
                $message_type = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
        $message_type = 'error';
    }
}
?>

    <div class="container">
        <!-- Page Title -->
        <div style="margin-bottom: 2rem;">
            <h1 class="section-title">Book Your Tickets</h1>
            <p class="section-subtitle">Purchase tickets for your visit to eMuse Museum and plan your experience.</p>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 4px; background-color: <?php echo ($message_type == 'success') ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo ($message_type == 'success') ? '#28a745' : '#dc3545'; ?>; color: <?php echo ($message_type == 'success') ? '#155724' : '#721c24'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($receipt): ?>
        <div style="margin-bottom:2rem;padding:1.5rem;background:#d4edda;border-radius:8px;border:2px solid #28a745;">
            <h3 style="color:#155724;margin-bottom:1rem;">🎫 Purchase Confirmed!</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($receipt['name']) ?> &nbsp; <strong>Visit Date:</strong> <?= date('F j, Y',strtotime($receipt['date'])) ?></p>
            <p><strong>Payment:</strong> <?= ucfirst($receipt['payment']) ?> &nbsp; <?= $receipt['promo']?'<strong>Promo:</strong> '.$receipt['promo'].' &nbsp;':'' ?><strong>Total Paid:</strong> ₱<?= number_format($receipt['total'],2) ?></p>
            <p style="margin-top:.75rem;"><strong>Your Ticket Code(s):</strong></p>
            <?php foreach ($receipt['codes'] as $code): ?>
            <code style="display:inline-block;background:#fff;border:1px solid #28a745;padding:.3rem .8rem;border-radius:4px;margin:.25rem;font-size:1rem;letter-spacing:2px;"><?= $code ?></code>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
            <!-- Ticket Pricing Information -->
            <div style="background: #f9f9f9; padding: 2rem; border-radius: 8px; border-left: 4px solid var(--primary-light);">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Ticket Prices</h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                        <span><strong>Adult</strong><br><small style="color: #666;">Ages 18+</small></span>
                        <span style="font-size: 1.3rem; font-weight: 600; color: var(--primary-dark);">PHP 500.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                        <span><strong>Child</strong><br><small style="color: #666;">Ages 3-17</small></span>
                        <span style="font-size: 1.3rem; font-weight: 600; color: var(--primary-dark);">PHP 400.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                        <span><strong>Senior</strong><br><small style="color: #666;">Ages 65+</small></span>
                        <span style="font-size: 1.3rem; font-weight: 600; color: var(--primary-dark);">PHP 350.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                        <span><strong>Student</strong><br><small style="color: #666;">Valid ID required</small></span>
                        <span style="font-size: 1.3rem; font-weight: 600; color: var(--primary-dark);">PHP 350.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span><strong>Group Rate</strong><br><small style="color: #666;">10+ tickets</small></span>
                        <span style="font-size: 1.3rem; font-weight: 600; color: var(--primary-dark);">PHP 400.00/person</span>
                    </div>
                </div>
                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--primary-accent); border-radius: 4px; color: #333;">
                    <p style="font-size: 0.95rem;"><strong> ᯓ★ Children under 3 enter free.</strong></p>
                </div>
            </div>

            <!-- Booking Form -->
            <div style="background: white; padding: 2rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Purchase Tickets</h2>
                <form method="POST" action="tickets.php">
                    <input type="hidden" name="action" value="purchase_ticket">

                    <div class="form-group">
                        <label for="visitor_name">Full Name *</label>
                        <input type="text" id="visitor_name" name="visitor_name" required>
                    </div>

                    <div class="form-group">
                        <label for="visitor_email">Email Address *</label>
                        <input type="email" id="visitor_email" name="visitor_email" required>
                    </div>

                    <div class="form-group">
                        <label for="visitor_phone">Phone Number</label>
                        <input type="tel" id="visitor_phone" name="visitor_phone" placeholder="(Optional)">
                    </div>

                    <div class="form-group">
                        <label for="ticket_type">Ticket Type *</label>
                        <select id="ticket_type" name="ticket_type" required>
                            <option value="">-- Select Ticket Type --</option>
                            <option value="adult">Adult (PHP 500.00)</option>
                            <option value="child">Child (PHP 400.00)</option>
                            <option value="senior">Senior (PHP 350.00)</option>
                            <option value="student">Student (PHP 350.00)</option>
                            <option value="group">Group Rate (PHP 400.00/person)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="visit_date">Visit Date *</label>
                        <input type="date" id="visit_date" name="visit_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Number of Tickets *</label>
                        <input type="number" id="quantity" name="quantity" min="1" max="50" value="1" required onchange="calcTicketTotal()">
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="online">Online / GCash</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="promo_code">Promo Code <small style="color:#888;">(optional)</small></label>
                        <div style="display:flex;gap:.5rem;">
                            <input type="text" id="promo_code" name="promo_code" placeholder="Enter code" style="text-transform:uppercase;flex:1;" onchange="calcTicketTotal()">
                        </div>
                        <?php if ($promoList): ?>
                        <div style="margin-top:.5rem;font-size:.82rem;color:#666;">
                            Active codes:
                            <?php foreach ($promoList as $pc): ?>
                            <span onclick="document.getElementById('promo_code').value='<?= $pc['code'] ?>';calcTicketTotal();"
                                  style="cursor:pointer;background:#e8f5e9;color:#2e7d32;padding:.1rem .5rem;border-radius:4px;margin-left:.25rem;">
                                <?= $pc['code'] ?> (<?= $pc['discount_type']==='percentage'?$pc['discount_value'].'% OFF':'₱'.$pc['discount_value'].' OFF' ?>)
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Live total -->
                    <div id="total-box" style="padding:1rem;background:#f0f0f0;border-radius:6px;margin-bottom:1rem;display:none;">
                        <div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span id="t-subtotal">₱0.00</span></div>
                        <div style="display:flex;justify-content:space-between;color:#c62828;" id="t-disc-row"><span>Discount:</span><span id="t-discount">₱0.00</span></div>
                        <div style="display:flex;justify-content:space-between;font-weight:700;border-top:1px solid #ccc;margin-top:.5rem;padding-top:.5rem;"><span>Total:</span><span id="t-total">₱0.00</span></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">Complete Purchase</button>
                </form>
            </div>
        </div>

        <!-- Ticket Information Section -->
        <section style="padding: 2rem; background: var(--primary-accent); border-radius: 8px; border-left: 4px solid var(--primary-light);">
            <h2 style="color: var(--primary-dark); margin-bottom: 1rem;">Before Your Visit</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; color: #333;">
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">🎫 Your Ticket</h4>
                    <p>A confirmation will be sent to your email. You'll receive a ticket code that you can bring on your visit or present digitally.</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">⏰ Operating Hours</h4>
                    <p>Monday - Friday: 9AM-5PM<br>Saturday - Sunday: 10AM-6PM<br>Closed on major holidays</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">✓ What's Included</h4>
                    <p>Access to all permanent exhibits, featured exhibitions, and guided tour information.</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">👥 Group Visits</h4>
                    <p>For groups of 10+, please contact us directly for special arrangements and rates.</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">♿ Accessibility</h4>
                    <p>The museum is fully accessible. Parking and elevators are available throughout the facility.</p>
                </div>
            </div>
        </section>

        <!-- Recent Transactions Section -->
        <section style="margin-top: 3rem;">
            <h2 class="section-title">Popular Visit Dates</h2>
            <p class="section-subtitle">Check availability and plan your visit in advance.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php
                try {
                    $stats_stmt = $pdo->prepare("SELECT visit_date, COUNT(*) as tickets_sold 
                                               FROM tickets 
                                               WHERE visit_date >= CURDATE()
                                               GROUP BY visit_date 
                                               ORDER BY tickets_sold DESC 
                                               LIMIT 5");
                    $stats_stmt->execute();
                    $popular_dates = $stats_stmt->fetchAll();
                    
                    if ($popular_dates) {
                        foreach ($popular_dates as $date_info) {
                            echo '<div style="padding: 1rem; background: #f9f9f9; border-radius: 4px; border-left: 3px solid var(--primary-light);">';
                            echo '<p style="font-weight: 600; color: var(--primary-dark);">' . date('l, F d, Y', strtotime($date_info['visit_date'])) . '</p>';
                            echo '<p style="color: #666; font-size: 0.9rem;">' . $date_info['tickets_sold'] . ' tickets sold</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div style="grid-column: 1/-1; padding: 1rem; text-align: center; color: #666;">No ticket sales data available yet.</div>';
                    }
                } catch (Exception $e) {}
                ?>
            </div>
        </section>
    </div>

<?php include 'includes/footer.php'; ?>
<script>
const PRICES = {adult:500,child:400,senior:350,student:350,group:400};
const PROMOS = {};
<?php foreach ($promoList as $pc): ?>
PROMOS['<?= $pc['code'] ?>'] = {type:'<?= $pc['discount_type'] ?>',val:<?= $pc['discount_value'] ?>};
<?php endforeach; ?>
function calcTicketTotal(){
    const type = document.getElementById('ticket_type').value;
    const qty  = parseInt(document.getElementById('quantity').value)||1;
    const promo= (document.getElementById('promo_code').value||'').toUpperCase().trim();
    if(!type){document.getElementById('total-box').style.display='none';return;}
    const price = PRICES[type]||500;
    const sub   = price*qty;
    let disc=0;
    if(promo && PROMOS[promo]){
        const p=PROMOS[promo];
        disc = p.type==='percentage' ? sub*(p.val/100) : Math.min(p.val,sub);
    }
    const total = Math.max(0,sub-disc);
    document.getElementById('t-subtotal').textContent  = '₱'+sub.toFixed(2);
    document.getElementById('t-discount').textContent  = '–₱'+disc.toFixed(2);
    document.getElementById('t-total').textContent     = '₱'+total.toFixed(2);
    document.getElementById('t-disc-row').style.display = disc>0?'flex':'none';
    document.getElementById('total-box').style.display = 'block';
}
document.getElementById('ticket_type').addEventListener('change',calcTicketTotal);
</script>
