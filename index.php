<?php
// ============================================================
// Auth Page (Sign In & Cashier Registration) — Sharon Store System
// ============================================================
require_once __DIR__ . '/includes/db.php';
startAppSession();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $go = $_SESSION['role'] === 'admin' ? '/sharonstore3.0/dashboard.php' : '/sharonstore3.0/pos.php';
    header("Location: $go");
    exit();
}

$error     = '';
$success   = '';
$activeTab = 'login'; // 'login' or 'register'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? 'login';

    if ($formAction === 'register') {
        $activeTab = 'register';
        $regFullName = trim($_POST['reg_full_name'] ?? '');
        $regUsername = trim($_POST['reg_username'] ?? '');
        $regPassword = $_POST['reg_password'] ?? '';
        $regConfirm  = $_POST['reg_confirm'] ?? '';

        if ($regFullName === '' || $regUsername === '' || $regPassword === '') {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($regUsername) < 3 || strlen($regUsername) > 30) {
            $error = 'Username must be between 3 and 30 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $regUsername)) {
            $error = 'Username can only contain letters, numbers, and underscores.';
        } elseif (strlen($regPassword) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($regPassword !== $regConfirm) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db = getDB();
                $chk = $db->prepare("SELECT COUNT(*) FROM tbl_users WHERE LOWER(username) = LOWER(?)");
                $chk->execute([$regUsername]);
                if ((int)$chk->fetchColumn() > 0) {
                    $error = "Username '{$regUsername}' is already taken. Please choose another.";
                } else {
                    $hash = password_hash($regPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    $ins = $db->prepare("INSERT INTO tbl_users (username, password, full_name, role, is_active) VALUES (?, ?, ?, 'cashier', 0)");
                    $ins->execute([$regUsername, $hash, $regFullName]);
                    $newId = (int)$db->lastInsertId();

                    require_once __DIR__ . '/includes/audit.php';
                    logAudit($newId, 'SELF_REGISTER', 'Auth', "Cashier '{$regUsername}' ({$regFullName}) registered. Awaiting manager approval.");

                    $success = "Registration submitted! Your account is pending Store Manager approval in the Accounts panel.";
                    $activeTab = 'login';
                    $_POST['username'] = $regUsername; // Pre-fill login input
                }
            } catch (Exception $e) {
                $error = 'Registration failed due to a system error. Please contact the administrator.';
                error_log("Register error: " . $e->getMessage());
            }
        }
    } else {
        // Sign In
        $activeTab = 'login';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $db   = getDB();
                $stmt = $db->prepare("SELECT user_id, username, password, full_name, role, is_active FROM tbl_users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ((int)$user['is_active'] !== 1) {
                        $error = 'Your account is pending manager approval. Please ask the Store Manager to activate your account.';
                    } else {
                        $_SESSION['user_id']   = $user['user_id'];
                        $_SESSION['username']  = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role']      = $user['role'];

                        // Audit log
                        require_once __DIR__ . '/includes/audit.php';
                        logAudit($user['user_id'], 'LOGIN', 'Auth', "User '{$user['username']}' signed in.");

                        $redirect = $user['role'] === 'admin' ? '/sharonstore3.0/dashboard.php' : '/sharonstore3.0/pos.php';
                        session_write_close();
                        header("Location: $redirect");
                        exit();
                    }
                } else {
                    $error = 'Invalid username or password. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'System error. Please contact the administrator.';
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Sharon Store</title>
    <meta name="description" content="Sharon Store POS & Inventory Management System — Secure Authentication">
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
    overflow-x: hidden;
    padding: 24px 16px;
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
    width: 100%; max-width: 440px;
    background: rgba(30, 42, 58, 0.88);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    padding: 38px 36px;
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
    gap: 14px; margin-bottom: 22px;
}
.login-logo-icon {
    width: 50px; height: 50px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 15px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    box-shadow: 0 8px 24px rgba(16,185,129,0.45);
}
.login-logo-text {
    display: flex; flex-direction: column; line-height: 1.2;
}
.login-store-name {
    font-size: 1.45rem; font-weight: 900;
    background: linear-gradient(135deg, #10b981, #34d399);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; letter-spacing: -0.5px;
}
.login-store-sub {
    font-size: 0.72rem; color: #8fa3bc; font-weight: 500;
    letter-spacing: 0.03em;
}

/* Auth Segmented Tabs */
.auth-tabs {
    display: flex;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 13px;
    padding: 4px;
    gap: 4px;
    margin-bottom: 24px;
}
.auth-tab {
    flex: 1;
    padding: 9px 12px;
    border-radius: 10px;
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    outline: none;
}
.auth-tab:hover {
    color: #ffffff;
}
.auth-tab.active {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(16,185,129,0.35);
}

/* Title */
.login-title {
    text-align: center; margin-bottom: 22px;
}
.login-title h1 {
    font-size: 1.3rem; font-weight: 800; color: #ffffff;
    letter-spacing: -0.3px; margin-bottom: 4px;
}
.login-title p {
    font-size: 0.82rem; color: #94a3b8;
}

/* Form */
.form-group { margin-bottom: 16px; }
.form-label {
    display: block; font-size: 0.8rem; font-weight: 600;
    color: #d1dae8; margin-bottom: 6px;
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
    width: 100%; padding: 12px 16px 12px 42px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px; color: #ffffff;
    font-size: 0.92rem; font-family: 'Inter', sans-serif;
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

/* Alerts */
.login-error {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 10px; padding: 12px 14px;
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 0.82rem; color: #fca5a5;
    margin-bottom: 18px; animation: shake 0.4s ease;
}
@keyframes shake {
    0%,100% { transform: translateX(0); }
    25%      { transform: translateX(-6px); }
    75%      { transform: translateX(6px); }
}
.login-error i { flex-shrink:0; margin-top:2px; font-size:1rem; color:#f87171; }

.login-success {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.3);
    border-radius: 10px; padding: 12px 14px;
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 0.82rem; color: #6ee7b7;
    margin-bottom: 18px;
}
.login-success i { flex-shrink:0; margin-top:2px; font-size:1rem; color:#10b981; }

.login-info-box {
    background: rgba(16,185,129,0.06);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.77rem;
    color: #94a3b8;
    line-height: 1.45;
    margin-bottom: 16px;
}

/* Submit button */
.login-btn {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none; border-radius: 12px;
    color: #fff; font-size: 0.94rem; font-weight: 700;
    font-family: 'Inter', sans-serif; cursor: pointer;
    transition: all 0.2s; letter-spacing: 0.02em;
    box-shadow: 0 4px 16px rgba(16,185,129,0.35);
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 6px;
}
.login-btn:hover {
    background: linear-gradient(135deg, #34d399, #10b981);
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(16,185,129,0.5);
}
.login-btn:active { transform: translateY(0); }
.login-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Switch link prompt */
.login-switch-prompt {
    text-align: center;
    margin-top: 22px;
    font-size: 0.82rem;
    color: #8fa3bc;
}
.login-switch-prompt a {
    color: #34d399;
    text-decoration: none;
    font-weight: 700;
    margin-left: 4px;
    transition: color 0.2s;
}
.login-switch-prompt a:hover {
    color: #6ee7b7;
    text-decoration: underline;
}

/* Footer */
.login-footer {
    text-align: center; margin-top: 22px;
    font-size: 0.72rem; color: #5c7a99;
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

    <!-- Segmented Tabs -->
    <div class="auth-tabs">
        <button type="button" class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" id="tabSignIn" onclick="switchAuthTab('login')">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
        </button>
        <button type="button" class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" id="tabRegister" onclick="switchAuthTab('register')">
            <i class="fa-solid fa-user-plus me-2"></i>Register
        </button>
    </div>

    <!-- Title -->
    <div class="login-title">
        <h1 id="authTitle"><?= $activeTab === 'register' ? 'Create Cashier Account' : 'Welcome Back' ?></h1>
        <p id="authSubtitle"><?= $activeTab === 'register' ? 'Submit details for Store Manager activation' : 'Sign in to access POS & Store Dashboard' ?></p>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
    <div class="login-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="login-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>

    <!-- ══ LOGIN FORM ═════════════════════════════════════════════ -->
    <form method="POST" action="" id="loginForm" class="<?= $activeTab === 'register' ? 'd-none' : '' ?>" autocomplete="on">
        <input type="hidden" name="form_action" value="login">

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
                <button type="button" class="pw-toggle" onclick="togglePasswordVisibility('password', 'pwIcon')" tabindex="-1" title="Show/hide password">
                    <i class="fa-solid fa-eye" id="pwIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">
            <i class="fa-solid fa-right-to-bracket"></i>
            <span id="loginBtnText">Sign In</span>
        </button>
    </form>

    <!-- ══ REGISTER FORM ══════════════════════════════════════════ -->
    <form method="POST" action="" id="registerForm" class="<?= $activeTab === 'register' ? '' : 'd-none' ?>" autocomplete="off">
        <input type="hidden" name="form_action" value="register">

        <div class="login-info-box">
            <i class="fa-solid fa-shield-halved text-emerald me-1"></i>
            Self-registered accounts are set to <strong>Cashier</strong> and require <strong>Store Manager activation</strong> in Accounts before sign in.
        </div>

        <div class="form-group">
            <label class="form-label" for="regFullName">Full Name <span class="text-danger">*</span></label>
            <div class="input-wrap">
                <input type="text" id="regFullName" name="reg_full_name" class="login-input"
                       placeholder="e.g. Maria Santos" required
                       value="<?= htmlspecialchars($_POST['reg_full_name'] ?? '') ?>">
                <i class="fa-solid fa-id-card input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="regUsername">Desired Username <span class="text-danger">*</span></label>
            <div class="input-wrap">
                <input type="text" id="regUsername" name="reg_username" class="login-input"
                       placeholder="e.g. cashier2" required autocomplete="username"
                       value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>">
                <i class="fa-solid fa-at input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="regPassword">Password <span class="text-danger">*</span></label>
            <div class="input-wrap">
                <input type="password" id="regPassword" name="reg_password" class="login-input"
                       placeholder="Min. 6 characters" required autocomplete="new-password"
                       style="padding-right: 44px;">
                <i class="fa-solid fa-lock input-icon"></i>
                <button type="button" class="pw-toggle" onclick="togglePasswordVisibility('regPassword', 'regPwIcon')" tabindex="-1" title="Show/hide password">
                    <i class="fa-solid fa-eye" id="regPwIcon"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="regConfirm">Confirm Password <span class="text-danger">*</span></label>
            <div class="input-wrap">
                <input type="password" id="regConfirm" name="reg_confirm" class="login-input"
                       placeholder="Re-enter password" required autocomplete="new-password">
                <i class="fa-solid fa-circle-check input-icon"></i>
            </div>
        </div>

        <button type="submit" class="login-btn" id="regBtn">
            <i class="fa-solid fa-user-plus"></i>
            <span id="regBtnText">Create Account</span>
        </button>
    </form>

    <div class="login-footer">
        &copy; <?= date('Y') ?> Sharon Store &mdash; Pampang Public Market
    </div>

</div>

<script>
// Switch between Sign In and Register tabs
function switchAuthTab(tab) {
    const loginForm   = document.getElementById('loginForm');
    const registerForm= document.getElementById('registerForm');
    const tabSignIn   = document.getElementById('tabSignIn');
    const tabRegister = document.getElementById('tabRegister');
    const authTitle   = document.getElementById('authTitle');
    const authSubtitle= document.getElementById('authSubtitle');

    if (tab === 'register') {
        loginForm.classList.add('d-none');
        registerForm.classList.remove('d-none');
        tabSignIn.classList.remove('active');
        tabRegister.classList.add('active');
        authTitle.textContent = 'Create Cashier Account';
        authSubtitle.textContent = 'Submit details for Store Manager activation';
        document.getElementById('regFullName').focus();
    } else {
        registerForm.classList.add('d-none');
        loginForm.classList.remove('d-none');
        tabRegister.classList.remove('active');
        tabSignIn.classList.add('active');
        authTitle.textContent = 'Welcome Back';
        authSubtitle.textContent = 'Sign in to access POS & Store Dashboard';
        document.getElementById('username').focus();
    }
}

// Password toggle helper
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

// Form submit loading states
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i><span>Signing in...</span>';
});

document.getElementById('registerForm').addEventListener('submit', function() {
    const btn = document.getElementById('regBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i><span>Submitting registration...</span>';
});
</script>
</body>
</html>
