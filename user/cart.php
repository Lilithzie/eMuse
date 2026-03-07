<?php
require '../config/database.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$message = ''; $message_type = ''; $receipt = null;

// ── Remove item ────────────────────────────────────────────────────────────
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header("Location: cart.php"); exit;
}

// ── Update quantities ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] ?? [] as $pid => $qty) {
        $pid = (int)$pid; $qty = (int)$qty;
        if ($qty <= 0) unset($_SESSION['cart'][$pid]);
        else $_SESSION['cart'][$pid] = $qty;
    }
    $message = 'Cart updated.'; $message_type = 'success';
}

// ── Checkout ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        $message = 'Please login to complete your purchase.';
        $message_type = 'error';
    } else {
    $customer_name  = trim($_POST['customer_name']  ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    $promo_code_str = strtoupper(trim($_POST['promo_code'] ?? ''));

    if (empty($customer_name) || empty($customer_email)) {
        $message = 'Please enter your name and email.'; $message_type = 'error';
    } elseif (empty($_SESSION['cart'])) {
        $message = 'Your cart is empty.'; $message_type = 'error';
    } else {
        try {
            // Build cart items
            $cartItems = []; $subtotal = 0;
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id=? AND status='active'");
                $stmt->execute([$pid]); $prod = $stmt->fetch();
                if ($prod) {
                    $safeQty = min($qty, $prod['stock_quantity']);
                    $line    = $prod['price'] * $safeQty;
                    $cartItems[] = ['product' => $prod, 'qty' => $safeQty, 'subtotal' => $line];
                    $subtotal += $line;
                }
            }

            if (empty($cartItems)) {
                $message = 'No valid items in cart.'; $message_type = 'error';
            } else {
                // Promo code
                $discountAmt = 0; $promoRecord = null;
                if ($promo_code_str) {
                    $ps = $pdo->prepare("SELECT * FROM promo_codes WHERE code=? AND status='active'
                        AND applicable_to IN ('products','all')
                        AND (valid_until IS NULL OR valid_until >= CURDATE())
                        AND (max_uses IS NULL OR uses_count < max_uses)");
                    $ps->execute([$promo_code_str]); $promoRecord = $ps->fetch();
                    if (!$promoRecord) {
                        $message = "Invalid or expired promo code: $promo_code_str"; $message_type = 'error';
                    } else {
                        $discountAmt = $promoRecord['discount_type'] === 'percentage'
                            ? $subtotal * ($promoRecord['discount_value'] / 100)
                            : min($promoRecord['discount_value'], $subtotal);
                    }
                }

                if (!$message) {
                    $totalAmount = max(0, $subtotal - $discountAmt);

                    // Insert sale header
                    $pdo->prepare("INSERT INTO product_sales
                        (sale_date, total_amount, payment_method, customer_name, customer_email, discount_amount, promo_code)
                        VALUES (CURDATE(),?,?,?,?,?,?)")
                        ->execute([$totalAmount, $payment_method, $customer_name, $customer_email,
                                   $discountAmt, $promo_code_str ?: null]);
                    $sale_id = $pdo->lastInsertId();

                    // Insert line items & deduct stock
                    foreach ($cartItems as $ci) {
                        $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
                            VALUES (?,?,?,?,?)")
                            ->execute([$sale_id, $ci['product']['product_id'],
                                       $ci['qty'], $ci['product']['price'], $ci['subtotal']]);
                        $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id=?")
                            ->execute([$ci['qty'], $ci['product']['product_id']]);
                    }

                    if ($promoRecord) {
                        $pdo->prepare("UPDATE promo_codes SET uses_count = uses_count + 1 WHERE promo_id=?")
                            ->execute([$promoRecord['promo_id']]);
                    }

                    $receipt = [
                        'sale_id'  => $sale_id,
                        'items'    => $cartItems,
                        'subtotal' => $subtotal,
                        'discount' => $discountAmt,
                        'total'    => $totalAmount,
                        'payment'  => $payment_method,
                        'name'     => $customer_name,
                        'email'    => $customer_email,
                        'promo'    => $promo_code_str,
                    ];
                    $_SESSION['cart'] = [];
                    $message = "Order #$sale_id confirmed! Total paid: ₱" . number_format($totalAmount, 2) . ". Thank you, " . htmlspecialchars($customer_name) . "!";
                    $message_type = 'success';
                }
            }
        } catch (Exception $e) {
            $message = 'An error occurred: ' . $e->getMessage(); $message_type = 'error';
        }
    }
    }
}

// ── Load promo codes for display ───────────────────────────────────────────
$promoList = $pdo->query("SELECT * FROM promo_codes WHERE status='active'
    AND applicable_to IN ('products','all')
    AND (valid_until IS NULL OR valid_until >= CURDATE())
    ORDER BY code")->fetchAll();

// ── Build cart details for display ────────────────────────────────────────
$cartDetails = []; $subtotal = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id=?");
    $stmt->execute([$pid]); $prod = $stmt->fetch();
    if ($prod) {
        $line = $prod['price'] * $qty;
        $cartDetails[] = ['product' => $prod, 'qty' => $qty, 'subtotal' => $line];
        $subtotal += $line;
    }
}
?>

<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
        <h1 class="section-title">Your Shopping Cart</h1>
        <a href="shop.php" class="btn btn-secondary">← Continue Shopping</a>
    </div>

    <?php if ($message && !$receipt): ?>
    <div style="margin-bottom:1.5rem;padding:1rem;border-radius:4px;
        background:<?= $message_type === 'success' ? '#d4edda' : '#f8d7da' ?>;
        border-left:4px solid <?= $message_type === 'success' ? '#28a745' : '#dc3545' ?>;
        color:<?= $message_type === 'success' ? '#155724' : '#721c24' ?>;">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($receipt): ?>
    <!-- ── Order Receipt ─────────────────────────────────────── -->
    <div style="padding:1.5rem;background:#d4edda;border:2px solid #28a745;border-radius:8px;margin-bottom:2rem;">
        <h3 style="color:#155724;margin-bottom:1rem;">✅ Order Confirmed — #<?= $receipt['sale_id'] ?></h3>
        <p>
            <strong>Name:</strong> <?= htmlspecialchars($receipt['name']) ?> &nbsp;
            <strong>Email:</strong> <?= htmlspecialchars($receipt['email']) ?> &nbsp;
            <strong>Payment:</strong> <?= ucfirst($receipt['payment']) ?>
            <?= $receipt['promo'] ? ' &nbsp; <strong>Promo:</strong> ' . htmlspecialchars($receipt['promo']) : '' ?>
        </p>
        <table style="width:100%;margin-top:1rem;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:2px solid #28a745;">
                    <th style="text-align:left;padding:.4rem 0;">Product</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipt['items'] as $ci): ?>
                <tr style="border-bottom:1px solid #c3e6cb;">
                    <td style="padding:.4rem 0;"><?= htmlspecialchars($ci['product']['name']) ?></td>
                    <td style="text-align:center;">x<?= $ci['qty'] ?></td>
                    <td style="text-align:right;">₱<?= number_format($ci['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if ($receipt['discount'] > 0): ?>
                <tr>
                    <td colspan="2" style="padding-top:.5rem;text-align:right;color:#c62828;">Discount:</td>
                    <td style="text-align:right;color:#c62828;">–₱<?= number_format($receipt['discount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" style="padding-top:.5rem;text-align:right;font-weight:700;">Total Paid:</td>
                    <td style="text-align:right;font-weight:700;font-size:1.1rem;">₱<?= number_format($receipt['total'], 2) ?></td>
                </tr>
            </tbody>
        </table>
        <a href="shop.php" class="btn btn-primary" style="margin-top:1.25rem;display:inline-block;">🛍️ Shop More</a>
    </div>

    <?php elseif (empty($cartDetails)): ?>
    <!-- ── Empty cart ─────────────────────────────────────────── -->
    <div style="text-align:center;padding:4rem;background:#f9f9f9;border-radius:8px;">
        <div style="font-size:4rem;margin-bottom:1rem;">🛒</div>
        <h3 style="color:#888;">Your cart is empty</h3>
        <a href="shop.php" class="btn btn-primary" style="margin-top:1rem;">Browse the Souvenir Shop</a>
    </div>

    <?php else: ?>
    <!-- ── Cart + Checkout ───────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;">

        <!-- Cart items -->
        <div>
            <form method="POST" action="cart.php">
                <input type="hidden" name="update_cart" value="1">
                <?php foreach ($cartDetails as $ci):
                    $prod = $ci['product'];
                ?>
                <div style="display:flex;gap:1rem;align-items:center;border:1px solid #eee;border-radius:8px;padding:1rem;margin-bottom:1rem;background:white;">
                    <div style="width:60px;height:60px;background:linear-gradient(135deg,var(--primary-accent),#ffe0b2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;">
                        <?php if (!empty($prod['image_path'])): ?>
                        <img src="../<?= htmlspecialchars($prod['image_path']) ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                        <?php else: ?>🛍️<?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <strong><?= htmlspecialchars($prod['name']) ?></strong>
                        <div style="font-size:.85rem;color:#888;">₱<?= number_format($prod['price'], 2) ?> each</div>
                        <?php if ($prod['stock_quantity'] < $ci['qty']): ?>
                        <div style="font-size:.8rem;color:#c62828;">Only <?= $prod['stock_quantity'] ?> left in stock</div>
                        <?php endif; ?>
                    </div>
                    <input type="number" name="qty[<?= $prod['product_id'] ?>]"
                           min="0" max="<?= $prod['stock_quantity'] ?>" value="<?= $ci['qty'] ?>"
                           style="width:60px;padding:.4rem;border:1px solid #ddd;border-radius:4px;text-align:center;">
                    <strong style="min-width:80px;text-align:right;">₱<?= number_format($ci['subtotal'], 2) ?></strong>
                    <a href="cart.php?remove=<?= $prod['product_id'] ?>" title="Remove"
                       style="color:#c62828;font-size:1.3rem;text-decoration:none;"
                       onclick="return confirm('Remove this item?')">✕</a>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-secondary">Update Quantities</button>
            </form>
        </div>

        <!-- Checkout panel -->
        <div>
            <?php if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']): ?>
            <div style="background:white;border:1px solid #eee;border-radius:8px;padding:1.5rem;position:sticky;top:1rem;">
                <h3 style="margin-bottom:1rem;color:var(--primary-dark);">Complete Your Purchase</h3>
                <div style="padding:2rem;text-align:center;background:#f9f9f9;border-radius:8px;border:2px dashed var(--primary-light);">
                    <p style="font-size:1rem;color:#666;margin-bottom:1rem;">Please login to checkout</p>
                    <a href="login.php" class="btn btn-primary" style="margin-right:.5rem;">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>
            <?php else: ?>
            <form method="POST" action="cart.php" style="background:white;border:1px solid #eee;border-radius:8px;padding:1.5rem;position:sticky;top:1rem;">
                <input type="hidden" name="checkout" value="1">
                <h3 style="margin-bottom:1.25rem;color:var(--primary-dark);">Order Summary</h3>

                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" name="customer_name" class="form-control" required
                           placeholder="Full name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="customer_email" class="form-control" required
                           placeholder="your@email.com" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="online">Online / GCash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Promo Code <small style="color:#888;">(optional)</small></label>
                    <input type="text" name="promo_code" id="cart_promo" class="form-control"
                           placeholder="Enter code" style="text-transform:uppercase;" oninput="calcCartTotal()">
                    <?php if ($promoList): ?>
                    <div style="margin-top:.4rem;font-size:.8rem;color:#666;">
                        <?php foreach ($promoList as $pc): ?>
                        <span onclick="document.getElementById('cart_promo').value='<?= $pc['code'] ?>';calcCartTotal();"
                              style="cursor:pointer;background:#e8f5e9;color:#2e7d32;padding:.15rem .5rem;border-radius:4px;display:inline-block;margin:.15rem .15rem 0 0;">
                            <?= $pc['code'] ?> (<?= $pc['discount_type'] === 'percentage' ? $pc['discount_value'] . '% OFF' : '₱' . $pc['discount_value'] . ' OFF' ?>)
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Totals -->
                <div style="border-top:1px solid #eee;padding-top:1rem;margin-top:.5rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
                        <span>Subtotal:</span>
                        <strong>₱<?= number_format($subtotal, 2) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;color:#c62828;margin-bottom:.5rem;" id="cart-disc-row">
                        <span>Discount:</span>
                        <strong id="cart-discount">₱0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:1.15rem;font-weight:700;border-top:2px solid #333;padding-top:.5rem;margin-top:.5rem;">
                        <span>Total:</span>
                        <span id="cart-total">₱<?= number_format($subtotal, 2) ?></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1.25rem;padding:.9rem;font-size:1rem;">
                    💳 Place Order
                </button>
            </form>
            <?php endif; ?>
        </div>

    </div>
    <?php endif; ?>
</div>

<script>
const CART_SUBTOTAL = <?= $subtotal ?? 0 ?>;
const PROMOS = {};
<?php foreach ($promoList as $pc): ?>
PROMOS['<?= $pc['code'] ?>'] = {type:'<?= $pc['discount_type'] ?>',val:<?= (float)$pc['discount_value'] ?>};
<?php endforeach; ?>

function calcCartTotal() {
    const code = (document.getElementById('cart_promo')?.value || '').toUpperCase().trim();
    let disc = 0;
    if (code && PROMOS[code]) {
        const p = PROMOS[code];
        disc = p.type === 'percentage'
            ? CART_SUBTOTAL * (p.val / 100)
            : Math.min(p.val, CART_SUBTOTAL);
    }
    const el = document.getElementById('cart-discount');
    const tl = document.getElementById('cart-total');
    const dr = document.getElementById('cart-disc-row');
    if (el) el.textContent = '₱' + disc.toFixed(2);
    if (tl) tl.textContent = '₱' + Math.max(0, CART_SUBTOTAL - disc).toFixed(2);
    if (dr) dr.style.display = disc > 0 ? 'flex' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const dr = document.getElementById('cart-disc-row');
    if (dr) dr.style.display = 'none';
});
</script>

<?php include 'includes/footer.php'; ?>
