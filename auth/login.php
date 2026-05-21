<?php
// ============================================================
// auth/login.php
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Nếu đã đăng nhập rồi thì về dashboard
if (isLoggedIn()) {
    redirect('views/dashboard.php');
}

$controller = new AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->login();
}

$errorMsg   = getFlash('error');
$successMsg = getFlash('success');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng nhập | <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
<style>
  body { background: #f8f9fa; min-height: 100vh; }
  .login-card { border-radius: 20px; border: 1px solid rgba(0,0,0,0.1); background: #ffffff; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
  .brand-logo { width: 64px; height: 64px; background: linear-gradient(135deg, #e63946, #dc3545); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; margin: 0 auto 12px; box-shadow: 0 8px 24px rgba(220,53,69,0.3); }
  .form-control { background: #ffffff; border: 1px solid rgba(0,0,0,0.15); color: #0f172a; border-radius: 10px; }
  .form-control:focus { background: #ffffff; border-color: #dc3545; color: #0f172a; box-shadow: 0 0 0 3px rgba(220,53,69,0.15); }
  .form-control::placeholder { color: #adb5bd; }
  .btn-login { background: linear-gradient(135deg, #e63946, #c1121f); border: none; border-radius: 10px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.3s; color: #fff; }
  .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(220,53,69,0.4); color: #fff; }
  .form-label { color: #334155; font-size: 0.85rem; font-weight: 500; }
  h4 { color: #0f172a; }
  .text-muted-light { color: #64748b !important; font-size: 0.85rem; }
  .link-register { color: #dc3545; text-decoration: none; }
  .link-register:hover { color: #c1121f; text-decoration: underline; }
</style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container" style="max-width:440px; padding: 24px 16px;">

  <div class="login-card p-4 p-md-5">
    <div class="text-center mb-4">
      <div class="brand-logo"><i class="bi bi-building"></i></div>
      <h4 class="fw-bold mb-1">Đăng Nhập Hệ Thống</h4>
      <p class="text-muted-light">Quản Lý Phòng Thực Hành Nhà A3</p>
    </div>

    <?php if ($errorMsg): ?>
      <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?= $errorMsg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if ($successMsg): ?>
      <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-2"></i><?= $successMsg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="mb-3">
        <label for="email" class="form-label"><i class="bi bi-envelope me-1"></i>Email</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="admin@a3.edu.vn" required autocomplete="email">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>Mật khẩu</label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
          <button class="btn btn-outline-secondary" type="button" id="togglePwd" style="border-color:rgba(255,255,255,0.15);color:#94a3b8;">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <div class="d-grid mt-4">
        <button type="submit" class="btn btn-login btn-primary btn-lg text-white">
          <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
        </button>
      </div>
    </form>

    <hr style="border-color:rgba(255,255,255,0.1); margin:24px 0;">
    <p class="text-center text-muted-light mb-0">
      Chưa có tài khoản?
      <a href="<?= BASE_URL ?>/auth/register.php" class="link-register fw-semibold">Đăng ký ngay</a>
    </p>


  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function(){
  const pwd = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  if(pwd.type === 'password'){ pwd.type='text'; icon.className='bi bi-eye-slash'; }
  else { pwd.type='password'; icon.className='bi bi-eye'; }
});
</script>
</body>
</html>

