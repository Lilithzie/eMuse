<?php
require '../config/database.php';
include 'includes/header.php';

$message = '';
$message_type = '';

// Handle tour booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'book_tour') {
    try {
        $tour_id = intval($_POST['tour_id']);
        $visitor_name = trim($_POST['visitor_name']);
        $visitor_email = trim($_POST['visitor_email']);
        $num_people = intval($_POST['number_of_people']);

        if (empty($visitor_name) || empty($visitor_email) || $num_people < 1) {
            $message = 'Please fill in all fields correctly.';
            $message_type = 'error';
        } else {
            // Check availability
            $check_stmt = $pdo->prepare("SELECT t.max_capacity, t.current_bookings, t.title 
                                        FROM tours t WHERE tour_id = ?");
            $check_stmt->execute([$tour_id]);
            $tour_info = $check_stmt->fetch();

            if (!$tour_info) {
                $message = 'Tour not found.';
                $message_type = 'error';
            } elseif (($tour_info['current_bookings'] + $num_people) > $tour_info['max_capacity']) {
                $message = 'Not enough available spaces. Only ' . ($tour_info['max_capacity'] - $tour_info['current_bookings']) . ' spots remaining.';
                $message_type = 'error';
            } else {
                // Insert booking
                $book_stmt = $pdo->prepare("INSERT INTO tour_bookings (tour_id, visitor_name, visitor_email, number_of_people) 
                                          VALUES (?, ?, ?, ?)");
                $book_stmt->execute([$tour_id, $visitor_name, $visitor_email, $num_people]);

                // Update current bookings
                $update_stmt = $pdo->prepare("UPDATE tours SET current_bookings = current_bookings + ? WHERE tour_id = ?");
                $update_stmt->execute([$num_people, $tour_id]);

                $message = 'Tour booked successfully! Confirmation has been sent to ' . htmlspecialchars($visitor_email) . '.';
                $message_type = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred while booking the tour.';
        $message_type = 'error';
    }
}

$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

    <div class="container">
        <!-- Page Title -->
        <div style="margin-bottom: 2rem;">
            <h1 class="section-title">Guided Tours</h1>
            <p class="section-subtitle">Experience immersive, expert-led tours through our most captivating exhibits.</p>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 4px; background-color: <?php echo ($message_type == 'success') ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo ($message_type == 'success') ? '#28a745' : '#dc3545'; ?>; color: <?php echo ($message_type == 'success') ? '#155724' : '#721c24'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="tours.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
                <div class="filter-group">
                    <label for="date">Filter by Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="filter-group">
                    <label for="search">Search Tours:</label>
                    <input type="text" id="search" name="search" placeholder="Tour name or guide..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($date_filter || $search_query): ?>
                    <a href="tours.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tours Grid -->
        <div class="cards-grid">
            <?php
            try {
                $query = "SELECT t.tour_id, t.title, t.description, t.tour_date, 
                                 t.start_time, t.end_time, t.max_capacity, t.current_bookings, 
                                 t.price, t.status, tg.full_name as guide_name
                          FROM tours t
                          LEFT JOIN tour_guides tg ON t.guide_id = tg.guide_id
                          WHERE t.status IN ('scheduled', 'ongoing')";
                $params = [];

                if ($date_filter) {
                    $query .= " AND DATE(t.tour_date) = ?";
                    $params[] = $date_filter;
                }

                if ($search_query) {
                    $query .= " AND (t.title LIKE ? OR t.description LIKE ? OR tg.full_name LIKE ?)";
                    $search_pattern = '%' . $search_query . '%';
                    $params[] = $search_pattern;
                    $params[] = $search_pattern;
                    $params[] = $search_pattern;
                }

                $query .= " ORDER BY t.tour_date ASC, t.start_time ASC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $tours = $stmt->fetchAll();

                if ($tours) {
                    foreach ($tours as $tour) {
                        $available = $tour['max_capacity'] - $tour['current_bookings'];
                        $capacity_percent = ($tour['current_bookings'] / $tour['max_capacity']) * 100;
                        $tour_date = new DateTime($tour['tour_date']);
                        $is_past = $tour_date < new DateTime();
                        ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($tour['title']); ?></h3>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.9;">
                                    Led by: <?php echo htmlspecialchars($tour['guide_name'] ?? 'TBA'); ?>
                                </p>
                            </div>
                            <div class="card-body">
                                <p><?php echo htmlspecialchars($tour['description']); ?></p>

                                <!-- Tour Details -->
                                <div style="margin-top: 1rem; padding: 1rem; background-color: #f9f9f9; border-radius: 4px;">
                                    <p class="text-muted" style="margin-bottom: 0.5rem;">
                                        <strong>📅 Date:</strong> <?php echo date('l, F d, Y', strtotime($tour['tour_date'])); ?>
                                    </p>
                                    <p class="text-muted" style="margin-bottom: 0.5rem;">
                                        <strong>⏰ Time:</strong> <?php echo date('g:i A', strtotime($tour['start_time'])); ?> - <?php echo date('g:i A', strtotime($tour['end_time'])); ?>
                                    </p>
                                    <p class="text-muted" style="margin-bottom: 0.5rem;">
                                        <strong>💰 Price:</strong> $<?php echo number_format($tour['price'] ?? 0, 2); ?> per person
                                    </p>
                                </div>

                                <!-- Capacity Information -->
                                <div style="margin-top: 1rem;">
                                    <p style="font-weight: 600; margin-bottom: 0.5rem;">Tour Capacity:</p>
                                    <div class="capacity-info">
                                        <div class="capacity-bar">
                                            <div class="capacity-fill" style="width: <?php echo min($capacity_percent, 100); ?>%; background: linear-gradient(90deg, var(--primary-light), #ffd89b);">
                                                <?php if ($capacity_percent > 10) echo $tour['current_bookings'] . '/' . $tour['max_capacity']; ?>
                                            </div>
                                        </div>
                                        <span class="text-muted" style="white-space: nowrap; margin-left: 0.5rem;">
                                            <?php echo max(0, $available); ?> spots available
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <?php if ($available > 0 && !$is_past): ?>
                                    <span class="card-badge" style="background-color: #4CAF50; color: white;">Available</span>
                                    <button onclick="document.getElementById('bookingForm<?php echo $tour['tour_id']; ?>').style.display='block'" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                        Book Now
                                    </button>
                                <?php elseif ($is_past): ?>
                                    <span class="card-badge" style="background-color: #999; color: white;">Past Tour</span>
                                <?php else: ?>
                                    <span class="card-badge" style="background-color: #f44336; color: white;">Full</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Booking Form Modal -->
                        <?php if ($available > 0 && !$is_past): ?>
                        <div id="bookingForm<?php echo $tour['tour_id']; ?>" style="display:none; margin-bottom: 2rem; padding: 2rem; background: #f9f9f9; border: 1px solid var(--border-color); border-radius: 8px;">
                            <h3 style="color: var(--primary-dark); margin-bottom: 1rem;">Book <?php echo htmlspecialchars($tour['title']); ?></h3>
                            <form method="POST" action="tours.php">
                                <input type="hidden" name="action" value="book_tour">
                                <input type="hidden" name="tour_id" value="<?php echo $tour['tour_id']; ?>">

                                <div class="form-group">
                                    <label for="visitor_name<?php echo $tour['tour_id']; ?>">Your Name:</label>
                                    <input type="text" id="visitor_name<?php echo $tour['tour_id']; ?>" name="visitor_name" required>
                                </div>

                                <div class="form-group">
                                    <label for="visitor_email<?php echo $tour['tour_id']; ?>">Email Address:</label>
                                    <input type="email" id="visitor_email<?php echo $tour['tour_id']; ?>" name="visitor_email" required>
                                </div>

                                <div class="form-group">
                                    <label for="number_of_people<?php echo $tour['tour_id']; ?>">Number of Participants:</label>
                                    <input type="number" id="number_of_people<?php echo $tour['tour_id']; ?>" name="number_of_people" min="1" max="<?php echo $available; ?>" required>
                                    <small style="color: var(--text-light);">Maximum <?php echo $available; ?> available</small>
                                </div>

                                <div style="display: flex; gap: 1rem;">
                                    <button type="submit" class="btn btn-primary">Confirm Booking</button>
                                    <button type="button" onclick="document.getElementById('bookingForm<?php echo $tour['tour_id']; ?>').style.display='none'" class="btn btn-secondary">Cancel</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        <?php
                    }
                } else {
                    echo '<div class="no-data" style="grid-column: 1/-1;"><p>No tours match your search criteria.</p></div>';
                }
            } catch (Exception $e) {
                echo '<div class="no-data" style="grid-column: 1/-1;"><p>Unable to load tours. Please try again later.</p></div>';
            }
            ?>
        </div>

        <!-- Tour Information Section -->
        <section style="margin-top: 3rem; padding: 2rem; background: var(--primary-accent); border-radius: 8px; border-left: 4px solid var(--primary-light);">
            <h2 style="color: var(--primary-dark); margin-bottom: 1rem;">About Our Guided Tours</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; color: #333;">
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">Expert Guidance</h4>
                    <p>Our experienced guides provide deep insights into the stories and significance of each piece in our collection.</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">Immersive Experience</h4>
                    <p>Interactive tours make our collections come alive with engaging narratives and historical context.</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">Flexible Scheduling</h4>
                    <p>Choose from various tour times throughout the day and week to fit your schedule.</p>
                </div>
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">Group Tours Available</h4>
                    <p>Book tours for your group and enjoy the camaraderie of exploring together.</p>
                </div>
            </div>
        </section>
    </div>

<?php include 'includes/footer.php'; ?>
