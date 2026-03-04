<?php
require '../config/database.php';
include 'includes/header.php';

$selected_classification = isset($_GET['classification']) ? intval($_GET['classification']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

    <div class="container">
        <!-- Page Title -->
        <div style="margin-bottom: 2rem;">
            <h1 class="section-title">Explore Exhibits</h1>
            <p class="section-subtitle">Discover our current and upcoming exhibitions from around the world.</p>
        </div>

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
                                <p><?php echo htmlspecialchars($exhibit['description'] ?? 'No description available'); ?></p>
                                <p class="text-muted" style="margin-top: 1rem;">
                                    <strong>Exhibition Dates:</strong><br>
                                    <?php echo date('F d, Y', strtotime($exhibit['start_date'])); ?> - 
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

        <!-- Exhibit Details Modal (if ID is set) -->
        <?php
        if (isset($_GET['id'])) {
            $exhibit_id = intval($_GET['id']);
            try {
                $stmt = $pdo->prepare("SELECT e.*, ec.name as classification 
                                     FROM exhibits e 
                                     LEFT JOIN exhibit_classifications ec ON e.classification_id = ec.classification_id
                                     WHERE e.exhibit_id = ?");
                $stmt->execute([$exhibit_id]);
                $exhibit = $stmt->fetch();
                
                if ($exhibit) {
                    // Count artworks in this exhibit
                    $art_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM artworks WHERE exhibit_id = ?");
                    $art_stmt->execute([$exhibit_id]);
                    $art_count = $art_stmt->fetch()['count'];
                    ?>
                    <div style="margin-top: 3rem; padding: 2rem; background: #f9f9f9; border-radius: 8px; border-left: 4px solid var(--primary-light);">
                        <h2 style="color: var(--primary-dark); margin-bottom: 1rem;"><?php echo htmlspecialchars($exhibit['title']); ?></h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <p><strong>Classification:</strong> <?php echo htmlspecialchars($exhibit['classification']); ?></p>
                            </div>
                            <div>
                                <p><strong>Status:</strong> <span class="card-badge status-<?php echo $exhibit['status']; ?>"><?php echo ucfirst($exhibit['status']); ?></span></p>
                            </div>
                            <div>
                                <p><strong>Artworks:</strong> <?php echo $art_count; ?></p>
                            </div>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <p><strong>Duration:</strong></p>
                            <p style="color: #666;"><?php echo date('F d, Y', strtotime($exhibit['start_date'])); ?> - <?php echo date('F d, Y', strtotime($exhibit['end_date'])); ?></p>
                        </div>
                        <div>
                            <p><strong>Description:</strong></p>
                            <p style="color: #666; line-height: 1.8;"><?php echo nl2br(htmlspecialchars($exhibit['description'])); ?></p>
                        </div>
                        <div style="margin-top: 1.5rem;">
                            <a href="artworks.php?exhibit=<?php echo $exhibit_id; ?>" class="btn btn-primary">View Artworks in This Exhibit</a>
                        </div>
                    </div>
                    <?php
                }
            } catch (Exception $e) {}
        }
        ?>
    </div>

<?php include 'includes/footer.php'; ?>
