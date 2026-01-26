<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../models/UserDefinition.php';

use App\Models\UserDefinition;
# powiązanie z bazą danych i sesją użytkownika w celu pobrania salda wykorzystywane w roulette.js
class BalanceController extends AppController
{

    #[AllowedMethods(['GET'])]
    #[RequireLogin]
    public function balanceApi(): void
    {
        header('Content-Type: application/json'); // Ustawienie nagłówka odpowiedzi na JSON

        $userId = $_SESSION['user_id'] ?? null; // Pobranie ID użytkownika z sesji
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']); // zwrocenie bledu jako JSON
            return;
        }

        $balance = isset($_SESSION['user_balance']) // Sprawdzenie, czy saldo jest już w sesji
            ? (int) $_SESSION['user_balance'] // Jeśli tak, użyj go
            : UserDefinition::getBalanceById((int) $userId); // W przeciwnym razie pobierz z bazy danych

        $_SESSION['user_balance'] = $balance; // Zapisz saldo w sesji dla przyszłych zapytań

        echo json_encode(['success' => true, 'balance' => $balance]); // Zwrócenie salda w formacie JSON
    }
}