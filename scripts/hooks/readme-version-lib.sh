#!/usr/bin/env bash
# Shared helpers for README ↔ VERSION sync hooks.

readme_version_manifest_paths() {
  local repo_root="$1"
  local manifest_file="${repo_root}/scripts/hooks/readme-version-manifest.txt"
  local -a paths=()

  if [[ -f "${manifest_file}" ]]; then
    while IFS= read -r line || [[ -n "${line}" ]]; do
      line="${line%%#*}"
      line="$(echo "${line}" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
      [[ -z "${line}" ]] && continue
      paths+=("${line}")
    done < "${manifest_file}"
  fi

  if [[ ${#paths[@]} -eq 0 ]]; then
    paths=(README.md)
  fi

  if [[ -n "${PAYCAL_README_VERSION_PATHS:-}" ]]; then
    IFS=':' read -r -a extra_paths <<< "${PAYCAL_README_VERSION_PATHS}"
    paths+=("${extra_paths[@]}")
  fi

  printf '%s\n' "${paths[@]}"
}

readme_version_read_version() {
  local repo_root="$1"
  local version_file="${repo_root}/VERSION"

  if [[ ! -f "${version_file}" ]]; then
    paycal_log "fatal" "VERSION file not found at repo root"
    return 1
  fi

  local version
  version="$(tr -d '[:space:]' < "${version_file}")"
  if [[ ! "${version}" =~ ^[0-9]+\.[0-9]{3}\.[0-9]{3}$ ]]; then
    paycal_log "fatal" "VERSION must match x.yyy.zzz format; got: ${version}"
    return 1
  fi

  printf '%s' "${version}"
}

readme_version_default_focus() {
  if [[ -n "${PAYCAL_RELEASE_FOCUS:-}" ]]; then
    printf '%s' "${PAYCAL_RELEASE_FOCUS}"
    return 0
  fi

  local subject=""
  if subject="$(git log -1 --format=%s 2>/dev/null)"; then
    subject="$(echo "${subject}" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    if [[ -n "${subject}" && ! "${subject}" =~ ^docs:\ sync\ README ]]; then
      printf '%s' "${subject}"
      return 0
    fi
  fi

  printf '%s' 'Maintenance and documentation updates'
}

readme_version_stage_and_commit() {
  local repo_root="$1"
  local mode="$2" # stage | commit
  shift 2
  local -a changed_paths=("$@")

  if [[ ${#changed_paths[@]} -eq 0 ]]; then
    return 0
  fi

  cd "${repo_root}"

  local path
  for path in "${changed_paths[@]}"; do
    [[ -z "${path}" ]] && continue
    if [[ -f "${path}" ]]; then
      git add "${path}"
    fi
  done

  while IFS= read -r manifest_path; do
    [[ -z "${manifest_path}" ]] && continue
    if [[ -f "${manifest_path}" ]] && ! git diff --quiet -- "${manifest_path}" 2>/dev/null; then
      git add "${manifest_path}"
    fi
  done < <(readme_version_manifest_paths "${repo_root}")

  if [[ -f VERSION ]] && ! git diff --quiet -- VERSION 2>/dev/null; then
    git add VERSION
  fi

  if [[ "${mode}" != "commit" ]]; then
    return 0
  fi

  if git diff --cached --quiet; then
    return 0
  fi

  git commit -m "$(cat <<'EOF'
docs: sync README release docs with VERSION

Automated README release header and Recent Releases sync from VERSION.
EOF
)"
  paycal_log "readme-version" "Committed README release doc sync"
}
