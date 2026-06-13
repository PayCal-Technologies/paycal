#!/usr/bin/env bash
set -euo pipefail

# Purpose: Interactive local pre-commit/pre-push workflow with yes/no at each step.
# Usage:
#   scripts/paycal workflow:local              # current repo only
#   scripts/paycal workflow:local --both         # private + public (when sibling exists)
#   scripts/paycal workflow:local --yes          # auto-accept all prompts (agents/CI)
#   PAYCAL_PUBLIC_ROOT=/path/to/paycal scripts/paycal workflow:local --both
#
# Run from an interactive terminal so [y/N] prompts work. Agents can use --yes.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

source "${SCRIPT_DIR}/lib/common.sh"
source "${SCRIPT_DIR}/lib/workflow-lib.sh"

SCOPE="current"
RUN_PUSH=0
RUN_COMMIT=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --both)
      SCOPE="both"
      ;;
    --current|--here)
      SCOPE="current"
      ;;
    --yes|-y)
      workflow_auto_yes=1
      ;;
    --push)
      RUN_PUSH=1
      ;;
    --commit)
      RUN_COMMIT=1
      ;;
    -h|--help)
      sed -n '3,12p' "$0"
      exit 0
      ;;
    *)
      paycal_log "fatal" "Unknown option: $1"
      exit 2
      ;;
  esac
  shift
done

declare -a REPO_ROOTS=()

add_repo_root() {
  local candidate="$1"
  [[ -d "${candidate}" ]] || return 1
  [[ -f "${candidate}/scripts/paycal" ]] || return 1

  local existing
  for existing in "${REPO_ROOTS[@]:-}"; do
    [[ "${existing}" == "${candidate}" ]] && return 0
  done

  REPO_ROOTS+=("${candidate}")
}

add_repo_root "${REPO_ROOT}"

if [[ "${SCOPE}" == "both" ]]; then
  sibling=""
  if sibling="$(workflow_default_sibling_root "${REPO_ROOT}")"; then
    add_repo_root "${sibling}" || true
  fi
  if [[ -n "${PAYCAL_PUBLIC_ROOT:-}" ]]; then
    add_repo_root "${PAYCAL_PUBLIC_ROOT}"
  fi
  if [[ -n "${PAYCAL_PRIVATE_ROOT:-}" ]]; then
    add_repo_root "${PAYCAL_PRIVATE_ROOT}"
  fi
fi

