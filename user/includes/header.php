<?php
// User Side Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo MUSEUM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime('../assets/css/style.css'); ?>">
    <style>
        /* ── Auth Side Panels ── */
        .auth-panel-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.52);
            z-index: 1040;
        }
        .auth-panel-overlay.active { display: block; }

        .auth-side-panel {
            position: fixed;
            top: 0;
            right: -460px;
            width: 420px;
            max-width: 100vw;
            height: 100vh;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            box-shadow: -6px 0 32px rgba(0,0,0,0.22);
            transition: right 0.35s cubic-bezier(0.4,0,0.2,1);
            overflow: hidden;
        }
        .auth-side-panel.active { right: 0; }

        .auth-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.5rem 1.75rem;
            background: var(--primary-dark);
            flex-shrink: 0;
        }
        .auth-panel-title {
            color: var(--primary-light);
            margin: 0 0 0.2rem;
            font-size: 1.45rem;
            font-weight: 700;
        }
        .auth-panel-subtitle {
            color: rgba(245,240,225,0.72);
            margin: 0;
            font-size: 0.86rem;
        }
        .auth-panel-close {
            background: none;
            border: none;
            color: var(--cream-harvest);
            font-size: 1.7rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 0 0 1rem;
            opacity: 0.75;
            transition: opacity 0.2s;
            flex-shrink: 0;
        }
        .auth-panel-close:hover { opacity: 1; }

        .auth-panel-body {
            padding: 1.6rem 1.75rem;
            overflow-y: auto;
            flex: 1;
            background: var(--cream-harvest);
        }
        .auth-panel-body .form-group { margin-bottom: 1.1rem; }
        .auth-panel-body .form-group label {
            color: var(--chestnut-grove);
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 0.35rem;
            display: block;
        }
        .auth-panel-body .form-group input {
            background: #fff;
            width: 100%;
            box-sizing: border-box;
        }
        .auth-panel-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
        }

        .auth-panel-alert {
            padding: 0.7rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.88rem;
            display: none;
        }
        .auth-panel-alert.error {
            background: #fff0f0;
            border: 1px solid #e57373;
            border-left: 4px solid #c62828;
            color: #b71c1c;
        }
        .auth-panel-alert.success {
            background: #f1f8e9;
            border: 1px solid #aed581;
            border-left: 4px solid #558b2f;
            color: #33691e;
        }
        .auth-panel-alert.info {
            background: #f5f0e1;
            border: 1px solid var(--smoky-oak, #8B9A6B);
            border-left: 4px solid var(--primary-dark, #2A3520);
            color: var(--primary-dark, #2A3520);
        }

        .auth-panel-footer-link {
            margin-top: 1.1rem;
            padding-top: 1.1rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-size: 0.88rem;
            color: var(--smoky-oak);
        }
        .auth-panel-footer-link a {
            color: var(--chestnut-grove);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-panel-footer-link a:hover { text-decoration: underline; }

        .auth-panel-demo {
            background: white;
            border-radius: 8px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.2rem;
            font-size: 0.8rem;
            color: var(--smoky-oak);
            border: 1px solid var(--border-color);
        }
        .auth-panel-demo strong { color: var(--chestnut-grove); display: block; margin-bottom: 0.4rem; }
        .auth-panel-demo table { width: 100%; border-collapse: collapse; }
        .auth-panel-demo td { padding: 0.18rem 0.25rem; }

        @media (max-width: 480px) {
            .auth-side-panel { width: 100%; right: -100%; }
        }

        /* Password toggle inside panels */
        .auth-panel-body .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .auth-panel-body .password-wrapper input {
            flex: 1;
            padding-right: 2.8rem;
        }
        .auth-panel-body .toggle-password {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--smoky-oak);
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .auth-panel-body .toggle-password:hover { color: var(--chestnut-grove); }
    </style>
</head>
<body>
    <!-- Auth Panel Overlay -->
    <div id="auth-panel-overlay" class="auth-panel-overlay" onclick="closeAuthPanels()"></div>

    <!-- Login Side Panel -->
    <div id="panel-login" class="auth-side-panel">
        <div class="auth-panel-header">
            <div>
                <h2 class="auth-panel-title">Login</h2>
                <p class="auth-panel-subtitle">Access Your Account</p>
            </div>
            <button class="auth-panel-close" onclick="closeAuthPanels()" aria-label="Close">&times;</button>
        </div>
        <div class="auth-panel-body">
            <div id="panel-login-alert" class="auth-panel-alert"></div>
            <form id="panel-login-form" novalidate>
                <input type="hidden" name="_ajax" value="1">
                <div class="form-group">
                    <label for="pl-username">Username</label>
                    <input type="text" id="pl-username" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="pl-password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="pl-password" name="password" required autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePanelPwd('pl-password',this)" aria-label="Toggle password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="panel-login-btn">Login</button>
            </form>
            <div class="auth-panel-demo">
                <strong>Demo Credentials (Staff password: Staff@123)</strong>
                <table>
                    <tr><td><strong>Visitor</strong></td><td>user / user123</td></tr>
                    <tr><td><strong>Admin</strong></td><td>admin / admin123</td></tr>
                    <tr><td><strong>Ticketing</strong></td><td>ticketing1</td></tr>
                    <tr><td><strong>Tour Guide</strong></td><td>guide1</td></tr>
                    <tr><td><strong>Maintenance</strong></td><td>maintenance1</td></tr>
                    <tr><td><strong>Shop Staff</strong></td><td>shopstaff1</td></tr>
                    <tr><td><strong>Manager</strong></td><td>manager</td></tr>
                </table>
            </div>
            <div class="auth-panel-footer-link">
                <p>Don't have an account? <a href="#" onclick="switchAuthPanel('register');return false;">Register here</a></p>
            </div>
        </div>
    </div>

    <!-- Register Side Panel -->
    <div id="panel-register" class="auth-side-panel">
        <div class="auth-panel-header">
            <div>
                <h2 class="auth-panel-title">Create Account</h2>
                <p class="auth-panel-subtitle">Register to book tickets and tours</p>
            </div>
            <button class="auth-panel-close" onclick="closeAuthPanels()" aria-label="Close">&times;</button>
        </div>
        <div class="auth-panel-body">
            <div id="panel-register-alert" class="auth-panel-alert"></div>
            <form id="panel-register-form" novalidate>
                <input type="hidden" name="_ajax" value="1">
                <div class="form-group">
                    <label for="pr-fullname">Full Name <span style="color:#a67474;">*</span></label>
                    <input type="text" id="pr-fullname" name="full_name" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="pr-email">Email <span style="color:#a67474;">*</span></label>
                    <input type="email" id="pr-email" name="email" required autocomplete="email">
                </div>
                <div class="auth-panel-two-col">
                    <div class="form-group">
                        <label for="pr-username">Username <span style="color:#a67474;">*</span></label>
                        <input type="text" id="pr-username" name="username" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="pr-phone">Phone</label>
                        <input type="tel" id="pr-phone" name="phone" autocomplete="tel">
                    </div>
                </div>
                <div class="form-group">
                    <label for="pr-password">Password <span style="color:#a67474;">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="pr-password" name="password" minlength="6" required autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePanelPwd('pr-password',this)" aria-label="Toggle password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="pr-confirm">Confirm Password <span style="color:#a67474;">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="pr-confirm" name="confirm_password" minlength="6" required autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePanelPwd('pr-confirm',this)" aria-label="Toggle password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <p style="font-size:0.8rem;color:var(--smoky-oak);margin-bottom:1rem;">Password must be at least 6 characters long</p>
                <button type="submit" class="btn btn-primary btn-block" id="panel-register-btn">Register</button>
            </form>
            <div class="auth-panel-footer-link">
                <p>Already have an account? <a href="#" onclick="switchAuthPanel('login');return false;">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        function openAuthPanel(which) {
            var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.paddingRight = scrollbarWidth + 'px';
            document.body.style.overflow = 'hidden';
            document.getElementById('auth-panel-overlay').classList.add('active');
            document.getElementById('panel-' + which).classList.add('active');
        }
        function closeAuthPanels() {
            document.getElementById('auth-panel-overlay').classList.remove('active');
            document.querySelectorAll('.auth-side-panel').forEach(p => p.classList.remove('active'));
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
        function switchAuthPanel(which) {
            document.querySelectorAll('.auth-side-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + which).classList.add('active');
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAuthPanels(); });

        function togglePanelPwd(id, btn) {
            const inp = document.getElementById(id);
            const svg = btn.querySelector('svg');
            if (inp.type === 'password') {
                inp.type = 'text';
                svg.innerHTML = '<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>';
            } else {
                inp.type = 'password';
                svg.innerHTML = '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>';
            }
        }

        // Login panel submit
        document.getElementById('panel-login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('panel-login-btn');
            const al  = document.getElementById('panel-login-alert');
            btn.disabled = true; btn.textContent = 'Logging in…';
            al.style.display = 'none'; al.className = 'auth-panel-alert';

            const fd = new FormData(this);
            fetch('login.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        al.className = 'auth-panel-alert error';
                        al.textContent = data.error;
                        al.style.display = 'block';
                        btn.disabled = false; btn.textContent = 'Login';
                    }
                })
                .catch(() => {
                    al.className = 'auth-panel-alert error';
                    al.textContent = 'An error occurred. Please try again.';
                    al.style.display = 'block';
                    btn.disabled = false; btn.textContent = 'Login';
                });
        });

        // Register panel submit
        document.getElementById('panel-register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('panel-register-btn');
            const al  = document.getElementById('panel-register-alert');
            btn.disabled = true; btn.textContent = 'Creating account…';
            al.style.display = 'none'; al.className = 'auth-panel-alert';

            if (document.getElementById('pr-password').value !== document.getElementById('pr-confirm').value) {
                al.className = 'auth-panel-alert error';
                al.textContent = 'Passwords do not match.';
                al.style.display = 'block';
                btn.disabled = false; btn.textContent = 'Register';
                return;
            }
            const fd = new FormData(this);
            fetch('register.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    al.className = 'auth-panel-alert ' + (data.success ? 'success' : 'error');
                    al.textContent = data.success ? data.message : data.error;
                    al.style.display = 'block';
                    btn.disabled = false; btn.textContent = 'Register';
                    if (data.success) { this.reset(); }
                })
                .catch(() => {
                    al.className = 'auth-panel-alert error';
                    al.textContent = 'An error occurred. Please try again.';
                    al.style.display = 'block';
                    btn.disabled = false; btn.textContent = 'Register';
                });
        });

        // Auto-open panel if redirected from login.php or register.php
        (function() {
            const params = new URLSearchParams(window.location.search);
            const panel = params.get('panel');
            if (panel === 'login' || panel === 'register') {
                openAuthPanel(panel);
                // Show notice message (e.g. "Please log in to purchase tickets")
                const noticeMap = {
                    tickets: '🎫 Please log in to purchase tickets.',
                    cart:    '🛒 Please log in to view your cart.',
                    shop:    '🛍️ Please log in to access the souvenir shop.',
                    tours:   '🗺️ Please log in to book a guided tour.'
                };
                const msg = params.get('msg');
                if (msg && noticeMap[msg]) {
                    const al = document.getElementById('panel-login-alert');
                    al.className = 'auth-panel-alert info';
                    al.textContent = noticeMap[msg];
                    al.style.display = 'block';
                }
                // Clean the ?panel= param from the URL without reloading
                const clean = window.location.pathname;
                history.replaceState(null, '', clean);
            }
        })();

        // Date inputs inside .date-input-wrap: only open picker via the icon button,
        // not when clicking the text area of the input.
        document.addEventListener('mousedown', function(e) {
            const input = e.target.closest('.date-input-wrap input[type="date"]');
            if (input) {
                e.preventDefault();
                input.focus();
            }
        }, true);
    </script>

    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><strong><?php echo MUSEUM_NAME; ?></strong></a>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a href="exhibits.php" class="nav-link <?php echo ($current_page == 'exhibits.php') ? 'active' : ''; ?>">Exhibits</a>
                </li>
                <li class="nav-item">
                    <a href="artworks.php" class="nav-link <?php echo ($current_page == 'artworks.php') ? 'active' : ''; ?>">Artworks</a>
                </li>
                <li class="nav-item">
                    <a href="tours.php" class="nav-link <?php echo ($current_page == 'tours.php') ? 'active' : ''; ?>">Tours</a>
                </li>
                <li class="nav-item">
                    <a href="tickets.php" class="nav-link <?php echo ($current_page == 'tickets.php') ? 'active' : ''; ?>">Tickets</a>
                </li>
                <li class="nav-item">
                    <a href="shop.php" class="nav-link <?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>">Shop</a>
                </li>
                <li class="nav-item">
                    <a href="feedback.php" class="nav-link <?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">Feedback</a>
                </li>
                <?php if (isset($_SESSION['cart']) && array_sum($_SESSION['cart']) > 0): ?>
                <li class="nav-item">
                    <a href="cart.php" class="nav-link" style="background:var(--primary-dark);color:white;border-radius:4px;padding:.3rem .75rem;">
                        🛒 <?= array_sum($_SESSION['cart']) ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <!-- Auth actions separated visually -->
            <div class="nav-auth-group">
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                    <span class="nav-link nav-welcome" style="color: rgba(250,243,227,0.7); font-size:0.82rem; letter-spacing:0.5px; height:auto; padding:0; cursor:default;">
                        Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="my-account.php" class="nav-link nav-login <?php echo ($current_page == 'my-account.php') ? 'active' : ''; ?>">My Account</a>
                    <a href="logout.php" class="nav-link nav-login">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link nav-login <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" onclick="openAuthPanel('login'); return false;">Login</a>
                    <a href="register.php" class="nav-link nav-login <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>" onclick="openAuthPanel('register'); return false;">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
