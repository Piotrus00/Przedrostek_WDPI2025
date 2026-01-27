<?php

require_once 'Repository.php';

class EarningsRepository extends Repository
{
    public function applyDailyEarning(int $userId, int $amount = 500, int $hours = 24): ?int
    {
        $connection = $this->database->connect();
        try {
            $connection->beginTransaction();

            $stmt = $connection->prepare('
                SELECT last_claimed
                FROM earnings
                WHERE user_id = :user_id
                FOR UPDATE
            ');
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastClaimed = $row ? $row['last_claimed'] : null;

            if (!$row) {
                $insert = $connection->prepare('
                    INSERT INTO earnings (user_id, last_claimed)
                    VALUES (:user_id, NULL)
                ');
                $insert->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $insert->execute();
            }

            $eligible = !$lastClaimed || (strtotime($lastClaimed) <= (time() - ($hours * 3600)));
            if (!$eligible) {
                $connection->commit();
                return null;
            }

            $updateBalance = $connection->prepare('
                UPDATE users
                SET balance = balance + :amount
                WHERE id = :user_id
                RETURNING balance
            ');
            $updateBalance->bindParam(':amount', $amount, PDO::PARAM_INT);
            $updateBalance->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $updateBalance->execute();
            $newBalanceRow = $updateBalance->fetch(PDO::FETCH_ASSOC);
            $newBalance = $newBalanceRow ? (int) $newBalanceRow['balance'] : null;

            $updateEarning = $connection->prepare('
                UPDATE earnings
                SET last_claimed = NOW()
                WHERE user_id = :user_id
            ');
            $updateEarning->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $updateEarning->execute();

            $connection->commit();
            return $newBalance;
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }
}
