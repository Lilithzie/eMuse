<?php
// User Side Footer
?>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3><?php echo MUSEUM_NAME; ?></h3>
                <p>Preserving history and celebrating cultural heritage through interactive museum management.</p>
                <p style="font-size: 0.9rem; margin-top: 1rem; opacity: 0.8;">Bringing museums closer to you</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="exhibits.php">Exhibits</a></li>
                    <li><a href="artworks.php">Artifacts & Artworks</a></li>
                    <li><a href="tours.php">Guided Tours</a></li>
                    <li><a href="tickets.php">Book Ticket</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact Information</h4>
                <p><strong>Email:</strong><br><a href="mailto:contact@museum.com" style="padding-left: 0;">contact@museum.com</a></p>
                <p><strong>Phone:</strong><br><a href="tel:+15551234567" style="padding-left: 0;">+1 (555) 123-4567</a></p>
                <p><strong>Hours:</strong><br>Mon - Sun: 9:00 AM - 6:00 PM</p>
                <p><strong>Location:</strong><br>118 Art District Avenue Culture City,</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 <?php echo MUSEUM_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

<!-- ── Toast Notification ─────────────────────────────────────────── -->
<style>
#toast-container {
    position:fixed; bottom:1.75rem; right:1.75rem; z-index:9999;
    display:flex; flex-direction:column; gap:.65rem; pointer-events:none;
}
.toast {
    pointer-events:all;
    min-width:280px; max-width:380px;
    padding:1rem 1.1rem;
    border-radius:10px;
    box-shadow:0 6px 24px rgba(0,0,0,.18);
    display:flex; align-items:flex-start; gap:.75rem;
    font-size:.9rem; line-height:1.45;
    animation: toastIn .35s cubic-bezier(.22,1,.36,1) both;
    position:relative; overflow:hidden;
}
.toast.toast-success { background:#1e5631; color:#d4edda; border-left:4px solid #52c97a; }
.toast.toast-error   { background:#6b1a1a; color:#f8d7da; border-left:4px solid #e05c5c; }
.toast-icon  { font-size:1.25rem; flex-shrink:0; margin-top:.05rem; }
.toast-body  { flex:1; }
.toast-close {
    background:transparent; border:none; cursor:pointer;
    font-size:1.1rem; opacity:.6; color:inherit; padding:0 0 0 .25rem;
    flex-shrink:0; line-height:1;
}
.toast-close:hover { opacity:1; }
.toast-progress {
    position:absolute; bottom:0; left:0; height:3px;
    background:rgba(255,255,255,.35); width:100%;
    animation: toastProgress 4s linear forwards;
}
@keyframes toastIn {
    from { opacity:0; transform:translateX(60px); }
    to   { opacity:1; transform:translateX(0); }
}
@keyframes toastOut {
    from { opacity:1; transform:translateX(0); max-height:100px; margin-bottom:0; }
    to   { opacity:0; transform:translateX(60px); max-height:0;   margin-bottom:-8px; }
}
@keyframes toastProgress {
    from { width:100%; }
    to   { width:0%; }
}
</style>
<div id="toast-container"></div>
<script>
function showToast(message, type) {
    type = type || 'success';
    var icon = type === 'success' ? '✅' : '❌';
    var container = document.getElementById('toast-container');
    var el = document.createElement('div');
    el.className = 'toast toast-' + type;
    el.innerHTML =
        '<span class="toast-icon">' + icon + '</span>' +
        '<span class="toast-body">' + message + '</span>' +
        '<button class="toast-close" onclick="dismissToast(this.parentElement)" title="Close">✕</button>' +
        '<div class="toast-progress"></div>';
    container.appendChild(el);
    setTimeout(function() { dismissToast(el); }, 4200);
}
function dismissToast(el) {
    if (!el || el._dismissing) return;
    el._dismissing = true;
    el.style.animation = 'toastOut .3s ease forwards';
    setTimeout(function() { el.remove(); }, 310);
}
</script>
</body>
</html>
