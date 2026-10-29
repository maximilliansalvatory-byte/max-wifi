<?php
/**
 * =====================================================
 * MAX WIFI - Voucher Actions (Disable / Enable / Delete)
 * Phase 5: Voucher System
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
    redirect('vouchers.php');
}

// Check if voucher exists
$stmt = $pdo->prepare("SELECT id, code, status FROM vouchers WHERE id = ?");
$stmt->execute([$id]);
$voucher = $stmt->fetch();

if (!$voucher) {
    setFlash('error', 'Voucher not found.');
    redirect('vouchers.php');
}

switch ($action) {

    case 'disable':
        if ($voucher['status'] !== 'available') {
            setFlash('error', 'Only available vouchers can be disabled.');
            redirect('vouchers.php');
        }

        $stmt = $pdo->prepare("UPDATE vouchers SET status = 'disabled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Voucher ' . $voucher['code'] . ' has been disabled.');
        break;

    case 'enable':
        if ($voucher['status'] !== 'disabled') {
            setFlash('error', 'Only disabled vouchers can be enabled.');
            redirect('vouchers.php');
        }

        $stmt = $pdo->prepare("UPDATE vouchers SET status = 'available', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Voucher ' . $voucher['code'] . ' has been enabled.');
        break;

    case 'delete':
        $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Voucher ' . $voucher['code'] . ' has been deleted.');
        break;

    default:
        setFlash('error', 'Unknown action.');
        break;
}

redirect('vouchers.php');