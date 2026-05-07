#!/usr/bin/env bash
set -euo pipefail

MODE="block"
if [[ ${1:-} == "--mode=warn" ]]; then
  MODE="warn"
elif [[ ${1:-} == "--mode=block" ]]; then
  MODE="block"
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_EXAMPLE="$ROOT_DIR/.env.example"

if [[ ! -f "$ENV_EXAMPLE" ]]; then
  echo "ERROR: Missing $ENV_EXAMPLE"
  exit 2
fi

REQUIRED_KEYS=$(grep -E '^[A-Z0-9_]+=' "$ENV_EXAMPLE" | cut -d= -f1)

if [[ -z "$REQUIRED_KEYS" ]]; then
  echo "ERROR: No required keys found in $ENV_EXAMPLE"
  exit 2
fi

warn_count=0

check_env_file() {
  local label="$1"
  local env_file="$2"
  local missing=0

  if [[ ! -f "$env_file" ]]; then
    echo "WARN: $label missing $env_file"
    return 1
  fi

  for key in $REQUIRED_KEYS; do
    if ! grep -q "^${key}=" "$env_file"; then
      echo "WARN: $label missing key $key in $env_file"
      missing=1
    fi
  done

  return $missing
}

check_remote_env() {
  local host="$1"
  local label="$2"
  local env_file="$3"
  local key_list

  key_list=$(printf "%s " $REQUIRED_KEYS)

  ssh "$host" "bash -s" <<EOF
keys="$key_list"
file="$env_file"
label="$label"

if [[ ! -f "\$file" ]]; then
  echo "WARN: \$label missing \$file"
  exit 1
fi

missing=0
for key in \$keys; do
  if ! grep -q "^\${key}=" "\$file"; then
    echo "WARN: \$label missing key \$key in \$file"
    missing=1
  fi
done

exit \$missing
EOF
}

if check_env_file "mac" "$ROOT_DIR/html/.env"; then
  :
else
  warn_count=$((warn_count + 1))
fi

DEV_HOST="${DEV_HOST:-paycal}"
PROD_HOST="${PROD_HOST:-paycal}"
DEV_ENV_FILE="${DEV_ENV_FILE:-/var/www/paycal/dev/html/.env}"
PROD_ENV_FILE="${PROD_ENV_FILE:-/var/www/paycal/prod/html/.env}"

if check_remote_env "$DEV_HOST" "dev" "$DEV_ENV_FILE"; then
  :
else
  warn_count=$((warn_count + 1))
fi

if check_remote_env "$PROD_HOST" "prod" "$PROD_ENV_FILE"; then
  :
else
  warn_count=$((warn_count + 1))
fi

if [[ $warn_count -gt 0 ]]; then
  if [[ "$MODE" == "block" ]]; then
    echo "ERROR: Env checklist failed ($warn_count warning group(s))."
    exit 1
  fi
  echo "WARN: Env checklist completed with warnings ($warn_count group(s))."
fi

exit 0
