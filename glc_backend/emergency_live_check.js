#!/usr/bin/env node

/**
 * Emergency Live Stream Checker
 * Run this manually when automatic detection isn't working
 * 
 * Usage:
 *   node emergency_live_check.js
 * 
 * What it does:
 *   - Forces a fresh check of YouTube API
 *   - Updates live.json with current stream status
 *   - Shows you what it found
 */

require('dotenv').config();
const https = require('https');
const fs = require('fs');
const path = require('path');

class EmergencyLiveChecker {
    constructor() {
        this.channelId = process.env.YOUTUBE_CHANNEL_ID;
        this.apiKey = process.env.YOUTUBE_LIVE_API_KEY || process.env.YOUTUBE_API_KEY;
        this.outputFile = path.join(__dirname, '../assets/data/live.json');
        
        if (!this.channelId || !this.apiKey) {
            console.error('❌ Missing YouTube credentials in .env file');
            console.log('Need: YOUTUBE_CHANNEL_ID and YOUTUBE_LIVE_API_KEY');
            process.exit(1);
        }
    }

    async checkLiveStream() {
        console.log('🔍 Emergency Live Stream Check Starting...');
        console.log(`📺 Channel: ${this.channelId}`);
        
        try {
            const liveData = await this.fetchLiveStreams();
            this.saveLiveData(liveData);
            this.displayResults(liveData);
            
        } catch (error) {
            console.error('❌ Error checking live stream:', error.message);
            this.saveErrorState();
        }
    }

    fetchLiveStreams() {
        return new Promise((resolve, reject) => {
            const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=${this.channelId}&eventType=live&type=video&key=${this.apiKey}`;
            
            console.log('🌐 Checking YouTube API...');
            
            https.get(url, (response) => {
                let data = '';
                
                response.on('data', (chunk) => {
                    data += chunk;
                });
                
                response.on('end', () => {
                    try {
                        const parsed = JSON.parse(data);
                        
                        if (parsed.error) {
                            reject(new Error(`YouTube API Error: ${parsed.error.message}`));
                            return;
                        }
                        
                        const liveStreams = parsed.items || [];
                        const result = {
                            isLive: liveStreams.length > 0,
                            lastChecked: new Date().toISOString(),
                            source: 'emergency-check',
                            streams: liveStreams.map(stream => ({
                                videoId: stream.id.videoId,
                                title: stream.snippet.title,
                                thumbnailUrl: stream.snippet.thumbnails.default.url,
                                publishedAt: stream.snippet.publishedAt
                            }))
                        };
                        
                        if (result.isLive) {
                            result.videoId = result.streams[0].videoId;
                            result.title = result.streams[0].title;
                        }
                        
                        resolve(result);
                        
                    } catch (parseError) {
                        reject(new Error(`Failed to parse YouTube response: ${parseError.message}`));
                    }
                });
                
            }).on('error', (error) => {
                reject(new Error(`Network error: ${error.message}`));
            });
        });
    }

    saveLiveData(data) {
        try {
            // Ensure directory exists
            const dir = path.dirname(this.outputFile);
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
            
            // Save the data
            fs.writeFileSync(this.outputFile, JSON.stringify(data, null, 2));
            console.log('✅ Updated live.json');
            
        } catch (error) {
            console.error('❌ Failed to save live.json:', error.message);
        }
    }

    saveErrorState() {
        const errorData = {
            isLive: false,
            lastChecked: new Date().toISOString(),
            source: 'emergency-check-error',
            error: 'Failed to check live stream status'
        };
        this.saveLiveData(errorData);
    }

    displayResults(data) {
        console.log('\n' + '='.repeat(50));
        console.log('🎥 EMERGENCY LIVE STREAM CHECK RESULTS');
        console.log('='.repeat(50));
        
        if (data.isLive) {
            console.log('🔴 STATUS: LIVE STREAM ACTIVE');
            console.log(`📺 Video ID: ${data.videoId}`);
            console.log(`📝 Title: ${data.title}`);
            console.log(`🕐 Last Checked: ${new Date(data.lastChecked).toLocaleString()}`);
        } else {
            console.log('⚫ STATUS: NO LIVE STREAM');
            console.log(`🕐 Last Checked: ${new Date(data.lastChecked).toLocaleString()}`);
            console.log('💡 The website will show the fallback video');
        }
        
        console.log('='.repeat(50));
        console.log('✅ Emergency check complete!');
        console.log('📁 Results saved to: assets/data/live.json');
        console.log('🌐 Your website will pick up these changes automatically');
    }
}

// Run the emergency check
const checker = new EmergencyLiveChecker();
checker.checkLiveStream();