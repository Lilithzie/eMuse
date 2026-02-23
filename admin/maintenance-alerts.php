<?php
require_once '../config/config.php';
checkAuth();

// Handle acknowledge action
if (isset($_GET['acknowledge']) && $_GET['acknowledge'] != '') {
    $alert_id = (int)$_GET['acknowledge'];
    $stmt = $pdo->prepare("
        UPDATE maintenance_alerts 
        SET is_acknowledged = TRUE, acknowledged_by = ?, acknowledged_at = NOW()
        WHERE alert_id = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $alert_id]);
    header('Location: maintenance-alerts.php?success=acknowledge');
    exit();
}

// Get all alerts
$alerts = $pdo->query("
    SELECT ma.*, e.name as equipment_name, e.equipment_type, e.status as equipment_status,
           au.full_name as acknowledged_by_name
    FROM maintenance_alerts ma
    JOIN equipment e ON ma.equipment_id = e.equipment_id
    LEFT JOIN admin_users au ON ma.acknowledged_by = au.admin_id
    ORDER BY ma.is_acknowledged ASC, ma.due_date ASC, ma.created_at DESC
")->fetchAll();

// Get statistics
$total_alerts = count($alerts);
$active_alerts = count(array_filter($alerts, fn($a) => !$a['is_acknowledged']));
$due_today = count(array_filter($alerts, fn($a) => !$a['is_acknowledged'] && $a['due_date'] == date('Y-m-d')));
$overdue = count(array_filter($alerts, fn($a) => !$a['is_acknowledged'] && $a['due_date'] < date('Y-m-d')));

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Maintenance Alerts</h1>
    <div style="display: flex; gap: 10px;">
        <a href="equipment.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            Equipment
        </a>
        <a href="maintenance-records.php" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Records
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Alert acknowledged successfully!
    </div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff9800" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $active_alerts; ?></h3>
            <p>Active Alerts</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #ffebee;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $overdue; ?></h3>
            <p>Overdue</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e1f5fe;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#03a9f4" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $due_today; ?></h3>
            <p>Due Today</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e5f5;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9c27b0" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_alerts - $active_alerts; ?></h3>
            <p>Resolved</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>All Maintenance Alerts</h2>
    </div>
    <div class="card-body">
        <?php if (empty($alerts)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <h3>No Maintenance Alerts</h3>
                <p>All equipment is up to date with maintenance schedules.</p>
            </div>
        <?php else: ?>
            <div class="alerts-list">
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert-item <?php echo $alert['is_acknowledged'] ? 'alert-resolved' : 'alert-active'; ?> 
                                          alert-<?php echo $alert['alert_type']; ?>">
                        <div class="alert-icon">
                            <?php if ($alert['alert_type'] == 'overdue' || $alert['alert_type'] == 'urgent'): ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            <?php else: ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="alert-content">
                            <div class="alert-header">
                                <h3><?php echo htmlspecialchars($alert['equipment_name']); ?></h3>
                                <span class="badge badge-<?php 
                                    echo $alert['alert_type'] == 'urgent' || $alert['alert_type'] == 'overdue' ? 'danger' : 
                                        ($alert['alert_type'] == 'due' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?>
                                </span>
                            </div>
                            <p class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></p>
                            <div class="alert-meta">
                                <span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <?php if ($alert['due_date']): ?>
                                        Due: <?php echo formatDate($alert['due_date']); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="text-muted">
                                    Created: <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                                </span>
                            </div>
                            <?php if ($alert['is_acknowledged']): ?>
                                <div class="alert-resolved-info">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Acknowledged by <?php echo htmlspecialchars($alert['acknowledged_by_name']); ?> 
                                    on <?php echo date('M d, Y H:i', strtotime($alert['acknowledged_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="alert-actions">
                            <?php if (!$alert['is_acknowledged']): ?>
                                <a href="?acknowledge=<?php echo $alert['alert_id']; ?>" 
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Mark this alert as acknowledged?')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Acknowledge
                                </a>
                            <?php endif; ?>
                            <a href="maintenance-records.php?equipment_id=<?php echo $alert['equipment_id']; ?>" 
                               class="btn btn-sm btn-secondary">
                                View Records
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.alert-item {
    display: flex;
    gap: 15px;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert-item.alert-urgent,
.alert-item.alert-overdue {
    border-left-color: #f44336;
    background: #ffebee;
}

.alert-item.alert-due {
    border-left-color: #ff9800;
    background: #fff3e0;
}

.alert-item.alert-warning_expiring {
    border-left-color: #03a9f4;
    background: #e1f5fe;
}

.alert-item.alert-resolved {
    opacity: 0.7;
    border-left-color: #9e9e9e;
    background: #f5f5f5;
}

.alert-icon {
    flex-shrink: 0;
}

.alert-icon svg {
    width: 32px;
    height: 32px;
}

.alert-content {
    flex: 1;
}

.alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.alert-header h3 {
    margin: 0;
    font-size: 18px;
}

.alert-message {
    margin: 10px 0;
    color: #424242;
}

.alert-meta {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #757575;
    align-items: center;
}

.alert-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.alert-resolved-info {
    margin-top: 10px;
    padding: 10px;
    background: rgba(76, 175, 80, 0.1);
    border-radius: 4px;
    color: #4caf50;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.alert-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #757575;
}

.empty-state svg {
    margin-bottom: 20px;
    color: #4caf50;
}

.empty-state h3 {
    margin: 15px 0 10px;
    color: #424242;
}
</style>

<?php include 'includes/footer.php'; ?>
