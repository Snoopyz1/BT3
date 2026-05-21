<?php
// ============================================================
// landing.php — Trang bìa (Landing Page) công khai
// Hệ Thống Quản Lý Phòng Thực Hành Nhà A3 — PTIT
// Không yêu cầu đăng nhập
// ============================================================
session_start();

require_once __DIR__ . '/config/database.php';
if (isset($_GET['test_file'])) die('YES_IT_IS_THIS_FILE');


// ── Các khung giờ hiển thị trên bảng lịch ──────────────────
// Khung giờ cố định để hiển thị trên bảng
// (Giống nư lịch tuần của schedules/index.php - hiển thị toàn bộ lịch trong ngày)
$TIME_SLOTS = [
    '07:00–09:00' => ['start' => '07:00:00', 'end' => '09:00:00'],

    '09:00–11:00' => ['start' => '09:00:00', 'end' => '11:00:00'],
    '11:00–13:00' => ['start' => '11:00:00', 'end' => '13:00:00'],
    '13:00–15:00' => ['start' => '13:00:00', 'end' => '15:00:00'],
    '15:00–17:00' => ['start' => '15:00:00', 'end' => '17:00:00'],
    '17:00–22:00' => ['start' => '17:00:00', 'end' => '22:00:00'],
];

$today       = date('Y-m-d');
$todayLabel  = date('d/m/Y', strtotime($today));
$dayOfWeek   = ['CN','T2','T3','T4','T5','T6','T7'][date('w', strtotime($today))];
$currentHour = (int)date('G');

// ── Dữ liệu mẫu dự phòng khi DB chưa sẵn sàng ─────────────
function sampleRooms(): array {
    return [
        ['id'=>1,'room_code'=>'A3-101','name'=>'Phòng TH Lập Trình',    'floor'=>1,'capacity'=>40,'computer_count'=>40,'status'=>'active'],
        ['id'=>2,'room_code'=>'A3-102','name'=>'Phòng TH Mạng Máy Tính','floor'=>1,'capacity'=>35,'computer_count'=>35,'status'=>'active'],
        ['id'=>3,'room_code'=>'A3-103','name'=>'Phòng TH IoT & Nhúng',  'floor'=>1,'capacity'=>30,'computer_count'=>30,'status'=>'active'],
        ['id'=>4,'room_code'=>'A3-201','name'=>'Phòng TH Web & Thiết Kế','floor'=>2,'capacity'=>40,'computer_count'=>40,'status'=>'active'],
        ['id'=>5,'room_code'=>'A3-202','name'=>'Phòng TH An Toàn TT',   'floor'=>2,'capacity'=>30,'computer_count'=>30,'status'=>'active'],
        ['id'=>6,'room_code'=>'A3-203','name'=>'Phòng TH CSDL',         'floor'=>2,'capacity'=>35,'computer_count'=>35,'status'=>'active'],
        ['id'=>7,'room_code'=>'A3-301','name'=>'Phòng Hội Thảo A3',     'floor'=>3,'capacity'=>60,'computer_count'=>2, 'status'=>'active'],
        ['id'=>8,'room_code'=>'A3-302','name'=>'Phòng TH AI & ML',      'floor'=>3,'capacity'=>20,'computer_count'=>20,'status'=>'active'],
    ];
}

function sampleSchedules(string $today): array {
    return [
        ['room_id'=>1,'room_code'=>'A3-101','start_time'=>'07:00:00','end_time'=>'09:00:00','title'=>'Có lớp','teacher'=>'GV Nguyễn Văn Thắng','status'=>'approved','date'=>$today],
        ['room_id'=>2,'room_code'=>'A3-102','start_time'=>'09:00:00','end_time'=>'11:00:00','title'=>'Có lớp','teacher'=>'GV Nguyễn Văn Thắng','status'=>'approved','date'=>$today],
        ['room_id'=>1,'room_code'=>'A3-101','start_time'=>'13:00:00','end_time'=>'15:00:00','title'=>'Chờ duyệt','teacher'=>'GV Hoàng Văn Minh','status'=>'pending','date'=>$today],
    ];
}

