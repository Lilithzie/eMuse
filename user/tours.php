<?php
require '../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$message_type = '';

// PRG: load flash message from session after redirect
if (isset($_SESSION['tour_flash'])) {
    $message      = $_SESSION['tour_flash']['msg'];
    $message_type = $_SESSION['tour_flash']['type'];
    unset($_SESSION['tour_flash']);
}

include 'includes/header.php';

// Handle tour booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'book_tour') {
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        header('Location: login.php?redirect=tours.php&msg=tours');
        exit;
    }
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
            $check_stmt = $pdo->prepare("SELECT t.max_capacity, t.title,
                (SELECT COALESCE(SUM(tb.number_of_people),0) FROM tour_bookings tb
                 WHERE tb.tour_id = t.tour_id AND tb.status = 'confirmed') AS current_bookings
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
                // Insert booking with booking_code
                $uid = $_SESSION['user_id'] ?? null;
                $book_stmt = $pdo->prepare("INSERT INTO tour_bookings (tour_id, visitor_name, visitor_email, number_of_people, user_id) 
                                          VALUES (?, ?, ?, ?, ?)");
                $book_stmt->execute([$tour_id, $visitor_name, $visitor_email, $num_people, $uid]);
                $new_booking_id = $pdo->lastInsertId();
                
                // Generate booking code
                $booking_code = 'TB-' . str_pad($new_booking_id, 6, '0', STR_PAD_LEFT);
                $pdo->prepare("UPDATE tour_bookings SET booking_code = ? WHERE booking_id = ?")->execute([$booking_code, $new_booking_id]);

                $msg = 'Tour booked successfully! Your booking code is <strong>' . $booking_code . '</strong>. Confirmation has been sent to ' . htmlspecialchars($visitor_email) . '.';
                $_SESSION['tour_flash'] = ['msg' => $msg, 'type' => 'success'];
                header('Location: tours.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred while booking the tour.';
        $message_type = 'error';
    }
}

// Handle tour cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_booking') {
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        header('Location: login.php?redirect=tours.php&msg=tours');
        exit;
    }
    try {
        $booking_code = strtoupper(trim($_POST['booking_code'] ?? ''));
        $cancel_email = trim($_POST['cancel_email']);
        if (empty($booking_code) || empty($cancel_email)) {
            $message = 'Please enter your booking code and email to cancel a booking.'; $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("SELECT tb.*, t.title, t.tour_date FROM tour_bookings tb JOIN tours t ON tb.tour_id=t.tour_id WHERE tb.booking_code=? AND tb.visitor_email=? AND tb.status='confirmed'");
            $stmt->execute([$booking_code, $cancel_email]); $booking = $stmt->fetch();
            if (!$booking) {
                $message = 'Booking not found or email does not match.'; $message_type = 'error';
            } elseif (strtotime($booking['tour_date']) < time()) {
                $message = 'Cannot cancel a past tour booking.'; $message_type = 'error';
            } else {
                $pdo->prepare("UPDATE tour_bookings SET status='cancelled' WHERE booking_code=?")->execute([$booking_code]);
                $_SESSION['tour_flash'] = ['msg' => "Booking {$booking_code} for '{$booking['title']}' has been cancelled.", 'type' => 'success'];
                header('Location: tours.php');
                exit;
            }
        }
    } catch (Exception $e) { $message = 'Error cancelling booking.'; $message_type = 'error'; }
}

$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!-- Page Banner -->
<div class="page-hero">
    <div class="page-hero-content">
        <h1>Guided Tours</h1>
        <p>Experience immersive, expert-led tours through our most captivating exhibits.</p>
    </div>
