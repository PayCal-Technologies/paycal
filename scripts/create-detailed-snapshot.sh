#!/usr/bin/env bash
# Purpose: Generate a complete, detailed repository and environment snapshot in Markdown.
# Usage:   bash scripts/create-detailed-snapshot.sh [output-markdown-path]
# Why here: Produces a deterministic handoff/debug artifact from the private repo without requiring external tooling.

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
STAMP="$(date -u +%Y-%m-%dT%H-%M-%SZ)"
DEFAULT_OUT_DIR="${REPO_ROOT}/tmp/snapshots"
DEFAULT_OUT_PATH="${DEFAULT_OUT_DIR}/snapshot-${STAMP}.md"
OUT_PATH="${1:-${DEFAULT_OUT_PATH}}"

mkdir -p "$(dirname "${OUT_PATH}")"

# Ensure we always write relative outputs from repository context.
cd "${REPO_ROOT}" || exit 1

append_line() {
  printf '%s\n' "$1" >> "${OUT_PATH}"
}

append_blank() {
  printf '\n' >> "${OUT_PATH}"
}

append_section() {
  append_line "## $1"
  append_blank
}

run_cmd_block() {
  local title="$1"
  local cmd="$2"

  append_line "### ${title}"
  append_blank
  append_line '```text'
  append_line "$ ${cmd}"

  set +e
  local output
  output="$(bash -lc "${cmd}" 2>&1)"
  local rc=$?
  set -e

  if [[ -n "${output}" ]]; then
    printf '%s\n' "${output}" >> "${OUT_PATH}"
  fi
  append_line "[exit:${rc}]"
  append_line '```'
  append_blank
}

sanitize_remotes() {
  git remote -v 2>/dev/null | sed -E 's#(https?://)[^/@]+@#\1***@#g'
}

snapshot_file_counts() {
  if command -v rg >/dev/null 2>&1; then
    rg --files | awk -F. '
      NF > 1 { ext=$NF; counts[tolower(ext)]++ }
      END {
        for (e in counts) {
          printf "%7d  .%s\\n", counts[e], e;
        }
      }
    ' | sort -nr | head -40
  else
    find . -type f | awk -F. '
      NF > 1 { ext=$NF; counts[tolower(ext)]++ }
      END {
        for (e in counts) {
          printf "%7d  .%s\\n", counts[e], e;
        }
      }
    ' | sort -nr | head -40
  fi
}

snapshot_key_files() {
  local paths=(
    "README.md"
    "composer.json"
    "package.json"
    "phpunit.xml"
    "phpstan.neon"
    "eslint.config.js"
    "scripts/paycal"
    "scripts/soc2-monthly-evidence-bundle.sh"
    "soc2/reports/soc2-control-map.json"
  )

  for p in "${paths[@]}"; do
    if [[ -e "${p}" ]]; then
      local mtime
      mtime="$(date -u -r "${p}" +%Y-%m-%dT%H:%M:%SZ 2>/dev/null || printf 'unknown')"
      printf 'FOUND  %s  (mtime=%s)\n' "${p}" "${mtime}"
    else
      printf 'MISSING %s\n' "${p}"
    fi
  done
}

snapshot_recent_soc2_outputs() {
  if [[ -d "soc2/bundles" ]]; then
    find soc2/bundles -maxdepth 3 -type f | sort | tail -40
  else
    echo "soc2/bundles directory not found"
  fi

  echo "---"

  if [[ -d "soc2/reports/freshness" ]]; then
    ls -1t soc2/reports/freshness | head -20
  else
    echo "soc2/reports/freshness directory not found"
  fi
}

: > "${OUT_PATH}"

append_line "# Detailed Snapshot"
append_blank
append_line "Generated at (UTC): ${STAMP}"
append_line "Repository root: ${REPO_ROOT}"
append_line "Output file: ${OUT_PATH}"
append_blank
append_line "This snapshot is intended for detailed debugging, handoff, and audit traceability."
append_blank

