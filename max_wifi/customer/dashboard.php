<?php
/**
 * =====================================================
 * MAX WIFI - Customer Dashboard (Improved)
 * With Buy Package + Redeem Voucher
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$db  = Database::getInstance();
$pdo = $db->getConnection();

$customerId = $_SESSION['customer_id'];

// Get customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Get active subscription
$stmt = $pdo->prepare("
    SELECT s.*, p.name AS package_name, p.download_speed, p.upload_speed
    FROM subscriptions s
    JOIN wifi_packages p ON p.id = s.package_id
    WHERE s.customer_id = ? AND s.status = 'active' AND s.end_at > NOW()
    ORDER BY s.end_at DESC
    LIMIT 1
");
$stmt->execute([$customerId]);
$subscription = $stmt->fetch();

$fullName = $customer['full_name'] ?: $customer['username'];
$error = '';
$success = '';

// Handle Redeem Voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_voucher'])) {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $code = strtoupper(clean($_POST['voucher_code'] ?? ''));

        if (empty($code)) {
            $error = 'Please enter a voucher code.';
        } else {
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

                    $duration = (int)$voucher['duration_value'];
                    $unit = $voucher['duration_unit'];

                    $interval = match($unit) {
                        'minutes' => "INTERVAL $duration MINUTE",
                        'hours'   => "INTERVAL $duration HOUR",
                        'days'    => "INTERVAL $duration DAY",
                        'months'  => "INTERVAL $duration MONTH",
                        default   => "INTERVAL 1 HOUR"
                    };

                    // Create subscription
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

                    // Mark voucher as used
                    $stmt = $pdo->prepare("
                        UPDATE vouchers 
                        SET status = 'used', used_by = ?, used_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$customerId, $voucher['id']]);

                    $pdo->commit();

                    setFlash('success', 'Voucher redeemed successfully! Your package is now active.');
                    header("Location: dashboard.php");
                    exit;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

// Calculate remaining time
$remainingText = 'No active package';
$progress = 0;

if ($subscription) {
    $now = time();
    $end = strtotime($subscription['end_at']);
    $start = strtotime($subscription['start_at']);

    $totalSeconds = max(1, $end - $start);
    $remainingSeconds = max(0, $end - $now);
    $progress = min(100, round((($totalSeconds - $remainingSeconds) / $totalSeconds) * 100));

    $hours = floor($remainingSeconds / 3600);
    $minutes = floor(($remainingSeconds % 3600) / 60);

    if ($hours >= 24) {
        $days = floor($hours / 24);
        $remainingText = $days . ' day(s) ' . ($hours % 24) . 'h left';
    } else {
        $remainingText = $hours . 'h ' . $minutes . 'm left';
    }
}

$csrfToken = generateCsrfToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar { background: #0f172a; }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            height: 100%;
        }
        .stat-card .icon {
            width: 48px; height: 48px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .progress { height: 10px; border-radius: 5px; }
        .action-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            height: 100%;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">MAX WIFI</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small">Hello, <?= clean($fullName) ?></span>
            <a href="buy.php" class="btn btn-primary btn-sm">Buy Package</a>
            <a href="payments.php" class="btn btn-outline-light btn-sm">My Payments</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <div class="mb-4">
        <h4 class="mb-1">My Dashboard</h4>
        <p class="text-muted mb-0">Welcome to your WiFi account</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= clean($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= clean($error) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Account Status</div>
                        <h5 class="mb-0 mt-1">
                            <?php if ($customer['status'] === 'active'): ?>
                                <span class="text-success">Active</span>
                            <?php else: ?>
                                <span class="text-danger"><?= ucfirst($customer['status']) ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-person-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Current Package</div>
                        <?php if ($subscription): ?>
                            <h5 class="mb-0 mt-1"><?= clean($subscription['package_name']) ?></h5>
                            <small class="text-muted"><?= $remainingText ?></small>
                        <?php else: ?>
                            <h5 class="mb-0 mt-1">None</h5>
                            <small class="text-muted">No active subscription</small>
                        <?php endif; ?>
                    </div>
                    <div class="icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-wifi"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Connected Devices</div>
                        <h5 class="mb-0 mt-1">0</h5>
                    </div>
                    <div class="icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-phone"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($subscription): ?>
    <!-- Active Package -->
    <div class="stat-card mb-4">
        <h5 class="mb-3">Active Package Details</h5>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><strong>Package:</strong> <?= clean($subscription['package_name']) ?></p>
                <p class="mb-2"><strong>Speed:</strong> ↓ <?= $subscription['download_speed'] ?> Mbps / ↑ <?= $subscription['upload_speed'] ?> Mbps</p>
                <p class="mb-2"><strong>Started:</strong> <?= date('d M Y H:i', strtotime($subscription['start_at'])) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Expires:</strong> <?= date('d M Y H:i', strtotime($subscription['end_at'])) ?></p>
                <p class="mb-2"><strong>Remaining:</strong> <?= $remainingText ?></p>
                <div class="progress mt-2">
                    <div class="progress-bar bg-primary" style="width: <?= $progress ?>%"></div>
                </div>
                <small class="text-muted"><?= $progress ?>% used</small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="action-card">
                <h5 class="mb-3"><i class="bi bi-cart me-2"></i> Buy Package</h5>
                <p class="text-muted">Purchase a new internet package using Mobile Money or Cash.</p>
                <a href="buy.php" class="btn btn-primary">
                    <i class="bi bi-credit-card"></i> Buy Now
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="action-card">
                <h5 class="mb-3"><i class="bi bi-ticket-perforated me-2"></i> Redeem Voucher</h5>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="redeem_voucher" value="1">
                    
                    <div class="input-group mb-3">
                        <input type="text" name="voucher_code" class="form-control text-uppercase" 
                               placeholder="Enter voucher code" required>
                        <button class="btn btn-success" type="submit">Redeem</button>
                    </div>
                </form>
                <small class="text-muted">Enter the voucher code you received to activate a package.</small>
            </div>
        </div>
    </div>

    <!-- Account Info -->
    <div class="stat-card">
        <h5 class="mb-3">Account Information</h5>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><strong>Username:</strong> <?= clean($customer['username']) ?></p>
                <p class="mb-2"><strong>Full Name:</strong> <?= clean($customer['full_name'] ?? '-') ?></p>
                <p class="mb-2"><strong>Phone:</strong> <?= clean($customer['phone'] ?? '-') ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Email:</strong> <?= clean($customer['email'] ?? '-') ?></p>
                <p class="mb-2"><strong>Last Login:</strong> 
                    <?= $customer['last_login_at'] ? date('d M Y H:i', strtotime($customer['last_login_at'])) : 'Never' ?>
                </p>
                <p class="mb-0"><strong>Member Since:</strong> <?= date('d M Y', strtotime($customer['created_at'])) ?></p>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>