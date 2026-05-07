#!/usr/bin/env bash
set -euo pipefail

# Purpose: block staged commits that accidentally include credentials, private keys,
#          or obviously sensitive local-only artifacts before they enter history.
# Usage: scripts/paycal checks:staged-sensitive
# Why here: this is hook-specific policy logic shared by the versioned git hooks.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="$(paycal_repo_root)"
cd "${repo_root}"

staged_files="$(git diff --cached --name-only --diff-filter=ACMR || true)"

if [[ -z "${staged_files}" ]]; then
  paycal_log "sensitive-check" "No staged files to scan"
  exit 0
fi

allowlisted_placeholder_regex='(REDACTED|redacted|placeholder|example|dummy|fake|changeme|your[_-]?|test[_-]?(key|token|secret|password)|<REDACTED[^>]*>|<[^>]*(SECRET|TOKEN|PASSWORD|KEY)[^>]*>)'
assignment_regex='(SECRET|TOKEN|PASSWORD|PASSWD|API[_-]?KEY|PRIVATE[_-]?KEY|ACCESS[_-]?KEY|CLIENT[_-]?SECRET|WEBHOOK[_-]?SECRET|REDIS_PASSWORD|STRIPE_SECRET|SMTP_PASSWORD)[A-Z0-9_ -]*[:=][[:space:]]*["'"'"']?[A-Za-z0-9+/_=.-]{8,}'
token_regex='(gh[pousr]_[A-Za-z0-9_]{20,}|github_pat_[A-Za-z0-9_]{20,}|AKIA[0-9A-Z]{16}|ASIA[0-9A-Z]{16}|sk_live_[0-9A-Za-z]{16,}|rk_live_[0-9A-Za-z]{16,}|xox[baprs]-[A-Za-z0-9-]{10,}|Bearer[[:space:]][A-Za-z0-9._=-]{20,})'
private_key_regex='-----BEGIN (OPENSSH |RSA |DSA |EC |PGP |ENCRYPTED )?PRIVATE KEY-----'

failures=()

is_blocked_path() {
  local file="$1"

  if [[ "${file}" == ".env.example" ]]; then
    return 1
  fi

  # SOC2 audit evidence artifacts (reports/, bundles/) are intentional commits
  # even when their paths contain segments like /logs/ (test-control output logs)
  [[ "${file}" =~ ^soc2/ ]] && return 1

  [[ "${file}" =~ (^|/)\.env($|\.) ]] && return 0
  [[ "${file}" =~ (^|/)\.envrc$ ]] && return 0
  [[ "${file}" =~ (^|/)\.DS_Store$ ]] && return 0
  [[ "${file}" =~ (^|/)dump\.rdb$ ]] && return 0
  [[ "${file}" =~ (^|/)tmp/ ]] && return 0
  [[ "${file}" =~ (^|/)logs/ ]] && return 0
  [[ "${file}" =~ (^|/)keys/ ]] && return 0
  [[ "${file}" =~ \.(pem|p12|pfx|key|crt|csr)$ ]] && return 0

  return 1
}

while IFS= read -r file; do
  [[ -z "${file}" ]] && continue

  if is_blocked_path "${file}"; then
    failures+=("blocked sensitive/local-only path: ${file}")
  fi

  added_lines="$(git diff --cached --no-color --unified=0 -- "${file}" | grep -E '^\+[^+]' || true)"
  [[ -z "${added_lines}" ]] && continue

  while IFS= read -r line; do
    [[ -z "${line}" ]] && continue

    if [[ "${line}" =~ ${private_key_regex} ]]; then
      failures+=("private key material detected in ${file}")
      break
    fi

    if [[ "${line}" =~ ${token_regex} ]]; then
      if ! printf '%s\n' "${line}" | grep -Eqi "${allowlisted_placeholder_regex}"; then
        failures+=("credential-like token detected in ${file}")
        break
      fi
    fi

    if [[ "${line}" =~ ${assignment_regex} ]]; then
      if ! printf '%s\n' "${line}" | grep -Eqi "${allowlisted_placeholder_regex}"; then
        failures+=("sensitive assignment detected in ${file}")
        break
      fi
    fi
  done <<< "${added_lines}"
done <<< "${staged_files}"

if (( ${#failures[@]} > 0 )); then
  paycal_log "fatal" "Sensitive content gate failed"
  for failure in "${failures[@]}"; do
    paycal_log "fatal" " - ${failure}"
  done
  paycal_log "fatal" "Review staged content and move private-only or secret material out of git before committing."
  exit 1
fi

paycal_log "sensitive-check" "OK: no blocked secrets or sensitive local artifacts detected in staged changes"