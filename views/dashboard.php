<?php
// ============================================================
// views/dashboard.php
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireLogin();

require_once __DIR__ . '/../models/RoomModel.php';
require_once __DIR__ . '/../models/DeviceModel.php';
require_once __DIR__ . '/../models/ScheduleModel.php';
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../models/BorrowModel.php';
require_once __DIR__ . '/../models/UserModel.php';

$roomModel     = new RoomModel();
$deviceModel   = new DeviceModel();
$scheduleModel = new ScheduleModel();
$reportModel   = new ReportModel();
$borrowModel   = new BorrowModel();
$userModel     = new UserModel();

// Stats & Recent data based on Role
$isAdmin = hasRole('admin') || hasRole('teacher') || hasRole('technician');

if ($isAdmin) {
    $totalRooms    = $roomModel->count();
    $totalDevices  = $deviceModel->count();
    $totalUsers    = $userModel->count();
    $pendingSchedules = $scheduleModel->countPending();
    $openReports   = $reportModel->countOpen();
    $activeBorrows = $borrowModel->countBorrowed();

    $recentSchedules = $scheduleModel->getAll(5);
    $recentReports   = $reportModel->getRecent(5);
} else {
    // Student sees their own stats
    $userId = $_SESSION['user_id'];
    $mySchedules = $scheduleModel->getByUser($userId);
    $pendingSchedules = count(array_filter($mySchedules, fn($s) => $s['status'] === 'pending'));
    
    // Borrowed by user manually querying from DB for count (fast enough via array in this small app)
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM device_borrow WHERE user_id = ? AND status = 'borrowed'");
    $stmt->execute([$userId]);
    $activeBorrows = $stmt->fetchColumn();

    $stmt2 = $db->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status != 'resolved'");
    $stmt2->execute([$userId]);
    $openReports = $stmt2->fetchColumn();

    $recentSchedules = array_slice($mySchedules, 0, 5);
    
    // Fetch recent reports by user
    $stmt3 = $db->prepare("SELECT r.*, rm.room_code FROM reports r LEFT JOIN rooms rm ON r.room_id = rm.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 5");
    $stmt3->execute([$userId]);
    $recentReports = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}

// Global data needed for everyone
$todaySchedules  = $scheduleModel->getByDate(date('Y-m-d'));
$roomStats    = $roomModel->countByStatus();
$deviceStats  = $deviceModel->countByStatus();

define('PAGE_TITLE', 'Dashboard');
require_once __DIR__ . '/partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>

