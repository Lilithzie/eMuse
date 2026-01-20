<?php
session_start();

// Simulated authentication - in production, use proper authentication
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_name'] = 'Admin User';
    $_SESSION['user_role'] = 'administrator';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse - Museum Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-landmark"></i>
                <h2>eMuse</h2>
            </div>
            
            <nav class="nav-menu">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="exhibits.php">
                            <i class="fas fa-palette"></i>
                            <span>Exhibit Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="ticketing.php">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Ticketing & QR Entry</span>
                        </a>
                    </li>
                    <li>
                        <a href="tours.php">
                            <i class="fas fa-route"></i>
                            <span>Guided Tours</span>
                        </a>
                    </li>
                    <li>
                        <a href="maintenance.php">
                            <i class="fas fa-tools"></i>
                            <span>Maintenance Tracking</span>
                        </a>
                    </li>
                    <li>
                        <a href="shop.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Souvenir Shop</span>
                        </a>
                    </li>
                    <li>
                        <a href="feedback.php">
                            <i class="fas fa-comments"></i>
                            <span>Visitor Feedback</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Reports & Analytics</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo $_SESSION['user_name']; ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Today's Visitors</h3>
                            <p class="stat-number">1,247</p>
                            <span class="stat-change positive">+12% from yesterday</span>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tickets Sold</h3>
                            <p class="stat-number">892</p>
                            <span class="stat-change positive">+8% from yesterday</span>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Today's Revenue</h3>
                            <p class="stat-number">$12,450</p>
                            <span class="stat-change positive">+15% from yesterday</span>
                        </div>
                    </div>

                    <div class="stat-card purple">
                        <div class="stat-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Active Exhibits</h3>
                            <p class="stat-number">24</p>
                            <span class="stat-change neutral">3 ending this month</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Recent Activity -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="ticketing.php?action=new" class="action-btn">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>New Ticket Sale</span>
                                </a>
                                <a href="exhibits.php?action=new" class="action-btn">
                                    <i class="fas fa-image"></i>
                                    <span>Add Exhibit</span>
                                </a>
                                <a href="tours.php?action=new" class="action-btn">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span>Schedule Tour</span>
                                </a>
                                <a href="maintenance.php?action=new" class="action-btn">
                                    <i class="fas fa-wrench"></i>
                                    <span>Log Maintenance</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Upcoming Tours</h3>
                        </div>
                        <div class="card-body">
                            <div class="activity-list">
                                <div class="activity-item">
                                    <div class="activity-time">10:00 AM</div>
                                    <div class="activity-details">
                                        <strong>Ancient Civilizations Tour</strong>
                                        <span>Guide: John Smith | 15/20 capacity</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-time">2:00 PM</div>
                                    <div class="activity-details">
                                        <strong>Modern Art Exhibition</strong>
                                        <span>Guide: Sarah Johnson | 18/25 capacity</span>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-time">4:30 PM</div>
                                    <div class="activity-details">
                                        <strong>Natural History Tour</strong>
                                        <span>Guide: Michael Brown | 12/20 capacity</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Maintenance Alerts</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert-list">
                                <div class="alert-item critical">
                                    <i class="fas fa-circle"></i>
                                    <div>
                                        <strong>HVAC System - Gallery 3</strong>
                                        <span>Urgent maintenance required</span>
                                    </div>
                                </div>
                                <div class="alert-item warning">
                                    <i class="fas fa-circle"></i>
                                    <div>
                                        <strong>Lighting - Main Entrance</strong>
                                        <span>Scheduled for replacement</span>
                                    </div>
                                </div>
                                <div class="alert-item normal">
                                    <i class="fas fa-circle"></i>
                                    <div>
                                        <strong>Display Case - Room 5</strong>
                                        <span>Routine inspection due</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-star"></i> Recent Feedback</h3>
                        </div>
                        <div class="card-body">
                            <div class="feedback-list">
                                <div class="feedback-item">
                                    <div class="feedback-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p>"Excellent exhibits and very knowledgeable guides!"</p>
                                    <span class="feedback-date">2 hours ago</span>
                                </div>
                                <div class="feedback-item">
                                    <div class="feedback-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <p>"Great experience, but could use more seating areas."</p>
                                    <span class="feedback-date">5 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
