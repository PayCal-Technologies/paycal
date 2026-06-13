#!/usr/bin/env bash
set -euo pipefail

# Purpose: Explicitly sync README release docs with VERSION (no git staging).
# Usage:   bash scripts/hooks/fix-readme-version.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="${REPO_ROOT}"
cd "${repo_root}"

changed_paths=()
while IFS= read -r path; do
  [[ -z "${path}" ]] && continue
  changed_paths+=("${path}")
done < <("${repo_root}/scripts/hooks/sync-readme-version.sh")

if [[ ${#changed_paths[@]} -eq 0 ]]; then
  paycal_log "fix:readme-version" "README release docs already match VERSION"
else
  paycal_log "fix:readme-version" "Updated ${#changed_paths[@]} README file(s); review with git diff and stage manually"
fi

"${repo_root}/scripts/hooks/check-readme-version.sh"
