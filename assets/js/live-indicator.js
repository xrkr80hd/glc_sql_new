// Live Stream Indicator - AJAX Polling for Sitewide Navigation
(function() {
    let isLive = false;
    
    function updateLiveIndicators() {
        fetch('/api/stream-status/')
            .then(res => res.json())
            .then(data => {
                isLive = data.is_live || false;
                
                // Find all "Watch Live" links in navigation
                const liveLinks = document.querySelectorAll('nav a[href*="live.html"]');
                
                liveLinks.forEach(link => {
                    if (isLive) {
                        if (!link.classList.contains('nav-live-indicator')) {
                            link.classList.add('nav-live-indicator');
                        }
                    } else {
                        link.classList.remove('nav-live-indicator');
                    }
                });
            })
            .catch(err => console.error('Live indicator check failed:', err));
    }
    
    // Check on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateLiveIndicators);
    } else {
        updateLiveIndicators();
    }
    
    // Poll every 30 seconds
    setInterval(updateLiveIndicators, 30000);
})();
