// Homepage Mobile Menu - CLEAN AND RELIABLE
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const body = document.body;

    if (!mobileMenuToggle || !navLinks) {
        return;
    }

    // Create overlay only once
    let overlay = document.querySelector('.mobile-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        body.appendChild(overlay);
    }

    let isMenuOpen = false;

    function openMenu() {
        isMenuOpen = true;
        
        // Add classes for CSS to handle
        navLinks.classList.add('mobile-open');
        overlay.classList.add('active');
        body.classList.add('mobile-menu-open');
        
        // Show close button
        if (mobileMenuClose) {
            mobileMenuClose.style.display = 'flex';
        }
        
        // Prevent body scroll
        body.style.overflow = 'hidden';
    }

    function closeMenu() {
        isMenuOpen = false;
        
        // Remove classes
        navLinks.classList.remove('mobile-open');
        overlay.classList.remove('active');
        body.classList.remove('mobile-menu-open');
        
        // Hide close button
        if (mobileMenuClose) {
            mobileMenuClose.style.display = 'none';
        }
        
        // Restore body scroll
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

    function toggleMenu() {
        if (isMenuOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    // Hamburger button click
    mobileMenuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
    });

    // Close button click
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMenu();
        });
    }

    // Overlay click to close
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeMenu();
        }
    });

    // Escape key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMenuOpen) {
            closeMenu();
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

                    // Close other dropdowns
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
                    } else {
                        dropdown.classList.add('open');
                        content.style.display = 'block';
                    }
                }
            });
        }
    });

    // Handle login/user button clicks on mobile
    const loginBtn = navLinks.querySelector('.nav-dropdown .login-btn');
    const userBtn = navLinks.querySelector('.nav-dropdown .user-btn');
    
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    }
    
    if (userBtn) {
        userBtn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    }

    // Handle navigation link clicks on mobile
    const navItems = navLinks.querySelectorAll('a');
    navItems.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't close menu for dropdown toggles
            if (this.parentElement.classList.contains('nav-dropdown') &&
                this.classList.contains('nav-btn')) {
                return;
            }

            // Close menu for regular navigation links
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Desktop mode
            if (isMenuOpen) {
                closeMenu();
            }
            navLinks.style.display = 'flex';
        } else {
            // Mobile mode
            navLinks.style.display = 'none';
        }
    });

    // Set initial display based on screen size
    if (window.innerWidth <= 768) {
        navLinks.style.display = 'none';
    } else {
        navLinks.style.display = 'flex';
    }
});
