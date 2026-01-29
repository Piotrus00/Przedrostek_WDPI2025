<?php

require_once 'Repository.php';

class LoginAttemptsRepository extends Repository
{
    public function logAttempt(string $email, string $ipAddress, bool $success, ?string $blockedUntil = null): void
    {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO login_attempts (email, ip_address, success, blocked_until)
            VALUES (:email, :ip_address, :success, :blocked_until)
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->bindParam(':success', $success, PDO::PARAM_BOOL);
        if ($blockedUntil === null) {
            $stmt->bindValue(':blocked_until', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':blocked_until', $blockedUntil, PDO::PARAM_STR);
        }
        $stmt->execute();
        // Insert odpowiedzialny za logowanie prób logowania (SecurityController::login)
    }

    public function getActiveBlock(string $email, string $ipAddress): ?string
    {
        $stmt = $this->database->connect()->prepare('
            SELECT blocked_until
            FROM login_attempts
            WHERE email = :email AND ip_address = :ip_address AND blocked_until IS NOT NULL
            ORDER BY blocked_until DESC
            LIMIT 1
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['blocked_until'])) {
            return null;
        }

        return $row['blocked_until'];
    }

    public function countRecentFailures(string $email, string $ipAddress, int $seconds): int
    {
        $stmt = $this->database->connect()->prepare('
            SELECT COUNT(*) AS failures
            FROM login_attempts
            WHERE email = :email
              AND ip_address = :ip_address
              AND success = FALSE
              AND attempted_at > NOW() - (:seconds || \' seconds\')::interval
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->bindParam(':seconds', $seconds, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['failures'] : 0;
    }
}
