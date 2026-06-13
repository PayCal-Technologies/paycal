#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"
source "${SCRIPT_DIR}/verified-head-lib.sh"

repo_root="$(paycal_repo_root)"
cd "${repo_root}"

paycal_stamp_verified_head "${repo_root}"
paycal_log "post-commit" "Recorded verified HEAD for quick-test skip on pre-push"
