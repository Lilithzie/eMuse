<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $authenticated = false;
    
    // First, try to authenticate as admin
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND role IN ('super_admin', 'admin', 'staff')");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Admin login successful
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['user_type'] = 'admin';
        header('Location: ../admin/index.php');
        exit();
    }
    
    // If not admin, try regular user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Regular user login successful
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = 'user';
        
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->execute([$user['user_id']]);
        
        // Redirect to home page or previous page
        $redirect = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit();
    }
    
    // If neither admin nor user, show error
    if (!$authenticated) {
        $error = 'Invalid username or password';
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .login-overlay {
        min-height: calc(100vh - 70px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 3rem 2rem;
        background: var(--cream-harvest);
    }
    
    .login-box {
        background: white;
        padding: 3rem;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        width: 100%;
        max-width: 420px;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .login-header h1 {
        color: var(--chestnut-grove);
        margin-bottom: 0.5rem;
        font-size: 2rem;
    }
    
    .login-header p {
        color: var(--smoky-oak);
        font-size: 0.95rem;
    }
    
    .login-overlay .form-group {
        margin-bottom: 1.5rem;
    }
    
    .login-info {
        background: var(--cream-harvest);
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1.5rem;
        font-size: 0.9rem;
    }
    
    .login-info strong {
        color: var(--chestnut-grove);
    }
    
    .register-link {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
    }
    
    .register-link a {
        color: var(--chestnut-grove);
        text-decoration: none;
        font-weight: 500;
    }
    
    .register-link a:hover {
        text-decoration: underline;
    }
</style>

<div class="login-overlay">
    <div class="login-box">
        <div class="login-header">
            <h1>Login</h1>
            <p>Access Your Account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <!-- Login Info -->
        <div class="login-info">
            <p><strong>Demo Credentials:</strong></p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                User: <strong>user</strong> / <strong>user123</strong><br>
                Admin: <strong>admin</strong> / <strong>admin123</strong>
            </p>
        </div>
        
        <!-- Register Link -->
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
