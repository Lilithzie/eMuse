<aside class="sidebar">
    <div class="logo">
        <i class="fas fa-landmark"></i>
        <h2>eMuse</h2>
    </div>
    
    <nav class="nav-menu">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'exhibits.php' ? 'active' : ''; ?>">
                <a href="exhibits.php">
                    <i class="fas fa-palette"></i>
                    <span>Exhibit Management</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'ticketing.php' ? 'active' : ''; ?>">
                <a href="ticketing.php">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Ticketing & QR Entry</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'tours.php' ? 'active' : ''; ?>">
                <a href="tours.php">
                    <i class="fas fa-route"></i>
                    <span>Guided Tours</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'maintenance.php' ? 'active' : ''; ?>">
                <a href="maintenance.php">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance Tracking</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : ''; ?>">
                <a href="shop.php">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Souvenir Shop</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                <a href="feedback.php">
                    <i class="fas fa-comments"></i>
                    <span>Visitor Feedback</span>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
