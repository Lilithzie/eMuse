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
    <title>Exhibit Management - eMuse</title>
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
                    <h2><i class="fas fa-palette"></i> Exhibit Management</h2>
                    <button class="btn btn-primary" onclick="openModal('addExhibitModal')">
                        <i class="fas fa-plus"></i> Add New Exhibit
                    </button>
                </div>

                <!-- Exhibit Filters -->
                <div class="dashboard-card" style="margin-bottom: 25px;">
                    <div class="card-body">
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <input type="text" class="form-control" placeholder="Search exhibits..." style="max-width: 300px;">
                            <select class="form-control" style="max-width: 200px;">
                                <option>All Categories</option>
                                <option>Ancient History</option>
                                <option>Modern Art</option>
                                <option>Natural History</option>
                                <option>Science & Technology</option>
                            </select>
                            <select class="form-control" style="max-width: 150px;">
                                <option>All Status</option>
                                <option>Active</option>
                                <option>Upcoming</option>
                                <option>Ended</option>
                            </select>
                            <button class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Exhibits Table -->
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Exhibit ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Artifacts</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#EXH001</td>
                                <td>Ancient Egypt: Treasures of the Pharaohs</td>
                                <td>Ancient History</td>
                                <td>Jan 1, 2026</td>
                                <td>Mar 31, 2026</td>
                                <td>87</td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#EXH002</td>
                                <td>Impressionist Masters Collection</td>
                                <td>Modern Art</td>
                                <td>Dec 15, 2025</td>
                                <td>Feb 28, 2026</td>
                                <td>52</td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#EXH003</td>
                                <td>Dinosaurs: Giants of the Past</td>
                                <td>Natural History</td>
                                <td>Feb 1, 2026</td>
                                <td>May 30, 2026</td>
                                <td>34</td>
                                <td><span class="badge badge-warning">Upcoming</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#EXH004</td>
                                <td>Space Exploration: Journey to the Stars</td>
                                <td>Science & Technology</td>
                                <td>Jan 10, 2026</td>
                                <td>Apr 15, 2026</td>
                                <td>63</td>
                                <td><span class="badge badge-success">Active</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>#EXH005</td>
                                <td>Renaissance Art & Architecture</td>
                                <td>Modern Art</td>
                                <td>Nov 1, 2025</td>
                                <td>Jan 14, 2026</td>
                                <td>45</td>
                                <td><span class="badge badge-danger">Ending Soon</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Exhibit Modal -->
    <div id="addExhibitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Exhibit</h3>
                <span class="close" onclick="closeModal('addExhibitModal')">&times;</span>
            </div>
            <form>
                <div class="form-group">
                    <label>Exhibit Name</label>
                    <input type="text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select class="form-control" required>
                        <option>Ancient History</option>
                        <option>Modern Art</option>
                        <option>Natural History</option>
                        <option>Science & Technology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" onclick="closeModal('addExhibitModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Exhibit</button>
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
