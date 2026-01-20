<header class="header">
    <div class="header-left">
        <h1><?php 
            $page_titles = [
                'index.php' => 'Dashboard',
                'exhibits.php' => 'Exhibit Management',
                'ticketing.php' => 'Ticketing & QR Entry',
                'tours.php' => 'Guided Tours',
                'maintenance.php' => 'Maintenance Tracking',
                'shop.php' => 'Souvenir Shop',
                'feedback.php' => 'Visitor Feedback',
                'reports.php' => 'Reports & Analytics'
            ];
            $current_page = basename($_SERVER['PHP_SELF']);
            echo isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'eMuse';
        ?></h1>
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
