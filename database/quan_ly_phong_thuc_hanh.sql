

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tạo CSDL
CREATE DATABASE IF NOT EXISTS `quan_ly_phong_thuc_hanh`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `quan_ly_phong_thuc_hanh`;

-- ============================================================
-- Bảng: users
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(100)   NOT NULL,
  `email`      VARCHAR(150)   NOT NULL UNIQUE,
  `password`   VARCHAR(255)   NOT NULL,
  `role`       ENUM('admin','teacher','technician','student') NOT NULL DEFAULT 'student',
  `phone`      VARCHAR(20)    DEFAULT NULL,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bảng: rooms
-- ============================================================
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_code`      VARCHAR(20)  NOT NULL UNIQUE,
  `name`           VARCHAR(150) NOT NULL,
  `floor`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `capacity`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `computer_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `description`    TEXT         DEFAULT NULL,
  `image`          VARCHAR(255) DEFAULT NULL,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_code` (`room_code`),
  INDEX `idx_status`    (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bảng: devices
-- ============================================================
DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`       INT UNSIGNED NOT NULL,
  `device_name`   VARCHAR(150) NOT NULL,
  `device_type`   ENUM('computer','projector','switch','printer','camera','furniture','other') NOT NULL DEFAULT 'other',
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `quantity`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `status`        ENUM('available','borrowed','broken','inactive') NOT NULL DEFAULT 'available',
  `description`   TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_id`    (`room_id`),
  INDEX `idx_status`     (`status`),
  INDEX `idx_device_type`(`device_type`),
  CONSTRAINT `fk_device_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bảng: room_schedule
-- ============================================================
DROP TABLE IF EXISTS `room_schedule`;
CREATE TABLE `room_schedule` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `date`       DATE         NOT NULL,
  `start_time` TIME         NOT NULL,
  `end_time`   TIME         NOT NULL,
  `purpose`    TEXT         DEFAULT NULL,
  `status`     ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `admin_note` TEXT         DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_room_date`   (`room_id`, `date`),
  INDEX `idx_user_id`     (`user_id`),
  INDEX `idx_status`      (`status`),
  INDEX `idx_date`        (`date`),
  CONSTRAINT `fk_schedule_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_schedule_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bảng: device_borrow
-- ============================================================
DROP TABLE IF EXISTS `device_borrow`;
CREATE TABLE `device_borrow` (
  `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `device_id`            INT UNSIGNED  NOT NULL,
  `user_id`              INT UNSIGNED  NOT NULL,
  `borrow_date`          DATE          NOT NULL,
  `expected_return_date` DATE          NOT NULL,
  `actual_return_date`   DATE          DEFAULT NULL,
  `purpose`              TEXT          DEFAULT NULL,
  `return_note`          TEXT          DEFAULT NULL,
  `status`               ENUM('borrowed','returned','lost') NOT NULL DEFAULT 'borrowed',
  `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_device_id`  (`device_id`),
  INDEX `idx_user_id`    (`user_id`),
  INDEX `idx_status`     (`status`),
  INDEX `idx_borrow_date`(`borrow_date`),
  CONSTRAINT `fk_borrow_device`
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_borrow_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bảng: reports (báo cáo sự cố)
-- ============================================================
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`     INT UNSIGNED DEFAULT NULL,
  `device_id`   INT UNSIGNED DEFAULT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT         NOT NULL,
  `severity`    ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status`      ENUM('open','processing','resolved') NOT NULL DEFAULT 'open',
  `resolution`  TEXT         DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status`    (`status`),
  INDEX `idx_severity`  (`severity`),
  INDEX `idx_user`      (`user_id`),
  CONSTRAINT `fk_report_room`
    FOREIGN KEY (`room_id`)   REFERENCES `rooms`(`id`)   ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_device`
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_user`
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Bảng: logs (nhật ký hệ thống)
-- ============================================================
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `action`     VARCHAR(100) NOT NULL,
  `detail`     TEXT         DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id`   (`user_id`),
  INDEX `idx_action`    (`action`),
  INDEX `idx_created_at`(`created_at`),
  CONSTRAINT `fk_log_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DỮ LIỆU MẪU
-- ============================================================

-- ── USERS ─────────────────────────────────────────────────
-- Mật khẩu mặc định: admin123 / teacher123 / technician123 / student123
-- (Đã hash bằng password_hash(..., PASSWORD_BCRYPT))
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `phone`, `status`, `created_at`) VALUES
('Quản Trị Viên', 'admin@a3.edu.vn',
 '$2y$10$TKh8H1.PfBpkKNrxhBN3aO6X.pSSFpLpPBHy.ViKVSzDRMawvSqa', 'admin', '0901234567', 'active', '2024-01-01 08:00:00'),
