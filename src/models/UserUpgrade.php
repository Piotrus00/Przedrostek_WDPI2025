<?php

namespace App\Models;

require_once __DIR__ . '/../repository/UpgradesRepository.php';

use UpgradesRepository;

class UserUpgrade
{
    public function __construct(
        public int $userId,
        public int $upgradeId,
        public int $level
    ) {}

    public static function getLevels(int $userId): array
    {
        $repository = new UpgradesRepository();
        return $repository->getUserUpgradeLevels($userId);
    }

    public static function setLevel(int $userId, int $upgradeId, int $level): void
    {
        $repository = new UpgradesRepository();
        $repository->setUserUpgradeLevel($userId, $upgradeId, $level);
    }
}
