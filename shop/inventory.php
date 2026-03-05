<?php
require_once '../config/config.php';
checkStaffAuth('shop_staff');

$success = $error = '';

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id   = (int)$_POST['product_id'];
    $adj_type     = sanitize($_POST['adj_type']); // 'set', 'add', 'subtract'
    $quantity     = (int)$_POST['quantity'];

    $stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE product_id=?");
    $stmt->execute([$product_id]); $prod = $stmt->fetch();

    if (!$prod) {
        $error = "Product not found.";
    } else {
        $newQty = match($adj_type) {
            'set'      => max(0, $quantity),
            'add'      => max(0, $prod['stock_quantity'] + $quantity),
            'subtract' => max(0, $prod['stock_quantity'] - $quantity),
            default    => $prod['stock_quantity'],
        };
        $pdo->prepare("UPDATE products SET stock_quantity=? WHERE product_id=?")->execute([$newQty, $product_id]);

        // Resolve existing stock alerts if now stocked
        if ($newQty > 0) {
            $pdo->prepare("UPDATE stock_alerts SET is_resolved=1, resolved_at=NOW() WHERE product_id=? AND is_resolved=0")->execute([$product_id]);
        }
        $success = "Stock updated for '{$prod['name']}': {$prod['stock_quantity']} → $newQty";
    }
}

$filterCat = (int)($_GET['cat'] ?? 0);
$where = $filterCat ? "WHERE p.category_id = $filterCat" : "WHERE 1";

$products = $pdo->query("
    SELECT p.*, pc.name as category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    $where
    ORDER BY pc.name ASC, p.name ASC
")->fetchAll();

$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Update Inventory</h1>
    <p style="color:#666;">Adjust stock quantities for souvenir shop products</p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<!-- Category Filter -->
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <a href="inventory.php" class="btn <?= !$filterCat?'btn-primary':'btn-secondary' ?>" style="<?= !$filterCat?'background:#bf360c;':'' ?>">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="?cat=<?= $c['category_id'] ?>" class="btn <?= $filterCat==$c['category_id']?'btn-primary':'btn-secondary' ?>" style="<?= $filterCat==$c['category_id']?'background:#bf360c;':'' ?>"><?= htmlspecialchars($c['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr><th>Product</th><th>SKU</th><th>Category</th><th>Current Stock</th><th>Reorder Level</th><th>Status</th><th>Update Stock</th></tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p):
                $stockColor = $p['stock_quantity'] == 0 ? '#c62828' : ($p['stock_quantity'] <= $p['reorder_level'] ? '#f57c00' : '#2e7d32');
                $stockBadge = $p['stock_quantity'] == 0 ? 'badge-danger' : ($p['stock_quantity'] <= $p['reorder_level'] ? 'badge-warning' : 'badge-success');
                $stockLabel = $p['stock_quantity'] == 0 ? 'Out of Stock' : ($p['stock_quantity'] <= $p['reorder_level'] ? 'Low Stock' : 'In Stock');
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                    <?php if ($p['status'] !== 'active'): ?>
                    <span class="badge badge-danger" style="margin-left:.25rem;"><?= ucfirst($p['status']) ?></span>
                    <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td>
                    <span style="color:<?= $stockColor ?>;font-size:1.1rem;font-weight:700;"><?= $p['stock_quantity'] ?></span>
                </td>
                <td><?= $p['reorder_level'] ?></td>
                <td><span class="badge <?= $stockBadge ?>"><?= $stockLabel ?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:.4rem;align-items:center;">
                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                        <input type="hidden" name="update_stock" value="1">
                        <select name="adj_type" style="font-size:.8rem;padding:.3rem .5rem;border:1px solid #ddd;border-radius:4px;">
                            <option value="add">Add</option>
                            <option value="subtract">Subtract</option>
                            <option value="set">Set to</option>
                        </select>
                        <input type="number" name="quantity" min="0" value="0" style="width:60px;padding:.3rem;border:1px solid #ddd;border-radius:4px;font-size:.85rem;">
                        <button type="submit" class="btn btn-sm" style="background:#bf360c;color:white;padding:.3rem .65rem;">✓</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
