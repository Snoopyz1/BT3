<?php
// ============================================================
// views/schedules/index.php
// ============================================================
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
requireLogin();

require_once __DIR__ . '/../../controllers/ScheduleController.php';
require_once __DIR__ . '/../../models/RoomModel.php';
require_once __DIR__ . '/../../models/ScheduleModel.php';

$ctrl       = new ScheduleController();
$roomModel  = new RoomModel();
$schedModel = new ScheduleModel();

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create')  $ctrl->create();
    if ($action === 'approve' && $id > 0) $ctrl->approve($id);
    if ($action === 'reject'  && $id > 0) $ctrl->reject($id);
    if ($action === 'cancel'  && $id > 0) $ctrl->cancel($id);
}

// Week view
$weekStart = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
$weekStart = date('Y-m-d', strtotime($weekStart));
$weekEnd   = date('Y-m-d', strtotime($weekStart . ' +6 days'));
$weekSchedules = $schedModel->getByWeek($weekStart, $weekEnd);

// List
$data      = $ctrl->index();
$schedules = $data['schedules'];
$total     = $data['total'];

$rooms = $roomModel->getActiveRooms();
$err   = getFlash('error');
$suc   = getFlash('success');

define('PAGE_TITLE', 'Lịch Đặt Phòng');
require_once __DIR__ . '/../partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-calendar3 me-2 text-primary"></i>Lịch Đặt Phòng</h1>
      <p class="page-subtitle mb-0">Đặt và quản lý lịch sử dụng phòng thực hành</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
      <i class="bi bi-plus-circle me-1"></i>Đặt Lịch Mới
    </button>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
  <?php if ($suc): ?><div class="alert alert-success"><?= $suc ?></div><?php endif; ?>

  <!-- Week navigator -->
  <div class="app-card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-week me-2 text-primary"></i>Lịch Tuần</h6>
      <div class="d-flex align-items-center gap-2">
        <a href="?week=<?= date('Y-m-d', strtotime($weekStart . ' -7 days')) ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-chevron-left"></i> Tuần trước
        </a>
        <span class="badge bg-primary-subtle text-primary px-3 py-2">
          <?= formatDate($weekStart) ?> – <?= formatDate($weekEnd) ?>
        </span>
        <a href="?week=<?= date('Y-m-d', strtotime($weekStart . ' +7 days')) ?>" class="btn btn-sm btn-outline-secondary">
          Tuần sau <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
    <div class="card-body p-0">
      <!-- Week grid -->
      <?php
      $days = [];
      for ($i = 0; $i < 7; $i++) {
          $days[] = date('Y-m-d', strtotime($weekStart . " +$i days"));
      }
      $dayNames = ['T2','T3','T4','T5','T6','T7','CN'];
      // Group schedules by day
      $byDay = [];
      foreach ($weekSchedules as $ws) {
          $byDay[$ws['date']][] = $ws;
      }
      ?>
      <div class="table-responsive">
        <table class="table app-table mb-0" style="min-width:700px;">
          <thead><tr>
            <th style="width:110px;">Phòng</th>
            <?php foreach ($days as $i => $d): ?>
              <th class="text-center <?= $d===date('Y-m-d')?'text-primary':'' ?>">
                <?= $dayNames[$i] ?><br>
                <small><?= date('d/m', strtotime($d)) ?></small>
              </th>
            <?php endforeach; ?>
          </tr></thead>
          <tbody>
            <?php foreach ($rooms as $rm): ?>
              <tr>
                <td class="fw-medium"><?= htmlspecialchars($rm['room_code']) ?></td>
                <?php foreach ($days as $d): ?>
                  <td style="vertical-align:top;padding:6px;">
                    <?php
                    foreach ($byDay[$d] ?? [] as $ws) {
                        if ($ws['room_id'] != $rm['id']) continue;
                        $color = match($ws['status']) {
                            'approved' => 'rgba(34,197,94,0.15)',
                            'pending'  => 'rgba(245,158,11,0.15)',
                            'rejected' => 'rgba(239,68,68,0.15)',
                            default    => 'rgba(100,116,139,0.15)',
                        };
                        $textColor = match($ws['status']) {
                            'approved' => '#15803d', /* Dark green */
                            'pending'  => '#b45309', /* Dark orange */
                            'rejected' => '#b91c1c', /* Dark red */
                            default    => '#475569', /* Dark gray */
                        };
                        echo '<div style="background:'.$color.';border-left: 3px solid '.$textColor.';border-radius:4px;padding:4px 6px;margin-bottom:4px;font-size:0.7rem;">';
                        echo '<strong style="color:'.$textColor.';display:block;margin-bottom:2px;">'.htmlspecialchars(mb_substr($ws['title'],0,20)).'</strong>';
                        echo '<span class="text-muted" style="font-weight:500;">'.substr($ws['start_time'],0,5).' - '.substr($ws['end_time'],0,5).'</span>';
                        echo '</div>';
                    }
                    ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Schedule List -->
  <div class="app-card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Danh Sách Lịch Đặt</h6>
      <div class="d-flex gap-2">
        <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Tìm kiếm..." style="width:200px;">
        <select id="statusFilter" class="form-select form-select-sm" style="width:160px;">
          <option value="">Tất cả trạng thái</option>
          <option value="pending">Chờ duyệt</option>
          <option value="approved">Đã duyệt</option>
          <option value="rejected">Từ chối</option>
          <option value="cancelled">Đã hủy</option>
        </select>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table app-table mb-0">
          <thead><tr>
            <th>#</th><th>Phòng</th><th>Tiêu đề</th><th>Ngày</th><th>Giờ</th>
            <th>Người đặt</th><th>Mục đích</th><th>Trạng thái</th><th>Thao tác</th>
          </tr></thead>
          <tbody>
            <?php if (empty($schedules)): ?>
              <tr><td colspan="9" class="text-center text-muted py-4">Chưa có lịch đặt phòng nào.</td></tr>
            <?php else: ?>
              <?php foreach ($schedules as $s): ?>
                <tr data-status="<?= $s['status'] ?>">
                  <td class="text-muted small"><?= $s['id'] ?></td>
                  <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;"><?= htmlspecialchars($s['room_code']) ?></span></td>
                  <td class="fw-medium"><?= htmlspecialchars($s['title']) ?></td>
                  <td class="text-muted small"><?= formatDate($s['date']) ?></td>
                  <td class="text-muted small"><?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?></td>
                  <td><?= htmlspecialchars($s['user_name']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars(mb_substr($s['purpose'],0,30)) ?></td>
                  <td><?= statusBadge($s['status']) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <?php if (hasRole('admin') && $s['status'] === 'pending'): ?>
                        <form method="POST" action="?action=approve&id=<?= $s['id'] ?>" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <button type="submit" class="btn btn-sm btn-success" title="Phê duyệt" data-bs-toggle="tooltip" data-bs-title="Phê duyệt">
                            <i class="bi bi-check-circle"></i>
                          </button>
                        </form>
                        <form method="POST" action="?action=reject&id=<?= $s['id'] ?>" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="admin_note" value="Không đáp ứng yêu cầu.">
                          <button type="submit" class="btn btn-sm btn-danger" title="Từ chối">
                            <i class="bi bi-x-circle"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                      <?php if (($s['user_id'] == $_SESSION['user_id'] || hasRole('admin'))
                             && in_array($s['status'], ['pending','approved'])): ?>
                        <form method="POST" action="?action=cancel&id=<?= $s['id'] ?>" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <button type="submit" class="btn btn-sm btn-outline-warning"
                                  data-confirm="Hủy lịch đặt này?">
                            <i class="bi bi-slash-circle"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /main-content -->
<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Create Schedule Modal -->
<div class="modal fade" id="createScheduleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=create">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-calendar-plus me-2 text-primary"></i>Đặt Lịch Phòng Mới</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Phòng thực hành *</label>
              <select name="room_id" class="form-select" required>
                <option value="">Chọn phòng...</option>
                <?php foreach ($rooms as $rm): ?>
                  <option value="<?= $rm['id'] ?>" <?= ($_GET['room_id']??'')==$rm['id']?'selected':'' ?>>
                    <?= htmlspecialchars($rm['room_code'] . ' — ' . $rm['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Tiêu đề *</label>
              <input type="text" name="title" class="form-control" placeholder="VD: Buổi thực hành mạng máy tính" required>
            </div>
            <div class="col-12">
              <label class="form-label">Ngày *</label>
              <input type="date" id="date" name="date" class="form-control" required
                     min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" data-min-today>
            </div>
            <div class="col-md-6">
              <label class="form-label">Giờ bắt đầu *</label>
              <input type="time" id="start_time" name="start_time" class="form-control" required value="07:00">
            </div>
            <div class="col-md-6">
              <label class="form-label">Giờ kết thúc *</label>
              <input type="time" id="end_time" name="end_time" class="form-control" required value="09:00">
            </div>
            <div class="col-12">
              <label class="form-label">Mục đích sử dụng</label>
              <textarea name="purpose" class="form-control" rows="3" placeholder="Mô tả mục đích sử dụng phòng..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Đặt lịch</button>
        </div>
      </form>
    </div>
  </div>
</div>


