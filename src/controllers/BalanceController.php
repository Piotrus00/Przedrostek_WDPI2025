<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../models/UserDefinition.php';

use App\Models\UserDefinition;

class BalanceController extends AppController
{

    #[AllowedMethods(['GET'])]
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

        $balance = isset($_SESSION['user_balance'])
            ? (int) $_SESSION['user_balance']
            : UserDefinition::getBalanceById((int) $userId);

        $_SESSION['user_balance'] = $balance;

        echo json_encode(['success' => true, 'balance' => $balance]);
    }
}