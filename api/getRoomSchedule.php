<?php
// ============================================================
// api/getRoomSchedule.php — REST API: Lấy lịch phòng
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/ScheduleModel.php';

// Chỉ cho phép GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Xác thực session (đơn giản)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$roomId = (int) ($_GET['room_id'] ?? 0);
$date   = clean($_GET['date'] ?? '');

// Validate
if ($roomId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số room_id']);
    exit;
}

if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tham số date không hợp lệ (YYYY-MM-DD)']);
    exit;
}

try {
    $model     = new ScheduleModel();
    $schedules = $model->getByRoom($roomId, $date);

    // Lấy thêm thông tin về tuần hiện tại nếu có
    $weekStart = $_GET['week_start'] ?? null;
    $weekEnd   = $_GET['week_end']   ?? null;
    $weekData  = [];
    if ($weekStart && $weekEnd) {
        $weekData = $model->getByWeek($weekStart, $weekEnd);
    }

    echo json_encode([
        'success'   => true,
        'room_id'   => $roomId,
        'date'      => $date,
        'schedules' => array_map(function ($s) {
            return [
                'id'          => $s['id'],
                'title'       => $s['title'],
                'start_time'  => $s['start_time'],
                'end_time'    => $s['end_time'],
                'user_name'   => $s['user_name'],
                'status'      => $s['status'],
            ];
        }, $schedules),
        'week_schedules' => $weekData ? array_map(function ($s) {
            return [
                'id'        => $s['id'],
                'room_code' => $s['room_code'],
                'title'     => $s['title'],
                'date'      => $s['date'],
                'start'     => $s['start_time'],
                'end'       => $s['end_time'],
                'status'    => $s['status'],
            ];
        }, $weekData) : [],
        'count' => count($schedules),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
