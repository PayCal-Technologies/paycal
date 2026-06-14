#!/usr/bin/env bash
set -euo pipefail

# Purpose: Local policy meta-checks (mirrors CI policy intent without running GitHub Actions).
# Usage:   bash scripts/hooks/check-policy-meta.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="${REPO_ROOT}"
cd "${repo_root}"

failures=0

paycal_log "policy-meta" "Running repository policy meta-checks"

if [[ -f "${repo_root}/phpstan-baseline.neon" ]] || grep -q 'baseline' "${repo_root}/phpstan.neon" 2>/dev/null; then
  paycal_log "fatal" "PHPStan baselines are not allowed"
  failures=$((failures + 1))
fi

if [[ ! -x "${repo_root}/scripts/hooks/check-readme-version.sh" ]]; then
  paycal_log "fatal" "Missing scripts/hooks/check-readme-version.sh"
  failures=$((failures + 1))
else
  if ! "${repo_root}/scripts/hooks/check-readme-version.sh"; then
    failures=$((failures + 1))
  fi
fi

if [[ ! -f "${repo_root}/scripts/public-promotion-allowlist.txt" ]]; then
  paycal_log "fatal" "Missing scripts/public-promotion-allowlist.txt"
  failures=$((failures + 1))
fi

for hook in githooks/pre-commit githooks/pre-push scripts/hooks/pre-commit.sh scripts/hooks/pre-push.sh; do
  if [[ ! -f "${repo_root}/${hook}" ]]; then
    paycal_log "fatal" "Missing required hook file: ${hook}"
    failures=$((failures + 1))
    continue
  fi
  if ! bash -n "${repo_root}/${hook}"; then
    paycal_log "fatal" "Shell syntax error in ${hook}"
    failures=$((failures + 1))
  fi
done

if [[ -d "${repo_root}/.github/workflows" ]]; then
  yaml_parser=""
  if python3 -c "import yaml" 2>/dev/null; then
    yaml_parser="python3"
  fi

  while IFS= read -r workflow; do
    if [[ -n "${yaml_parser}" ]]; then
      if ! python3 -c "import yaml; yaml.safe_load(open('${workflow}'))" 2>/dev/null; then
        paycal_log "fatal" "Invalid workflow YAML: ${workflow}"
        failures=$((failures + 1))
      fi
    fi

    if grep -E 'uses:\s*[^@\s]+@[^0-9a-f]{40}' "${workflow}" >/dev/null 2>&1; then
      if grep -E 'uses:\s*[^@]+@v[0-9]' "${workflow}" >/dev/null 2>&1; then
        paycal_log "fatal" "Unpinned action ref (use full SHA): ${workflow}"
        failures=$((failures + 1))
      fi
    fi
  done < <(find "${repo_root}/.github/workflows" -name '*.yml' -type f | sort)

  if [[ -z "${yaml_parser}" ]]; then
    paycal_log "policy-meta" "Skipping workflow YAML parse (PyYAML unavailable)"
  fi
fi

if [[ -f "${repo_root}/scripts/test/check-test-repo-boundaries.php" ]]; then
  if ! php "${repo_root}/scripts/test/check-test-repo-boundaries.php"; then
    failures=$((failures + 1))
  fi
fi

if [[ "${failures}" -gt 0 ]]; then
  paycal_log "fatal" "Policy meta-check failed (${failures} issue(s))"
  exit 1
fi

paycal_log "policy-meta" "OK: policy meta-checks passed"
