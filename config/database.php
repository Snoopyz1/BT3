<?php
// ============================================================
// config/database.php — Cấu hình kết nối CSDL
// ============================================================

date_default_timezone_set('Asia/Ho_Chi_Minh');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'quan_ly_phong_thuc_hanh');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/BT3');
define('APP_NAME', 'Quản Lý Phòng Thực Hành Nhà A3');
define('APP_VERSION', '1.0.0');

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Kết nối CSDL thất bại: ' . $e->getMessage()
                ]));
            }
        }
        return self::$instance;
    }

    // Lấy kết nối để dùng trực tiếp
    public static function getConnection(): PDO {
        return self::getInstance();
    }
}
