document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const slideContainer = document.querySelector('.slideshow-container');
    let currentSlide = 0;
    let isAnimating = false;
    
    // Create slide indicators
    const indicatorsContainer = document.createElement('div');
    indicatorsContainer.className = 'slide-indicators';
    slides.forEach((_, index) => {
        const indicator = document.createElement('div');
        indicator.className = 'slide-indicator' + (index === 0 ? ' active' : '');
        indicator.addEventListener('click', () => {
            if (!isAnimating && index !== currentSlide) {
                showSlide(index, index > currentSlide ? 'right' : 'left');
            }
        });
        indicatorsContainer.appendChild(indicator);
    });
    slideContainer.appendChild(indicatorsContainer);

    // Create navigation arrows
    const prevButton = document.createElement('div');
    prevButton.className = 'slide-nav prev';
    prevButton.innerHTML = '❮';
    prevButton.addEventListener('click', () => {
        if (!isAnimating) {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev, 'right');
        }
    });

    const nextButton = document.createElement('div');
    nextButton.className = 'slide-nav next';
    nextButton.innerHTML = '❯';
    nextButton.addEventListener('click', () => {
        if (!isAnimating) {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next, 'left');
        }
    });

    slideContainer.appendChild(prevButton);
    slideContainer.appendChild(nextButton);
    
    // Initialize first slide
    slides.forEach((slide, index) => {
        if (index === 0) {
            slide.style.display = 'block';
            slide.style.transform = 'translateX(0)';
            slide.style.opacity = '1';
        } else {
            slide.style.display = 'none';
            slide.style.transform = 'translateX(100%)';
            slide.style.opacity = '0';
        }
    });
    
    function updateIndicators(index) {
        const indicators = document.querySelectorAll('.slide-indicator');
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
        });
    }
    
    function showSlide(index, direction) {
        if (isAnimating) return;
        isAnimating = true;

        // Show new slide
        slides[index].style.display = 'block';
        slides[index].style.transform = direction === 'right' ? 
            'translateX(-100%)' : 'translateX(100%)';
        slides[index].style.opacity = '0';

        // Force reflow
        slides[index].offsetHeight;

        // Animate slides
        requestAnimationFrame(() => {
            // Move current slide out
            slides[currentSlide].style.transform = direction === 'right' ? 
                'translateX(100%)' : 'translateX(-100%)';
            slides[currentSlide].style.opacity = '0';
            
            // Move new slide in
            slides[index].style.transform = 'translateX(0)';
            slides[index].style.opacity = '1';
            
            // Update indicators
            updateIndicators(index);
            
            // Clean up after transition
            setTimeout(() => {
                slides[currentSlide].style.display = 'none';
                currentSlide = index;
                isAnimating = false;
            }, 800);
        });
    }
    
    function nextSlide() {
        if (!isAnimating) {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next, 'left');
        }
    }
    
    // Change slide every 3 seconds
    let slideInterval = setInterval(nextSlide, 3000);
    
    // Pause slideshow on hover
    slideContainer.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    slideContainer.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 3000);
    });
}); 