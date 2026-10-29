<?php
/**
 * =====================================================
 * MAX WIFI - Captive Portal (Improved)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';
$showSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $loginType = $_POST['login_type'] ?? 'account';

    if ($loginType === 'account') {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            $db  = Database::getInstance();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("
                SELECT id, username, password, full_name, status 
                FROM customers 
                WHERE username = ? AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $customer = $stmt->fetch();

            if (!$customer || !verifyPassword($password, $customer['password'])) {
                $error = 'Invalid username or password.';
            } elseif ($customer['status'] !== 'active') {
                $error = 'Your account is not active.';
            } else {
                $sub = $pdo->prepare("
                    SELECT id FROM subscriptions 
                    WHERE customer_id = ? AND status = 'active' AND end_at > NOW()
                    LIMIT 1
                ");
                $sub->execute([$customer['id']]);
                
                if (!$sub->fetch()) {
                    $error = 'You have no active package. Please buy a package first.';
                } else {
                    $success = 'Login successful! You are now connected to the internet.';
                    $showSuccess = true;
                }
            }
        }
    } 
    elseif ($loginType === 'voucher') {
        $code = strtoupper(clean($_POST['voucher_code'] ?? ''));

        if (empty($code)) {
            $error = 'Please enter a voucher code.';
        } else {
            $db  = Database::getInstance();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? LIMIT 1");
            $stmt->execute([$code]);
            $voucher = $stmt->fetch();

            if (!$voucher) {
                $error = 'Invalid voucher code.';
            } elseif ($voucher['status'] !== 'available') {
                $error = 'This voucher has already been used or is disabled.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $guestUsername = 'guest_' . strtolower(substr($voucher['code'], -6));
                    $guestPassword = hashPassword(bin2hex(random_bytes(4)));
                    $uuid = generateUuid();

                    $stmt = $pdo->prepare("
                        INSERT INTO customers (uuid, username, password, full_name, status, created_at)
                        VALUES (?, ?, ?, ?, 'active', NOW())
                    ");
                    $stmt->execute([
                        $uuid,
                        $guestUsername,
                        $guestPassword,
                        'Guest User (' . $voucher['code'] . ')'
                    ]);
                    $customerId = $pdo->lastInsertId();

                    $duration = (int)$voucher['duration_value'];
                    $unit = $voucher['duration_unit'];

                    $interval = match($unit) {
                        'minutes' => "INTERVAL $duration MINUTE",
                        'hours'   => "INTERVAL $duration HOUR",
                        'days'    => "INTERVAL $duration DAY",
                        'months'  => "INTERVAL $duration MONTH",
                        default   => "INTERVAL 1 HOUR"
                    };

                    $stmt = $pdo->prepare("
                        INSERT INTO subscriptions 
                        (customer_id, package_id, voucher_id, start_at, end_at, data_quota, status)
                        VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), $interval), ?, 'active')
                    ");
                    $stmt->execute([
                        $customerId,
                        $voucher['package_id'],
                        $voucher['id'],
                        $voucher['data_quota']
                    ]);

                    $stmt = $pdo->prepare("
                        UPDATE vouchers 
                        SET status = 'used', used_by = ?, used_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$customerId, $voucher['id']]);

                    $pdo->commit();

                    $success = 'Voucher accepted! You are now connected for ' . $duration . ' ' . $unit . '.';
                    $showSuccess = true;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Something went wrong. Please try again.';
                }
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
    <title>WiFi Login - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 20px;
        }
        .portal-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .portal-header {
            background: #0f172a;
            color: #fff;
            text-align: center;
            padding: 2rem 1.5rem 1.5rem;
        }
        .portal-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .portal-body {
            padding: 2rem 1.5rem;
        }
        .nav-pills .nav-link {
            color: #64748b;
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            background: #3b82f6;
        }
        .btn-connect {
            background: #3b82f6;
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            width: 100%;
            border-radius: 10px;
        }
        .btn-connect:hover {
            background: #2563eb;
        }
        .success-box {
            text-align: center;
            padding: 1rem 0;
        }
        .success-icon {
            font-size: 4rem;
            color: #22c55e;
        }
    </style>
</head>
<body>

<div class="portal-card">
    <div class="portal-header">
        <h1>MAX WIFI</h1>
        <p class="mb-0 opacity-75">Connect to the Internet</p>
    </div>

    <div class="portal-body">

        <?php if ($showSuccess): ?>
            <!-- SUCCESS STATE -->
            <div class="success-box">
                <div class="success-icon mb-3">✓</div>
                <h4 class="text-success mb-2">Connected!</h4>
                <p class="text-muted"><?= clean($success) ?></p>
                <p class="small text-muted mt-3">You can now close this page and start browsing.</p>
            </div>

        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= clean($error) ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-pills nav-fill mb-4" id="loginTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="account-tab" data-bs-toggle="pill" data-bs-target="#account" type="button">Account</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="voucher-tab" data-bs-toggle="pill" data-bs-target="#voucher" type="button">Voucher</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Account Login -->
                <div class="tab-pane fade show active" id="account">
                    <form method="POST">
                        <input type="hidden" name="login_type" value="account">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control form-control-lg" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-connect">Connect</button>
                    </form>
                </div>

                <!-- Voucher Login -->
                <div class="tab-pane fade" id="voucher">
                    <form method="POST">
                        <input type="hidden" name="login_type" value="voucher">
                        <div class="mb-4">
                            <label class="form-label">Voucher Code</label>
                            <input type="text" name="voucher_code" class="form-control form-control-lg text-uppercase" 
                                   placeholder="e.g. MAX-A1B2C3D4" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-connect">Connect with Voucher</button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <small class="text-muted">Powered by MAX WIFI</small>
            </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>