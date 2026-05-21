<?php
// ============================================================
// views/reports/index.php
// ============================================================
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
requireLogin();

require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../../models/RoomModel.php';
require_once __DIR__ . '/../../models/DeviceModel.php';

$ctrl        = new ReportController();
$roomModel   = new RoomModel();
$deviceModel = new DeviceModel();

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') $ctrl->create();
    if ($action === 'update_status' && $id > 0) $ctrl->updateStatus($id);
}
if ($action === 'delete' && $id > 0 && hasRole('admin')) $ctrl->delete($id);

$data    = $ctrl->index();
$reports = $data['reports'];
$total   = $data['total'];
$rooms   = $roomModel->getActiveRooms();
$devices = $deviceModel->getAll(200);

$severityLabels = ['low'=>'Thấp','medium'=>'Trung bình','high'=>'Cao','critical'=>'Nghiêm trọng'];
$err = getFlash('error');
$suc = getFlash('success');

define('PAGE_TITLE', 'Báo Cáo Sự Cố');
require_once __DIR__ . '/../partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Báo Cáo Sự Cố</h1>
      <p class="page-subtitle mb-0">Quản lý và theo dõi sự cố phòng & thiết bị</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#createReportModal">
      <i class="bi bi-plus-circle me-1"></i>Tạo Báo Cáo
    </button>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
  <?php if ($suc): ?><div class="alert alert-success"><?= $suc ?></div><?php endif; ?>

  <!-- Filter -->
  <div class="app-card mb-3 p-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(0,0,0,0.06);border-color:rgba(0,0,0,0.12);color:#64748b;"><i class="bi bi-search"></i></span>
          <input type="text" id="tableSearch" class="form-control" placeholder="Tìm báo cáo...">
        </div>
      </div>
      <div class="col-md-3">
        <select id="statusFilter" class="form-select">
          <option value="">Tất cả trạng thái</option>
          <option value="open">Mới tạo</option>
          <option value="processing">Đang xử lý</option>
          <option value="resolved">Đã xử lý</option>
        </select>
      </div>
      <div class="col-md-4 text-end text-muted small">
        Tổng: <strong class="text-white"><?= $total ?></strong> báo cáo
      </div>
    </div>
  </div>

  <!-- Reports Table -->
  <div class="app-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table app-table mb-0">
          <thead><tr>
            <th>#</th><th>Phòng</th><th>Thiết bị</th><th>Tiêu đề</th>
            <th>Mức độ</th><th>Người báo cáo</th><th>Ngày tạo</th><th>Trạng thái</th><th>Thao tác</th>
          </tr></thead>
          <tbody>
            <?php if (empty($reports)): ?>
              <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>Không có sự cố nào.</td></tr>
            <?php else: ?>
              <?php foreach ($reports as $r): ?>
                <tr data-status="<?= $r['status'] ?>">
                  <td class="text-muted small"><?= $r['id'] ?></td>
                  <td>
                    <?php if ($r['room_code']): ?>
                      <span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;"><?= htmlspecialchars($r['room_code']) ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($r['device_name'] ?? '—') ?></td>
                  <td class="fw-medium"><?= htmlspecialchars($r['title']) ?></td>
                  <td>
                    <?php
                    $sev = $r['severity'];
                    $sevBg = ['low'=>'success','medium'=>'warning','high'=>'orange','critical'=>'danger'];
                    $bg = $sevBg[$sev] ?? 'secondary';
                    echo '<span class="badge bg-'.$bg.'-subtle text-'.$bg.'">'.($severityLabels[$sev] ?? $sev).'</span>';
                    ?>
                  </td>
                  <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                  <td class="text-muted small"><?= formatDateTime($r['created_at']) ?></td>
                  <td><?= statusBadge($r['status']) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <?php if (hasRole(['admin','technician']) && $r['status'] !== 'resolved'): ?>
                        <button class="btn btn-sm btn-outline-info"
                                data-bs-toggle="modal" data-bs-target="#updateReportModal<?= $r['id'] ?>"
                                title="Cập nhật">
                          <i class="bi bi-pencil-square"></i>
                        </button>
                      <?php endif; ?>
                      <?php if (hasRole('admin')): ?>
                        <a href="?action=delete&id=<?= $r['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           data-confirm="Xóa báo cáo này?">
                          <i class="bi bi-trash"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>

                <!-- Update Status Modal -->
                <?php if (hasRole(['admin','technician'])): ?>
                <div class="modal fade" id="updateReportModal<?= $r['id'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                      <form method="POST" action="?action=update_status&id=<?= $r['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <div class="modal-header">
                          <h5 class="modal-title" style="font-size:1rem;"><i class="bi bi-tools me-2"></i>Cập Nhật Sự Cố</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <p class="text-muted small mb-3"><?= htmlspecialchars(mb_substr($r['title'],0,60)) ?></p>
                          <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                              <option value="open"       <?= $r['status']==='open'?'selected':'' ?>>Mới tạo</option>
                              <option value="processing" <?= $r['status']==='processing'?'selected':'' ?>>Đang xử lý</option>
                              <option value="resolved"   <?= $r['status']==='resolved'?'selected':'' ?>>Đã xử lý</option>
                            </select>
                          </div>
                          <div>
                            <label class="form-label">Ghi chú giải quyết</label>
                            <textarea name="resolution" class="form-control" rows="3"
                              placeholder="Mô tả cách giải quyết..."><?= htmlspecialchars($r['resolution'] ?? '') ?></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                          <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
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

<!-- Create Report Modal -->
<div class="modal fade" id="createReportModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=create">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Tạo Báo Cáo Sự Cố</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Phòng liên quan</label>
              <select name="room_id" class="form-select">
                <option value="">Chọn phòng (nếu có)</option>
                <?php foreach ($rooms as $rm): ?>
                  <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['room_code']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Thiết bị liên quan</label>
              <select name="device_id" class="form-select">
                <option value="">Chọn thiết bị (nếu có)</option>
                <?php foreach ($devices as $dv): ?>
                  <option value="<?= $dv['id'] ?>">[<?= htmlspecialchars($dv['room_code']??'') ?>] <?= htmlspecialchars($dv['device_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Tiêu đề sự cố *</label>
              <input type="text" name="title" class="form-control" placeholder="Tóm tắt sự cố..." required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mức độ nghiêm trọng</label>
              <select name="severity" class="form-select">
                <option value="low">Thấp</option>
                <option value="medium" selected>Trung bình</option>
                <option value="high">Cao</option>
                <option value="critical">Nghiêm trọng</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Mô tả chi tiết *</label>
              <textarea name="description" class="form-control" rows="4"
                placeholder="Mô tả chi tiết sự cố, thời gian xảy ra, tác động..." required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i>Gửi báo cáo</button>
        </div>
      </form>
    </div>
  </div>
</div>


