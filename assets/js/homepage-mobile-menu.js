// Homepage Mobile Menu Functionality - Simplified Test Version
console.log('Homepage Mobile Menu JS loaded successfully!');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Starting Mobile Menu Test');
    
    // Get elements
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    
    console.log('Elements found:', {
        toggle: mobileMenuToggle,
        navLinks: navLinks,
        close: mobileMenuClose
    });
    
    // Simple test - add red border to button
    if (mobileMenuToggle) {
        mobileMenuToggle.style.border = '3px solid red';
        mobileMenuToggle.style.backgroundColor = 'yellow';
        console.log('Button styled for testing');
        
        // Simple click test
        mobileMenuToggle.addEventListener('click', function() {
            console.log('Button clicked!');
            alert('Hamburger button is working!');
            
            // Toggle menu
            if (navLinks.classList.contains('mobile-open')) {
                navLinks.classList.remove('mobile-open');
                console.log('Menu closed');
            } else {
                navLinks.classList.add('mobile-open');
                console.log('Menu opened');
            }
        });
    } else {
        console.error('Mobile menu toggle button not found!');
    }
    
    // Close button test
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', function() {
            console.log('Close button clicked');
            navLinks.classList.remove('mobile-open');
        });
    }
    
    console.log('Mobile menu test setup complete');
});
