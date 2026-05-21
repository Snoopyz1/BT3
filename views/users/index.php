<?php
// ============================================================
// views/users/index.php
// ============================================================
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
requireRole('admin');

require_once __DIR__ . '/../../models/UserModel.php';

$model = new UserModel();

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $data = [
            'full_name' => clean($_POST['full_name'] ?? ''),
            'email'     => clean($_POST['email'] ?? ''),
            'password'  => $_POST['password'] ?? 'ptit2024',
            'role'      => clean($_POST['role'] ?? 'student'),
            'phone'     => clean($_POST['phone'] ?? ''),
            'status'    => clean($_POST['status'] ?? 'active'),
        ];
        if ($model->create($data)) {
            writeLog(Database::getConnection(), $_SESSION['user_id'], 'create_user', "Thêm user: {$data['email']}");
            flashMessage('success', 'Thêm người dùng thành công!');
        } else {
            flashMessage('error', 'Thêm người dùng thất bại. Email có thể đã tồn tại.');
        }
        redirect('views/users/index.php');
    }
    if ($action === 'edit' && $id > 0) {
        $data = [
            'full_name' => clean($_POST['full_name'] ?? ''),
            'email'     => clean($_POST['email'] ?? ''),
            'role'      => clean($_POST['role'] ?? 'student'),
            'phone'     => clean($_POST['phone'] ?? ''),
            'status'    => clean($_POST['status'] ?? 'active'),
        ];
        if (!empty($_POST['password'])) $data['password'] = $_POST['password'];
        if ($model->update($id, $data)) {
            writeLog(Database::getConnection(), $_SESSION['user_id'], 'update_user', "Cập nhật user ID $id");
            flashMessage('success', 'Cập nhật người dùng thành công!');
        } else {
            flashMessage('error', 'Cập nhật thất bại.');
        }
        redirect('views/users/index.php');
    }
}
if ($action === 'delete' && $id > 0) {
    if ($id == $_SESSION['user_id']) {
        flashMessage('error', 'Không thể xóa tài khoản của chính mình!');
    } elseif ($model->delete($id)) {
        writeLog(Database::getConnection(), $_SESSION['user_id'], 'delete_user', "Xóa user ID $id");
        flashMessage('success', 'Đã xóa người dùng.');
    } else {
        flashMessage('error', 'Xóa thất bại.');
    }
    redirect('views/users/index.php');
}

$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;
$users  = $model->getAll($limit, $offset);
$total  = $model->count();
$err    = getFlash('error');
$suc    = getFlash('success');

$roleLabels = ['admin'=>'Quản trị viên','teacher'=>'Giáo viên','technician'=>'Kỹ thuật viên','student'=>'Sinh viên'];

define('PAGE_TITLE', 'Người Dùng');
require_once __DIR__ . '/../partials/header.php';
?>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<div class="d-flex flex-column min-vh-100">
<div class="app-wrapper">
<?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="page-title mb-1"><i class="bi bi-people me-2 text-purple"></i>Quản Lý Người Dùng</h1>
      <p class="page-subtitle mb-0">Quản lý tài khoản và phân quyền hệ thống</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
      <i class="bi bi-person-plus me-1"></i>Thêm Người Dùng
    </button>
  </div>

  <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
  <?php if ($suc): ?><div class="alert alert-success"><?= $suc ?></div><?php endif; ?>

  <!-- Filter bar -->
  <div class="app-card mb-3 p-3">
    <div class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(0,0,0,0.06);border-color:rgba(0,0,0,0.12);color:#64748b;"><i class="bi bi-search"></i></span>
          <input type="text" id="tableSearch" class="form-control" placeholder="Tìm tên, email...">
        </div>
      </div>
      <div class="col-md-3">
        <select id="statusFilter" class="form-select">
          <option value="">Tất cả</option>
          <option value="active">Hoạt động</option>
          <option value="inactive">Bị khóa</option>
        </select>
      </div>
      <div class="col-md-4 text-end text-muted small">
        Tổng: <strong class="text-white"><?= $total ?></strong> tài khoản
      </div>
    </div>
  </div>

  <!-- Users Table -->
  <div class="app-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table app-table mb-0">
          <thead><tr>
            <th>#</th><th>Họ tên</th><th>Email</th><th>Điện thoại</th>
            <th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th>
          </tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr data-status="<?= $u['status'] ?>">
                <td class="text-muted small"><?= $u['id'] ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#9b2226,#dc3545);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;">
                      <?= strtoupper(substr($u['full_name'],0,1)) ?>
                    </div>
                    <span class="fw-medium"><?= htmlspecialchars($u['full_name']) ?></span>
                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                      <span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem;">Bạn</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                <td>
                  <?php
                  $roleBg = ['admin'=>'danger','teacher'=>'primary','technician'=>'warning','student'=>'success'];
                  $rBg = $roleBg[$u['role']] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?= $rBg ?>-subtle text-<?= $rBg ?>"><?= $roleLabels[$u['role']] ?? $u['role'] ?></span>
                </td>
                <td><?= statusBadge($u['status']) ?></td>
                <td class="text-muted small"><?= formatDate($u['created_at']) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                      <a href="?action=delete&id=<?= $u['id'] ?>"
                         class="btn btn-sm btn-outline-danger"
                         data-confirm="Xóa tài khoản '<?= htmlspecialchars($u['full_name']) ?>'?">
                        <i class="bi bi-trash"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

              <!-- Edit User Modal -->
              <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="?action=edit&id=<?= $u['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Sửa Người Dùng</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Điện thoại</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone']) ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Vai trò</label>
                            <select name="role" class="form-select">
                              <?php foreach ($roleLabels as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $u['role']===$k?'selected':'' ?>><?= $v ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                              <option value="active"   <?= $u['status']==='active'?'selected':'' ?>>Hoạt động</option>
                              <option value="inactive" <?= $u['status']==='inactive'?'selected':'' ?>>Bị khóa</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Mật khẩu mới <small class="text-muted">(để trống nếu không đổi)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
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

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-4 d-flex justify-content-center">
    <?= paginate($total, $limit, $page, '?') ?>
  </div>

</div><!-- /main-content -->
<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="?action=create">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-person-plus me-2 text-primary"></i>Thêm Người Dùng Mới</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ và tên *</label>
              <input type="text" name="full_name" class="form-control" placeholder="Nguyễn Văn A" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" placeholder="user@ptit.edu.vn" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Điện thoại</label>
              <input type="tel" name="phone" class="form-control" placeholder="0912345678">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vai trò</label>
              <select name="role" class="form-select">
                <option value="student">Sinh viên</option>
                <option value="teacher">Giáo viên</option>
                <option value="technician">Kỹ thuật viên</option>
                <option value="admin">Quản trị viên</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mật khẩu *</label>
              <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required minlength="6">
            </div>
            <div class="col-md-6">
              <label class="form-label">Trạng thái</label>
              <select name="status" class="form-select">
                <option value="active">Hoạt động</option>
                <option value="inactive">Khóa</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Thêm người dùng</button>
        </div>
      </form>
    </div>
  </div>
</div>


