const initRoulette = () => {
    //zmienne
	let currentBet = 0;
	let bets = [];
	let selectedChip = 10;
	let isSpinning = false;
	let recentNumbers = [];
	let userBalance = 0;

    //stale elementy DOM
	const wheelEl = document.getElementById('rouletteWheel');
	const numberGridEl = document.getElementById('numberGrid');
	const recentNumbersEl = document.getElementById('recentNumbers');
	const currentBetInputEl = document.getElementById('currentBetInput');
	const clearBetsBtn = document.getElementById('clearBetsBtn');
	const spinBtn = document.getElementById('spinBtn');
	const winningNumberEl = document.getElementById('winningNumber');
	const winningsDisplayEl = document.getElementById('winningsDisplay');
	const chipButtons = document.querySelectorAll('.chip-btn[data-chip]');

    //jezeli brak elementow - wyjdz
	if (!wheelEl || !numberGridEl || !recentNumbersEl || !currentBetInputEl || !clearBetsBtn || !spinBtn || !winningNumberEl || !winningsDisplayEl) {
		return;
	}

    //kolejnosc liczb na kole
	const rouletteNumbers = wheelEl.dataset.roulette ? JSON.parse(wheelEl.dataset.roulette) : [];
    //czerwone liczby
	const redNumbers = wheelEl.dataset.red ? JSON.parse(wheelEl.dataset.red) : [];

    //kolorujemy liczby w ui
	const getNumberColor = (num) => {
		if (num === 0) return 'green';
		return redNumbers.includes(num) ? 'red' : 'black';
	};

    //zabezpieczenie UI
	const updateControlsUI = () => {
		currentBetInputEl.value = currentBet > 0 ? currentBet.toString() : '';
		clearBetsBtn.disabled = isSpinning || currentBet === 0;
		spinBtn.disabled = isSpinning || currentBet === 0 || currentBet > userBalance;

		updateSelectedChipUI();
	};

    //aktualizujemy UI wybranego przycisku za ile chcemy grac mozna wybra tylko 1 na raz +10 +50...
	const updateSelectedChipUI = () => {
		document.querySelectorAll('.chip-btn').forEach(btn => btn.classList.remove('selected'));
		chipButtons.forEach(btn => {
			if (Number(btn.dataset.chip) === selectedChip) {
				btn.classList.add('selected');
			}
		});
	};

    //aktualizacja zakladow w UI
	const attachBetCellListeners = () => {
		document.querySelectorAll('.bet-cell.number').forEach(cell => { //pobieramy wszystkie komorki z numerami 1-367
			const betValue = Number(cell.dataset.bet); //pobieramy wartosc zakladu z atrybutu data-bet
			cell.addEventListener('click', () => placeBet(betValue)); //po kliknieciu w komorke stawiamy zaklad na dany numer
		});

		const zeroCell = document.querySelector('.bet-cell.zero');
		if (zeroCell) {
			zeroCell.addEventListener('click', () => placeBet(0));
		}
	};

    //historia ostatnich liczb
	const renderRecentNumbers = () => {
		recentNumbersEl.innerHTML = '';
		recentNumbers.forEach((num) => {
			const numEl = document.createElement('div');
			numEl.className = `recent-number ${getNumberColor(num)}`;
			numEl.textContent = num;
			recentNumbersEl.appendChild(numEl);
		});
	};


	const updateBetChips = () => {
		document.querySelectorAll('.bet-cell .chip, .range-box .bet-chip').forEach(el => { //pobiera wszystkie opcje liczb i zakresow
			el.textContent = '';
			el.classList.remove('visible'); //ukrywa je po wylosowaniu
		});

		bets.forEach(bet => {
			const betKey = String(bet.number); //pobiera wartosc zakladu jako klucz
			const cell = document.querySelector(`.bet-cell[data-bet="${betKey}"] .chip`); //pobiera komorke z danym zakladem
            const rangeBox = document.querySelector(`.range-box[data-bet="${betKey}"]`); // to samo dla czerwone czarne
			//aktualizuje wartosc zakladu i pokazuje ja w UI
            if (cell) {
				cell.textContent = `$${bet.amount}`;
				cell.classList.add('visible');
			}
            //to samo dla czerwone czarne tylko ze sa 2 zielone wiec tutaj inna logika troszke
			if (rangeBox) {
				const totalEl = rangeBox.querySelector('.range-total');
				const chipEl = rangeBox.querySelector('.bet-chip');
				if (totalEl) {
					const multiplier = rangeBox.dataset.multiplier;
					if (multiplier) totalEl.textContent = multiplier;
				}
				if (chipEl) {
					chipEl.textContent = `$${bet.amount}`;
					chipEl.classList.add('visible');
				}
			}
		});
	};

	const placeBet = (number) => {
        //blokady
		if (isSpinning) return;
		if (selectedChip <= 0) return;

        //sprawdzamy czy juz jest zaklad na dany numer
		const existingBet = bets.find(b => b.number === number);
		if (existingBet) {
			bets = bets.map(b => b.number === number ? { ...b, amount: b.amount + selectedChip } : b); //aktualizujemy kwote zakladu
		} else {
			bets = [...bets, { number, amount: selectedChip }]; //dodajemy nowy zaklad
		}

		currentBet += selectedChip;
		updateControlsUI();
		updateBetChips();
	};

	const clearBets = () => {
		if (isSpinning) return;
		currentBet = 0;
		bets = [];
		updateControlsUI();
		updateBetChips();
	};

	const fetchBalance = async () => {
		try {
			const response = await fetch('/api/balance');
			const data = await response.json();
			if (data && data.success) {
				userBalance = Number(data.balance) || 0;
				if (window.updateBalanceDisplay) {
					window.updateBalanceDisplay(userBalance);
				}
				updateControlsUI();
			}
		} catch (error) {
			// ignore
		}
	};

	const spinWheel = async () => {
		if (isSpinning || currentBet === 0) return; //blokady
		if (currentBet > userBalance) return;

		isSpinning = true;
		winningNumberEl.textContent = '-'; //resetujemy wyswietlanie wygranej liczby
		winningsDisplayEl.textContent = '--';
		updateControlsUI(); //blokujemy przyciski

		let result;
		let randomIndex;

        //wysylamy zapytanie do serwera o wynik
		try {
			const response = await fetch('/api/roulette', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ action: 'spin', bets })
			});

			const data = await response.json();
			if (!data.success || !data.result) {
				throw new Error(data.error || 'Spin failed');
			}

			result = data.result;
			randomIndex = rouletteNumbers.findIndex(item => item.num === result.num); //znajdujemy index wylosowanej liczby
			if (typeof data.balance !== 'undefined') {
				userBalance = Number(data.balance) || 0;
				if (window.updateBalanceDisplay) {
					window.updateBalanceDisplay(userBalance);
				}
			}
			if (typeof data.payout !== 'undefined') {
				winningsDisplayEl.textContent = `+$${data.payout}`;
			}
		} catch (error) {
			isSpinning = false;
			updateControlsUI();
			return;
		}

        //zabezpieczenie
		if (randomIndex < 0) {
			isSpinning = false;
			updateControlsUI();
			return;
		}

        //animacja
		wheelEl.style.transition = 'none';
		wheelEl.style.transform = 'translateX(0)';

        //drobne opoznienie przed animacja
		setTimeout(() => {
			const numberWidth = 54; //szerokosc pojedynczej liczby na kole
			const repetitions = 3; //ile razy powtarzamy sekwencje liczb na kole
			const winningIndexInLastSet = (repetitions - 1) * rouletteNumbers.length + randomIndex; //obliczamy index w ostatnim powtorzeniu

            //obliczamy przesuniecie kola tak aby wylosowana liczba znalazla sie na srodku
			const containerWidth = wheelEl.parentElement ? wheelEl.parentElement.offsetWidth : wheelEl.offsetWidth;
            //obliczamy offset
			const offset = (winningIndexInLastSet * numberWidth) - (containerWidth / 2) + (numberWidth / 2);

			wheelEl.style.transition = 'transform 5s cubic-bezier(0.17, 0.67, 0.35, 0.96)'; //rodzaj animacji
			wheelEl.style.transform = `translateX(-${offset}px)`; //przesuwamy kole

			setTimeout(() => {
				recentNumbers = [result.num, ...recentNumbers].slice(0, 10);
				winningNumberEl.textContent = result.num.toString();
				renderRecentNumbers(); //aktualizujemy historie ostatnich liczb
				isSpinning = false;
				updateControlsUI(); //odblokowujemy przyciski
				bets = [];
				currentBet = 0;
				updateBetChips(); //czyscimy zaklady
			}, 5000);
		}, 50);
	};

	chipButtons.forEach(btn => {
		btn.addEventListener('click', () => {
			selectedChip = Number(btn.dataset.chip); //ustawiamy wybrany przycisk
			updateSelectedChipUI();
		});
	});

	clearBetsBtn.addEventListener('click', clearBets); //czysczenie zakladow po kliknieciu clear
	spinBtn.addEventListener('click', spinWheel); //rozpoczecie obrotu kola po kliknieciu spin

    
	document.querySelectorAll('.range-box').forEach(box => {
		const bet = box.dataset.bet;
		if (bet !== undefined) {
			box.addEventListener('click', () => {
				const betValue = bet === '0' ? 0 : bet;
				placeBet(betValue);
			});
		}
	});

	attachBetCellListeners(); //dodajemy nasluchiwacze na komorki z numerami
	renderRecentNumbers(); //renderujemy historie ostatnich liczb
	updateControlsUI(); //aktualizujemy UI
	updateSelectedChipUI(); //aktualizujemy UI wybranego przycisku
	updateBetChips(); //aktualizujemy zaklady w UI
	fetchBalance();
};

//inicjalizacja gry po zaladowaniu DOM
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initRoulette);
} else {
	initRoulette();
}
