<?php

require_once 'Repository.php';

class UpgradesRepository extends Repository
{
    public function getDefinitions(): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT id, title, description, base_cost, max_level
            FROM upgrades
            ORDER BY id
        ');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getUserUpgradeLevels(int $userId): array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT upgrade_id, level
            FROM user_upgrades
            WHERE user_id = :user_id
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $levels = [];
        foreach ($rows as $row) {
            $levels[(string) $row['upgrade_id']] = (int) $row['level'];
        }

        return $levels;
    }

    public function setUserUpgradeLevel(int $userId, int $upgradeId, int $level): void
    {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO user_upgrades (user_id, upgrade_id, level)
            VALUES (:user_id, :upgrade_id, :level)
            ON CONFLICT (user_id, upgrade_id)
            DO UPDATE SET level = EXCLUDED.level
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':upgrade_id', $upgradeId, PDO::PARAM_INT);
        $stmt->bindParam(':level', $level, PDO::PARAM_INT);
        $stmt->execute();
        // Insert odpowiedzialny za zapis poziomu ulepszenia (UpgradesController::upgradesApi)
    }
}
