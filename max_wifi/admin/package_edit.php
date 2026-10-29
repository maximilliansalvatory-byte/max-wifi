<?php
/**
 * =====================================================
 * MAX WIFI - Edit WiFi Package
 * Phase 4: WiFi Packages
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', 'Invalid package.');
    redirect('packages.php');
}

$stmt = $pdo->prepare("SELECT * FROM wifi_packages WHERE id = ?");
$stmt->execute([$id]);
$package = $stmt->fetch();

if (!$package) {
    setFlash('error', 'Package not found.');
    redirect('packages.php');
}

$errors = [];
$form = [
    'name'            => $package['name'],
    'description'     => $package['description'],
    'price'           => $package['price'],
    'duration_value'  => $package['duration_value'],
    'duration_unit'   => $package['duration_unit'],
    'download_speed'  => $package['download_speed'],
    'upload_speed'    => $package['upload_speed'],
    'data_quota'      => $package['data_quota'],
    'max_devices'     => $package['max_devices'],
    'status'          => $package['status']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $form['name']           = clean($_POST['name'] ?? '');
        $form['description']    = clean($_POST['description'] ?? '');
        $form['price']          = (float)($_POST['price'] ?? 0);
        $form['duration_value'] = (int)($_POST['duration_value'] ?? 1);
        $form['duration_unit']  = clean($_POST['duration_unit'] ?? 'hours');
        $form['download_speed'] = (int)($_POST['download_speed'] ?? 5);
        $form['upload_speed']   = (int)($_POST['upload_speed'] ?? 2);
        $form['data_quota']     = $_POST['data_quota'] !== '' ? (int)$_POST['data_quota'] : null;
        $form['max_devices']    = (int)($_POST['max_devices'] ?? 1);
        $form['status']         = clean($_POST['status'] ?? 'active');

        if (empty($form['name'])) {
            $errors[] = 'Package name is required.';
        }
        if ($form['price'] < 0) {
            $errors[] = 'Price cannot be negative.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE wifi_packages SET 
                    name = ?, description = ?, price = ?, duration_value = ?, duration_unit = ?,
                    download_speed = ?, upload_speed = ?, data_quota = ?, max_devices = ?, status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $form['name'],
                $form['description'] ?: null,
                $form['price'],
                $form['duration_value'],
                $form['duration_unit'],
                $form['download_speed'],
                $form['upload_speed'],
                $form['data_quota'],
                $form['max_devices'],
                $form['status'],
                $id
            ]);

            setFlash('success', 'Package updated successfully.');
            redirect('packages.php');
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
    <title>Edit Package - MAX WIFI</title>
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
            padding: 2rem; max-width: 800px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">MAX WIFI</div>

    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php" class="active"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
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
            <h5 class="mb-0">Edit Package</h5>
            <small class="text-muted">Update package details</small>
        </div>
        <a href="packages.php" class="btn btn-outline-secondary btn-sm">
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

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Package Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= clean($form['name']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Price (TZS) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= $form['price'] ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= clean($form['description'] ?? '') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Duration Value</label>
                    <input type="number" name="duration_value" class="form-control" min="1" value="<?= $form['duration_value'] ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Duration Unit</label>
                    <select name="duration_unit" class="form-select">
                        <option value="minutes" <?= $form['duration_unit'] === 'minutes' ? 'selected' : '' ?>>Minutes</option>
                        <option value="hours"   <?= $form['duration_unit'] === 'hours' ? 'selected' : '' ?>>Hours</option>
                        <option value="days"    <?= $form['duration_unit'] === 'days' ? 'selected' : '' ?>>Days</option>
                        <option value="months"  <?= $form['duration_unit'] === 'months' ? 'selected' : '' ?>>Months</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Max Devices</label>
                    <input type="number" name="max_devices" class="form-control" min="1" value="<?= $form['max_devices'] ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Download Speed (Mbps)</label>
                    <input type="number" name="download_speed" class="form-control" min="1" value="<?= $form['download_speed'] ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Upload Speed (Mbps)</label>
                    <input type="number" name="upload_speed" class="form-control" min="1" value="<?= $form['upload_speed'] ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Data Quota (MB)</label>
                    <input type="number" name="data_quota" class="form-control" min="0" value="<?= $form['data_quota'] ?>" placeholder="Leave empty for Unlimited">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Package
                </button>
                <a href="packages.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>