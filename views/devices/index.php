<?php
// ============================================================
// views/devices/index.php
// ============================================================
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
requireLogin();

require_once __DIR__ . '/../../controllers/DeviceController.php';
require_once __DIR__ . '/../../models/DeviceModel.php';
require_once __DIR__ . '/../../models/RoomModel.php';

$ctrl      = new DeviceController();
$roomModel = new RoomModel();

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') $ctrl->create();
    if ($action === 'edit' && $id > 0) $ctrl->update($id);
}
if ($action === 'delete' && $id > 0) $ctrl->delete($id);

$data    = $ctrl->index();
$devices = $data['devices'];
$total   = $data['total'];
$rooms   = $roomModel->getActiveRooms();

$deviceTypes = [
    'computer' => 'Máy tính',
    'projector'=> 'Máy chiếu',
    'switch'   => 'Switch/Hub',
    'printer'  => 'Máy in',
    'camera'   => 'Camera',
    'furniture'=> 'Bàn ghế',
    'other'    => 'Khác',
];

$err = getFlash('error');
$suc = getFlash('success');

define('PAGE_TITLE', 'Thiết Bị');
require_once __DIR__ . '/../partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-cpu me-2 text-cyan"></i>Quản Lý Thiết Bị</h1>
      <p class="page-subtitle mb-0">Toàn bộ thiết bị trong các phòng thực hành</p>
    </div>
    <?php if (hasRole(['admin','technician'])): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDeviceModal">
        <i class="bi bi-plus-circle me-1"></i>Thêm Thiết Bị
      </button>
    <?php endif; ?>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
  <?php if ($suc): ?><div class="alert alert-success"><?= $suc ?></div><?php endif; ?>

  <!-- Filter bar -->
  <div class="app-card mb-3 p-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(0,0,0,0.06);border-color:rgba(0,0,0,0.12);color:#64748b;"><i class="bi bi-search"></i></span>
          <input type="text" id="tableSearch" class="form-control" placeholder="Tìm thiết bị...">
        </div>
      </div>
      <div class="col-md-3">
        <select id="deviceTypeFilter" class="form-select">
          <option value="">Tất cả loại</option>
          <?php foreach ($deviceTypes as $k=>$v): ?>
            <option value="<?= $k ?>"><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select id="statusFilter" class="form-select">
          <option value="">Tất cả trạng thái</option>
          <option value="available">Sẵn sàng</option>
          <option value="borrowed">Đang mượn</option>
          <option value="broken">Hỏng hóc</option>
        </select>
      </div>
      <div class="col-md-2 text-end text-muted small">
        Tổng: <strong class="text-white"><?= $total ?></strong>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="app-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table app-table mb-0">
          <thead><tr>
            <th>#</th><th>Phòng</th><th>Tên thiết bị</th><th>Loại</th>
            <th>Serial</th><th>Số lượng</th><th>Trạng thái</th><th>Thao tác</th>
          </tr></thead>
          <tbody>
            <?php if (empty($devices)): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">Chưa có thiết bị nào.</td></tr>
            <?php else: ?>
              <?php foreach ($devices as $d): ?>
                <tr data-device-type="<?= $d['device_type'] ?>" data-status="<?= $d['status'] ?>">
                  <td class="text-muted small"><?= $d['id'] ?></td>
                  <td>
                    <span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">
                      <?= htmlspecialchars($d['room_code'] ?? 'N/A') ?>
                    </span>
                  </td>
                  <td class="fw-medium"><?= htmlspecialchars($d['device_name']) ?></td>
                  <td>
                    <?php
                    $typeIcons = [
                      'computer'=>'bi-pc-display','projector'=>'bi-projector','switch'=>'bi-hdd-network',
                      'printer'=>'bi-printer','camera'=>'bi-camera-video','furniture'=>'bi-table','other'=>'bi-box',
                    ];
                    $icon = $typeIcons[$d['device_type']] ?? 'bi-box';
                    ?>
                    <span class="text-muted small"><i class="bi <?= $icon ?> me-1"></i><?= $deviceTypes[$d['device_type']] ?? $d['device_type'] ?></span>
                  </td>
                  <td class="text-muted small font-monospace"><?= htmlspecialchars($d['serial_number'] ?: '—') ?></td>
                  <td class="text-center"><?= $d['quantity'] ?></td>
                  <td><?= statusBadge($d['status']) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <?php if (hasRole(['admin','technician'])): ?>
                        <button class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editDeviceModal<?= $d['id'] ?>"
                                title="Sửa">
                          <i class="bi bi-pencil"></i>
                        </button>
                      <?php endif; ?>
                      <?php if (hasRole('admin')): ?>
                        <a href="?action=delete&id=<?= $d['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           data-confirm="Xóa thiết bị '<?= htmlspecialchars($d['device_name']) ?>'?">
                          <i class="bi bi-trash"></i>
                        </a>
                      <?php endif; ?>
                      <?php if ($d['status'] === 'available'): ?>
                        <a href="<?= BASE_URL ?>/views/devices/borrow.php?device_id=<?= $d['id'] ?>"
                           class="btn btn-sm btn-outline-warning" title="Mượn thiết bị">
                          <i class="bi bi-arrow-up-right-circle"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>

                <!-- Edit Device Modal -->
                <?php if (hasRole(['admin','technician'])): ?>
                <div class="modal fade" id="editDeviceModal<?= $d['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST" action="?action=edit&id=<?= $d['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <div class="modal-header">
                          <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Sửa Thiết Bị</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row g-3">
                            <div class="col-md-6">
                              <label class="form-label">Phòng *</label>
                              <select name="room_id" class="form-select" required>
                                <?php foreach ($rooms as $rm): ?>
                                  <option value="<?= $rm['id'] ?>" <?= $rm['id']==$d['room_id']?'selected':'' ?>>
                                    <?= htmlspecialchars($rm['room_code']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Loại thiết bị</label>
                              <select name="device_type" class="form-select">
                                <?php foreach ($deviceTypes as $k=>$v): ?>
                                  <option value="<?= $k ?>" <?= $k===$d['device_type']?'selected':'' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                              </select>
                            </div>
                            <div class="col-12">
                              <label class="form-label">Tên thiết bị *</label>
                              <input type="text" name="device_name" class="form-control" value="<?= htmlspecialchars($d['device_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label">Số serial</label>
                              <input type="text" name="serial_number" class="form-control" value="<?= htmlspecialchars($d['serial_number']) ?>">
                            </div>
                            <div class="col-md-3">
                              <label class="form-label">Số lượng</label>
                              <input type="number" name="quantity" class="form-control" value="<?= $d['quantity'] ?>" min="1">
                            </div>
                            <div class="col-md-3">
                              <label class="form-label">Trạng thái</label>
                              <select name="status" class="form-select">
                                <option value="available" <?= $d['status']==='available'?'selected':'' ?>>Sẵn sàng</option>
                                <option value="borrowed"  <?= $d['status']==='borrowed'?'selected':'' ?>>Đang mượn</option>
                                <option value="broken"    <?= $d['status']==='broken'?'selected':'' ?>>Hỏng hóc</option>
                                <option value="inactive"  <?= $d['status']==='inactive'?'selected':'' ?>>Không dùng</option>
                              </select>
                            </div>
                            <div class="col-12">
                              <label class="form-label">Mô tả</label>
                              <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($d['description']) ?></textarea>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
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

<!-- Create Device Modal -->
<?php if (hasRole(['admin','technician'])): ?>
<div class="modal fade" id="createDeviceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=create">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm Thiết Bị Mới</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Phòng *</label>
              <select name="room_id" class="form-select" required>
                <option value="">Chọn phòng...</option>
                <?php foreach ($rooms as $rm): ?>
                  <option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['room_code'] . ' — ' . $rm['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Loại thiết bị</label>
              <select name="device_type" class="form-select">
                <?php foreach ($deviceTypes as $k=>$v): ?>
                  <option value="<?= $k ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Tên thiết bị *</label>
              <input type="text" name="device_name" class="form-control" placeholder="VD: Máy tính HP EliteDesk 800 G5" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Số serial</label>
              <input type="text" name="serial_number" class="form-control" placeholder="VD: HP2024-001">
            </div>
            <div class="col-md-3">
              <label class="form-label">Số lượng</label>
              <input type="number" name="quantity" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select name="status" class="form-select">
                <option value="available">Sẵn sàng</option>
                <option value="broken">Hỏng hóc</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Mô tả</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Thông tin thêm về thiết bị..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Thêm thiết bị</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>


