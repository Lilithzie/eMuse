<?php
require_once '../config/config.php';
checkAuth();

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] != '') {
    $product_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    header('Location: products.php?success=delete');
    exit();
}

// Get all products with category info
$stmt = $pdo->query("
    SELECT p.*, pc.name as category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();

// Get statistics
$total_products = count($products);
$active_products = count(array_filter($products, fn($p) => $p['status'] == 'active'));
$low_stock = count(array_filter($products, fn($p) => $p['stock_quantity'] <= $p['reorder_level']));
$total_stock_value = array_sum(array_map(fn($p) => $p['stock_quantity'] * $p['cost_price'], $products));

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Product Inventory</h1>
    <div style="display: flex; gap: 10px;">
        <a href="product-categories.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            Categories
        </a>
        <a href="product-form.php" class="btn btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Product
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php 
        if ($_GET['success'] == 'create') echo 'Product added successfully!';
        elseif ($_GET['success'] == 'update') echo 'Product updated successfully!';
        elseif ($_GET['success'] == 'delete') echo 'Product deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_products; ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $active_products; ?></h3>
            <p>Active Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $low_stock; ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e5f5;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($total_stock_value); ?></h3>
            <p>Stock Value</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Products</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No products found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'low-stock-row' : ''; ?>">
                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($product['sku']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td><?php echo formatCurrency($product['cost_price'] ?? 0); ?></td>
                                <td>
                                    <span class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'text-danger' : ''; ?>">
                                        <strong><?php echo $product['stock_quantity']; ?></strong>
                                        <?php if ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                            <svg style="vertical-align: middle;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                                <line x1="12" y1="9" x2="12" y2="13"/>
                                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['reorder_level']; ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $product['status'] == 'active' ? 'success' : 
                                            ($product['status'] == 'inactive' ? 'secondary' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="product-form.php?id=<?php echo $product['product_id']; ?>" class="btn-icon" title="Edit">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <a href="?delete=<?php echo $product['product_id']; ?>" 
                                           class="btn-icon btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this product?')"
                                           title="Delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.low-stock-row {
    background-color: #fff3e0 !important;
}

.text-danger {
    color: #f44336;
}
</style>

<?php include 'includes/footer.php'; ?>
