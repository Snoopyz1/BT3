<?php
// ============================================================
// database/generate_hashes.php
// Chạy file này để lấy hash mật khẩu cần cập nhật vào SQL
// Truy cập: http://localhost/BT3/database/generate_hashes.php
// ============================================================

// Bảo mật: chỉ chạy từ CLI hoặc localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(403);
    die('Forbidden');
}

$passwords = [
    'admin123'      => 'Mật khẩu Admin',
    'teacher123'    => 'Mật khẩu Giáo viên',
    'tech123'       => 'Mật khẩu Kỹ thuật viên',
    'student123'    => 'Mật khẩu Sinh viên',
    'password'      => 'Mật khẩu demo',
];

echo "=== BCrypt Hash Generator ===\n\n";
foreach ($passwords as $pwd => $label) {
    $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "$label ($pwd):\n$hash\n\n";
}

echo "\n=== Cập nhật SQL ===\n";
echo "Chạy lệnh sau trong MySQL để cập nhật mật khẩu admin:\n";
$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
echo "UPDATE users SET password='$adminHash' WHERE email='admin\@a3.edu.vn';\n";

echo "\nHoặc update tất cả accounts với mật khẩu 'admin123':\n";
echo "UPDATE users SET password='$adminHash';\n";
