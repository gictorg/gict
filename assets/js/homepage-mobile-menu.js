// Homepage Mobile Menu Functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    const body = document.body;
    
    // Exit early if required elements don't exist (not on homepage)
    if (!mobileMenuToggle || !navLinks) {
        return;
    }
    
    // Create mobile overlay
    const overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    body.appendChild(overlay);
    
    // Toggle menu function
    function toggleMenu() {
        const isOpen = navLinks.classList.contains('mobile-open');
        
        console.log('Toggle menu called, current state:', isOpen);
        
        if (isOpen) {
            // Close menu
            navLinks.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.style.overflow = '';
            console.log('Menu closed');
            
            // Close all dropdowns when closing menu
            const dropdowns = navLinks.querySelectorAll('.nav-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('open');
                const content = dropdown.querySelector('.nav-dropdown-content');
                if (content) {
                    content.style.display = 'none';
                }
            });
        } else {
            // Open menu
            navLinks.classList.add('mobile-open');
            overlay.classList.add('active');
            body.style.overflow = 'hidden';
            console.log('Menu opened');
        }
    }
    
    // Event listeners
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMenu);
    }
    
    // Close menu when clicking overlay
    overlay.addEventListener('click', function() {
        navLinks.classList.remove('mobile-open');
        overlay.classList.remove('active');
        body.style.overflow = '';
        
        // Close all dropdowns
        const dropdowns = navLinks.querySelectorAll('.nav-dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('open');
            const content = dropdown.querySelector('.nav-dropdown-content');
            if (content) {
                content.style.display = 'none';
            }
        });
    });
    
    // Close menu when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            navLinks.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.style.overflow = '';
            
            // Close all dropdowns
            const dropdowns = navLinks.querySelectorAll('.nav-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('open');
                const content = dropdown.querySelector('.nav-dropdown-content');
                if (content) {
                    content.style.display = 'none';
                }
            });
        }
    });
    
    // Close menu on window resize (if switching to desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            navLinks.classList.remove('mobile-open');
            overlay.classList.remove('active');
            body.style.overflow = '';
            
            // Reset dropdowns for desktop
            const dropdowns = navLinks.querySelectorAll('.nav-dropdown');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('open');
                const content = dropdown.querySelector('.nav-dropdown-content');
                if (content) {
                    content.style.display = '';
                }
            });
        }
    });
    
    // Handle dropdown toggles on mobile
    const dropdowns = navLinks.querySelectorAll('.nav-dropdown');
    dropdowns.forEach(dropdown => {
        const btn = dropdown.querySelector('.nav-btn');
        const content = dropdown.querySelector('.nav-dropdown-content');
        
        if (btn && content) {
            btn.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Dropdown clicked:', dropdown);
                    
                    // Close other open dropdowns
                    dropdowns.forEach(d => {
                        if (d !== dropdown) {
                            d.classList.remove('open');
                            const otherContent = d.querySelector('.nav-dropdown-content');
                            if (otherContent) {
                                otherContent.style.display = 'none';
                            }
                        }
                    });
                    
                    // Toggle current dropdown
                    if (dropdown.classList.contains('open')) {
                        dropdown.classList.remove('open');
                        content.style.display = 'none';
                        console.log('Dropdown closed');
                    } else {
                        dropdown.classList.add('open');
                        content.style.display = 'block';
                        console.log('Dropdown opened');
                    }
                }
            });
        }
    });
    
    // Handle login button click in mobile
    const loginBtn = navLinks.querySelector('.login-btn');
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                // Close mobile menu when login is clicked
                navLinks.classList.remove('mobile-open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            }
        });
    }
    
    // Handle all navigation links (close menu on click)
    const navItems = navLinks.querySelectorAll('a');
    navItems.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't close menu for dropdown toggles
            if (this.parentElement.classList.contains('nav-dropdown') && 
                this.classList.contains('nav-btn')) {
                return;
            }
            
            if (window.innerWidth <= 768) {
                navLinks.classList.remove('mobile-open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            }
        });
    });
});