</div>

    <div class="container">

        <!-- Login notice overlay (shown briefly before redirecting) -->
        <div id="login-notice" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:12px;padding:2.5rem 2rem;max-width:380px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.2);">
                <div style="font-size:2.5rem;margin-bottom:.75rem;">🗺️</div>
                <h3 style="color:#2A3520;margin-bottom:.5rem;">Login Required</h3>
                <p style="color:#555;margin-bottom:1.25rem;">You need to be logged in to book a tour.</p>
                <div style="width:100%;height:4px;background:#eee;border-radius:4px;overflow:hidden;"><div style="height:100%;background:#3D4A2F;animation:nprogress 1.8s linear forwards;"></div></div>
                <p style="color:#999;font-size:.8rem;margin-top:.75rem;">Redirecting to login…</p>
            </div>
        </div>

        <!-- Page Title removed - using page-hero banner above -->

        <!-- Message Display -->
        <?php if ($message && $message_type !== 'success'): ?>
            <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 4px; background-color: #f8d7da; border-left: 4px solid #dc3545; color: #721c24;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($message && $message_type === 'success'): ?>
        <script>document.addEventListener('DOMContentLoaded',function(){showToast(<?= json_encode(htmlspecialchars($message)) ?>);});</script>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="tours.php">
                <div class="filter-group">
                    <label for="date">Filter by Date:</label>
                    <div class="date-input-wrap">
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                        <button type="button" class="date-icon-btn" onclick="this.previousElementSibling.showPicker()" title="Pick date">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </button>
                    </div>
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
                                 t.start_time, t.end_time, t.max_capacity,
                                 (SELECT COALESCE(SUM(tb.number_of_people),0) FROM tour_bookings tb WHERE tb.tour_id = t.tour_id AND tb.status = 'confirmed') AS current_bookings,
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
                        <div class="card tour-card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($tour['title']); ?></h3>
                                <p>Led by: <?php echo htmlspecialchars($tour['guide_name'] ?? 'TBA'); ?></p>
                            </div>
                            <div class="card-body">
                                <p class="tour-desc"><?php echo htmlspecialchars($tour['description']); ?></p>

                                <div class="tour-meta">
                                    <div class="tour-meta-row">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <span><?php echo date('M j, Y', strtotime($tour['tour_date'])); ?></span>
                                    </div>
                                    <div class="tour-meta-row">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <span><?php echo date('g:i A', strtotime($tour['start_time'])); ?>
                                            <?php if (!empty($tour['end_time'])): ?>– <?php echo date('g:i A', strtotime($tour['end_time'])); ?><?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="tour-meta-row">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        <span>₱<?php echo number_format($tour['price'] ?? 0, 2); ?> per person</span>
                                    </div>
                                    <div class="tour-meta-row">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        <?php if ($available > 0 && !$is_past): ?>
                                            <span style="color:#2e7d32;font-weight:600;"><?php echo $available; ?> spots available</span>
                                        <?php elseif ($is_past): ?>
                                            <span style="color:#888;">Tour has ended</span>
                                        <?php else: ?>
                                            <span style="color:#c62828;font-weight:600;">Fully booked</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <?php if ($available > 0 && !$is_past): ?>
                                    <?php if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']): ?>
                                        <a href="#" class="btn btn-primary btn-sm" onclick="openAuthPanel('login'); return false;">Login to Book</a>
                                    <?php else: ?>
                                        <button onclick="document.getElementById('bookingForm<?php echo $tour['tour_id']; ?>').style.display='block'" class="btn btn-primary btn-sm">Book Now</button>
                                    <?php endif; ?>
                                    <span class="tour-status-badge available">Available</span>
                                <?php elseif ($is_past): ?>
                                    <span class="tour-status-badge past">Past Tour</span>
                                <?php else: ?>
                                    <span class="tour-status-badge full">Fully Booked</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Booking Form Modal -->
                        <?php if ($available > 0 && !$is_past): ?>
                        <div id="bookingForm<?php echo $tour['tour_id']; ?>" style="display:none; margin-bottom: 2rem; padding: 2rem; background: #f9f9f9; border: 1px solid var(--border-color); border-radius: 8px; grid-column: 1 / -1;">
                            <h3 style="color: var(--primary-dark); margin-bottom: 1rem;">Book <?php echo htmlspecialchars($tour['title']); ?></h3>
                            <form method="POST" action="tours.php" onsubmit="return requireLogin()">
                                <input type="hidden" name="action" value="book_tour">
                                <input type="hidden" name="tour_id" value="<?php echo $tour['tour_id']; ?>">

                                <div class="form-group">
                                    <label for="visitor_name<?php echo $tour['tour_id']; ?>">Your Name:</label>
                                    <input type="text" id="visitor_name<?php echo $tour['tour_id']; ?>" name="visitor_name" required value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label for="visitor_email<?php echo $tour['tour_id']; ?>">Email Address:</label>
                                    <input type="email" id="visitor_email<?php echo $tour['tour_id']; ?>" name="visitor_email" required value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
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
        <!-- Cancel a Booking -->
        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
        <section style="margin-top:3rem;padding:2rem;background:#fff3cd;border-radius:8px;border-left:4px solid #f57c00;">
            <h2 style="color:#795548;margin-bottom:1rem;">Cancel a Booking</h2>
            <p style="color:#555;margin-bottom:1.5rem;">Enter your booking code and email address to cancel a confirmed booking.</p>
            <form method="POST" action="tours.php" style="display:grid;grid-template-columns:1fr 1fr auto;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
                <input type="hidden" name="action" value="cancel_booking">
                <div class="form-group" style="margin:0;">
                    <label>Booking Code</label>
                    <input type="text" name="booking_code" class="form-control" placeholder="e.g. TB-000012" required style="padding:.6rem;text-transform:uppercase;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Your Email</label>
                    <input type="email" name="cancel_email" class="form-control" placeholder="Email used when booking" required style="padding:.6rem;">
                </div>
                <button type="submit" class="btn btn-secondary" style="padding:.6rem 1.2rem;background:#e64a19;color:white;border:none;border-radius:4px;">Cancel Booking</button>
            </form>

            <!-- Lookup bookings by email -->
            <?php if (!empty($_GET['lookup_email'])): ?>
            <?php
                $lookupEmail = trim($_GET['lookup_email']);
                $lookupRows = $pdo->prepare("SELECT tb.*, t.title, t.tour_date FROM tour_bookings tb JOIN tours t ON tb.tour_id=t.tour_id WHERE tb.visitor_email=? ORDER BY t.tour_date DESC LIMIT 10");
                $lookupRows->execute([$lookupEmail]); $lookupRows = $lookupRows->fetchAll();
            ?>
            <div style="margin-top:1.5rem;">
                <h4>Bookings for <?= htmlspecialchars($lookupEmail) ?>:</h4>
                <?php if ($lookupRows): ?>
                <table style="width:100%;border-collapse:collapse;margin-top:.75rem;font-size:.9rem;">
                    <thead><tr style="background:#795548;color:white;"><th style="padding:.5rem;">Booking Code</th><th>Tour</th><th>Date</th><th>People</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($lookupRows as $lr): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:.5rem 1rem;font-weight:600;"><?= htmlspecialchars($lr['booking_code'] ?? 'TB-' . str_pad($lr['booking_id'], 6, '0', STR_PAD_LEFT)) ?></td>
                        <td><?= htmlspecialchars($lr['title']) ?></td>
                        <td><?= date('M j, Y', strtotime($lr['tour_date'])) ?></td>
                        <td><?= $lr['number_of_people'] ?></td>
                        <td><span><?= ucfirst($lr['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?><p style="color:#666;">No bookings found.</p><?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="GET" action="tours.php" style="margin-top:1rem;display:flex;gap:.75rem;align-items:flex-end;">
                <div class="form-group" style="margin:0;flex:1;">
                    <label style="font-size:.85rem;">Look Up My Bookings By Email:</label>
                    <input type="email" name="lookup_email" class="form-control" placeholder="Your email" style="padding:.5rem;" value="<?= htmlspecialchars($_GET['lookup_email'] ?? $_SESSION['user_email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-secondary" style="padding:.5rem 1rem;">Look Up</button>
            </form>
        </section>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>
<style>
/* ── Tour card refinements ── */
.tour-card .card-header h3 {
    font-size: 1.05rem;
    line-height: 1.3;
    margin: 0;
}
.tour-card .card-header p {
    font-size: 0.82rem;
    margin: 4px 0 0;
    opacity: 0.85;
}
.tour-desc {
    font-size: 0.88rem;
    color: #555;
    line-height: 1.55;
    margin: 0 0 1rem;
    flex: none;
}
.tour-meta {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    border-top: 1px solid rgba(109,96,83,0.1);
    padding-top: 0.85rem;
}
.tour-meta-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #444;
}
.tour-meta-row svg {
    flex-shrink: 0;
    opacity: 0.55;
    stroke: var(--chestnut-grove, #3d2b1f);
}
.tour-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.28rem 0.75rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.tour-status-badge.available {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}
.tour-status-badge.past {
    background: #f5f5f5;
    color: #757575;
    border: 1px solid #ddd;
}
.tour-status-badge.full {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}
.btn-sm {
    padding: 0.42rem 1rem !important;
    font-size: 0.85rem !important;
}
.card-footer {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}
</style>
<script>
const IS_LOGGED_IN = <?= (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) ? 'true' : 'false' ?>;
function requireLogin() {
    if (!IS_LOGGED_IN) {
        showLoginNotice('login.php?redirect=tours.php&msg=tours');
        return false;
    }
    return true;
}
function showLoginNotice(url) {
    var el = document.getElementById('login-notice');
    if (el) { el.style.display = 'flex'; setTimeout(function(){ window.location = url; }, 1800); }
    else { window.location = url; }
}
</script>
