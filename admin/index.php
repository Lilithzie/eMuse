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

// Pending payment tickets (awaiting cash collection)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'pending'");
$pendingTickets = $stmt->fetch()['total'];

// Paid / confirmed tickets (ready for entry)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status = 'confirmed'");
$confirmedTickets = $stmt->fetch()['total'];

// Upcoming tours
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tours WHERE tour_date >= ? AND status = 'scheduled'");
$stmt->execute([$today]);
$upcomingTours = $stmt->fetch()['total'];

// Recent tickets
$recentTickets = $pdo->query("SELECT * FROM tickets ORDER BY purchase_date DESC LIMIT 5")->fetchAll();

// Upcoming tours list
$upcomingToursList = $pdo->prepare("SELECT t.*, g.full_name as guide_name, (SELECT COALESCE(SUM(tb.number_of_people),0) FROM tour_bookings tb WHERE tb.tour_id = t.tour_id AND tb.status = 'confirmed') AS current_bookings FROM tours t LEFT JOIN tour_guides g ON t.guide_id = g.guide_id WHERE t.tour_date >= ? AND t.status = 'scheduled' ORDER BY t.tour_date, t.start_time LIMIT 5");
$upcomingToursList->execute([$today]);
$toursList = $upcomingToursList->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard">
    <div class="page-header">
        <h1>Dashboard</h1>
        <span style="font-size:13px;color:var(--color-text-secondary);font-weight:500;"><?php echo date('l, F j, Y'); ?></span>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,0.12);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
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
            <div class="stat-icon" style="background:rgba(59,130,246,0.12);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
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
            <div class="stat-icon" style="background:rgba(245,158,11,0.12);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $pendingTickets; ?></h3>
                <p>Awaiting Payment</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(196,163,90,0.12);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C4A35A" stroke-width="2">
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
