<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UpgradesRepository.php';

class UpgradesController extends AppController
{
    private UserRepository $userRepository;
    private UpgradesRepository $upgradesRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->upgradesRepository = new UpgradesRepository();
    }

    #[RequireLogin]
    public function index(): void
    {
        $this->render('upgrades');
    }

    private function getUpgradesDefinition(): array
    {
    # use the repository to fetch upgrade definitions
    $definitions = $this->upgradesRepository->getDefinitions();

        return array_map(function (array $def) {
            return [
                'id' => (string) $def['id'],
                'title' => $def['title'],
                'description' => $def['description'],
                'baseCost' => (int) $def['base_cost'],
                'maxLevel' => (int) $def['max_level']
            ];
        }, $definitions);
    }

    private function getUserUpgrades(int $userId): array
    {
        return $this->upgradesRepository->getUserUpgradeLevels($userId);
    }

    private function setUserUpgradeLevel(int $userId, int $upgradeId, int $level): void
    {
        $this->upgradesRepository->setUserUpgradeLevel($userId, $upgradeId, $level);
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

        $upgradeIdInt = (int) $upgradeId;

        $definition = null;
        foreach ($definitions as $def) {
            if ((int) $def['id'] === $upgradeIdInt) {
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

        $this->setUserUpgradeLevel((int) $userId, $upgradeIdInt, $currentLevel + 1);

        echo json_encode([
            'success' => true,
            'balance' => $newBalance,
            'upgrades' => $buildUpgrades()
        ]);
    }
}
