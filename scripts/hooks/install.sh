#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="$(paycal_repo_root)"
hooks_src="${repo_root}/githooks"
hooks_dst="${repo_root}/.git/hooks"

install -m 0755 "${hooks_src}/pre-commit" "${hooks_dst}/pre-commit"
install -m 0755 "${hooks_src}/pre-push" "${hooks_dst}/pre-push"

paycal_log "ok" "Installed hooks:"
paycal_log "ok" " - ${hooks_dst}/pre-commit"
paycal_log "ok" " - ${hooks_dst}/pre-push"
