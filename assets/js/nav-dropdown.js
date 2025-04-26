document.querySelectorAll('.nav-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        // Close other open dropdowns
        document.querySelectorAll('.nav-dropdown.open').forEach(function(openDropdown) {
            if (openDropdown !== btn.parentElement) {
                openDropdown.classList.remove('open');
            }
        });
        // Toggle this dropdown
        btn.parentElement.classList.toggle('open');
    });
});

// Optional: Close dropdowns when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-dropdown')) {
        document.querySelectorAll('.nav-dropdown.open').forEach(function(openDropdown) {
            openDropdown.classList.remove('open');
        });
    }
}); 