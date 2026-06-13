#!/usr/bin/env bash
set -euo pipefail

# Purpose: Ensure release README files document the current VERSION with a release blurb.
# Usage:   bash scripts/hooks/check-readme-version.sh
# Why here: Keeps README release highlights aligned with VERSION before commit/push.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

repo_root="${REPO_ROOT}"
cd "${repo_root}"

version_file="${repo_root}/VERSION"
if [[ ! -f "${version_file}" ]]; then
  paycal_log "fatal" "VERSION file not found at repo root"
  exit 1
fi

version="$(tr -d '[:space:]' < "${version_file}")"
if [[ ! "${version}" =~ ^[0-9]+\.[0-9]{3}\.[0-9]{3}$ ]]; then
  paycal_log "fatal" "VERSION must match x.yyy.zzz format; got: ${version}"
  exit 1
fi

version_tag="v${version}"

# Extend with additional paths (one per line) as needed.
manifest_file="${repo_root}/scripts/hooks/readme-version-manifest.txt"
readme_paths=()

if [[ -f "${manifest_file}" ]]; then
  while IFS= read -r line || [[ -n "${line}" ]]; do
    line="${line%%#*}"
    line="$(echo "${line}" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    [[ -z "${line}" ]] && continue
    readme_paths+=("${line}")
  done < "${manifest_file}"
fi

if [[ ${#readme_paths[@]} -eq 0 ]]; then
  readme_paths=(README.md)
fi

if [[ -n "${PAYCAL_README_VERSION_PATHS:-}" ]]; then
  IFS=':' read -r -a extra_paths <<< "${PAYCAL_README_VERSION_PATHS}"
  readme_paths+=("${extra_paths[@]}")
fi

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
      /^# Recent Releases/ { in_releases=1 }
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
done

if [[ "${failures}" -gt 0 ]]; then
  paycal_log "fatal" "README version policy failed (${failures} issue(s)). Update README release docs or run scripts/version-bump.sh for releases."
  exit 1
fi

paycal_log "readme-version" "OK: README release docs match VERSION ${version_tag}"
