<?php
require_once '../../config/config.php';
checkStaffAuth('manager');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse — Manager Portal</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        :root { --manager-dark:#004d40; --manager-mid:#00695c; --manager-light:#e0f2f1; }
        .sidebar { background: var(--manager-dark); }
        .sidebar .brand { border-bottom-color: var(--manager-mid); }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--manager-mid); }
        .stat-card { border-left: 4px solid var(--manager-dark); }
        .btn-primary { background: var(--manager-dark); }
        .badge-manager { background: var(--manager-light); color: var(--manager-dark); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">
            <span style="font-size:1.4rem;">🏛</span>
            <div>
                <strong>eMuse</strong>
                <small style="display:block;opacity:.7;font-size:.7rem;">Manager Portal</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/manager/index.php" class="<?= $currentPage=='index.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= SITE_URL ?>/manager/visitor-stats.php" class="<?= $currentPage=='visitor-stats.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Visitor Statistics
            </a>
            <a href="<?= SITE_URL ?>/manager/revenue-reports.php" class="<?= $currentPage=='revenue-reports.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Revenue Reports
            </a>
            <a href="<?= SITE_URL ?>/manager/sales-reports.php" class="<?= $currentPage=='sales-reports.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Sales Reports
            </a>
            <a href="<?= SITE_URL ?>/manager/performance-reports.php" class="<?= $currentPage=='performance-reports.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Performance Reports
            </a>
        </nav>
        <div class="sidebar-footer">
            <span style="font-size:.85rem;opacity:.8;"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            <a href="<?= SITE_URL ?>/manager/logout.php" class="logout-btn">Sign Out</a>
        </div>
    </aside>
    <div class="main-content">
        <header class="topbar">
            <span style="font-weight:600;color:var(--manager-dark);">Manager Portal</span>
            <span class="badge badge-manager"><?= getRoleLabel($_SESSION['admin_role']) ?></span>
        </header>
        <div class="content">
