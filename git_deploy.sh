#!/bin/bash

# ==============================================================================
#  GIT AUTO-DEPLOY SCRIPT (CRON JOB)
# ==============================================================================
# Purpose:
# Keep production synced with origin/main without getting blocked by runtime
# cache files generated on the server.
#
# Usage:
# /bin/bash /home/kaishopi/domains/kaishop.id.vn/public_html/git_deploy.sh
# ==============================================================================

set -u

PROJECT_DIR="/home/kaishopi/domains/kaishop.id.vn/public_html"
LOG_FILE="${PROJECT_DIR}/storage/logs/cron_deploy.log"
BRANCH="main"
GIT_BIN="/usr/bin/git"

mkdir -p "${PROJECT_DIR}/storage/logs" "${PROJECT_DIR}/storage/cache"

exec >> "$LOG_FILE" 2>&1

echo "------------------------------------------------------------"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting deploy..."

cd "$PROJECT_DIR" || {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Could not change directory to $PROJECT_DIR"
    exit 1
}

if ! "$GIT_BIN" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $PROJECT_DIR is not a git repository"
    exit 1
fi

# Runtime cache files can be modified by the app on production. Reset and clean
# them first so deploy never gets blocked by local cache changes.
"$GIT_BIN" restore --source=HEAD --staged --worktree -- storage/cache >/dev/null 2>&1 || true
find storage/cache -type f ! -name '.gitignore' -delete >/dev/null 2>&1 || true

if ! "$GIT_BIN" fetch --prune origin "$BRANCH"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: git fetch failed"
    exit 1
fi

# Production should mirror origin/main exactly. This avoids merge conflicts from
# local runtime changes and keeps cron deploy deterministic.
if ! "$GIT_BIN" reset --hard "origin/$BRANCH"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: git reset --hard origin/$BRANCH failed"
    exit 1
fi

find storage/cache -type f ! -name '.gitignore' -delete >/dev/null 2>&1 || true

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Success: Production synced to origin/$BRANCH"
