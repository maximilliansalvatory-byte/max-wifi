<?php
/**
 * =====================================================
 * MAX WIFI - Package Actions (Toggle Status / Delete)
 * Phase 4: WiFi Packages
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
    redirect('packages.php');
}

// Check if package exists
$stmt = $pdo->prepare("SELECT id, name, status FROM wifi_packages WHERE id = ?");
$stmt->execute([$id]);
$package = $stmt->fetch();

if (!$package) {
    setFlash('error', 'Package not found.');
    redirect('packages.php');
}

switch ($action) {

    case 'toggle':
        $newStatus = $package['status'] === 'active' ? 'inactive' : 'active';

        $stmt = $pdo->prepare("UPDATE wifi_packages SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $message = $newStatus === 'active' 
            ? 'Package "' . $package['name'] . '" has been activated.' 
            : 'Package "' . $package['name'] . '" has been deactivated.';

        setFlash('success', $message);
        break;

    case 'delete':
        $stmt = $pdo->prepare("DELETE FROM wifi_packages WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Package "' . $package['name'] . '" has been deleted.');
        break;

    default:
        setFlash('error', 'Unknown action.');
        break;
}

redirect('packages.php');