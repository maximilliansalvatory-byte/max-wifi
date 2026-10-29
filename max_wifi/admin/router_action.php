<?php
/**
 * =====================================================
 * MAX WIFI - Router Actions (Delete)
 * Phase 10: MikroTik Integration
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
    redirect('routers.php');
}

$stmt = $pdo->prepare("SELECT id, name FROM routers WHERE id = ?");
$stmt->execute([$id]);
$router = $stmt->fetch();

if (!$router) {
    setFlash('error', 'Router not found.');
    redirect('routers.php');
}

$stmt = $pdo->prepare("DELETE FROM routers WHERE id = ?");
$stmt->execute([$id]);

setFlash('success', 'Router "' . $router['name'] . '" has been deleted.');
redirect('routers.php');