<?php
/**
 * =====================================================
 * MAX WIFI - Active Sessions
 * Phase 9: Session Management
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Fetch active sessions
$stmt = $pdo->query("
    SELECT s.*, c.full_name, c.username AS customer_username
    FROM sessions s
    LEFT JOIN customers c ON c.id = s.customer_id
    WHERE s.status = 'active'
    ORDER BY s.start_time DESC
");
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sessions - MAX WIFI</title>
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
    <a href="packages.php">
        <i class="bi bi-wifi me-2"></i> Packages
    </a>
    <a href="vouchers.php">
        <i class="bi bi-ticket-perforated me-2"></i> Vouchers
    </a>
    <a href="sessions.php" class="active">
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
            <h5 class="mb-0">Active Sessions</h5>
            <small class="text-muted">Currently online users</small>
        </div>
        <div>
            <span class="badge bg-success fs-6"><?= count($sessions) ?> Online</span>
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
                        <th>User</th>
                        <th>IP Address</th>
                        <th>MAC Address</th>
                        <th>Device</th>
                        <th>Started</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sessions) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No active sessions at the moment
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $index => $s): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <?= clean($s['full_name'] ?? $s['customer_username'] ?? $s['username']) ?>
                                </td>
                                <td><?= clean($s['ip_address'] ?? '-') ?></td>
                                <td><code><?= clean($s['mac_address'] ?? '-') ?></code></td>
                                <td><?= clean($s['device_name'] ?? '-') ?></td>
                                <td><?= date('d M H:i', strtotime($s['start_time'])) ?></td>
                                <td>
                                    <?php
                                    $seconds = time() - strtotime($s['start_time']);
                                    $hours = floor($seconds / 3600);
                                    $mins  = floor(($seconds % 3600) / 60);
                                    echo $hours . 'h ' . $mins . 'm';
                                    ?>
                                </td>
                                <td>
                                    <a href="session_action.php?action=disconnect&id=<?= $s['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Disconnect this user?')">
                                        <i class="bi bi-x-circle"></i> Disconnect
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <strong>Note:</strong> Real session data will appear here when MikroTik / RADIUS integration is connected (Phase 10).
        For now this page is ready and waiting for live data.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>