function openTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));

  const target = document.getElementById(id);
  if (target) {
    target.classList.add('active');
  }
  if (btn) {
    btn.classList.add('active');
  }
}

function toggleMenu() {
  const menu = document.getElementById('mobileMenu');
  if (menu) {
    menu.classList.toggle('open');
  }
}

const setText = (id, value) => {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value;
  }
};

const formatMoney = (value) => {
  const amount = Number(value) || 0;
  return `$${amount}`;
};

const loadStatistics = async () => {
  try {
    const response = await fetch('/api/statistics');
    const data = await response.json();
    if (!data || !data.success) return;

    const general = data.general || {};
    const upgrades = data.upgrades || {};
    const other = data.other || {};

    setText('statBalance', formatMoney(general.balance));
    setText('statTotalNet', formatMoney(general.totalNet));
    setText('statLosses', String(general.losses ?? 0));
    setText('statWins', String(general.wins ?? 0));
    setText('statGreen', String(general.green ?? 0));
    setText('statBlack', String(general.black ?? 0));
    setText('statRed', String(general.red ?? 0));

    setText('statBoughtUpgrades', String(upgrades.boughtUpgrades ?? 0));
    setText('statRemainingUpgrades', String(upgrades.remainingUpgrades ?? 0));
    setText('statTotalSpentUpgrades', formatMoney(upgrades.totalSpent));
    setText('statGreenMultiplier', String(upgrades.greenMultiplier ?? 1));
    setText('statRedMultiplier', String(upgrades.redMultiplier ?? 2));
    setText('statBlackMultiplier', String(upgrades.blackMultiplier ?? 2));
    setText('statGreenChance', `x${upgrades.greenChance ?? 1}`);

    setText('statTotalGames', String(other.totalGames ?? 0));
    setText('statHighestWin', formatMoney(other.highestWin));
    setText('statHighestLoss', formatMoney(other.highestLoss));
  } catch (error) {
    // ignore
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', loadStatistics);
} else {
  loadStatistics();
}
