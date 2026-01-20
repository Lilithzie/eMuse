<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticketing & QR Entry - eMuse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content">
                <div class="page-header">
                    <h2><i class="fas fa-ticket-alt"></i> Ticketing & QR Entry</h2>
                    <button class="btn btn-primary" onclick="openModal('newTicketModal')">
                        <i class="fas fa-plus"></i> New Ticket Sale
                    </button>
                </div>

                <!-- Stats -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card blue">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tickets Sold Today</h3>
                            <p class="stat-number">892</p>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Scanned Entries</h3>
                            <p class="stat-number">847</p>
                        </div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Current Visitors</h3>
                            <p class="stat-number">423</p>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Revenue Today</h3>
                            <p class="stat-number">$8,920</p>
                        </div>
                    </div>
                </div>

                <!-- QR Scanner Section -->
                <div class="dashboard-card" style="margin-bottom: 25px;">
                    <div class="card-header">
                        <h3><i class="fas fa-qrcode"></i> QR Code Scanner</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div style="flex: 1;">
                                <input type="text" class="form-control" placeholder="Scan QR code or enter ticket ID..." style="font-size: 18px;">
                            </div>
                            <button class="btn btn-success" style="padding: 15px 30px;">
                                <i class="fas fa-check"></i> Validate Entry
                            </button>
                        </div>
                        <div style="margin-top: 15px; padding: 15px; background-color: var(--light-bg); border-radius: 8px;">
                            <p style="margin: 0; color: var(--text-light);">
                                <i class="fas fa-info-circle"></i> Scan visitor's QR code ticket to validate entry
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Tickets Table -->
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Customer Name</th>
                                <th>Type</th>
                                <th>Purchase Date</th>
                                <th>Visit Date</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#TKT20260115001</td>
                                <td>John Anderson</td>
                                <td>Adult</td>
                                <td>Jan 15, 2026 09:15 AM</td>
                                <td>Jan 15, 2026</td>
                                <td>$15.00</td>
                                <td><span class="badge badge-success">Used</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#TKT20260115002</td>
                                <td>Sarah Williams</td>
                                <td>Student</td>
                                <td>Jan 15, 2026 09:32 AM</td>
                                <td>Jan 15, 2026</td>
                                <td>$10.00</td>
                                <td><span class="badge badge-info">Active</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#TKT20260115003</td>
                                <td>Michael Brown (Family Pass)</td>
                                <td>Family</td>
                                <td>Jan 15, 2026 10:05 AM</td>
                                <td>Jan 15, 2026</td>
                                <td>$40.00</td>
                                <td><span class="badge badge-success">Used</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#TKT20260115004</td>
                                <td>Emma Davis</td>
                                <td>Senior</td>
                                <td>Jan 14, 2026 03:45 PM</td>
                                <td>Jan 16, 2026</td>
                                <td>$12.00</td>
                                <td><span class="badge badge-info">Active</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#TKT20260115005</td>
                                <td>Robert Miller</td>
                                <td>Adult</td>
                                <td>Jan 13, 2026 11:20 AM</td>
                                <td>Jan 15, 2026</td>
                                <td>$15.00</td>
                                <td><span class="badge badge-danger">Expired</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Refund">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- New Ticket Modal -->
    <div id="newTicketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Ticket Sale</h3>
                <span class="close" onclick="closeModal('newTicketModal')">&times;</span>
            </div>
            <form>
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ticket Type</label>
                    <select class="form-control" required>
                        <option value="15">Adult - $15.00</option>
                        <option value="10">Student - $10.00</option>
                        <option value="12">Senior - $12.00</option>
                        <option value="8">Child - $8.00</option>
                        <option value="40">Family Pass - $40.00</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" class="form-control" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label>Visit Date</label>
                    <input type="date" class="form-control" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('newTicketModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Generate Ticket</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-header h2 {
            font-size: 24px;
            color: var(--text-dark);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            margin: 0 2px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-content form {
            padding: 20px;
        }
        .close {
            font-size: 28px;
            cursor: pointer;
        }
    </style>

    <script src="assets/js/main.js"></script>
</body>
</html>
