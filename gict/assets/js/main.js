document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.style.display = navLinks.style.display === 'block' ? 'none' : 'block';
        });
    }

    // Accessibility Features
    const accessButtons = document.querySelectorAll('.access-btn');
    let currentFontSize = 1;

    accessButtons.forEach((btn, index) => {
        btn.addEventListener('click', function() {
            switch(index) {
                case 0: // A-
                    currentFontSize = Math.max(0.8, currentFontSize - 0.2);
                    document.body.style.fontSize = `${currentFontSize}em`;
                    break;
                case 1: // A
                    currentFontSize = 1;
                    document.body.style.fontSize = `${currentFontSize}em`;
                    break;
                case 2: // A+
                    currentFontSize = Math.min(1.4, currentFontSize + 0.2);
                    document.body.style.fontSize = `${currentFontSize}em`;
                    break;
                case 3: // T (High Contrast)
                    document.body.classList.toggle('high-contrast');
                    break;
                case 4: // T (Reset)
                    document.body.classList.remove('high-contrast');
                    currentFontSize = 1;
                    document.body.style.fontSize = `${currentFontSize}em`;
                    break;
            }
        });
    });

    // Smooth Scrolling for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // News Card Hover Effect
    const newsCards = document.querySelectorAll('.news-card');
    newsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Quick Links Animation
    const quickLinks = document.querySelectorAll('.quick-link');
    quickLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Responsive Navigation
    function handleResize() {
        if (window.innerWidth > 768) {
            navLinks.style.display = 'flex';
        } else {
            navLinks.style.display = 'none';
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check
}); 