if [[ ${#REPO_ROOTS[@]} -eq 0 ]]; then
  paycal_log "fatal" "No PayCal repo roots resolved"
  exit 1
fi

workflow_section "PayCal local workflow"
workflow_note "Repos: ${REPO_ROOTS[*]}"
workflow_note "Answer y/yes to run a step; anything else skips it."
if [[ "${workflow_auto_yes}" == "1" ]]; then
  workflow_warn "AUTO-YES mode: all prompts accepted"
fi

run_repo_workflow() {
  local root="$1"
  local kind
  kind="$(workflow_repo_kind "${root}")"

  workflow_section "Repository: ${root} (${kind})"

  if ! workflow_confirm "Run workflow for ${kind} repo (${root})?"; then
    workflow_note "Skipped ${root}"
    return 0
  fi

  if ! workflow_hooks_installed "${root}"; then
    if workflow_confirm "Install git hooks in ${root}? (scripts/paycal hooks:install)"; then
      ( cd "${root}" && "${root}/scripts/paycal" hooks:install )
    elif ! workflow_confirm "Continue without installing hooks?"; then
      return 1
    fi
  else
    workflow_note "Git hooks look installed"
  fi

  workflow_git_summary "${root}" "${kind}"

  if workflow_confirm "Show full unstaged diff?"; then
    git -C "${root}" diff | "${PAGER:-less -R}"
  fi
  if workflow_confirm "Show full staged diff?"; then
    git -C "${root}" diff --cached | "${PAGER:-less -R}"
  fi

  if workflow_confirm "Try automatic fixes (README/VERSION, docblocks)?"; then
    if ! ( cd "${root}" && "${root}/scripts/paycal" checks:readme-version ); then
      workflow_warn "README/VERSION check failed"
      if workflow_confirm "Run scripts/paycal fix:readme-version?"; then
        ( cd "${root}" && "${root}/scripts/paycal" fix:readme-version )
        git -C "${root}" diff --stat
        if workflow_confirm "Stage README/VERSION fix files?"; then
          while IFS= read -r path; do
            [[ -z "${path}" ]] && continue
            git -C "${root}" add "${path}"
          done < <(git -C "${root}" diff --name-only)
          git -C "${root}" diff --cached --stat
        fi
      fi
    fi

    if ! ( cd "${root}" && php "${root}/scripts/test/check-missing-method-docblocks.php" >/dev/null 2>&1 ); then
      workflow_warn "Docblock check failed"
      if workflow_confirm "Run scripts/paycal fix:docblocks?"; then
        ( cd "${root}" && "${root}/scripts/paycal" fix:docblocks )
        git -C "${root}" diff --stat
        if workflow_confirm "Stage docblock fix files?"; then
          while IFS= read -r path; do
            [[ -z "${path}" ]] && continue
            git -C "${root}" add "${path}"
          done < <(git -C "${root}" diff --name-only -- html/src)
          git -C "${root}" diff --cached --stat
        fi
      fi
    fi
  fi

  if workflow_confirm "Run policy checks (composer, README, policy-meta, staged secrets)?"; then
    workflow_run_check "Composer state" bash -c "cd '${root}' && '${root}/scripts/paycal' checks:composer-state" || true
    workflow_run_check "README/VERSION" bash -c "cd '${root}' && '${root}/scripts/paycal' checks:readme-version" || true
    workflow_run_check "Policy meta" bash -c "cd '${root}' && '${root}/scripts/paycal' checks:policy-meta" || true
    if [[ -n "$(git -C "${root}" diff --cached --name-only)" ]]; then
      workflow_run_check "Staged secrets scan" bash -c "cd '${root}' && '${root}/scripts/paycal' checks:staged-sensitive" || true
    else
      workflow_note "Skipping staged secrets scan (nothing staged)"
    fi
  fi

  if [[ "${kind}" == "private" ]] && workflow_confirm "Run public promotion scope check (main...HEAD)?"; then
    workflow_run_check "Promotion scope" bash -c "cd '${root}' && '${root}/scripts/paycal' checks:public-promotion-scope main...HEAD" || true
  fi

  if workflow_confirm "Run PHPStan Level 9 (full tree)?"; then
    workflow_run_check "PHPStan L9" bash -c "cd '${root}' && vendor/bin/phpstan analyse --configuration=phpstan.neon --level=9 --memory-limit=1G --no-progress" || true
  fi

  if workflow_confirm "Run test:quick (PHPUnit unit, no slow/stress)?"; then
    if [[ "${kind}" == "public" ]]; then
      workflow_warn "Public repo may have known test:quick failures from moat/promotion drift"
    fi
    if ! workflow_run_check_optional_continue "test:quick" bash -c "cd '${root}' && composer run test:quick"; then
      return 1
    fi
  fi

  if workflow_confirm "Simulate pre-commit hook?"; then
    workflow_run_check_optional_continue "pre-commit hook" bash -c "cd '${root}' && '${root}/scripts/paycal' hooks:pre-commit" || true
  fi

  if workflow_confirm "Simulate pre-push hook? (uses origin as remote name)"; then
    local remote_url
    remote_url="$(git -C "${root}" config --get remote.origin.url 2>/dev/null || true)"
    workflow_run_check_optional_continue "pre-push hook" bash -c "cd '${root}' && '${root}/scripts/paycal' hooks:pre-push origin '${remote_url}'" || true
  fi

  if [[ "${RUN_COMMIT}" == "1" ]] || workflow_confirm "Create a git commit from staged changes?"; then
    if [[ -z "$(git -C "${root}" diff --cached --name-only)" ]]; then
      workflow_warn "Nothing staged; skipping commit"
    else
      git -C "${root}" diff --cached --stat
      local msg=""
      if [[ "${workflow_auto_yes}" != "1" ]]; then
        printf 'Commit subject (Enter to skip commit): '
        read -r msg </dev/tty 2>/dev/null || read -r msg
      fi
      if [[ -n "${msg}" ]]; then
        git -C "${root}" commit -m "${msg}"
        workflow_note "Committed in ${root}"
      else
        workflow_note "Commit skipped"
      fi
    fi
  fi

  if [[ "${RUN_PUSH}" == "1" ]] || workflow_confirm "Push ${kind} repo to origin?"; then
    local push_flags=()
    if [[ "${kind}" == "public" ]] && workflow_confirm "Use --no-verify for push? (only if hooks are known broken, e.g. public test debt)"; then
      push_flags=(--no-verify)
    fi
    git -C "${root}" push "${push_flags[@]}" origin HEAD
    workflow_note "Push finished for ${root}"
  fi

  return 0
}

for root in "${REPO_ROOTS[@]}"; do
  run_repo_workflow "${root}" || true
done

workflow_section "Done"
if [[ "${workflow_failures}" -gt 0 ]]; then
  workflow_warn "Completed with ${workflow_failures} check failure(s)"
  exit 1
fi

workflow_note "All selected checks passed"
exit 0
