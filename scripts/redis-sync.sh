#!/usr/bin/env bash
#
# Redis Sync Script
# Syncs local Mac Redis with dev server Redis
# Handles online/offline scenarios with automatic failover
#
# Usage:
#   ./redis-sync.sh              # Run sync once
#   ./redis-sync.sh --daemon     # Run as daemon (every 5 mins)
#   ./redis-sync.sh --force-push # Force push local to dev
#   ./redis-sync.sh --force-pull # Force pull from dev
#

set -euo pipefail

# Configuration
DEV_HOST="paycal.app"
DEV_REDIS_PORT=6379
LOCAL_REDIS_PORT=6379
SYNC_INTERVAL=300  # 5 minutes
LOCK_FILE="/tmp/redis-sync.lock"
LOG_FILE="/var/www/paycal/dev/logs/redis-sync.log"
STATE_FILE="/tmp/redis-sync-state"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging
log() {
    echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

# Check if dev server is reachable
check_connectivity() {
    if ssh -o ConnectTimeout=5 -o BatchMode=yes "$DEV_HOST" "exit" 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Get Redis info
get_redis_info() {
    local host=$1
    local port=$2
    
    if [[ "$host" == "local" ]]; then
        redis-cli -p "$port" INFO server 2>/dev/null | grep -E "redis_version|uptime_in_seconds" || echo "error"
    else
        ssh "$host" "redis-cli -p $port INFO server 2>/dev/null | grep -E 'redis_version|uptime_in_seconds'" || echo "error"
    fi
}

# Get last write timestamp from Redis
get_last_write_timestamp() {
    local host=$1
    local port=$2
    
    if [[ "$host" == "local" ]]; then
        redis-cli -p "$port" LASTSAVE 2>/dev/null || echo "0"
    else
        ssh "$host" "redis-cli -p $port LASTSAVE 2>/dev/null" || echo "0"
    fi
}

# Check if local Redis has changes since last sync
has_local_changes() {
    if [[ ! -f "$STATE_FILE" ]]; then
        return 0  # No state file, assume changes
    fi
    
    local last_sync=$(cat "$STATE_FILE" 2>/dev/null || echo "0")
    local current_write=$(get_last_write_timestamp "local" "$LOCAL_REDIS_PORT")
    
    if [[ "$current_write" -gt "$last_sync" ]]; then
        return 0  # Has changes
    else
        return 1  # No changes
    fi
}

# Dump local Redis to dev server
push_to_dev() {
    info "Pushing local Redis changes to dev server..."
    
    # Create temporary dump
    local temp_dump="/tmp/redis-dump-$(date +%s).rdb"
    
    # Save local Redis
    redis-cli -p "$LOCAL_REDIS_PORT" SAVE >/dev/null 2>&1
    
    # Copy dump file
    local redis_dir=$(redis-cli -p "$LOCAL_REDIS_PORT" CONFIG GET dir | tail -1)
    local dump_file="$redis_dir/dump.rdb"
    
    if [[ ! -f "$dump_file" ]]; then
        error "Local Redis dump file not found: $dump_file"
        return 1
    fi
    
    # Copy dump (readable by all, no sudo needed)
    cp "$dump_file" "$temp_dump" 2>/dev/null || sudo cp "$dump_file" "$temp_dump"
    
    # Ensure we can delete it later
    if [[ $(stat -f '%u' "$temp_dump") -eq 0 ]]; then
        sudo chown $(whoami):admin "$temp_dump"
    fi
    
    # Transfer to dev server
    info "Transferring dump to dev server..."
    scp -q "$temp_dump" "$DEV_HOST:/tmp/redis-mac-sync.rdb"
    
    # Stop dev Redis, replace dump, restart
    ssh "$DEV_HOST" bash <<'EOF'
        sudo systemctl stop redis-server
        sudo cp /tmp/redis-mac-sync.rdb /var/lib/redis/dump.rdb
        sudo chown redis:redis /var/lib/redis/dump.rdb
        sudo systemctl start redis-server
        rm /tmp/redis-mac-sync.rdb
EOF
    
    # Cleanup
    rm "$temp_dump"
    
    # Update state file
    get_last_write_timestamp "local" "$LOCAL_REDIS_PORT" > "$STATE_FILE"
    
    success "Successfully pushed local changes to dev server"
}

# Pull from dev server to local
pull_from_dev() {
    info "Pulling Redis data from dev server..."
    
    # Create temporary dump from dev
    ssh "$DEV_HOST" "redis-cli -p $DEV_REDIS_PORT SAVE >/dev/null 2>&1"
    
    local temp_dump="/tmp/redis-pull-$(date +%s).rdb"
    
    # Download dev dump
    ssh "$DEV_HOST" "cat /var/lib/redis/dump.rdb" > "$temp_dump"
    
    # Stop local Redis, replace dump, restart
    redis-cli -p "$LOCAL_REDIS_PORT" SHUTDOWN NOSAVE 2>/dev/null || true
    sleep 2
    
    local redis_dir=$(redis-cli -p "$LOCAL_REDIS_PORT" CONFIG GET dir 2>/dev/null | tail -1 || echo "/usr/local/var/db/redis")
    sudo cp "$temp_dump" "$redis_dir/dump.rdb"
    sudo chown root:admin "$redis_dir/dump.rdb"
    
    # Start Redis (macOS)
    sudo brew services restart redis >/dev/null 2>&1 || sudo redis-server --daemonize yes
    
    # Wait for Redis to come up
    sleep 2
    
    # Cleanup
    rm -f "$temp_dump"
    
    # Update state file
    get_last_write_timestamp "local" "$LOCAL_REDIS_PORT" > "$STATE_FILE"
    
    success "Successfully pulled data from dev server"
}

# Setup replication
setup_replication() {
    info "Setting up Redis replication to dev server..."
    
    # Configure local Redis as replica via SSH tunnel
    # Note: Redis replication requires direct connection, so we'll use periodic sync instead
    warn "Redis native replication requires exposed ports. Using periodic sync mode."
}

# Main sync logic
sync_redis() {
    log "Starting Redis sync..."
    
    if check_connectivity; then
        info "Dev server is online"
        
        if has_local_changes; then
            warn "Local changes detected, pushing to dev server first..."
            push_to_dev
            sleep 5
            info "Now pulling latest from dev to ensure sync..."
            pull_from_dev
        else
            info "No local changes, pulling from dev..."
            pull_from_dev
        fi
        
        success "Sync completed successfully"
    else
        warn "Dev server is offline - running in standalone mode"
        log "Local Redis will continue accepting writes"
    fi
}

# Daemon mode
run_daemon() {
    info "Starting Redis sync daemon (interval: ${SYNC_INTERVAL}s)"
    
    while true; do
        sync_redis
        sleep "$SYNC_INTERVAL"
    done
}

# Main
main() {
    local mode="${1:-once}"
    
    # Create lock file to prevent concurrent runs
    if [[ -f "$LOCK_FILE" ]] && [[ "$mode" != "--force-push" ]] && [[ "$mode" != "--force-pull" ]]; then
        error "Sync already running (lock file exists: $LOCK_FILE)"
        exit 1
    fi
    
    touch "$LOCK_FILE"
    trap "rm -f $LOCK_FILE" EXIT
    
    case "$mode" in
        --daemon)
            run_daemon
            ;;
        --force-push)
            push_to_dev
            ;;
        --force-pull)
            pull_from_dev
            ;;
        once|*)
            sync_redis
            ;;
    esac
}

main "$@"
