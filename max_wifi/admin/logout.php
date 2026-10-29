<?php
/**
 * =====================================================
 * MAX WIFI - Admin Logout
 * Phase 1: Authentication + RBAC
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$auth->logout();

setFlash('success', 'You have been logged out successfully.');
redirect('login.php');