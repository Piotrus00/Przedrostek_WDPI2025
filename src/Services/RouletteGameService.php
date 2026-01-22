<?php

class RouletteGameService
{
    private static function getUpgradeLevel(array $levels, string $id): int
    {
        return isset($levels[$id]) ? (int) $levels[$id] : 0;
    }

	# mozna pomyslec zeby to do bazy przeniesc i wtedy upgrade'y inaczej dodawac
	public static function getRouletteNumbers(): array
	{
		return [
			['num' => 0, 'color' => 'green'],
			['num' => 32, 'color' => 'red'],
			['num' => 15, 'color' => 'black'],
			['num' => 19, 'color' => 'red'],
			['num' => 4, 'color' => 'black'],
			['num' => 21, 'color' => 'red'],
			['num' => 2, 'color' => 'black'],
			['num' => 25, 'color' => 'red'],
			['num' => 17, 'color' => 'black'],
			['num' => 34, 'color' => 'red'],
			['num' => 6, 'color' => 'black'],
			['num' => 27, 'color' => 'red'],
			['num' => 13, 'color' => 'black'],
			['num' => 36, 'color' => 'red'],
			['num' => 11, 'color' => 'black'],
			['num' => 30, 'color' => 'red'],
			['num' => 8, 'color' => 'black'],
			['num' => 23, 'color' => 'red'],
			['num' => 10, 'color' => 'black'],
			['num' => 5, 'color' => 'red'],
			['num' => 24, 'color' => 'black'],
			['num' => 16, 'color' => 'red'],
			['num' => 33, 'color' => 'black'],
			['num' => 1, 'color' => 'red'],
			['num' => 20, 'color' => 'black'],
			['num' => 14, 'color' => 'red'],
			['num' => 31, 'color' => 'black'],
			['num' => 9, 'color' => 'red'],
			['num' => 22, 'color' => 'black'],
			['num' => 18, 'color' => 'red'],
			['num' => 29, 'color' => 'black'],
			['num' => 7, 'color' => 'red'],
			['num' => 28, 'color' => 'black'],
			['num' => 12, 'color' => 'red'],
			['num' => 35, 'color' => 'black'],
			['num' => 3, 'color' => 'red'],
			['num' => 26, 'color' => 'black'],
		];
	}

	public static function getRedNumbers(): array
	{
		return [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];
	}

	public static function spin(array $upgradeLevels = []): array
	{
		$rouletteNumbers = self::getRouletteNumbers();
		$additionalSevenLevel = self::getUpgradeLevel($upgradeLevels, '1');
		$luckyGreenLevel = self::getUpgradeLevel($upgradeLevels, '5');

		if ($additionalSevenLevel > 0) {
			for ($i = 0; $i < $additionalSevenLevel; $i++) {
				$rouletteNumbers[] = ['num' => 7, 'color' => 'red'];
			}
		}

		if ($luckyGreenLevel > 0) {
			for ($i = 0; $i < $luckyGreenLevel; $i++) {
				$rouletteNumbers[] = ['num' => 0, 'color' => 'green'];
			}
		}

		$randomIndex = random_int(0, count($rouletteNumbers) - 1);
		$result = $rouletteNumbers[$randomIndex];

		return [
			'result' => $result,
			'index' => $randomIndex
		];
	}

	public static function calculateWinnings(array $bets, array $result, array $upgradeLevels = [], int $totalBet = 0): int
	{
		$num = $result['num'] ?? null;
		$color = $result['color'] ?? null;

		if ($num === null || $color === null) {
			return 0;
		}

		$totalWin = 0;
		$blackMultiplier = 2 + (0.2 * self::getUpgradeLevel($upgradeLevels, '2'));
		$redMultiplier = 2 + (0.2 * self::getUpgradeLevel($upgradeLevels, '3'));
		$greenMultiplierLevel = self::getUpgradeLevel($upgradeLevels, '4');

		foreach ($bets as $bet) {
			if (!is_array($bet)) {
				continue;
			}

			$amount = isset($bet['amount']) ? (int) $bet['amount'] : 0;
			if ($amount <= 0) {
				continue;
			}

			$betNumber = $bet['number'] ?? null;

			if (is_numeric($betNumber)) {
				$betValue = (int) $betNumber;
				if ($betValue === (int) $num) {
					$multiplier = 36;
					if ($betValue === 0 && $greenMultiplierLevel > 0) {
						$multiplier = (int) round($multiplier * (1 + $greenMultiplierLevel));
					}
					$totalWin += $amount * $multiplier;
				}
				continue;
			}

			if (!is_string($betNumber)) {
				continue;
			}

			switch ($betNumber) {
				case 'red':
					if ($color === 'red') {
						$totalWin += (int) round($amount * $redMultiplier);
					}
					break;
				case 'black':
					if ($color === 'black') {
						$totalWin += (int) round($amount * $blackMultiplier);
					}
					break;
				case 'even':
					if ($num !== 0 && $num % 2 === 0) {
						$totalWin += $amount * 2;
					}
					break;
				case 'odd':
					if ($num % 2 === 1) {
						$totalWin += $amount * 2;
					}
					break;
				case '1-18':
					if ($num >= 1 && $num <= 18) {
						$totalWin += $amount * 2;
					}
					break;
				case '19-36':
					if ($num >= 19 && $num <= 36) {
						$totalWin += $amount * 2;
					}
					break;
			}
		}

		$moreMoneyLevel = self::getUpgradeLevel($upgradeLevels, '7');
		if ($moreMoneyLevel > 0 && $totalWin > 0) {
			$totalWin = (int) round($totalWin * (1 + (0.1 * $moreMoneyLevel)));
		}

		$refundLevel = self::getUpgradeLevel($upgradeLevels, '6');
		if ($totalWin === 0 && $refundLevel > 0 && $totalBet > 0) {
			$refundRoll = random_int(1, 100);
			if ($refundRoll <= $refundLevel) {
				$totalWin = $totalBet;
			}
		}

		return $totalWin;
	}
}
