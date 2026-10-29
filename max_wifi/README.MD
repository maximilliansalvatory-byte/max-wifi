# MAX WIFI - Enterprise Hybrid WiFi Hotspot Management System

## Overview
MAX WIFI is a complete WiFi Hotspot Management System inspired by MikroTik and UniFi.
It supports customer management, packages, vouchers, payments, captive portal, and more.

## Features

### Admin Panel
- Secure Login with RBAC
- Dashboard with real-time statistics
- Customer Management (CRUD)
- WiFi Packages Management
- Voucher Generation (single & bulk)
- Payment Recording + Service Layer
- Active Sessions Monitoring
- Routers & Access Points Management
- Reports with Graphs + Print/PDF
- System Settings

### Customer Portal
- Registration & Login
- Dashboard with active subscription
- Buy Package
- View remaining time

### Captive Portal
- Login with Account
- Login with Voucher
- Auto-create guest + subscription

### Other
- Payment Service Layer (M-Pesa, Airtel, HaloPesa, Tigo, NMB, CRDB, Cash)
- Auto-expire subscriptions (Cron)
- Security Headers + Rate Limiting
- Soft deletes & Audit ready

## Tech Stack
- PHP 8+
- MySQL (PDO)
- Bootstrap 5
- Chart.js
- Custom MikroTik helper class

## Installation

1. Clone/download the project into your web root
2. Create database `max_wifi`
3. Import `database/schema_full.sql`
4. Configure `config/database.php`
5. Access Admin: `/admin/login.php`
   - Default: superadmin / Admin@12345

## Default Credentials
- **Admin**: superadmin / Admin@12345
- Change password after first login!

## Folder Structure