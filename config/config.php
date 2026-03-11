<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Isolate sessions per portal so multiple staff roles can be open simultaneously
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if     (str_contains($uri, '/admin/'))       session_name('emuse_admin');
    elseif (str_contains($uri, '/ticketing/'))   session_name('emuse_tick');
    elseif (str_contains($uri, '/tourguide/'))   session_name('emuse_guide');
    elseif (str_contains($uri, '/maintenance/')) session_name('emuse_maint');
    elseif (str_contains($uri, '/shop/'))        session_name('emuse_shop');
    elseif (str_contains($uri, '/manager/'))     session_name('emuse_mgr');
    // else: default PHPSESSID for the user/visitor portal
    session_start();
}

// Site Configuration
define('SITE_NAME', 'eMuse - Museum Management');
if (!defined('MUSEUM_NAME')) {
    define('MUSEUM_NAME', 'Museum');
}
define('SITE_URL', 'http://localhost/eMuse');

// Timezone
date_default_timezone_set('UTC');

// Include database connection
require_once __DIR__ . '/database.php';

// Authentication check function - Admin / Super Admin only
function checkAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit();
    }
    // Only super_admin and admin can access the full admin panel
    if (!in_array($_SESSION['admin_role'], ['super_admin', 'admin'])) {
        redirectByRole($_SESSION['admin_role']);
        exit();
    }
}

// Auth check for any staff role
function checkStaffAuth($required_role = null) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Redirect to each portal's own isolated login page
        $portalLogins = [
            'emuse_admin' => SITE_URL . '/admin/login.php',
            'emuse_tick'  => SITE_URL . '/ticketing/login.php',
            'emuse_guide' => SITE_URL . '/tourguide/login.php',
            'emuse_maint' => SITE_URL . '/maintenance/login.php',
            'emuse_shop'  => SITE_URL . '/shop/login.php',
            'emuse_mgr'   => SITE_URL . '/manager/login.php',
        ];
        $loginUrl = $portalLogins[session_name()] ?? SITE_URL . '/user/login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
    if ($required_role !== null) {
        $allowed = is_array($required_role) ? $required_role : [$required_role];
        if (!in_array($_SESSION['admin_role'], $allowed)) {
            redirectByRole($_SESSION['admin_role']);
            exit();
        }
    }
}

// Redirect user to their correct portal based on role
function redirectByRole($role) {
    $url = SITE_URL;
    switch ($role) {
        case 'super_admin':
        case 'admin':
            header("Location: $url/admin/index.php"); break;
        case 'ticketing_staff':
            header("Location: $url/ticketing/index.php"); break;
        case 'tour_guide':
            header("Location: $url/tourguide/index.php"); break;
        case 'maintenance_staff':
            header("Location: $url/maintenance/index.php"); break;
        case 'shop_staff':
            header("Location: $url/shop/index.php"); break;
        case 'manager':
            header("Location: $url/manager/index.php"); break;
        default:
            header("Location: $url/user/index.php"); break;
    }
    exit();
}

// Get role display label
function getRoleLabel($role) {
    $labels = [
        'super_admin'      => 'Super Admin',
        'admin'            => 'Administrator',
        'ticketing_staff'  => 'Ticketing Staff',
        'tour_guide'       => 'Tour Guide',
        'maintenance_staff'=> 'Maintenance Staff',
        'shop_staff'       => 'Shop Staff',
        'manager'          => 'Manager',
    ];
    return $labels[$role] ?? ucfirst(str_replace('_',' ',$role));
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}
?>
