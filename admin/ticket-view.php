<?php
require_once '../config/config.php';
checkAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: tickets.php'); exit; }

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $new_status = $_POST['new_status'];
    if (in_array($new_status, ['confirmed', 'pending'])) {
        $pdo->prepare("UPDATE tickets SET status=? WHERE ticket_id=?")->execute([$new_status, $id]);
    }
    header("Location: ticket-view.php?id=$id&success=payment_updated");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket) { header('Location: tickets.php'); exit; }

// Entry log for this ticket
$logStmt = $pdo->prepare("SELECT el.*, a.username AS scanned_by_name FROM entry_log el LEFT JOIN admins a ON el.scanned_by = a.admin_id WHERE el.ticket_id = ? ORDER BY el.scan_time DESC");
$logStmt->execute([$id]);
$logs = $logStmt->fetchAll();

$typeLabels  = ['adult'=>'Adult','child'=>'Child','senior'=>'Senior','student'=>'Student','group'=>'Group'];
$statusColor = ['confirmed'=>'#2e7d32','used'=>'#1565c0','cancelled'=>'#c62828','pending'=>'#f57f17'];

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Ticket Details</h1>
    <div style="display:flex;gap:.75rem;">
        <?php if (isset($_GET['success']) && $_GET['success']==='payment_updated'): ?>
        <span style="padding:.35rem .9rem;background:#d4edda;color:#155724;border-radius:4px;font-size:.85rem;">Payment status updated.</span>
        <?php endif; ?>
        <a href="ticket-print-admin.php?id=<?= $ticket['ticket_id'] ?>" target="_blank" class="btn btn-secondary">🖨 Print Ticket</a>
        <a href="tickets.php" class="btn btn-secondary">← Back to Tickets</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start;">

    <!-- Left: Details Card -->
    <div class="card">
        <div class="card-header"><h3>Ticket Information</h3></div>
        <div style="padding:1.5rem;">
            <table style="width:100%;border-collapse:collapse;font-size:.92rem;">
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;width:130px;">Ticket Code</td>
                    <td style="padding:.6rem .5rem;font-weight:700;letter-spacing:1.5px;font-size:1rem;"><?= htmlspecialchars($ticket['ticket_code']) ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Visitor Name</td>
                    <td style="padding:.6rem .5rem;"><?= htmlspecialchars($ticket['visitor_name']) ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Email</td>
                    <td style="padding:.6rem .5rem;"><?= htmlspecialchars($ticket['visitor_email']) ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Phone</td>
                    <td style="padding:.6rem .5rem;"><?= htmlspecialchars($ticket['visitor_phone'] ?: '—') ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Ticket Type</td>
                    <td style="padding:.6rem .5rem;"><?= $typeLabels[$ticket['ticket_type']] ?? ucfirst($ticket['ticket_type']) ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Visit Date</td>
                    <td style="padding:.6rem .5rem;font-weight:600;"><?= formatDate($ticket['visit_date']) ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Price</td>
                    <td style="padding:.6rem .5rem;"><?= formatCurrency($ticket['price'] ?? 0) ?></td>
                </tr>
                <?php if (!empty($ticket['discount_amount']) && $ticket['discount_amount'] > 0): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Discount</td>
                    <td style="padding:.6rem .5rem;color:#c62828;">–<?= formatCurrency($ticket['discount_amount']) ?> <?= $ticket['promo_code'] ? '('.$ticket['promo_code'].')' : '' ?></td>
                </tr>
                <?php endif; ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Amount Paid</td>
                    <td style="padding:.6rem .5rem;font-weight:700;"><?= formatCurrency($ticket['amount_paid'] ?? $ticket['price'] ?? 0) ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Payment</td>
                    <td style="padding:.6rem .5rem;"><?= ucfirst($ticket['payment_method'] ?? 'cash') ?> (pay at counter)</td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Status</td>
                    <td style="padding:.6rem .5rem;">
                        <span style="display:inline-block;padding:.25rem .75rem;border-radius:12px;font-size:.78rem;font-weight:700;text-transform:uppercase;color:#fff;background:<?= $statusColor[$ticket['status']] ?? '#555' ?>;">
                            <?= ($ticket['status'] === 'confirmed' ? 'Paid' : ucfirst($ticket['status'])) ?>
                        </span>
                    </td>
                </tr>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:.6rem .5rem;color:#666;">Purchased</td>
                    <td style="padding:.6rem .5rem;"><?= !empty($ticket['purchase_date']) ? formatDateTime($ticket['purchase_date']) : '—' ?></td>
                </tr>
                <?php if (!empty($ticket['scanned_at'])): ?>
                <tr>
                    <td style="padding:.6rem .5rem;color:#666;">Scanned At</td>
                    <td style="padding:.6rem .5rem;"><?= formatDateTime($ticket['scanned_at']) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <?php if (in_array($ticket['status'], ['pending', 'confirmed'])): ?>
            <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #eee;">
                <p style="font-size:.85rem;color:#666;margin-bottom:.75rem;"><strong>Payment Action</strong> — Cash is the only payment method.</p>
                <?php if ($ticket['status'] === 'pending'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_payment">
                    <input type="hidden" name="new_status" value="confirmed">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm cash payment received and mark ticket as paid?')"
                        style="background:#2e7d32;border-color:#2e7d32;">
                        ✓ Mark as Paid (Cash Received)
                    </button>
                </form>
                <?php elseif ($ticket['status'] === 'confirmed'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_payment">
                    <input type="hidden" name="new_status" value="pending">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Revert payment status back to pending/unpaid?')">
                        ✕ Revert to Unpaid / Pending
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: QR Code + Entry Log -->
    <div style="display:flex;flex-direction:column;gap:2rem;">
        <div class="card">
            <div class="card-header"><h3>QR Code</h3></div>
            <div style="padding:1.5rem;text-align:center;">
                <div id="qrcode" style="display:inline-block;padding:12px;background:#fff;border:2px solid #e0e0e0;border-radius:8px;margin-bottom:1rem;"></div>
                <p style="font-size:.75rem;color:#888;letter-spacing:1px;margin-bottom:1rem;"><?= htmlspecialchars($ticket['ticket_code']) ?></p>
                <p style="font-size:.82rem;color:#555;">Any external QR scanner or phone camera will read this code. Present at museum entry.</p>
                <div style="margin-top:1rem;display:flex;gap:.75rem;justify-content:center;">
                    <button onclick="downloadQR()" class="btn btn-secondary" style="font-size:.82rem;">⬇ Download QR</button>
                    <a href="ticket-print-admin.php?id=<?= $ticket['ticket_id'] ?>" target="_blank" class="btn btn-secondary" style="font-size:.82rem;">🖨 Print</a>
                </div>
            </div>
        </div>

        <!-- Entry Log -->
        <div class="card">
            <div class="card-header"><h3>Scan / Entry Log</h3></div>
            <div style="padding:1.5rem;">
                <?php if ($logs): ?>
                <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:.5rem;text-align:left;">Time</th>
                            <th style="padding:.5rem;text-align:left;">Type</th>
                            <th style="padding:.5rem;text-align:left;">By</th>
                            <th style="padding:.5rem;text-align:left;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:.5rem;"><?= formatDateTime($log['scan_time'] ?? $log['created_at'] ?? '') ?></td>
                            <td style="padding:.5rem;">
                                <span style="padding:.15rem .5rem;border-radius:4px;font-size:.75rem;font-weight:700;background:<?= $log['entry_type']==='entry'?'#e8f5e9':'#fff3e0' ?>;color:<?= $log['entry_type']==='entry'?'#2e7d32':'#e65100' ?>;">
                                    <?= ucfirst($log['entry_type']) ?>
                                </span>
                            </td>
                            <td style="padding:.5rem;"><?= htmlspecialchars($log['scanned_by_name'] ?? '—') ?></td>
                            <td style="padding:.5rem;color:#888;"><?= htmlspecialchars($log['notes'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:#999;text-align:center;padding:1rem;">No scan events recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
var qr = new QRCode(document.getElementById("qrcode"), {
    text: "<?= addslashes($ticket['ticket_code']) ?>",
    width: 200, height: 200,
    colorDark: "#2A3520",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
});

function downloadQR() {
    // QRCode.js renders a canvas; grab it and download
    var canvas = document.querySelector('#qrcode canvas');
    if (!canvas) {
        // Fallback: img
        var img = document.querySelector('#qrcode img');
        if (img) {
            var a = document.createElement('a');
            a.href = img.src;
            a.download = "ticket-<?= addslashes($ticket['ticket_code']) ?>.png";
            a.click();
        }
        return;
    }
    var a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = "ticket-<?= addslashes($ticket['ticket_code']) ?>.png";
    a.click();
}
</script>

<?php include 'includes/footer.php'; ?>
