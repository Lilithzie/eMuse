<?php
require_once '../config/config.php';
checkStaffAuth('shop_staff');

$success = $error = '';
$receiptData = null;

// Load active products
$products = $pdo->query("
    SELECT p.*, pc.name as category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    WHERE p.status = 'active'
    ORDER BY pc.name ASC, p.name ASC
")->fetchAll();

// Load active promo codes
$promoCodes = $pdo->query("
    SELECT * FROM promo_codes
    WHERE status='active'
      AND applicable_to IN ('products','all')
      AND (valid_until IS NULL OR valid_until >= CURDATE())
    ORDER BY code ASC
")->fetchAll();

// Process sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale'])) {
    $customer_name   = trim(sanitize($_POST['customer_name']));
    $customer_email  = trim(sanitize($_POST['customer_email']));
    $payment_method  = sanitize($_POST['payment_method']);
    $promo_code_str  = strtoupper(trim(sanitize($_POST['promo_code'])));
    $notes           = trim(sanitize($_POST['notes']));
    $item_ids        = $_POST['item_product_id'] ?? [];
    $item_qtys       = $_POST['item_qty'] ?? [];

    // Build cart
    $cartItems = [];
    $subtotal  = 0;
    for ($i = 0; $i < count($item_ids); $i++) {
        $pid = (int)$item_ids[$i];
        $qty = (int)$item_qtys[$i];
        if ($pid <= 0 || $qty <= 0) continue;

        $product = $pdo->prepare("SELECT * FROM products WHERE product_id=? AND status='active' AND stock_quantity >= ?");
        $product->execute([$pid, $qty]);
        $prod = $product->fetch();
        if (!$prod) { $error = "Product ID $pid is unavailable or insufficient stock."; break; }

        $cartItems[] = ['product' => $prod, 'qty' => $qty, 'subtotal' => $prod['price'] * $qty];
        $subtotal += $prod['price'] * $qty;
    }

    if (!$error && empty($cartItems)) { $error = "Please add at least one product to the sale."; }

    // Apply promo code
    $discountAmt = 0;
    $promoRecord = null;
    if (!$error && $promo_code_str) {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code=? AND status='active' AND applicable_to IN ('products','all') AND (valid_until IS NULL OR valid_until >= CURDATE()) AND (max_uses IS NULL OR uses_count < max_uses)");
        $stmt->execute([$promo_code_str]);
        $promoRecord = $stmt->fetch();
        if (!$promoRecord) {
            $error = "Invalid or expired promo code: $promo_code_str";
        } else {
            if ($promoRecord['discount_type'] === 'percentage') {
                $discountAmt = $subtotal * ($promoRecord['discount_value'] / 100);
            } else {
                $discountAmt = min($promoRecord['discount_value'], $subtotal);
            }
        }
    }

    if (!$error) {
        $totalAmount = max(0, $subtotal - $discountAmt);

        // Insert sale record
        $pdo->prepare("
            INSERT INTO product_sales (sale_date, total_amount, payment_method, customer_name, customer_email, served_by, discount_amount, promo_code, notes)
            VALUES (CURDATE(),?,?,?,?,?,?,?,?)
        ")->execute([$totalAmount, $payment_method, $customer_name ?: null, $customer_email ?: null, $_SESSION['admin_id'], $discountAmt, $promo_code_str ?: null, $notes]);

        $sale_id = $pdo->lastInsertId();

        // Insert sale items & update inventory
        foreach ($cartItems as $ci) {
            $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)")
                ->execute([$sale_id, $ci['product']['product_id'], $ci['qty'], $ci['product']['price'], $ci['subtotal']]);
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id=?")
                ->execute([$ci['qty'], $ci['product']['product_id']]);

            // Check for low stock / out of stock alerts
            $newQty = $pdo->prepare("SELECT stock_quantity, reorder_level FROM products WHERE product_id=?");
            $newQty->execute([$ci['product']['product_id']]); $nq = $newQty->fetch();
            if ($nq['stock_quantity'] === 0) {
                $pdo->prepare("INSERT INTO stock_alerts (product_id, alert_type, message) VALUES (?,?,?) ON DUPLICATE KEY UPDATE created_at=NOW()")
                    ->execute([$ci['product']['product_id'], 'out_of_stock', "'{$ci['product']['name']}' is now OUT OF STOCK"]);
            } elseif ($nq['stock_quantity'] <= $nq['reorder_level']) {
                $pdo->prepare("INSERT INTO stock_alerts (product_id, alert_type, message) VALUES (?,?,?) ON DUPLICATE KEY UPDATE created_at=NOW()")
                    ->execute([$ci['product']['product_id'], 'low_stock', "'{$ci['product']['name']}' is below reorder level ({$nq['stock_quantity']} left)"]);
            }
        }

        // Update promo code usage
        if ($promoRecord) {
            $pdo->prepare("UPDATE promo_codes SET uses_count = uses_count + 1 WHERE promo_id=?")->execute([$promoRecord['promo_id']]);
        }

        // Build receipt
        $receiptData = [
            'sale_id'       => $sale_id,
            'items'         => $cartItems,
            'subtotal'      => $subtotal,
            'discount'      => $discountAmt,
            'total'         => $totalAmount,
            'payment'       => $payment_method,
            'customer'      => $customer_name,
            'promo'         => $promo_code_str,
            'served_by'     => $_SESSION['admin_name'],
        ];
        $success = "Sale #$sale_id recorded successfully!";
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Point of Sale</h1>
    <p style="color:#666;">Record sales transactions and process payments</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<?php if ($receiptData): ?>
<!-- Receipt -->
<div class="card" style="margin-bottom:2rem;border:2px solid #2e7d32;">
    <div class="card-header" style="background:#e8f5e9;"><h3>🧾 Receipt – Sale #<?= $receiptData['sale_id'] ?></h3></div>
    <div style="padding:1.5rem;">
        <div style="display:flex;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
            <span><strong>Customer:</strong> <?= htmlspecialchars($receiptData['customer'] ?: 'Walk-in') ?></span>
            <span><strong>Served by:</strong> <?= htmlspecialchars($receiptData['served_by']) ?></span>
            <span><strong>Date:</strong> <?= date('M j, Y g:i A') ?></span>
            <span><strong>Payment:</strong> <?= ucfirst($receiptData['payment']) ?></span>
        </div>
        <table class="data-table">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
                <?php foreach ($receiptData['items'] as $ci): ?>
                <tr>
                    <td><?= htmlspecialchars($ci['product']['name']) ?></td>
                    <td><?= $ci['qty'] ?></td>
                    <td>₱<?= number_format($ci['product']['price'],2) ?></td>
                    <td>₱<?= number_format($ci['subtotal'],2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="3" style="text-align:right;">Subtotal:</td><td>₱<?= number_format($receiptData['subtotal'],2) ?></td></tr>
                <?php if ($receiptData['discount'] > 0): ?>
                <tr style="color:#c62828;"><td colspan="3" style="text-align:right;">Discount (<?= $receiptData['promo'] ?>):</td><td>–₱<?= number_format($receiptData['discount'],2) ?></td></tr>
                <?php endif; ?>
                <tr style="font-weight:700;font-size:1.1rem;"><td colspan="3" style="text-align:right;">TOTAL:</td><td>₱<?= number_format($receiptData['total'],2) ?></td></tr>
            </tfoot>
        </table>
        <div style="margin-top:1rem;display:flex;gap:.75rem;">
            <button onclick="window.print()" class="btn btn-secondary">🖨 Print Receipt</button>
            <a href="pos.php" class="btn btn-primary">New Sale</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- POS Form -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;">

    <!-- Cart Builder -->
    <div class="card">
        <div class="card-header"><h3>Sale Items</h3></div>
        <div style="padding:1.5rem;">
            <form method="POST" id="pos-form">
                <!-- Dynamic Item Rows -->
                <div id="item-rows">
                    <div class="item-row" style="display:grid;grid-template-columns:3fr 1fr auto;gap:.75rem;margin-bottom:.75rem;align-items:flex-end;">
                        <div class="form-group" style="margin:0;">
                            <label>Product</label>
                            <select name="item_product_id[]" class="form-control product-select" onchange="updatePrice(this)">
                                <option value="">— Select Product —</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock_quantity'] ?>">
                                    <?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['stock_quantity'] ?>) – ₱<?= number_format($p['price'],2) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Qty</label>
                            <input type="number" name="item_qty[]" min="1" value="1" class="form-control qty-input" onchange="recalcTotal()">
                        </div>
                        <div style="padding-bottom:.2rem;">
                            <button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm">✕</button>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addRow()" class="btn btn-secondary btn-sm" style="margin-bottom:1.5rem;">+ Add Item</button>

                <hr style="margin-bottom:1.5rem;">

                <!-- Customer & Payment -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Walk-in / Name">
                    </div>
                    <div class="form-group">
                        <label>Customer Email</label>
                        <input type="email" name="customer_email" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control" onchange="recalcTotal()">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="online">Online / GCash</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Promo Code</label>
                        <input type="text" name="promo_code" id="promo_code" class="form-control" placeholder="Optional" style="text-transform:uppercase;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>

                <button type="submit" name="submit_sale" class="btn btn-primary btn-block" style="font-size:1.05rem;padding:.85rem;">
                    💳 Process Sale
                </button>
            </form>
        </div>
    </div>

    <!-- Order Summary -->
    <div>
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-header"><h3>Order Summary</h3></div>
            <div style="padding:1.25rem;">
                <div id="summary-items" style="min-height:80px;margin-bottom:1rem;font-size:.9rem;color:#666;">(No items selected)</div>
                <div style="border-top:1px solid #eee;padding-top:1rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
                        <span>Subtotal:</span><strong id="subtotal-display">₱0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;color:#c62828;">
                        <span>Discount:</span><strong id="discount-display">–₱0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:700;border-top:2px solid #333;padding-top:.5rem;margin-top:.5rem;">
                        <span>TOTAL:</span><span id="total-display">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Promo Codes -->
        <div class="card">
            <div class="card-header"><h3>Active Promo Codes</h3></div>
            <div style="padding:.75rem;">
                <?php foreach ($promoCodes as $pc): ?>
                <div onclick="document.getElementById('promo_code').value='<?= $pc['code'] ?>';recalcTotal();"
                     style="cursor:pointer;padding:.6rem .75rem;border:1px solid #eee;border-radius:6px;margin-bottom:.5rem;hover:background:#fbe9e7;">
                    <strong><?= $pc['code'] ?></strong>
                    <span style="float:right;color:#2e7d32;font-weight:600;">
                        <?= $pc['discount_type']==='percentage' ? $pc['discount_value'].'% OFF' : '₱'.$pc['discount_value'].' OFF' ?>
                    </span>
                    <div style="font-size:.8rem;color:#999;">Valid until <?= $pc['valid_until'] ?? 'No expiry' ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (!$promoCodes): ?><p style="color:#999;font-size:.9rem;">No active promos.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const PRODUCT_PRICES = {};
<?php foreach ($products as $p): ?>
PRODUCT_PRICES[<?= $p['product_id'] ?>] = { price: <?= $p['price'] ?>, name: <?= json_encode($p['name']) ?> };
<?php endforeach; ?>

const PROMO_CODES = {};
<?php foreach ($promoCodes as $pc): ?>
PROMO_CODES['<?= $pc['code'] ?>'] = { type: '<?= $pc['discount_type'] ?>', value: <?= $pc['discount_value'] ?> };
<?php endforeach; ?>

function recalcTotal() {
    let subtotal = 0;
    const summaryLines = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.product-select');
        const qty = parseInt(row.querySelector('.qty-input').value) || 0;
        const pid = parseInt(sel.value) || 0;
        if (pid && qty > 0 && PRODUCT_PRICES[pid]) {
            const line = PRODUCT_PRICES[pid].price * qty;
            subtotal += line;
            summaryLines.push(`<div style="margin-bottom:.3rem;">${PRODUCT_PRICES[pid].name} x${qty} = ₱${line.toFixed(2)}</div>`);
        }
    });

    const promoCode = document.getElementById('promo_code').value.toUpperCase().trim();
    let discount = 0;
    if (promoCode && PROMO_CODES[promoCode]) {
        const p = PROMO_CODES[promoCode];
        discount = p.type === 'percentage' ? subtotal * (p.value / 100) : Math.min(p.value, subtotal);
    }

    const total = Math.max(0, subtotal - discount);
    document.getElementById('subtotal-display').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('discount-display').textContent = '–₱' + discount.toFixed(2);
    document.getElementById('total-display').textContent    = '₱' + total.toFixed(2);
    document.getElementById('summary-items').innerHTML = summaryLines.length ? summaryLines.join('') : '<span style="color:#999;">(No items selected)</span>';
}

function updatePrice(sel) { recalcTotal(); }

function addRow() {
    const container = document.getElementById('item-rows');
    const first = container.querySelector('.item-row');
    const clone = first.cloneNode(true);
    clone.querySelector('.product-select').value = '';
    clone.querySelector('.qty-input').value = 1;
    clone.querySelector('.product-select').addEventListener('change', function(){ updatePrice(this); });
    clone.querySelector('.qty-input').addEventListener('change', recalcTotal);
    container.appendChild(clone);
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) { btn.closest('.item-row').remove(); recalcTotal(); }
}

document.getElementById('promo_code').addEventListener('input', recalcTotal);
</script>

<?php include 'includes/footer.php'; ?>
