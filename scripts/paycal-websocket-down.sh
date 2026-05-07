#!/usr/bin/env bash
set -euo pipefail

# Purpose:
# - Stop the native PayCal WebSocket daemon started by paycal-websocket-up.sh.
# Why this file exists here:
# - Prevents orphaned local WebSocket processes during stack teardown.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PID_FILE="$ROOT_DIR/tmp/paycal-websocket.pid"

if [[ ! -f "$PID_FILE" ]]; then
  printf 'PayCal WebSocket daemon is not running.\n'
  exit 0
fi

daemon_pid="$(cat "$PID_FILE" 2>/dev/null || true)"
rm -f "$PID_FILE"

if [[ -n "$daemon_pid" ]] && kill -0 "$daemon_pid" >/dev/null 2>&1; then
  kill "$daemon_pid" >/dev/null 2>&1 || true
  printf 'Stopped PayCal WebSocket daemon (pid=%s)\n' "$daemon_pid"
  exit 0
fi

printf 'PayCal WebSocket daemon pid file was stale.\n'