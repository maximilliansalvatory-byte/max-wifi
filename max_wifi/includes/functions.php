<?php
/**
 * =====================================================
 * MAX WIFI - Common Helper Functions
 * Phase 1: Authentication + RBAC
 * =====================================================
 *
 * HOW TO USE THIS FILE:
 *
 * require_once __DIR__ . '/../includes/functions.php';
 *
 * Then you can use any function below, example:
 * $uuid = generateUuid();
 * $clean = clean($_POST['username']);
 * redirect('dashboard.php');
 * setFlash('success', 'Login successful');
 * if (isLoggedIn()) { ... }
 *
 * =====================================================
 */

/**
 * Generate UUID v4
 */
function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Sanitize string input
 */
function clean(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $url): void
{
    header("Location: " . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,   // success | error | warning | info
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if admin is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in admin user ID
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        redirect('login.php');
    }
}

/**
 * Check if current user is Super Admin
 */
function isSuperAdmin(): bool
{
    return isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1;
}

/**
 * Get client IP address
 */
function getClientIp(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return trim(explode(',', $ip)[0]);
}

/**
 * Return JSON response and exit
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generate CSRF Token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Hash password
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}