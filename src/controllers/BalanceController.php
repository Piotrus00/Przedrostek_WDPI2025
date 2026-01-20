<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../repository/UserRepository.php';

class BalanceController extends AppController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    #[AllowedMethods(['GET', 'POST'])]
    #[RequireLogin]
    public function balanceApi(): void
    {
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            return;
        }

        if ($this->isGet()) {
            $balance = isset($_SESSION['user_balance'])
                ? (int) $_SESSION['user_balance']
                : $this->userRepository->getUserBalanceById((int) $userId);

            $_SESSION['user_balance'] = $balance;

            echo json_encode(['success' => true, 'balance' => $balance]);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            return;
        }

        $currentBalance = isset($_SESSION['user_balance'])
            ? (int) $_SESSION['user_balance']
            : $this->userRepository->getUserBalanceById((int) $userId);

        if (isset($input['delta'])) {
            $delta = (int) $input['delta'];
            $newBalance = $currentBalance + $delta;
        } elseif (isset($input['balance'])) {
            $newBalance = (int) $input['balance'];
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing delta or balance']);
            return;
        }

        if ($newBalance < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient balance']);
            return;
        }

        $this->userRepository->updateUserBalance((int) $userId, $newBalance);
        $_SESSION['user_balance'] = $newBalance;

        echo json_encode(['success' => true, 'balance' => $newBalance]);
    }
}