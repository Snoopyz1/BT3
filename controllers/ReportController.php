<?php
// ============================================================
// controllers/ReportController.php
// ============================================================
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

class ReportController {
    private ReportModel $model;

    public function __construct() {
        $this->model = new ReportModel();
    }

    public function index(): array {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;
        return [
            'reports' => $this->model->getAll($limit, $offset),
            'total'   => $this->model->count(),
            'page'    => $page,
            'limit'   => $limit,
        ];
    }

    public function create(): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'room_id'     => !empty($_POST['room_id'])   ? (int) $_POST['room_id']   : null,
            'device_id'   => !empty($_POST['device_id']) ? (int) $_POST['device_id'] : null,
            'user_id'     => (int) $_SESSION['user_id'],
            'title'       => clean($_POST['title'] ?? ''),
            'description' => clean($_POST['description'] ?? ''),
            'severity'    => clean($_POST['severity'] ?? 'medium'),
        ];

        if (empty($data['title']) || empty($data['description'])) {
            flashMessage('error', 'Vui lòng nhập tiêu đề và mô tả sự cố.');
            return;
        }

        $id = $this->model->create($data);
        if ($id) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'create_report', "Tạo báo cáo sự cố: {$data['title']}");
            flashMessage('success', 'Báo cáo sự cố đã được gửi thành công!');
        } else {
            flashMessage('error', 'Gửi báo cáo thất bại.');
        }
        redirect('views/reports/index.php');
    }

    public function updateStatus(int $id): void {
        requireRole(['admin', 'technician']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $status     = clean($_POST['status'] ?? '');
        $resolution = clean($_POST['resolution'] ?? '');

        if ($this->model->updateStatus($id, $status, $resolution)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'update_report_status', "Cập nhật trạng thái báo cáo ID $id -> $status");
            flashMessage('success', 'Cập nhật trạng thái báo cáo thành công!');
        } else {
            flashMessage('error', 'Cập nhật thất bại.');
        }
        redirect('views/reports/index.php');
    }

    public function delete(int $id): void {
        requireRole('admin');
        if ($this->model->delete($id)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'delete_report', "Xóa báo cáo ID $id");
            flashMessage('success', 'Đã xóa báo cáo.');
        } else {
            flashMessage('error', 'Xóa thất bại.');
        }
        redirect('views/reports/index.php');
    }
}
