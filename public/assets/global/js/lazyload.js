/*!
 * LazyLoad - Simple image lazy loading
 * Version: 1.0.0
 */

(function() {
    'use strict';
    
    // Check if IntersectionObserver is supported
    if (!('IntersectionObserver' in window)) {
        // Fallback for older browsers
        var images = document.querySelectorAll('img[data-src]');
        for (var i = 0; i < images.length; i++) {
            if (images[i].dataset.src) {
                images[i].src = images[i].dataset.src;
                images[i].removeAttribute('data-src');
            }
        }
        return;
    }
    
    // Create observer
    var imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var image = entry.target;
                if (image.dataset.src) {
                    image.src = image.dataset.src;
                    image.removeAttribute('data-src');
                    image.classList.remove('lazyload');
                    imageObserver.unobserve(image);
                }
            }
        });
    });
    
    // Observe all images with data-src
    var images = document.querySelectorAll('img[data-src]');
    for (var i = 0; i < images.length; i++) {
        imageObserver.observe(images[i]);
    }
    
    // Also handle background images
    var bgElements = document.querySelectorAll('[data-bg]');
    for (var j = 0; j < bgElements.length; j++) {
        imageObserver.observe(bgElements[j]);
    }
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // LazyLoad is already initialized above
    });
} else {
    // DOM is already ready
}