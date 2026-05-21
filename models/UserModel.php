<?php
// ============================================================
// models/UserModel.php
// ============================================================
require_once __DIR__ . '/../config/database.php';

class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT id, full_name, email, role, phone, status, created_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function create(array $data): int|false {
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, password, role, phone, status, created_at)
             VALUES (?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role']    ?? 'student',
            $data['phone']   ?? '',
            $data['status']  ?? 'active',
        ]);
        return $this->db->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        $allowed = ['full_name', 'email', 'role', 'phone', 'status'];
        foreach ($allowed as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function login(string $email, string $password): array|false {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['password'])) return false;
        if ($user['status'] !== 'active') return false;
        return $user;
    }
}
