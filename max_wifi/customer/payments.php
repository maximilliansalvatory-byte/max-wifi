<?php
/**
 * =====================================================
 * MAX WIFI - Customer Payment History
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$db  = Database::getInstance();
$pdo = $db->getConnection();

$customerId = $_SESSION['customer_id'];

// Get customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$fullName = $customer['full_name'] ?: $customer['username'];

// Get customer payments
$stmt = $pdo->prepare("
    SELECT p.*, pk.name AS package_name, pm.name AS method_name
    FROM payments p
    LEFT JOIN wifi_packages pk ON pk.id = p.package_id
    LEFT JOIN payment_methods pm ON pm.id = p.payment_method_id
    WHERE p.customer_id = ?
    ORDER BY p.id DESC
    LIMIT 50
");
$stmt->execute([$customerId]);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .navbar { background: #0f172a; }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">MAX WIFI</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white-50 small">Hello, <?= clean($fullName) ?></span>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
            <a href="buy.php" class="btn btn-primary btn-sm">Buy Package</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <div class="mb-4">
        <h4 class="mb-1">My Payment History</h4>
        <p class="text-muted mb-0">All your payment transactions</p>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Package</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No payments found yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= clean($p['package_name'] ?? '-') ?></td>
                                <td><?= number_format($p['amount']) ?> <?= $p['currency'] ?></td>
                                <td><?= clean($p['method_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $badge = match($p['status']) {
                                        'success'   => 'bg-success',
                                        'pending'   => 'bg-warning text-dark',
                                        'failed'    => 'bg-danger',
                                        default     => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= ucfirst($p['status']) ?></span>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>