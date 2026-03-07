<?php
// Ticketing Staff Portal Header
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse – Ticketing Staff</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime('../assets/css/style.css'); ?>">
    <style>
        .sidebar-header p { color:rgba(255,255,255,.65); }
        .nav-item:hover,.nav-item.active { background:rgba(255,255,255,.15); }
        .nav-section { color:rgba(255,255,255,.5); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>eMuse</h2>
            <p>Ticketing Staff</p>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $current_page=='index.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <div class="nav-section">Ticket Validation</div>
            <a href="scanner.php" class="nav-item <?= $current_page=='scanner.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Scan / Validate Ticket
            </a>
            <a href="entry-log.php" class="nav-item <?= $current_page=='entry-log.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                Entry Log
            </a>
            <a href="visitor-count.php" class="nav-item <?= $current_page=='visitor-count.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Visitor Count
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>
    <main class="main-content">
        <header class="top-bar">
            <div class="top-bar-left"><h3><?= date('l, F j, Y') ?></h3></div>
            <div class="top-bar-right">
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></span>
                    <span class="user-role">Ticketing Staff</span>
                </div>
            </div>
        </header>
        <div class="content">
