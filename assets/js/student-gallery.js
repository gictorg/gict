document.addEventListener('DOMContentLoaded', function() {
    const gallery = document.querySelector('.students-gallery');
    if (!gallery) return;
    
    const track = document.createElement('div');
    track.className = 'students-gallery-track';
    
    // Get all original items
    const items = Array.from(gallery.children);
    
    if (items.length === 0) return;
    
    // Only create carousel effect if we have enough items (4 or more)
    // For fewer items, just display them once without duplication
    if (items.length >= 4) {
        // Add original items to track
        items.forEach(item => track.appendChild(item.cloneNode(true)));
        
        // Clone items once for seamless loop (only if we have enough items)
        items.forEach(item => track.appendChild(item.cloneNode(true)));
    } else {
        // For fewer than 4 items, just show them once - no cloning
        items.forEach(item => track.appendChild(item.cloneNode(true)));
    }
    
    // Clear gallery and add track
    gallery.innerHTML = '';
    gallery.appendChild(track);
    
    // Function to check if we need to reset (only for carousel)
    if (items.length >= 4) {
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
    }
}); 