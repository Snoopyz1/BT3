<?php
// ============================================================
// index.php — Entry point
// ============================================================
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

// Route đơn giản theo GET param ?page=
$page = $_GET['page'] ?? 'dashboard';
$page = preg_replace('/[^a-zA-Z0-9_\-]/', '', $page);

if (!isLoggedIn() && $page !== 'login' && $page !== 'register') {
    // Nếu chưa đăng nhập, chuyển về trang bìa (landing page)
    header('Location: landing.php');
    exit;
}

// Chuyển đến trang tương ứng
switch ($page) {
    case 'dashboard':
        require_once __DIR__ . '/views/dashboard.php';
        break;
    case 'rooms':
        require_once __DIR__ . '/views/rooms/index.php';
        break;
    case 'devices':
        require_once __DIR__ . '/views/devices/index.php';
        break;
    case 'schedules':
        require_once __DIR__ . '/views/schedules/index.php';
        break;
    case 'reports':
        require_once __DIR__ . '/views/reports/index.php';
        break;
    case 'users':
        require_once __DIR__ . '/views/users/index.php';
        break;
    case 'borrow':
        require_once __DIR__ . '/views/devices/borrow.php';
        break;
    case 'logs':
        require_once __DIR__ . '/views/logs.php';
        break;
    default:
        require_once __DIR__ . '/views/dashboard.php';
}
