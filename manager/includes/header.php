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

        /* ── PRINT STYLES ─────────────────────────────────── */
        .print-report-header { display: none; }

        @media print {
            /* ── Force print colours ── */
            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* ── Page setup ── */
            @page { size: A4 portrait; margin: 1.5cm 1.8cm; }

            /* ── Hide navigatiion chrome only ── */
            .sidebar,
            .top-bar,
            form[method="GET"],
            .btn,
            .no-print,
            .page-header {
                display: none !important;
            }

            /* ── Reset layout ── */
            body, html {
                background: #fff !important;
                font-size: 9.5pt;
                font-family: "Segoe UI", Arial, sans-serif;
                color: #111;
            }
            .admin-layout { display: block !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; background: #fff !important; }
            .content      { padding: 0 !important; }

            /* ── Print-only report banner ── */
            .print-report-header {
                display: flex !important;
                justify-content: space-between;
                align-items: flex-end;
                border-bottom: 2.5pt solid #2a3520;
                padding-bottom: 8pt;
                margin-bottom: 16pt;
            }
            .print-report-header .museum-name {
                font-size: 13pt;
                font-weight: 800;
                color: #2a3520;
                line-height: 1.2;
            }
            .print-report-header .report-subtitle {
                font-size: 9pt;
                color: #555;
                margin-top: 2pt;
            }
            .print-report-header .report-meta {
                text-align: right;
                font-size: 8.5pt;
                color: #444;
                line-height: 1.7;
            }
            .print-report-header .report-meta strong { color: #111; }

            /* ── Collapse grid wrappers to vertical stack ── */
            div[style*="display:grid"],
            div[style*="display: grid"],
            div[style*="grid-template-columns"] {
                display: block !important;
                width: 100% !important;
            }

            /* ── Stats summary row ── */
            .stats-grid {
                display: flex !important;
                flex-wrap: nowrap !important;
                gap: 0 !important;
                margin-bottom: 14pt !important;
                border: 1pt solid #ccc !important;
                border-radius: 4pt !important;
                overflow: hidden !important;
            }
            .stat-card {
                flex: 1 1 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-right: 1pt solid #ddd !important;
                border-radius: 0 !important;
                padding: 0 !important;
            }
            .stat-card:last-child { border-right: none !important; }
            .stat-card .stat-content { padding: 8pt 10pt !important; text-align: center !important; }
            .stat-card .stat-content h3 { font-size: 14pt !important; font-weight: 700 !important; color: #2a3520 !important; margin: 0 0 2pt !important; }
            .stat-card .stat-content p  { font-size: 7.5pt !important; color: #555 !important; margin: 0 !important; text-transform: uppercase; letter-spacing: 0.3px; }

            /* ── Cards ── */
            .card {
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                overflow: visible !important;
                box-shadow: none !important;
                border: 1pt solid #ccc !important;
                border-radius: 4pt !important;
                margin-bottom: 12pt !important;
                padding: 0 !important;
                width: 100% !important;
                background: #fff !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
                transform: none !important;
                transition: none !important;
            }
            .card:hover { transform: none !important; box-shadow: none !important; }
            .card-header {
                display: block !important;
                background: #2a3520 !important;
                border-bottom: none !important;
                padding: 6pt 10pt !important;
                color: #fff !important;
            }
            .card-header h3 {
                font-size: 9.5pt !important;
                font-weight: 700 !important;
                color: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                font-family: "Segoe UI", Arial, sans-serif !important;
            }

            /* ── Tables ── */
            .data-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 8.5pt !important;
            }
            .data-table th {
                background: #3d4f30 !important;
                color: #fff !important;
                padding: 5pt 8pt !important;
                text-align: left;
                font-size: 8pt !important;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }
            .data-table td {
                padding: 4.5pt 8pt !important;
                border-bottom: 0.5pt solid #e8e8e8 !important;
                color: #111 !important;
                vertical-align: middle;
            }
            .data-table tr:nth-child(even) td { background: #f9f9f9 !important; }
            .data-table tfoot tr td {
                background: #e8f5e9 !important;
                font-weight: 700 !important;
                color: #1b5e20 !important;
                border-top: 1pt solid #a5d6a7 !important;
            }

            /* ── Misc ── */
            a::after { content: none !important; }
            a { text-decoration: none !important; color: inherit !important; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" style="display:block;">
                <img src="../img/emuse-logo.png" alt="eMuse Logo" style="max-width:140px;height:auto;display:block;margin:0 auto 6px auto;">
            </a>
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
