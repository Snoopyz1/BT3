<?php
// ============================================================
// models/ReportModel.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class ReportModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT rp.*, r.room_code, r.name AS room_name,
                    d.device_name,
                    u.full_name AS reporter_name
             FROM reports rp
             LEFT JOIN rooms r ON r.id = rp.room_id
             LEFT JOIN devices d ON d.id = rp.device_id
             LEFT JOIN users u ON u.id = rp.user_id
             ORDER BY rp.created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT rp.*, r.room_code, d.device_name, u.full_name AS reporter_name
             FROM reports rp
             LEFT JOIN rooms r ON r.id=rp.room_id
             LEFT JOIN devices d ON d.id=rp.device_id
             LEFT JOIN users u ON u.id=rp.user_id
             WHERE rp.id=?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO reports (room_id, device_id, user_id, title, description, severity, status, created_at)
             VALUES (?,?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $data['room_id']    ?? null,
            $data['device_id']  ?? null,
            $data['user_id'],
            $data['title'],
            $data['description'],
            $data['severity']   ?? 'medium',
            'open',
        ]);
        return $this->db->lastInsertId() ?: false;
    }

    public function updateStatus(int $id, string $status, string $resolution = ''): bool {
        $stmt = $this->db->prepare("UPDATE reports SET status=?, resolution=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$status, $resolution, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM reports WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function countByStatus(): array {
        $stmt = $this->db->query("SELECT status, COUNT(*) as cnt FROM reports GROUP BY status");
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = $row['cnt'];
        }
        return $result;
    }

    public function countOpen(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM reports WHERE status='open'")->fetchColumn();
    }

    public function getRecent(int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT rp.*, r.room_code, u.full_name AS reporter_name
             FROM reports rp
             LEFT JOIN rooms r ON r.id=rp.room_id
             LEFT JOIN users u ON u.id=rp.user_id
             ORDER BY rp.created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
