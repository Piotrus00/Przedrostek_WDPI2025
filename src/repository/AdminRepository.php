<?php

require_once 'Repository.php';

class AdminRepository extends Repository
{
    public function getUsers(): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT id, email, firstname, lastname, role, balance, created_at, enabled
            FROM users
            ORDER BY id
        ');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteUser(int $userId): void
    {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM users WHERE id = :id
        ');
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function setUserEnabled(int $userId, bool $enabled): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET enabled = :enabled WHERE id = :id
        ');
        $stmt->bindParam(':enabled', $enabled, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getLoginAttemptStats(int $limit = 50): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT email, ip_address, failed_last_hour, last_attempt_at, blocked_until
            FROM v_login_attempt_stats
            ORDER BY failed_last_hour DESC, last_attempt_at DESC
            LIMIT :limit
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRecentLoginAttempts(int $limit = 50): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT id, email, ip_address, success, attempted_at, blocked_until
            FROM v_login_attempts_recent
            LIMIT :limit
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
