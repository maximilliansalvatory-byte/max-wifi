<?php
/**
 * =====================================================
 * MAX WIFI - Access Point Actions (Delete)
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

if ($id <= 0 || $action !== 'delete') {
    setFlash('error', 'Invalid request.');
    redirect('access_points.php');
}

$stmt = $pdo->prepare("SELECT id, name FROM access_points WHERE id = ?");
$stmt->execute([$id]);
$ap = $stmt->fetch();

if (!$ap) {
    setFlash('error', 'Access Point not found.');
    redirect('access_points.php');
}

$stmt = $pdo->prepare("DELETE FROM access_points WHERE id = ?");
$stmt->execute([$id]);

setFlash('success', 'Access Point "' . $ap['name'] . '" has been deleted.');
redirect('access_points.php');