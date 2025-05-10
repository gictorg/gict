document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.events-slider');
    const slides = document.querySelectorAll('.event-slide');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    let currentIndex = 0;
    const slidesPerView = 3;
    const totalSlides = slides.length;
    
    function updateSlidesPosition() {
        const slideWidth = slides[0].offsetWidth + 20; // Width + gap
        const maxIndex = totalSlides - slidesPerView;
        
        // Scroll to the current position
        slider.scrollTo({
            left: currentIndex * slideWidth,
            behavior: 'smooth'
        });
        
        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= maxIndex;
    }
    
    // Event listeners for buttons
    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlidesPosition();
        }
    });
    
    nextBtn.addEventListener('click', () => {
        const maxIndex = totalSlides - slidesPerView;
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlidesPosition();
        }
    });
    
    // Initial setup
    updateSlidesPosition();
    
    // Update on window resize
    window.addEventListener('resize', updateSlidesPosition);
}); 