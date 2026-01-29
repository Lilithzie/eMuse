<?php
require_once '../config/config.php';
checkAuth();

// Get statistics
$today = date('Y-m-d');

// Total exhibits
$stmt = $pdo->query("SELECT COUNT(*) as total FROM exhibits WHERE status = 'active'");
$activeExhibits = $stmt->fetch()['total'];

// Today's visitors
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE visit_date = ? AND status = 'used'");
$stmt->execute([$today]);
$todayVisitors = $stmt->fetch()['total'];

// Pending tickets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'confirmed'");
$pendingTickets = $stmt->fetch()['total'];

// Upcoming tours
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tours WHERE tour_date >= ? AND status = 'scheduled'");
$stmt->execute([$today]);
$upcomingTours = $stmt->fetch()['total'];

// Recent tickets
$recentTickets = $pdo->query("SELECT * FROM tickets ORDER BY purchase_date DESC LIMIT 5")->fetchAll();

// Upcoming tours list
$upcomingToursList = $pdo->prepare("SELECT t.*, g.full_name as guide_name FROM tours t LEFT JOIN tour_guides g ON t.guide_id = g.guide_id WHERE t.tour_date >= ? AND t.status = 'scheduled' ORDER BY t.tour_date, t.start_time LIMIT 5");
$upcomingToursList->execute([$today]);
$toursList = $upcomingToursList->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #e8f5e9;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4caf50" stroke-width="2">
                    <rect x="2" y="7" width="20" height="15" rx="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $activeExhibits; ?></h3>
                <p>Active Exhibits</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #e3f2fd;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2196f3" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $todayVisitors; ?></h3>
                <p>Today's Visitors</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #fff3e0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $pendingTickets; ?></h3>
                <p>Pending Tickets</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #f3e5f5;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $upcomingTours; ?></h3>
                <p>Upcoming Tours</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <h2>Recent Tickets</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ticket Code</th>
                            <th>Visitor</th>
                            <th>Visit Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['ticket_code']; ?></td>
                            <td><?php echo $ticket['visitor_name']; ?></td>
                            <td><?php echo formatDate($ticket['visit_date']); ?></td>
                            <td><span class="badge badge-<?php echo $ticket['status']; ?>"><?php echo ucfirst($ticket['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="dashboard-section">
            <h2>Upcoming Tours</h2>
            <div class="tour-list">
                <?php foreach ($toursList as $tour): ?>
                <div class="tour-item">
                    <h4><?php echo $tour['title']; ?></h4>
                    <p class="tour-guide">Guide: <?php echo $tour['guide_name'] ?? 'Not assigned'; ?></p>
                    <p class="tour-time">
                        <?php echo formatDate($tour['tour_date']); ?> at 
                        <?php echo date('g:i A', strtotime($tour['start_time'])); ?>
                    </p>
                    <p class="tour-capacity"><?php echo $tour['current_bookings']; ?> / <?php echo $tour['max_capacity']; ?> booked</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
