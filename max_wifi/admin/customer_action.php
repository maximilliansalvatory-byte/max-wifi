<?php
/**
 * =====================================================
 * MAX WIFI - Customer Actions (Suspend / Activate / Delete)
 * Phase 3: Customer Management
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$auth = new Auth();

$db  = Database::getInstance();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0 || empty($action)) {
    setFlash('error', 'Invalid request.');
    redirect('customers.php');
}

// Check if customer exists
$stmt = $pdo->prepare("SELECT id, full_name, status FROM customers WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect('customers.php');
}

switch ($action) {

    case 'suspend':
        $auth->requirePermission('customers.suspend');

        $stmt = $pdo->prepare("UPDATE customers SET status = 'suspended', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Customer "' . $customer['full_name'] . '" has been suspended.');
        break;

    case 'activate':
        $auth->requirePermission('customers.suspend');

        $stmt = $pdo->prepare("UPDATE customers SET status = 'active', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Customer "' . $customer['full_name'] . '" has been activated.');
        break;

    case 'delete':
        $auth->requirePermission('customers.delete');

        // Soft delete
        $stmt = $pdo->prepare("UPDATE customers SET deleted_at = NOW(), status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Customer "' . $customer['full_name'] . '" has been deleted.');
        break;

    default:
        setFlash('error', 'Unknown action.');
        break;
}

redirect('customers.php');