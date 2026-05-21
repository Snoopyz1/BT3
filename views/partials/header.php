<?php
// ============================================================
// views/partials/header.php — HTML head & navbar
// ============================================================
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', APP_NAME);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= PAGE_TITLE ?> | <?= APP_NAME ?></title>
<meta name="description" content="Hệ thống quản lý phòng thực hành Nhà A3 - PTIT">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
  <div class="container-fluid px-4">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/views/dashboard.php">
      <div class="brand-icon"><i class="bi bi-building-fill-check"></i></div>
      <span class="brand-name d-none d-md-inline">Nhà A3</span>
    </a>

    <!-- Toggle mobile -->
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Right side -->
    <div class="ms-auto d-flex align-items-center gap-3">
      <!-- Notifications bell -->
      <div class="dropdown">
        <button class="btn btn-sm btn-icon position-relative" data-bs-toggle="dropdown">
          <i class="bi bi-bell fs-5 text-muted"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge" style="display:none;font-size:9px;"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end notif-dropdown" id="notifDropdown">
          <li><h6 class="dropdown-header">Thông báo</h6></li>
          <li><span class="dropdown-item-text text-muted small">Không có thông báo mới</span></li>
        </ul>
      </div>

      <!-- User dropdown -->
      <div class="dropdown">
        <button class="btn d-flex align-items-center gap-2 user-menu-btn" data-bs-toggle="dropdown">
          <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
          </div>
          <span class="d-none d-lg-block text-dark small"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
          <i class="bi bi-chevron-down text-muted small d-none d-lg-block"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end user-dropdown">
          <li>
            <div class="px-3 py-2 border-bottom" style="border-color:rgba(0,0,0,0.1)!important;">
              <p class="mb-0 fw-semibold text-white small"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></p>
              <p class="mb-0 text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></p>
              <span class="badge mt-1" style="background:rgba(59,130,246,0.3);color:#60a5fa;"><?= getRoleLabel($_SESSION['role'] ?? '') ?></span>
            </div>
          </li>
          <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Hồ sơ cá nhân</a></li>
          <li><hr class="dropdown-divider" style="border-color:rgba(0,0,0,0.08);"></li>
          <li>
            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
              <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<?php
function getRoleLabel(string $role): string {
    return match($role) {
        'admin'      => 'Quản trị viên',
        'teacher'    => 'Giáo viên',
        'technician' => 'Kỹ thuật viên',
        'student'    => 'Sinh viên',
        default      => 'Người dùng',
    };
}
?>



