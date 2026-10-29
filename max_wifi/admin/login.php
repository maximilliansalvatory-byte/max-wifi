<?php
/**
 * =====================================================
 * MAX WIFI - Admin Login (with Security)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!rateLimit('admin_login', 5, 300)) {
        $error = 'Too many login attempts. Please wait 5 minutes and try again.';
    } elseif (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            $auth = new Auth();
            $result = $auth->login($username, $password);

            if ($result['success']) {
                clearRateLimit('admin_login');
                redirect('dashboard.php');
            } else {
                $error = $result['message'] ?? 'Invalid username or password.';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }
        .login-card .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
            text-align: center;
            margin-bottom: 0.3rem;
        }
        .login-card .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 2rem;
        }
        .btn-login {
            background: #3b82f6;
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo">MAX WIFI</div>
    <div class="subtitle">Admin Panel</div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= clean($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control form-control-lg" 
                   value="<?= clean($username) ?>" required autofocus>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" required>
        </div>

        <button type="submit" class="btn btn-primary btn-login">Sign In</button>
    </form>

    <div class="text-center mt-4">
        <small class="text-muted">© <?= date('Y') ?> MAX WIFI</small>
    </div>
</div>

</body>
</html>