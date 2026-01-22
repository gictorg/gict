// Homepage Mobile Menu - CLEAN AND RELIABLE
document.addEventListener('DOMContentLoaded', function () {
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

        // Force display flex via inline style as well to ensure visibility
        navLinks.style.display = 'flex';
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
                content.style.display = '';
                content.style.maxHeight = '';
            }
        });

        // Hide links on mobile
        if (window.innerWidth <= 768) {
            navLinks.style.display = 'none';
        }
    }

    function toggleMenu() {
        if (isMenuOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    // Hamburger button click
    mobileMenuToggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
    });

    // Close button click
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeMenu();
        });
    }

    // Overlay click to close
    overlay.addEventListener('click', function (e) {
        closeMenu();
    });

    // Escape key to close
    document.addEventListener('keydown', function (e) {
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
            btn.addEventListener('click', function (e) {
                if (window.innerWidth <= 768) {
                    // Prevent navigation and other listeners (like nav-dropdown.js)
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const isOpen = dropdown.classList.contains('open');

                    // Close ALL other dropdowns first
                    dropdowns.forEach(d => {
                        if (d !== dropdown) {
                            d.classList.remove('open');
                            const otherContent = d.querySelector('.nav-dropdown-content');
                            if (otherContent) {
                                otherContent.style.display = 'none';
                                otherContent.style.maxHeight = '0';
                            }
                        }
                    });

                    // Toggle current dropdown
                    if (isOpen) {
                        dropdown.classList.remove('open');
                        content.style.display = 'none';
                        content.style.maxHeight = '0';
                    } else {
                        dropdown.classList.add('open');
                        content.style.display = 'block';
                        content.style.maxHeight = '500px'; // Ensure it's large enough
                    }
                }
            });
        }
    });

    // Handle regular navigation link clicks on mobile
    const navItems = navLinks.querySelectorAll('a');
    navItems.forEach(link => {
        link.addEventListener('click', function (e) {
            // Don't close menu for dropdown TOGGLE buttons
            if (this.classList.contains('nav-btn') && this.nextElementSibling && this.nextElementSibling.classList.contains('nav-dropdown-content')) {
                return;
            }

            // Close menu for actual links
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth > 768) {
                // Return to desktop mode
                if (isMenuOpen) {
                    closeMenu();
                }
                navLinks.style.display = 'flex';
                // Reset any mobile-specific inline styles on dropdowns
                dropdowns.forEach(d => {
                    const content = d.querySelector('.nav-dropdown-content');
                    if (content) {
                        content.style.display = '';
                        content.style.maxHeight = '';
                    }
                });
            } else {
                // Return to mobile mode
                if (!isMenuOpen) {
                    navLinks.style.display = 'none';
                } else {
                    navLinks.style.display = 'flex';
                }
            }
        }, 100);
    });

    // Set initial display based on screen size
    if (window.innerWidth <= 768) {
        navLinks.style.display = 'none';
    } else {
        navLinks.style.display = 'flex';
    }
});

