<?php
session_start();

// Clear only customer session data
unset($_SESSION['customer_id']);
unset($_SESSION['customer_uuid']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_user']);

header("Location: login.php");
exit;