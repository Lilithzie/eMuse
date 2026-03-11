<?php
require '../config/database.php';
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-media">
                <img src="../img/hero.jpg" alt="eMuse Museum Hero">
            </div>
            <div>
                <h1>Welcome to <?php echo MUSEUM_NAME; ?></h1>
                <div class="hero-divider"></div>
                <p>A place where stories live and creativity thrives. Our museum invites you to explore unique exhibits, discover cultural treasures, and experience art and history up close. Whether you're visiting for learning, inspiration, or enjoyment, we are here to make your journey memorable through engaging displays, guided tours, and welcoming spaces.</p>
                <div class="hero-cta">
                    <a href="exhibits.php" class="btn btn-primary btn-lg">Explore Exhibits</a>
                    <a href="tickets.php" class="btn btn-outline-primary btn-lg" style="background-color: #ffffff00; color: white;">Book Your Visit</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Classifications Section -->
    <section class="py-5 featured-brands">
            <h3 class="mb-3 text-center">Explore by Classification</h3>
                <div class="brand-logos">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT classification_id, name FROM exhibit_classifications LIMIT 10");
                        $stmt->execute();
                        $classifications = $stmt->fetchAll();
                        
                        if ($classifications) {
                            foreach ($classifications as $class) {
                                echo '<a href="exhibits.php?classification=' . $class['classification_id'] . '" title="' . htmlspecialchars($class['name']) . '" style="display: inline-flex; align-items: center; justify-content: center; width: 120px; height: 80px; background: var(--btn-bg); color: var(--text-light); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; text-align: center; padding: 1rem; transition: all 0.3s; margin: 0.5rem;">' . htmlspecialchars($class['name']) . '</a>';
                            }
                        }
                    } catch (Exception $e) {}
                    ?>
                </div>
    </section>

    <!-- Main Container -->
    <div class="container">
        <!-- Featured Exhibits Section -->
        <section id="featured-exhibits" class="py-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Featured Exhibits</h2>
                <a href="exhibits.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
            </div>
            <p class="section-subtitle">Explore our current and upcoming exhibitions curated by world-class experts.</p>
            <div class="cards-grid">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT e.exhibit_id, e.title, e.description, e.status, 
                                             e.start_date, e.end_date, ec.name as classification 
                                             FROM exhibits e 
                                             LEFT JOIN exhibit_classifications ec ON e.classification_id = ec.classification_id
                                             WHERE e.status IN ('active', 'upcoming')
                                             ORDER BY e.start_date DESC
                                             LIMIT 6");
                        $stmt->execute();
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
                                        <div style="margin-top: 0.5rem; margin-bottom: 1rem;">
                                            <span class="location-badge"><?php echo $status_text; ?></span>
                                            <span class="location-badge"><?php echo htmlspecialchars($exhibit['classification'] ?? 'General'); ?></span>
                                        </div>
                                        
                                        <p class="text-muted">
                                            <strong>Duration:</strong><br>
                                            <?php echo date('M d, Y', strtotime($exhibit['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($exhibit['end_date'])); ?>
                                        </p>
                                    </div>
                                    <div class="card-footer">
                                        <a href="exhibits.php?id=<?php echo $exhibit['exhibit_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Learn More</a>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-data" style="grid-column: 1/-1;"><p>No exhibits currently available.</p></div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="no-data" style="grid-column: 1/-1;"><p>Unable to load exhibits.</p></div>';
                    }
                    ?>
            </div>
        </section>

        <!-- Artifacts & Artworks Section -->
        <section id="popular-artworks" class="py-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Featured Artworks</h2>
                <a href="artworks.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
            </div>
            <p class="section-subtitle">Discover masterpieces from our extensive collection across all locations.</p>
            <div class="cards-grid">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT a.artwork_id, a.title, a.artist, a.type, a.year_created, 
                                             l.name as location, l.floor, a.description, a.image_path
                                             FROM artworks a 
                                             LEFT JOIN locations l ON a.location_id = l.location_id
                                             ORDER BY a.artwork_id
                                             LIMIT 20");
                        $stmt->execute();
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
                                    </div>
                                    <div class="card-footer">
                                        <a href="artworks.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Explore More</a>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-data" style="grid-column: 1/-1;"><p>No artworks currently available.</p></div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="no-data" style="grid-column: 1/-1;"><p>Unable to load artworks.</p></div>';
                    }
                    ?>
            </div>
        </section>

        <!-- Guided Tours Section -->
        <section id="guided-tours" class="py-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Guided Tours</h2>
                <a href="tours.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
            </div>
            <p class="section-subtitle">Experience expert-led tours through our most spectacular exhibits.</p>
            <div class="cards-grid">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT t.tour_id, t.title, t.description, t.tour_date, 
                                             t.start_time, t.end_time, t.max_capacity,
                                             (SELECT COALESCE(SUM(tb.number_of_people),0) FROM tour_bookings tb WHERE tb.tour_id = t.tour_id AND tb.status = 'confirmed') AS current_bookings,
                                             t.price, tg.full_name as guide_name
                                             FROM tours t
                                             LEFT JOIN tour_guides tg ON t.guide_id = tg.guide_id
                                             WHERE t.status IN ('scheduled', 'ongoing') AND t.tour_date >= CURDATE()
                                             ORDER BY t.tour_date ASC
                                             LIMIT 20");
                        $stmt->execute();
                        $tours = $stmt->fetchAll();
                        
                        if ($tours) {
                            foreach ($tours as $tour) {
                                $available = $tour['max_capacity'] - $tour['current_bookings'];
                                $capacity_percent = ($tour['current_bookings'] / $tour['max_capacity']) * 100;
                                ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h3><?php echo htmlspecialchars($tour['title']); ?></h3>
                                        <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;">
                                            Led by: <?php echo htmlspecialchars($tour['guide_name'] ?? 'TBA'); ?>
                                        </p>
                                    </div>
                                    <div class="card-body">
                                        <p><?php echo htmlspecialchars($tour['description'] ?? 'No description available'); ?></p>
                                        
                                        <div style="margin-top: 1rem; margin-bottom: 1rem;">
                                            <span class="location-badge"><?php echo date('M d, Y', strtotime($tour['tour_date'])); ?></span>
                                            <span class="location-badge"><?php echo date('g:i A', strtotime($tour['start_time'])); ?></span>
                                        </div>
                                        
                                        <p class="text-muted">
                                            <strong>Price:</strong> ₱<?php echo number_format($tour['price'] ?? 0, 2); ?>
                                        </p>
                                        
                                        <p class="text-muted" style="margin-top: 0.5rem;">
                                            <strong>Availability:</strong>
                                            <?php if ($available > 0): ?>
                                                <span style="color: green;"><?php echo $available; ?> spots available</span>
                                            <?php else: ?>
                                                <span style="color: red;">Tour Full</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="card-footer">
                                        <a href="tours.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Book Tour</a>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-data" style="grid-column: 1/-1;"><p>No tours currently available.</p></div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="no-data" style="grid-column: 1/-1;"><p>Unable to load tours.</p></div>';
                    }
                    ?>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="section">
            <div class="info-box" style="text-align: center; padding: 3rem 2rem;">
                <h3 style="font-size: 1.8rem; margin-bottom: 1rem;">Ready for Your Museum Experience?</h3>
                <p style="font-size: 1.1rem; margin-bottom: 2rem;">Plan your visit today and discover the wonders that await you.</p>
                <div class="hero-buttons" style="justify-content: center;">
                    <a href="tickets.php" class="btn btn-primary">Purchase Tickets</a>
                    <a href="tours.php" class="btn btn-secondary">Schedule a Guided Tour</a>
                </div>
            </div>
        </section>
    </div>

<?php include 'includes/footer.php'; ?>
                                        