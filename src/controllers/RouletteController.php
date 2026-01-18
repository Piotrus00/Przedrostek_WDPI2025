<?php

require_once 'AppController.php';
require_once __DIR__ . '/../Services/RouletteGameService.php';

class RouletteController extends AppController
{
    public function __construct()
    {
        parent::__construct();
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

				$spin = RouletteGameService::spin();
				$result = $spin['result'];
				$randomIndex = $spin['index'];
				$payout = RouletteGameService::calculateWinnings($bets, $result);

				echo json_encode([
					'success' => true,
					'result' => $result,
					'index' => $randomIndex,
					'payout' => $payout
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

