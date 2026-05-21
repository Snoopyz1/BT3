<?php
// ============================================================
// config/helpers.php — Hàm tiện ích dùng chung
// ============================================================

/**
 * Redirect đến URL
 */
function redirect(string $url): void {
    header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
    exit;
}

/**
 * Sanitize đầu vào
 */
function clean(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Lấy role của người dùng hiện tại
 */
function getUserRole(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Kiểm tra quyền
 */
function hasRole(string|array $roles): bool {
    $currentRole = getUserRole();
    if (is_array($roles)) {
        return in_array($currentRole, $roles);
    }
    return $currentRole === $roles;
}

/**
 * Yêu cầu đăng nhập
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

/**
 * Yêu cầu quyền cụ thể
 */
function requireRole(string|array $roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'Bạn không có quyền truy cập trang này.';
        redirect('views/dashboard.php');
    }
}

/**
 * Hiển thị thông báo flash
 */
function flashMessage(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): string {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return '';
}

/**
 * Format ngày tháng tiếng Việt
 */
function formatDate(string $date, string $format = 'd/m/Y'): string {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime(string $datetime): string {
    if (empty($datetime)) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Trạng thái badge Bootstrap
 */
function statusBadge(string $status): string {
    $map = [
        'pending'   => ['warning', 'Chờ duyệt'],
        'approved'  => ['success', 'Đã duyệt'],
        'rejected'  => ['danger',  'Từ chối'],
        'cancelled' => ['secondary','Đã hủy'],
        'active'    => ['success', 'Hoạt động'],
        'inactive'  => ['danger',  'Không hoạt động'],
        'available' => ['success', 'Sẵn sàng'],
        'borrowed'  => ['warning', 'Đang mượn'],
        'broken'    => ['danger',  'Hỏng hóc'],
        'open'      => ['danger',  'Mới tạo'],
        'processing'=> ['info',    'Đang xử lý'],
        'resolved'  => ['success', 'Đã xử lý'],
    ];
    $cfg = $map[$status] ?? ['secondary', $status];
    return "<span class=\"badge bg-{$cfg[0]}\">{$cfg[1]}</span>";
}

/**
 * Phân trang
 */
function paginate(int $total, int $perPage, int $current, string $url): string {
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<nav><ul class="pagination mb-0">';
    // Prev
    $html .= '<li class="page-item ' . ($current <= 1 ? 'disabled' : '') . '">'
        . '<a class="page-link" href="' . $url . '?page=' . ($current - 1) . '">«</a></li>';
    for ($i = 1; $i <= $pages; $i++) {
        $html .= '<li class="page-item ' . ($i === $current ? 'active' : '') . '">'
            . '<a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    // Next
    $html .= '<li class="page-item ' . ($current >= $pages ? 'disabled' : '') . '">'
        . '<a class="page-link" href="' . $url . '?page=' . ($current + 1) . '">»</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

/**
 * CSRF Token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('CSRF token không hợp lệ.');
    }
}

/**
 * Ghi log hoạt động
 */
function writeLog(PDO $db, int $userId, string $action, string $detail = ''): void {
    $stmt = $db->prepare("INSERT INTO logs (user_id, action, detail, created_at) VALUES (?,?,?,NOW())");
    $stmt->execute([$userId, $action, $detail]);
}