// ── Kết nối DB và lấy dữ liệu ──────────────────────────────
$dbOk    = false;
$dbError = '';
$rooms = $schedules = $stats = [];

try {
    $pdo  = Database::getInstance();
    $dbOk = true;

    // Danh sách phòng (bỏ phòng inactive kiểu 'A3-103B')
    $stmt = $pdo->query(
        "SELECT id, room_code, name, floor, capacity, computer_count, status
           FROM rooms
          WHERE status = 'active'
          ORDER BY floor, room_code"
    );
    $rooms = $stmt->fetchAll();

    // ── Danh sách phòng đầy đủ thông tin (cùng logic với schedules/index.php qua RoomModel)
    require_once __DIR__ . '/models/ScheduleModel.php';
    $scheduleModel = new ScheduleModel();

    // Lấy $schedules cùng nguồn với schedules/index.php:
    //   schedules/index.php dùng: $schedModel->getByDate($today)
    $allSchedulesToday = $scheduleModel->getByDate($today);

    // Chỉ hiển thị approved và pending trên landing page
    $schedules = array_values(array_filter($allSchedulesToday, function($s) {
        return in_array($s['status'], ['approved', 'pending']);
    }));


    // ---------- Thống kê ----------
    // Tổng số phòng active
    $totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='active'")->fetchColumn();

    // Phòng đang bận hôm nay (approved & đang trong khung giờ hiện tại)
    $nowTime = date('H:i:s');
    $stmt2   = $pdo->prepare(
        "SELECT COUNT(DISTINCT room_id)
           FROM room_schedule
          WHERE date = :d
            AND status = 'approved'
            AND start_time <= :t1
            AND end_time   >  :t2"
    );
    $stmt2->execute([':d' => $today, ':t1' => $nowTime, ':t2' => $nowTime]);
    $busyNow = (int)$stmt2->fetchColumn();

    // Số lịch đặt hôm nay (approved)
    $stmt3 = $pdo->prepare(
        "SELECT COUNT(*) FROM room_schedule WHERE date=:d AND status='approved'"
    );
    $stmt3->execute([':d' => $today]);
    $scheduledToday = (int)$stmt3->fetchColumn();

    // Thiết bị đang được mượn
    $borrowed = $pdo->query(
        "SELECT COUNT(*) FROM device_borrow WHERE status='borrowed'"
    )->fetchColumn();

    $stats = [
        'total_rooms'    => $totalRooms,
        'free_rooms'     => max(0, $totalRooms - $busyNow),
        'busy_rooms'     => $busyNow,
        'scheduled_today'=> $scheduledToday,
        'borrowed'       => $borrowed,
    ];

} catch (Throwable $e) {
    $dbOk       = false; // MUST set to false here!
    // Khi DB chưa sẵn sàng hoặc có lỗi truy vấn:
    $dbError    = $e->getMessage();
    $rooms      = sampleRooms();
    $schedules  = [];
    $totalRooms = count($rooms);
    $stats      = ['total_rooms' => $totalRooms, 'free_rooms' => $totalRooms, 'busy_rooms' => 0, 'scheduled_today' => 0, 'borrowed' => 0];
}

// ── Build schedule grid ──────────────────────────────────────
// $grid[$roomCode][$slotLabel] = slot info array | null
$grid = [];
foreach ($rooms as $r) {
    $grid[$r['room_code']] = [];
    foreach ($TIME_SLOTS as $label => $_) {
        $grid[$r['room_code']][$label] = null;
    }
}

foreach ($schedules as $s) {
    $sStart = strtotime($s['start_time']);
    $sEnd   = strtotime($s['end_time']);
    
    foreach ($TIME_SLOTS as $label => $bounds) {
        $bStart = strtotime($bounds['start']);
        $bEnd   = strtotime($bounds['end']);
        
        // Kiểm tra overlap thực sự
        if ($sStart < $bEnd && $sEnd > $bStart) {
            if (isset($grid[$s['room_code']])) {
                $grid[$s['room_code']][$label] = $s;
            }
        }
    }
}

