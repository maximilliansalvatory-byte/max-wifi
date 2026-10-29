<?php
/**
 * =====================================================
 * MAX WIFI - Routers List
 * Phase 10: MikroTik Integration
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->query("
    SELECT r.*, l.name AS location_name 
    FROM routers r
    LEFT JOIN locations l ON l.id = r.location_id
    ORDER BY r.id DESC
");
$routers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routers - MAX WIFI</title>
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

<div class="sidebar">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="sessions.php"><i class="bi bi-activity me-2"></i> Sessions</a>
    <a href="routers.php" class="active"><i class="bi bi-hdd-network me-2"></i> Routers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
      <a href="reports.php"><i class="bi bi-bar-chart me-2"></i> Report</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0">MikroTik Routers</h5>
            <small class="text-muted">Manage your routers</small>
        </div>
        <a href="router_add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Router
        </a>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
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
                        <th>Name</th>
                        <th>IP Address</th>
                        <th>API Port</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Last Seen</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($routers) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No routers added yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($routers as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= clean($r['name']) ?></strong></td>
                                <td><?= clean($r['ip_address']) ?></td>
                                <td><?= $r['api_port'] ?></td>
                                <td><?= clean($r['location_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $badge = match($r['status']) {
                                        'online'  => 'bg-success',
                                        'offline' => 'bg-danger',
                                        default   => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span>
                                </td>
                                <td><?= $r['last_seen'] ? date('d M H:i', strtotime($r['last_seen'])) : '-' ?></td>
                                <td>
                                    <a href="router_edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="router_action.php?action=delete&id=<?= $r['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this router?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
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