<?php
// ============================================================
// views/rooms/index.php
// ============================================================
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
requireLogin();

require_once __DIR__ . '/../../controllers/RoomController.php';

$ctrl = new RoomController();

// Handle actions
$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? 0);

if ($action === 'delete' && $id > 0) {
    $ctrl->delete($id);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') $ctrl->create();
    if ($action === 'edit' && $id > 0) $ctrl->update($id);
}

// Get data
$data     = $ctrl->index();
$rooms    = $data['rooms'];
$total    = $data['total'];
$page     = $data['page'];
$limit    = $data['limit'];

// For edit modal
$editRoom = null;
if ($action === 'edit' && $id > 0) {
    $editRoom = $ctrl->edit($id);
}

$err = getFlash('error');
$suc = getFlash('success');

define('PAGE_TITLE', 'Phòng Thực Hành');
require_once __DIR__ . '/../partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content">
  <!-- Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-building me-2 text-primary"></i>Phòng Thực Hành</h1>
      <p class="page-subtitle mb-0">Quản lý tất cả phòng thực hành Nhà A3</p>
    </div>
    <?php if (hasRole(['admin','technician'])): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">
        <i class="bi bi-plus-circle me-1"></i>Thêm Phòng
      </button>
    <?php endif; ?>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
  <?php if ($suc): ?><div class="alert alert-success"><?= $suc ?></div><?php endif; ?>

  <!-- Search & filter -->
  <div class="app-card mb-3 p-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(0,0,0,0.06);border-color:rgba(0,0,0,0.12);color:#64748b;"><i class="bi bi-search"></i></span>
          <input type="text" id="tableSearch" class="form-control" placeholder="Tìm kiếm phòng...">
        </div>
      </div>
      <div class="col-md-3">
        <select id="statusFilter" class="form-select">
          <option value="">Tất cả trạng thái</option>
          <option value="active">Hoạt động</option>
          <option value="inactive">Không hoạt động</option>
        </select>
      </div>
      <div class="col-md-4 text-end text-muted small">
        Tổng: <strong class="text-white"><?= $total ?></strong> phòng
      </div>
    </div>
  </div>

  <!-- Rooms Grid -->
  <div class="row g-3">
    <?php if (empty($rooms)): ?>
      <div class="col-12"><div class="app-card p-5 text-center text-muted"><i class="bi bi-building-x fs-1 d-block mb-3"></i>Chưa có phòng nào được tạo.</div></div>
    <?php else: ?>
      <?php foreach ($rooms as $room): ?>
        <div class="col-sm-6 col-xl-4" data-status="<?= $room['status'] ?>">
          <div class="app-card h-100">
            <!-- Room image -->
            <div style="height:160px;overflow:hidden;border-radius:16px 16px 0 0;background:linear-gradient(135deg,#0d2137,#1a3550);display:flex;align-items:center;justify-content:center;">
              <?php if (!empty($room['image']) && file_exists(__DIR__ . '/../../' . $room['image'])): ?>
                <img src="<?= BASE_URL . '/' . htmlspecialchars($room['image']) ?>" alt="<?= htmlspecialchars($room['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <i class="bi bi-building text-primary" style="font-size:3rem;opacity:0.4;"></i>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between mb-2">
                <div>
                  <h6 class="fw-bold mb-1 text-white"><?= htmlspecialchars($room['room_code']) ?></h6>
                  <p class="text-muted small mb-0"><?= htmlspecialchars($room['name']) ?></p>
                </div>
                <?= statusBadge($room['status']) ?>
              </div>
              <div class="row g-2 mb-3 mt-2">
                <div class="col-6">
                  <div class="p-2 rounded-2 text-center" style="background:rgba(59,130,246,0.1);">
                    <div class="fw-bold text-primary"><?= $room['capacity'] ?></div>
                    <div class="text-muted" style="font-size:0.71rem;">Sức chứa</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 rounded-2 text-center" style="background:rgba(6,182,212,0.1);">
                    <div class="fw-bold text-info"><?= $room['computer_count'] ?></div>
                    <div class="text-muted" style="font-size:0.71rem;">Máy tính</div>
                  </div>
                </div>
              </div>
              <?php if (!empty($room['description'])): ?>
                <p class="text-muted small mb-3" style="line-height:1.4;"><?= htmlspecialchars(mb_substr($room['description'],0,80)) ?><?= mb_strlen($room['description'])>80?'…':'' ?></p>
              <?php endif; ?>
              <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/views/schedules/index.php?room_id=<?= $room['id'] ?>" class="btn btn-sm btn-outline-secondary flex-grow-1">
                  <i class="bi bi-calendar3 me-1"></i>Lịch
                </a>
                <?php if (hasRole(['admin','technician'])): ?>
                  <a href="?action=edit&id=<?= $room['id'] ?>"
                     class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRoomModal<?= $room['id'] ?>">
                    <i class="bi bi-pencil"></i>
                  </a>
                <?php endif; ?>
                <?php if (hasRole('admin')): ?>
                  <a href="?action=delete&id=<?= $room['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="Bạn có chắc muốn xóa phòng <?= htmlspecialchars($room['room_code']) ?>?">
                    <i class="bi bi-trash"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Edit Modal per room -->
          <?php if (hasRole(['admin','technician'])): ?>
          <div class="modal fade" id="editRoomModal<?= $room['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST" action="?action=edit&id=<?= $room['id'] ?>" enctype="multipart/form-data">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Sửa Phòng <?= htmlspecialchars($room['room_code']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">Mã phòng *</label>
                        <input type="text" name="room_code" class="form-control" value="<?= htmlspecialchars($room['room_code']) ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Tên phòng *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($room['name']) ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tầng</label>
                        <input type="number" name="floor" class="form-control" value="<?= $room['floor'] ?>" min="1" max="10">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Sức chứa</label>
                        <input type="number" name="capacity" class="form-control" value="<?= $room['capacity'] ?>" min="0">
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Số máy tính</label>
                        <input type="number" name="computer_count" class="form-control" value="<?= $room['computer_count'] ?>" min="0">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($room['description']) ?></textarea>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                          <option value="active"   <?= $room['status']==='active'?'selected':'' ?>>Hoạt động</option>
                          <option value="inactive" <?= $room['status']==='inactive'?'selected':'' ?>>Không hoạt động</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Ảnh phòng</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Lưu thay đổi</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <div class="mt-4 d-flex justify-content-center">
    <?= paginate($total, $limit, $page, '?') ?>
  </div>

</div><!-- /main-content -->
<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Create Room Modal -->
<?php if (hasRole(['admin','technician'])): ?>
<div class="modal fade" id="createRoomModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=create" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm Phòng Mới</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Mã phòng *</label>
              <input type="text" name="room_code" class="form-control" placeholder="VD: A3-101" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tên phòng *</label>
              <input type="text" name="name" class="form-control" placeholder="Phòng thực hành mạng" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tầng</label>
              <input type="number" name="floor" class="form-control" value="1" min="1" max="10">
            </div>
            <div class="col-md-4">
              <label class="form-label">Sức chứa</label>
              <input type="number" name="capacity" class="form-control" value="30" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Số máy tính</label>
              <input type="number" name="computer_count" class="form-control" value="30" min="0">
            </div>
            <div class="col-12">
              <label class="form-label">Mô tả</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Mô tả phòng thực hành..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Trạng thái</label>
              <select name="status" class="form-select">
                <option value="active">Hoạt động</option>
                <option value="inactive">Không hoạt động</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ảnh phòng</label>
              <input type="file" id="image" name="image" class="form-control" accept="image/*">
            </div>
            <div class="col-12">
              <img id="imagePreview" src="" alt="" style="display:none;max-width:100%;border-radius:10px;margin-top:8px;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Thêm phòng</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>


