<?php
/**
 * =====================================================
 * MAX WIFI - Print Vouchers
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Get available vouchers (or filtered)
$status = $_GET['status'] ?? 'available';

$stmt = $pdo->prepare("
    SELECT v.*, p.name AS package_name, p.duration_value, p.duration_unit
    FROM vouchers v
    LEFT JOIN wifi_packages p ON p.id = v.package_id
    WHERE v.status = ?
    ORDER BY v.id DESC
    LIMIT 100
");
$stmt->execute([$status]);
$vouchers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f1f5f9;
        }
        .voucher-card {
            background: #fff;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1.2rem;
            text-align: center;
            height: 100%;
            page-break-inside: avoid;
        }
        .voucher-code {
            font-family: monospace;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-block;
            margin: 8px 0;
        }
        .no-print {
            margin-bottom: 1.5rem;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .voucher-card {
                border: 1px solid #333;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="container py-4">

    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Print Vouchers</h4>
            <small class="text-muted">Available vouchers ready for printing</small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Now
            </button>
            <a href="vouchers.php" class="btn btn-outline-secondary ms-2">Back</a>
        </div>
    </div>

    <?php if (count($vouchers) === 0): ?>
        <div class="alert alert-warning">No available vouchers to print.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($vouchers as $v): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="voucher-card">
                        <div class="fw-bold text-primary">MAX WIFI</div>
                        <div class="voucher-code"><?= clean($v['code']) ?></div>
                        <div class="small text-muted"><?= clean($v['package_name'] ?? '') ?></div>
                        <div class="small">
                            <?= $v['duration_value'] ?> <?= $v['duration_unit'] ?>
                        </div>
                        <div class="small fw-bold mt-1">
                            <?= number_format($v['price']) ?> TZS
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>