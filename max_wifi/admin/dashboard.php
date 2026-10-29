<?php
/**
 * =====================================================
 * MAX WIFI - Admin Dashboard (Improved)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// ===================== REAL STATISTICS =====================

$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL")->fetchColumn();
$activeCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();
$totalPackages = (int)$pdo->query("SELECT COUNT(*) FROM wifi_packages")->fetchColumn();
$activePackages = (int)$pdo->query("SELECT COUNT(*) FROM wifi_packages WHERE status = 'active'")->fetchColumn();
$totalVouchers = (int)$pdo->query("SELECT COUNT(*) FROM vouchers")->fetchColumn();
$availableVouchers = (int)$pdo->query("SELECT COUNT(*) FROM vouchers WHERE status = 'available'")->fetchColumn();
$usedVouchers = (int)$pdo->query("SELECT COUNT(*) FROM vouchers WHERE status = 'used'")->fetchColumn();
$totalPayments = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success' AND DATE(created_at) = CURDATE()")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success'")->fetchColumn();
$activeSessions = (int)$pdo->query("SELECT COUNT(*) FROM sessions WHERE status = 'active'")->fetchColumn();
$totalRouters = (int)$pdo->query("SELECT COUNT(*) FROM routers")->fetchColumn();
$totalAPs = (int)$pdo->query("SELECT COUNT(*) FROM access_points")->fetchColumn();
$onlineAPs = (int)$pdo->query("SELECT COUNT(*) FROM access_points WHERE status = 'online'")->fetchColumn();

$fullName = $_SESSION['full_name'] ?? 'Admin';
$username = $_SESSION['username'] ?? '';
$roles    = $_SESSION['roles'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MAX WIFI</title>
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
        .stat-card {
            background: #fff; border-radius: 12px; padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08); height: 100%;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .icon {
            width: 48px; height: 48px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="sessions.php"><i class="bi bi-activity me-2"></i> Sessions</a>
    <a href="routers.php"><i class="bi bi-hdd-network me-2"></i> Routers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="reports.php"><i class="bi bi-bar-chart me-2"></i> Report</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
   
</div>

<div class="main-content">

    <div class="topbar">
        <div>
            <h5 class="mb-0">Dashboard</h5>
            <small class="text-muted">Welcome back, <?= clean($fullName) ?></small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary"><?= clean(implode(', ', $roles)) ?></span>
            <span class="text-muted"><?= clean($username) ?></span>
        </div>
           <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= clean($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Total Customers</div>
                        <h3 class="mb-0 mt-1"><?= number_format($totalCustomers) ?></h3>
                        <small class="text-success"><?= $activeCustomers ?> active</small>
                    </div>
                    <div class="icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Today Revenue</div>
                        <h3 class="mb-0 mt-1"><?= number_format($todayRevenue) ?></h3>
                        <small class="text-muted">TZS</small>
                    </div>
                    <div class="icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Active Sessions</div>
                        <h3 class="mb-0 mt-1"><?= number_format($activeSessions) ?></h3>
                    </div>
                    <div class="icon bg-info bg-opacity-10 text-info"><i class="bi bi-activity"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Available Vouchers</div>
                        <h3 class="mb-0 mt-1"><?= number_format($availableVouchers) ?></h3>
                        <small class="text-muted"><?= $usedVouchers ?> used</small>
                    </div>
                    <div class="icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-ticket-perforated"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2 -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Total Packages</div>
                        <h3 class="mb-0 mt-1"><?= number_format($totalPackages) ?></h3>
                        <small class="text-success"><?= $activePackages ?> active</small>
                    </div>
                    <div class="icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-wifi"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Total Revenue</div>
                        <h3 class="mb-0 mt-1"><?= number_format($totalRevenue) ?></h3>
                        <small class="text-muted">TZS</small>
                    </div>
                    <div class="icon bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Routers</div>
                        <h3 class="mb-0 mt-1"><?= number_format($totalRouters) ?></h3>
                    </div>
                    <div class="icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-hdd-network"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="text-muted small">Access Points</div>
                        <h3 class="mb-0 mt-1"><?= number_format($totalAPs) ?></h3>
                        <small class="text-success"><?= $onlineAPs ?> online</small>
                    </div>
                    <div class="icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-router"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <h5 class="mb-2">System Status</h5>
        <p class="text-muted mb-0">
            MAX WIFI system is running. All core modules (Customers, Packages, Vouchers, Payments, Sessions, Routers, Access Points) are active.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>