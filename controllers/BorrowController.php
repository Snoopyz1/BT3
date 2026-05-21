<?php
// ============================================================
// controllers/BorrowController.php
// ============================================================
require_once __DIR__ . '/../models/BorrowModel.php';
require_once __DIR__ . '/../models/DeviceModel.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

class BorrowController {
    private BorrowModel  $model;
    private DeviceModel  $deviceModel;

    public function __construct() {
        $this->model       = new BorrowModel();
        $this->deviceModel = new DeviceModel();
    }

    public function index(): array {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;
        $borrows = hasRole(['admin','technician'])
            ? $this->model->getAll($limit, $offset)
            : $this->model->getByUser($_SESSION['user_id']);
        return [
            'borrows' => $borrows,
            'total'   => $this->model->count(),
            'page'    => $page,
            'limit'   => $limit,
        ];
    }

    public function create(): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'device_id'            => (int) ($_POST['device_id'] ?? 0),
            'user_id'              => (int) $_SESSION['user_id'],
            'borrow_date'          => clean($_POST['borrow_date'] ?? date('Y-m-d')),
            'expected_return_date' => clean($_POST['expected_return_date'] ?? ''),
            'purpose'              => clean($_POST['purpose'] ?? ''),
        ];

        if ($data['device_id'] <= 0 || empty($data['expected_return_date'])) {
            flashMessage('error', 'Vui lòng chọn thiết bị và ngày trả dự kiến.');
            return;
        }

        $device = $this->deviceModel->findById($data['device_id']);
        if (!$device || $device['status'] !== 'available') {
            flashMessage('error', 'Thiết bị không sẵn sàng để mượn.');
            return;
        }

        $id = $this->model->create($data);
        if ($id) {
            $this->deviceModel->updateStatus($data['device_id'], 'borrowed');
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'borrow_device', "Mượn thiết bị ID {$data['device_id']}");
            flashMessage('success', 'Đăng ký mượn thiết bị thành công!');
        } else {
            flashMessage('error', 'Đăng ký mượn thiết bị thất bại.');
        }
        redirect('views/devices/borrow.php');
    }

    public function returnDevice(int $id): void {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $borrow = $this->model->findById($id);
        if (!$borrow) {
            flashMessage('error', 'Không tìm thấy bản ghi mượn.');
            redirect('views/devices/borrow.php');
        }

        // Kiểm tra quyền
        if ($borrow['user_id'] != $_SESSION['user_id'] && !hasRole(['admin','technician'])) {
            flashMessage('error', 'Bạn không có quyền thực hiện thao tác này.');
            redirect('views/devices/borrow.php');
        }

        $returnDate = clean($_POST['return_date'] ?? date('Y-m-d'));
        $note       = clean($_POST['return_note'] ?? '');

        if ($this->model->returnDevice($id, $returnDate, $note)) {
            $this->deviceModel->updateStatus($borrow['device_id'], 'available');
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'return_device', "Trả thiết bị ID {$borrow['device_id']}");
            flashMessage('success', 'Trả thiết bị thành công!');
        } else {
            flashMessage('error', 'Trả thiết bị thất bại.');
        }
        redirect('views/devices/borrow.php');
    }
}
