<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../repository/StatisticsRepository.php';
require_once __DIR__ . '/../models/UpgradeDefinition.php';
require_once __DIR__ . '/../models/UserUpgrade.php';
require_once __DIR__ . '/../models/UserDefinition.php';

use App\Models\UpgradeDefinition;
use App\Models\UserUpgrade;
use App\Models\UserDefinition;

class StatisticsController extends AppController
{
    private StatisticsRepository $statisticsRepository;

    public function __construct()
    {
        parent::__construct();
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
        header('Content-Type: application/json'); // zwracamy JSON

        $userId = $_SESSION['user_id'] ?? null; // pobranie ID użytkownika z sesji
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            return;
        }

        $balance = isset($_SESSION['user_balance']) // pobranie salda z sesji/bazy
            ? (int) $_SESSION['user_balance']
            : UserDefinition::getBalanceById((int) $userId);
        $_SESSION['user_balance'] = $balance; // aktualizacja salda w sesji

        $gameStats = $this->statisticsRepository->getUserGameStats((int) $userId); // pobranie statystyk gier
        $definitions = UpgradeDefinition::fetchAll(); // pobranie definicji ulepszeń
        $levels = UserUpgrade::getLevels((int) $userId); // pobranie poziomów ulepszeń użytkownika

        $totalMaxLevels = 0;
        $totalBoughtLevels = 0;

        $totalSpent = $this->statisticsRepository->getTotalUpgradesCost((int) $userId);

        # przypisanie statystyk ulepszeń
        foreach ($definitions as $def) {
            $maxLevel = (int) $def->maxLevel;
            $id = (string) $def->id;
            $level = isset($levels[$id]) ? (int) $levels[$id] : 0;

            $totalMaxLevels += $maxLevel;
            $totalBoughtLevels += $level;
        }

        $remainingUpgrades = max(0, $totalMaxLevels - $totalBoughtLevels); // pozostałe ulepszenia do kupienia

        #pobranie poziomów poszczególnych ulepszeń
        $blackLevel = isset($levels['2']) ? (int) $levels['2'] : 0; //czarny
        $redLevel = isset($levels['3']) ? (int) $levels['3'] : 0; //czerwony
        $greenLevel = isset($levels['4']) ? (int) $levels['4'] : 0; //zielony
        $luckyGreenLevel = isset($levels['5']) ? (int) $levels['5'] : 0; //szansa zielony

        # przygotowanie odpowiedzi JSON
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
                'greenMultiplier' => 36 + $greenLevel,
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

        echo json_encode($response); // zwrócenie odpowiedzi JSON
    }
}
