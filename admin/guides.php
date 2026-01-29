<?php
require_once '../config/config.php';
checkAuth();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM tour_guides WHERE guide_id = ?")->execute([$id]);
    header('Location: guides.php?success=deleted');
    exit();
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $specialization = sanitize($_POST['specialization']);
    $status = $_POST['status'];
    
    if (isset($_POST['guide_id'])) {
        $stmt = $pdo->prepare("UPDATE tour_guides SET full_name = ?, email = ?, phone = ?, specialization = ?, status = ? WHERE guide_id = ?");
        $stmt->execute([$full_name, $email, $phone, $specialization, $status, $_POST['guide_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tour_guides (full_name, email, phone, specialization, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $specialization, $status]);
    }
    
    header('Location: guides.php?success=saved');
    exit();
}

$guides = $pdo->query("SELECT * FROM tour_guides ORDER BY full_name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Tour Guides</h1>
    <button onclick="showAddModal()" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add New Guide
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'saved') echo 'Guide saved successfully!';
        if ($_GET['success'] == 'deleted') echo 'Guide deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Specialization</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guides as $guide): ?>
                <tr>
                    <td><?php echo $guide['guide_id']; ?></td>
                    <td><?php echo $guide['full_name']; ?></td>
                    <td><?php echo $guide['email']; ?></td>
                    <td><?php echo $guide['phone']; ?></td>
                    <td><?php echo $guide['specialization']; ?></td>
                    <td><span class="badge badge-<?php echo $guide['status']; ?>"><?php echo ucfirst($guide['status']); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <button onclick='editGuide(<?php echo json_encode($guide); ?>)' class="btn-icon" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <a href="?delete=<?php echo $guide['guide_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete this guide?')" title="Delete">
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

<!-- Modal -->
<div id="guideModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Guide</h2>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" id="guide_id" name="guide_id">
            
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="specialization">Specialization *</label>
                <input type="text" id="specialization" name="specialization" placeholder="e.g., Modern Art, Ancient History" required>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Guide</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Guide';
    document.getElementById('guideModal').style.display = 'flex';
    document.querySelector('form').reset();
    document.getElementById('guide_id').value = '';
}

function editGuide(guide) {
    document.getElementById('modalTitle').textContent = 'Edit Guide';
    document.getElementById('guide_id').value = guide.guide_id;
    document.getElementById('full_name').value = guide.full_name;
    document.getElementById('email').value = guide.email;
    document.getElementById('phone').value = guide.phone;
    document.getElementById('specialization').value = guide.specialization;
    document.getElementById('status').value = guide.status;
    document.getElementById('guideModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('guideModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('guideModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
