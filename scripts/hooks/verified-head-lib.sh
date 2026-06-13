#!/usr/bin/env bash
# Shared helpers for skipping duplicate quick-test runs when HEAD is already verified.

paycal_verified_head_file() {
  local repo_root="$1"
  printf '%s/tmp/.paycal-verified-head' "${repo_root}"
}

paycal_stamp_verified_head() {
  local repo_root="$1"
  local stamp_file
  stamp_file="$(paycal_verified_head_file "${repo_root}")"
  mkdir -p "${repo_root}/tmp"
  git -C "${repo_root}" rev-parse HEAD > "${stamp_file}"
}

paycal_head_has_verified_quick_tests() {
  local repo_root="$1"
  local stamp_file current_head stamped_head

  stamp_file="$(paycal_verified_head_file "${repo_root}")"
  if [[ ! -f "${stamp_file}" ]]; then
    return 1
  fi

  stamped_head="$(tr -d '[:space:]' < "${stamp_file}")"
  current_head="$(git -C "${repo_root}" rev-parse HEAD)"
  [[ "${stamped_head}" == "${current_head}" ]]
}
