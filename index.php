<?php
// ============================================================
// Login Page — Sharon Store System
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $go = $_SESSION['role'] === 'admin' ? '/sharonstore3.0/dashboard.php' : '/sharonstore3.0/pos.php';
    header("Location: $go");
    exit();
}

require_once __DIR__ . '/includes/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT user_id, username, password, full_name, role FROM tbl_users WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Audit log
                require_once __DIR__ . '/includes/audit.php';
                logAudit($user['user_id'], 'LOGIN', 'Auth', "User '{$user['username']}' signed in.");

                $redirect = $user['role'] === 'admin' ? '/sharonstore3.0/dashboard.php' : '/sharonstore3.0/pos.php';
                header("Location: $redirect");
                exit();
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'System error. Please contact the administrator.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Sharon Store</title>
    <meta name="description" content="Sharon Store POS & Inventory Management System — Secure Login">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }
body {
    font-family: 'Inter', system-ui, sans-serif;
    background: #0f172a;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}

/* Animated background */
.login-bg {
    position: fixed; inset: 0; z-index: 0;
    background: radial-gradient(ellipse 80% 80% at 50% -20%, rgba(16,185,129,0.15) 0%, transparent 60%),
                linear-gradient(135deg, #0a1628 0%, #0f172a 50%, #0a1628 100%);
}
.login-bg-orb {
    position: absolute; border-radius: 50%;
    filter: blur(80px); opacity: 0.5;
    animation: orb-move 8s ease-in-out infinite alternate;
}
.login-bg-orb.orb1 {
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(16,185,129,0.25), transparent 70%);
    top: -10%; left: -5%;
}
.login-bg-orb.orb2 {
    width: 350px; height: 350px;
    background: radial-gradient(circle, rgba(59,130,246,0.15), transparent 70%);
    bottom: -10%; right: -5%;
    animation-delay: -4s;
}
.login-bg-orb.orb3 {
    width: 250px; height: 250px;
    background: radial-gradient(circle, rgba(139,92,246,0.12), transparent 70%);
    top: 50%; left: 60%;
    animation-delay: -2s;
}
@keyframes orb-move {
    from { transform: translate(0,0) scale(1); }
    to   { transform: translate(30px, 20px) scale(1.08); }
}

/* Card */
.login-card {
    position: relative; z-index: 1;
    width: 100%; max-width: 420px;
    background: rgba(30, 42, 58, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    padding: 44px 40px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
    animation: card-in 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) both;
}
@keyframes card-in {
    from { opacity:0; transform: translateY(30px) scale(0.97); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}

/* Logo */
.login-logo {
    display: flex; align-items: center; justify-content: center;
    gap: 14px; margin-bottom: 28px;
}
.login-logo-icon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    box-shadow: 0 8px 24px rgba(16,185,129,0.45);
}
.login-logo-text {
    display: flex; flex-direction: column; line-height: 1.2;
}
.login-store-name {
    font-size: 1.5rem; font-weight: 900;
    background: linear-gradient(135deg, #10b981, #34d399);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; letter-spacing: -0.5px;
}
.login-store-sub {
    font-size: 0.72rem; color: #8fa3bc; font-weight: 500;
    letter-spacing: 0.03em;
}

/* Title */
.login-title {
    text-align: center; margin-bottom: 28px;
}
.login-title h1 {
    font-size: 1.35rem; font-weight: 800; color: #ffffff;
    letter-spacing: -0.3px; margin-bottom: 6px;
}
.login-title p {
    font-size: 0.85rem; color: #94a3b8;
}

/* Form */
.form-group { margin-bottom: 18px; }
.form-label {
    display: block; font-size: 0.82rem; font-weight: 600;
    color: #d1dae8; margin-bottom: 8px;
    letter-spacing: 0.02em;
}
.input-wrap { position: relative; }
.input-icon {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%); color: #475569;
    font-size: 15px; pointer-events: none;
    transition: color 0.2s;
}
.login-input {
    width: 100%; padding: 13px 16px 13px 42px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px; color: #ffffff;
    font-size: 0.93rem; font-family: 'Inter', sans-serif;
    font-weight: 500; transition: all 0.2s;
    outline: none;
}
.login-input::placeholder { color: #4d6a85; }
.login-input:focus {
    background: rgba(16,185,129,0.05);
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
}
.login-input:focus + .input-icon,
.input-wrap:focus-within .input-icon { color: #10b981; }

.pw-toggle {
    position: absolute; right: 14px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; color: #475569;
    cursor: pointer; font-size: 15px; padding: 4px;
    transition: color 0.2s;
}
.pw-toggle:hover { color: #94a3b8; }

/* Error alert */
.login-error {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 10px; padding: 12px 16px;
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 0.83rem; color: #fca5a5;
    margin-bottom: 18px; animation: shake 0.4s ease;
}
@keyframes shake {
    0%,100% { transform: translateX(0); }
    25%      { transform: translateX(-6px); }
    75%      { transform: translateX(6px); }
}
.login-error i { flex-shrink:0; margin-top:1px; font-size:1rem; color:#f87171; }

/* Login button */
.login-btn {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none; border-radius: 12px;
    color: #fff; font-size: 0.95rem; font-weight: 700;
    font-family: 'Inter', sans-serif; cursor: pointer;
    transition: all 0.2s; letter-spacing: 0.02em;
    box-shadow: 0 4px 16px rgba(16,185,129,0.35);
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 8px;
}
.login-btn:hover {
    background: linear-gradient(135deg, #34d399, #10b981);
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(16,185,129,0.5);
}
.login-btn:active { transform: translateY(0); }
.login-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Credentials hint */
.login-hint {
    margin-top: 24px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px; padding: 14px 16px;
}
.login-hint-title {
    font-size: 0.7rem; font-weight: 700; color: #8fa3bc;
    text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px;
}
.cred-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 4px 0;
}
.cred-role {
    font-size: 0.75rem; font-weight: 700;
    padding: 2px 8px; border-radius: 12px;
}
.cred-role.admin    { background: rgba(16,185,129,0.18); color: #6ee7b7; }
.cred-role.cashier  { background: rgba(96,165,250,0.18);  color: #93c5fd; }
.cred-info { font-size: 0.8rem; color: #a0b4cc; font-family: 'Courier New', monospace; font-weight: 600; }
.cred-btn  { font-size: 0.72rem; color: #34d399; background: none; border: none; cursor: pointer; padding: 2px 6px; font-weight: 600; }
.cred-btn:hover { color: #6ee7b7; }

/* Footer */
.login-footer {
    text-align: center; margin-top: 20px;
    font-size: 0.73rem; color: #5c7a99;
}
</style>
</head>
<body>

<div class="login-bg">
    <div class="login-bg-orb orb1"></div>
    <div class="login-bg-orb orb2"></div>
    <div class="login-bg-orb orb3"></div>
</div>

<div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
        <div class="login-logo-icon"><i class="fa-solid fa-leaf"></i></div>
        <div class="login-logo-text">
            <span class="login-store-name">Sharon Store</span>
            <span class="login-store-sub">Inventory & Sales Forecasting System</span>
        </div>
    </div>

    <!-- Title -->
    <div class="login-title">
        <h1>Welcome Back</h1>
        <p>Sign in to continue to your dashboard</p>
    </div>

    <!-- Error Message -->
    <?php if ($error): ?>
    <div class="login-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="" id="loginForm" autocomplete="on">

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
                <input type="text" id="username" name="username" class="login-input"
                       placeholder="Enter your username" required autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <i class="fa-solid fa-user input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" class="login-input"
                       placeholder="Enter your password" required autocomplete="current-password"
                       style="padding-right: 44px;">
                <i class="fa-solid fa-lock input-icon"></i>
                <button type="button" class="pw-toggle" id="pwToggle" tabindex="-1"
                        title="Show/hide password">
                    <i class="fa-solid fa-eye" id="pwIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">
            <i class="fa-solid fa-right-to-bracket"></i>
            <span id="loginBtnText">Sign In</span>
        </button>

    </form>

    <!-- Credentials Hint -->
    <div class="login-hint">
        <div class="login-hint-title"><i class="fa-solid fa-circle-info me-1"></i>Demo Credentials</div>
        <div class="cred-row">
            <span class="cred-role admin">Admin</span>
            <span class="cred-info">admin / admin123</span>
            <button class="cred-btn" onclick="fillCreds('admin','admin123')" title="Fill credentials">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Use
            </button>
        </div>
        <div class="cred-row">
            <span class="cred-role cashier">Cashier</span>
            <span class="cred-info">cashier1 / cashier123</span>
            <button class="cred-btn" onclick="fillCreds('cashier1','cashier123')" title="Fill credentials">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Use
            </button>
        </div>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> Sharon Store &mdash; Pampang Public Market
    </div>

</div>

<script>
// Password toggle
document.getElementById('pwToggle').addEventListener('click', function() {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('pwIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        pw.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
});

// Fill demo credentials
function fillCreds(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
    document.getElementById('username').focus();
}

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn  = document.getElementById('loginBtn');
    const text = document.getElementById('loginBtnText');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Signing in…</span>';
});
</script>
</body>
</html>
