<?php
// ============================================================
// views/logs.php — System activity log
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireRole('admin');

$db   = Database::getConnection();
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$total = (int) $db->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$stmt  = $db->prepare(
    "SELECT l.*, u.full_name, u.email
     FROM logs l
     LEFT JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC LIMIT ? OFFSET ?"
);
$stmt->execute([$limit, $offset]);
$logs = $stmt->fetchAll();

$actionIcons = [
    'login'              => ['bi-box-arrow-in-right','text-success'],
    'logout'             => ['bi-box-arrow-right','text-muted'],
    'create_room'        => ['bi-building-add','text-primary'],
    'update_room'        => ['bi-pencil-square','text-warning'],
    'delete_room'        => ['bi-building-x','text-danger'],
    'create_device'      => ['bi-cpu','text-primary'],
    'update_device'      => ['bi-pencil','text-warning'],
    'delete_device'      => ['bi-trash','text-danger'],
    'create_schedule'    => ['bi-calendar-plus','text-primary'],
    'approve_schedule'   => ['bi-calendar-check','text-success'],
    'reject_schedule'    => ['bi-calendar-x','text-danger'],
    'borrow_device'      => ['bi-arrow-up-right-circle','text-warning'],
    'return_device'      => ['bi-arrow-return-left','text-success'],
    'create_report'      => ['bi-exclamation-triangle','text-danger'],
    'update_report_status'=> ['bi-tools','text-info'],
    'create_user'        => ['bi-person-plus','text-primary'],
    'update_user'        => ['bi-person-gear','text-warning'],
    'delete_user'        => ['bi-person-x','text-danger'],
];

define('PAGE_TITLE', 'Nhật Ký Hệ Thống');
require_once __DIR__ . '/partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<div class="main-content">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-journal-text me-2 text-info"></i>Nhật Ký Hệ Thống</h1>
      <p class="page-subtitle mb-0">Theo dõi mọi hoạt động trong hệ thống</p>
    </div>
    <span class="badge bg-info-subtle text-info px-3 py-2">Tổng: <?= number_format($total) ?> bản ghi</span>
  </div>

  <div class="app-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Hoạt Động Gần Đây</h6>
      <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Tìm kiếm..." style="width:200px;">
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table app-table mb-0">
          <thead><tr>
            <th>#</th><th>Người dùng</th><th>Hành động</th><th>Chi tiết</th><th>Thời gian</th>
          </tr></thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Chưa có nhật ký nào.</td></tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <?php
                $iconInfo = $actionIcons[$log['action']] ?? ['bi-circle','text-muted'];
                ?>
                <tr>
                  <td class="text-muted small"><?= $log['id'] ?></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div style="width:28px;height:28px;background:linear-gradient(135deg,#9b2226,#dc3545);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;color:#fff;flex-shrink:0;">
                        <?= strtoupper(substr($log['full_name']??'?',0,1)) ?>
                      </div>
                      <div>
                        <p class="mb-0 small fw-medium"><?= htmlspecialchars($log['full_name'] ?? 'Hệ thống') ?></p>
                        <p class="mb-0 text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($log['email'] ?? '') ?></p>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="<?= $iconInfo[1] ?>">
                      <i class="bi <?= $iconInfo[0] ?> me-1"></i>
                      <span class="font-monospace small"><?= htmlspecialchars($log['action']) ?></span>
                    </span>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($log['detail'] ?: '—') ?></td>
                  <td class="text-muted small"><?= formatDateTime($log['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-4 d-flex justify-content-center">
    <?= paginate($total, $limit, $page, '?') ?>
  </div>

</div><!-- /main-content -->
<?php require_once __DIR__ . '/partials/footer.php'; ?>


