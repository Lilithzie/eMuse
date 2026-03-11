<?php
require_once '../config/config.php';
if (!empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        if ($admin['role'] !== 'maintenance_staff') {
            $error = 'This portal is for Maintenance Staff only.';
        } else {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['admin_id'];
            $_SESSION['admin_name']      = $admin['full_name'];
            $_SESSION['admin_role']      = $admin['role'];
            header('Location: index.php'); exit();
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eMuse — Maintenance Staff Login</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= @filemtime('../assets/css/style.css') ?>">
    <style>
        body { background:var(--bg-color); display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .login-box { background:#fff; border-radius:16px; padding:2.5rem; width:100%; max-width:420px; box-shadow:0 8px 32px rgba(0,0,0,.14); }
        .login-box img { display:block; margin:0 auto 1.2rem; max-width:150px; }
        .login-box h2 { text-align:center; color:var(--chestnut-grove); margin-bottom:.2rem; }
        .login-box .subtitle { text-align:center; color:var(--smoky-oak); font-size:.9rem; margin-bottom:1.5rem; }
        .btn-block { width:100%; margin-top:.5rem; }
    </style>
</head>
<body>
<div class="login-box">
    <img src="../img/emuse-logo.png" alt="eMuse Logo">
    <h2>Maintenance Staff</h2>
    <p class="subtitle">Sign in to your account</p>
    <?php if ($error): ?><div class="alert alert-error" style="margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>
</div>
</body>
</html>
