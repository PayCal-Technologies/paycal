#!/usr/bin/env bash
set -euo pipefail

REMOTE="origin"
BRANCH="main"
HOST="paycal"
DEV_PAYCAL_DIR="/var/www/paycal-mac/dev"
REMOTE_SCRIPT="/var/www/paycal/scripts/sync.paycal.app.sh"
REMOTE_MODE=false
COMMIT_ARG=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --remote)
      REMOTE_MODE=true
      shift
      ;;
    --commit)
      COMMIT_ARG="${2:-}"
      shift 2
      ;;
    *)
      echo "ERROR: unknown argument $1"
      exit 1
      ;;
  esac
done

if $REMOTE_MODE; then
  if [[ -z "$COMMIT_ARG" ]]; then
    echo "ERROR: --commit required in remote mode"
    exit 1
  fi

  COMMIT="$COMMIT_ARG"
  cd "$DEV_PAYCAL_DIR"
  LOG_DIR="/var/www/paycal-mac/shared"
  LOG_FILE="$LOG_DIR/deploy.log"
  mkdir -p "$LOG_DIR"
  if ! git diff --quiet || ! git diff --cached --quiet; then
    msg="WARN: remote working tree not clean; skipping deploy"
    echo "$msg"
    printf "%s %s\n" "$(date -u +%FT%TZ)" "$msg" >> "$LOG_FILE"
    exit 0
  fi
  git fetch "$REMOTE"
  git checkout -q "$BRANCH"
  git reset --hard "$COMMIT"
  echo "DEPLOYED_COMMIT=$COMMIT" > .deploy-state
  echo "DEPLOYED_AT=$(date -u +%FT%TZ)" >> .deploy-state
  systemctl reload php8.4-fpm || php-fpm reload
  systemctl reload nginx
  echo "✔ Deployed $COMMIT to dev.paycal.app"
  exit 0
fi

cd "$DEV_PAYCAL_DIR"

# --- sanity: clean working tree ---
if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "ERROR: working tree not clean"
  exit 1
fi

# --- ensure we are on main ---
CURRENT_BRANCH="$(git symbolic-ref --short HEAD)"
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
  echo "ERROR: not on $BRANCH (on $CURRENT_BRANCH)"
  exit 1
fi

# --- sync state check ---
git fetch "$REMOTE"

LOCAL="$(git rev-parse $BRANCH)"
REMOTE_SHA="$(git rev-parse $REMOTE/$BRANCH)"
BASE="$(git merge-base $BRANCH $REMOTE/$BRANCH)"

if [ "$LOCAL" != "$REMOTE_SHA" ]; then
  if [ "$LOCAL" = "$BASE" ]; then
    echo "ERROR: local branch behind $REMOTE/$BRANCH"
    echo "Run: git rebase $REMOTE/$BRANCH"
    exit 1
  elif [ "$REMOTE_SHA" = "$BASE" ]; then
    echo "Local ahead of remote — pushing"
    git push "$REMOTE" "$BRANCH"
  else
    echo "ERROR: local and remote have diverged"
    echo "Run: git rebase $REMOTE/$BRANCH"
    exit 1
  fi
fi

# --- deploy exact commit ---
COMMIT="$(git rev-parse HEAD)"

ssh -T -o LogLevel=ERROR "$HOST" "$REMOTE_SCRIPT --remote --commit $COMMIT"
