let upgrades = [];
let userBalance = 0;

const balanceEl = document.getElementById('userBalance');
const upgradesGrid = document.getElementById('upgradesGrid');

async function fetchUpgrades() {
  try {
    const response = await fetch('/api/upgrades');
    const data = await response.json();
    if (data && data.success) {
      userBalance = Number(data.balance) || 0;
      upgrades = Array.isArray(data.upgrades) ? data.upgrades : [];
      balanceEl.textContent = userBalance;
      if (window.updateBalanceDisplay) {
        window.updateBalanceDisplay(userBalance);
      }
    }
  } catch (error) {
    // ignore
  }
}

async function purchaseUpgrade(id) {
  try {
    const response = await fetch('/api/upgrades', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await response.json();
    if (data && data.success) {
      userBalance = Number(data.balance) || 0;
      upgrades = Array.isArray(data.upgrades) ? data.upgrades : upgrades;
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
      const updated = await purchaseUpgrade(upgrade.id);
      if (!updated) return;
      renderUpgrades();
    });

    upgradesGrid.appendChild(card);
  });
}

fetchUpgrades().then(renderUpgrades);
