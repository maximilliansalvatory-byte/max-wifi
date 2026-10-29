<?php
/**
 * =====================================================
 * MAX WIFI - Customers List
 * Phase 3: Customer Management
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$auth = new Auth();
$auth->requirePermission('customers.view');

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Fetch all customers
$stmt = $pdo->query("
    SELECT c.*, l.name AS location_name 
    FROM customers c
    LEFT JOIN locations l ON l.id = c.location_id
    WHERE c.deleted_at IS NULL
    ORDER BY c.id DESC
");
$customers = $stmt->fetchAll();

$fullName = $_SESSION['full_name'] ?? 'Admin';
$username = $_SESSION['username'] ?? '';
$roles    = $_SESSION['roles'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - MAX WIFI</title>
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
        .badge-status { font-size: 0.75rem; padding: 0.35em 0.65em; }
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
    <a href="customers.php" class="active">
        <i class="bi bi-people me-2"></i> Customers
    </a>
    <a href="packages.php">
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
            <h5 class="mb-0">Customers</h5>
            <small class="text-muted">Manage WiFi customers</small>
        </div>
        <div>
            <?php if ($auth->hasPermission('customers.create')): ?>

                <a href="assign_package.php" class="btn btn-outline-primary btn-sm ms-2">
                    <i class="bi bi-gift"></i> Assign Package
                </a>
                
                <a href="customer_add.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Customer
                </a>
            <?php endif; ?>
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

    <div class="card-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($customers) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No customers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $index => $c): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= clean($c['full_name'] ?? '-') ?></td>
                                <td><?= clean($c['username']) ?></td>
                                <td><?= clean($c['phone'] ?? '-') ?></td>
                                <td><?= clean($c['location_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $statusClass = match($c['status']) {
                                        'active'    => 'bg-success',
                                        'suspended' => 'bg-warning text-dark',
                                        'inactive'  => 'bg-secondary',
                                        'expired'   => 'bg-danger',
                                        default     => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge badge-status <?= $statusClass ?>">
                                        <?= ucfirst($c['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($auth->hasPermission('customers.edit')): ?>
                                            <a href="customer_edit.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($auth->hasPermission('customers.suspend')): ?>
                                            <?php if ($c['status'] === 'active'): ?>
                                                <a href="customer_action.php?action=suspend&id=<?= $c['id'] ?>" 
                                                   class="btn btn-outline-warning"
                                                   onclick="return confirm('Suspend this customer?')" title="Suspend">
                                                    <i class="bi bi-pause-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="customer_action.php?action=activate&id=<?= $c['id'] ?>" 
                                                   class="btn btn-outline-success"
                                                   onclick="return confirm('Activate this customer?')" title="Activate">
                                                    <i class="bi bi-play-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($auth->hasPermission('customers.delete')): ?>
                                            <a href="customer_action.php?action=delete&id=<?= $c['id'] ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this customer?')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>