('Nguyễn Văn Thắng', 'teacher@a3.edu.vn',
 '$2y$10$TKh8H1.PfBpkKNrxhBN3aO6X.pSSFpLpPBHy.ViKVSzDRMawvSqa', 'teacher', '0912345678', 'active', '2024-01-05 08:00:00'),
('Trần Kỹ Thuật', 'tech@a3.edu.vn',
 '$2y$10$TKh8H1.PfBpkKNrxhBN3aO6X.pSSFpLpPBHy.ViKVSzDRMawvSqa', 'technician', '0923456789', 'active', '2024-01-10 08:00:00'),
('Lê Văn Sinh', 'student@a3.edu.vn',
 '$2y$10$TKh8H1.PfBpkKNrxhBN3aO6X.pSSFpLpPBHy.ViKVSzDRMawvSqa', 'student', '0934567890', 'active', '2024-02-01 08:00:00'),
('Phạm Thị Mai', 'mai.pt@a3.edu.vn',
 '$2y$10$TKh8H1.PfBpkKNrxhBN3aO6X.pSSFpLpPBHy.ViKVSzDRMawvSqa', 'student', '0945678901', 'active', '2024-02-05 08:00:00'),
('Hoàng Văn Minh', 'minh.hv@a3.edu.vn',
 '$2y$10$TKh8H1.PfBpkKNrxhBN3aO6X.pSSFpLpPBHy.ViKVSzDRMawvSqa', 'teacher', '0956789012', 'active', '2024-02-10 08:00:00');

-- ── ROOMS ─────────────────────────────────────────────────
INSERT INTO `rooms` (`room_code`, `name`, `floor`, `capacity`, `computer_count`, `description`, `status`) VALUES
('A3-101', 'Phòng Thực Hành Lập Trình',  1, 40, 40, 'Phòng thực hành cho môn Lập trình cơ sở, CSDL. Trang bị máy tính Intel Core i5, RAM 8GB, SSD 256GB.', 'active'),
('A3-102', 'Phòng Thực Hành Mạng Máy Tính', 1, 35, 35, 'Phòng có switch Cisco 3750, Router, cáp UTP Cat6. Thực hành CCNA và các môn mạng.', 'active'),
('A3-103', 'Phòng Thực Hành IoT & Nhúng', 1, 30, 30, 'Trang bị Arduino, Raspberry Pi, cảm biến. Dành cho môn IoT, Hệ thống nhúng.', 'active'),
('A3-201', 'Phòng Thực Hành Web & Thiết Kế', 2, 40, 40, 'Máy tính cấu hình cao, màn hình IPS. Dành cho thiết kế đồ họa, lập trình web.', 'active'),
('A3-202', 'Phòng Thực Hành An Toàn Thông Tin', 2, 30, 30, 'Môi trường sandbox cách ly mạng. Thực hành ethical hacking, pentest.', 'active'),
('A3-203', 'Phòng Thực Hành CSDL', 2, 35, 35, 'Cài sẵn MySQL, PostgreSQL, Oracle. Dành cho môn CSDL và Data Warehousing.', 'active'),
('A3-301', 'Phòng Hội Thảo A3', 3, 60, 2, 'Phòng hội thảo lớn, có máy chiếu và bảng tương tác thông minh.', 'active'),
('A3-302', 'Phòng Thực Hành AI & Machine Learning', 3, 20, 20, 'Server GPU NVIDIA RTX 3090. Môi trường Python/TensorFlow/PyTorch.', 'active'),
('A3-103B', 'Phòng Thực Hành Dự Phòng', 1, 30, 30, 'Phòng dự phòng, sử dụng khi các phòng khác bận.', 'inactive');

