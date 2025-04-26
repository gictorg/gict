document.addEventListener('DOMContentLoaded', function () {
  const nav = document.querySelector('.main-nav');
  const toggle = document.querySelector('.mobile-nav-toggle');
  const navList = document.querySelector('.mobile-nav-list');
  const items = document.querySelectorAll('.mobile-nav-item');

  // Hamburger menu toggle
  if (toggle) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('open');
    });
  }

  // Dropdown toggle for mobile sub-menus
  items.forEach(item => {
    const link = item.querySelector('a');
    link.addEventListener('click', function (e) {
      // Only toggle if clicking the top-level link
      e.preventDefault();
      // Close other open dropdowns
      items.forEach(i => { if (i !== item) i.classList.remove('open'); });
      item.classList.toggle('open');
    });
  });
}); 