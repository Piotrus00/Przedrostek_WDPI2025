const updateBalanceDisplay = (balance) => {
    const amount = Number(balance) || 0;
    document.querySelectorAll('[data-balance]').forEach(el => {
        el.textContent = `${amount}$`;
    });
};

window.updateBalanceDisplay = updateBalanceDisplay;

const burger = document.querySelector('.burger');
const navItems = document.querySelector('.nav-items');

if (burger && navItems) {
    burger.addEventListener('click', () => {
        navItems.classList.toggle('active');
    });

    navItems.querySelectorAll('.nav-button').forEach(btn => {
        btn.addEventListener('click', () => {
            navItems.classList.remove('active');
        });
    });
}
