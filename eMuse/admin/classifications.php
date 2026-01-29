<?php
require_once '../config/config.php';
checkAuth();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM exhibit_classifications WHERE classification_id = ?")->execute([$id]);
    header('Location: classifications.php?success=deleted');
    exit();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    if (isset($_POST['classification_id'])) {
        $stmt = $pdo->prepare("UPDATE exhibit_classifications SET name = ?, description = ? WHERE classification_id = ?");
        $stmt->execute([$name, $description, $_POST['classification_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO exhibit_classifications (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
    }
    
    header('Location: classifications.php?success=saved');
    exit();
}

$classifications = $pdo->query("SELECT * FROM exhibit_classifications ORDER BY name")->fetchAll();
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Exhibit Classifications</h1>
    <button onclick="showAddModal()" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Classification
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Classification <?php echo $_GET['success'] == 'saved' ? 'saved' : 'deleted'; ?> successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classifications as $class): ?>
                <tr>
                    <td><?php echo $class['classification_id']; ?></td>
                    <td><?php echo $class['name']; ?></td>
                    <td><?php echo $class['description']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button onclick='editItem(<?php echo json_encode($class); ?>)' class="btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <a href="?delete=<?php echo $class['classification_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete?')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="itemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Classification</h2>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" id="classification_id" name="classification_id">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Classification';
    document.getElementById('itemModal').style.display = 'flex';
    document.querySelector('form').reset();
}
function editItem(item) {
    document.getElementById('modalTitle').textContent = 'Edit Classification';
    document.getElementById('classification_id').value = item.classification_id;
    document.getElementById('name').value = item.name;
    document.getElementById('description').value = item.description;
    document.getElementById('itemModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('itemModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
