<?php

class RouletteGameService
{
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

	public static function spin(): array
	{
		$rouletteNumbers = self::getRouletteNumbers();
		$randomIndex = random_int(0, count($rouletteNumbers) - 1);
		$result = $rouletteNumbers[$randomIndex];

		return [
			'result' => $result,
			'index' => $randomIndex
		];
	}

	public static function calculateWinnings(array $bets, array $result): int
	{
		$num = $result['num'] ?? null;
		$color = $result['color'] ?? null;

		if ($num === null || $color === null) {
			return 0;
		}

		$totalWin = 0;

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
					$totalWin += $amount * 36;
				}
				continue;
			}

			if (!is_string($betNumber)) {
				continue;
			}

			switch ($betNumber) {
				case 'red':
					if ($color === 'red') {
						$totalWin += $amount * 2;
					}
					break;
				case 'black':
					if ($color === 'black') {
						$totalWin += $amount * 2;
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

		return $totalWin;
	}
}
