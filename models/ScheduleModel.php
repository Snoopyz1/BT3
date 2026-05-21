<?php
// ============================================================
// models/ScheduleModel.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class ScheduleModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, r.room_code, r.name AS room_name, u.full_name AS user_name, u.email
             FROM room_schedule s
             LEFT JOIN rooms r ON r.id = s.room_id
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.date DESC, s.start_time DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM room_schedule")->fetchColumn();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT s.*, r.room_code, r.name AS room_name, u.full_name AS user_name
             FROM room_schedule s
             LEFT JOIN rooms r ON r.id = s.room_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByDate(string $date): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, r.room_code, r.name AS room_name, u.full_name AS user_name
             FROM room_schedule s
             LEFT JOIN rooms r ON r.id = s.room_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.date = ?
             ORDER BY s.start_time ASC"
        );
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    public function getByWeek(string $startDate, string $endDate): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, r.room_code, r.name AS room_name, u.full_name AS user_name
             FROM room_schedule s
             LEFT JOIN rooms r ON r.id = s.room_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.date BETWEEN ? AND ?
             ORDER BY s.date ASC, s.start_time ASC"
        );
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function getByUser(int $userId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, r.room_code, r.name AS room_name
             FROM room_schedule s
             LEFT JOIN rooms r ON r.id = s.room_id
             WHERE s.user_id = ?
             ORDER BY s.date DESC LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function isConflict(int $roomId, string $date, string $start, string $end, int $excludeId = 0): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM room_schedule
             WHERE room_id = ? AND date = ?
               AND status NOT IN ('rejected','cancelled')
               AND id != ?
               AND NOT (end_time <= ? OR start_time >= ?)"
        );
        $stmt->execute([$roomId, $date, $excludeId, $start, $end]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO room_schedule (room_id, user_id, title, date, start_time, end_time, purpose, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $data['room_id'],
            $data['user_id'],
            $data['title'],
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['purpose'] ?? '',
            $data['status']  ?? 'pending',
        ]);
        return $this->db->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE room_schedule SET room_id=?, title=?, date=?, start_time=?, end_time=?, purpose=?, status=? WHERE id=?"
        );
        return $stmt->execute([
            $data['room_id'],
            $data['title'],
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['purpose'],
            $data['status'],
            $id,
        ]);
    }

    public function updateStatus(int $id, string $status, string $note = ''): bool {
        $stmt = $this->db->prepare("UPDATE room_schedule SET status=?, admin_note=? WHERE id=?");
        return $stmt->execute([$status, $note, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM room_schedule WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function getByRoom(int $roomId, string $date): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.full_name AS user_name FROM room_schedule s
             LEFT JOIN users u ON u.id=s.user_id
             WHERE s.room_id=? AND s.date=? AND s.status='approved'
             ORDER BY s.start_time"
        );
        $stmt->execute([$roomId, $date]);
        return $stmt->fetchAll();
    }

    public function countPending(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM room_schedule WHERE status='pending'")->fetchColumn();
    }

    public function countByStatus(): array {
        $stmt = $this->db->query("SELECT status, COUNT(*) as cnt FROM room_schedule GROUP BY status");
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = $row['cnt'];
        }
        return $result;
    }
}
