#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="$(paycal_repo_root)"
cd "${repo_root}"

paycal_log "pre-commit" "Purging .DS_Store files from working tree"
bash "${repo_root}/scripts/remove-dsstore.sh"

paycal_log "pre-commit" "Scanning staged changes for secrets and sensitive local artifacts"
"${repo_root}/scripts/hooks/check-staged-sensitive.sh"

paycal_log "pre-commit" "Checking Composer validity and direct dependency freshness"
"${repo_root}/scripts/hooks/check-composer-state.sh"

staged_php_files="$(git diff --cached --name-only --diff-filter=ACM | grep -E '\\.php$' || true)"

if [[ -n "${staged_php_files}" ]]; then
  paycal_log "pre-commit" "Linting staged PHP files"
  while IFS= read -r file; do
    [[ -z "${file}" ]] && continue
    php -l "${file}" >/dev/null
  done <<< "${staged_php_files}"

  paycal_log "pre-commit" "Checking staged PHP files declare strict_types=1"
  strict_fail=0
  while IFS= read -r file; do
    [[ -z "${file}" ]] && continue
    if ! grep -q "declare(strict_types=1);" "${file}"; then
      paycal_log "fatal" "Missing declare(strict_types=1): ${file}"
      strict_fail=1
    fi
  done <<< "${staged_php_files}"
  if [[ "${strict_fail}" -eq 1 ]]; then exit 1; fi

  paycal_log "pre-commit" "Scanning staged PHP diff for semantic-looking rewrites"
  staged_php_paths_file="$(mktemp "${repo_root}/tmp/staged-php-semantic-diff.XXXXXX.txt")"
  trap 'rm -f "${staged_php_paths_file}"' EXIT
  printf "%s\n" "${staged_php_files}" > "${staged_php_paths_file}"
  php "${repo_root}/scripts/check-semantic-diff.php" --cached --paths-file "${staged_php_paths_file}"
fi

paycal_log "pre-commit" "Verifying PHPStan baseline policy"
if grep -q "baseline" "${repo_root}/phpstan.neon"; then
  paycal_log "fatal" "Baselines are not allowed (found baseline reference in phpstan.neon)"
  exit 1
fi

if [[ -f "${repo_root}/phpstan-baseline.neon" ]]; then
  paycal_log "fatal" "Baselines are not allowed (found phpstan-baseline.neon)"
  exit 1
fi

paycal_log "pre-commit" "Capturing AST graph metrics snapshot and deltas"
php "${repo_root}/scripts/ast/capture-ast-metrics.php" --source=pre-commit || true

if [[ -n "${staged_php_files}" ]]; then
  staged_php_files_in_html="$(echo "${staged_php_files}" | grep -E '^html/.*\.php$' || true)"
  staged_php_files_in_src="$(echo "${staged_php_files}" | grep -E '^html/src/.*\.php$' || true)"
  if [[ -n "${staged_php_files_in_html}" ]]; then
    paycal_log "pre-commit" "Running PHPStan Level 9 on staged PHP files"
    cd "${repo_root}"
    # shellcheck disable=SC2086
    vendor/bin/phpstan analyse --configuration=phpstan.neon --level=9 --memory-limit=1G --no-progress ${staged_php_files_in_html}
    cd "${repo_root}"
  fi
  if [[ -n "${staged_php_files_in_src}" ]]; then
    paycal_log "pre-commit" "Stage 1: compiling missing-docblock report for html/src"
    missing_docblocks_report="$(mktemp "${repo_root}/tmp/missing-docblocks.XXXXXX.json")"
    staged_src_paths_file="$(mktemp "${repo_root}/tmp/staged-src-docblocks.XXXXXX.txt")"
    printf "%s\n" "${staged_php_files_in_src}" > "${staged_src_paths_file}"
    php scripts/test/list-missing-method-docblocks.php --output "${missing_docblocks_report}" --paths-file "${staged_src_paths_file}"

    paycal_log "pre-commit" "Stage 2: applying generated docblocks for reported methods"
    changed_docblocks_paths_file="$(mktemp "${repo_root}/tmp/changed-docblocks.XXXXXX.txt")"
    php scripts/test/apply-missing-method-docblocks.php --input "${missing_docblocks_report}" --output-paths-file "${changed_docblocks_paths_file}"

    changed_docblock_files="$(cat "${changed_docblocks_paths_file}" 2>/dev/null || true)"
    if [[ -n "${changed_docblock_files}" ]]; then
      while IFS= read -r changed_file; do
        [[ -z "${changed_file}" ]] && continue
        git add "${changed_file}"
      done <<< "${changed_docblock_files}"
    fi

    paycal_log "pre-commit" "Running docblock quality checks for html/src"
    php scripts/test/check-missing-method-docblocks.php
    php scripts/test/check-duplicate-docblocks.php
  fi
fi

if [[ -n "${staged_php_files_in_html:-}" ]] && [[ -x "${repo_root}/vendor/bin/php-cs-fixer" ]]; then
  paycal_log "pre-commit" "Running safe formatter dry-run on staged HTML PHP files"
  while IFS= read -r file; do
    [[ -z "${file}" ]] && continue
    "${repo_root}/vendor/bin/php-cs-fixer" fix "${repo_root}/${file}" --config="${repo_root}/.php-cs-fixer.php" --dry-run --diff --using-cache=no >/dev/null
  done <<< "${staged_php_files_in_html}"
fi

paycal_log "pre-commit" "Running PayCal quick tests"
cd "${repo_root}"
composer run test:quick

paycal_log "pre-commit" "OK: lint + phpstan + quick tests passed"
