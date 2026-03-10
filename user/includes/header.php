<?php
// User Side Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse - Museum Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime('../assets/css/style.css'); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><strong>eMuse</strong></a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a href="exhibits.php" class="nav-link <?php echo ($current_page == 'exhibits.php') ? 'active' : ''; ?>">Exhibits</a>
                </li>
                <li class="nav-item">
                    <a href="artworks.php" class="nav-link <?php echo ($current_page == 'artworks.php') ? 'active' : ''; ?>">Artworks</a>
                </li>
                <li class="nav-item">
                    <a href="tours.php" class="nav-link <?php echo ($current_page == 'tours.php') ? 'active' : ''; ?>">Tours</a>
                </li>
                <li class="nav-item">
                    <a href="tickets.php" class="nav-link <?php echo ($current_page == 'tickets.php') ? 'active' : ''; ?>">Tickets</a>
                </li>
                <li class="nav-item">
                    <a href="shop.php" class="nav-link <?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>">Shop</a>
                </li>
                <li class="nav-item">
                    <a href="feedback.php" class="nav-link <?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">Feedback</a>
                </li>
                <?php if (isset($_SESSION['cart']) && array_sum($_SESSION['cart']) > 0): ?>
                <li class="nav-item">
                    <a href="cart.php" class="nav-link" style="background:var(--primary-dark);color:white;border-radius:4px;padding:.3rem .75rem;">
                        🛒 <?= array_sum($_SESSION['cart']) ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <!-- Auth actions separated visually -->
            <div class="nav-auth-group">
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                    <span class="nav-link nav-welcome" style="color: var(--golden-sand); font-size:0.82rem; letter-spacing:0.5px; height:auto; padding:0; cursor:default;">
                        Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="my-account.php" class="nav-link nav-login <?php echo ($current_page == 'my-account.php') ? 'active' : ''; ?>">My Account</a>
                    <a href="logout.php" class="nav-link nav-login">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link nav-login <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Login</a>
                    <a href="register.php" class="nav-link nav-login <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
