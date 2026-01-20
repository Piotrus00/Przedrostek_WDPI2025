<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class UpgradesController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    #[RequireLogin]
    public function index(): void
    {
        $this->render('upgrades');
    }

    private function getUpgradesDefinition(): array
    {
        return [
            ['id' => '1', 'title' => 'Additional 7', 'description' => '2x 7 chances', 'baseCost' => 20, 'maxLevel' => 5],
            ['id' => '2', 'title' => 'Black Multiplier', 'description' => '+0.2x multiplier', 'baseCost' => 100, 'maxLevel' => 5],
            ['id' => '3', 'title' => 'Red Multiplier', 'description' => '+0.2x multiplier', 'baseCost' => 100, 'maxLevel' => 5],
            ['id' => '4', 'title' => 'Green Multiplier', 'description' => '2x multiplier', 'baseCost' => 100, 'maxLevel' => 5],
            ['id' => '5', 'title' => 'Lucky Green', 'description' => '2x green chance', 'baseCost' => 75, 'maxLevel' => 4],
            ['id' => '6', 'title' => 'Refund', 'description' => '1% refund chance', 'baseCost' => 250, 'maxLevel' => 5],
            ['id' => '7', 'title' => 'More Money', 'description' => '+0.1x more money', 'baseCost' => 500, 'maxLevel' => 10],
        ];
    }

    private function getUserUpgrades(): array
    {
        if (!isset($_SESSION['upgrades']) || !is_array($_SESSION['upgrades'])) {
            $_SESSION['upgrades'] = [];
        }
        return $_SESSION['upgrades'];
    }

    private function setUserUpgradeLevel(string $id, int $level): void
    {
        $_SESSION['upgrades'][$id] = $level;
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
        $levels = $this->getUserUpgrades();

        $buildUpgrades = function () use ($definitions, $levels): array {
            return array_map(function ($def) use ($levels) {
                $id = $def['id'];
                $currentLevel = isset($levels[$id]) ? (int) $levels[$id] : 0;
                return array_merge($def, ['currentLevel' => $currentLevel]);
            }, $definitions);
        };

        if ($this->isGet()) {
            $balance = isset($_SESSION['user_balance'])
                ? (int) $_SESSION['user_balance']
                : $this->userRepository->getUserBalanceById((int) $userId);
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

        $definition = null;
        foreach ($definitions as $def) {
            if ($def['id'] === $upgradeId) {
                $definition = $def;
                break;
            }
        }

        if (!$definition) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Upgrade not found']);
            return;
        }

        $currentLevel = isset($levels[$upgradeId]) ? (int) $levels[$upgradeId] : 0;
        if ($currentLevel >= (int) $definition['maxLevel']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Upgrade already maxed']);
            return;
        }

        $nextCost = (int) $definition['baseCost'] * ($currentLevel + 1);

        $balance = isset($_SESSION['user_balance'])
            ? (int) $_SESSION['user_balance']
            : $this->userRepository->getUserBalanceById((int) $userId);

        if ($balance < $nextCost) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
            return;
        }

        $newBalance = $balance - $nextCost;
        $this->userRepository->updateUserBalance((int) $userId, $newBalance);
        $_SESSION['user_balance'] = $newBalance;

        $this->setUserUpgradeLevel($upgradeId, $currentLevel + 1);

        echo json_encode([
            'success' => true,
            'balance' => $newBalance,
            'upgrades' => $buildUpgrades()
        ]);
    }
}
