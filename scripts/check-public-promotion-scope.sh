#!/usr/bin/env bash
# Purpose: Enforce that public-promotion candidate diffs do not include private SOC2 paths.
# Usage:   bash scripts/check-public-promotion-scope.sh [git-range]
# Why here: This repo is the private source of truth, so we enforce promotion hygiene before exporting patches.

set -euo pipefail

RANGE="${1:-main...HEAD}"

# Only inspect paths that are normally considered for public promotion.
CANDIDATES=()
while IFS= read -r path; do
  if [[ -n "${path}" ]]; then
    CANDIDATES+=("${path}")
  fi
done < <(git diff --name-only "${RANGE}" -- html/ docs/ strings/ .github/)

if [[ ${#CANDIDATES[@]} -eq 0 ]]; then
  echo "[promotion-scope] no candidate files under html/docs/strings/.github for range ${RANGE}"
  exit 0
fi

# Keep this list aligned with dual-repo promotion policy docs.
FORBIDDEN_REGEX='^(soc2/|html/admin/soc2/|html/css/admin/soc2/|html/src/Domain/Soc2Surface\.php$|html/extensions/overrides/soc2-surface/|\.github/workflows/|\.circleci/|\.azure-pipelines/|\.gitlab-ci\.yml$|Jenkinsfile$|\.git($|/))'

violations=()
for path in "${CANDIDATES[@]}"; do
  if [[ "${path}" =~ ${FORBIDDEN_REGEX} ]]; then
    violations+=("${path}")
  fi
done

if [[ ${#violations[@]} -gt 0 ]]; then
  echo "[promotion-scope] FAIL: private SOC2 paths detected in public-promotion candidate set:"
  printf ' - %s\n' "${violations[@]}"
  echo "[promotion-scope] Update your git diff pathspec exclusions before creating the public patch."
  exit 1
fi

echo "[promotion-scope] PASS: no private SOC2 paths found in candidate set (${RANGE})"
