<?php
require 'config/database.php';
include 'includes/user-header.php';
?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-inner">
            <div>
                <h1>Welcome to eMuse</h1>
                <h3> ______________________________________________________</h3>
                <p>A place where stories live and creativity thrives. Our museum invites you to explore unique exhibits, discover cultural treasures, and experience art and history up close. Whether you’re visiting for learning, inspiration, or enjoyment, we are here to make your journey memorable through engaging displays, guided tours, and welcoming spaces.</p>
                <div class="hero-cta">
                    <a href="exhibits.php" class="btn btn-primary btn-lg">Explore Exhibits</a>
                    <a href="tickets.php" class="btn btn-outline-primary btn-lg">Book Your Visit</a>
                </div>
            </div>
            <div class="hero-media">
                <img src="img/hero.jpg" alt="eMuse Museum Hero" style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--card-radius);">
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
                                echo '<a href="exhibits.php?classification=' . $class['classification_id'] . '" title="' . htmlspecialchars($class['name']) . '" style="display: inline-flex; align-items: center; justify-content: center; width: 120px; height: 80px; background: linear-gradient(135deg, var(--btn-bg) 0%, var(--smoky-oak) 100%); color: var(--text-light); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; text-align: center; padding: 1rem; transition: all 0.3s; margin: 0.5rem;">' . htmlspecialchars($class['name']) . '</a>';
                            }
                        }
                    } catch (Exception $e) {}
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Container -->
    <div class="container">
        <!-- Featured Exhibits Section -->
        <section id="featured-exhibits" class="py-5">
            <div class="container text-center">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Featured Exhibits</h2>
                    <a href="exhibits.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
                </div>
                <p class="section-subtitle">Explore our current and upcoming exhibitions curated by world-class experts.</p>
                
                <div class="products-grid">
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
                                <div class="product-item">
                                    <div class="card shadow-sm product-card">
                                        <div style="position: relative; overflow: hidden; border-radius: 12px 12px 0 0; background: linear-gradient(135deg, var(--btn-bg) 0%, var(--smoky-oak) 100%); min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <div style="color: var(--text-light); text-align: center; padding: 2rem;">
                                                <h4 style="margin: 0; font-size: 1.3rem;"><?php echo htmlspecialchars($exhibit['classification'] ?? 'General'); ?></h4>
                                            </div>
                                            <?php if ($exhibit['status'] === 'active'): ?>
                                                <div class="badge-bestseller">Active</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title product-name"><?php echo htmlspecialchars($exhibit['title']); ?></h5>
                                            <p class="product-scent"><?php echo htmlspecialchars($exhibit['classification'] ?? 'General'); ?></p>
                                            <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                                <?php echo date('M d, Y', strtotime($exhibit['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($exhibit['end_date'])); ?>
                                            </p>
                                            
                                            <div class="product-actions" style="margin-top: 1rem;">
                                                <a href="exhibits.php?id=<?php echo $exhibit['exhibit_id']; ?>" class="btn btn-dark btn-sm" style="width: 100%; margin-bottom: 0.5rem;">Learn More</a>
                                                <span class="card-badge <?php echo $status_class; ?>" style="display: inline-block; width: 100%; text-align: center;"><?php echo $status_text; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">No exhibits currently available.</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">Unable to load exhibits.</div>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Artifacts & Artworks Section -->
        <section id="popular-artworks" class="py-5">
            <div class="container text-center">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Featured Artworks</h2>
                    <a href="artworks.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
                </div>
                <p class="section-subtitle">Discover masterpieces from our extensive collection across all locations.</p>
                
                <div class="products-grid">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT a.artwork_id, a.title, a.artist, a.type, a.year_created, 
                                             l.name as location, a.description
                                             FROM artworks a 
                                             LEFT JOIN locations l ON a.location_id = l.location_id
                                             ORDER BY a.artwork_id DESC
                                             LIMIT 6");
                        $stmt->execute();
                        $artworks = $stmt->fetchAll();
                        
                        if ($artworks) {
                            foreach ($artworks as $artwork) {
                                ?>
                                <div class="product-item">
                                    <div class="card shadow-sm product-card">
                                        <div style="position: relative; overflow: hidden; border-radius: 12px 12px 0 0; background: linear-gradient(135deg, var(--accent) 0%, #e0d4c1 100%); min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <div style="color: var(--text-dark); text-align: center; padding: 2rem;">
                                                <h4 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($artwork['type']); ?></h4>
                                                <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem;"><?php echo htmlspecialchars($artwork['year_created'] ?? 'Date Unknown'); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title product-name"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                                            <p class="product-scent"><?php echo htmlspecialchars($artwork['artist'] ?? 'Unknown Artist'); ?></p>
                                            <p style="color: var(--text-dark); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                                <strong>Location:</strong> <?php echo htmlspecialchars($artwork['location'] ?? 'TBA'); ?>
                                            </p>
                                            
                                            <div class="product-actions" style="margin-top: 1rem;">
                                                <a href="artworks.php?id=<?php echo $artwork['artwork_id']; ?>" class="btn btn-dark btn-sm" style="width: 100%;">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">No artworks currently available.</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">Unable to load artworks.</div>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Guided Tours Section -->
        <section id="guided-tours" class="py-5">
            <div class="container text-center">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Guided Tours</h2>
                    <a href="tours.php" class="btn btn-sm btn-outline-secondary">Browse All</a>
                </div>
                <p class="section-subtitle">Experience expert-led tours through our most spectacular exhibits.</p>
                
                <div class="products-grid">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT t.tour_id, t.title, t.description, t.tour_date, 
                                             t.start_time, t.end_time, t.max_capacity, t.current_bookings, 
                                             t.price, tg.full_name as guide_name
                                             FROM tours t
                                             LEFT JOIN tour_guides tg ON t.guide_id = tg.guide_id
                                             WHERE t.status IN ('scheduled', 'ongoing')
                                             ORDER BY t.tour_date ASC
                                             LIMIT 6");
                        $stmt->execute();
                        $tours = $stmt->fetchAll();
                        
                        if ($tours) {
                            foreach ($tours as $tour) {
                                $available = $tour['max_capacity'] - $tour['current_bookings'];
                                $capacity_percent = ($tour['current_bookings'] / $tour['max_capacity']) * 100;
                                ?>
                                <div class="product-item">
                                    <div class="card shadow-sm product-card">
                                        <div style="position: relative; overflow: hidden; border-radius: 12px 12px 0 0; background: linear-gradient(135deg, var(--smoky-oak) 0%, var(--btn-bg) 100%); min-height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 2rem;">
                                            <div style="color: var(--text-light); text-align: center;">
                                                <h4 style="margin: 0; font-size: 1.1rem;">📅 <?php echo date('M d, Y', strtotime($tour['tour_date'])); ?></h4>
                                                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">⏰ <?php echo date('g:i A', strtotime($tour['start_time'])); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title product-name"><?php echo htmlspecialchars($tour['title']); ?></h5>
                                            <p class="product-scent">Led by: <?php echo htmlspecialchars($tour['guide_name'] ?? 'TBA'); ?></p>
                                            <p style="color: var(--text-dark); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                                <strong>Price:</strong> $<?php echo number_format($tour['price'] ?? 0, 2); ?>
                                            </p>
                                            
                                            <!-- Capacity Bar -->
                                            <div class="capacity-info" style="margin: 0.5rem 0;">
                                                <div class="capacity-bar">
                                                    <div class="capacity-fill" style="width: <?php echo min($capacity_percent, 100); ?>%; background: linear-gradient(90deg, var(--primary-light), #ffd89b);">
                                                        <?php if ($capacity_percent > 10) echo $tour['current_bookings'] . '/' . $tour['max_capacity']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="product-actions" style="margin-top: 1rem;">
                                                <a href="tours.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-dark btn-sm" style="width: 100%; margin-bottom: 0.5rem;">Book Tour</a>
                                                <?php if ($available > 0): ?>
                                                    <span class="card-badge" style="display: inline-block; width: 100%; color: white;"><?php echo $available; ?> Spots Available</span>
                                                <?php else: ?>
                                                    <span class="card-badge" style="display: inline-block; width: 100%; color: white;">Tour Full</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">No tours currently available.</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">Unable to load tours.</div>';
                    }
                    ?>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="section">
            <div class="info-box" style="text-align: center; padding: 3rem 2rem;">
                <h3 style="font-size: 1.8rem;  margin-bottom: 1rem;">Ready for Your Museum Experience?</h3>
                <p style="font-size: 1.1rem;  margin-bottom: 2rem;">Plan your visit today and discover the wonders that await you.</p>
                <div class="hero-buttons" style="justify-content: center;">
                    <a href="tickets.php" class="btn btn-primary">Purchase Tickets</a>
                    <a href="tours.php" class="btn btn-secondary">Schedule a Guided Tour</a>
                </div>
            </div>
        </section>
    </div>

<?php include 'includes/user-footer.php'; ?>
