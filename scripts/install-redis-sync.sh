#!/usr/bin/env bash
#
# Install Redis Sync System
# Sets up automatic bidirectional sync between Mac and dev server
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLIST_FILE="$ROOT_DIR/scripts/local.paycal.redis-sync.plist"
PLIST_DEST="$HOME/Library/LaunchAgents/local.paycal.redis-sync.plist"

echo "========================================="
echo "Redis Sync Installation"
echo "========================================="
echo ""

# Create logs directory
echo "[1/5] Creating logs directory..."
mkdir -p "$ROOT_DIR/logs"
touch "$ROOT_DIR/logs/redis-sync.log"
touch "$ROOT_DIR/logs/redis-sync-php.log"
touch "$ROOT_DIR/logs/redis-sync-stdout.log"
touch "$ROOT_DIR/logs/redis-sync-stderr.log"

# Make sync script executable
echo "[2/5] Setting permissions..."
chmod +x "$ROOT_DIR/scripts/redis-sync.sh"

# Test connectivity to dev server
echo "[3/5] Testing connectivity to dev server..."
if ssh -o ConnectTimeout=5 paycal.app "exit" 2>/dev/null; then
    echo "✓ Dev server reachable"
else
    echo "✗ WARNING: Cannot reach dev server"
    echo "  Sync will run in standalone mode until connectivity is restored"
fi

# Install launchd plist (optional)
echo "[4/5] Install launchd daemon (runs every 5 minutes)?"
echo "  This will automatically sync Redis in the background."
read -p "Install? [y/N]: " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Copy plist
    cp "$PLIST_FILE" "$PLIST_DEST"
    
    # Load the agent
    launchctl unload "$PLIST_DEST" 2>/dev/null || true
    launchctl load "$PLIST_DEST"
    
    echo "✓ LaunchAgent installed and loaded"
    echo "  Status: launchctl list | grep redis-sync"
    echo "  Stop:   launchctl unload $PLIST_DEST"
    echo "  Start:  launchctl load $PLIST_DEST"
else
    echo "Skipped. You can install later by running:"
    echo "  cp $PLIST_FILE $PLIST_DEST"
    echo "  launchctl load $PLIST_DEST"
fi

# Test sync
echo "[5/5] Run initial sync test?"
read -p "Test sync now? [y/N]: " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Running sync..."
    "$ROOT_DIR/scripts/redis-sync.sh" once
    echo ""
fi

echo ""
echo "========================================="
echo "Installation Complete!"
echo "========================================="
echo ""
echo "Available Commands:"
echo "  Sync once:       $ROOT_DIR/scripts/redis-sync.sh"
echo "  Force push:      $ROOT_DIR/scripts/redis-sync.sh --force-push"
echo "  Force pull:      $ROOT_DIR/scripts/redis-sync.sh --force-pull"
echo "  Run as daemon:   $ROOT_DIR/scripts/redis-sync.sh --daemon"
echo ""
echo "PHP Endpoint:"
echo "  http://mac.paycal.local/api/redis-sync.php?action=sync"
echo "  http://mac.paycal.local/api/redis-sync.php?action=status"
echo ""
echo "Logs:"
echo "  Bash:   tail -f $ROOT_DIR/logs/redis-sync.log"
echo "  PHP:    tail -f $ROOT_DIR/logs/redis-sync-php.log"
echo "  Stdout: tail -f $ROOT_DIR/logs/redis-sync-stdout.log"
echo ""
