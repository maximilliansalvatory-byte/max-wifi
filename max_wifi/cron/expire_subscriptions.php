<?php
/**
 * =====================================================
 * MAX WIFI - Auto Expire Subscriptions
 * =====================================================
 *
 * Run this file via Cron Job every 5-10 minutes.
 *
 * Example cron (copy carefully):
 * Every 5 minutes:
 * 0,5,10,15,20,25,30,35,40,45,50,55 * * * * php /path/to/max_wifi/cron/expire_subscriptions.php
 */

require_once __DIR__ . '/../includes/db.php';

$db  = Database::getInstance();
$pdo = $db->getConnection();

try {
    // Expire subscriptions that have passed end_at
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
        SET status = 'expired', updated_at = NOW()
        WHERE status = 'active' AND end_at < NOW()
    ");
    $stmt->execute();

    $expiredCount = $stmt->rowCount();

    // Optional: Also expire vouchers if needed
    $stmt2 = $pdo->prepare("
        UPDATE vouchers 
        SET status = 'expired', updated_at = NOW()
        WHERE status = 'available' AND expires_at IS NOT NULL AND expires_at < NOW()
    ");
    $stmt2->execute();

    $expiredVouchers = $stmt2->rowCount();

    // Log result
    $message = date('Y-m-d H:i:s') . " - Expired Subscriptions: $expiredCount | Expired Vouchers: $expiredVouchers" . PHP_EOL;
    
    file_put_contents(__DIR__ . '/expire_log.txt', $message, FILE_APPEND);

    echo $message;

} catch (Exception $e) {
    $error = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . PHP_EOL;
    file_put_contents(__DIR__ . '/expire_log.txt', $error, FILE_APPEND);
    echo $error;
}