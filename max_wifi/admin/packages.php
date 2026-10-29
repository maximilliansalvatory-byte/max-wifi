<?php
/**
 * =====================================================
 * MAX WIFI - WiFi Packages List
 * Phase 4: WiFi Packages
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$auth = new Auth();
// We will use a temporary permission check (we will add proper package permissions later)
if (!isSuperAdmin() && !$auth->hasPermission('dashboard.view')) {
    setFlash('error', 'Access denied.');
    redirect('dashboard.php');
}

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Check if packages table exists, if not we will create it in the next step
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM wifi_packages LIMIT 1");
    $tableExists = true;
} catch (Exception $e) {
    $tableExists = false;
}

$packages = [];
if ($tableExists) {
    $stmt = $pdo->query("SELECT * FROM wifi_packages ORDER BY id DESC");
    $packages = $stmt->fetchAll();
}

$fullName = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages - MAX WIFI</title>
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
        .card-table {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 1.5rem;
        }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: #64748b; }
    </style>
</head>
<body>

<!-- Sidebar -->
<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">MAX WIFI</div>

    <a href="dashboard.php">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>
    <a href="customers.php">
        <i class="bi bi-people me-2"></i> Customers
    </a>
    <a href="packages.php" class="active">
        <i class="bi bi-wifi me-2"></i> Packages
    </a>
    <a href="vouchers.php">
        <i class="bi bi-ticket-perforated me-2"></i> Vouchers
    </a>
    <a href="sessions.php">
        <i class="bi bi-activity me-2"></i> Sessions
    </a>
    <a href="routers.php">
        <i class="bi bi-hdd-network me-2"></i> Routers
    </a>
    <a href="access_points.php">
        <i class="bi bi-router me-2"></i> Access Points
    </a>
    <a href="payments.php">
        <i class="bi bi-cash-coin me-2"></i> Payments
    </a>
    <a href="reports.php"><i class="bi bi-bar-chart me-2"></i> Report</a>
    <a href="settings.php">
        <i class="bi bi-gear me-2"></i> Settings
    </a>

    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>
<!-- Main Content -->
<div class="main-content">

    <div class="topbar">
        <div>
            <h5 class="mb-0">WiFi Packages</h5>
            <small class="text-muted">Manage internet packages</small>
        </div>
        <div>
            <a href="package_add.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Add Package
            </a>
        </div>
    </div>

    <?php 
    $flash = getFlash();
    if ($flash): 
    ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= clean($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$tableExists): ?>
        <div class="alert alert-warning">
            <strong>Notice:</strong> The packages table does not exist yet.<br>
            Please run the SQL I will give you in the next step to create it.
        </div>
    <?php else: ?>

    <div class="card-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Package Name</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Speed</th>
                        <th>Data Quota</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($packages) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No packages found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($packages as $index => $p): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= clean($p['name']) ?></strong><br>
                                    <small class="text-muted"><?= clean($p['description'] ?? '') ?></small>
                                </td>
                                <td><?= number_format($p['price']) ?> TZS</td>
                                <td><?= $p['duration_value'] ?> <?= $p['duration_unit'] ?></td>
                                <td>
                                    ↓ <?= $p['download_speed'] ?> Mbps<br>
                                    ↑ <?= $p['upload_speed'] ?> Mbps
                                </td>
                                <td>
                                    <?= $p['data_quota'] ? number_format($p['data_quota']) . ' MB' : 'Unlimited' ?>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="package_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="package_action.php?action=toggle&id=<?= $p['id'] ?>" 
                                           class="btn btn-outline-warning" title="Enable/Disable">
                                            <i class="bi bi-toggle-on"></i>
                                        </a>
                                        <a href="package_action.php?action=delete&id=<?= $p['id'] ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Delete this package?')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>