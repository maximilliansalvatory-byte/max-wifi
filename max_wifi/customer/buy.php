<?php
/**
 * =====================================================
 * MAX WIFI - Customer Buy Package
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments/PaymentService.php';

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

// Get active packages
$packages = $pdo->query("SELECT * FROM wifi_packages WHERE status = 'active' ORDER BY price ASC")->fetchAll();

// Get payment methods
$methods = $pdo->query("SELECT * FROM payment_methods WHERE status = 'active' ORDER BY name")->fetchAll();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $packageId  = (int)($_POST['package_id'] ?? 0);
        $methodSlug = clean($_POST['payment_method'] ?? '');
        $phone      = clean($_POST['phone'] ?? $customer['phone'] ?? '');

        if ($packageId <= 0) {
            $errors[] = 'Please select a package.';
        }
        if (empty($methodSlug)) {
            $errors[] = 'Please select a payment method.';
        }

        // Get package
        $stmt = $pdo->prepare("SELECT * FROM wifi_packages WHERE id = ? AND status = 'active'");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();

        if (!$package) {
            $errors[] = 'Selected package not found.';
        }

        if (empty($errors)) {
            $paymentService = new PaymentService($pdo);

            $result = $paymentService->initiate([
                'provider'    => $methodSlug,
                'amount'      => $package['price'],
                'phone'       => $phone,
                'customer_id' => $customerId,
                'package_id'  => $packageId,
                'reference'   => 'CUST-' . $customerId . '-' . time()
            ]);

            if ($result['success']) {
                if (($result['status'] ?? '') === 'success') {
                    // Cash → create subscription immediately
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

                    setFlash('success', 'Payment successful! Your package is now active.');
                    header("Location: dashboard.php");
                    exit;
                } else {
                    setFlash('success', $result['message'] . ' Transaction: ' . ($result['transaction_id'] ?? ''));
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $errors[] = $result['message'] ?? 'Payment failed.';
            }
        }
    }
}

$csrfToken = generateCsrfToken();
$fullName = $customer['full_name'] ?: $customer['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Package - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar { background: #0f172a; }
        .package-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            height: 100%;
            transition: all 0.2s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .package-card:hover, .package-card.selected {
            border-color: #3b82f6;
            transform: translateY(-3px);
        }
        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">MAX WIFI</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small">Hello, <?= clean($fullName) ?></span>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <div class="mb-4">
        <h4 class="mb-1">Buy Package</h4>
        <p class="text-muted mb-0">Choose a package and payment method</p>
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

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <h5 class="mb-3">1. Select Package</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($packages as $p): ?>
                <div class="col-md-4">
                    <label class="package-card d-block">
                        <input type="radio" name="package_id" value="<?= $p['id'] ?>" class="d-none" required>
                        <h5><?= clean($p['name']) ?></h5>
                        <h3 class="text-primary"><?= number_format($p['price']) ?> TZS</h3>
                        <p class="mb-1 text-muted">
                            <?= $p['duration_value'] ?> <?= $p['duration_unit'] ?>
                        </p>
                        <p class="mb-0 small">
                            ↓ <?= $p['download_speed'] ?> Mbps / ↑ <?= $p['upload_speed'] ?> Mbps
                        </p>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-card">
            <h5 class="mb-3">2. Payment Details</h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">-- Select Method --</option>
                        <?php foreach ($methods as $m): ?>
                            <option value="<?= clean($m['slug']) ?>"><?= clean($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= clean($customer['phone'] ?? '') ?>" placeholder="07XXXXXXXX">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-credit-card"></i> Proceed to Payment
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>

</div>

<script>
document.querySelectorAll('.package-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});
</script>

</body>
</html>