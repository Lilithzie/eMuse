<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse – Shop Staff</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime('../assets/css/style.css'); ?>">
    <style>
        .nav-item:hover,.nav-item.active { background:rgba(255,255,255,.15); }
        .nav-section { color:rgba(255,255,255,.5); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" style="display:block;">
                <img src="../img/emuse-logo.png" alt="eMuse Logo" style="max-width:140px;height:auto;display:block;margin:0 auto 6px auto;">
            </a>
            <p>Shop Staff</p>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $current_page=='index.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <div class="nav-section">Sales</div>
            <a href="pos.php" class="nav-item <?= $current_page=='pos.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                New Sale (POS)
            </a>
            <a href="sales-history.php" class="nav-item <?= $current_page=='sales-history.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Sales History
            </a>
            <div class="nav-section">Inventory</div>
            <a href="inventory.php" class="nav-item <?= $current_page=='inventory.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M6 3l3 4M18 3l-3 4"/></svg>
                Update Inventory
            </a>
            <a href="stock-report.php" class="nav-item <?= $current_page=='stock-report.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                Stock Report
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
                    <span class="user-role">Shop Staff</span>
                </div>
            </div>
        </header>
        <div class="content">
