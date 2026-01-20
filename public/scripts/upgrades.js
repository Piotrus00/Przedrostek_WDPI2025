const upgrades = [
  { id: '1', title: 'Additional 7', description: '2x 7 chances', baseCost: 20, maxLevel: 5, currentLevel: 0 },
  { id: '2', title: 'Black Multiplier', description: '+0.2x multiplier', baseCost: 100, maxLevel: 5, currentLevel: 0 },
  { id: '3', title: 'Red Multiplier', description: '+0.2x multiplier', baseCost: 100, maxLevel: 5, currentLevel: 0 },
  { id: '4', title: 'Green Multiplier', description: '2x multiplier', baseCost: 100, maxLevel: 5, currentLevel: 0 },
  { id: '5', title: 'Lucky Green', description: '2x green chance', baseCost: 75, maxLevel: 4, currentLevel: 0 },
  { id: '6', title: 'Refund', description: '1% refund chance', baseCost: 250, maxLevel: 5, currentLevel: 0 },
  { id: '7', title: 'More Money', description: '+0.1x more money', baseCost: 500, maxLevel: 10, currentLevel: 0 },
];

let userBalance = 0;

const balanceEl = document.getElementById('userBalance');
const upgradesGrid = document.getElementById('upgradesGrid');

async function fetchBalance() {
  try {
    const response = await fetch('/api/balance');
    const data = await response.json();
    if (data && data.success) {
      userBalance = Number(data.balance) || 0;
      balanceEl.textContent = userBalance;
      if (window.updateBalanceDisplay) {
        window.updateBalanceDisplay(userBalance);
      }
    }
  } catch (error) {
    // ignore
  }
}

async function updateBalance(delta) {
  try {
    const response = await fetch('/api/balance', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ delta })
    });
    const data = await response.json();
    if (data && data.success) {
      userBalance = Number(data.balance) || 0;
      balanceEl.textContent = userBalance;
      if (window.updateBalanceDisplay) {
        window.updateBalanceDisplay(userBalance);
      }
      return true;
    }
  } catch (error) {
    // ignore
  }
  return false;
}

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

    card.addEventListener('click', async () => {
      if (!canAfford || isMaxed) return;
      const updated = await updateBalance(-nextCost);
      if (!updated) return;
      upgrade.currentLevel += 1;
      renderUpgrades();
    });

    upgradesGrid.appendChild(card);
  });
}

fetchBalance().then(renderUpgrades);
