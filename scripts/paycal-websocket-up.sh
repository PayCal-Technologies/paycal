#!/usr/bin/env bash
set -euo pipefail

# Purpose:
# - Start the native PayCal WebSocket daemon used by exact /ws upgrade requests.
# Why this file exists here:
# - Keeps local operator workflow symmetric with native-up/native-down instead of
#   leaving a manual background process outside the repo-managed stack.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PID_FILE="$ROOT_DIR/tmp/paycal-websocket.pid"
LOG_FILE="$ROOT_DIR/logs/paycal-websocket.log"
PHP_BIN="${PAYCAL_PHP_BIN:-$(command -v php)}"

mkdir -p "$ROOT_DIR/tmp" "$ROOT_DIR/logs"

if [[ -f "$PID_FILE" ]]; then
  existing_pid="$(cat "$PID_FILE" 2>/dev/null || true)"
  if [[ -n "$existing_pid" ]] && kill -0 "$existing_pid" >/dev/null 2>&1; then
    printf 'PayCal WebSocket daemon already running (pid=%s)\n' "$existing_pid"
    exit 0
  fi
  rm -f "$PID_FILE"
fi

nohup "$PHP_BIN" "$ROOT_DIR/scripts/paycal-websocket-server.php" >>"$LOG_FILE" 2>&1 &
daemon_pid="$!"
printf '%s\n' "$daemon_pid" > "$PID_FILE"
printf 'Started PayCal WebSocket daemon (pid=%s)\n' "$daemon_pid"