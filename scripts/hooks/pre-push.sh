#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="$(paycal_repo_root)"
cd "${repo_root}"

should_run_public_scope_guard() {
	local remote_name="$1"
	local remote_url="$2"

	if [[ "${PAYCAL_ENFORCE_PUBLIC_SCOPE_GUARD:-0}" == "1" ]]; then
		return 0
	fi

	if [[ "${remote_name}" == "public" ]]; then
		return 0
	fi

	if [[ "${remote_url}" == *"paycal-public"* ]]; then
		return 0
	fi

	return 1
}

remote_name="${1:-}"
remote_url="${2:-}"

if should_run_public_scope_guard "${remote_name}" "${remote_url}"; then
	paycal_log "pre-push" "Running public promotion scope guard (${remote_name})"
	"${repo_root}/scripts/check-public-promotion-scope.sh" "main...HEAD"
else
	paycal_log "pre-push" "Skipping public promotion scope guard for remote: ${remote_name:-unknown}"
fi

paycal_log "pre-push" "Verifying PHPStan baseline policy"
if grep -q "baseline" "phpstan.neon"; then
	paycal_log "fatal" "Baselines are not allowed (found baseline reference in phpstan.neon)"
	exit 1
fi

if [[ -f "phpstan-baseline.neon" ]]; then
	paycal_log "fatal" "Baselines are not allowed (found phpstan-baseline.neon)"
	exit 1
fi

paycal_log "pre-push" "Running full PHPStan Level 9 verification"
vendor/bin/phpstan analyse --configuration=phpstan.neon --level=9 --memory-limit=1G --no-progress

paycal_log "pre-push" "Running PayCal quick tests"
composer run test:quick

paycal_log "pre-push" "OK: phpstan + quick tests passed"
