# 🏢 Quản Lý Phòng Thực Hành Nhà A3

> Hệ thống web quản lý phòng thực hành toàn diện cho Nhà A3 — PTIT  
> **Stack:** PHP 8.1+ (Thuần) · MySQL 8.0 · Bootstrap 5.3 · PDO

---

## 📋 Mô Tả Hệ Thống

Hệ thống **Quản Lý Phòng Thực Hành Nhà A3** là ứng dụng web MVC PHP thuần quản lý toàn bộ hoạt động phòng máy tính, phòng thực hành tại Nhà A3 trường PTIT.

### Tính năng chính

| Tính năng | Mô tả |
|-----------|-------|
| 🔐 **Phân quyền 4 cấp** | Admin, Giáo viên, Kỹ thuật viên, Sinh viên |
| 🏫 **Quản lý phòng** | CRUD phòng thực hành, ảnh, thông số |
| 📅 **Đặt lịch phòng** | Đặt lịch, phê duyệt, xem tuần/ngày |
| 💻 **Quản lý thiết bị** | CRUD thiết bị theo phòng |
| 🔄 **Mượn/Trả thiết bị** | Đăng ký mượn, xác nhận trả, cảnh báo quá hạn |
| 🚨 **Báo cáo sự cố** | Tạo báo cáo, theo dõi xử lý |
| 📊 **Dashboard** | Thống kê tổng quan real-time |
| 📝 **Nhật ký hệ thống** | Log toàn bộ hoạt động |
| 🔔 **Thông báo** | Polling API thông báo thời gian thực |
| 🌐 **REST API** | API nội bộ JSON |

---

## 🏗️ Kiến Trúc Dự Án

```
BT3/
├── 📄 index.php                  ← Entry point
├── 📁 config/
│   ├── database.php              ← Cấu hình & class Database (PDO)
│   └── helpers.php               ← Hàm tiện ích dùng chung
├── 📁 models/
│   ├── UserModel.php             ← CRUD người dùng + xác thực
│   ├── RoomModel.php             ← CRUD phòng thực hành
│   ├── DeviceModel.php           ← CRUD thiết bị
│   ├── ScheduleModel.php         ← Đặt lịch & kiểm tra trùng
│   ├── BorrowModel.php           ← Mượn/trả thiết bị
│   └── ReportModel.php           ← Báo cáo sự cố
├── 📁 controllers/
│   ├── AuthController.php        ← Đăng nhập / Đăng ký / Đăng xuất
│   ├── RoomController.php        ← Quản lý phòng + upload ảnh
│   ├── DeviceController.php      ← Quản lý thiết bị
│   ├── ScheduleController.php    ← Đặt lịch + phê duyệt
│   ├── BorrowController.php      ← Mượn/trả thiết bị
│   └── ReportController.php      ← Báo cáo sự cố
├── 📁 views/
│   ├── dashboard.php             ← Trang chủ/Dashboard
│   ├── logs.php                  ← Nhật ký hệ thống
│   ├── partials/
│   │   ├── header.php            ← HTML head + Navbar
│   │   ├── sidebar.php           ← Thanh điều hướng bên (role-based)
│   │   └── footer.php            ← Footer + JS scripts
│   ├── rooms/
│   │   └── index.php             ← Danh sách & CRUD phòng (card view)
│   ├── schedules/
│   │   └── index.php             ← Lịch đặt phòng + week calendar
│   ├── devices/
│   │   ├── index.php             ← Danh sách & CRUD thiết bị
│   │   └── borrow.php            ← Mượn/trả thiết bị
│   ├── reports/
│   │   └── index.php             ← Báo cáo sự cố
│   └── users/
│       └── index.php             ← Quản lý người dùng (Admin only)
├── 📁 auth/
│   ├── login.php                 ← Trang đăng nhập
│   ├── register.php              ← Trang đăng ký
│   └── logout.php                ← Xử lý đăng xuất
├── 📁 api/
│   ├── getRoomSchedule.php       ← API: Lấy lịch theo phòng/ngày
│   ├── reportIncident.php        ← API: Tạo báo cáo sự cố (POST JSON)
│   └── getNotifications.php      ← API: Thông báo polling
├── 📁 assets/
│   ├── css/style.css             ← Dark theme custom CSS
│   ├── js/app.js                 ← JavaScript chính
│   └── images/rooms/             ← Ảnh upload phòng
└── 📁 database/
    └── quan_ly_phong_thuc_hanh.sql   ← Script SQL hoàn chỉnh
```

---

## 🚀 Hướng Dẫn Cài Đặt

### Yêu cầu hệ thống

- **XAMPP** / **WAMP** / **Laragon** (PHP 8.1+ · MySQL 8.0+)
- PHP Extensions: `pdo_mysql`, `mbstring`, `fileinfo`
- Trình duyệt hiện đại (Chrome, Firefox, Edge)

### Bước 1: Đặt file vào thư mục web

```bash
# Sao chép toàn bộ thư mục BT3 vào htdocs (XAMPP)
```

### Bước 2: Import Database

1. Mở **XAMPP Control Panel** → Start **Apache** và **MySQL**
2. Truy cập: `http://localhost/phpmyadmin`
3. Click **Import** → Chọn file `database/quan_ly_phong_thuc_hanh.sql`
4. Click **Go** để import

