<?php
require '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$message_type = '';
$receipt = null;

// PRG: load receipt from session after redirect
if (isset($_SESSION['ticket_receipt'])) {
    $receipt      = $_SESSION['ticket_receipt'];
    $message      = $_SESSION['ticket_message'] ?? '';
    $message_type = 'success';
    unset($_SESSION['ticket_receipt'], $_SESSION['ticket_message']);
}

include 'includes/header.php';

// Active ticket-applicable promo codes
$promoList = $pdo->query("SELECT * FROM promo_codes WHERE status='active' AND applicable_to IN ('tickets','all') AND (valid_until IS NULL OR valid_until >= CURDATE()) ORDER BY code")->fetchAll();

// Handle ticket purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'purchase_ticket') {
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        header('Location: login.php?redirect=tickets.php&msg=tickets');
        exit;
    }
    try {
        $visitor_name   = trim($_POST['visitor_name']);
        $visitor_email  = trim($_POST['visitor_email']);
        $visitor_phone  = trim($_POST['visitor_phone']);
        $ticket_type    = trim($_POST['ticket_type']);
        $visit_date     = trim($_POST['visit_date']);
        $quantity       = intval($_POST['quantity']);
        $payment_method = 'cash'; // Cash only
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
                    $uid = $_SESSION['user_id'] ?? null;
                    $pdo->prepare("INSERT INTO tickets
                        (ticket_code, visitor_name, visitor_email, visitor_phone, ticket_type, price, discount_amount, promo_code, amount_paid, payment_method, visit_date, status, user_id)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,'pending',?)")
                        ->execute([$ticket_code,$visitor_name,$visitor_email,$visitor_phone,$ticket_type,$price,$discountEach,$promo_code_str?:null,$priceEach,$payment_method,$visit_date,$uid]);
                    $ticketCodes[] = $ticket_code;
                }

                if ($promoRecord) $pdo->prepare("UPDATE promo_codes SET uses_count=uses_count+1 WHERE promo_id=?")->execute([$promoRecord['promo_id']]);

                $receiptData = ['codes'=>$ticketCodes,'type'=>$ticket_type,'qty'=>$quantity,'price'=>$price,'discount'=>$discountPer,'total'=>$amountPaid,'payment'=>$payment_method,'promo'=>$promo_code_str,'email'=>$visitor_email,'name'=>$visitor_name,'date'=>$visit_date];
                $successMsg  = "Booking confirmed! $quantity ticket(s) reserved. Total: ₱".number_format($amountPaid,2).". Please pay at the ticketing counter on your visit date to complete entry.";
                $_SESSION['ticket_receipt'] = $receiptData;
                $_SESSION['ticket_message'] = $successMsg;
                header('Location: tickets.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
        $message_type = 'error';
    }
}
?>

<!-- Page Banner -->
<div class="page-hero">
    <div class="page-hero-content">
        <h1>Book Your Visit</h1>
        <p>Purchase tickets and plan your unforgettable eMuse Museum experience.</p>
    </div>
