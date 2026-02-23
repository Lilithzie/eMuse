<?php
require_once '../config/config.php';
checkAuth();

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] != '') {
    $category_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM product_categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    header('Location: product-categories.php?success=delete');
    exit();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    if (isset($_POST['category_id']) && $_POST['category_id'] != '') {
        $category_id = (int)$_POST['category_id'];
        $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, description = ? WHERE category_id = ?");
        $stmt->execute([$name, $description, $category_id]);
        header('Location: product-categories.php?success=update');
    } else {
        $stmt = $pdo->prepare("INSERT INTO product_categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        header('Location: product-categories.php?success=create');
    }
    exit();
}

// Get all categories
$categories = $pdo->query("
    SELECT pc.*, COUNT(p.product_id) as product_count
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_id = p.category_id
    GROUP BY pc.category_id
    ORDER BY pc.name
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Product Categories</h1>
    <a href="products.php" class="btn btn-secondary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Back to Products
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php 
        if ($_GET['success'] == 'create') echo 'Category added successfully!';
        elseif ($_GET['success'] == 'update') echo 'Category updated successfully!';
        elseif ($_GET['success'] == 'delete') echo 'Category deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="two-column-layout">
    <div class="card">
        <div class="card-header">
            <h2>All Categories</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">No categories found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        <?php if ($category['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $category['product_count']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                    class="btn-icon" title="Edit">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                            <a href="?delete=<?php echo $category['category_id']; ?>" 
                                               class="btn-icon btn-danger" 
                                               onclick="return confirm('Are you sure? This will affect <?php echo $category['product_count']; ?> products.')"
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
    
    <div class="card">
        <div class="card-header">
            <h2 id="formTitle">Add New Category</h2>
        </div>
        <div class="card-body">
            <form method="POST" class="form" id="categoryForm">
                <input type="hidden" name="category_id" id="category_id">
                
                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Category</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Clear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.two-column-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

@media (max-width: 968px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
}

.text-muted {
    color: #757575;
}
</style>

<script>
function editCategory(category) {
    document.getElementById('formTitle').textContent = 'Edit Category';
    document.getElementById('category_id').value = category.category_id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description || '';
    document.getElementById('submitBtn').textContent = 'Update Category';
    
    // Scroll to form
    document.getElementById('categoryForm').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('formTitle').textContent = 'Add New Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('category_id').value = '';
    document.getElementById('submitBtn').textContent = 'Add Category';
}
</script>

<?php include 'includes/footer.php'; ?>
