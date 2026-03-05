<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse – Maintenance Staff</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime('../assets/css/style.css'); ?>">
    <style>
        .sidebar { background:#4a148c; }
        .nav-item:hover,.nav-item.active { background:rgba(255,255,255,.15); }
        .nav-section { color:rgba(255,255,255,.5); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>eMuse</h2>
            <p>Maintenance Staff</p>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $current_page=='index.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <div class="nav-section">Maintenance</div>
            <a href="record-issue.php" class="nav-item <?= $current_page=='record-issue.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Record Equipment Issue
            </a>
            <a href="my-records.php" class="nav-item <?= $current_page=='my-records.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                My Maintenance Records
            </a>
            <a href="repair-schedule.php" class="nav-item <?= $current_page=='repair-schedule.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Repair Schedule
            </a>
            <a href="equipment.php" class="nav-item <?= $current_page=='equipment.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                Equipment List
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
                    <span class="user-role">Maintenance Staff</span>
                </div>
            </div>
        </header>
        <div class="content">
