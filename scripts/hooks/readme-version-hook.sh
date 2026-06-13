#!/usr/bin/env bash
set -euo pipefail

# Purpose: Verify README docs match VERSION (check-only; hooks do not mutate or commit).
# Usage:   bash scripts/hooks/readme-version-hook.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

source "${REPO_ROOT}/scripts/lib/common.sh"

exec "${REPO_ROOT}/scripts/hooks/check-readme-version.sh"
