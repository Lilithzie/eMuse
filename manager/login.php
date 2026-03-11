<?php
require_once '../config/config.php';
// Already logged in -> go straight to dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit();
}
// All staff log in through the main portal
header('Location: ' . SITE_URL . '/user/index.php?panel=login');
exit();
