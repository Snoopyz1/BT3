<?php
// ============================================================
// controllers/AuthController.php
// ============================================================
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../config/helpers.php';

class AuthController {
    private UserModel $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            flashMessage('error', 'Vui lòng nhập đầy đủ email và mật khẩu.');
            return;
        }

        $user = $this->model->login($email, $password);
        if (!$user) {
            flashMessage('error', 'Email hoặc mật khẩu không đúng, hoặc tài khoản bị khóa.');
            return;
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];

        $db = Database::getConnection();
        writeLog($db, $user['id'], 'login', 'Đăng nhập thành công từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        redirect('views/dashboard.php');
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $data = [
            'full_name' => clean($_POST['full_name'] ?? ''),
            'email'     => clean($_POST['email'] ?? ''),
            'password'  => $_POST['password'] ?? '',
            'phone'     => clean($_POST['phone'] ?? ''),
            'role'      => 'student',
            'status'    => 'active',
        ];

        if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
            flashMessage('error', 'Vui lòng nhập đầy đủ thông tin.');
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            flashMessage('error', 'Email không hợp lệ.');
            return;
        }

        if (strlen($data['password']) < 6) {
            flashMessage('error', 'Mật khẩu phải có ít nhất 6 ký tự.');
            return;
        }

        if ($this->model->findByEmail($data['email'])) {
            flashMessage('error', 'Email đã được sử dụng.');
            return;
        }

        $id = $this->model->create($data);
        if ($id) {
            flashMessage('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            redirect('auth/login.php');
        } else {
            flashMessage('error', 'Đăng ký thất bại. Thử lại.');
        }
    }

    public function logout(): void {
        if (isLoggedIn()) {
            $db = Database::getConnection();
            writeLog($db, $_SESSION['user_id'], 'logout', 'Đăng xuất');
        }
        session_destroy();
        redirect('auth/login.php');
    }
}
