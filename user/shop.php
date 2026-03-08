<?php
require '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include 'includes/header.php';

// Session-based cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$msg = ''; $msgType = '';

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        header('Location: login.php?redirect=shop.php&msg=shop');
        exit;
    }
    $pid = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id=? AND status='active' AND stock_quantity > 0");
    $stmt->execute([$pid]); $prod = $stmt->fetch();
    if ($prod) {
        $current = $_SESSION['cart'][$pid] ?? 0;
        $newQty  = min($current + $qty, $prod['stock_quantity']);
        $_SESSION['cart'][$pid] = $newQty;
        $msg = "Added '{$prod['name']}' to cart."; $msgType = 'success';
    } else {
        $msg = "Item unavailable."; $msgType = 'error';
    }
}

$filterCat = (int)($_GET['cat'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$whereParts = ["p.status='active' AND p.stock_quantity > 0"];
$params = [];
if ($filterCat) { $whereParts[] = "p.category_id=?"; $params[] = $filterCat; }
if ($search)    { $whereParts[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $pdo->prepare("SELECT p.*, pc.name as category FROM products p LEFT JOIN product_categories pc ON p.category_id=pc.category_id WHERE ".implode(' AND ',$whereParts)." ORDER BY pc.name, p.name");
$stmt->execute($params); $products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();
$cartCount  = array_sum($_SESSION['cart']);
?>

<!-- Page Banner -->
<div class="page-hero">
    <div class="page-hero-content">
        <h1>Souvenir Shop</h1>
        <p>Browse and purchase unique museum keepsakes and gifts.</p>
    </div>
</div>

<div class="container">
    <div style="display:flex;justify-content:flex-end;margin-bottom:1.5rem;">
        <a href="cart.php" class="btn btn-primary" style="display:flex;align-items:center;gap:.5rem;">
            🛒 Cart <?= $cartCount>0?"<span style='background:white;color:var(--primary-dark);border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;'>$cartCount</span>":'' ?>
        </a>
    </div>

    <?php if ($msg && $msgType !== 'success'): ?><div style="margin-bottom:1.5rem;padding:1rem;border-radius:4px;background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($msg && $msgType === 'success'): ?><script>document.addEventListener('DOMContentLoaded',function(){showToast(<?= json_encode(htmlspecialchars($msg)) ?>);});</script><?php endif; ?>

    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" action="shop.php">
            <div class="filter-group">
                <label for="cat">Category</label>
                <select id="cat" name="cat">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['category_id'] ?>" <?= $filterCat==$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="flex:2;">
                <label for="search">Search Products</label>
                <input type="text" id="search" name="search" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($filterCat || $search): ?><a href="shop.php" class="btn btn-secondary">Clear</a><?php endif; ?>
        </form>
    </div>

    <!-- Products Grid -->
    <div class="cards-grid">
        <?php foreach ($products as $p): ?>
        <div class="card">
            <?php if (!empty($p['image_path'])): ?>
            <div class="card-image" style="height: 200px; overflow: hidden; border-radius: 8px 8px 0 0;">
                <img src="../<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <?php endif; ?>
            <div class="card-header">
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;"><?= htmlspecialchars($p['category'] ?? '') ?></p>
            </div>
            <div class="card-body">
                <?php if ($p['description']): ?>
                <p><?= htmlspecialchars(substr($p['description'],0,100)) ?>...</p>
                <?php endif; ?>
                <div style="margin-top: 1rem; margin-bottom: 1rem;">
                    <span class="location-badge">₱<?= number_format($p['price'],2) ?></span>
                    <span class="location-badge" style="<?= $p['stock_quantity']<=5?'background:#ffebee;color:#c62828;':'' ?>"><?= $p['stock_quantity'] ?> in stock</span>
                </div>
            </div>
            <div class="card-footer">
                <form method="POST" action="shop.php?cat=<?= $filterCat ?>&search=<?= urlencode($search) ?>" style="display:flex;gap:.5rem;align-items:center;width:100%;"
                      <?php if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']): ?>onsubmit="return shopRequireLogin()"<?php endif; ?>>
                    <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="number" name="quantity" min="1" max="<?= $p['stock_quantity'] ?>" value="1" style="width:55px;padding:.4rem;border:1px solid #ddd;border-radius:4px;font-size:.85rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;padding:.5rem;">Add to Cart</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$products): ?>
        <div class="no-data" style="grid-column:1/-1;"><p>No products found.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Login notice overlay -->
<div id="shop-login-notice" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:2.5rem 2rem;max-width:360px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.2);">
        <div style="font-size:2.5rem;margin-bottom:.75rem;">🛒</div>
        <h3 style="color:#2A3520;margin-bottom:.5rem;">Login Required</h3>
        <p style="color:#555;margin-bottom:1.5rem;">You need to be logged in to add items to your cart.</p>
        <div style="display:flex;gap:.75rem;justify-content:center;">
            <a href="login.php?redirect=shop.php&msg=shop" class="btn btn-primary" style="padding:.6rem 1.4rem;text-decoration:none;">Login</a>
            <a href="register.php" class="btn btn-secondary" style="padding:.6rem 1.4rem;text-decoration:none;">Register</a>
            <button onclick="document.getElementById('shop-login-notice').style.display='none'" class="btn btn-secondary" style="padding:.6rem 1rem;">Cancel</button>
        </div>
    </div>
</div>
<style>@keyframes nprogress{from{width:0}to{width:100%}}</style>
<script>
function shopRequireLogin() {
    document.getElementById('shop-login-notice').style.display = 'flex';
    return false;
}
</script>
