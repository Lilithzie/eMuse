<?php
require_once '../config/config.php';
checkAuth();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM exhibits WHERE exhibit_id = ?")->execute([$id]);
    header('Location: exhibits.php?success=deleted');
    exit();
}

// Get all exhibits
$exhibits = $pdo->query("
    SELECT e.*, c.name as classification_name 
    FROM exhibits e 
    LEFT JOIN exhibit_classifications c ON e.classification_id = c.classification_id 
    ORDER BY e.start_date DESC
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Exhibits Management</h1>
    <a href="exhibit-form.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add New Exhibit
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'saved') echo 'Exhibit saved successfully!';
        if ($_GET['success'] == 'deleted') echo 'Exhibit deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Classification</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exhibits as $exhibit): ?>
                <tr>
                    <td><?php echo $exhibit['exhibit_id']; ?></td>
                    <td><?php echo $exhibit['title']; ?></td>
                    <td><?php echo $exhibit['classification_name']; ?></td>
                    <td><?php echo formatDate($exhibit['start_date']); ?></td>
                    <td><?php echo formatDate($exhibit['end_date']); ?></td>
                    <td><span class="badge badge-<?php echo $exhibit['status']; ?>"><?php echo ucfirst($exhibit['status']); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="exhibit-form.php?id=<?php echo $exhibit['exhibit_id']; ?>" class="btn-icon" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <a href="?delete=<?php echo $exhibit['exhibit_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete this exhibit?')" title="Delete">
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

<?php include 'includes/footer.php'; ?>
