/**
 * Image Preloader - Optimize image loading performance
 * This script helps reduce stuttering by managing image loads
 */

// Add loading class to images until they're loaded
document.addEventListener('DOMContentLoaded', function() {
    // Find all images that will be lazy loaded
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    images.forEach(img => {
        // Add loaded class when image loads
        img.addEventListener('load', function() {
            this.classList.add('image-loaded');
        });
        
        // Handle error cases
        img.addEventListener('error', function() {
            this.classList.add('image-error');
            console.warn('Failed to load image:', this.src);
        });
    });
});

// Intersection Observer for better lazy loading control
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // If image has data-src, load it
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px' // Start loading 50px before entering viewport
    });
    
    // Observe images with data-src attribute
    window.observeLazyImages = function() {
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => imageObserver.observe(img));
    };
}

// Network speed detection and image quality adjustment
function getConnectionSpeed() {
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    
    if (connection) {
        // Adjust image quality based on connection
        if (connection.effectiveType === '4g') {
            return 'high'; // Load full quality
        } else if (connection.effectiveType === '3g') {
            return 'medium'; // Load medium quality
        } else {
            return 'low'; // Load low quality
        }
    }
    
    return 'high'; // Default to high quality
}

// Export for use in main.js
window.imageOptimizer = {
    getConnectionSpeed: getConnectionSpeed,
    
    // Get optimized image URL based on connection
    getOptimizedUrl: function(url) {
        if (!url || !url.includes('unsplash.com')) {
            return url;
        }
        
        const speed = getConnectionSpeed();
        const params = new URLSearchParams();
        
        switch(speed) {
            case 'low':
                params.set('w', '400');
                params.set('q', '60');
                break;
            case 'medium':
                params.set('w', '600');
                params.set('q', '70');
                break;
            default: // high
                params.set('w', '800');
                params.set('q', '80');
        }
        
        params.set('fit', 'crop');
        params.set('auto', 'format');
        
        // Add params to URL
        const separator = url.includes('?') ? '&' : '?';
        return url + separator + params.toString();
    }
};

// Cache loaded images in memory
const imageCache = new Map();

window.cacheImage = function(url) {
    if (!imageCache.has(url)) {
        const img = new Image();
        img.src = url;
        imageCache.set(url, img);
    }
    return imageCache.get(url);
};

console.log('Image optimizer loaded - Connection speed:', getConnectionSpeed());
