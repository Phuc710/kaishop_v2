<?php
/**
 * Admin Dashboard — Forward tới OOP Router
 * 
 * Apache serve /admin/ trực tiếp (vì .htaccess bỏ qua directory).
 * File này load app config rồi gọi DashboardController trực tiếp.
 */

// Chặn double load
if (defined('APP_LOADED'))
    return;

// Load app config
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Controllers/Admin/DashboardController.php';

// Gọi DashboardController trực tiếp
$controller = new DashboardController();
$controller->index();