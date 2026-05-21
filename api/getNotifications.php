<?php
// ============================================================
// api/getNotifications.php — REST API: Thông báo
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'count' => 0, 'items' => []]);
    exit;
}

$db      = Database::getConnection();
$userId  = (int) $_SESSION['user_id'];
$role    = $_SESSION['role'];
$items   = [];

// Lịch chờ duyệt (cho admin)
if ($role === 'admin') {
    $stmt = $db->query("SELECT COUNT(*) FROM room_schedule WHERE status='pending'");
    $pendingCount = (int) $stmt->fetchColumn();
    if ($pendingCount > 0) {
        $items[] = [
            'type'    => 'pending_schedule',
            'message' => "$pendingCount lịch đặt phòng đang chờ duyệt",
            'url'     => BASE_URL . '/views/schedules/index.php',
        ];
    }

    $stmt2 = $db->query("SELECT COUNT(*) FROM reports WHERE status='open'");
    $openReports = (int) $stmt2->fetchColumn();
    if ($openReports > 0) {
        $items[] = [
            'type'    => 'open_report',
            'message' => "$openReports sự cố chưa được xử lý",
            'url'     => BASE_URL . '/views/reports/index.php',
        ];
    }
}

// Thiết bị quá hạn trả (cho người dùng hiện tại)
$stmt3 = $db->prepare(
    "SELECT COUNT(*) FROM device_borrow WHERE user_id=? AND status='borrowed' AND expected_return_date < CURDATE()"
);
$stmt3->execute([$userId]);
$overdue = (int) $stmt3->fetchColumn();
if ($overdue > 0) {
    $items[] = [
        'type'    => 'overdue_borrow',
        'message' => "Bạn có $overdue thiết bị quá hạn trả",
        'url'     => BASE_URL . '/views/devices/borrow.php',
    ];
}

echo json_encode([
    'success' => true,
    'count'   => count($items),
    'items'   => $items,
]);
