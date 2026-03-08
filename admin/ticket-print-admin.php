<?php
require_once '../config/config.php';
checkAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: tickets.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket) { header('Location: tickets.php'); exit; }

$typeLabels  = ['adult'=>'Adult','child'=>'Child','senior'=>'Senior','student'=>'Student','group'=>'Group'];
$statusColor = ['confirmed'=>'#1565c0','used'=>'#2e7d32','cancelled'=>'#c62828','pending'=>'#f57f17'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse Ticket — <?= htmlspecialchars($ticket['ticket_code']) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: "Montserrat", system-ui, sans-serif;
            background: #f0ede4;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 2rem;
        }
        .ticket-wrapper { width: 680px; }
        .ticket {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13);
            display: flex;
        }
        .ticket-left {
            background: linear-gradient(160deg, #3D4A2F 0%, #2A3520 100%);
            color: #F5F0E1;
            padding: 2.5rem 2rem;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .ticket-left .museum-name { font-size: 1.6rem; font-weight: 800; letter-spacing: 2px; margin-bottom: .2rem; }
        .ticket-left .museum-sub  { font-size: .72rem; letter-spacing: 3px; text-transform: uppercase; opacity: .7; margin-bottom: 1.5rem; }
        .ticket-left .qrcode-box  { background: #fff; border-radius: 8px; padding: 10px; margin-bottom: 1rem; }
        .ticket-left .ticket-code { font-size: .68rem; letter-spacing: 2px; opacity: .8; word-break: break-all; }
        .ticket-right {
            flex: 1; padding: 2rem 2rem 2rem 1.5rem;
            border-left: 3px dashed #d0cbbf;
            position: relative;
        }
        .ticket-right::before { content:''; position:absolute; left:-16px; top:-16px; width:32px; height:32px; background:#f0ede4; border-radius:50%; }
        .ticket-right::after  { content:''; position:absolute; left:-16px; bottom:-16px; width:32px; height:32px; background:#f0ede4; border-radius:50%; }
        .ticket-type-badge {
            display:inline-block; background:#C4A35A; color:#2A3520;
            font-weight:700; font-size:.78rem; letter-spacing:2px;
            text-transform:uppercase; padding:.3rem .8rem; border-radius:4px; margin-bottom:.8rem;
        }
        .ticket-title { font-size: 1.35rem; font-weight: 700; color: #2A3520; margin-bottom: 1.2rem; }
        .detail-row  { display:flex; gap:1rem; margin-bottom:.6rem; font-size:.87rem; color:#555; }
        .detail-row .label { font-weight:600; color:#3D4A2F; min-width:90px; }
        .status-badge { display:inline-block; padding:.2rem .7rem; border-radius:12px; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#fff; }
        .ticket-footer { margin-top:1.5rem; padding-top:1rem; border-top:1px solid #e8e4da; font-size:.72rem; color:#888; }
        .print-btn { display:block; width:100%; margin-top:1.5rem; padding:.75rem; background:#3D4A2F; color:#F5F0E1; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer; text-align:center; }
        .print-btn:hover { background:#2A3520; }
        @media print {
            body { background:#fff; padding:0; }
            .ticket-wrapper { width:100%; }
            .ticket { box-shadow:none; }
            .no-print { display:none !important; }
        }
    </style>
</head>
<body>
<div class="ticket-wrapper">
    <div class="ticket">
        <div class="ticket-left">
            <div class="museum-name">eMuse</div>
            <div class="museum-sub">Museum</div>
            <div class="qrcode-box"><div id="qrcode"></div></div>
            <div class="ticket-code"><?= htmlspecialchars($ticket['ticket_code']) ?></div>
        </div>
        <div class="ticket-right">
            <div class="ticket-type-badge"><?= htmlspecialchars($typeLabels[$ticket['ticket_type']] ?? ucfirst($ticket['ticket_type'])) ?> Ticket</div>
            <div class="ticket-title">eMuse Museum — General Admission</div>
            <div class="detail-row"><span class="label">Visitor</span><span><?= htmlspecialchars($ticket['visitor_name']) ?></span></div>
            <div class="detail-row"><span class="label">Email</span><span><?= htmlspecialchars($ticket['visitor_email']) ?></span></div>
            <?php if ($ticket['visitor_phone']): ?>
            <div class="detail-row"><span class="label">Phone</span><span><?= htmlspecialchars($ticket['visitor_phone']) ?></span></div>
            <?php endif; ?>
            <div class="detail-row"><span class="label">Visit Date</span><span><?= date('l, F d, Y', strtotime($ticket['visit_date'])) ?></span></div>
            <div class="detail-row"><span class="label">Amount Paid</span><span>₱<?= number_format($ticket['amount_paid'] ?? $ticket['price'] ?? 0, 2) ?></span></div>
            <?php if (!empty($ticket['payment_method'])): ?>
            <div class="detail-row"><span class="label">Payment</span><span><?= ucfirst($ticket['payment_method']) ?></span></div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="label">Status</span>
                <span class="status-badge" style="background:<?= $statusColor[$ticket['status']] ?? '#555' ?>"><?= ucfirst($ticket['status']) ?></span>
            </div>
            <div class="ticket-footer">
                Present this QR code at the museum entrance. Valid for the date shown above only. Non-transferable.
            </div>
        </div>
    </div>
    <button class="print-btn no-print" onclick="window.print()">🖨 Print This Ticket</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: "<?= addslashes($ticket['ticket_code']) ?>",
    width: 160, height: 160,
    colorDark: "#2A3520",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
});
</script>
</body>
</html>
