<?php
// ============================================================
// auth/register.php
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../controllers/AuthController.php';

if (isLoggedIn()) redirect('views/dashboard.php');

$controller = new AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->register();
}

$errorMsg   = getFlash('error');
$successMsg = getFlash('success');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng ký | <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { background: #f8f9fa; min-height:100vh; }
  .register-card { border-radius:20px; border:1px solid rgba(0,0,0,0.1); background:#ffffff; box-shadow:0 15px 35px rgba(0,0,0,0.05); }
  .brand-logo { width:64px;height:64px;background:linear-gradient(135deg,#e63946,#dc3545);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;margin:0 auto 12px;box-shadow:0 8px 24px rgba(220,53,69,0.3); }
  .form-control { background:#ffffff;border:1px solid rgba(0,0,0,0.15);color:#0f172a;border-radius:10px; }
  .form-control:focus { border-color:#dc3545;color:#0f172a;box-shadow:0 0 0 3px rgba(220,53,69,0.15); }
  .form-control::placeholder { color:#adb5bd; }
  .form-select { background-color:#ffffff;border:1px solid rgba(0,0,0,0.15);color:#0f172a;border-radius:10px; }
  .form-select:focus { border-color:#dc3545;box-shadow:0 0 0 3px rgba(220,53,69,0.15); }
  .btn-register { background:linear-gradient(135deg,#e63946,#c1121f);border:none;border-radius:10px;font-weight:600;transition:all 0.3s; color:#fff; }
  .btn-register:hover { transform:translateY(-2px);box-shadow:0 8px 24px rgba(220,53,69,0.4); color:#fff; }
  .form-label { color:#334155;font-size:0.85rem;font-weight:500; }
  h4 { color:#0f172a; }
  small { color:#64748b !important; }
</style>
</head>
<body class="d-flex align-items-center justify-content-center py-4">
<div class="container" style="max-width:480px; padding:24px 16px;">
  <div class="register-card p-4 p-md-5">
    <div class="text-center mb-4">
      <div class="brand-logo"><i class="bi bi-person-plus"></i></div>
      <h4 class="fw-bold mb-1">Tạo Tài Khoản</h4>
      <p style="color:#64748b;font-size:0.85rem;">Hệ Thống Quản Lý Phòng Thực Hành A3</p>
    </div>

    <?php if ($errorMsg): ?>
      <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-2"></i><?= $errorMsg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="mb-3">
        <label class="form-label"><i class="bi bi-person me-1"></i>Họ và tên <span class="text-danger">*</span></label>
        <input type="text" name="full_name" class="form-control" placeholder="Nguyễn Văn A" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><i class="bi bi-envelope me-1"></i>Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" placeholder="example@ptit.edu.vn" required>
      </div>
      <div class="mb-3">
        <label class="form-label"><i class="bi bi-phone me-1"></i>Số điện thoại</label>
        <input type="tel" name="phone" class="form-control" placeholder="0912345678">
      </div>
      <div class="mb-3">
        <label class="form-label"><i class="bi bi-lock me-1"></i>Mật khẩu <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required minlength="6">
      </div>
      <div class="mb-4">
        <label class="form-label"><i class="bi bi-shield me-1"></i>Vai trò</label>
        <select name="role" class="form-select" disabled>
          <option value="student">Sinh viên</option>
        </select>
        <small class="d-block mt-1">* Vai trò mặc định là Sinh viên. Liên hệ Admin để thay đổi.</small>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-register btn-primary btn-lg text-white">
          <i class="bi bi-person-check me-2"></i>Đăng ký tài khoản
        </button>
      </div>
    </form>

    <hr style="border-color:rgba(255,255,255,0.1);margin:24px 0;">
    <p class="text-center mb-0" style="color:#64748b;font-size:0.85rem;">
      Đã có tài khoản?
      <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-semibold text-decoration-none">Đăng nhập</a>
    </p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
