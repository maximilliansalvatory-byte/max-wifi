<?php
/**
 * =====================================================
 * MAX WIFI - Authentication Class
 * Phase 1: Authentication + RBAC
 * =====================================================
 *
 * HOW TO USE THIS FILE:
 *
 * require_once __DIR__ . '/../includes/db.php';
 * require_once __DIR__ . '/../includes/functions.php';
 * require_once __DIR__ . '/../includes/auth.php';
 *
 * $auth = new Auth();
 *
 * // Login
 * $result = $auth->login($username, $password);
 *
 * // Logout
 * $auth->logout();
 *
 * // Check permission
 * if ($auth->hasPermission('customers.view')) { ... }
 *
 * // Require permission
 * $auth->requirePermission('users.create');
 *
 * =====================================================
 */

class Auth
{
    private PDO $pdo;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    /**
     * Login admin user
     */
    public function login(string $username, string $password): array
    {
        $username = trim($username);

        // Find user
        $stmt = $this->pdo->prepare("
            SELECT id, uuid, username, email, password, full_name, status, 
                   is_super_admin, login_attempts, locked_until
            FROM users 
            WHERE (username = ? OR email = ?) 
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid username or password.'
            ];
        }

        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return [
                'success' => false,
                'message' => 'Account is temporarily locked. Please try again later.'
            ];
        }

        // Check account status
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Your account is ' . $user['status'] . '. Contact administrator.'
            ];
        }

        // Verify password
        if (!verifyPassword($password, $user['password'])) {
            $this->increaseLoginAttempts($user['id'], $user['login_attempts']);
            return [
                'success' => false,
                'message' => 'Invalid username or password.'
            ];
        }

        // Login successful → reset attempts + update last login
        $this->resetLoginAttempts($user['id']);
        $this->updateLastLogin($user['id']);

        // Load user roles & permissions
        $roles = $this->getUserRoles($user['id']);
        $permissions = $this->getUserPermissions($user['id']);

        // Set session
        session_regenerate_id(true);

        $_SESSION['user_id']        = $user['id'];
        $_SESSION['user_uuid']      = $user['uuid'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['full_name']      = $user['full_name'];
        $_SESSION['email']          = $user['email'];
        $_SESSION['is_super_admin'] = (int)$user['is_super_admin'];
        $_SESSION['roles']          = $roles;
        $_SESSION['permissions']    = $permissions;
        $_SESSION['login_time']     = time();

        // Audit log
        $this->logAction($user['id'], 'login', 'auth', 'User logged in successfully');

        return [
            'success' => true,
            'message' => 'Login successful.',
            'user'    => [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name']
            ]
        ];
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], 'logout', 'auth', 'User logged out');
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if (isSuperAdmin()) {
            return true;
        }

        if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
            return false;
        }

        return in_array($permissionSlug, $_SESSION['permissions']);
    }

    /**
     * Require a permission or die / redirect
     */
    public function requirePermission(string $permissionSlug): void
    {
        if (!$this->hasPermission($permissionSlug)) {
            setFlash('error', 'You do not have permission to perform this action.');
            redirect('dashboard.php');
        }
    }

    /**
     * Get user roles
     */
    private function getUserRoles(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.slug 
            FROM roles r
            INNER JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get user permissions
     */
    private function getUserPermissions(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.slug 
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            INNER JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Increase failed login attempts
     */
    private function increaseLoginAttempts(int $userId, int $currentAttempts): void
    {
        $config = require __DIR__ . '/../config/database.php';
        $maxAttempts = $config['max_login_attempts'] ?? 5;
        $lockMinutes = $config['lockout_duration'] ?? 15;

        $newAttempts = $currentAttempts + 1;

        if ($newAttempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$lockMinutes} minutes"));
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET login_attempts = ?, locked_until = ?, status = 'locked' 
                WHERE id = ?
            ");
            $stmt->execute([$newAttempts, $lockedUntil, $userId]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE users SET login_attempts = ? WHERE id = ?
            ");
            $stmt->execute([$newAttempts, $userId]);
        }
    }

    /**
     * Reset login attempts after successful login
     */
    private function resetLoginAttempts(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET login_attempts = 0, locked_until = NULL, status = 'active' 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Update last login info
     */
    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET last_login_at = NOW(), last_login_ip = ? 
            WHERE id = ?
        ");
        $stmt->execute([getClientIp(), $userId]);
    }

    /**
     * Write to audit log
     */
    private function logAction(int $userId, string $action, string $module, string $description = null): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, module, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            $module,
            getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}