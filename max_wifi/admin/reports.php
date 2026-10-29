<?php
/**
 * =====================================================
 * MAX WIFI - Reports (with Graphs + Print)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// ===================== STATISTICS =====================
$totalCustomers    = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL")->fetchColumn();
$activeCustomers   = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();
$totalPackages     = (int)$pdo->query("SELECT COUNT(*) FROM wifi_packages")->fetchColumn();
$availableVouchers = (int)$pdo->query("SELECT COUNT(*) FROM vouchers WHERE status = 'available'")->fetchColumn();
$usedVouchers      = (int)$pdo->query("SELECT COUNT(*) FROM vouchers WHERE status = 'used'")->fetchColumn();
$todayRevenue      = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success' AND DATE(created_at) = CURDATE()")->fetchColumn();
$monthRevenue      = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$totalRevenue      = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success'")->fetchColumn();
$pendingPayments   = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
$activeSessions    = (int)$pdo->query("SELECT COUNT(*) FROM sessions WHERE status = 'active'")->fetchColumn();

// Last 7 days revenue for chart
$last7Days = $pdo->query("
    SELECT DATE(created_at) as day, COALESCE(SUM(amount),0) as total
    FROM payments 
    WHERE status = 'success' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d M', strtotime($day));
    $chartData[] = isset($last7Days[$day]) ? (float)$last7Days[$day] : 0;
}

// Payment methods distribution
$methodStats = $pdo->query("
    SELECT pm.name, COUNT(p.id) as total
    FROM payment_methods pm
    LEFT JOIN payments p ON p.payment_method_id = pm.id AND p.status = 'success'
    GROUP BY pm.id
")->fetchAll();

// Recent payments
$recentPayments = $pdo->query("
    SELECT p.amount, p.created_at, c.full_name, c.username, pk.name AS package_name
    FROM payments p
    LEFT JOIN customers c ON c.id = p.customer_id
    LEFT JOIN wifi_packages pk ON pk.id = p.package_id
    WHERE p.status = 'success'
    ORDER BY p.id DESC
    LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
        }
        .card-table {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 1.5rem;
        }
        @media print {
            .sidebar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

<div class="sidebar no-print">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="sessions.php"><i class="bi bi-activity me-2"></i> Sessions</a>
    <a href="routers.php"><i class="bi bi-hdd-network me-2"></i> Routers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
    <a href="reports.php" class="active"><i class="bi bi-bar-chart me-2"></i> Reports</a>
    <a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">

    <div class="topbar">
        <div>
            <h5 class="mb-0">Reports & Analytics</h5>
            <small class="text-muted">Business overview</small>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Revenue Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Today Revenue</div>
                <h3 class="mb-0 mt-1 text-success"><?= number_format($todayRevenue) ?> <small class="fs-6">TZS</small></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">This Month</div>
                <h3 class="mb-0 mt-1"><?= number_format($monthRevenue) ?> <small class="fs-6">TZS</small></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Total Revenue</div>
                <h3 class="mb-0 mt-1"><?= number_format($totalRevenue) ?> <small class="fs-6">TZS</small></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Pending Payments</div>
                <h3 class="mb-0 mt-1 text-warning"><?= number_format($pendingPayments) ?></h3>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card-table">
                <h6 class="mb-3">Revenue Last 7 Days</h6>
                <canvas id="revenueChart" height="120"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-table">
                <h6 class="mb-3">Payment Methods</h6>
                <canvas id="methodsChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Other Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Total Customers</div>
                <h3 class="mb-0 mt-1"><?= number_format($totalCustomers) ?></h3>
                <small class="text-success"><?= $activeCustomers ?> active</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Available Vouchers</div>
                <h3 class="mb-0 mt-1"><?= number_format($availableVouchers) ?></h3>
                <small class="text-muted"><?= $usedVouchers ?> used</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Active Sessions</div>
                <h3 class="mb-0 mt-1"><?= number_format($activeSessions) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small">Total Packages</div>
                <h3 class="mb-0 mt-1"><?= number_format($totalPackages) ?></h3>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card-table">
        <h5 class="mb-3">Recent Successful Payments</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentPayments) === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No successful payments yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td><?= clean($p['full_name'] ?? $p['username'] ?? 'Guest') ?></td>
                                <td><?= clean($p['package_name'] ?? '-') ?></td>
                                <td><?= number_format($p['amount']) ?> TZS</td>
                                <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// Revenue Chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Revenue (TZS)',
            data: <?= json_encode($chartData) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Methods Chart
new Chart(document.getElementById('methodsChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($methodStats, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($methodStats, 'total')) ?>,
            backgroundColor: ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#64748b']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

</body>
</html>