-- ── DEVICES ───────────────────────────────────────────────
INSERT INTO `devices` (`room_id`, `device_name`, `device_type`, `serial_number`, `quantity`, `status`, `description`) VALUES
-- A3-101
(1, 'Máy tính HP EliteDesk 800 G5',    'computer',  'HP800G5-2024-001', 40, 'available', 'Intel i5-9500, RAM 8GB, SSD 256GB, Win 11 Pro'),
(1, 'Màn hình Dell UltraSharp 24"',    'other',     'DEL24-2024-001',   40, 'available', 'IPS, Full HD, 60Hz'),
(1, 'Máy chiếu Epson X49',             'projector', 'EPS-X49-2024-01',   1, 'available', '3600 Lumens, HDMI, VGA'),
(1, 'Switch Cisco Catalyst 2960',      'switch',    'CSCO-2960-101',     2, 'available', '24 port 10/100Mbps'),
-- A3-102
(2, 'Máy tính Dell OptiPlex 7090',     'computer',  'DEL7090-2024-001', 35, 'available', 'Intel i7-10700, RAM 16GB, SSD 512GB'),
(2, 'Router Cisco 2901',               'switch',    'CSCO-2901-102',     4, 'available', 'Enterprise Router, 2 WAN ports'),
(2, 'Switch Cisco Catalyst 3750',      'switch',    'CSCO-3750-102',     6, 'available', '24 port Gigabit PoE'),
(2, 'Máy chiếu BenQ MH550',           'projector', 'BNQ-MH550-102',     1, 'available', '3500 Lumens, Full HD'),
-- A3-103
(3, 'Kit Arduino Mega 2560',           'other',     'ARD-MEGA-2024',    30, 'available', 'Kèm breadboard, LED, cảm biến'),
(3, 'Raspberry Pi 4 Model B (8GB)',    'computer',  'RPI4-8G-2024',     15, 'available', 'ARM Cortex-A72, WiFi, BT 5.0'),
(3, 'Oscilloscope Rigol DS1054Z',      'other',     'RIG-1054Z-103',     5, 'available', '50MHz, 4 kênh'),
-- A3-201
(4, 'Máy tính Lenovo ThinkCentre M90n','computer',  'LEN-M90N-2024',    40, 'available', 'Intel i7-10700T, RAM 16GB, SSD 512GB'),
(4, 'Màn hình LG 27" 4K',             'other',     'LG27-4K-2024',     40, 'available', 'IPS, UHD, USB-C'),
(4, 'Wacom Intuos Pro Medium',         'other',     'WAC-INTM-2024',    20, 'borrowed',  'Bảng vẽ điện tử chuyên nghiệp'),
-- A3-202
(5, 'Máy tính Asus ProArt PA90',       'computer',  'ASUS-PA90-2024',   30, 'available', 'Intel i9-9900K, RAM 32GB, tách mạng'),
(5, 'Switch TP-Link TL-SG116E',       'switch',    'TPL-116E-202',      5, 'available', '16 port managed'),
-- A3-203
(6, 'Máy tính HP ProDesk 400 G7',     'computer',  'HP400G7-2024',     35, 'available', 'Intel i5-10500, RAM 8GB, SSD 256GB'),
(6, 'MySQL Server Workbench Station',  'computer',  'MYSQL-SRV-203',     1, 'available', 'Server cài MySQL, PostgreSQL, Oracle'),
-- A3-301
(7, 'Máy chiếu Panasonic PT-MZ670',   'projector', 'PAN-MZ670-301',     2, 'available', '6500 Lumens, 4K, laser'),
(7, 'Bảng tương tác SMART Board',     'other',     'SMA-6275-301',      2, 'available', '75 inch, 4K, multi-touch 20 điểm'),
(7, 'Bộ âm thanh Bose Sistema',       'other',     'BOSE-301-2024',     1, 'available', 'Loa hội thảo 500W'),
-- A3-302
(8, 'GPU Server NVIDIA RTX 3090',      'computer',  'NVI-RTX-3090-302',  4, 'available', '24GB VRAM, CUDA, TensorRT'),
(8, 'Workstation HP Z8 G4',           'computer',  'HPZ8G4-302-2024',   8, 'available', 'Xeon W, RAM 128GB ECC');

