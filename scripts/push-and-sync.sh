#!/usr/bin/env bash
# Push changes and automatically sync to dev environment
# Usage: ./scripts/push-and-sync.sh [git push arguments]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "[→] Pushing to remote..."
git push "$@"

echo ""
echo "[→] Running dev sync..."
bash "$SCRIPT_DIR/sync.dev.paycal.app.sh"

echo ""
echo "✔ Push and sync complete"