**Hoặc** dùng MySQL CLI:
```bash
mysql -u root -p < database/quan_ly_phong_thuc_hanh.sql
```

### Bước 3: Cấu hình kết nối

Mở file `config/database.php` và chỉnh thông tin:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'quan_ly_phong_thuc_hanh');
define('DB_USER', 'root');      // ← tên user MySQL của bạn
define('DB_PASS', '');          // ← mật khẩu MySQL (mặc định XAMPP để trống)
define('BASE_URL', 'http://localhost/BT3');
```

### Bước 4: Chạy dự án

Truy cập: **`http://localhost/BT3/auth/login.php`**

---

## 👤 Tài Khoản Demo

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| 🔴 Admin | `admin@a3.edu.vn` | `password` |
| 🟡 Giáo viên | `teacher@a3.edu.vn` | `password` |
| 🟢 Kỹ thuật viên | `tech@a3.edu.vn` | `password` |
| 🔵 Sinh viên | `student@a3.edu.vn` | `password` |

> **Lưu ý:** Hash trong SQL dùng `password` (Laravel factory hash). Để đổi mật khẩu, chạy script PHP:
> ```php
> echo password_hash('admin123', PASSWORD_BCRYPT);
> ```
> Sau đó UPDATE trực tiếp vào bảng `users`.

---

## 🌐 REST API

### 1. Lấy lịch phòng theo ngày

```
GET /api/getRoomSchedule.php?room_id=1&date=2026-04-15
```

**Response:**
```json
{
  "success": true,
  "room_id": 1,
  "date": "2026-04-15",
  "schedules": [
    {
      "id": 1,
      "title": "Thực hành PHP/MySQL",
      "start_time": "07:00:00",
      "end_time": "09:00:00",
      "user_name": "Nguyễn Văn Thắng",
      "status": "approved"
    }
  ],
  "count": 1
}
```

### 2. Tạo báo cáo sự cố

```
POST /api/reportIncident.php
Content-Type: application/json

{
  "title": "Máy tính bị lỗi màn hình",
  "description": "Màn hình số 5 không có tín hiệu",
  "severity": "high",
  "room_id": 1,
  "device_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Báo cáo sự cố đã được tạo thành công.",
  "report_id": 10
}
```

### 3. Lấy thông báo

```
GET /api/getNotifications.php
```

---

## 🔐 Phân Quyền Hệ Thống

| Chức năng | Admin | GV | KTV | SV |
|-----------|:-----:|:--:|:---:|:--:|
| Xem Dashboard | ✅ | ✅ | ✅ | ✅ |
| Xem phòng | ✅ | ✅ | ✅ | ✅ |
| Thêm/Sửa phòng | ✅ | ❌ | ✅ | ❌ |
| Xóa phòng | ✅ | ❌ | ❌ | ❌ |
| Xem lịch | ✅ | ✅ | ✅ | ✅ |
| Đặt lịch | ✅ | ✅ | ✅ | ✅ |
| Phê duyệt lịch | ✅ | ❌ | ❌ | ❌ |
| Quản lý thiết bị | ✅ | ❌ | ✅ | ❌ |
| Mượn/Trả thiết bị | ✅ | ✅ | ✅ | ✅ |
| Báo cáo sự cố | ✅ | ✅ | ✅ | ✅ |
| Xử lý sự cố | ✅ | ❌ | ✅ | ❌ |
| Quản lý người dùng | ✅ | ❌ | ❌ | ❌ |
| Xem nhật ký | ✅ | ❌ | ❌ | ❌ |

---

## 🛠️ Bảo Mật

- ✅ **CSRF Token** trên mọi form POST
- ✅ **Password Hash** bằng `password_hash()` (bcrypt)
- ✅ **PDO Prepared Statements** — chống SQL Injection
- ✅ **`htmlspecialchars()`** — chống XSS
- ✅ **`clean()`** — sanitize toàn bộ input
- ✅ **Kiểm tra session** — requireLogin() & requireRole()
- ✅ **Kiểm tra conflict** lịch đặt phòng

---

## 🔧 Gợi Ý Mở Rộng Tương Lai

- [ ] **Email Notification** — Gửi email khi lịch được duyệt/từ chối
- [ ] **QR Code** — Mã QR cho từng phòng để check-in nhanh
- [ ] **Calendar Export** — Xuất lịch ra file `.ics` (Google Calendar)
- [ ] **Barcode Scanner** — Quét mã thiết bị khi mượn/trả
- [ ] **Thống kê nâng cao** — Biểu đồ Chart.js, báo cáo Excel/PDF
- [ ] **Ứng dụng mobile** — React Native kết nối qua REST API
- [ ] **WebSocket** — Thông báo real-time thay vì polling
- [ ] **OAuth2** — Đăng nhập bằng Google/Microsoft PTIT
- [ ] **Multi-building** — Mở rộng cho nhiều tòa nhà
- [ ] **Maintenance schedule** — Lịch bảo trì định kỳ thiết bị

---

## 📞 Hỗ Trợ

**Hệ Thống Quản Lý Phòng Thực Hành Nhà A3**  
Học Viện Công Nghệ Bưu Chính Viễn Thông (PTIT)  
Phiên bản: `1.0.0` | PHP 8.1+ | Bootstrap 5.3

---

*Được phát triển bởi nhóm sinh viên PTIT — Môn Lập Trình Web*
