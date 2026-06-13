#!/usr/bin/env bash
# Shared helpers for scripts/local-workflow.sh

workflow_auto_yes="${PAYCAL_WORKFLOW_AUTO_YES:-0}"
workflow_failures=0

workflow_confirm() {
  local prompt="$1"

  if [[ "${workflow_auto_yes}" == "1" ]]; then
    paycal_log "workflow" "AUTO-YES: ${prompt}"
    return 0
  fi

  local answer=""
  if [[ -t 0 ]]; then
    printf '%s [y/N]: ' "${prompt}"
    read -r answer
  elif [[ -r /dev/tty ]]; then
    printf '%s [y/N]: ' "${prompt}" >/dev/tty
    read -r answer </dev/tty
  else
    paycal_log "workflow" "Non-interactive shell; treating as NO: ${prompt}"
    return 1
  fi

  [[ "${answer}" =~ ^[Yy]([Ee][Ss])?$ ]]
}

workflow_section() {
  printf '\n=== %s ===\n' "$1"
}

workflow_note() {
  paycal_log "workflow" "$*"
}

workflow_warn() {
  paycal_log "warn" "$*"
}

workflow_mark_failure() {
  workflow_failures=$((workflow_failures + 1))
}

workflow_run_check() {
  local title="$1"
  shift

  workflow_section "${title}"
  if "$@"; then
    workflow_note "PASS: ${title}"
    return 0
  fi

  workflow_warn "FAIL: ${title}"
  workflow_mark_failure
  return 1
}

workflow_run_check_optional_continue() {
  local title="$1"
  shift

  if workflow_run_check "${title}" "$@"; then
    return 0
  fi

  if workflow_confirm "Continue anyway after '${title}' failed?"; then
    workflow_warn "Continuing despite failure: ${title}"
    return 0
  fi

  return 1
}

workflow_repo_kind() {
  local root="$1"
  local remote_url=""

  remote_url="$(git -C "${root}" config --get remote.origin.url 2>/dev/null || true)"
  if [[ "${remote_url}" == *"paycal-private"* ]]; then
    printf 'private'
    return 0
  fi
  if [[ "${root}" == *"paycal-private"* ]]; then
    printf 'private'
    return 0
  fi
  if [[ "${remote_url}" == *"/paycal.git"* || "${remote_url}" == *"/paycal" ]]; then
    printf 'public'
    return 0
  fi
  if [[ "${root}" == */paycal && "${root}" != *"paycal-private"* ]]; then
    printf 'public'
    return 0
  fi

  printf 'unknown'
}

workflow_default_sibling_root() {
  local root="$1"
  local kind
  kind="$(workflow_repo_kind "${root}")"

  if [[ "${kind}" == "private" && -d "${root%/paycal-private}/paycal" ]]; then
    printf '%s/paycal' "${root%/paycal-private}"
    return 0
  fi

  if [[ "${kind}" == "public" && -d "${root%/paycal}/paycal-private" ]]; then
    printf '%s/paycal-private' "${root%/paycal}"
    return 0
  fi

  return 1
}

workflow_hooks_installed() {
  local root="$1"
  local missing=0

  for hook in pre-commit pre-push post-commit; do
    if [[ ! -x "${root}/.git/hooks/${hook}" ]]; then
      workflow_warn "Missing hook: ${root}/.git/hooks/${hook}"
      missing=$((missing + 1))
    fi
  done

  [[ "${missing}" -eq 0 ]]
}

workflow_git_summary() {
  local root="$1"
  local kind="$2"

  workflow_section "Git summary (${kind}) — ${root}"
  git -C "${root}" status -sb
  printf '\n'
  git -C "${root}" diff --stat
  printf '\n'
  git -C "${root}" diff --cached --stat
}
