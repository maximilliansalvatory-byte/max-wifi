<?php
/**
 * MAX WIFI - Database Configuration
 * Phase 1: Authentication + RBAC
 * 
 * IMPORTANT: Never commit real credentials to public repositories.
 * Change these values according to your local/XAMPP environment.
 */

return [

    // Database Connection
    'host'      => '127.0.0.1',
    'port'      => '3306',
    'dbname'    => 'max_wifi',
    'username'  => 'root',
    'password'  => '',              // ← Change this if your MySQL has a password
    'charset'   => 'utf8mb4',

    // PDO Options
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],

    // Application Settings
    'app_name'          => 'MAX WIFI',
    'app_url'           => 'http://localhost/max-wifi',   // Change according to your path
    'timezone'          => 'Africa/Dar_es_Salaam',
    
    // Security
    'session_name'      => 'MAXWIFI_SESSION',
    'session_lifetime'  => 30,          // minutes
    'max_login_attempts'=> 5,
    'lockout_duration'  => 15,          // minutes
];