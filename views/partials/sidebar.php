<?php
// ============================================================
// views/partials/sidebar.php
// ============================================================
$currentFile = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'student';

$navItems = [
    ['icon'=>'bi-speedometer2','label'=>'Dashboard',       'href'=>BASE_URL.'/views/dashboard.php',        'file'=>'dashboard.php', 'roles'=>['admin','teacher','technician','student']],
    ['icon'=>'bi-building',   'label'=>'Phòng Thực Hành', 'href'=>BASE_URL.'/views/rooms/index.php',       'file'=>'index.php',     'roles'=>['admin','teacher','technician','student']],
    ['icon'=>'bi-calendar3',  'label'=>'Lịch Đặt Phòng',  'href'=>BASE_URL.'/views/schedules/index.php',   'file'=>'index.php',     'roles'=>['admin','teacher','technician','student']],
    ['icon'=>'bi-cpu',        'label'=>'Thiết Bị',         'href'=>BASE_URL.'/views/devices/index.php',     'file'=>'index.php',     'roles'=>['admin','teacher','technician']],
    ['icon'=>'bi-arrow-left-right','label'=>'Mượn/Trả TBị','href'=>BASE_URL.'/views/devices/borrow.php',  'file'=>'borrow.php',    'roles'=>['admin','teacher','technician','student']],
    ['icon'=>'bi-exclamation-triangle','label'=>'Báo Cáo Sự Cố','href'=>BASE_URL.'/views/reports/index.php','file'=>'index.php',   'roles'=>['admin','teacher','technician','student']],
    ['icon'=>'bi-people',     'label'=>'Người Dùng',       'href'=>BASE_URL.'/views/users/index.php',       'file'=>'index.php',     'roles'=>['admin']],
    ['icon'=>'bi-journal-text','label'=>'Nhật Ký Hệ Thống','href'=>BASE_URL.'/views/logs.php',             'file'=>'logs.php',      'roles'=>['admin']],
];
?>

<!-- SIDEBAR — Desktop -->
<aside class="app-sidebar d-none d-lg-flex flex-column">
  <div class="sidebar-inner flex-grow-1 py-3">
    <ul class="nav flex-column gap-1 px-2">
      <?php foreach ($navItems as $item): ?>
        <?php if (!in_array($role, $item['roles'])) continue; ?>
        <li class="nav-item">
          <a href="<?= $item['href'] ?>" class="nav-link sidebar-link <?= $currentFile === $item['file'] ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?> sidebar-icon"></i>
            <span><?= $item['label'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="sidebar-footer px-3 py-3">
    <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:rgba(0,0,0,0.05);">
      <div class="user-avatar-sm"><?= strtoupper(substr($_SESSION['user_name']??'U',0,1)) ?></div>
      <div class="overflow-hidden">
        <p class="mb-0 text-white small fw-medium text-truncate"><?= htmlspecialchars($_SESSION['user_name']??'') ?></p>
        <p class="mb-0 text-muted" style="font-size:0.7rem;"><?= getRoleLabel($role) ?></p>
      </div>
    </div>
  </div>
</aside>

<!-- SIDEBAR — Mobile Offcanvas -->
<div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="sidebarOffcanvas">
  <div class="offcanvas-header border-bottom" style="border-color:rgba(0,0,0,0.1)!important;">
    <h6 class="offcanvas-title text-white fw-bold"><i class="bi bi-building-fill-check me-2"></i>Nhà A3</h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-2">
    <ul class="nav flex-column gap-1">
      <?php foreach ($navItems as $item): ?>
        <?php if (!in_array($role, $item['roles'])) continue; ?>
        <li class="nav-item">
          <a href="<?= $item['href'] ?>" class="nav-link sidebar-link <?= $currentFile === $item['file'] ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?> sidebar-icon"></i>
            <span><?= $item['label'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
      <li class="mt-2">
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link sidebar-link text-danger">
          <i class="bi bi-box-arrow-right sidebar-icon"></i>
          <span>Đăng xuất</span>
        </a>
      </li>
    </ul>
  </div>
</div>