// ── Trạng thái mỗi phòng (để hiện badge) ────────────────────
$nowTime     = date('H:i:s');
$busyRoomIds = [];
foreach ($schedules as $s) {
    if ($s['status'] === 'approved'
        && $s['start_time'] <= $nowTime
        && $s['end_time']   >  $nowTime) {
        $busyRoomIds[$s['room_id'] ?? $s['room_code']] = true;
    }
}

// Phòng bảo trì (inactive)
$maintenanceRooms = [];
if ($dbOk) {
    try {
        $stmt = $pdo->query("SELECT room_code FROM rooms WHERE status='inactive'");
        foreach ($stmt->fetchAll() as $mr) {
            $maintenanceRooms[$mr['room_code']] = true;
        }
    } catch (Throwable $_) {}
}

/**
 * Trả về class CSS và nội dung cho một ô lịch.
 */
function renderSlot(?array $s): string {
    if ($s === null) {
        return '<span class="slot slot-free">Trống</span>';
    }
    $name = htmlspecialchars($s['user_name'] ?? $s['teacher'] ?? 'Người dùng');
    if ($s['status'] === 'pending') {
        return '<span class="slot slot-pending">⏳ Chờ duyệt<br><small>' . $name . '</small></span>';
    }
    return '<span class="slot slot-busy">📚 Có lớp<br><small>' . $name . '</small></span>';
}

/**
 * Trả về class & label trạng thái phòng hiện tại.
 */
function roomStatus(array $room, array $busyRoomIds, array $maintenanceRooms): array {
    $code = $room['room_code'];
    if (isset($maintenanceRooms[$code])) {
        return ['dot-maintenance','badge-maintenance','🔧 Bảo trì'];
    }
    if ($room['status'] === 'inactive') {
        return ['dot-inactive','badge-inactive','Không hoạt động'];
    }
    $key = $room['id'] ?? $code;
    if (isset($busyRoomIds[$key])) {
        return ['dot-busy','badge-busy','🔴 Đang có lớp'];
    }
    return ['dot-free','badge-free','🟢 Trống'];
}

