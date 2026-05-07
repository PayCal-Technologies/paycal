#!/usr/bin/env bash
set -euo pipefail

# Purpose: block commits when Composer metadata is invalid or direct dependencies are outdated.
# Usage: invoked from pre-commit or run manually from the repo root.
# Why here: keeps Composer policy centralized so git hooks and manual checks enforce the same rule.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="$(paycal_repo_root)"
cd "${repo_root}"

paycal_log "pre-commit" "Validating composer.json and composer.lock"
composer validate --strict >/dev/null

paycal_log "pre-commit" "Checking for direct Composer package updates"
outdated_output="$(composer outdated --direct --strict 2>&1)" || outdated_status=$?
outdated_status="${outdated_status:-0}"

if [[ "${outdated_status}" -ne 0 ]]; then
  if [[ -n "${outdated_output}" ]]; then
    printf '%s\n' "${outdated_output}"
  fi
  paycal_log "fatal" "Composer direct dependencies are outdated; update composer.json/composer.lock before committing"
  exit 1
fi