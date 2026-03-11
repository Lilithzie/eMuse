<?php
require '../config/database.php';
include 'includes/header.php';

// --- Detail view ---
if (isset($_GET['id'])) {
    $exhibit_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare(
            "SELECT e.*, ec.name as classification
             FROM exhibits e
             LEFT JOIN exhibit_classifications ec ON e.classification_id = ec.classification_id
             WHERE e.exhibit_id = ?"
        );
        $stmt->execute([$exhibit_id]);
        $ex = $stmt->fetch();
        if ($ex) {
            $art_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM artworks WHERE exhibit_id = ?");
            $art_stmt->execute([$exhibit_id]);
            $art_count = $art_stmt->fetch()['cnt'];
        }
    } catch (Exception $e) { $ex = null; }

    if (!empty($ex)): ?>
<div class="page-hero">
    <div class="page-hero-content">
        <h1><?php echo htmlspecialchars($ex['title']); ?></h1>
        <p><?php echo htmlspecialchars($ex['classification'] ?? 'General'); ?></p>
    </div>
</div>
<div class="container" style="padding-top:2rem;">
    <a href="exhibits.php" class="btn btn-secondary" style="margin-bottom:1.5rem;display:inline-block;">&larr; Back to Exhibits</a>
    <div style="background:var(--card-bg);border-radius:16px;padding:2rem;box-shadow:0 4px 20px rgba(0,0,0,.07);">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;margin-bottom:1.5rem;">
            <span class="card-badge status-<?php echo $ex['status']; ?>" style="font-size:1rem;padding:.3rem .9rem;"><?php echo ucfirst($ex['status']); ?></span>
            <span style="color:var(--smoky-oak);"><?php echo date('F d, Y', strtotime($ex['start_date'])); ?> &ndash; <?php echo date('F d, Y', strtotime($ex['end_date'])); ?></span>
            <span style="color:var(--smoky-oak);"><strong><?php echo $art_count; ?></strong> artwork<?php echo $art_count != 1 ? 's' : ''; ?></span>
        </div>
        <?php if (!empty($ex['description'])): ?>
        <p style="line-height:1.8;color:var(--cocoa-bark);margin-bottom:1.5rem;"><?php echo nl2br(htmlspecialchars($ex['description'])); ?></p>
        <?php endif; ?>
        <?php if ($art_count > 0): ?>
        <a href="artworks.php?exhibit=<?php echo $exhibit_id; ?>" class="btn btn-primary">View Artworks in This Exhibit</a>
        <?php endif; ?>
    </div>
</div>
    <?php else: ?>
<div class="container" style="padding:3rem 1rem;text-align:center;">
    <p>Exhibit not found. <a href="exhibits.php">Return to exhibits</a></p>
</div>
    <?php endif;
    include 'includes/footer.php';
    exit;
}

$selected_classification = isset($_GET['classification']) ? intval($_GET['classification']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!-- Page Banner -->
<div class="page-hero">
    <div class="page-hero-content">
        <h1>Explore Exhibits</h1>
        <p>Discover our current and upcoming exhibitions from around the world.</p>
    </div>
</div>

    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="exhibits.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div class="filter-group">
                    <label for="classification">Filter by Classification:</label>
                    <select id="classification" name="classification" onchange="this.form.submit();">
                        <option value="0">All Categories</option>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT classification_id, name FROM exhibit_classifications ORDER BY name");
                            $stmt->execute();
                            $classifications = $stmt->fetchAll();
                            foreach ($classifications as $class) {
                                $selected = ($selected_classification == $class['classification_id']) ? 'selected' : '';
                                echo "<option value=\"{$class['classification_id']}\" {$selected}>" . htmlspecialchars($class['name']) . "</option>";
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                </div>
                <div class="filter-group" style="flex: 1; min-width: 200px;">
                    <label for="search">Search Exhibits:</label>
                    <input type="text" id="search" name="search" placeholder="Enter exhibit name..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($selected_classification || $search_query): ?>
                    <a href="exhibits.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Exhibits Grid -->
        <div class="cards-grid">
            <?php
            try {
                $query = "SELECT e.exhibit_id, e.title, e.description, e.status, 
                                 e.start_date, e.end_date, ec.name as classification 
                          FROM exhibits e 
                          LEFT JOIN exhibit_classifications ec ON e.classification_id = ec.classification_id
                          WHERE 1=1";
                $params = [];

                if ($selected_classification > 0) {
                    $query .= " AND e.classification_id = ?";
                    $params[] = $selected_classification;
                }

                if ($search_query) {
                    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
                    $search_pattern = '%' . $search_query . '%';
                    $params[] = $search_pattern;
                    $params[] = $search_pattern;
                }

                $query .= " ORDER BY e.start_date DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $exhibits = $stmt->fetchAll();

                if ($exhibits) {
                    foreach ($exhibits as $exhibit) {
                        $status_class = 'status-' . $exhibit['status'];
                        $status_text = ucfirst($exhibit['status']);
                        ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($exhibit['title']); ?></h3>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;">
                                    <?php echo htmlspecialchars($exhibit['classification'] ?? 'General'); ?>
                                </p>
                            </div>
                            <div class="card-body">
                                <p class="text-muted" style="margin-bottom:.75rem;">
                                    <strong>Exhibition Dates:</strong><br>
                                    <?php echo date('F d, Y', strtotime($exhibit['start_date'])); ?> – 
                                    <?php echo date('F d, Y', strtotime($exhibit['end_date'])); ?>
                                </p>
                            </div>
                            <div class="card-footer">
                                <span class="card-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                <a href="exhibits.php?id=<?php echo $exhibit['exhibit_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">View Details</a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-data" style="grid-column: 1/-1;"><p>No exhibits match your search criteria.</p></div>';
                }
            } catch (Exception $e) {
                echo '<div class="no-data" style="grid-column: 1/-1;"><p>Unable to load exhibits. Please try again later.</p></div>';
            }
            ?>
        </div>

    </div>

<?php include 'includes/footer.php'; ?>
