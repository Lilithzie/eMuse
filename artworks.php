<?php
require 'config/database.php';

// ── Filter Parameters ──
$selected_location = isset($_GET['location']) ? intval($_GET['location']) : 0;
$selected_exhibit  = isset($_GET['exhibit'])  ? intval($_GET['exhibit'])  : 0;
$search_query      = isset($_GET['search'])   ? trim($_GET['search'])    : '';

// ── Fetch Locations (for filter dropdown) ──
$locations = [];
try {
    $stmt = $pdo->prepare("SELECT location_id, name, floor FROM locations ORDER BY floor, name");
    $stmt->execute();
    $locations = $stmt->fetchAll();
} catch (Exception $e) {}

// ── Fetch Artworks ──
$artworks     = [];
$artworks_err = false;
try {
    $query = "SELECT a.artwork_id, a.title, a.artist, a.type, a.year_created,
                     a.description, a.condition_status, a.image_path,
                     l.name AS location, l.floor,
                     e.title AS exhibit_title
              FROM artworks a
              LEFT JOIN locations l ON a.location_id = l.location_id
              LEFT JOIN exhibits  e ON a.exhibit_id  = e.exhibit_id
              WHERE 1=1";
    $params = [];

    if ($selected_location > 0) {
        $query   .= " AND a.location_id = ?";
        $params[] = $selected_location;
    }
    if ($selected_exhibit > 0) {
        $query   .= " AND a.exhibit_id = ?";
        $params[] = $selected_exhibit;
    }
    if ($search_query) {
        $query .= " AND (a.title LIKE ? OR a.artist LIKE ? OR a.type LIKE ? OR a.description LIKE ?)";
        $pattern  = '%' . $search_query . '%';
        $params   = array_merge($params, [$pattern, $pattern, $pattern, $pattern]);
    }

    $query .= " ORDER BY a.artwork_id ASC";
    $stmt   = $pdo->prepare($query);
    $stmt->execute($params);
    $artworks = $stmt->fetchAll();
} catch (Exception $e) {
    $artworks_err = true;
}

// ── Fetch Museum Locations (directory section) ──
$museum_locations = [];
try {
    $stmt = $pdo->prepare("SELECT l.location_id, l.name, l.floor, l.capacity, l.description,
                                  COUNT(a.artwork_id) AS artwork_count
                           FROM locations l
                           LEFT JOIN artworks a ON l.location_id = a.location_id
                           GROUP BY l.location_id
                           ORDER BY l.floor, l.name");
    $stmt->execute();
    $museum_locations = $stmt->fetchAll();
} catch (Exception $e) {}

include 'includes/user-header.php';
?>

<!-- ═══════════════════════════════════════════
     Page Content
     ═══════════════════════════════════════════ -->
<div class="container">

    <!-- Page Title -->
    <div style="margin-bottom: 2rem;">
        <h1 class="section-title">Artifacts & Artworks</h1>
        <p class="section-subtitle">Explore our extensive collection of masterpieces and historical artifacts by location and type.</p>
    </div>

    <!-- ── Filter Section ── -->
    <div class="filter-section">
        <form method="GET" action="artworks.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
            <div class="filter-group">
                <label for="location">Filter by Location:</label>
                <select id="location" name="location" onchange="this.form.submit();">
                    <option value="0">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo $loc['location_id']; ?>" <?php echo ($selected_location == $loc['location_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['name'] . ' (' . $loc['floor'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
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

    <!-- ── Artworks Grid ── -->
    <div class="cards-grid">
        <?php if ($artworks_err): ?>
            <div class="no-data" style="grid-column: 1/-1;">
                <p>Unable to load artworks. Please try again later.</p>
            </div>
        <?php elseif (empty($artworks)): ?>
            <div class="no-data" style="grid-column: 1/-1;">
                <p>No artworks match your search criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($artworks as $artwork): ?>
                <div class="card artwork-card">
                    <?php if (!empty($artwork['image_path'])): ?>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>"
                                 alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                        <p>by <?php echo htmlspecialchars($artwork['artist'] ?? 'Unknown Artist'); ?></p>
                    </div>

                    <div class="card-body">
                        <p><?php echo htmlspecialchars($artwork['description'] ?? 'No description available'); ?></p>

                        <!-- Type & Year Badges -->
                        <div style="margin-top: 1rem; margin-bottom: 1rem;">
                            <span class="location-badge"><?php echo htmlspecialchars(ucfirst($artwork['type'])); ?></span>
                            <span class="location-badge"><?php echo htmlspecialchars($artwork['year_created'] ?? 'Date Unknown'); ?></span>
                        </div>

                        <!-- Location -->
                        <p class="text-muted">
                            <strong>Location:</strong><br>
                            <?php echo htmlspecialchars($artwork['location'] ?? 'TBA'); ?>
                            <?php if (!empty($artwork['floor'])): ?> - <?php echo htmlspecialchars($artwork['floor']); ?><?php endif; ?>
                        </p>

                        <!-- Condition -->
                        <p class="text-muted" style="margin-top: 0.5rem;">
                            <strong>Condition:</strong>
                            <span class="condition-<?php echo $artwork['condition_status']; ?>">
                                <?php echo ucfirst($artwork['condition_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Museum Locations Directory ── -->
    <?php if (!empty($museum_locations)): ?>
    <section style="margin-top: 3rem;">
        <h2 class="section-title">Museum Locations</h2>
        <p class="section-subtitle">Visit our collections in these locations throughout the museum.</p>

        <div class="cards-grid">
            <?php foreach ($museum_locations as $loc): ?>
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
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php include 'includes/user-footer.php'; ?>
