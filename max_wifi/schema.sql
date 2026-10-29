-- =====================================================
-- MAX WIFI - Enterprise Hybrid Hotspot Management System
-- PHASE 1: Database Schema (Authentication + RBAC)
-- =====================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS `max_wifi` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `max_wifi`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: locations
-- -----------------------------------------------------
CREATE TABLE `locations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `address` TEXT NULL,
  `city` VARCHAR(80) NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(120) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_locations_code` (`code`),
  KEY `idx_locations_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: roles
-- -----------------------------------------------------
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = cannot be deleted',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: permissions
-- -----------------------------------------------------
CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `module` VARCHAR(50) NOT NULL COMMENT 'e.g. customers, packages, payments',
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_slug` (`slug`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: role_permissions
-- -----------------------------------------------------
CREATE TABLE `role_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: users (Admin & Staff)
-- -----------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `avatar` VARCHAR(255) NULL,
  `status` ENUM('active','inactive','locked','suspended') NOT NULL DEFAULT 'active',
  `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL,
  `password_changed_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_uuid` (`uuid`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: user_roles
-- -----------------------------------------------------
CREATE TABLE `user_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: customers (WiFi end-users)
-- -----------------------------------------------------
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(120) NULL,
  `phone` VARCHAR(30) NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(120) NULL,
  `status` ENUM('active','inactive','suspended','expired') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME NULL,
  `phone_verified_at` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customers_uuid` (`uuid`),
  UNIQUE KEY `uk_customers_username` (`username`),
  KEY `idx_customers_location` (`location_id`),
  KEY `idx_customers_status` (`status`),
  KEY `idx_customers_phone` (`phone`),
  KEY `idx_customers_deleted` (`deleted_at`),
  CONSTRAINT `fk_customers_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: system_settings
-- -----------------------------------------------------
CREATE TABLE `system_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `setting_type` ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
  `group_name` VARCHAR(50) NOT NULL DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: audit_logs
-- -----------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `customer_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `target_type` VARCHAR(50) NULL,
  `target_id` INT UNSIGNED NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_customer` (`customer_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_audit_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA - Default Roles
-- =====================================================
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Full system access', 1),
('Network Admin', 'network_admin', 'Network, routers, APs and sessions management', 1),
('Sales Admin', 'sales_admin', 'Packages, vouchers and customer sales', 1),
('Finance Admin', 'finance_admin', 'Payments, transactions and revenue', 1),
('Support Admin', 'support_admin', 'Customer support and basic management', 1),
('Viewer', 'viewer', 'Read-only access', 1);

-- =====================================================
-- SEED DATA - Core Permissions (Phase 1)
-- =====================================================
INSERT INTO `permissions` (`name`, `slug`, `module`, `description`) VALUES
-- Users & Roles
('View Users', 'users.view', 'users', 'View admin users'),
('Create Users', 'users.create', 'users', 'Create admin users'),
('Edit Users', 'users.edit', 'users', 'Edit admin users'),
('Delete Users', 'users.delete', 'users', 'Delete admin users'),
('Manage Roles', 'roles.manage', 'roles', 'Manage roles and permissions'),

-- Customers
('View Customers', 'customers.view', 'customers', 'View customers'),
('Create Customers', 'customers.create', 'customers', 'Create customers'),
('Edit Customers', 'customers.edit', 'customers', 'Edit customers'),
('Delete Customers', 'customers.delete', 'customers', 'Delete customers'),
('Suspend Customers', 'customers.suspend', 'customers', 'Suspend/activate customers'),

-- Locations
('View Locations', 'locations.view', 'locations', 'View locations'),
('Manage Locations', 'locations.manage', 'locations', 'Create/edit/delete locations'),

-- System
('View Dashboard', 'dashboard.view', 'dashboard', 'Access admin dashboard'),
('View Reports', 'reports.view', 'reports', 'View reports'),
('Manage Settings', 'settings.manage', 'settings', 'Manage system settings'),
('View Audit Logs', 'audit.view', 'audit', 'View audit logs');

-- =====================================================
-- SEED DATA - Super Admin gets ALL permissions
-- =====================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- =====================================================
-- SEED DATA - Default Location
-- =====================================================
INSERT INTO `locations` (`name`, `code`, `address`, `city`, `status`) VALUES
('Main Office', 'MAIN', 'Head Office', 'Dar es Salaam', 'active');

-- =====================================================
-- SEED DATA - Default Super Admin
-- Password: Admin@12345  (CHANGE IMMEDIATELY AFTER FIRST LOGIN)
-- =====================================================
INSERT INTO `users` (`uuid`, `username`, `email`, `password`, `full_name`, `status`, `is_super_admin`) VALUES
(UUID(), 'superadmin', 'admin@maxwifi.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Super Admin', 'active', 1);

-- Assign Super Admin role
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);

-- =====================================================
-- SEED DATA - Basic System Settings
-- =====================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `group_name`, `description`) VALUES
('business_name', 'MAX WIFI', 'string', 'general', 'Business / Company name'),
('currency', 'TZS', 'string', 'general', 'Default currency'),
('timezone', 'Africa/Dar_es_Salaam', 'string', 'general', 'System timezone'),
('session_timeout', '30', 'integer', 'security', 'Admin session timeout in minutes'),
('max_login_attempts', '5', 'integer', 'security', 'Max failed login attempts before lock'),
('lockout_duration', '15', 'integer', 'security', 'Account lockout duration in minutes');

SET FOREIGN_KEY_CHECKS = 1;