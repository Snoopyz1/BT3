<?php
// ============================================================
// controllers/RoomController.php
// ============================================================
require_once __DIR__ . '/../models/RoomModel.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

class RoomController {
    private RoomModel $model;

    public function __construct() {
        $this->model = new RoomModel();
    }

    public function index(): array {
        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        return [
            'rooms' => $this->model->getAll($limit, $offset),
            'total' => $this->model->count(),
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function create(): void {
        requireRole(['admin', 'technician']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'room_code'     => clean($_POST['room_code'] ?? ''),
            'name'          => clean($_POST['name'] ?? ''),
            'floor'         => (int) ($_POST['floor'] ?? 1),
            'capacity'      => (int) ($_POST['capacity'] ?? 0),
            'computer_count'=> (int) ($_POST['computer_count'] ?? 0),
            'description'   => clean($_POST['description'] ?? ''),
            'status'        => clean($_POST['status'] ?? 'active'),
            'image'         => '',
        ];

        if (empty($data['room_code']) || empty($data['name'])) {
            flashMessage('error', 'Vui lòng nhập mã phòng và tên phòng.');
            return;
        }

        if ($this->model->findByCode($data['room_code'])) {
            flashMessage('error', 'Mã phòng đã tồn tại.');
            return;
        }

        // Upload ảnh
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../assets/images/rooms/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $name = 'room_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $name)) {
                $data['image'] = 'assets/images/rooms/' . $name;
            }
        }

        $id = $this->model->create($data);
        if ($id) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'create_room', "Tạo phòng {$data['room_code']}");
            flashMessage('success', 'Thêm phòng thành công!');
        } else {
            flashMessage('error', 'Thêm phòng thất bại.');
        }
        redirect('views/rooms/index.php');
    }

    public function edit(int $id): array|false {
        requireRole(['admin', 'technician']);
        return $this->model->findById($id);
    }

    public function update(int $id): void {
        requireRole(['admin', 'technician']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $room = $this->model->findById($id);
        if (!$room) {
            flashMessage('error', 'Phòng không tồn tại.');
            redirect('views/rooms/index.php');
        }

        $data = [
            'room_code'     => clean($_POST['room_code'] ?? ''),
            'name'          => clean($_POST['name'] ?? ''),
            'floor'         => (int) ($_POST['floor'] ?? 1),
            'capacity'      => (int) ($_POST['capacity'] ?? 0),
            'computer_count'=> (int) ($_POST['computer_count'] ?? 0),
            'description'   => clean($_POST['description'] ?? ''),
            'status'        => clean($_POST['status'] ?? 'active'),
            'image'         => $room['image'],
        ];

        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../assets/images/rooms/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $name = 'room_' . $id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $name)) {
                $data['image'] = 'assets/images/rooms/' . $name;
            }
        }

        if ($this->model->update($id, $data)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'update_room', "Cập nhật phòng ID $id");
            flashMessage('success', 'Cập nhật phòng thành công!');
        } else {
            flashMessage('error', 'Cập nhật thất bại.');
        }
        redirect('views/rooms/index.php');
    }

    public function delete(int $id): void {
        requireRole(['admin']);
        if ($this->model->delete($id)) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'delete_room', "Xóa phòng ID $id");
            flashMessage('success', 'Đã xóa phòng.');
        } else {
            flashMessage('error', 'Xóa phòng thất bại.');
        }
        redirect('views/rooms/index.php');
    }
}
