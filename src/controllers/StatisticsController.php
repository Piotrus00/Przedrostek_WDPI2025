<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UpgradesRepository.php';
require_once __DIR__ . '/../repository/StatisticsRepository.php';

class StatisticsController extends AppController
{
    private UserRepository $userRepository;
    private UpgradesRepository $upgradesRepository;
    private StatisticsRepository $statisticsRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->upgradesRepository = new UpgradesRepository();
        $this->statisticsRepository = new StatisticsRepository();
    }

    #[RequireLogin]
    public function index(): void
    {
        $this->render('statistics');
    }

    #[AllowedMethods(['GET'])]
    #[RequireLogin]
    public function statsApi(): void
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            return;
        }

        $balance = isset($_SESSION['user_balance'])
            ? (int) $_SESSION['user_balance']
            : $this->userRepository->getUserBalanceById((int) $userId);
        $_SESSION['user_balance'] = $balance;

        $gameStats = $this->statisticsRepository->getUserGameStats((int) $userId);
        $definitions = $this->upgradesRepository->getDefinitions();
        $levels = $this->upgradesRepository->getUserUpgradeLevels((int) $userId);

        $totalMaxLevels = 0;
        $totalBoughtLevels = 0;
        $totalSpent = 0;

        foreach ($definitions as $def) {
            $maxLevel = (int) $def['max_level'];
            $baseCost = (int) $def['base_cost'];
            $id = (string) $def['id'];
            $level = isset($levels[$id]) ? (int) $levels[$id] : 0;

            $totalMaxLevels += $maxLevel;
            $totalBoughtLevels += $level;
            if ($level > 0) {
                $totalSpent += $baseCost * ($level * ($level + 1) / 2);
            }
        }

        $remainingUpgrades = max(0, $totalMaxLevels - $totalBoughtLevels);

        $blackLevel = isset($levels['2']) ? (int) $levels['2'] : 0;
        $redLevel = isset($levels['3']) ? (int) $levels['3'] : 0;
        $greenLevel = isset($levels['4']) ? (int) $levels['4'] : 0;
        $luckyGreenLevel = isset($levels['5']) ? (int) $levels['5'] : 0;

        $response = [
            'success' => true,
            'general' => [
                'balance' => $balance,
                'totalNet' => (int) $gameStats['total_net'],
                'losses' => (int) $gameStats['losses'],
                'wins' => (int) $gameStats['wins'],
                'green' => (int) $gameStats['green'],
                'black' => (int) $gameStats['black'],
                'red' => (int) $gameStats['red'],
            ],
            'upgrades' => [
                'boughtUpgrades' => $totalBoughtLevels,
                'remainingUpgrades' => $remainingUpgrades,
                'totalSpent' => (int) $totalSpent,
                'greenMultiplier' => 1 + $greenLevel,
                'redMultiplier' => round(2 + (0.2 * $redLevel), 1),
                'blackMultiplier' => round(2 + (0.2 * $blackLevel), 1),
                'greenChance' => 1 + $luckyGreenLevel,
            ],
            'other' => [
                'totalGames' => (int) $gameStats['total_games'],
                'highestWin' => (int) $gameStats['highest_win'],
                'highestLoss' => (int) abs((int) $gameStats['highest_loss']),
            ]
        ];

        echo json_encode($response);
    }
}
