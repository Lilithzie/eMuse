<?php
require_once '../config/config.php';
checkStaffAuth('ticketing_staff');

$success = '';
$error   = '';
$ticket  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_code'])) {
    $code = strtoupper(trim(sanitize($_POST['ticket_code'])));
    $entry_type = $_POST['entry_type'] ?? 'entry';

    $stmt = $pdo->prepare("
        SELECT t.*, tt.price as type_price 
        FROM tickets t 
        LEFT JOIN ticket_types tt ON t.ticket_type = tt.ticket_type 
        WHERE t.ticket_code = ?
    ");
    $stmt->execute([$code]);
    $ticket = $stmt->fetch();

    if ($ticket) {
        if ($entry_type === 'entry') {
            if ($ticket['status'] === 'confirmed') {
                // Mark ticket as used
                $pdo->prepare("UPDATE tickets SET status='used', scanned_at=NOW(), scanned_by=? WHERE ticket_id=?")
                    ->execute([$_SESSION['admin_id'], $ticket['ticket_id']]);

                // Log entry
                $pdo->prepare("INSERT INTO entry_log (ticket_id, scanned_by, entry_type) VALUES (?,?,?)")
                    ->execute([$ticket['ticket_id'], $_SESSION['admin_id'], 'entry']);

                // Update visitor stats
                $pdo->prepare("INSERT INTO visitor_stats (visit_date, total_visitors) VALUES (?,1) 
                    ON DUPLICATE KEY UPDATE total_visitors = total_visitors + 1")
                    ->execute([$ticket['visit_date']]);

                $success = "✓ Entry granted! Welcome, " . htmlspecialchars($ticket['visitor_name']);
                // Refresh ticket
                $stmt->execute([$code]); $ticket = $stmt->fetch();

            } elseif ($ticket['status'] === 'used') {
                $error = "This ticket was already scanned on " . formatDateTime($ticket['scanned_at']);
            } elseif ($ticket['status'] === 'cancelled') {
                $error = "This ticket has been cancelled.";
            } elseif ($ticket['status'] === 'pending') {
                $error = "⚠ Payment not yet collected. Please ask the visitor to pay at the counter first, then mark the ticket as paid via Ticket Payments before scanning.";
            } else {
                $error = "Ticket status: " . ucfirst($ticket['status']);
            }
        } else {
            // Exit scan – just log it
            $pdo->prepare("INSERT INTO entry_log (ticket_id, scanned_by, entry_type, notes) VALUES (?,?,?,?)")
                ->execute([$ticket['ticket_id'], $_SESSION['admin_id'], 'exit', 'Exit recorded']);
            $success = "Exit recorded for " . htmlspecialchars($ticket['visitor_name']);
        }
    } else {
        $error = "Ticket not found: " . htmlspecialchars($code);
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Scan / Validate Ticket</h1>
    <p style="color:#666;">Verify QR codes and control museum entry</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
    <!-- Scanner Form -->
    <div class="card">
        <div class="card-header"><h3>Ticket Verifier</h3></div>
        <div style="padding:2rem;text-align:center;">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--chestnut-grove)" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <p style="color:#666;margin-bottom:1.5rem;">Enter ticket code manually, use a barcode reader, or scan with your camera</p>

            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

            <!-- Camera QR Scanner -->
            <div style="margin-bottom:1.5rem;">
                <button type="button" id="startCamBtn" onclick="startCameraScanner()"
                    class="btn btn-primary" style="margin-bottom:.75rem;">
                    📷 Scan QR with Camera
                </button>
                <button type="button" id="stopCamBtn" onclick="stopCameraScanner()"
                    class="btn btn-danger" style="display:none;margin-bottom:.75rem;">
                    ✕ Stop Camera
                </button>
                <div id="qr-reader" style="width:100%;max-width:360px;margin:0 auto;display:none;border-radius:8px;overflow:hidden;"></div>
                <div id="qr-result" style="margin-top:.5rem;font-size:.85rem;color:var(--chestnut-grove);"></div>
            </div>

            <form method="POST" action="" id="scanForm">
                <div class="form-group" style="text-align:left;">
                    <label>Ticket Code *</label>
                    <input type="text" name="ticket_code" id="ticket_code_input" placeholder="e.g. TK20260305..." required autofocus
                           value="<?= isset($_POST['ticket_code']) ? htmlspecialchars(strtoupper(trim($_POST['ticket_code']))) : '' ?>"
                           style="text-transform:uppercase;font-size:1.1rem;letter-spacing:1px;">
                </div>
                <div class="form-group" style="text-align:left;">
                    <label>Entry Type</label>
                    <select name="entry_type" class="form-control">
                        <option value="entry">Entry (Arrival)</option>
                        <option value="exit">Exit (Departure)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Verify Ticket
                </button>
            </form>
        </div>
    </div>

    <!-- Ticket Details -->
    <div class="card">
        <div class="card-header"><h3>Ticket Details</h3></div>
        <div style="padding:2rem;">
            <?php if ($ticket): ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:.75rem;">
                    <span style="color:#666;">Ticket Code</span>
                    <strong><code><?= htmlspecialchars($ticket['ticket_code']) ?></code></strong>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:.75rem;">
                    <span style="color:#666;">Visitor Name</span>
                    <strong><?= htmlspecialchars($ticket['visitor_name']) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:.75rem;">
                    <span style="color:#666;">Email</span>
                    <span><?= htmlspecialchars($ticket['visitor_email'] ?? '—') ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:.75rem;">
                    <span style="color:#666;">Ticket Type</span>
                    <span><?= ucfirst($ticket['ticket_type']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:.75rem;">
                    <span style="color:#666;">Visit Date</span>
                    <span><?= formatDate($ticket['visit_date']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:.75rem;">
                    <span style="color:#666;">Purchase Date</span>
                    <span><?= formatDateTime($ticket['purchase_date']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#666;">Status</span>
                    <?php $sc=['confirmed'=>'badge-warning','used'=>'badge-success','cancelled'=>'badge-danger','pending'=>'badge-warning']; ?>
                    <span class="badge <?= $sc[$ticket['status']] ?? 'badge-warning' ?>"><?= ucfirst($ticket['status']) ?></span>
                </div>
                <?php if ($ticket['scanned_at']): ?>
                <div style="display:flex;justify-content:space-between;border-top:1px solid #eee;padding-top:.75rem;">
                    <span style="color:#666;">Scanned At</span>
                    <span><?= formatDateTime($ticket['scanned_at']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;color:#999;padding:3rem;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <p style="margin-top:1rem;">Scan or enter a ticket code to see details</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var html5QrCode = null;

function startCameraScanner() {
    document.getElementById('qr-reader').style.display = 'block';
    document.getElementById('startCamBtn').style.display = 'none';
    document.getElementById('stopCamBtn').style.display = 'inline-block';

    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        function(decodedText) {
            // Fill the input and auto-submit
            var input = document.getElementById('ticket_code_input');
            input.value = decodedText.toUpperCase().trim();
            document.getElementById('qr-result').textContent = '✓ Scanned: ' + decodedText;
            stopCameraScanner();
            // Auto-submit after short delay
            setTimeout(function() {
                document.getElementById('scanForm').submit();
            }, 400);
        },
        function(err) { /* scan errors are normal, ignore */ }
    ).catch(function(err) {
        document.getElementById('qr-result').textContent = 'Camera error: ' + err;
        document.getElementById('startCamBtn').style.display = 'inline-block';
        document.getElementById('stopCamBtn').style.display = 'none';
        document.getElementById('qr-reader').style.display = 'none';
    });
}

function stopCameraScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(function() {
            document.getElementById('qr-reader').style.display = 'none';
            document.getElementById('startCamBtn').style.display = 'inline-block';
            document.getElementById('stopCamBtn').style.display = 'none';
        }).catch(function() {});
    }
}
</script>