$totalScheduledToday = count(array_filter($schedules, fn($s) => $s['status'] === 'approved'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Phòng Thực Hành A3 — PTIT</title>
    <meta name="description"
          content="Hệ thống quản lý phòng thực hành Nhà A3 — PTIT. Kiểm tra lịch sử dụng phòng, tình trạng thiết bị và đặt phòng trực tuyến.">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Landing CSS -->
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════════════════ -->
<nav class="navbar-a3">
    <a href="landing.php" class="brand" id="brand-logo">
        <div class="brand-icon">
            <i class="bi bi-building"></i>
        </div>
        <div class="brand-text">
            <div class="title">HỆ THỐNG QUẢN LÝ PHÒNG THỰC HÀNH A3</div>
            <div class="sub">Học viện Công nghệ Bưu chính Viễn thông — PTIT</div>
        </div>
    </a>

    <div class="nav-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php" class="btn-primary-a3" id="btn-dashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        <?php else: ?>
            <a href="auth/login.php"    class="btn-outline-primary-a3" id="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
            </a>
            <a href="auth/register.php" class="btn-primary-a3"         id="btn-register">
                <i class="bi bi-person-plus"></i> Đăng ký
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     NOTICE BAR
════════════════════════════════════════════════════════════ -->
<div class="notice-bar" id="notice-bar">
    <span class="notice-label"><i class="bi bi-megaphone-fill"></i> Thông báo</span>
    <div class="notice-scroll">
        <span>
            🏫 Hệ thống quản lý phòng thực hành A3 — PTIT đang hoạt động &nbsp;|&nbsp;
            📅 Hôm nay: <?= $dayOfWeek . ' ' . $todayLabel ?> &nbsp;|&nbsp;
            📚 Có <strong><?= $stats['scheduled_today'] ?? 0 ?></strong> lịch sử dụng phòng hôm nay &nbsp;|&nbsp;
            💻 <strong><?= $stats['borrowed'] ?? 0 ?></strong> thiết bị đang được mượn &nbsp;|&nbsp;
            🔓 Đăng nhập để đặt phòng hoặc mượn thiết bị
        </span>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     HERO BANNER
════════════════════════════════════════════════════════════ -->
<section class="hero-banner" id="hero">
    <div class="hero-inner">
        <h1 class="hero-title">
            <i class="bi bi-building me-2"></i>Quản Lý Phòng Thực Hành Nhà A3
        </h1>
        <p class="hero-sub">
            Theo dõi lịch sử dụng phòng, tình trạng thiết bị và đặt phòng trực tuyến
            — nhanh chóng, minh bạch, hiệu quả.
        </p>
        <div class="hero-date">
            <i class="bi bi-calendar3"></i>
            <span><?= $dayOfWeek ?>, ngày <?= $todayLabel ?></span>
            <span id="live-clock" style="font-variant-numeric:tabular-nums;"></span>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     STAT CARDS
════════════════════════════════════════════════════════════ -->
<section class="stats-row" id="stats-section" aria-label="Thống kê nhanh">

    <div class="stat-card" id="stat-total">
        <div class="stat-icon blue"><i class="bi bi-buildings"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $stats['total_rooms'] ?? 0 ?></div>
            <div class="stat-label">Tổng số phòng TH</div>
        </div>
    </div>

    <div class="stat-card" id="stat-free">
        <div class="stat-icon green"><i class="bi bi-door-open"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $stats['free_rooms'] ?? 0 ?></div>
            <div class="stat-label">Phòng đang trống</div>
        </div>
    </div>

    <div class="stat-card" id="stat-busy">
        <div class="stat-icon red"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $stats['busy_rooms'] ?? 0 ?></div>
            <div class="stat-label">Phòng đang có lớp</div>
        </div>
    </div>

    <div class="stat-card" id="stat-borrowed">
        <div class="stat-icon orange"><i class="bi bi-laptop"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $stats['borrowed'] ?? 0 ?></div>
            <div class="stat-label">Thiết bị đang mượn</div>
        </div>
    </div>

</section>

<!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
════════════════════════════════════════════════════════════ -->
<main class="page-main" id="main-content">

    <!-- ── A: Bảng lịch sử dụng phòng hôm nay ──────────────── -->
    <section id="schedule-section" aria-labelledby="schedule-heading">
        <div class="d-flex align-items-center mb-3 gap-3">
            <h2 class="section-title mb-0" id="schedule-heading">
                <i class="bi bi-calendar-week text-danger me-1"></i>
                Lịch sử dụng phòng hôm nay
                <span style="font-size:.78rem;font-weight:500;color:#64748b;margin-left:.4rem;">
                    (<?= $dayOfWeek ?> <?= $todayLabel ?>)
                </span>
            </h2>
            <button class="refresh-btn" id="refresh-btn" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Làm mới
            </button>
        </div>

        <?php if (!$dbOk): ?>
        <div class="alert alert-danger d-flex flex-column gap-1 mb-3" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-database-x"></i>
                <strong>Không kết nối được CSDL — dữ liệu hiển thị có thể không chính xác.</strong>
            </div>
            <?php if (!empty($dbError)): ?>
            <div style="font-size:0.8rem; font-family:monospace; opacity:0.85;">
                Lỗi: <?= htmlspecialchars($dbError) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php /* Debug: Xem ?debug=1 để kiểm tra dữ liệu */ ?>
        <?php if (isset($_GET['debug'])): ?>
        <div class="alert alert-info mb-3" style="font-size:0.78rem;font-family:monospace;">
            <strong>🛠 DEBUG:</strong>
            DB OK: <?= $dbOk ? 'YES' : 'NO' ?> |
            Tổng lịch hôm nay (all status): <?= isset($allSchedulesToday) ? count($allSchedulesToday) : 'N/A' ?> |
            Lịch hiển thị (approved+pending): <?= count($schedules) ?> |
            Số phòng: <?= count($rooms) ?>
            <?php if (!empty($schedules)): ?>
            <pre style="max-height:200px;overflow:auto;margin-top:8px;"><?= htmlspecialchars(json_encode($schedules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php endif; ?>
            <?php if (!empty($dbError)): ?>
            <div class="text-danger">DB Error: <?= htmlspecialchars($dbError) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="schedule-wrap" id="schedule-table-wrap">
            <table class="schedule-table" id="schedule-table"
                   aria-label="Bảng lịch sử dụng phòng hôm nay">
                <thead>
                    <tr>
                        <th scope="col"><i class="bi bi-door-closed me-1"></i>Phòng</th>
                        <?php foreach ($TIME_SLOTS as $label => $_): ?>
                        <th scope="col"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $room):
                    $code = $room['room_code'];
                    if ($room['status'] === 'inactive') continue;
                ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($code) ?></strong>
                            <span class="td-room-name"><?= htmlspecialchars($room['name']) ?></span>
                        </td>
                        <?php foreach ($TIME_SLOTS as $label => $_): ?>
                        <td><?= renderSlot($grid[$code][$label] ?? null) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div><!-- /.schedule-wrap -->

        <!-- Chú thích màu sắc -->
        <div class="legend mt-2">
            <span class="legend-item">
                <span class="legend-box" style="background:#fee2e2;border:1px solid #fecaca;"></span>
                Đang có lớp
            </span>
            <span class="legend-item">
                <span class="legend-box" style="background:#dcfce7;border:1px solid #bbf7d0;"></span>
                Trống
            </span>
            <span class="legend-item">
                <span class="legend-box" style="background:#fef9c3;border:1px solid #fde68a;"></span>
                Chờ duyệt
            </span>
            <span class="legend-item">
                <span class="legend-box" style="background:#f1f5f9;border:1px solid #e2e8f0;"></span>
                Không áp dụng
            </span>
        </div>
    </section>

    <div style="height:2.5rem;"></div>

    <!-- ── B: Tình trạng phòng hiện tại ────────────────────── -->
    <section id="rooms-section" aria-labelledby="rooms-heading">
        <h2 class="section-title" id="rooms-heading">
            <i class="bi bi-grid-3x3-gap text-danger me-1"></i>
            Tình trạng các phòng thực hành
        </h2>

        <!-- Room status badges (mini overview) -->
        <div class="room-header-grid" id="room-badge-grid">
            <?php foreach ($rooms as $room):
                if ($room['status'] === 'inactive') continue;
                [$dotClass, , $statusLabel] = roomStatus($room, $busyRoomIds, $maintenanceRooms);
            ?>
            <div class="room-badge" id="badge-<?= htmlspecialchars($room['room_code']) ?>">
                <div class="dot <?= $dotClass ?>"></div>
                <div>
                    <div class="code"><?= htmlspecialchars($room['room_code']) ?></div>
                    <div class="status-text"><?= $statusLabel ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Room detail cards -->
        <div class="rooms-grid" id="rooms-grid">
            <?php foreach ($rooms as $room):
                if ($room['status'] === 'inactive') continue;
                [$dotClass, $badgeClass, $statusLabel] = roomStatus($room, $busyRoomIds, $maintenanceRooms);
            ?>
            <article class="room-card" id="card-<?= htmlspecialchars($room['room_code']) ?>">
                <div class="room-card-header">
                    <div>
                        <div class="room-card-code"><?= htmlspecialchars($room['room_code']) ?></div>
                        <div class="room-card-name"><?= htmlspecialchars($room['name']) ?></div>
                    </div>
                    <span class="status-badge <?= $badgeClass ?>">
                        <?= $statusLabel ?>
                    </span>
                </div>
                <div class="room-card-body">
                    <div class="room-meta">
                        <span>
                            <i class="bi bi-people"></i>
                            <?= $room['capacity'] ?> chỗ
                        </span>
                        <span>
                            <i class="bi bi-pc-display"></i>
                            <?= $room['computer_count'] ?> máy
                        </span>
                        <span>
                            <i class="bi bi-layers"></i>
                            Tầng <?= $room['floor'] ?>
                        </span>
                    </div>
                    <?php
                    // Lịch tiếp theo trong ngày
                    $upcoming = null;
                    foreach ($schedules as $s) {
                        if (($s['room_code'] ?? '') === $room['room_code']
                            && $s['status'] === 'approved'
                            && $s['start_time'] > $nowTime) {
                            $upcoming = $s;
                            break;
                        }
                    }
                    ?>
                    <?php if ($upcoming): ?>
                    <div class="mt-2" style="font-size:.74rem;color:#64748b;">
                        <i class="bi bi-clock-history"></i>
                        Tiếp theo: <?= substr($upcoming['start_time'],0,5) ?>–<?= substr($upcoming['end_time'],0,5) ?>
                        — <?= htmlspecialchars($upcoming['teacher'] ?? '') ?>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<!-- ══════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ -->
<footer class="footer-a3" id="footer" role="contentinfo">
    <div class="footer-inner">

        <div class="footer-copy">
            <strong>&copy; <?= date('Y') ?> Quản Lý Phòng Thực Hành Nhà A3</strong><br>
            Học viện Công nghệ Bưu chính Viễn thông (PTIT) — Hà Nội
        </div>

        <div class="footer-contact">
            <i class="bi bi-tools me-1"></i>
            Hỗ trợ kỹ thuật:<br>
            <a href="mailto:tech@a3.edu.vn">tech@a3.edu.vn</a>
            &nbsp;|&nbsp;
            <a href="tel:02432551234">024 3255 1234</a>
        </div>

        <div class="footer-admin">
            <a href="auth/login.php" id="footer-admin-link">
                <i class="bi bi-shield-lock"></i>
                Đăng nhập Admin
            </a>
        </div>

    </div>
</footer>

<!-- ══════════════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Đồng hồ thời gian thực ──────────────────────────────────
(function liveClock() {
    const el = document.getElementById('live-clock');
    function tick() {
        const now = new Date();
        const h   = String(now.getHours()).padStart(2,'0');
        const m   = String(now.getMinutes()).padStart(2,'0');
        const s   = String(now.getSeconds()).padStart(2,'0');
        if (el) el.textContent = `| ${h}:${m}:${s}`;
    }
    tick();
    setInterval(tick, 1000);
})();

// ── Tự động làm mới mỗi 5 phút ─────────────────────────────
setTimeout(() => location.reload(), 5 * 60 * 1000);

// ── Highlight cột giờ hiện tại ──────────────────────────────
(function highlightCurrentSlot() {
    const now   = new Date();
    const total = now.getHours() * 60 + now.getMinutes();
    const slots = [
        { start: 7*60,  end:  9*60, col: 2 },
        { start: 9*60,  end: 11*60, col: 3 },
        { start: 13*60, end: 15*60, col: 4 },
        { start: 15*60, end: 17*60, col: 5 },
    ];
    const active = slots.find(s => total >= s.start && total < s.end);
    if (!active) return;
    const tbl = document.getElementById('schedule-table');
    if (!tbl) return;
    // Đánh dấu header
    const th = tbl.querySelectorAll('thead th')[active.col - 1];
    if (th) {
        th.style.background = 'linear-gradient(135deg, #7f1d1d, #991b1b)';
        th.style.position   = 'relative';
        const badge = document.createElement('span');
        badge.textContent   = '▶ Hiện tại';
        badge.style.cssText = 'display:block;font-size:.65rem;opacity:.85;';
        th.appendChild(badge);
    }
    // Đánh dấu các td tương ứng
    tbl.querySelectorAll('tbody tr').forEach(tr => {
        const td = tr.querySelectorAll('td')[active.col - 1];
        if (td) td.style.background = '#fff5f5';
    });
})();
</script>

</body>
</html>
