#!/usr/bin/env bash
set -euo pipefail

# Purpose: Sync README docs with VERSION and stage or commit changes for hooks.
# Usage:   bash scripts/hooks/readme-version-hook.sh <stage|commit>

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"
source "${SCRIPT_DIR}/readme-version-lib.sh"

mode="${1:-stage}"
if [[ "${mode}" != "stage" && "${mode}" != "commit" ]]; then
  paycal_log "fatal" "Usage: readme-version-hook.sh <stage|commit>"
  exit 1
fi

repo_root="${REPO_ROOT}"
cd "${repo_root}"

changed_paths=()
while IFS= read -r path; do
  [[ -z "${path}" ]] && continue
  changed_paths+=("${path}")
done < <("${repo_root}/scripts/hooks/sync-readme-version.sh")

if [[ ${#changed_paths[@]} -gt 0 ]]; then
  readme_version_stage_and_commit "${repo_root}" "${mode}" "${changed_paths[@]}"
fi

"${repo_root}/scripts/hooks/check-readme-version.sh"