append_section "Host And Runtime"
run_cmd_block "Date" "date -u"
run_cmd_block "Uname" "uname -a"
run_cmd_block "Current User" "id"
run_cmd_block "Working Directory" "pwd"
run_cmd_block "Disk Usage" "df -h ."
run_cmd_block "Memory (macOS vm_stat)" "vm_stat | head -40"

append_section "Toolchain Versions"
run_cmd_block "Bash" "bash --version | head -2"
run_cmd_block "Git" "git --version"
run_cmd_block "PHP" "php -v | head -5"
run_cmd_block "Composer" "composer --version"
run_cmd_block "Node" "node --version"
run_cmd_block "Redis CLI" "redis-cli --version"

append_section "Git Repository State"
run_cmd_block "Git Root" "git rev-parse --show-toplevel"
run_cmd_block "Current Branch" "git branch --show-current"
run_cmd_block "Current HEAD" "git rev-parse HEAD"
run_cmd_block "Short Status" "git status --short --branch"
run_cmd_block "Changed Files (name-status)" "git diff --name-status"
run_cmd_block "Staged Files (name-status)" "git diff --cached --name-status"
run_cmd_block "Recent Commits" "git --no-pager log --oneline -25"

append_line "### Remotes (sanitized)"
append_blank
append_line '```text'
append_line "$ git remote -v (sanitized)"
{
  sanitize_remotes
  echo "[exit:$?]"
} >> "${OUT_PATH}"
append_line '```'
append_blank

append_section "Workspace Inventory"
run_cmd_block "Top-Level Listing" "ls -la"
append_line "### File Extension Counts (top 40)"
append_blank
append_line '```text'
append_line "$ rg --files | awk ..."
{
  snapshot_file_counts
  echo "[exit:$?]"
} >> "${OUT_PATH}"
append_line '```'
append_blank

append_line "### Key File Presence And Modification Times"
append_blank
append_line '```text'
append_line "$ key file presence check"
{
  snapshot_key_files
  echo "[exit:$?]"
} >> "${OUT_PATH}"
append_line '```'
append_blank

append_section "SOC2 Snapshot Details"
append_line "### Recent SOC2 Artifacts"
append_blank
append_line '```text'
append_line "$ recent soc2 outputs"
{
  snapshot_recent_soc2_outputs
  echo "[exit:$?]"
} >> "${OUT_PATH}"
append_line '```'
append_blank
run_cmd_block "Current Bundle Manifest (if present)" "if [[ -f soc2/bundles/2026-04/bundle.json ]]; then sed -n '1,220p' soc2/bundles/2026-04/bundle.json; else echo 'bundle manifest not found'; fi"
run_cmd_block "Current Auditor Index (if present)" "if [[ -f soc2/bundles/2026-04/auditor-index.json ]]; then sed -n '1,220p' soc2/bundles/2026-04/auditor-index.json; else echo 'auditor index not found'; fi"
run_cmd_block "Latest Freshness File Head" "latest=\$(ls -1t soc2/reports/freshness/soc2-evidence-freshness-*.json 2>/dev/null | head -1); if [[ -n \"\$latest\" ]]; then echo \"file=\$latest\"; sed -n '1,200p' \"\$latest\"; else echo 'no freshness files found'; fi"

append_section "Service Checks"
run_cmd_block "Brew Services (filtered)" "brew services list | grep -E 'php|nginx|redis|dnsmasq' || true"
run_cmd_block "Open Local Ports (common)" "for p in 80 443 6379 9000 8081; do echo \"== :\$p ==\"; lsof -nP -iTCP:\$p -sTCP:LISTEN || true; done"

append_section "Completion"
append_line "Snapshot generation completed at (UTC): $(date -u +%Y-%m-%dT%H:%M:%SZ)"
append_line "Generated by: scripts/create-detailed-snapshot.sh"
append_blank

echo "Snapshot written to ${OUT_PATH}"
