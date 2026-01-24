<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../models/UpgradeDefinition.php';
require_once __DIR__ . '/../models/UserUpgrade.php';
require_once __DIR__ . '/../models/UserDefinition.php';

use App\Models\UpgradeDefinition;
use App\Models\UserUpgrade;
use App\Models\UserDefinition;

class UpgradesController extends AppController
{
    #[RequireLogin]
    public function index(): void
    {
        $this->render('upgrades');
    }

    private function getUpgradesDefinition(): array
    {
        return UpgradeDefinition::fetchAll();
    }

    private function getUserUpgrades(int $userId): array
    {
        return UserUpgrade::getLevels($userId);
    }

    private function setUserUpgradeLevel(int $userId, int $upgradeId, int $level): void
    {
        UserUpgrade::setLevel($userId, $upgradeId, $level);
    }

    #[AllowedMethods(['GET', 'POST'])]
    #[RequireLogin]
    public function upgradesApi(): void
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            return;
        }

        $definitions = $this->getUpgradesDefinition();
        $levels = $this->getUserUpgrades((int) $userId);

        $buildUpgrades = function () use ($definitions, $levels): array {
            return array_map(function (UpgradeDefinition $def) use ($levels): array {
                $id = (string) $def->id;
                $currentLevel = isset($levels[$id]) ? (int) $levels[$id] : 0;
                return array_merge($def->toArray(), ['currentLevel' => $currentLevel]);
            }, $definitions);
        };

        if ($this->isGet()) {
            $balance = isset($_SESSION['user_balance'])
                ? (int) $_SESSION['user_balance']
                : UserDefinition::getBalanceById((int) $userId);
            $_SESSION['user_balance'] = $balance;

            echo json_encode([
                'success' => true,
                'balance' => $balance,
                'upgrades' => $buildUpgrades()
            ]);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            return;
        }

        $upgradeId = $input['id'] ?? null;
        if (!$upgradeId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing upgrade id']);
            return;
        }

        $upgradeIdInt = (int) $upgradeId;

        $definition = null;
        foreach ($definitions as $def) {
            if ((int) $def->id === $upgradeIdInt) {
                $definition = $def;
                break;
            }
        }

        if (!$definition) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Upgrade not found']);
            return;
        }

        $currentLevel = isset($levels[(string) $upgradeIdInt]) ? (int) $levels[(string) $upgradeIdInt] : 0;
        if ($currentLevel >= (int) $definition->maxLevel) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Upgrade already maxed']);
            return;
        }

        $nextCost = $definition->nextCost($currentLevel);

        $balance = isset($_SESSION['user_balance'])
            ? (int) $_SESSION['user_balance']
            : UserDefinition::getBalanceById((int) $userId);

        if ($balance < $nextCost) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
            return;
        }

        $newBalance = $balance - $nextCost;
        UserDefinition::updateBalance((int) $userId, $newBalance);
        $_SESSION['user_balance'] = $newBalance;

        $this->setUserUpgradeLevel((int) $userId, $upgradeIdInt, $currentLevel + 1);

        echo json_encode([
            'success' => true,
            'balance' => $newBalance,
            'upgrades' => $buildUpgrades()
        ]);
    }
}
