const upgrades = [
  { id: '1', title: 'Additional 3', description: 'on max', baseCost: 30, maxLevel: 5, currentLevel: 1 },
  { id: '2', title: 'Double Wins', description: 'x2 multiplier', baseCost: 50, maxLevel: 5, currentLevel: 0 },
  { id: '3', title: 'Lucky Green', description: '+10% green chance', baseCost: 75, maxLevel: 4, currentLevel: 0 },
  { id: '4', title: 'Fast Spin', description: 'reduce spin time', baseCost: 40, maxLevel: 3, currentLevel: 0 },
  { id: '5', title: 'Bonus Rounds', description: 'unlock special mode', baseCost: 100, maxLevel: 5, currentLevel: 0 },
  { id: '6', title: 'Auto Play', description: 'enable automation', baseCost: 60, maxLevel: 3, currentLevel: 0 }
];

let userBalance = 500;

const balanceEl = document.getElementById('userBalance');
const upgradesGrid = document.getElementById('upgradesGrid');

function renderUpgrades() {
  upgradesGrid.innerHTML = '';
  upgrades.forEach(upgrade => {
    const nextCost = upgrade.baseCost * (upgrade.currentLevel + 1);
    const canAfford = userBalance >= nextCost;
    const isMaxed = upgrade.currentLevel >= upgrade.maxLevel;

    const card = document.createElement('div');
    card.className = 'upgrade-card' + (canAfford && !isMaxed ? '' : ' disabled');

    card.innerHTML = `
      <h3>${upgrade.title}</h3>
      <p class="description">${upgrade.description}</p>
      <p class="cost">${isMaxed ? 'MAX' : nextCost + '$'}</p>
      <div class="levels">
        ${Array.from({ length: upgrade.maxLevel }).map((_, i) => 
          `<div class="level ${i < upgrade.currentLevel ? 'active' : ''}"></div>`
        ).join('')}
      </div>
    `;

    card.addEventListener('click', () => {
      if (!canAfford || isMaxed) return;
      userBalance -= nextCost;
      upgrade.currentLevel += 1;
      balanceEl.textContent = userBalance;
      renderUpgrades();
    });

    upgradesGrid.appendChild(card);
  });
}

renderUpgrades();
