#!/usr/bin/env bash
set -euo pipefail

# Purpose: Update README release headers/sections to match VERSION.
# Usage:   bash scripts/hooks/sync-readme-version.sh
# Output:  One changed repo-relative path per line (stdout).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"
source "${SCRIPT_DIR}/readme-version-lib.sh"

repo_root="${REPO_ROOT}"
cd "${repo_root}"

version="$(readme_version_read_version "${repo_root}")"
version_tag="v${version}"
release_date="${PAYCAL_RELEASE_DATE:-$(date +%Y-%m-%d)}"
release_focus="$(readme_version_default_focus)"

declare -a readme_paths=()
while IFS= read -r path; do
  [[ -z "${path}" ]] && continue
  readme_paths+=("${path}")
done < <(readme_version_manifest_paths "${repo_root}")

declare -a changed_paths=()

sync_readme_file() {
  local readme_rel="$1"
  local readme="${repo_root}/${readme_rel}"
  local modified=0

  if [[ ! -f "${readme}" ]]; then
    paycal_log "fatal" "README not found: ${readme_rel}"
    return 1
  fi

  local latest_line="Latest documented release: **${version_tag}**"
  if ! grep -qF "${latest_line}" "${readme}"; then
    if grep -qE '^Latest documented release:' "${readme}"; then
      sed -i '' "s/^Latest documented release:.*/${latest_line}/" "${readme}"
    else
      paycal_log "fatal" "${readme_rel}: missing 'Latest documented release:' line to update"
      return 1
    fi
    modified=1
  fi

  if ! grep -qE "^## ${version_tag} " "${readme}"; then
    local entry_file
    entry_file="$(mktemp "${repo_root}/tmp/readme-release-entry.XXXXXX")"
    cat > "${entry_file}" <<EOF
## ${version_tag} (${release_date})

**Release Focus:** ${release_focus}

- See \`docs/CHANGELOG.md\` and \`docs/v1.changelog.md\` for concise technical release notes.

EOF

    if grep -q '^# Recent Releases' "${readme}"; then
      awk -v entryfile="${entry_file}" '
        /^# Recent Releases/ {
          print
          print ""
          while ((getline line < entryfile) > 0) {
            print line
          }
          close(entryfile)
          next
        }
        { print }
      ' "${readme}" > "${readme}.tmp"
      mv "${readme}.tmp" "${readme}"
    else
      paycal_log "fatal" "${readme_rel}: missing '# Recent Releases' section"
      rm -f "${entry_file}"
      return 1
    fi

    rm -f "${entry_file}"
    modified=1
  fi

  if [[ "${modified}" -eq 1 ]]; then
    changed_paths+=("${readme_rel}")
    paycal_log "readme-version" "Updated ${readme_rel} for ${version_tag}"
  fi
}

paycal_log "readme-version" "Syncing README release docs with VERSION ${version_tag}"

for readme_rel in "${readme_paths[@]}"; do
  sync_readme_file "${readme_rel}"
done

if [[ ${#changed_paths[@]} -eq 0 ]]; then
  paycal_log "readme-version" "README release docs already match VERSION ${version_tag}"
else
  paycal_log "readme-version" "Synced ${#changed_paths[@]} README file(s)"
fi

if [[ ${#changed_paths[@]} -gt 0 ]]; then
  printf '%s\n' "${changed_paths[@]}"
fi
