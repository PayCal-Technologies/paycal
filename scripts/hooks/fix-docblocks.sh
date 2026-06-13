#!/usr/bin/env bash
set -euo pipefail

# Purpose: Apply missing method docblocks for html/src (explicit fixer; hooks do not mutate).
# Usage:   bash scripts/hooks/fix-docblocks.sh [paths-file]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="${REPO_ROOT}"
cd "${repo_root}"

rm -f "${repo_root}/tmp/fix-docblocks-paths."*.txt "${repo_root}/tmp/missing-docblocks."*.json "${repo_root}/tmp/changed-docblocks."*.txt 2>/dev/null || true

paths_file="${1:-}"
if [[ -z "${paths_file}" ]]; then
  paths_file="$(mktemp "${repo_root}/tmp/fix-docblocks-paths.XXXXXX.txt")"
  find html/src -name '*.php' -type f | sort > "${paths_file}"
fi

missing_docblocks_report="$(mktemp "${repo_root}/tmp/missing-docblocks.XXXXXX.json")"
changed_docblocks_paths_file="$(mktemp "${repo_root}/tmp/changed-docblocks.XXXXXX.txt")"

php scripts/test/list-missing-method-docblocks.php --output "${missing_docblocks_report}" --paths-file "${paths_file}"
php scripts/test/apply-missing-method-docblocks.php --input "${missing_docblocks_report}" --output-paths-file "${changed_docblocks_paths_file}"

changed_count=0
while IFS= read -r changed_file; do
  [[ -z "${changed_file}" ]] && continue
  changed_count=$((changed_count + 1))
  paycal_log "fix:docblocks" "Updated ${changed_file}"
done < "${changed_docblocks_paths_file}"

if [[ "${changed_count}" -eq 0 ]]; then
  paycal_log "fix:docblocks" "No docblock changes needed"
else
  paycal_log "fix:docblocks" "Applied docblocks to ${changed_count} file(s); review with git diff"
fi

php scripts/test/check-missing-method-docblocks.php
php scripts/test/check-duplicate-docblocks.php
