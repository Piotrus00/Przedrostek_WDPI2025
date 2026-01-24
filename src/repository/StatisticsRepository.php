<?php

require_once 'Repository.php';

class StatisticsRepository extends Repository
{
    public function logRouletteGame(
        int $userId,
        int $totalBet,
        int $payout,
        int $resultNumber,
        string $resultColor
    ): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO roulette_games (user_id, total_bet, payout, result_number, result_color)
            VALUES (:user_id, :total_bet, :payout, :result_number, :result_color)
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':total_bet', $totalBet, PDO::PARAM_INT);
        $stmt->bindParam(':payout', $payout, PDO::PARAM_INT);
        $stmt->bindParam(':result_number', $resultNumber, PDO::PARAM_INT);
        $stmt->bindParam(':result_color', $resultColor, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getUserGameStats(int $userId): array
    {
        $stmt = $this->database->connect()->prepare("
            SELECT
                COUNT(*) AS total_games,
                COALESCE(SUM(total_bet), 0) AS total_bet,
                COALESCE(SUM(payout), 0) AS total_payout,
                COALESCE(SUM(payout - total_bet), 0) AS total_net,
                COALESCE(SUM(CASE WHEN (payout - total_bet) > 0 THEN 1 ELSE 0 END), 0) AS wins,
                COALESCE(SUM(CASE WHEN (payout - total_bet) < 0 THEN 1 ELSE 0 END), 0) AS losses,
                COALESCE(SUM(CASE WHEN result_color = 'green' THEN 1 ELSE 0 END), 0) AS green,
                COALESCE(SUM(CASE WHEN result_color = 'red' THEN 1 ELSE 0 END), 0) AS red,
                COALESCE(SUM(CASE WHEN result_color = 'black' THEN 1 ELSE 0 END), 0) AS black,
                COALESCE(MAX(CASE WHEN (payout - total_bet) > 0 THEN (payout - total_bet) ELSE NULL END), 0) AS highest_win,
                COALESCE(MIN(CASE WHEN (payout - total_bet) < 0 THEN (payout - total_bet) ELSE NULL END), 0) AS highest_loss
            FROM roulette_games
            WHERE user_id = :user_id
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
}
