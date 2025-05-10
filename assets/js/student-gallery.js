document.addEventListener('DOMContentLoaded', function() {
    const gallery = document.querySelector('.students-gallery');
    const track = document.createElement('div');
    track.className = 'students-gallery-track';
    
    // Get all original items
    const items = Array.from(gallery.children);
    
    // Add original items to track
    items.forEach(item => track.appendChild(item.cloneNode(true)));
    
    // Clone items and add them again to ensure seamless loop
    items.forEach(item => track.appendChild(item.cloneNode(true)));
    items.forEach(item => track.appendChild(item.cloneNode(true)));
    items.forEach(item => track.appendChild(item.cloneNode(true)));
    
    // Clear gallery and add track
    gallery.innerHTML = '';
    gallery.appendChild(track);
    
    // Function to check if we need to reset
    function checkPosition() {
        const trackRect = track.getBoundingClientRect();
        const galleryRect = gallery.getBoundingClientRect();
        
        if (trackRect.right <= galleryRect.right) {
            // Reset without animation
            track.style.animation = 'none';
            track.offsetHeight; // Trigger reflow
            track.style.animation = null;
        }
    }
    
    // Add animation event listener
    track.addEventListener('animationend', checkPosition);
}); 