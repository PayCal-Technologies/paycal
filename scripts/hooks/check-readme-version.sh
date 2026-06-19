#!/usr/bin/env bash
set -euo pipefail

# Purpose: Verify README release docs match VERSION (run after sync-readme-version.sh).
# Usage:   bash scripts/hooks/check-readme-version.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"
source "${SCRIPT_DIR}/readme-version-lib.sh"

repo_root="${REPO_ROOT}"
cd "${repo_root}"

version="$(readme_version_read_version "${repo_root}")"
version_tag="v${version}"

declare -a readme_paths=()
while IFS= read -r path; do
  [[ -z "${path}" ]] && continue
  readme_paths+=("${path}")
done < <(readme_version_manifest_paths "${repo_root}")

failures=0

check_latest_documented_release() {
  local readme="$1"

  if grep -qE "Latest documented release: \\*\\*${version_tag}\\*\\*" "${readme}"; then
    return 0
  fi

  paycal_log "fatal" "${readme}: expected 'Latest documented release: **${version_tag}**' to match VERSION"
  return 1
}

check_recent_release_blurb() {
  local readme="$1"

  if ! grep -qE "^## ${version_tag} " "${readme}"; then
    paycal_log "fatal" "${readme}: missing Recent Releases heading '## ${version_tag} (...)'"
    return 1
  fi

  local blurb_lines
  blurb_lines="$(
    awk -v ver="${version_tag}" '
      BEGIN { in_releases=0; in_section=0 }
      /^#{1,3} Recent Releases/ { in_releases=1 }
      !in_releases { next }
      in_releases && /^## v[0-9]+\.[0-9]+\.[0-9]+ / {
        if (in_section) { exit }
        if ($0 ~ "^## " ver " ") { in_section=1; next }
      }
      in_section && /^## v[0-9]+\.[0-9]+\.[0-9]+ / { exit }
      in_section { print }
    ' "${readme}"
  )"

  if printf '%s\n' "${blurb_lines}" | grep -qE '^\*\*Release Focus:\*\* .+|^- .+|^[A-Za-z0-9].{19,}'; then
    return 0
  fi

  paycal_log "fatal" "${readme}: '## ${version_tag}' must include a release blurb (**Release Focus:** line and/or bullet summary)"
  return 1
}

readme_inventory_config() {
  if [[ -f "${repo_root}/phpunit.public.xml" ]]; then
    printf '%s' 'phpunit.public.xml'
    return 0
  fi

  printf '%s' 'phpunit.xml'
}

count_test_files() {
  local path="$1"
  if [[ ! -d "${path}" ]]; then
    printf '0'
    return 0
  fi

  find "${path}" -name '*Test.php' -type f | wc -l | tr -d '[:space:]'
}

count_suite_files() {
  local config="$1"
  local suite="$2"

  vendor/bin/phpunit --configuration "${config}" --testsuite "${suite}" --list-tests \
    | sed -n '/^ - /p' \
    | awk -F'::' '{print $1}' \
    | sort -u \
    | wc -l \
    | tr -d '[:space:]'
}