<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="main-content">

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1">
        <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
      </h1>
      <p class="page-subtitle mb-0">
        Xin chào, <strong class="text-white"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>!
        Hôm nay là <?= date('l, d/m/Y') ?>
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>/views/schedules/index.php" class="btn btn-primary btn-sm">
        <i class="bi bi-calendar-plus me-1"></i>Đặt lịch
      </a>
      <a href="<?= BASE_URL ?>/views/reports/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-exclamation-triangle me-1"></i>Báo cáo sự cố
      </a>
    </div>
  </div>

  <?php $err = getFlash('error'); $suc = getFlash('success');
    if ($err): ?><div class="alert alert-danger fade-in"><?= $err ?></div><?php endif;
    if ($suc): ?><div class="alert alert-success fade-in"><?= $suc ?></div><?php endif; ?>

  <!-- Stat Cards Row -->
  <div class="row g-3 mb-4">
    <?php if ($isAdmin): ?>
    <div class="col-sm-6 col-xl-4 fade-in fade-in-1">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-building"></i></div>
        <div>
          <div class="stat-value" data-target="<?= $totalRooms ?>"><?= $totalRooms ?></div>
          <div class="stat-label">Tổng số phòng</div>
          <div class="mt-1">
            <span class="badge bg-success-subtle text-success"><?= $roomStats['active'] ?? 0 ?> hoạt động</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4 fade-in fade-in-2">
      <div class="stat-card">
        <div class="stat-icon cyan"><i class="bi bi-cpu"></i></div>
        <div>
          <div class="stat-value" data-target="<?= $totalDevices ?>"><?= $totalDevices ?></div>
          <div class="stat-label">Tổng thiết bị</div>
          <div class="mt-1">
            <span class="badge bg-warning-subtle text-warning"><?= $deviceStats['borrowed'] ?? 0 ?> đang mượn</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4 fade-in fade-in-3">
      <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-people"></i></div>
        <div>
          <div class="stat-value" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div>
          <div class="stat-label">Người dùng</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="col-sm-6 col-xl-4 fade-in fade-in-1">
      <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
        <div>
          <div class="stat-value" data-target="<?= $pendingSchedules ?>"><?= $pendingSchedules ?></div>
          <div class="stat-label">Lịch chờ duyệt <?= !$isAdmin ? '(Của tôi)' : '' ?></div>
          <?php if($isAdmin): ?>
          <div class="mt-1">
            <span class="badge bg-success-subtle text-success"><?= $scheduleStats['approved'] ?? 0 ?> đã duyệt</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4 fade-in fade-in-2">
      <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
          <div class="stat-value" data-target="<?= $openReports ?>"><?= $openReports ?></div>
          <div class="stat-label">Sự cố chưa xử lý <?= !$isAdmin ? '(Của tôi)' : '' ?></div>
          <?php if($isAdmin): ?>
          <div class="mt-1">
            <span class="badge bg-success-subtle text-success"><?= $reportStats['resolved'] ?? 0 ?> đã xử lý</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4 fade-in fade-in-3">
      <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-arrow-left-right"></i></div>
        <div>
          <div class="stat-value" data-target="<?= $activeBorrows ?>"><?= $activeBorrows ?></div>
          <div class="stat-label">Thiết bị đang mượn <?= !$isAdmin ? '(Của tôi)' : '' ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2-column content -->
  <div class="row g-4">

    <!-- Today's Schedule -->
    <div class="col-lg-7">
      <div class="app-card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-day me-2 text-primary"></i>Lịch hôm nay
            <span class="badge bg-primary ms-2"><?= date('d/m/Y') ?></span>
          </h6>
          <a href="<?= BASE_URL ?>/views/schedules/index.php" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($todaySchedules)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Không có lịch nào hôm nay
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table app-table mb-0">
                <thead><tr>
                  <th>Phòng</th><th>Tiêu đề</th><th>Giờ</th><th>Người đặt</th><th>Trạng thái</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($todaySchedules as $s): ?>
                    <tr>
                      <td><span class="badge" style="background:rgba(59,130,246,0.2);color:#60a5fa;"><?= htmlspecialchars($s['room_code']) ?></span></td>
                      <td><?= htmlspecialchars($s['title']) ?></td>
                      <td class="text-muted small"><?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?></td>
                      <td><?= htmlspecialchars($s['user_name']) ?></td>
                      <td><?= statusBadge($s['status']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Incident Reports -->
    <div class="col-lg-5">
      <div class="app-card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-circle me-2 text-danger"></i>Sự cố gần đây</h6>
          <a href="<?= BASE_URL ?>/views/reports/index.php" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($recentReports)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>Không có sự cố nào
            </div>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($recentReports as $r): ?>
                <li class="list-group-item" style="background:transparent;border-color:var(--border-color);padding:12px 20px;">
                  <div class="d-flex align-items-start gap-2">
                    <div class="flex-grow-1 overflow-hidden">
                      <p class="mb-1 fw-medium text-truncate" style="color:var(--text-primary);">
                        <?= htmlspecialchars($r['title']) ?>
                      </p>
                      <small class="text-muted">
                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($r['room_code'] ?? 'N/A') ?>
                        &nbsp;·&nbsp;<?= htmlspecialchars($r['reporter_name']) ?>
                        &nbsp;·&nbsp;<?= formatDateTime($r['created_at']) ?>
                      </small>
                    </div>
                    <?= statusBadge($r['status']) ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Recent Schedules Table -->
    <div class="col-12">
      <div class="app-card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-warning"></i>Lịch đặt phòng gần đây</h6>
          <a href="<?= BASE_URL ?>/views/schedules/index.php" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table app-table mb-0">
              <thead><tr>
                <th>#</th><th>Phòng</th><th>Tiêu đề</th><th>Ngày</th><th>Giờ</th><th>Người đặt</th><th>Mục đích</th><th>Trạng thái</th>
              </tr></thead>
              <tbody>
                <?php if (empty($recentSchedules)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">Chưa có lịch đặt phòng nào.</td></tr>
                <?php else: ?>
                  <?php foreach ($recentSchedules as $s): ?>
                    <tr>
                      <td class="text-muted small"><?= $s['id'] ?></td>
                      <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;"><?= htmlspecialchars($s['room_code']) ?></span></td>
                      <td><?= htmlspecialchars($s['title']) ?></td>
                      <td class="text-muted small"><?= formatDate($s['date']) ?></td>
                      <td class="text-muted small"><?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?></td>
                      <td><?= htmlspecialchars($s['user_name']) ?></td>
                      <td class="text-muted small"><?= htmlspecialchars(mb_substr($s['purpose'],0,30)) ?></td>
                      <td><?= statusBadge($s['status']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /row -->
</div><!-- /main-content -->
<?php require_once __DIR__ . '/partials/footer.php'; ?>


