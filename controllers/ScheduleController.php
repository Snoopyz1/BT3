<?php
// ============================================================
// controllers/ScheduleController.php
// ============================================================
require_once __DIR__ . '/../models/ScheduleModel.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

class ScheduleController {
    private ScheduleModel $model;

    public function __construct() {
        $this->model = new ScheduleModel();
    }

    public function index(): array {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $schedules = hasRole('admin')
            ? $this->model->getAll($limit, $offset)
            : $this->model->getByUser($_SESSION['user_id']);

        return [
            'schedules' => $schedules,
            'total'     => $this->model->count(),
            'page'      => $page,
            'limit'     => $limit,
        ];
    }

    public function create(): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'room_id'    => (int) ($_POST['room_id'] ?? 0),
            'user_id'    => (int) $_SESSION['user_id'],
            'title'      => clean($_POST['title'] ?? ''),
            'date'       => clean($_POST['date'] ?? ''),
            'start_time' => clean($_POST['start_time'] ?? ''),
            'end_time'   => clean($_POST['end_time'] ?? ''),
            'purpose'    => clean($_POST['purpose'] ?? ''),
            'status'     => hasRole('admin') ? 'approved' : 'pending',
        ];

        if ($data['room_id'] <= 0 || empty($data['title']) || empty($data['date'])
            || empty($data['start_time']) || empty($data['end_time'])) {
            flashMessage('error', 'Vui lòng nhập đầy đủ thông tin lịch đặt.');
            return;
        }

        if ($data['start_time'] >= $data['end_time']) {
            flashMessage('error', 'Giờ kết thúc phải sau giờ bắt đầu.');
            return;
        }

        if ($data['date'] < date('Y-m-d')) {
            flashMessage('error', 'Không thể đặt lịch cho ngày trong quá khứ.');
            return;
        }

        if ($this->model->isConflict($data['room_id'], $data['date'], $data['start_time'], $data['end_time'])) {
            flashMessage('error', 'Phòng đã có lịch trong khung giờ này. Vui lòng chọn giờ khác.');
            return;
        }

        $id = $this->model->create($data);
        if ($id) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'create_schedule', "Đặt lịch phòng ID {$data['room_id']} ngày {$data['date']}");
            flashMessage('success', 'Đặt lịch thành công!' . (hasRole('admin') ? '' : ' Chờ admin phê duyệt.'));
        } else {
            flashMessage('error', 'Đặt lịch thất bại.');
        }
        redirect('views/schedules/index.php');
    }

    public function approve(int $id): void {
        requireRole('admin');
        $note = clean($_POST['admin_note'] ?? '');
        if ($this->model->updateStatus($id, 'approved', $note)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'approve_schedule', "Phê duyệt lịch ID $id");
            flashMessage('success', 'Đã phê duyệt lịch đặt phòng.');
        } else {
            flashMessage('error', 'Phê duyệt thất bại.');
        }
        redirect('views/schedules/index.php');
    }

    public function reject(int $id): void {
        requireRole('admin');
        $note = clean($_POST['admin_note'] ?? 'Không đáp ứng yêu cầu.');
        if ($this->model->updateStatus($id, 'rejected', $note)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'reject_schedule', "Từ chối lịch ID $id");
            flashMessage('success', 'Đã từ chối lịch đặt phòng.');
        } else {
            flashMessage('error', 'Thất bại.');
        }
        redirect('views/schedules/index.php');
    }

    public function cancel(int $id): void {
        requireLogin();
        $schedule = $this->model->findById($id);
        if (!$schedule) {
            flashMessage('error', 'Lịch không tồn tại.');
            redirect('views/schedules/index.php');
        }
        // Chỉ chủ lịch hoặc admin được hủy
        if ($schedule['user_id'] != $_SESSION['user_id'] && !hasRole('admin')) {
            flashMessage('error', 'Bạn không có quyền hủy lịch này.');
            redirect('views/schedules/index.php');
        }
        if ($this->model->updateStatus($id, 'cancelled')) {
            flashMessage('success', 'Đã hủy lịch đặt phòng.');
        } else {
            flashMessage('error', 'Hủy thất bại.');
        }
        redirect('views/schedules/index.php');
    }
}
