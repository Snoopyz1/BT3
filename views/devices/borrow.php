<?php
// ============================================================
// views/devices/borrow.php
// ============================================================
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
requireLogin();

require_once __DIR__ . '/../../controllers/BorrowController.php';
require_once __DIR__ . '/../../models/DeviceModel.php';

$ctrl        = new BorrowController();
$deviceModel = new DeviceModel();

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'borrow') $ctrl->create();
    if ($action === 'return' && $id > 0) $ctrl->returnDevice($id);
}

$data    = $ctrl->index();
$borrows = $data['borrows'];
$total   = $data['total'];

$availableDevices = $deviceModel->getAvailableDevices();
$preselectedDeviceId = (int) ($_GET['device_id'] ?? 0);

$err = getFlash('error');
$suc = getFlash('success');

define('PAGE_TITLE', 'Mượn/Trả Thiết Bị');
require_once __DIR__ . '/../partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Mượn / Trả Thiết Bị</h1>
      <p class="page-subtitle mb-0">Theo dõi lịch sử mượn trả thiết bị</p>
    </div>
    <button class="btn btn-warning text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#borrowModal">
      <i class="bi bi-arrow-up-right-circle me-1"></i>Đăng Ký Mượn
    </button>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
  <?php if ($suc): ?><div class="alert alert-success"><?= $suc ?></div><?php endif; ?>

  <!-- Borrow History Table -->
  <div class="app-card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i>Lịch Sử Mượn/Trả</h6>
      <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Tìm kiếm..." style="width:200px;">
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table app-table mb-0">
          <thead><tr>
            <th>#</th><th>Phòng</th><th>Thiết bị</th><th>Người mượn</th>
            <th>Ngày mượn</th><th>Dự kiến trả</th><th>Ngày trả</th><th>Trạng thái</th><th>Thao tác</th>
          </tr></thead>
          <tbody>
            <?php if (empty($borrows)): ?>
              <tr><td colspan="9" class="text-center text-muted py-4">Chưa có lịch sử mượn thiết bị.</td></tr>
            <?php else: ?>
              <?php foreach ($borrows as $b): ?>
                <?php
                $isOverdue = $b['status'] === 'borrowed' && $b['expected_return_date'] < date('Y-m-d');
                ?>
                <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                  <td class="text-muted small"><?= $b['id'] ?></td>
                  <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;"><?= htmlspecialchars($b['room_code'] ?? 'N/A') ?></span></td>
                  <td class="fw-medium"><?= htmlspecialchars($b['device_name']) ?></td>
                  <td><?= htmlspecialchars($b['borrower_name']) ?></td>
                  <td class="text-muted small"><?= formatDate($b['borrow_date']) ?></td>
                  <td class="small <?= $isOverdue ? 'text-danger fw-semibold' : 'text-muted' ?>">
                    <?= formatDate($b['expected_return_date']) ?>
                    <?php if ($isOverdue): ?><i class="bi bi-exclamation-triangle ms-1"></i><?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= $b['actual_return_date'] ? formatDate($b['actual_return_date']) : '—' ?></td>
                  <td>
                    <?= statusBadge($b['status']) ?>
                    <?php if ($isOverdue): ?><span class="badge bg-danger ms-1">QUÁ HẠN</span><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($b['status'] === 'borrowed' && ($b['user_id'] == $_SESSION['user_id'] || hasRole(['admin','technician']))): ?>
                      <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#returnModal<?= $b['id'] ?>">
                        <i class="bi bi-arrow-return-left me-1"></i>Trả
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>

                <!-- Return Modal -->
                <?php if ($b['status'] === 'borrowed' && ($b['user_id'] == $_SESSION['user_id'] || hasRole(['admin','technician']))): ?>
                <div class="modal fade" id="returnModal<?= $b['id'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                      <form method="POST" action="?action=return&id=<?= $b['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <div class="modal-header">
                          <h5 class="modal-title" style="font-size:1rem;"><i class="bi bi-arrow-return-left me-2 text-success"></i>Xác Nhận Trả Thiết Bị</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <p class="text-muted small mb-3">Thiết bị: <strong class="text-white"><?= htmlspecialchars($b['device_name']) ?></strong></p>
                          <div class="mb-3">
                            <label class="form-label">Ngày trả thực tế</label>
                            <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                          </div>
                          <div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="return_note" class="form-control" rows="2" placeholder="Tình trạng khi trả..."></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                          <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle me-1"></i>Xác nhận trả</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div><!-- /main-content -->
<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Borrow Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=borrow">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-arrow-up-right-circle me-2 text-warning"></i>Đăng Ký Mượn Thiết Bị</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Thiết bị *</label>
              <select name="device_id" class="form-select" required>
                <option value="">Chọn thiết bị sẵn sàng...</option>
                <?php foreach ($availableDevices as $dv): ?>
                  <option value="<?= $dv['id'] ?>" <?= $dv['id']==$preselectedDeviceId?'selected':'' ?>>
                    [<?= htmlspecialchars($dv['room_code'] ?? 'N/A') ?>] <?= htmlspecialchars($dv['device_name']) ?>
                    <?php if ($dv['serial_number']): ?>(<?= htmlspecialchars($dv['serial_number']) ?>)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ngày mượn</label>
              <input type="date" name="borrow_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ngày trả dự kiến *</label>
              <input type="date" name="expected_return_date" class="form-control"
                     min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Mục đích mượn</label>
              <textarea name="purpose" class="form-control" rows="3" placeholder="Mục đích sử dụng thiết bị..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-warning text-dark fw-semibold">
            <i class="bi bi-check-circle me-1"></i>Xác nhận mượn
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


