<?php
/**
 * =====================================================
 * MAX WIFI - Generate Vouchers
 * Phase 5: Voucher System
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Fetch active packages
$packages = $pdo->query("SELECT id, name, price, duration_value, duration_unit, data_quota, max_devices 
                         FROM wifi_packages WHERE status = 'active' ORDER BY name")->fetchAll();

$errors = [];
$successCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $packageId = (int)($_POST['package_id'] ?? 0);
        $quantity  = (int)($_POST['quantity'] ?? 1);
        $prefix    = strtoupper(clean($_POST['prefix'] ?? 'MAX'));

        if ($packageId <= 0) {
            $errors[] = 'Please select a package.';
        }
        if ($quantity < 1 || $quantity > 100) {
            $errors[] = 'Quantity must be between 1 and 100.';
        }

        // Get package details
        $stmt = $pdo->prepare("SELECT * FROM wifi_packages WHERE id = ? AND status = 'active'");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();

        if (!$package) {
            $errors[] = 'Selected package not found or inactive.';
        }

        if (empty($errors)) {
            $createdBy = currentUserId();

            for ($i = 0; $i < $quantity; $i++) {
                // Generate secure random code
                $random = strtoupper(bin2hex(random_bytes(4))); // 8 characters
                $code = $prefix . '-' . $random;

                // Make sure code is unique
                $check = $pdo->prepare("SELECT id FROM vouchers WHERE code = ?");
                $check->execute([$code]);
                if ($check->fetch()) {
                    $i--; // retry
                    continue;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO vouchers 
                    (code, package_id, created_by, price, duration_value, duration_unit, 
                     data_quota, max_devices, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())
                ");

                $stmt->execute([
                    $code,
                    $package['id'],
                    $createdBy,
                    $package['price'],
                    $package['duration_value'],
                    $package['duration_unit'],
                    $package['data_quota'],
                    $package['max_devices']
                ]);

                $successCount++;
            }

            setFlash('success', $successCount . ' voucher(s) generated successfully.');
            redirect('vouchers.php');
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
    <title>Generate Vouchers - MAX WIFI</title>
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
            padding: 2rem; max-width: 600px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">MAX WIFI</div>

    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php" class="active"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>

    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <div class="topbar">
        <div>
            <h5 class="mb-0">Generate Vouchers</h5>
            <small class="text-muted">Create single or bulk vouchers</small>
        </div>
        <a href="vouchers.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= clean($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="mb-3">
                <label class="form-label">Select Package <span class="text-danger">*</span></label>
                <select name="package_id" class="form-select" required>
                    <option value="">-- Choose Package --</option>
                    <?php foreach ($packages as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= clean($p['name']) ?> — <?= number_format($p['price']) ?> TZS 
                            (<?= $p['duration_value'] ?> <?= $p['duration_unit'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                <input type="number" name="quantity" class="form-control" min="1" max="100" value="1" required>
                <small class="text-muted">Maximum 100 vouchers at once</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Code Prefix</label>
                <input type="text" name="prefix" class="form-control" value="MAX" maxlength="6">
                <small class="text-muted">Example: MAX-A1B2C3D4</small>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-ticket-perforated"></i> Generate Vouchers
                </button>
                <a href="vouchers.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>