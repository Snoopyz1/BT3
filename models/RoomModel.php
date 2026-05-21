<?php
// ============================================================
// models/RoomModel.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class RoomModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, 
                    (SELECT COUNT(*) FROM devices d WHERE d.room_id = r.id) AS device_count
             FROM rooms r ORDER BY r.room_code ASC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByCode(string $code): array|false {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function create(array $data): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO rooms (room_code, name, floor, capacity, computer_count, description, image, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $data['room_code'],
            $data['name'],
            $data['floor']          ?? 1,
            $data['capacity']       ?? 0,
            $data['computer_count'] ?? 0,
            $data['description']    ?? '',
            $data['image']          ?? '',
            $data['status']         ?? 'active',
        ]);
        return $this->db->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE rooms SET room_code=?, name=?, floor=?, capacity=?, computer_count=?,
             description=?, image=?, status=? WHERE id=?"
        );
        return $stmt->execute([
            $data['room_code'],
            $data['name'],
            $data['floor'],
            $data['capacity'],
            $data['computer_count'],
            $data['description'],
            $data['image'],
            $data['status'],
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getActiveRooms(): array {
        $stmt = $this->db->query("SELECT id, room_code, name FROM rooms WHERE status='active' ORDER BY room_code");
        return $stmt->fetchAll();
    }

    public function countByStatus(): array {
        $stmt = $this->db->query("SELECT status, COUNT(*) as cnt FROM rooms GROUP BY status");
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = $row['cnt'];
        }
        return $result;
    }
}
