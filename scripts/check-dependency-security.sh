#!/usr/bin/env bash
set -euo pipefail

# Purpose: daily dependency security audit for Composer (PHP) and npm (JS).
# Usage: scripts/paycal checks:dependency-security
#        composer run security:audit
# Why here: mirrors the security-gates CI workflow so the same checks can be
#   run locally or in pre-push to catch CVEs and version drift before they
#   reach GitHub. Complements dependabot (which raises PRs) by giving an
#   immediate pass/fail signal at the command line.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

cd "${REPO_ROOT}"

FAIL=0

# ── Composer: structural validity ────────────────────────────────────────────
paycal_log "security:audit" "Validating composer.json and composer.lock"
if ! composer validate --strict --no-check-publish 2>&1; then
  paycal_log "fatal" "composer validate failed"
  FAIL=1
fi

# ── Composer: CVE / advisory audit ───────────────────────────────────────────
paycal_log "security:audit" "Running composer audit (CVE/advisory check)"
if ! composer audit --locked --no-interaction 2>&1; then
  paycal_log "fatal" "composer audit found vulnerabilities"
  FAIL=1
fi

# ── Composer: version drift on direct deps ───────────────────────────────────
paycal_log "security:audit" "Checking for outdated direct Composer packages"
outdated_out="$(composer outdated --direct 2>&1)" || true
# composer outdated prints a header line even when nothing is outdated;
# actual outdated entries contain a version column (lines with two or more spaces
# between package name and version token, produced by the table formatter).
if echo "${outdated_out}" | grep -qE '^[a-z].*[0-9]+\.[0-9]+'; then
  printf '%s\n' "${outdated_out}"
  paycal_log "warn" "Direct Composer packages have available updates (see above)"
  # Warn only — outdated deps are not a hard block here (pre-commit hook blocks)
fi

# ── npm: CVE audit ───────────────────────────────────────────────────────────
if [[ -f "${REPO_ROOT}/package-lock.json" ]]; then
  if command -v npm >/dev/null 2>&1; then
    paycal_log "security:audit" "Running npm audit (CVE check, moderate+ severity)"
    if ! npm audit --audit-level=moderate --prefix "${REPO_ROOT}" 2>&1; then
      paycal_log "fatal" "npm audit found vulnerabilities at moderate or higher severity"
      FAIL=1
    fi
  else
    paycal_log "warn" "npm not found; skipping JS audit (run in CI or install Node)"
  fi
else
  paycal_log "security:audit" "No package-lock.json found; skipping npm audit"
fi

# ── Summary ──────────────────────────────────────────────────────────────────
if [[ "${FAIL}" -eq 0 ]]; then
  paycal_log "security:audit" "OK: all dependency security checks passed"
else
  paycal_log "fatal" "Dependency security audit FAILED — see errors above"
  exit 1
fi
