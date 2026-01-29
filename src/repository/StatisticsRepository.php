<?php

require_once 'Repository.php';

class StatisticsRepository extends Repository
{
    public function logRouletteGame(
        int $userId,
        int $totalBet,
        int $payout,
        int $betRedCount,
        int $betBlackCount,
        int $betGreenCount,
        int $resultNumber,
        string $resultColor
    ): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO roulette_games (
                user_id,
                total_bet,
                payout,
                bet_red_count,
                bet_black_count,
                bet_green_count,
                result_number,
                result_color
            )
            VALUES (
                :user_id,
                :total_bet,
                :payout,
                :bet_red_count,
                :bet_black_count,
                :bet_green_count,
                :result_number,
                :result_color
            )
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':total_bet', $totalBet, PDO::PARAM_INT);
        $stmt->bindParam(':payout', $payout, PDO::PARAM_INT);
        $stmt->bindParam(':bet_red_count', $betRedCount, PDO::PARAM_INT);
        $stmt->bindParam(':bet_black_count', $betBlackCount, PDO::PARAM_INT);
        $stmt->bindParam(':bet_green_count', $betGreenCount, PDO::PARAM_INT);
        $stmt->bindParam(':result_number', $resultNumber, PDO::PARAM_INT);
        $stmt->bindParam(':result_color', $resultColor, PDO::PARAM_STR);
        $stmt->execute();
        // Insert odpowiedzialny za log gry ruletki (RouletteController::gameApi)
    }

public function getUserGameStats(int $userId): array
{
    # Pobiera statystyki gier użytkownika z widoku v_user_game_stats
    $stmt = $this->database->connect()->prepare("
        SELECT * FROM v_user_game_stats WHERE user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_games' => 0,
        'total_bet' => 0,
        'total_payout' => 0,
        'total_net' => 0,
        'wins' => 0,
        'losses' => 0,
        'green' => 0,
        'red' => 0,
        'black' => 0,
        'highest_win' => 0,
        'highest_loss' => 0,
    ];
}

    public function getTotalUpgradesCost(int $userId): int
    {
        $stmt = $this->database->connect()->prepare('
            SELECT total_upgrades_cost(:user_id) AS total_cost
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['total_cost'] : 0;
    }

}
