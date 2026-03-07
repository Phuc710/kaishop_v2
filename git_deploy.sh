#!/bin/bash

# ==============================================================================
#  GIT AUTO-DEPLOY SCRIPT (CRON JOB)
# ==============================================================================
# Purpose: This script is intended to be run by a Cron Job to automatically
# keep your project updated with the latest code from GitHub.
#
# Usage:
# Add this to your Cron Job (every 1-5 minutes):
# /bin/bash /home/kaishopi/domains/kaishop.id.vn/public_html/git_deploy.sh
# ==============================================================================

# Project directory (Update this path if it's different on your server)
PROJECT_DIR="/home/kaishopi/domains/kaishop.id.vn/public_html"
LOG_FILE="${PROJECT_DIR}/storage/logs/cron_deploy.log"
BRANCH="main"

# Ensure log directory exists
mkdir -p "$(dirname "$LOG_FILE")"

# Execute pull
{
    echo "------------------------------------------------------------"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting Git Pull..."
    
    cd "$PROJECT_DIR" || { echo "ERROR: Could not change directory to $PROJECT_DIR"; exit 1; }
    
    # Run git pull
    # We use --ff-only to ensure we don't accidentally create merge commits on server
    /usr/bin/git pull origin "$BRANCH" --ff-only 2>&1
    
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -eq 0 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Success: Code updated."
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Error: Git pull failed with exit code $EXIT_CODE."
    fi
} >> "$LOG_FILE" 2>&1
