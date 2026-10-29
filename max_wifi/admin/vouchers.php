<?php
/**
 * =====================================================
 * MAX WIFI - Vouchers List (Improved + Print)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Filter
$statusFilter = $_GET['status'] ?? 'all';

$sql = "
    SELECT v.*, p.name AS package_name
    FROM vouchers v
    LEFT JOIN wifi_packages p ON p.id = v.package_id
";

if ($statusFilter === 'available') {
    $sql .= " WHERE v.status = 'available'";
} elseif ($statusFilter === 'used') {
    $sql .= " WHERE v.status = 'used'";
} elseif ($statusFilter === 'disabled') {
    $sql .= " WHERE v.status = 'disabled'";
}

$sql .= " ORDER BY v.id DESC LIMIT 200";

$vouchers = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vouchers - MAX WIFI</title>
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
        .code-badge {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php" class="active"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="sessions.php"><i class="bi bi-activity me-2"></i> Sessions</a>
    <a href="routers.php"><i class="bi bi-hdd-network me-2"></i> Routers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="reports.php"><i class="bi bi-bar-chart me-2"></i> Reports</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">

    <div class="topbar">
        <div>
            <h5 class="mb-0">Vouchers</h5>
            <small class="text-muted">Manage voucher codes</small>
        </div>
        <div>
            <a href="voucher_generate.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Generate Vouchers
            </a>
            <a href="voucher_print.php" class="btn btn-outline-secondary btn-sm ms-2">
                <i class="bi bi-printer"></i> Print Vouchers
            </a>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= clean($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="mb-3">
        <div class="btn-group">
            <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
            <a href="?status=available" class="btn btn-sm <?= $statusFilter === 'available' ? 'btn-success' : 'btn-outline-success' ?>">Available</a>
            <a href="?status=used" class="btn btn-sm <?= $statusFilter === 'used' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Used</a>
            <a href="?status=disabled" class="btn btn-sm <?= $statusFilter === 'disabled' ? 'btn-warning' : 'btn-outline-warning' ?>">Disabled</a>
        </div>
    </div>

    <div class="card-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($vouchers) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No vouchers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vouchers as $i => $v): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <span class="code-badge"><?= clean($v['code']) ?></span>
                                    <button class="btn btn-sm btn-outline-secondary ms-1" 
                                            onclick="copyCode('<?= clean($v['code']) ?>')" title="Copy">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </td>
                                <td><?= clean($v['package_name'] ?? '-') ?></td>
                                <td><?= number_format($v['price']) ?> TZS</td>
                                <td><?= $v['duration_value'] ?> <?= $v['duration_unit'] ?></td>
                                <td>
                                    <?php
                                    $badge = match($v['status']) {
                                        'available' => 'bg-success',
                                        'used'      => 'bg-secondary',
                                        'disabled'  => 'bg-warning text-dark',
                                        'expired'   => 'bg-danger',
                                        default     => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= ucfirst($v['status']) ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($v['created_at'])) ?></td>
                                <td>
                                    <?php if ($v['status'] === 'available'): ?>
                                        <a href="voucher_action.php?action=disable&id=<?= $v['id'] ?>" 
                                           class="btn btn-sm btn-outline-warning"
                                           onclick="return confirm('Disable this voucher?')" title="Disable">
                                            <i class="bi bi-slash-circle"></i>
                                        </a>
                                    <?php elseif ($v['status'] === 'disabled'): ?>
                                        <a href="voucher_action.php?action=enable&id=<?= $v['id'] ?>" 
                                           class="btn btn-sm btn-outline-success"
                                           onclick="return confirm('Enable this voucher?')" title="Enable">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="voucher_action.php?action=delete&id=<?= $v['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this voucher permanently?')" title="Delete">
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
<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('Voucher code copied: ' + code);
    }, function() {
        prompt('Copy this code:', code);
    });
}
</script>
</body>
</html>