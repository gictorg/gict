document.addEventListener('DOMContentLoaded', function() {
    // Only apply desktop dropdown behavior on larger screens
    if (window.innerWidth > 768) {
        document.querySelectorAll('.nav-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                // Allow real navigation for non-dropdown links (e.g., Login)
                var href = btn.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('javascript:')) {
                    return; // do not intercept normal links
                }
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
    }
    
    // Handle window resize to switch between mobile and desktop behavior
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Reset to desktop mode
            document.querySelectorAll('.nav-dropdown').forEach(function(dropdown) {
                dropdown.classList.remove('open');
            });
        }
    });
}); 