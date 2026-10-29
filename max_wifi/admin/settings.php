<?php
/**
 * =====================================================
 * MAX WIFI - System Settings
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Fetch all settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settingsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$settings = [
    'business_name'     => $settingsRaw['business_name'] ?? 'MAX WIFI',
    'currency'          => $settingsRaw['currency'] ?? 'TZS',
    'timezone'          => $settingsRaw['timezone'] ?? 'Africa/Dar_es_Salaam',
    'session_timeout'   => $settingsRaw['session_timeout'] ?? '30',
    'max_login_attempts'=> $settingsRaw['max_login_attempts'] ?? '5',
    'lockout_duration'  => $settingsRaw['lockout_duration'] ?? '15',
];

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $settings['business_name']      = clean($_POST['business_name'] ?? '');
        $settings['currency']           = clean($_POST['currency'] ?? 'TZS');
        $settings['timezone']           = clean($_POST['timezone'] ?? 'Africa/Dar_es_Salaam');
        $settings['session_timeout']    = (int)($_POST['session_timeout'] ?? 30);
        $settings['max_login_attempts'] = (int)($_POST['max_login_attempts'] ?? 5);
        $settings['lockout_duration']   = (int)($_POST['lockout_duration'] ?? 15);

        if (empty($settings['business_name'])) {
            $errors[] = 'Business name is required.';
        }

        if (empty($errors)) {
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }

            $success = true;
            setFlash('success', 'Settings updated successfully.');
            redirect('settings.php');
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar {
            width: 260px; min-height: 100vh; background: #0f172a; color: #fff;
            position: fixed; left: 0; top: 0; padding-top: 1.5rem; z-index: 100;
        }
        .sidebar .brand {
            font-size: 1.4rem; font-weight: 700; padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid #1e293b; margin-bottom: 1rem;
        }
        .sidebar a {
            display: block; color: #94a3b8; text-decoration: none;
            padding: 0.75rem 1.5rem; transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active { background: #1e293b; color: #fff; }
        .main-content { margin-left: 260px; padding: 2rem; }
        .topbar {
            background: #fff; border-radius: 12px; padding: 1rem 1.5rem;
            margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex; justify-content: space-between; align-items: center;
        }
        .form-card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 2rem; max-width: 700px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand">MAX WIFI</div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="customers.php"><i class="bi bi-people me-2"></i> Customers</a>
    <a href="packages.php"><i class="bi bi-wifi me-2"></i> Packages</a>
    <a href="vouchers.php"><i class="bi bi-ticket-perforated me-2"></i> Vouchers</a>
    <a href="sessions.php"><i class="bi bi-activity me-2"></i> Sessions</a>
    <a href="routers.php"><i class="bi bi-hdd-network me-2"></i> Routers</a>
    <a href="access_points.php"><i class="bi bi-router me-2"></i> Access Points</a>
    <a href="payments.php"><i class="bi bi-cash-coin me-2"></i> Payments</a>
      <a href="reports.php"><i class="bi bi-bar-chart me-2"></i> Report</a>
    <a href="settings.php" class="active"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div>
            <h5 class="mb-0">System Settings</h5>
            <small class="text-muted">Configure your system</small>
        </div>
           <div class="mt-4 px-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= clean($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= clean($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <h6 class="mb-3 text-muted">General Settings</h6>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-control" value="<?= clean($settings['business_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="<?= clean($settings['currency']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" class="form-control" value="<?= clean($settings['timezone']) ?>">
                </div>
            </div>

            <h6 class="mb-3 text-muted">Security Settings</h6>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Session Timeout (minutes)</label>
                    <input type="number" name="session_timeout" class="form-control" value="<?= $settings['session_timeout'] ?>" min="5">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Login Attempts</label>
                    <input type="number" name="max_login_attempts" class="form-control" value="<?= $settings['max_login_attempts'] ?>" min="3">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lockout Duration (minutes)</label>
                    <input type="number" name="lockout_duration" class="form-control" value="<?= $settings['lockout_duration'] ?>" min="5">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>