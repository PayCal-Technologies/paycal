#!/usr/bin/env bash
# set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOOK_DIR="$ROOT_DIR/.git/hooks"

mkdir -p "$HOOK_DIR"
#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOOK_DIR="$ROOT_DIR/.git/hooks"

mkdir -p "$HOOK_DIR"

cat > "$HOOK_DIR/pre-push" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"
"$ROOT_DIR/scripts/check-envs.sh" --mode=block
EOF

chmod +x "$HOOK_DIR/pre-push"

echo "Installed pre-push hook at $HOOK_DIR/pre-push"
