<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db  = Database::getInstance();
$pdo = $db->getConnection();

$newHash = password_hash('Admin@12345', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'superadmin'");
$stmt->execute([$newHash]);

echo "Password updated successfully!<br>";
echo "New hash: " . $newHash . "<br><br>";
echo "Now you can login with:<br>";
echo "Username: <b>superadmin</b><br>";
echo "Password: <b>Admin@12345</b><br><br>";
echo "<a href='login.php'>Go to Login</a>";