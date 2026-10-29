<?php
/**
 * =====================================================
 * MAX WIFI - Customer Registration
 * =====================================================
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['customer_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$form = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
    'phone'     => '',
    'password'  => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $form['full_name'] = clean($_POST['full_name'] ?? '');
        $form['username']  = clean($_POST['username'] ?? '');
        $form['email']     = clean($_POST['email'] ?? '');
        $form['phone']     = clean($_POST['phone'] ?? '');
        $form['password']  = $_POST['password'] ?? '';
        $confirmPassword   = $_POST['confirm_password'] ?? '';

        if (empty($form['full_name'])) {
            $errors[] = 'Full name is required.';
        }
        if (empty($form['username'])) {
            $errors[] = 'Username is required.';
        }
        if (empty($form['password']) || strlen($form['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($form['password'] !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        // Database connection
        $db  = Database::getInstance();
        $pdo = $db->getConnection();

        // Check if username already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE username = ? AND deleted_at IS NULL");
            $stmt->execute([$form['username']]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already taken.';
            }
        }

        // Insert new customer
        if (empty($errors)) {
            $uuid   = generateUuid();
            $hashed = hashPassword($form['password']);

            $stmt = $pdo->prepare("
                INSERT INTO customers (uuid, username, email, phone, password, full_name, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $uuid,
                $form['username'],
                $form['email'] ?: null,
                $form['phone'] ?: null,
                $hashed,
                $form['full_name']
            ]);

            setFlash('success', 'Account created successfully! Please login.');
            header("Location: login.php");
            exit;
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
    <title>Register - MAX WIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .register-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
        }
    </style>
</head>
<body>

<div class="register-card">
    <h3 class="text-center mb-1">Create Account</h3>
    <p class="text-center text-muted mb-4">Join MAX WIFI</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= clean($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= clean($form['full_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= clean($form['username']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email (optional)</label>
            <input type="email" name="email" class="form-control" value="<?= clean($form['email']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= clean($form['phone']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
        </div>

        <div class="mb-4">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
    </form>

    <div class="text-center mt-3">
        <small>Already have an account? <a href="login.php">Login here</a></small>
    </div>
</div>

</body>
</html>