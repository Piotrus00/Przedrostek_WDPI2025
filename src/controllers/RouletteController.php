<?php

require_once 'AppController.php';
require_once __DIR__ . '/../Services/RouletteGameService.php';
require_once __DIR__ . '/../repository/StatisticsRepository.php';
require_once __DIR__ . '/../models/UserDefinition.php';
require_once __DIR__ . '/../models/UserUpgrade.php';

use App\Models\UserDefinition;
use App\Models\UserUpgrade;

class RouletteController extends AppController
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
        $this->render('roulette');
    }

    #[RequireLogin]
    public function gameApi()
	{
		header('Content-Type: application/json'); // zwracamy JSON
		try {
			$userId = $_SESSION['user_id'] ?? null;
			if (!$userId) {
				throw new Exception('Not logged in');
			}

			if (!UserDefinition::getEnabledById((int) $userId)) {
				http_response_code(403);
				echo json_encode([
					'success' => false,
					'error' => 'Account disabled'
				]);
				exit;
			}

			$rawInput = file_get_contents('php://input');
			$input = json_decode($rawInput, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Invalid JSON input');
			}

			$action = $input['action'] ?? ''; // akcja do wykonania

			#spin ruletki
			if ($action === 'spin') {
				$bets = $input['bets'] ?? []; // zakłady użytkownika
				if (!is_array($bets)) {
					$bets = [];
				}

				# Obliczanie łącznej kwoty zakładów
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

				$currentBalance = isset($_SESSION['user_balance']) // saldo z sesji
					? (int) $_SESSION['user_balance']
					: UserDefinition::getBalanceById((int) $userId);

				if ($totalBet <= 0) {
					throw new Exception('No bets placed');
				}
				if ($currentBalance < $totalBet) { // sprawdzenie salda z postawionymi zakładami
					throw new Exception('Insufficient balance');
				}

				$upgradeLevels = UserUpgrade::getLevels((int) $userId); // pobranie poziomów ulepszeń użytkownika
				$spin = RouletteGameService::spin($upgradeLevels); // wykonanie obrotu ruletki z ulepszeniami
				$result = $spin['result']; // wynik obrotu
				$randomIndex = $spin['index']; // indeks wylosowanego numeru
				$payout = RouletteGameService::calculateWinnings($bets, $result, $upgradeLevels, $totalBet); // obliczenie wygranej

				$betRedCount = 0;
				$betBlackCount = 0;
				$betGreenCount = 0;
				$redNumbers = RouletteGameService::getRedNumbers();
				foreach ($bets as $bet) {
					if (!is_array($bet)) {
						continue;
					}
					$betValue = $bet['number'] ?? null;
					if ($betValue === 'red') {
						$betRedCount++;
						continue;
					}
					if ($betValue === 'black') {
						$betBlackCount++;
						continue;
					}
					if ($betValue === 0 || $betValue === '0') {
						$betGreenCount++;
						continue;
					}
					if (is_numeric($betValue)) {
						$betNumber = (int) $betValue;
						if ($betNumber === 0) {
							$betGreenCount++;
						} elseif (in_array($betNumber, $redNumbers, true)) {
							$betRedCount++;
						} else {
							$betBlackCount++;
						}
					}
				}

				$newBalance = $currentBalance - $totalBet + $payout; // aktualizacja salda
				UserDefinition::updateBalance((int) $userId, $newBalance); // zapis salda do bazy danych
				$_SESSION['user_balance'] = $newBalance; // aktualizacja salda w sesji

				# Logowanie gry do statystyk
				$resultNumber = isset($result['num']) ? (int) $result['num'] : 0;
				$resultColor = isset($result['color']) ? (string) $result['color'] : 'green';
				$this->statisticsRepository->logRouletteGame(
					(int) $userId,
					$totalBet,
					$payout,
					$betRedCount,
					$betBlackCount,
					$betGreenCount,
					$resultNumber,
					$resultColor
				);
				# Zwracanie wyniku gry jako JSON
				echo json_encode([
					'success' => true,
					'result' => $result,
					'index' => $randomIndex,
					'payout' => $payout,
					'totalBet' => $totalBet,
					'balance' => $newBalance
				]);
				exit;
			} // koniec akcji 'spin'

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

