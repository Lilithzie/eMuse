<?php
require '../config/database.php';
include 'includes/header.php';

$selected_location = isset($_GET['location']) ? intval($_GET['location']) : 0;
$selected_exhibit = isset($_GET['exhibit']) ? intval($_GET['exhibit']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

    <div class="container">
        <!-- Page Title -->
        <div style="margin-bottom: 2rem;">
            <h1 class="section-title">Artifacts & Artworks</h1>
            <p class="section-subtitle">Explore our extensive collection of masterpieces and historical artifacts by location and type.</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="artworks.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
                <div class="filter-group">
                    <label for="location">Filter by Location:</label>
                    <select id="location" name="location" onchange="this.form.submit();">
                        <option value="0">All Locations</option>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT location_id, name, floor FROM locations ORDER BY floor, name");
                            $stmt->execute();
                            $locations = $stmt->fetchAll();
                            foreach ($locations as $loc) {
                                $selected = ($selected_location == $loc['location_id']) ? 'selected' : '';
                                echo "<option value=\"{$loc['location_id']}\" {$selected}>" . htmlspecialchars($loc['name'] . ' (' . $loc['floor'] . ')') . "</option>";
                            }
                        } catch (Exception $e) {}
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Search Artworks:</label>
                    <input type="text" id="search" name="search" placeholder="Title, artist, or type..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($selected_location || $search_query): ?>
                    <a href="artworks.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Artworks Grid -->
        <div class="cards-grid">
            <?php
            try {
                $query = "SELECT a.artwork_id, a.title, a.artist, a.type, a.year_created, 
                                 a.description, a.condition_status, l.name as location, l.floor,
                                 e.title as exhibit_title
                          FROM artworks a 
                          LEFT JOIN locations l ON a.location_id = l.location_id
                          LEFT JOIN exhibits e ON a.exhibit_id = e.exhibit_id
                          WHERE 1=1";
                $params = [];

                if ($selected_location > 0) {
                    $query .= " AND a.location_id = ?";
                    $params[] = $selected_location;
                }

                if ($selected_exhibit > 0) {
                    $query .= " AND a.exhibit_id = ?";
                    $params[] = $selected_exhibit;
                }

                if ($search_query) {
                    $query .= " AND (a.title LIKE ? OR a.artist LIKE ? OR a.type LIKE ? OR a.description LIKE ?)";
                    $search_pattern = '%' . $search_query . '%';
                    $params[] = $search_pattern;
                    $params[] = $search_pattern;
                    $params[] = $search_pattern;
                    $params[] = $search_pattern;
                }

                $query .= " ORDER BY a.title ASC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $artworks = $stmt->fetchAll();

                if ($artworks) {
                    foreach ($artworks as $artwork) {
                        $type_label = ucfirst($artwork['type']);
                        ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;">
                                    by <?php echo htmlspecialchars($artwork['artist'] ?? 'Unknown Artist'); ?>
                                </p>
                            </div>
                            <div class="card-body">
                                <p><?php echo htmlspecialchars($artwork['description'] ?? 'No description available'); ?></p>
                                
                                <!-- Type and Year Badges -->
                                <div style="margin-top: 1rem; margin-bottom: 1rem;">
                                    <span class="location-badge"><?php echo htmlspecialchars($type_label); ?></span>
                                    <span class="location-badge"><?php echo htmlspecialchars($artwork['year_created'] ?? 'Date Unknown'); ?></span>
                                </div>

                                <!-- Location Info -->
                                <p class="text-muted">
                                    <strong>Location:</strong><br>
                                    <?php echo htmlspecialchars($artwork['location'] ?? 'TBA'); ?>
                                    <?php if ($artwork['floor']): echo ' - ' . htmlspecialchars($artwork['floor']); endif; ?>
                                </p>

                                <!-- Condition Status -->
                                <p class="text-muted" style="margin-top: 0.5rem;">
                                    <strong>Condition:</strong> 
                                    <span class="condition-<?php echo $artwork['condition_status']; ?>">
                                        <?php echo ucfirst($artwork['condition_status']); ?>
                                    </span>
                                </p>

                                <!-- Exhibit Info -->
                                <?php if ($artwork['exhibit_title']): ?>
                                <p class="text-muted" style="margin-top: 0.5rem;">
                                    <strong>On Display:</strong> 
                                    <a href="exhibits.php?id=<?php echo htmlspecialchars($artwork['exhibit_id']); ?>" 
                                       style="color: var(--primary-light); text-decoration: none;">
                                        <?php echo htmlspecialchars($artwork['exhibit_title']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-data" style="grid-column: 1/-1;"><p>No artworks match your search criteria.</p></div>';
                }
            } catch (Exception $e) {
                echo '<div class="no-data" style="grid-column: 1/-1;"><p>Unable to load artworks. Please try again later.</p></div>';
            }
            ?>
        </div>

        <!-- Locations Directory -->
        <section style="margin-top: 3rem;">
            <h2 class="section-title">Museum Locations</h2>
            <p class="section-subtitle">Visit our collections in these locations throughout the museum.</p>
            
            <div class="cards-grid">
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT l.location_id, l.name, l.floor, l.capacity, l.description,
                                                  COUNT(a.artwork_id) as artwork_count
                                         FROM locations l
                                         LEFT JOIN artworks a ON l.location_id = a.location_id
                                         GROUP BY l.location_id
                                         ORDER BY l.floor, l.name");
                    $stmt->execute();
                    $locs = $stmt->fetchAll();
                    
                    foreach ($locs as $loc) {
                        ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($loc['name']); ?></h3>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;">
                                    <?php echo htmlspecialchars($loc['floor']); ?>
                                </p>
                            </div>
                            <div class="card-body">
                                <p><?php echo htmlspecialchars($loc['description']); ?></p>
                                <p class="text-muted" style="margin-top: 1rem;">
                                    <strong>Capacity:</strong> <?php echo $loc['capacity']; ?> people<br>
                                    <strong>Artworks:</strong> <?php echo $loc['artwork_count']; ?> items
                                </p>
                            </div>
                            <div class="card-footer">
                                <a href="artworks.php?location=<?php echo $loc['location_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">View Collection</a>
                            </div>
                        </div>
                        <?php
                    }
                } catch (Exception $e) {}
                ?>
            </div>
        </section>
    </div>

<?php include 'includes/footer.php'; ?>
