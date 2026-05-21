<?php
// ============================================================
// models/BorrowModel.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class BorrowModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT b.*, d.device_name, d.device_type, r.room_code,
                    u.full_name AS borrower_name, u.email AS borrower_email
             FROM device_borrow b
             LEFT JOIN devices d ON d.id = b.device_id
             LEFT JOIN rooms r ON r.id = d.room_id
             LEFT JOIN users u ON u.id = b.user_id
             ORDER BY b.borrow_date DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getByUser(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT b.*, d.device_name, r.room_code
             FROM device_borrow b
             LEFT JOIN devices d ON d.id=b.device_id
             LEFT JOIN rooms r ON r.id=d.room_id
             WHERE b.user_id=? ORDER BY b.borrow_date DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM device_borrow")->fetchColumn();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT b.*, d.device_name, u.full_name AS borrower_name
             FROM device_borrow b
             LEFT JOIN devices d ON d.id=b.device_id
             LEFT JOIN users u ON u.id=b.user_id
             WHERE b.id=?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO device_borrow (device_id, user_id, borrow_date, expected_return_date, purpose, status, created_at)
             VALUES (?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $data['device_id'],
            $data['user_id'],
            $data['borrow_date'],
            $data['expected_return_date'],
            $data['purpose'] ?? '',
            'borrowed',
        ]);
        return $this->db->lastInsertId() ?: false;
    }

    public function returnDevice(int $id, string $returnDate, string $note = ''): bool {
        $stmt = $this->db->prepare(
            "UPDATE device_borrow SET actual_return_date=?, return_note=?, status='returned' WHERE id=?"
        );
        return $stmt->execute([$returnDate, $note, $id]);
    }

    public function countBorrowed(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM device_borrow WHERE status='borrowed'")->fetchColumn();
    }

    public function getOverdue(): array {
        $stmt = $this->db->query(
            "SELECT b.*, d.device_name, u.full_name AS borrower_name, u.email
             FROM device_borrow b
             LEFT JOIN devices d ON d.id=b.device_id
             LEFT JOIN users u ON u.id=b.user_id
             WHERE b.status='borrowed' AND b.expected_return_date < CURDATE()"
        );
        return $stmt->fetchAll();
    }
}
