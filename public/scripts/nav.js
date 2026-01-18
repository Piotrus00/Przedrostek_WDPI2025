document.addEventListener('DOMContentLoaded', () => {
    const burger = document.querySelector('.burger');
    const navItems = document.querySelector('.nav-items');

    if (!burger || !navItems) return;

    burger.addEventListener('click', () => {
        navItems.classList.toggle('active');
    });

    navItems.querySelectorAll('.nav-button').forEach(btn => {
        btn.addEventListener('click', () => {
            navItems.classList.remove('active');
        });
    });
});
