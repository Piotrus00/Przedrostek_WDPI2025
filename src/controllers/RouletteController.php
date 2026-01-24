<?php

require_once 'AppController.php';
require_once __DIR__ . '/../Services/RouletteGameService.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UpgradesRepository.php';
require_once __DIR__ . '/../repository/StatisticsRepository.php';

class RouletteController extends AppController
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
        $this->render('roulette');
    }

    #[RequireLogin]
    public function gameApi()
	{
		header('Content-Type: application/json');
		try {
			$userId = $_SESSION['user_id'] ?? null;
			if (!$userId) {
				throw new Exception('Not logged in');
			}

			$rawInput = file_get_contents('php://input');
			$input = json_decode($rawInput, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Invalid JSON input');
			}

			$action = $input['action'] ?? '';

			if ($action === 'spin') {
				$bets = $input['bets'] ?? [];
				if (!is_array($bets)) {
					$bets = [];
				}

				$totalBet = 0;
				foreach ($bets as $bet) {
					if (!is_array($bet)) {
						continue;
					}
					$amount = isset($bet['amount']) ? (int) $bet['amount'] : 0;
					if ($amount > 0) {
						$totalBet += $amount;
					}
				}

				$currentBalance = isset($_SESSION['user_balance'])
					? (int) $_SESSION['user_balance']
					: $this->userRepository->getUserBalanceById((int) $userId);

				if ($totalBet <= 0) {
					throw new Exception('No bets placed');
				}
				if ($currentBalance < $totalBet) {
					throw new Exception('Insufficient balance');
				}

				$upgradeLevels = $this->upgradesRepository->getUserUpgradeLevels((int) $userId);
				$spin = RouletteGameService::spin($upgradeLevels);
				$result = $spin['result'];
				$randomIndex = $spin['index'];
				$payout = RouletteGameService::calculateWinnings($bets, $result, $upgradeLevels, $totalBet);

				$newBalance = $currentBalance - $totalBet + $payout;
				$this->userRepository->updateUserBalance((int) $userId, $newBalance);
				$_SESSION['user_balance'] = $newBalance;

				$resultNumber = isset($result['num']) ? (int) $result['num'] : 0;
				$resultColor = isset($result['color']) ? (string) $result['color'] : 'green';
				$this->statisticsRepository->logRouletteGame(
					(int) $userId,
					$totalBet,
					$payout,
					$resultNumber,
					$resultColor
				);

				echo json_encode([
					'success' => true,
					'result' => $result,
					'index' => $randomIndex,
					'payout' => $payout,
					'totalBet' => $totalBet,
					'balance' => $newBalance
				]);
				exit;
			}

			throw new Exception('Unknown action: ' . htmlspecialchars($action));
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
			exit;
		}
	}
}

