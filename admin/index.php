<?php
// admin/index.php
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Go to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

/* ── Rate limiting: max 5 attempts per 15 minutes per session ── */
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}
$_SESSION['login_attempts'] = array_filter(
    $_SESSION['login_attempts'],
    fn($t) => $t > time() - 900
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (count($_SESSION['login_attempts']) >= 5) {
        $error = 'Too many attempts. Please wait 15 minutes and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Enter both username and password.';
        } else {
            $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_user']     = $username;
                $_SESSION['last_activity']  = time();
                unset($_SESSION['login_attempts']);

                $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")
                    ->execute([$admin['id']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $_SESSION['login_attempts'][] = time();
                $error = 'Invalid credentials.';
            }
        }
    }
}

$timeoutMsg = isset($_GET['timeout']) ? 'Session timed out. Please log in again.' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — ConverterTool.in</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #F1F5F9;
            margin: 0;
        }
        .login-wrap {
            width: 100%;
            max-width: 380px;
            padding: 1rem;
        }
        .login-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(15,23,42,0.06);
        }
        .login-brand {
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: #0F172A;
            margin-bottom: 0.25rem;
        }
        .login-brand span { color: #2563EB; }
        .login-sub {
            text-align: center;
            color: #64748B;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .login-field { margin-bottom: 1rem; }
        .login-field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }
        .login-field input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #fff;
            color: #0F172A;
        }
        .login-field input:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: #0F172A;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .login-btn:hover { background: #2563EB; }
        .login-error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .login-hint {
            text-align: center;
            font-size: 0.78rem;
            color: #94A3B8;
            margin-top: 1.25rem;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand" style="display:flex;align-items:center;justify-content:center;gap:0.6rem;">
            <img
                src="<?= BASE_PATH ?>assets/img/icon-192.png"
                alt=""
                width="40"
                height="40"
                style="border-radius:10px;box-shadow:0 2px 6px rgba(15,23,42,0.15);"
                decoding="async"
            >
            <span>ConverterTool<span style="color:#2563EB;">.in</span> Admin</span>
        </div>
        <div class="login-sub">Sign in to continue</div>

        <?php if ($error): ?>
            <div class="login-error"><?= e($error) ?></div>
        <?php elseif ($timeoutMsg): ?>
            <div class="login-error"><?= e($timeoutMsg) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="login-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="login-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Sign in</button>
        </form>

        <div class="login-hint">Protected area — unauthorized access prohibited</div>
    </div>
</div>
</body>
</html>