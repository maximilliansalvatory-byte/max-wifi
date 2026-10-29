<?php
/**
 * =====================================================
 * MAX WIFI - Payment Actions (Mark as Paid / Failed)
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0 || empty($action)) {
    setFlash('error', 'Invalid request.');
    redirect('payments.php');
}

$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('error', 'Payment not found.');
    redirect('payments.php');
}

switch ($action) {

    case 'mark_paid':
        if ($payment['status'] !== 'pending') {
            setFlash('error', 'Only pending payments can be marked as paid.');
            redirect('payments.php');
        }

        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'success', paid_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        // Create subscription if customer exists
        if ($payment['customer_id']) {
            $pkg = $pdo->prepare("SELECT * FROM wifi_packages WHERE id = ?");
            $pkg->execute([$payment['package_id']]);
            $package = $pkg->fetch();

            if ($package) {
                $duration = (int)$package['duration_value'];
                $unit = $package['duration_unit'];

                $interval = match($unit) {
                    'minutes' => "INTERVAL $duration MINUTE",
                    'hours'   => "INTERVAL $duration HOUR",
                    'days'    => "INTERVAL $duration DAY",
                    'months'  => "INTERVAL $duration MONTH",
                    default   => "INTERVAL 1 HOUR"
                };

                $sub = $pdo->prepare("
                    INSERT INTO subscriptions 
                    (customer_id, package_id, start_at, end_at, data_quota, status)
                    VALUES (?, ?, NOW(), DATE_ADD(NOW(), $interval), ?, 'active')
                ");
                $sub->execute([
                    $payment['customer_id'],
                    $payment['package_id'],
                    $package['data_quota']
                ]);
            }
        }

        setFlash('success', 'Payment marked as paid successfully. Subscription created.');
        break;

    case 'mark_failed':
        $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Payment marked as failed.');
        break;

    default:
        setFlash('error', 'Unknown action.');
        break;
}

redirect('payments.php');