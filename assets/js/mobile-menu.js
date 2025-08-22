// Mobile menu functionality for admin dashboard
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const body = document.body;
    
    // Exit early if required elements don't exist (not on admin page)
    if (!menuToggle || !sidebar) {
        return;
    }
    
    // Create mobile overlay
    const overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    body.appendChild(overlay);
    
    // Toggle menu function
    function toggleMenu() {
        const isOpen = sidebar.classList.contains('mobile-open');
        
        if (isOpen) {
            // Close menu
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.style.overflow = '';
        } else {
            // Open menu
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
            body.style.overflow = 'hidden';
        }
    }
    
    // Event listeners
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMenu);
    }
    
    // Close menu when clicking overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        body.style.overflow = '';
    });
    
    // Close menu when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.style.overflow = '';
        }
    });
    
    // Close menu on window resize (if switching to desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1000) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.style.overflow = '';
        }
    });
    
    // Close menu when clicking on sidebar links (mobile)
    if (sidebar) {
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1000) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    body.style.overflow = '';
                }
            });
        });
    }
}); 