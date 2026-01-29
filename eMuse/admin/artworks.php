<?php
require_once '../config/config.php';
checkAuth();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM artworks WHERE artwork_id = ?")->execute([$id]);
    header('Location: artworks.php?success=deleted');
    exit();
}

// Get all artworks
$artworks = $pdo->query("
    SELECT a.*, e.title as exhibit_title, l.name as location_name 
    FROM artworks a 
    LEFT JOIN exhibits e ON a.exhibit_id = e.exhibit_id 
    LEFT JOIN locations l ON a.location_id = l.location_id 
    ORDER BY a.created_at DESC
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Artworks & Artifacts</h1>
    <a href="artwork-form.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add New Artwork
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'saved') echo 'Artwork saved successfully!';
        if ($_GET['success'] == 'deleted') echo 'Artwork deleted successfully!';
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
                    <th>Artist</th>
                    <th>Type</th>
                    <th>Exhibit</th>
                    <th>Location</th>
                    <th>Condition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artworks as $artwork): ?>
                <tr>
                    <td><?php echo $artwork['artwork_id']; ?></td>
                    <td><?php echo $artwork['title']; ?></td>
                    <td><?php echo $artwork['artist']; ?></td>
                    <td><span class="badge"><?php echo ucfirst($artwork['type']); ?></span></td>
                    <td><?php echo $artwork['exhibit_title'] ?? 'Not assigned'; ?></td>
                    <td><?php echo $artwork['location_name'] ?? 'Not assigned'; ?></td>
                    <td><span class="condition-<?php echo $artwork['condition_status']; ?>"><?php echo ucfirst($artwork['condition_status']); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="artwork-form.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn-icon" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <a href="?delete=<?php echo $artwork['artwork_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete this artwork?')" title="Delete">
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
