<?php
// ============================================================
// controllers/DeviceController.php
// ============================================================
require_once __DIR__ . '/../models/DeviceModel.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

class DeviceController {
    private DeviceModel $model;

    public function __construct() {
        $this->model = new DeviceModel();
    }

    public function index(): array {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;
        return [
            'devices' => $this->model->getAll($limit, $offset),
            'total'   => $this->model->count(),
            'page'    => $page,
            'limit'   => $limit,
        ];
    }

    public function create(): void {
        requireRole(['admin', 'technician']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'room_id'       => (int) ($_POST['room_id'] ?? 0),
            'device_name'   => clean($_POST['device_name'] ?? ''),
            'device_type'   => clean($_POST['device_type'] ?? 'other'),
            'serial_number' => clean($_POST['serial_number'] ?? ''),
            'quantity'      => (int) ($_POST['quantity'] ?? 1),
            'status'        => clean($_POST['status'] ?? 'available'),
            'description'   => clean($_POST['description'] ?? ''),
        ];

        if (empty($data['device_name']) || $data['room_id'] <= 0) {
            flashMessage('error', 'Vui lòng nhập tên thiết bị và chọn phòng.');
            return;
        }

        $id = $this->model->create($data);
        if ($id) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'create_device', "Thêm thiết bị: {$data['device_name']}");
            flashMessage('success', 'Thêm thiết bị thành công!');
        } else {
            flashMessage('error', 'Thêm thiết bị thất bại.');
        }
        redirect('views/devices/index.php');
    }

    public function update(int $id): void {
        requireRole(['admin', 'technician']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'room_id'       => (int) ($_POST['room_id'] ?? 0),
            'device_name'   => clean($_POST['device_name'] ?? ''),
            'device_type'   => clean($_POST['device_type'] ?? 'other'),
            'serial_number' => clean($_POST['serial_number'] ?? ''),
            'quantity'      => (int) ($_POST['quantity'] ?? 1),
            'status'        => clean($_POST['status'] ?? 'available'),
            'description'   => clean($_POST['description'] ?? ''),
        ];

        if ($this->model->update($id, $data)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'update_device', "Cập nhật thiết bị ID $id");
            flashMessage('success', 'Cập nhật thiết bị thành công!');
        } else {
            flashMessage('error', 'Cập nhật thất bại.');
        }
        redirect('views/devices/index.php');
    }

    public function delete(int $id): void {
        requireRole(['admin']);
        if ($this->model->delete($id)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'delete_device', "Xóa thiết bị ID $id");
            flashMessage('success', 'Đã xóa thiết bị.');
        } else {
            flashMessage('error', 'Xóa thất bại.');
        }
        redirect('views/devices/index.php');
    }
}
