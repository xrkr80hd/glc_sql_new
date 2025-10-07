// Live Stream Manager for Liberty Church
class LiveStreamManager {
    constructor() {
        this.statusUrl = 'assets/data/live.json';
        this.updateInterval = 60000; // 60 seconds
        this.retryCount = 0;
        this.maxRetries = 3;
        this.isManualMode = false;

        this.init();
    }

    init() {
        if (this.isManualMode) {
            this.showManualMode();
        } else {
            this.loadLiveStream();
            this.startAutoRefresh();
        }
        console.log('🎥 Live Stream Manager initialized');
    }

    showManualMode() {
        this.renderFallbackVideo();
    }

    async loadLiveStream() {
        try {
            const url = `${this.statusUrl}?cb=${Date.now()}`;
            const response = await fetch(url, { cache: 'no-store' });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            this.updateStreamDisplay(data);
            this.retryCount = 0;
        } catch (error) {
            console.error('Failed to load stream data:', error);
            this.handleError();
        }
    }

    updateStreamDisplay(streamData) {
        if (!streamData || !streamData.isLive) {
            this.renderFallbackVideo(streamData);
            return;
        }

        // Stream is live - show YouTube iframe
        this.renderLiveStream(streamData);
    }

    renderLiveStream(streamData) {
        const ls1 = document.getElementById('LS1');
        const ls2 = document.getElementById('LS2');

        if (!ls1 || !ls2) return;

        const videoId = streamData.videoId;
        const title = streamData.title || 'Liberty Church Live Service';

        // Hide fallback video, show live stream
        ls2.style.display = 'none';
        ls1.style.display = 'block';

        if (videoId) {
            // Beautiful live stream with same styling as sermons.html
            ls1.innerHTML = `
                <div class="live-indicator">
                    <span class="live-dot"></span>
                    <span class="live-text">LIVE NOW</span>
                </div>
                <div class="embed aspect-16x9">
                    <iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&showinfo=0&modestbranding=1"
                        width="560"
                        height="315"
                        title="${title}"
                        frameborder="0"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen></iframe>
                </div>
            `;
            
            console.log('🔴 LIVE: Switched to YouTube stream');
        } else {
            // Fallback to loading state
            ls1.innerHTML = `
                <div class="live-indicator">
                    <span class="live-dot"></span>
                    <span class="live-text">GOING LIVE SOON</span>
                </div>
                <div class="embed aspect-16x9">
                    <div class="content placeholder">
                        Live stream is starting soon. Please stay tuned.
                    </div>
                </div>
            `;
        }

        // Update page title
        document.title = '🔴 LIVE: Sunday Service | Liberty Church';
    }

    renderFallbackVideo(streamData = null) {
        const ls1 = document.getElementById('LS1');
        const ls2 = document.getElementById('LS2');

        if (!ls2) return;

        // Hide live stream iframe
        if (ls1) {
            ls1.style.display = 'none';
        }

        // Show beautiful MP4 fallback video
        ls2.style.display = 'block';

        const upcoming = streamData && streamData.upcoming;
        const nextServiceText = upcoming && upcoming.scheduledStart
            ? `Next service starts ${this.formatDate(upcoming.scheduledStart)}`
            : 'We are not currently streaming live. Join us Sundays at 10:00 AM.';

        ls2.innerHTML = `
            <div class="embed aspect-16x9">
                <video class="fallback-video" autoplay muted loop>
                    <source src="assets/stream_fallback_loop/stream_fall_back_loop.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            <p class="note">${nextServiceText}</p>
        `;

        console.log('📺 Showing warm MP4 fallback video');
        document.title = 'Live Services | Liberty Church';
    }

    handleError() {
        this.retryCount++;

        if (this.retryCount <= this.maxRetries) {
            console.log(`Retrying... (${this.retryCount}/${this.maxRetries})`);
            setTimeout(() => this.loadLiveStream(), 5000);
        } else {
            this.renderFallbackVideo();
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadLiveStream(), this.updateInterval);
    }

    refresh() {
        this.retryCount = 0;
        this.loadLiveStream();
    }

    formatDate(isoString) {
        try {
            const date = new Date(isoString);
            return date.toLocaleString('en-US', {
                weekday: 'long',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            return 'soon';
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.liveStreamManager = new LiveStreamManager();
});

// Enhanced CSS for better display - FIXED VIDEO POSITIONING
const style = document.createElement('style');
style.textContent = `
.live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 1rem;
    color: #c42020;
    background: rgba(255,0,0,.08);
    padding: 4px 12px;
    border-radius: 999px;
    border: 1px solid rgba(255,0,0,.35);
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.live-dot {
    width: 8px;
    height: 8px;
    background-color: #ff2a2a;
    border-radius: 50%;
    box-shadow: 0 0 0 4px rgba(255,42,42,.25);
    animation: pulse 1.6s infinite ease-in-out;
}

@keyframes pulse {
    0%, 100% { 
        transform: scale(0.9); 
        opacity: 0.9; 
    }
    50% { 
        transform: scale(1.15); 
        opacity: 1; 
    }
}

.offline-message {
    color: var(--muted);
    font-style: italic;
    margin-bottom: 1rem;
    text-align: center;
}

.embed.aspect-16x9 {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 aspect ratio */
    background: var(--card);
    border: 2px solid transparent;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.embed .content {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.embed .content.placeholder {
    display: grid;
    place-items: center;
    color: var(--muted);
    background: var(--bg-alt);
    border: 1px dashed var(--border);
    font-weight: 500;
    text-align: center;
    border-radius: 12px;
}

/* CRITICAL FIX: Video positioning with !important to override any inline styles */
.fallback-video {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border: 0 !important;
    border-radius: 12px;
}

/* Ensure any iframe also stays contained */
.embed iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
    border-radius: 12px;
}

.note {
    color: var(--muted);
    font-size: 0.92rem;
    text-align: center;
    margin-top: 1rem;
}
`;
document.head.appendChild(style);