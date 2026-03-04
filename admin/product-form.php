<?php
require_once '../config/config.php';
checkAuth();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$isEdit = false;

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();

if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    $isEdit = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : null;
    $sku = sanitize($_POST['sku']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $cost_price = $_POST['cost_price'] ? (float)$_POST['cost_price'] : null;
    $stock_quantity = (int)$_POST['stock_quantity'];
    $reorder_level = (int)$_POST['reorder_level'];
    $supplier = sanitize($_POST['supplier']);
    $status = $_POST['status'];
    
    if ($isEdit) {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, category_id = ?, sku = ?, description = ?, price = ?, 
                cost_price = ?, stock_quantity = ?, reorder_level = ?, supplier = ?, status = ?
            WHERE product_id = ?
        ");
        $stmt->execute([$name, $category_id, $sku, $description, $price, $cost_price, 
                       $stock_quantity, $reorder_level, $supplier, $status, $product_id]);
        header('Location: products.php?success=update');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO products (name, category_id, sku, description, price, cost_price, 
                                stock_quantity, reorder_level, supplier, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category_id, $sku, $description, $price, $cost_price, 
                       $stock_quantity, $reorder_level, $supplier, $status]);
        header('Location: products.php?success=create');
    }
    exit();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $isEdit ? 'Edit Product' : 'Add New Product'; ?></h1>
    <a href="products.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Products
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" class="form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo $product['name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="sku">SKU *</label>
                    <input type="text" id="sku" name="sku" class="form-control" 
                           value="<?php echo $product['sku'] ?? ''; ?>" required
                           placeholder="e.g., BK-001">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                <?php echo (isset($product['category_id']) && $product['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active" <?php echo (!isset($product['status']) || $product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($product['status']) && $product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="discontinued" <?php echo (isset($product['status']) && $product['status'] == 'discontinued') ? 'selected' : ''; ?>>Discontinued</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Selling Price (₱) *</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                           value="<?php echo $product['price'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="cost_price">Cost Price (₱)</label>
                    <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0"
                           value="<?php echo $product['cost_price'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0"
                           value="<?php echo $product['stock_quantity'] ?? '0'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="reorder_level">Reorder Level *</label>
                    <input type="number" id="reorder_level" name="reorder_level" class="form-control" min="0"
                           value="<?php echo $product['reorder_level'] ?? '10'; ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="supplier">Supplier</label>
                    <input type="text" id="supplier" name="supplier" class="form-control" 
                           value="<?php echo $product['supplier'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?php echo $product['description'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $isEdit ? 'Update Product' : 'Add Product'; ?>
                </button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
