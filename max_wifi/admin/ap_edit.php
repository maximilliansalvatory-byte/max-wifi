<?php
/**
 * =====================================================
 * MAX WIFI - Edit Access Point
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
    setFlash('error', 'Invalid Access Point.');
    redirect('access_points.php');
}

$stmt = $pdo->prepare("SELECT * FROM access_points WHERE id = ?");
$stmt->execute([$id]);
$ap = $stmt->fetch();

if (!$ap) {
    setFlash('error', 'Access Point not found.');
    redirect('access_points.php');
}

$routers   = $pdo->query("SELECT id, name FROM routers ORDER BY name")->fetchAll();
$locations = $pdo->query("SELECT id, name FROM locations WHERE status = 'active' ORDER BY name")->fetchAll();

$errors = [];
$form = [
    'name'        => $ap['name'],
    'mac_address' => $ap['mac_address'],
    'ip_address'  => $ap['ip_address'],
    'ssid'        => $ap['ssid'],
    'model'       => $ap['model'],
    'router_id'   => $ap['router_id'],
    'location_id' => $ap['location_id'],
    'status'      => $ap['status']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $form['name']        = clean($_POST['name'] ?? '');
        $form['mac_address'] = clean($_POST['mac_address'] ?? '');
        $form['ip_address']  = clean($_POST['ip_address'] ?? '');
        $form['ssid']        = clean($_POST['ssid'] ?? '');
        $form['model']       = clean($_POST['model'] ?? '');
        $form['router_id']   = (int)($_POST['router_id'] ?? 0);
        $form['location_id'] = (int)($_POST['location_id'] ?? 0);
        $form['status']      = clean($_POST['status'] ?? 'offline');

        if (empty($form['name'])) {
            $errors[] = 'Access Point name is required.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE access_points SET 
                    name = ?, mac_address = ?, ip_address = ?, ssid = ?, model = ?,
                    router_id = ?, location_id = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $form['name'],
                $form['mac_address'] ?: null,
                $form['ip_address'] ?: null,
                $form['ssid'] ?: null,
                $form['model'] ?: null,
                $form['router_id'] ?: null,
                $form['location_id'] ?: null,
                $form['status'],
                $id
            ]);

            setFlash('success', 'Access Point updated successfully.');
            redirect('access_points.php');
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
    <title>Edit Access Point - MAX WIFI</title>
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
    <a href="access_points.php" class="active"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0">Edit Access Point</h5>
            <small class="text-muted">Update Access Point details</small>
        </div>
        <a href="access_points.php" class="btn btn-outline-secondary btn-sm">
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
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= clean($form['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" class="form-control" value="<?= clean($form['mac_address'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-control" value="<?= clean($form['ip_address'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">SSID</label>
                    <input type="text" name="ssid" class="form-control" value="<?= clean($form['ssid'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-control" value="<?= clean($form['model'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Router</label>
                    <select name="router_id" class="form-select">
                        <option value="">-- Select Router --</option>
                        <?php foreach ($routers as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $form['router_id'] == $r['id'] ? 'selected' : '' ?>>
                                <?= clean($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= $l['id'] ?>" <?= $form['location_id'] == $l['id'] ? 'selected' : '' ?>>
                                <?= clean($l['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="online"  <?= $form['status'] === 'online' ? 'selected' : '' ?>>Online</option>
                        <option value="offline" <?= $form['status'] === 'offline' ? 'selected' : '' ?>>Offline</option>
                        <option value="warning" <?= $form['status'] === 'warning' ? 'selected' : '' ?>>Warning</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Access Point
                </button>
                <a href="access_points.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>