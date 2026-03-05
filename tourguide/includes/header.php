<?php
// Tour Guide Portal Header
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse – Tour Guide</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime('../assets/css/style.css'); ?>">
    <style>
        :root { --portal-color:#2e7d32; }
        .sidebar { background: var(--portal-color); }
        .nav-item:hover,.nav-item.active { background:rgba(255,255,255,.15); }
        .nav-section { color:rgba(255,255,255,.5); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>eMuse</h2>
            <p>Tour Guide</p>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $current_page=='index.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <div class="nav-section">My Tours</div>
            <a href="my-tours.php" class="nav-item <?= $current_page=='my-tours.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Assigned Tours
            </a>
            <a href="tour-participants.php" class="nav-item <?= $current_page=='tour-participants.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Participant Lists
            </a>
            <div class="nav-section">Tour Actions</div>
            <a href="update-status.php" class="nav-item <?= $current_page=='update-status.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Update Tour Status
            </a>
            <a href="report-issue.php" class="nav-item <?= $current_page=='report-issue.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Report Tour Issue
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
                    <span class="user-role">Tour Guide</span>
                </div>
            </div>
        </header>
        <div class="content">
