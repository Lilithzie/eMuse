<?php
require '../config/database.php';
include 'includes/header.php';

// --- Detail view ---
if (isset($_GET['id'])) {
    $artwork_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare(
            "SELECT a.*, l.name as location_name, l.floor,
                    e.title as exhibit_title, e.exhibit_id as eid
             FROM artworks a
             LEFT JOIN locations l ON a.location_id = l.location_id
             LEFT JOIN exhibits e  ON a.exhibit_id  = e.exhibit_id
             WHERE a.artwork_id = ?"
        );
        $stmt->execute([$artwork_id]);
        $aw = $stmt->fetch();
    } catch (Exception $e) { $aw = null; }

    if ($aw): ?>
<div class="page-hero">
    <div class="page-hero-content">
        <h1><?php echo htmlspecialchars($aw['title']); ?></h1>
        <p>by <?php echo htmlspecialchars($aw['artist'] ?? 'Unknown Artist'); ?></p>
    </div>
</div>
<div class="container" style="padding-top:2rem;">
    <a href="artworks.php" class="btn btn-secondary" style="margin-bottom:1.5rem;display:inline-block;">&larr; Back to Artworks</a>
    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:2.5rem;align-items:start;">
        <?php if (!empty($aw['image_path'])): ?>
        <div style="border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12);">
            <img src="../<?php echo htmlspecialchars($aw['image_path']); ?>"
                 alt="<?php echo htmlspecialchars($aw['title']); ?>"
                 style="width:100%;display:block;object-fit:cover;">
        </div>
        <?php else: ?>
        <div style="border-radius:12px;background:#f0ebe0;height:300px;display:flex;align-items:center;justify-content:center;color:#999;font-size:1rem;">No Image Available</div>
        <?php endif; ?>
        <div>
            <h2 style="margin-bottom:.5rem;"><?php echo htmlspecialchars($aw['title']); ?></h2>
            <p style="color:var(--smoky-oak);margin-bottom:1.5rem;">by <?php echo htmlspecialchars($aw['artist'] ?? 'Unknown Artist'); ?></p>
            <table style="width:100%;border-collapse:collapse;margin-bottom:1.5rem;">
                <tr><td style="padding:.45rem 0;font-weight:600;width:42%;color:var(--chestnut-grove);">Type</td><td><?php echo htmlspecialchars(ucfirst($aw['type'] ?? 'N/A')); ?></td></tr>
                <tr><td style="padding:.45rem 0;font-weight:600;color:var(--chestnut-grove);">Year Created</td><td><?php echo htmlspecialchars($aw['year_created'] ?? 'Unknown'); ?></td></tr>
                <tr><td style="padding:.45rem 0;font-weight:600;color:var(--chestnut-grove);">Condition</td><td><?php echo htmlspecialchars(ucfirst($aw['condition_status'] ?? 'N/A')); ?></td></tr>
                <tr><td style="padding:.45rem 0;font-weight:600;color:var(--chestnut-grove);">Location</td><td><?php echo htmlspecialchars(($aw['location_name'] ?? 'TBA') . (!empty($aw['floor']) ? ' — '.$aw['floor'] : '')); ?></td></tr>
                <?php if (!empty($aw['exhibit_title'])): ?>
                <tr><td style="padding:.45rem 0;font-weight:600;color:var(--chestnut-grove);">Exhibit</td>
                    <td><a href="exhibits.php?id=<?php echo $aw['eid']; ?>" style="color:var(--golden-sand);"><?php echo htmlspecialchars($aw['exhibit_title']); ?></a></td></tr>
                <?php endif; ?>
            </table>
            <?php if (!empty($aw['description'])): ?>
            <p style="line-height:1.8;color:var(--cocoa-bark);"><?php echo nl2br(htmlspecialchars($aw['description'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
    <?php else: ?>
<div class="container" style="padding:3rem 1rem;text-align:center;">
    <p>Artwork not found. <a href="artworks.php">Return to collection</a></p>
</div>
    <?php endif;
    include 'includes/footer.php';
    exit;
}

$selected_location = isset($_GET['location']) ? intval($_GET['location']) : 0;
$selected_exhibit = isset($_GET['exhibit']) ? intval($_GET['exhibit']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!-- Page Banner -->
<div class="page-hero">
    <div class="page-hero-content">
        <h1>Artifacts &amp; Artworks</h1>
        <p>Explore our extensive collection of masterpieces and historical artifacts.</p>
    </div>
</div>

    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="artworks.php">
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
                                 a.description, a.condition_status, a.image_path, a.exhibit_id,
                                 l.name as location, l.floor,
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
                        ?>
                        <div class="card">
                            <?php if (!empty($artwork['image_path'])): ?>
                                <div class="card-image" style="height: 200px; overflow: hidden; border-radius: 8px 8px 0 0;">
                                    <img src="../<?php echo htmlspecialchars($artwork['image_path']); ?>"
                                         alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>

                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;">
                                    by <?php echo htmlspecialchars($artwork['artist'] ?? 'Unknown Artist'); ?>
                                </p>
                            </div>

                            <div class="card-body">
                                <div style="margin-top: 0.5rem; margin-bottom: 1rem;">
                                    <span class="location-badge"><?php echo htmlspecialchars(ucfirst($artwork['type'])); ?></span>
                                    <span class="location-badge"><?php echo htmlspecialchars($artwork['year_created'] ?? 'Date Unknown'); ?></span>
                                </div>

                                <p class="text-muted">
                                    <strong>Location:</strong><br>
                                    <?php echo htmlspecialchars($artwork['location'] ?? 'TBA'); ?>
                                    <?php if (!empty($artwork['floor'])): ?> - <?php echo htmlspecialchars($artwork['floor']); ?><?php endif; ?>
                                </p>
                                <?php if (!empty($artwork['exhibit_title'])): ?>
                                <p class="text-muted" style="margin-top: 0.5rem;">
                                    <strong>On Display:</strong>
                                    <a href="exhibits.php?id=<?php echo $artwork['exhibit_id']; ?>" style="color: var(--primary-light); text-decoration: none;">
                                        <?php echo htmlspecialchars($artwork['exhibit_title']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="artworks.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">View Details</a>
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
