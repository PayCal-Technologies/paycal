#!/usr/bin/env bash
set -euo pipefail

paycal_repo_root() {
  if command -v git >/dev/null 2>&1; then
    git rev-parse --show-toplevel 2>/dev/null || pwd
  else
    pwd
  fi
}

paycal_log() {
  local level="$1"
  shift
  printf '[%s] %s\n' "${level}" "$*"
}

paycal_require_cmd() {
  local cmd="$1"
  if ! command -v "${cmd}" >/dev/null 2>&1; then
    paycal_log "fatal" "required command not found: ${cmd}"
    return 1
  fi
}

paycal_run() {
  local title="$1"
  shift
  paycal_log "run" "${title}"
  "$@"
}
