<?php
/**
 * =====================================================
 * MAX WIFI - Record Payment (Using Payment Service)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments/PaymentService.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

$customers = $pdo->query("SELECT id, full_name, username FROM customers WHERE deleted_at IS NULL AND status = 'active' ORDER BY full_name")->fetchAll();
$packages  = $pdo->query("SELECT id, name, price FROM wifi_packages WHERE status = 'active' ORDER BY name")->fetchAll();
$methods   = $pdo->query("SELECT id, name, slug FROM payment_methods WHERE status = 'active' ORDER BY name")->fetchAll();

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $packageId  = (int)($_POST['package_id'] ?? 0);
        $methodSlug = clean($_POST['payment_method'] ?? '');
        $amount     = (float)($_POST['amount'] ?? 0);
        $phone      = clean($_POST['phone'] ?? '');
        $reference  = clean($_POST['reference'] ?? '');

        if ($packageId <= 0)   $errors[] = 'Please select a package.';
        if (empty($methodSlug)) $errors[] = 'Please select a payment method.';
        if ($amount <= 0)      $errors[] = 'Amount must be greater than 0.';

        if (empty($errors)) {
            $paymentService = new PaymentService($pdo);

            $result = $paymentService->initiate([
                'provider'    => $methodSlug,
                'amount'      => $amount,
                'phone'       => $phone,
                'customer_id' => $customerId ?: null,
                'package_id'  => $packageId,
                'reference'   => $reference ?: null
            ]);

            if ($result['success']) {
                // If payment is successful (especially Cash), create subscription
                if (($result['status'] ?? '') === 'success' && $customerId > 0) {
                    $pkg = $pdo->prepare("SELECT * FROM wifi_packages WHERE id = ?");
                    $pkg->execute([$packageId]);
                    $package = $pkg->fetch();

                    if ($package) {
                        $duration = (int)$package['duration_value'];
                        $unit = $package['duration_unit'];

                        $interval = match($unit) {
                            'minutes' => "INTERVAL $duration MINUTE",
                            'hours'   => "INTERVAL $duration HOUR",
                            'days'    => "INTERVAL $duration DAY",
                            'months'  => "INTERVAL $duration MONTH",
                            default   => "INTERVAL 1 HOUR"
                        };

                        $sub = $pdo->prepare("
                            INSERT INTO subscriptions 
                            (customer_id, package_id, start_at, end_at, data_quota, status)
                            VALUES (?, ?, NOW(), DATE_ADD(NOW(), $interval), ?, 'active')
                        ");
                        $sub->execute([
                            $customerId,
                            $packageId,
                            $package['data_quota']
                        ]);
                    }
                }

                setFlash('success', $result['message'] . ' | Transaction: ' . ($result['transaction_id'] ?? 'N/A'));
                redirect('payments.php');
            } else {
                $errors[] = $result['message'] ?? 'Payment failed.';
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
    <title>Record Payment - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar {
            width: 260px; min-height: 100vh; background: #0f172a; color: #fff;
            position: fixed; left: 0; top: 0; padding-top: 1.5rem; z-index: 100;
        }
        .sidebar .brand {
            font-size: 1.4rem; font-weight: 700; padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid #1e293b; margin-bottom: 1rem;
        }
        .sidebar a {
            display: block; color: #94a3b8; text-decoration: none;
            padding: 0.75rem 1.5rem; transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active { background: #1e293b; color: #fff; }
        .main-content { margin-left: 260px; padding: 2rem; }
        .topbar {
            background: #fff; border-radius: 12px; padding: 1rem 1.5rem;
            margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex; justify-content: space-between; align-items: center;
        }
        .form-card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 2rem; max-width: 700px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="sessions.php"><i class="bi bi-activity me-2"></i> Sessions</a>
    <a href="routers.php"><i class="bi bi-hdd-network me-2"></i> Routers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php" class="active"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0">Record Payment</h5>
            <small class="text-muted">Using Payment Service Layer</small>
        </div>
        <a href="payments.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= clean($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Customer (optional)</label>
                    <select name="customer_id" class="form-select">
                        <option value="">-- Guest / No customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= clean($c['full_name'] ?: $c['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Package <span class="text-danger">*</span></label>
                    <select name="package_id" class="form-select" required>
                        <option value="">-- Select Package --</option>
                        <?php foreach ($packages as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= clean($p['name']) ?> (<?= number_format($p['price']) ?> TZS)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">-- Select Method --</option>
                        <?php foreach ($methods as $m): ?>
                            <option value="<?= clean($m['slug']) ?>"><?= clean($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Reference (optional)</label>
                    <input type="text" name="reference" class="form-control">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Process Payment
                </button>
                <a href="payments.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>