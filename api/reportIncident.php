<?php
// ============================================================
// api/reportIncident.php — REST API: Tạo báo cáo sự cố
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/ReportModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']);
    exit;
}

// Nhận JSON body hoặc form POST
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
} else {
    $body = $_POST;
}

$title       = clean($body['title'] ?? '');
$description = clean($body['description'] ?? '');
$severity    = clean($body['severity'] ?? 'medium');
$roomId      = !empty($body['room_id'])   ? (int) $body['room_id']   : null;
$deviceId    = !empty($body['device_id']) ? (int) $body['device_id'] : null;

// Validation
$errors = [];
if (empty($title))       $errors[] = 'Tiêu đề không được để trống.';
if (empty($description)) $errors[] = 'Mô tả không được để trống.';
if (!in_array($severity, ['low','medium','high','critical'])) {
    $errors[] = 'Mức độ không hợp lệ. Cho phép: low, medium, high, critical.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $model = new ReportModel();
    $id    = $model->create([
        'room_id'     => $roomId,
        'device_id'   => $deviceId,
        'user_id'     => (int) $_SESSION['user_id'],
        'title'       => $title,
        'description' => $description,
        'severity'    => $severity,
    ]);

    if ($id) {
        $db = Database::getConnection();
        writeLog($db, $_SESSION['user_id'], 'create_report', "API: Tạo báo cáo '$title'");

        http_response_code(201);
        echo json_encode([
            'success'    => true,
            'message'    => 'Báo cáo sự cố đã được tạo thành công.',
            'report_id'  => $id,
            'data'       => [
                'title'       => $title,
                'description' => $description,
                'severity'    => $severity,
                'room_id'     => $roomId,
                'device_id'   => $deviceId,
                'status'      => 'open',
                'created_by'  => $_SESSION['user_name'],
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Tạo báo cáo thất bại.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
