<?php
/**
 * =====================================================
 * MAX WIFI - Customer Login (with Security)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();

if (isset($_SESSION['customer_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!rateLimit('customer_login', 5, 300)) {
        $error = 'Too many login attempts. Please wait 5 minutes and try again.';
    } elseif (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            $db  = Database::getInstance();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("
                SELECT id, uuid, username, password, full_name, status 
                FROM customers 
                WHERE username = ? AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $customer = $stmt->fetch();

            if (!$customer) {
                $error = 'Invalid username or password.';
            } elseif ($customer['status'] !== 'active') {
                $error = 'Your account is ' . $customer['status'] . '. Please contact support.';
            } elseif (!verifyPassword($password, $customer['password'])) {
                $error = 'Invalid username or password.';
            } else {
                clearRateLimit('customer_login');
                session_regenerate_id(true);

                $_SESSION['customer_id']   = $customer['id'];
                $_SESSION['customer_uuid'] = $customer['uuid'];
                $_SESSION['customer_name'] = $customer['full_name'] ?: $customer['username'];
                $_SESSION['customer_user'] = $customer['username'];

                $update = $pdo->prepare("UPDATE customers SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
                $update->execute([getClientIp(), $customer['id']]);

                header("Location: dashboard.php");
                exit;
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
    <title>Customer Login - MAX WIFI</title>
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
            font-size: 0.95rem;
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
    <div class="subtitle">Customer Portal</div>

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

    <div class="text-center mt-3">
        <small>Don't have an account? <a href="register.php">Register here</a></small>
    </div>

    <div class="text-center mt-3">
        <small class="text-muted">© <?= date('Y') ?> MAX WIFI</small>
    </div>
</div>

</body>
</html>