-- ── ROOM SCHEDULES ────────────────────────────────────────
INSERT INTO `room_schedule` (`room_id`, `user_id`, `title`, `date`, `start_time`, `end_time`, `purpose`, `status`) VALUES
(1, 2, 'Thực hành Lập trình PHP/MySQL',        CURDATE(), '07:00:00', '09:00:00', 'Buổi thực hành môn LTW nhóm N01', 'approved'),
(2, 2, 'Thực hành CCNA Module 3',              CURDATE(), '09:00:00', '11:00:00', 'Cấu hình VLAN và Trunking', 'approved'),
(1, 6, 'Seminar An toàn thông tin',            CURDATE(), '13:00:00', '15:00:00', 'Giới thiệu OWASP Top 10', 'pending'),
(4, 2, 'Thực hành Thiết kế đồ họa',           DATE_ADD(CURDATE(), INTERVAL 1 DAY), '07:00:00', '09:00:00', 'Adobe Illustrator cơ bản', 'approved'),
(3, 6, 'Lab IoT - Đề tài nhà thông minh',     DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '11:30:00', 'Lập trình Arduino điều khiển thiết bị qua WiFi', 'pending'),
(8, 6, 'Thực hành Machine Learning',           DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:00:00', '16:00:00', 'Training mô hình CNN phân loại ảnh', 'approved'),
(7, 2, 'Hội thảo Công nghệ Blockchain',        DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '11:00:00', 'Seminar công nghệ', 'pending'),
(1, 4, 'Ôn tập trước kỳ thi cuối kỳ',         DATE_ADD(CURDATE(), INTERVAL 5 DAY), '15:00:00', '17:00:00', 'Nhóm sinh viên tự ôn tập', 'rejected'),
(2, 4, 'Thực hành môn Mạng máy tính',         DATE_ADD(CURDATE(), INTERVAL -1 DAY), '07:00:00', '09:00:00', 'Cấu hình RIP và OSPF', 'approved'),
(5, 6, 'Lab Pentest Web Application',          DATE_ADD(CURDATE(), INTERVAL -2 DAY), '13:00:00', '16:00:00', 'Tìm lỗ hổng SQL Injection, XSS', 'approved');

-- ── DEVICE BORROW ─────────────────────────────────────────
INSERT INTO `device_borrow` (`device_id`, `user_id`, `borrow_date`, `expected_return_date`, `actual_return_date`, `purpose`, `status`) VALUES
(14, 4, DATE_ADD(CURDATE(), INTERVAL -5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY),  NULL,    'Làm đồ án thiết kế', 'borrowed'),
(10, 5, DATE_ADD(CURDATE(), INTERVAL -3 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY),  NULL,    'Dự án IoT - nhà thông minh', 'borrowed'),
(9,  4, DATE_ADD(CURDATE(), INTERVAL -10 DAY),DATE_ADD(CURDATE(), INTERVAL -5 DAY), DATE_ADD(CURDATE(), INTERVAL -5 DAY), 'Thực hành Arduino nâng cao', 'returned'),
(3,  6, DATE_ADD(CURDATE(), INTERVAL -7 DAY), DATE_ADD(CURDATE(), INTERVAL -2 DAY), DATE_ADD(CURDATE(), INTERVAL -2 DAY), 'Chiếu bài thuyết trình', 'returned'),
(14, 5, DATE_ADD(CURDATE(), INTERVAL -15 DAY),DATE_ADD(CURDATE(), INTERVAL -8 DAY), DATE_ADD(CURDATE(), INTERVAL -8 DAY), 'Làm bán đồ án', 'returned');

-- ── REPORTS ───────────────────────────────────────────────
INSERT INTO `reports` (`room_id`, `device_id`, `user_id`, `title`, `description`, `severity`, `status`, `resolution`) VALUES
(1, 1,    4, 'Máy tính A3-101 số 12 không khởi động được',
    'Máy số 12 bị lỗi không POST, không có tín hiệu lên màn hình. Kiểm tra thấy đèn nguồn nhấp nháy 3 lần.', 'high', 'processing', NULL),
(2, 6,    5, 'Router Cisco 2901 không nhận cổng GigabitEthernet 0/1',
    'Cổng GE0/1 của router bị DOWN, không UP được. Đã thử thay cáp nhưng vẫn không lên.', 'medium', 'open', NULL),
(4, 13,   4, 'Màn hình LG 27" bị sáng chóa góc trái',
    'Màn hình số 15 bị backlight bleeding nặng ở góc trái bên dưới, ảnh hưởng đến công việc thiết kế.', 'low', 'resolved',
    'Đã liên hệ nhà cung cấp bảo hành. Màn hình được thay thế ngày 2026-04-10.'),
(NULL, NULL, 2, 'Điều hòa phòng A3-201 không mát',
    'Điều hòa phòng A3-201 hoạt động nhưng không đủ lạnh, nhiệt độ duy trì khoảng 28-30 độ trong phòng 40 người.', 'medium', 'processing', NULL),
(1, NULL, 4, 'Mất điện đột ngột phòng A3-101',
    'Phòng A3-101 bị mất điện vào 09:30 ngày hôm qua, 5 máy tính bị tắt đột ngột khi đang làm bài thi.', 'critical', 'resolved',
    'Điện do CB tổng bị trip. Đã reset CB và kiểm tra tải điện phòng. Ổn định sau 15 phút. Khuyến cáo sinh viên lưu bài thường xuyên.');

-- ── LOGS ──────────────────────────────────────────────────
INSERT INTO `logs` (`user_id`, `action`, `detail`, `created_at`) VALUES
(1, 'login',           'Đăng nhập thành công từ IP: 127.0.0.1',              '2026-04-15 07:30:00'),
(1, 'create_room',     'Tạo phòng A3-302',                                   '2026-04-15 07:35:00'),
(2, 'login',           'Đăng nhập thành công từ IP: 192.168.1.10',           '2026-04-15 08:00:00'),
(2, 'create_schedule', 'Đặt lịch phòng A3-101 ngày hôm nay 07:00-09:00',    '2026-04-15 08:05:00'),
(1, 'approve_schedule','Phê duyệt lịch ID 1',                                '2026-04-15 08:10:00'),
(4, 'login',           'Đăng nhập thành công từ IP: 192.168.1.25',           '2026-04-15 08:30:00'),
(4, 'borrow_device',   'Mượn thiết bị Wacom Intuos Pro Medium',              '2026-04-15 08:35:00'),
(4, 'create_report',   'Tạo báo cáo sự cố: Máy tính A3-101 số 12',          '2026-04-15 09:00:00'),
(3, 'login',           'Đăng nhập thành công từ IP: 192.168.1.5',            '2026-04-15 09:15:00'),
(3, 'update_report_status','Cập nhật trạng thái báo cáo ID 1 -> processing','2026-04-15 09:20:00'),
(1, 'create_user',     'Thêm user: minh.hv@a3.edu.vn',                      '2026-04-15 10:00:00'),
(5, 'login',           'Đăng nhập thành công từ IP: 192.168.1.30',           '2026-04-15 10:30:00'),
(5, 'borrow_device',   'Mượn thiết bị Raspberry Pi 4 ID 10',                '2026-04-15 10:35:00'),
(5, 'create_report',   'Tạo báo cáo: Router Cisco 2901 không nhận cổng',    '2026-04-15 11:00:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Kiểm tra dữ liệu
-- ============================================================
SELECT 'users'         AS tbl, COUNT(*) AS cnt FROM users
UNION SELECT 'rooms',          COUNT(*) FROM rooms
UNION SELECT 'devices',        COUNT(*) FROM devices
UNION SELECT 'room_schedule',  COUNT(*) FROM room_schedule
UNION SELECT 'device_borrow',  COUNT(*) FROM device_borrow
UNION SELECT 'reports',        COUNT(*) FROM reports
UNION SELECT 'logs',           COUNT(*) FROM logs;
