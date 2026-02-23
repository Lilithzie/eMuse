<?php
require_once '../config/config.php';
checkAuth();

// Get all active products
$products = $pdo->query("
    SELECT * FROM products WHERE status = 'active' ORDER BY name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pdo->beginTransaction();
    
    try {
        $sale_date = $_POST['sale_date'];
        $payment_method = $_POST['payment_method'];
        $customer_name = sanitize($_POST['customer_name']);
        $customer_email = sanitize($_POST['customer_email']);
        $notes = sanitize($_POST['notes']);
        $served_by = $_SESSION['admin_id'];
        
        // Calculate total
        $total_amount = 0;
        $items = [];
        
        foreach ($_POST['product_id'] as $index => $product_id) {
            if (!empty($product_id) && !empty($_POST['quantity'][$index])) {
                $quantity = (int)$_POST['quantity'][$index];
                $unit_price = (float)$_POST['unit_price'][$index];
                $subtotal = $quantity * $unit_price;
                
                $items[] = [
                    'product_id' => (int)$product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'subtotal' => $subtotal
                ];
                
                $total_amount += $subtotal;
            }
        }
        
        if (empty($items)) {
            throw new Exception("No items in sale");
        }
        
        // Insert sale
        $stmt = $pdo->prepare("
            INSERT INTO product_sales (sale_date, total_amount, payment_method, customer_name, 
                                     customer_email, served_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sale_date, $total_amount, $payment_method, $customer_name, 
                       $customer_email, $served_by, $notes]);
        $sale_id = $pdo->lastInsertId();
        
        // Insert sale items and update stock
        foreach ($items as $item) {
            // Insert item
            $stmt = $pdo->prepare("
                INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$sale_id, $item['product_id'], $item['quantity'], 
                          $item['unit_price'], $item['subtotal']]);
            
            // Update product stock
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ?
                WHERE product_id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $pdo->commit();
        header('Location: sales.php?success=create');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>New Sale</h1>
    <a href="sales.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Sales
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="form" id="saleForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="sale_date">Sale Date *</label>
                    <input type="date" id="sale_date" name="sale_date" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="customer_email">Customer Email</label>
                    <input type="email" id="customer_email" name="customer_email" class="form-control">
                </div>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h3>Sale Items</h3>
            <div id="saleItems">
                <div class="sale-item">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Product *</label>
                            <select name="product_id[]" class="form-control product-select" required onchange="updatePrice(this)">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['product_id']; ?>" 
                                            data-price="<?php echo $product['price']; ?>"
                                            data-stock="<?php echo $product['stock_quantity']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> 
                                        (Stock: <?php echo $product['stock_quantity']; ?>) - 
                                        <?php echo formatCurrency($product['price']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity[]" class="form-control quantity-input" 
                                   min="1" value="1" required onchange="calculateSubtotal(this)">
                        </div>
                        <div class="form-group">
                            <label>Unit Price (₱) *</label>
                            <input type="number" name="unit_price[]" class="form-control price-input" 
                                   step="0.01" min="0" required readonly>
                        </div>
                        <div class="form-group">
                            <label>Subtotal (₱)</label>
                            <input type="text" class="form-control subtotal-display" readonly value="0.00">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger" onclick="removeItem(this)">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-secondary" onclick="addItem()" style="margin-bottom: 20px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add Item
            </button>
            
            <div class="total-section">
                <h2>Total: <span id="totalAmount">₱0.00</span></h2>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Complete Sale</button>
                <a href="sales.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.sale-item {
    padding: 20px;
    margin-bottom: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}

.total-section {
    text-align: right;
    padding: 20px;
    background: #e3f2fd;
    border-radius: 8px;
    margin: 20px 0;
}

.total-section h2 {
    margin: 0;
    color: #1976d2;
}
</style>

<script>
const products = <?php echo json_encode($products); ?>;

function updatePrice(select) {
    const option = select.options[select.selectedIndex];
    const priceInput = select.closest('.sale-item').querySelector('.price-input');
    priceInput.value = option.dataset.price || 0;
    calculateSubtotal(priceInput);
}

function calculateSubtotal(input) {
    const item = input.closest('.sale-item');
    const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
    const price = parseFloat(item.querySelector('.price-input').value) || 0;
    const subtotal = quantity * price;
    
    item.querySelector('.subtotal-display').value = '₱' + subtotal.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.sale-item').forEach(item => {
        const quantity = parseFloat(item.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(item.querySelector('.price-input').value) || 0;
        total += quantity * price;
    });
    
    document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
}

function addItem() {
    const container = document.getElementById('saleItems');
    const firstItem = container.querySelector('.sale-item');
    const newItem = firstItem.cloneNode(true);
    
    // Reset values
    newItem.querySelector('.product-select').value = '';
    newItem.querySelector('.quantity-input').value = '1';
    newItem.querySelector('.price-input').value = '';
    newItem.querySelector('.subtotal-display').value = '0.00';
    
    container.appendChild(newItem);
}

function removeItem(button) {
    const items = document.querySelectorAll('.sale-item');
    if (items.length > 1) {
        button.closest('.sale-item').remove();
        calculateTotal();
    } else {
        alert('Cannot remove the last item');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
