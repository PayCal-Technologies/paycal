#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

# Legacy tokens targeted for removal after component migration.
LEGACY_TOKENS=(
  --body-back
  --body-fore
  --panel-back
  --panel-fore
  --panel-border-color
  --btn-back
  --btn-fore
  --btn-border-colors
  --btn-border-colors-active
  --btn-primary-back
  --btn-primary-fore
  --btn-secondary-back
  --btn-secondary-fore
  --chrome-back
  --chrome-fore
  --header-back
  --header-fore
  --footer-back
  --footer-fore
  --spot-back
  --spot-fore
  --context-menu-back
  --context-menu-fore
  --warning
  --cal-day-back
  --cal-day-hover-back
  --cal-day-border
)

# Search scope intentionally excludes theme source files where aliases are expected.
SEARCH_GLOB=(
  html/css/common/index.php
  html/css/calendar/index.php
  html/css/navigation/index.php
  html/css/settings/index.php
  html/css/datagrid/index.php
  html/css/earnings/index.php
  html/css/utilities/index.php
  html/css/auth/index.php
  html/css/admin/index.php
  html/css/admin/redis/index.php
  html/css/sites/index.php
  html/css/transparency/index.php
  html/css/help/index.php
  html/css/phantomwing/index.php
)

echo "Legacy theme token usage report"
echo "Generated: $(date '+%Y-%m-%d %H:%M:%S')"
echo

printf '%-30s %8s\n' "TOKEN" "COUNT"
printf '%-30s %8s\n' "-----" "-----"

for token in "${LEGACY_TOKENS[@]}"; do
  count="$({ rg --glob "!html/css/*_dark/index.php" --glob "!html/css/*_light/index.php" -o -- "$token" "${SEARCH_GLOB[@]}" || true; } | wc -l | tr -d ' ')"
  printf '%-30s %8s\n' "$token" "$count"
done

echo
echo "Detailed occurrences:"
for token in "${LEGACY_TOKENS[@]}"; do
  echo
  echo "## $token"
  rg -n --glob "!html/css/*_dark/index.php" --glob "!html/css/*_light/index.php" -- "$token" "${SEARCH_GLOB[@]}" || true
done
