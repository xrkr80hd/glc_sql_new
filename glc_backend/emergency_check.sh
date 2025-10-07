#!/bin/bash

# Emergency Live Stream Checker
# Quick script to manually check if we're live when automatic detection fails

echo "🚨 EMERGENCY LIVE STREAM CHECK"
echo "=============================="
echo ""

# Change to backend directory
cd "$(dirname "$0")"

# Run the emergency checker
node emergency_live_check.js

echo ""
echo "💡 TIP: If you're live but it says you're not:"
echo "   1. Check your .env file has correct YouTube API keys"  
echo "   2. Make sure you're actually broadcasting (not just scheduled)"
echo "   3. Wait 1-2 minutes, then refresh your website"