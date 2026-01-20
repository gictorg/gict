document.addEventListener('DOMContentLoaded', function() {
    const gallery = document.querySelector('.students-gallery');
    if (!gallery) return;
    
    const track = document.createElement('div');
    track.className = 'faculty-gallery-track';
    
    // Get all original items
    const items = Array.from(gallery.children);
    
    if (items.length === 0) return;
    
    // Calculate item width (260px + 20px gap)
    const itemWidth = 280;
    const totalItems = items.length;
    
    // Add original items to track
    items.forEach(item => track.appendChild(item.cloneNode(true)));
    
    // Clone items for seamless infinite loop
    items.forEach(item => track.appendChild(item.cloneNode(true)));
    
    // Clear gallery and add track
    gallery.innerHTML = '';
    gallery.appendChild(track);
    
    // Apply animation - calculate duration based on number of items
    const animationDuration = totalItems * 3; // 3 seconds per item
    track.style.animation = `facultyScroll ${animationDuration}s linear infinite`;
});