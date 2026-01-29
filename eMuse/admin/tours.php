<?php
require_once '../config/config.php';
checkAuth();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM tours WHERE tour_id = ?")->execute([$id]);
    header('Location: tours.php?success=deleted');
    exit();
}

// Get all tours
$tours = $pdo->query("
    SELECT t.*, g.full_name as guide_name 
    FROM tours t 
    LEFT JOIN tour_guides g ON t.guide_id = g.guide_id 
    ORDER BY t.tour_date DESC, t.start_time ASC
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Tour Management</h1>
    <a href="tour-form.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Create New Tour
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'saved') echo 'Tour saved successfully!';
        if ($_GET['success'] == 'deleted') echo 'Tour deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tour Title</th>
                    <th>Guide</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Capacity</th>
                    <th>Bookings</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tours as $tour): ?>
                <tr>
                    <td><?php echo $tour['tour_id']; ?></td>
                    <td><?php echo $tour['title']; ?></td>
                    <td><?php echo $tour['guide_name'] ?? 'Not assigned'; ?></td>
                    <td><?php echo formatDate($tour['tour_date']); ?></td>
                    <td><?php echo date('g:i A', strtotime($tour['start_time'])); ?></td>
                    <td><?php echo $tour['max_capacity']; ?></td>
                    <td>
                        <span class="booking-count" style="color: <?php echo $tour['current_bookings'] >= $tour['max_capacity'] ? '#f44336' : '#4caf50'; ?>">
                            <?php echo $tour['current_bookings']; ?> / <?php echo $tour['max_capacity']; ?>
                        </span>
                    </td>
                    <td><?php echo formatCurrency($tour['price']); ?></td>
                    <td><span class="badge badge-<?php echo $tour['status']; ?>"><?php echo ucfirst($tour['status']); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="tour-form.php?id=<?php echo $tour['tour_id']; ?>" class="btn-icon" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <a href="?delete=<?php echo $tour['tour_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Delete this tour?')" title="Delete">
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
