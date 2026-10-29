<?php
/**
 * =====================================================
 * MAX WIFI - Edit Customer
 * Phase 3: Customer Management
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$auth = new Auth();
$auth->requirePermission('customers.edit');

$db  = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', 'Invalid customer.');
    redirect('customers.php');
}

// Fetch customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect('customers.php');
}

// Fetch locations
$locations = $pdo->query("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name")->fetchAll();

$errors = [];
$form = [
    'full_name'   => $customer['full_name'],
    'username'    => $customer['username'],
    'email'       => $customer['email'],
    'phone'       => $customer['phone'],
    'location_id' => $customer['location_id'],
    'status'      => $customer['status'],
    'password'    => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $form['full_name']   = clean($_POST['full_name'] ?? '');
        $form['username']    = clean($_POST['username'] ?? '');
        $form['email']       = clean($_POST['email'] ?? '');
        $form['phone']       = clean($_POST['phone'] ?? '');
        $form['location_id'] = (int)($_POST['location_id'] ?? 0);
        $form['status']      = clean($_POST['status'] ?? 'active');
        $form['password']    = $_POST['password'] ?? '';

        if (empty($form['full_name'])) {
            $errors[] = 'Full name is required.';
        }
        if (empty($form['username'])) {
            $errors[] = 'Username is required.';
        }

        // Check username uniqueness (exclude current customer)
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ? AND id != ? AND deleted_at IS NULL");
            $stmt->execute([$form['username'], $id]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already exists.';
            }
        }

        if (empty($errors)) {
            if (!empty($form['password'])) {
                // Update with new password
                $hashedPassword = hashPassword($form['password']);
                $stmt = $pdo->prepare("
                    UPDATE customers SET 
                        full_name = ?, username = ?, email = ?, phone = ?, 
                        location_id = ?, status = ?, password = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $form['full_name'],
                    $form['username'],
                    $form['email'] ?: null,
                    $form['phone'] ?: null,
                    $form['location_id'] ?: null,
                    $form['status'],
                    $hashedPassword,
                    $id
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("
                    UPDATE customers SET 
                        full_name = ?, username = ?, email = ?, phone = ?, 
                        location_id = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $form['full_name'],
                    $form['username'],
                    $form['email'] ?: null,
                    $form['phone'] ?: null,
                    $form['location_id'] ?: null,
                    $form['status'],
                    $id
                ]);
            }

            setFlash('success', 'Customer updated successfully.');
            redirect('customers.php');
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
    <title>Edit Customer - MAX WIFI</title>
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

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php" class="active"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="#"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="#"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="#"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="#"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="#"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">

    <div class="topbar">
        <div>
            <h5 class="mb-0">Edit Customer</h5>
            <small class="text-muted">Update customer information</small>
        </div>
        <a href="customers.php" class="btn btn-outline-secondary btn-sm">
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
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?= clean($form['full_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= clean($form['username']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= clean($form['email'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= clean($form['phone'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" minlength="6">
                    <small class="text-muted">Leave blank if you don't want to change password</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= $form['location_id'] == $loc['id'] ? 'selected' : '' ?>>
                                <?= clean($loc['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"    <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive"  <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= $form['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="expired"   <?= $form['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Customer
                </button>
                <a href="customers.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>