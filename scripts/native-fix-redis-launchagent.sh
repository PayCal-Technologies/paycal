#!/usr/bin/env bash
set -euo pipefail

# Purpose:
# - Repair local Redis launchd registration conflicts for native development.
# Why this file exists here:
# - Encodes safe cleanup/restart steps when stale system/user plists break Redis startup.

USER_PLIST="$HOME/Library/LaunchAgents/homebrew.mxcl.redis.plist"
SYSTEM_PLIST="/Library/LaunchDaemons/homebrew.mxcl.redis.plist"
REDIS_LOG="/opt/homebrew/var/log/redis.log"
REDIS_HOST="${PAYCAL_REDIS_HOST:-127.0.0.1}"
REDIS_PORT="${PAYCAL_REDIS_PORT:-6379}"
REDIS_USER="${PAYCAL_REDIS_USER:-paycal}"
REDIS_PASSWORD="${PAYCAL_REDIS_PASSWORD:-}"

say() {
  printf '%s\n' "$*"
}

warn() {
  printf 'WARN: %s\n' "$*" >&2
}

say "Inspecting Redis launchd state"
brew services list | grep '^redis' || true

if [[ -f "$SYSTEM_PLIST" ]]; then
  warn "Found system-level Redis plist: $SYSTEM_PLIST"
  if sudo -n true 2>/dev/null; then
    say "Removing stale system LaunchDaemon registration"
    sudo launchctl bootout system "$SYSTEM_PLIST" >/dev/null 2>&1 || true
    sudo rm -f "$SYSTEM_PLIST"
  else
    warn "Passwordless sudo unavailable. Run these commands manually to fully clean the stale system daemon:"
    printf '  sudo launchctl bootout system %q || true\n' "$SYSTEM_PLIST"
    printf '  sudo rm -f %q\n' "$SYSTEM_PLIST"
  fi
fi

if [[ -f "$USER_PLIST" ]]; then
  say "Resetting user-level Redis LaunchAgent"
  launchctl bootout "gui/$(id -u)" "$USER_PLIST" >/dev/null 2>&1 || true
fi

brew services stop redis >/dev/null 2>&1 || true
sleep 1
brew services start redis >/dev/null 2>&1 || true
sleep 2

say "Redis launchd summary:"
brew services list | grep '^redis' || true

if [[ -z "$REDIS_PASSWORD" ]]; then
  warn "PAYCAL_REDIS_PASSWORD is not set; skipping authenticated Redis PING"
elif redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" --user "$REDIS_USER" -a "$REDIS_PASSWORD" PING >/dev/null 2>&1; then
  say "Redis authenticated PING: OK"
else
  warn "Redis authenticated PING failed"
fi

if [[ -f "$REDIS_LOG" ]]; then
  say "Recent Redis log tail:"
  tail -20 "$REDIS_LOG" || true
fi
