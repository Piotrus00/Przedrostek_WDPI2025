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
