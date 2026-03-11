<?php
require_once '../config/config.php';
checkStaffAuth('manager');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse — Manager Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime('../assets/css/style.css'); ?>">
    <style>
        .nav-item:hover,.nav-item.active { background:rgba(255,255,255,.15); }
        .nav-section { color:rgba(255,255,255,.5); }

        @media print {
            /* Hide all non-report UI */
            .sidebar,
            .top-bar,
            .no-print,
            .page-header .btn,
            form[method="GET"] {
                display: none !important;
            }

            /* Remove sidebar offset so content fills full page */
            .admin-layout {
                display: block !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: #fff !important;
            }
            .content {
                padding: 1.5rem !important;
            }
            body, html {
                background: #fff !important;
            }

            /* Clean up card styles for print */
            .card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
            }
            .stat-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }
            .stats-grid {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: .5rem !important;
            }
            .data-table th {
                background: #e0e0e0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Print header with report title and date */
            .print-header {
                display: block !important;
            }

            /* Force page title visible */
            .page-header {
                margin-bottom: 1rem !important;
            }

            a[href]::after { content: none !important; }
        }

        /* Hidden on screen, shown on print */
        .print-header { display: none; }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../img/emuse-logo.png" alt="eMuse Logo" style="max-width:140px;height:auto;display:block;margin:0 auto 6px auto;">
            <p>Manager Portal</p>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $currentPage=='index.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="visitor-stats.php" class="nav-item <?= $currentPage=='visitor-stats.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Visitor Statistics
            </a>
            <a href="revenue-reports.php" class="nav-item <?= $currentPage=='revenue-reports.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Revenue Reports
            </a>
            <a href="sales-reports.php" class="nav-item <?= $currentPage=='sales-reports.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                Sales Reports
            </a>
            <a href="performance-reports.php" class="nav-item <?= $currentPage=='performance-reports.php'?'active':'' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Performance Reports
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">Sign Out</a>
        </div>
    </aside>
    <main class="main-content">
        <header class="top-bar">
            <div class="top-bar-left"><h3><?= date('l, F j, Y') ?></h3></div>
            <div class="top-bar-right">
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                    <span class="user-role">Manager</span>
                </div>
            </div>
        </header>
        <div class="content">
