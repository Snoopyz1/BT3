<?php
// ============================================================
// models/DeviceModel.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class DeviceModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT d.*, r.room_code, r.name AS room_name
             FROM devices d
             LEFT JOIN rooms r ON r.id = d.room_id
             ORDER BY d.id DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getByRoom(int $roomId): array {
        $stmt = $this->db->prepare("SELECT * FROM devices WHERE room_id = ? ORDER BY device_name");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM devices")->fetchColumn();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT d.*, r.room_code FROM devices d LEFT JOIN rooms r ON r.id=d.room_id WHERE d.id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO devices (room_id, device_name, device_type, serial_number, quantity, status, description, created_at)
             VALUES (?,?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $data['room_id'],
            $data['device_name'],
            $data['device_type']    ?? 'other',
            $data['serial_number']  ?? '',
            $data['quantity']       ?? 1,
            $data['status']         ?? 'available',
            $data['description']    ?? '',
        ]);
        return $this->db->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE devices SET room_id=?, device_name=?, device_type=?, serial_number=?,
             quantity=?, status=?, description=? WHERE id=?"
        );
        return $stmt->execute([
            $data['room_id'],
            $data['device_name'],
            $data['device_type'],
            $data['serial_number'],
            $data['quantity'],
            $data['status'],
            $data['description'],
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM devices WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE devices SET status=? WHERE id=?");
        return $stmt->execute([$status, $id]);
    }

    public function countByStatus(): array {
        $stmt = $this->db->query("SELECT status, COUNT(*) as cnt FROM devices GROUP BY status");
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = $row['cnt'];
        }
        return $result;
    }

    public function getAvailableDevices(): array {
        $stmt = $this->db->query(
            "SELECT d.*, r.room_code FROM devices d LEFT JOIN rooms r ON r.id=d.room_id WHERE d.status='available'"
        );
        return $stmt->fetchAll();
    }
}