format_inventory_number() {
  local value="$1"
  local out=""
  while [[ ${#value} -gt 3 ]]; do
    out=",${value: -3}${out}"
    value="${value:0:${#value}-3}"
  done

  printf '%s%s' "${value}" "${out}"
}

check_suite_inventory() {
  local readme="$1"
  local config
  config="$(readme_inventory_config)"

  if [[ ! -f "${repo_root}/${config}" ]]; then
    paycal_log "fatal" "${readme}: PHPUnit inventory config not found: ${config}"
    return 1
  fi

  local listed_tests
  listed_tests="$(vendor/bin/phpunit --configuration "${config}" --list-tests | sed -n '/^ - /p' | wc -l | tr -d '[:space:]')"
  local listed_tests_label
  listed_tests_label="$(format_inventory_number "${listed_tests}")"
  local unit_files integration_files contract_files soc2_files exploit_files manual_files total_files
  unit_files="$(count_test_files "${repo_root}/html/tests/Unit")"
  integration_files="$(count_test_files "${repo_root}/html/tests/Integration")"
  contract_files="$(count_test_files "${repo_root}/html/tests/Contract")"
  soc2_files="$(count_test_files "${repo_root}/html/tests/Soc2")"
  exploit_files="$(count_test_files "${repo_root}/html/tests/Exploits")"
  manual_files="$(count_test_files "${repo_root}/html/tests/Manual")"
  total_files=$((unit_files + integration_files + contract_files + soc2_files + exploit_files + manual_files))

  local suite_unit suite_integration suite_contract suite_soc2 suite_timezone suite_accessibility suite_exploits
  suite_unit="$(count_suite_files "${config}" "PayCal Unit")"
  suite_integration="$(count_suite_files "${config}" "PayCal Integration")"
  suite_contract="$(count_suite_files "${config}" "PayCal Contract")"
  suite_soc2="$(count_suite_files "${config}" "PayCal Soc2")"
  suite_timezone="$(count_suite_files "${config}" "PayCal Timezone")"
  suite_accessibility="$(count_suite_files "${config}" "PayCal Accessibility")"
  suite_exploits="$(count_suite_files "${config}" "PayCal Exploits")"

  local expected_badge="tests-${listed_tests}%20listed-blue"
  local expected_listed="- **${listed_tests_label} listed tests**"
  local expected_files="- **${total_files} repository test files**"
  local expected_categories
  if [[ "${config}" == "phpunit.public.xml" ]]; then
    expected_categories="- **Active public suite file split:** **${suite_unit} Unit**, **${suite_integration} Integration**, **${suite_contract} Contract**, **${suite_timezone} Timezone**, **${suite_accessibility} Accessibility**"
  else
    expected_categories="- **Active suite file split:** **${suite_unit} Unit**, **${suite_integration} Integration**, **${suite_contract} Contract**, **${suite_soc2} SOC2**, **${suite_timezone} Timezone**, **${suite_accessibility} Accessibility**, **${suite_exploits} Exploit**"
  fi
  local expected_config="via \`./vendor/bin/phpunit --configuration ${config} --list-tests\`"

  local ok=0
  grep -qF -- "${expected_badge}" "${readme}" || { paycal_log "fatal" "${readme}: stale test badge; expected ${expected_badge}"; ok=1; }
  grep -qF -- "${expected_listed}" "${readme}" || { paycal_log "fatal" "${readme}: stale listed test count; expected '${expected_listed}'"; ok=1; }
  grep -qF -- "${expected_files}" "${readme}" || { paycal_log "fatal" "${readme}: stale test file count; expected '${expected_files}'"; ok=1; }
  grep -qF -- "${expected_categories}" "${readme}" || { paycal_log "fatal" "${readme}: stale test category inventory; expected '${expected_categories}'"; ok=1; }
  grep -qF -- "${expected_config}" "${readme}" || { paycal_log "fatal" "${readme}: stale PHPUnit inventory command; expected ${expected_config}"; ok=1; }

  return "${ok}"
}

paycal_log "readme-version" "Checking README release docs against VERSION ${version_tag}"

for readme_rel in "${readme_paths[@]}"; do
  readme="${repo_root}/${readme_rel}"

  if [[ ! -f "${readme}" ]]; then
    paycal_log "fatal" "README not found: ${readme_rel}"
    failures=$((failures + 1))
    continue
  fi

  check_latest_documented_release "${readme}" || failures=$((failures + 1))
  check_recent_release_blurb "${readme}" || failures=$((failures + 1))
  check_suite_inventory "${readme}" || failures=$((failures + 1))
done

if [[ "${failures}" -gt 0 ]]; then
  paycal_log "fatal" "README version policy failed (${failures} issue(s)). Run: scripts/paycal fix:readme-version"
  exit 1
fi

paycal_log "readme-version" "OK: README release docs match VERSION ${version_tag}"
