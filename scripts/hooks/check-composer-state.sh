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

# Packages blocked from upgrading due to genuine upstream dependency conflicts.
# Remove an entry from this list as soon as the conflict is resolved upstream.
# phpunit/phpunit 13.2.0 requires sebastian/diff ^9 which conflicts with
# friendsofphp/php-cs-fixer (supports up to sebastian/diff ^8 as of 3.95.x).
OUTDATED_IGNORE=(
  "phpunit/phpunit"
)

outdated_output="$(composer outdated --direct --strict 2>&1)" || outdated_status=$?
outdated_status="${outdated_status:-0}"

if [[ "${outdated_status}" -ne 0 ]]; then
  # Filter out ignored packages from the outdated output.
  filtered_output="${outdated_output}"
  for pkg in "${OUTDATED_IGNORE[@]}"; do
    filtered_output="$(printf '%s\n' "${filtered_output}" | grep -v "^${pkg} " || true)"
  done
  # Keep only lines that look like actual package entries (vendor/package format).
  filtered_output="$(printf '%s\n' "${filtered_output}" | grep -E '^[a-z0-9_-]+/[a-z0-9_-]+' || true)"
  if [[ -n "${filtered_output}" ]]; then
    printf '%s\n' "${outdated_output}"
    paycal_log "fatal" "Composer direct dependencies are outdated; update composer.json/composer.lock before committing"
    exit 1
  fi
  if [[ ${#OUTDATED_IGNORE[@]} -gt 0 ]]; then
    paycal_log "pre-commit" "Skipped outdated check for conflict-blocked packages: ${OUTDATED_IGNORE[*]}"
  fi
fi