</div>

    <div class="container">

        <!-- Login notice overlay (shown briefly before redirecting) -->
        <div id="login-notice" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:12px;padding:2.5rem 2rem;max-width:380px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.2);">
                <div style="font-size:2.5rem;margin-bottom:.75rem;">🎫</div>
                <h3 style="color:#2A3520;margin-bottom:.5rem;">Login Required</h3>
                <p style="color:#555;margin-bottom:1.25rem;">You need to be logged in to purchase tickets.</p>
                <div style="width:100%;height:4px;background:#eee;border-radius:4px;overflow:hidden;"><div style="height:100%;background:#3D4A2F;animation:nprogress 1.8s linear forwards;"></div></div>
                <p style="color:#999;font-size:.8rem;margin-top:.75rem;">Redirecting to login…</p>
            </div>
        </div>

        <!-- Message Display -->
        <?php if ($message && $message_type !== 'success'): ?>
            <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 4px; background-color: #f8d7da; border-left: 4px solid #dc3545; color: #721c24;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($message && $message_type === 'success'): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){showToast(<?= json_encode(htmlspecialchars($message)) ?>);});</script>
        <?php endif; ?>

        <?php if ($receipt): ?>
        <div style="margin-bottom:2rem;padding:1.5rem;background:#d4edda;border-radius:8px;border:2px solid #28a745;">
            <h3 style="color:#155724;margin-bottom:1rem;">🎫 Ticket Booking Confirmed!</h3>
            <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:.75rem 1rem;margin-bottom:.75rem;color:#856404;">
                <strong>⚠ Payment Required:</strong> Please pay <strong>₱<?= number_format($receipt['total'],2) ?></strong> in cash at the museum ticketing counter on your visit date before entry.
            </div>
            <p><strong>Name:</strong> <?= htmlspecialchars($receipt['name']) ?> &nbsp; <strong>Visit Date:</strong> <?= date('F j, Y',strtotime($receipt['date'])) ?></p>
            <p><strong>Payment Method:</strong> Cash (pay at counter) &nbsp; <?= $receipt['promo']?'<strong>Promo:</strong> '.$receipt['promo'].' &nbsp;':'' ?><strong>Amount Due:</strong> ₱<?= number_format($receipt['total'],2) ?></p>
            <p style="margin-top:.75rem;margin-bottom:.75rem;"><strong>Your Ticket(s) — show or scan QR code at museum entry:</strong></p>
            <div style="display:flex;flex-wrap:wrap;gap:1.5rem;">
                <?php foreach ($receipt['codes'] as $i => $code): ?>
                <div style="background:#fff;border:1px solid #28a745;padding:1.25rem;border-radius:8px;text-align:center;min-width:175px;">
                    <div id="qr-receipt-<?= $i ?>" style="display:inline-block;margin-bottom:.6rem;"></div>
                    <code style="display:block;font-size:.8rem;letter-spacing:1.5px;color:#155724;word-break:break-all;"><?= htmlspecialchars($code) ?></code>
                    <small style="display:block;color:#555;margin:.35rem 0 .75rem;"><?= ucfirst($receipt['type']) ?> · <?= date('M d, Y',strtotime($receipt['date'])) ?></small>
                    <a href="ticket-print.php?code=<?= urlencode($code) ?>" target="_blank"
                       style="display:inline-block;padding:.35rem .9rem;background:#155724;color:#fff;border-radius:4px;text-decoration:none;font-size:.82rem;">
                        🖨 Print / Save
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        <?php foreach ($receipt['codes'] as $i => $code): ?>
        new QRCode(document.getElementById("qr-receipt-<?= $i ?>"), {
            text: "<?= addslashes($code) ?>",
            width: 150, height: 150,
            colorDark: "#155724", colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        <?php endforeach; ?>
        </script>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
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
                <form method="POST" action="tickets.php" onsubmit="return requireLogin()">
                    <input type="hidden" name="action" value="purchase_ticket">

                    <div class="form-group">
                        <label for="visitor_name">Full Name *</label>
                        <input type="text" id="visitor_name" name="visitor_name" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="visitor_email">Email Address *</label>
                        <input type="email" id="visitor_email" name="visitor_email" required value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
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
                        <label>Payment Method</label>
                        <div style="padding:.6rem .75rem;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;color:#555;">
                            💵 Cash — Pay at the museum ticketing counter on your visit date
                        </div>
                        <input type="hidden" name="payment_method" value="cash">
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

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">Reserve Ticket(s)</button>
                </form>
            </div>
        </div>

        <!-- Ticket Information Section -->
        <section style="padding: 2rem; background: var(--primary-accent); border-radius: 8px; border-left: 4px solid var(--primary-light);">
            <h2 style="color: var(--primary-dark); margin-bottom: 1rem;">Before Your Visit</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; color: #333;">
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">🎫 Your Ticket</h4>
                    <p>Submit the form to reserve your ticket. On your visit date, present your ticket code at the counter and pay in cash to complete entry.</p>
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
<style>@keyframes nprogress{from{width:0}to{width:100%}}</style>
<script>
const IS_LOGGED_IN = <?= (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) ? 'true' : 'false' ?>;
function requireLogin() {
    if (!IS_LOGGED_IN) {
        showLoginNotice('login.php?redirect=tickets.php&msg=tickets');
        return false;
    }
    return true;
}
function showLoginNotice(url) {
    var el = document.getElementById('login-notice');
    if (el) { el.style.display = 'flex'; setTimeout(function(){ window.location = url; }, 1800); }
    else { window.location = url; }
}
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
