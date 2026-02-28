<?php
session_start();

// Determine user type and redirect appropriately
if (isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
} else {
    $user_type = 'user'; // Default
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect based on user type
if ($user_type === 'admin') {
    header('Location: ../admin/login.php');
} else {
    header('Location: login.php');
}
exit();
?>
