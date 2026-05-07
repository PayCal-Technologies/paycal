#!/usr/bin/env bash
set -euo pipefail

REMOTE="origin"
BRANCH="main"
HOST="deploy@dev.paycal.app"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOCAL_PATH="${LOCAL_PATH:-$(cd "$SCRIPT_DIR/.." && pwd)}"
REMOTE_PATH="${REMOTE_PATH:-}"
SSH="/usr/bin/ssh"
GIT="/usr/bin/git"

usage() {
  echo "Usage:"
  echo "  $0                   # Deploy current main branch to dev"
  echo "  $0 -i                # Force Redis import after deploy"
  echo "  $0 -h                # Show this help"
  exit 1
}

force_redis_import=0
if [[ "$#" -gt 0 ]]; then
  case "$1" in
    -h|--help) usage ;;
    -i|--redis) force_redis_import=1 ;;
    *) usage ;;
  esac
fi

cd "$LOCAL_PATH"

changed_files=$($GIT diff --name-only "$REMOTE/$BRANCH...$BRANCH")
need_redis_import=0
if echo "$changed_files" | grep -qE '^(templates/|strings/)'; then
  need_redis_import=1
fi

echo "[→] Pushing local main branch..."
$GIT push $REMOTE $BRANCH

echo "[→] Deploying to dev..."

remote_cmd="
  set -e
  REMOTE_PATH=\"$REMOTE_PATH\"
  if [ -z \"\$REMOTE_PATH\" ]; then
    for candidate in /var/www/paycal-private /var/www/paycal/dev /var/www/paycal-mac/dev /private/var/www/paycal/dev; do
      if [ -d \"\$candidate\" ]; then
        REMOTE_PATH=\"\$candidate\"
        break
      fi
    done
  fi
  if [ -z \"\$REMOTE_PATH\" ]; then
    echo 'ERROR: remote deploy path not found (checked /var/www/paycal-private, /var/www/paycal/dev, /var/www/paycal-mac/dev, /private/var/www/paycal/dev)' >&2
    exit 1
  fi
  cd \"\$REMOTE_PATH\"
  $GIT pull $REMOTE $BRANCH
  mkdir -p logs
  chgrp www-data html/.env 2>/dev/null || true
  chmod 640 html/.env 2>/dev/null || true
  chgrp -R www-data logs 2>/dev/null || true
  chmod -R g+rwX logs 2>/dev/null || true
	sudo -n systemctl reload php8.4-fpm || true
	sudo -n systemctl reload nginx || true"

if [[ "$need_redis_import" -eq 1 || "$force_redis_import" -eq 1 ]]; then
  remote_cmd="$remote_cmd && ./scripts/redis-update.sh"
fi

$SSH -o StrictHostKeyChecking=yes $HOST "$remote_cmd && echo '✔ Deployed latest main to dev'"

exit $?
