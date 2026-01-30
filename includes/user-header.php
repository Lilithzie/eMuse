<?php
// User Side Header
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse - Museum Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><strong>eMuse ❤︎</strong></a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a href="exhibits.php" class="nav-link <?php echo ($current_page == 'exhibits.php') ? 'active' : ''; ?>">Exhibits</a>
                </li>
                <li class="nav-item">
                    <a href="artworks.php" class="nav-link <?php echo ($current_page == 'artworks.php') ? 'active' : ''; ?>">Artifacts & Artworks</a>
                </li>
                <li class="nav-item">
                    <a href="tours.php" class="nav-link <?php echo ($current_page == 'tours.php') ? 'active' : ''; ?>">Guided Tours</a>
                </li>
                <li class="nav-item">
                    <a href="tickets.php" class="nav-link <?php echo ($current_page == 'tickets.php') ? 'active' : ''; ?>">Book Ticket</a>
                </li>
                <li class="nav-item">
                    <a href="admin/login.php" class="nav-link nav-login">Login</a>
                </li>
            </ul>
        </div>
    </nav>
</body>
</html>
