#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_DIR="${1:-${ROOT_DIR}/html}"
SKIP_PHP_LINT="${SKIP_PHP_LINT:-0}"
SKIP_PHPSTAN="${SKIP_PHPSTAN:-0}"

if [[ ! -d "${TARGET_DIR}" ]]; then
  echo "[fatal] target directory not found: ${TARGET_DIR}" >&2
  exit 2
fi

EXIT_CODE=0

print_section() {
  local title="$1"
  printf "\n== %s ==\n" "${title}"
}

run_check() {
  local title="$1"
  local pattern="$2"
  local use_pcre="${3:-0}"

  print_section "${title}"

  set +e
  if [[ "${use_pcre}" == "1" ]]; then
    rg -n -P "${pattern}" "${TARGET_DIR}" --glob '!**/vendor/**' --glob '!**/.tmp/**'
  else
    rg -n "${pattern}" "${TARGET_DIR}" --glob '!**/vendor/**' --glob '!**/.tmp/**'
  fi
  local rc=$?
  set -e

  if [[ ${rc} -eq 0 ]]; then
    echo "[warn] ${title}: potential issues found"
    EXIT_CODE=1
  elif [[ ${rc} -eq 1 ]]; then
    echo "[ok] ${title}"
  else
    echo "[error] ${title}: check failed (exit ${rc})"
    EXIT_CODE=1
  fi
}

print_section "Context"
echo "Root:   ${ROOT_DIR}"
echo "Target: ${TARGET_DIR}"
echo "Skip PHP Lint: ${SKIP_PHP_LINT}"
echo "Skip PHPStan:  ${SKIP_PHPSTAN}"

# 1) PHP syntax lint over project PHP files (excluding vendor).
print_section "PHP Syntax Lint"
if [[ "${SKIP_PHP_LINT}" == "1" ]]; then
  echo "[skip] SKIP_PHP_LINT=1"
else
  set +e
  PHP_FILES=$(find "${TARGET_DIR}" -type f -name '*.php' ! -path '*/vendor/*' ! -path '*/.tmp/*')
  set -e
  if [[ -z "${PHP_FILES}" ]]; then
    echo "[warn] no PHP files found in target"
  else
    while IFS= read -r php_file; do
      php -l "${php_file}" >/dev/null 2>&1 || {
        echo "[fail] php -l: ${php_file}"
        EXIT_CODE=1
      }
    done <<< "${PHP_FILES}"
    if [[ ${EXIT_CODE} -eq 0 ]]; then
      echo "[ok] php -l passed"
    fi
  fi
fi

# 2) Edge case: legacy URL concatenation patterns.
run_check \
  "Legacy app URL concatenation" \
  "appPublicURL\\(\\)\\s*\\.\\s*['\\\"]|appBaseURL\\(\\)\\s*\\.\\s*['\\\"]|rtrim\\([^\\n]*appPublicURL\\("

# 3) Edge case: SITE constant used in URL contexts.
run_check \
  "SITE constant in URL contexts" \
  "(href|src|action|Location:|fetch\\(|import\\s+.*from\\s+['\\\"]).*SITE"

# 4) Edge case: template output immediately followed by path text without separator.
run_check \
  "Template output missing separator" \
  "(href|src|action)=['\\\"]<\\?php\\s+echo\\s+[^;]+;\\s*\\?>[^'\\\"/?#&][^'\\\"]*['\\\"]"

# 5) Edge case: suspicious local double slashes in URL attributes.
run_check \
  "Suspicious double slash in attributes" \
  "(href|src|action)=['\\\"](?!https?://|data:|//)[^'\\\"]*//[^'\\\"]*['\\\"]" \
  1

# 6) Optional phpstan run if present.
print_section "PHPStan (optional)"
if [[ "${SKIP_PHPSTAN}" == "1" ]]; then
  echo "[skip] SKIP_PHPSTAN=1"
elif [[ -x "${ROOT_DIR}/vendor/bin/phpstan" && -f "${ROOT_DIR}/phpstan.neon" ]]; then
  set +e
  (cd "${ROOT_DIR}" && ./vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress --error-format=table) \
    || EXIT_CODE=1
  set -e
else
  echo "[skip] phpstan not available in expected location"
fi

print_section "Result"
if [[ ${EXIT_CODE} -eq 0 ]]; then
  echo "[ok] no edge-case diagnostics failures detected"
else
  echo "[fail] edge-case diagnostics found problems"
fi

exit ${EXIT_CODE}
