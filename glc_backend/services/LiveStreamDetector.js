/**
 * Live Stream Detection Service
 * Automatically detects when Liberty Church goes live on YouTube
 * Works outside normal service hours for special events, prayer meetings, etc.
 */

const fs = require('fs');
const path = require('path');
require('dotenv').config();

class LiveStreamDetector {
    constructor() {
        this.channelId = process.env.YOUTUBE_CHANNEL_ID;
        this.apiKey = process.env.YOUTUBE_LIVE_API_KEY || process.env.YOUTUBE_API_KEY;
        this.outputPath = path.join(__dirname, '../assets/data/live.json');
        this.checkInterval = 2 * 60 * 1000; // Check every 2 minutes
        this.isRunning = false;
        
        // Ensure output directory exists
        this.ensureOutputDirectory();
        
        console.log('🔴 Live Stream Detector initialized');
        console.log(`📺 Monitoring channel: ${this.channelId}`);
        console.log(`⏰ Check interval: ${this.checkInterval / 1000} seconds`);
    }

    /**
     * Ensure the output directory exists
     */
    ensureOutputDirectory() {
        const dir = path.dirname(this.outputPath);
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
            console.log(`📁 Created directory: ${dir}`);
        }
    }

    /**
     * Start the live stream detection service
     */
    start() {
        if (this.isRunning) {
            console.log('⚠️ Live stream detector is already running');
            return;
        }

        this.isRunning = true;
        console.log('🚀 Starting live stream detection service...');
        
        // Initial check
        this.checkLiveStream();
        
        // Set up recurring checks
        this.intervalId = setInterval(() => {
            this.checkLiveStream();
        }, this.checkInterval);
        
        console.log('✅ Live stream detector is now running');
    }

    /**
     * Stop the live stream detection service
     */
    stop() {
        if (!this.isRunning) {
            console.log('⚠️ Live stream detector is not running');
            return;
        }

        this.isRunning = false;
        
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        console.log('🛑 Live stream detector stopped');
    }

    /**
     * Check if the channel is currently live streaming
     */
    async checkLiveStream() {
        try {
            console.log(`🔍 Checking for live streams at ${new Date().toLocaleTimeString()}...`);
            
            if (!this.apiKey || !this.channelId) {
                throw new Error('Missing YouTube API credentials');
            }

            // Search for live broadcasts on the channel
            const searchUrl = `https://www.googleapis.com/youtube/v3/search?` +
                `part=snippet&channelId=${this.channelId}&eventType=live&type=video&key=${this.apiKey}`;

            const response = await fetch(searchUrl);
            
            if (!response.ok) {
                throw new Error(`YouTube API error: ${response.status} ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.items && data.items.length > 0) {
                // Found live stream!
                const liveStream = data.items[0];
                const streamData = {
                    isLive: true,
                    videoId: liveStream.id.videoId,
                    title: liveStream.snippet.title,
                    description: liveStream.snippet.description,
                    thumbnail: liveStream.snippet.thumbnails.high?.url || liveStream.snippet.thumbnails.default?.url,
                    publishedAt: liveStream.snippet.publishedAt,
                    detectedAt: new Date().toISOString(),
                    source: 'auto-detection'
                };

                await this.updateLiveStatus(streamData);
                console.log(`🔴 LIVE DETECTED: "${streamData.title}" (${streamData.videoId})`);
                
            } else {
                // No live stream found
                const offlineData = {
                    isLive: false,
                    detectedAt: new Date().toISOString(),
                    source: 'auto-detection',
                    nextCheck: new Date(Date.now() + this.checkInterval).toISOString()
                };

                await this.updateLiveStatus(offlineData);
                console.log('📺 No live stream detected');
            }

        } catch (error) {
            console.error('❌ Error checking live stream:', error.message);
            
            // Update with error status
            const errorData = {
                isLive: false,
                error: error.message,
                detectedAt: new Date().toISOString(),
                source: 'auto-detection-error'
            };
            
            await this.updateLiveStatus(errorData);
        }
    }

    /**
     * Update the live stream status file
     */
    async updateLiveStatus(streamData) {
        try {
            // Read existing data if it exists
            let existingData = {};
            if (fs.existsSync(this.outputPath)) {
                const fileContent = fs.readFileSync(this.outputPath, 'utf8');
                existingData = JSON.parse(fileContent);
            }

            // Merge with new data
            const updatedData = {
                ...existingData,
                ...streamData,
                lastUpdated: new Date().toISOString()
            };

            // Write to file
            fs.writeFileSync(this.outputPath, JSON.stringify(updatedData, null, 2));
            
            console.log(`💾 Live status updated: ${this.outputPath}`);
            
        } catch (error) {
            console.error('❌ Error updating live status file:', error.message);
        }
    }

    /**
     * Get current live stream status
     */
    async getCurrentStatus() {
        try {
            if (fs.existsSync(this.outputPath)) {
                const fileContent = fs.readFileSync(this.outputPath, 'utf8');
                return JSON.parse(fileContent);
            }
            return { isLive: false };
        } catch (error) {
            console.error('❌ Error reading live status:', error.message);
            return { isLive: false, error: error.message };
        }
    }

    /**
     * Force an immediate check (useful for testing)
     */
    async forceCheck() {
        console.log('🔍 Force checking live stream status...');
        await this.checkLiveStream();
    }
}

// Export the class for use in other files
module.exports = LiveStreamDetector;

// If this file is run directly, start the detector
if (require.main === module) {
    const detector = new LiveStreamDetector();
    
    // Start the detector
    detector.start();
    
    // Graceful shutdown
    process.on('SIGINT', () => {
        console.log('\n🛑 Received SIGINT. Shutting down gracefully...');
        detector.stop();
        process.exit(0);
    });
    
    process.on('SIGTERM', () => {
        console.log('\n🛑 Received SIGTERM. Shutting down gracefully...');
        detector.stop();
        process.exit(0);
    });
}