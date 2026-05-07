#!/usr/bin/env bash

# Purpose: Recursively remove all .DS_Store files from the repository working tree.
# Usage: bash scripts/remove-dsstore.sh
# Why here: macOS Finder creates .DS_Store files in any directory it opens.
#           This script purges them so they cannot be accidentally staged.
#           It is also invoked automatically by scripts/hooks/pre-commit.sh.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

cd "${REPO_ROOT}"

# Recursively remove all .DS_Store files from current directory
find . -type f -name ".DS_Store" -print -delete
