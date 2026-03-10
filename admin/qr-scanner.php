<?php
require_once '../config/config.php';
checkAuth();

// Handle QR scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_code'])) {
    $ticket_code = sanitize($_POST['ticket_code']);
    
    $stmt = $pdo->prepare("SELECT t.*, tt.price FROM tickets t LEFT JOIN ticket_types tt ON t.ticket_type = tt.ticket_type WHERE t.ticket_code = ?");
    $stmt->execute([$ticket_code]);
    $ticket = $stmt->fetch();
    
    if ($ticket) {
        if ($ticket['status'] == 'confirmed') {
            // Update ticket status
            $updateStmt = $pdo->prepare("UPDATE tickets SET status = 'used', scanned_at = NOW(), scanned_by = ? WHERE ticket_id = ?");
            $updateStmt->execute([$_SESSION['admin_id'], $ticket['ticket_id']]);
            
            $success = "Ticket scanned successfully! Welcome " . $ticket['visitor_name'];
        } elseif ($ticket['status'] == 'used') {
            $error = "This ticket has already been used on " . formatDateTime($ticket['scanned_at']);
        } else {
            $error = "This ticket is " . $ticket['status'];
        }
    } else {
        $error = "Ticket not found";
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>QR Code Scanner</h1>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="scanner-container">
    <div class="scanner-box">
        <div class="scanner-header">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            <h2>Scan Ticket QR Code</h2>
            <p>Use your camera to scan, or enter the ticket code manually</p>
        </div>

        <!-- Camera Scanner -->
        <div style="text-align:center;margin-bottom:1.5rem;">
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
        
        <form method="POST" action="" class="scanner-form" id="adminScanForm">
            <div class="form-group">
                <label for="ticket_code">Ticket Code</label>
                <input type="text" id="ticket_code" name="ticket_code" placeholder="TKT-XXXXXXXX" required autofocus style="text-transform:uppercase;">
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Verify Ticket
            </button>
        </form>
        
        <?php if (isset($ticket) && !isset($error)): ?>
        <div class="ticket-info">
            <h3>Ticket Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Visitor Name:</label>
                    <span><?php echo $ticket['visitor_name']; ?></span>
                </div>
                <div class="info-item">
                    <label>Ticket Type:</label>
                    <span><?php echo ucfirst($ticket['ticket_type']); ?></span>
                </div>
                <div class="info-item">
                    <label>Visit Date:</label>
                    <span><?php echo formatDate($ticket['visit_date']); ?></span>
                </div>
                <div class="info-item">
                    <label>Price:</label>
                    <span><?php echo formatCurrency($ticket['price']); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

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
            document.getElementById('ticket_code').value = decodedText.toUpperCase().trim();
            document.getElementById('qr-result').textContent = '✓ Scanned: ' + decodedText;
            stopCameraScanner();
            setTimeout(function() { document.getElementById('adminScanForm').submit(); }, 400);
        },
        function(err) {}
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

// Auto-clear form after 3 seconds on success
<?php if (isset($success)): ?>
setTimeout(() => {
    document.getElementById('ticket_code').value = '';
    document.getElementById('ticket_code').focus();
}, 3000);
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
