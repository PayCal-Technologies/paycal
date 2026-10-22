#!/usr/bin/env bash
# PayCal health monitor
# Purpose: detect outage/500 conditions and send one-time alert/recovery emails.
# Why here: repo-managed operational script for server cron execution.
# Usage: run manually or via cron every 5 minutes.

set -euo pipefail

RECIPIENT="${PAYCAL_HEALTH_ALERT_RECIPIENT:-cshaiku@gmail.com}"
STATE_FILE="/tmp/paycal-health-monitor.state"
HOSTNAME="$(hostname -f 2>/dev/null || hostname)"
NOW_UTC="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"

send_email() {
  local subject="$1"
  local body="$2"

  printf "%b\n" "$body" | "$PHP_BIN" "$SCRIPT_DIR/send-operational-email.php" \
    --to "$RECIPIENT" \
    --subject "$subject"
}

PROBE_REPORT="$(python3 "${SCRIPT_DIR}/paycal-http-probes.py")"
RESULTS=()
FAILURES=()
while IFS='|' read -r name url code result; do
  RESULTS+=("$name|$url|$code|$result")
  if [[ "$result" != "OK" && "$result" != "EXPECTED_MAINTENANCE" ]]; then
    FAILURES+=("$name $url -> $code $result")
  fi
done < <(printf '%s' "$PROBE_REPORT" | python3 -c 'import json,sys; [print("|".join(str(p[k]) for k in ("endpoint","url","http_code","result"))) for p in json.load(sys.stdin)["probes"]]')

if [[ "${1:-}" == "--dry-run" ]]; then
  printf '%s\n' "$PROBE_REPORT"
  exit 0
fi

CURRENT_STATE="ok"
if [[ ${#FAILURES[@]} -gt 0 ]]; then
  CURRENT_STATE="fail"
fi

PREV_STATE="unknown"
if [[ -f "$STATE_FILE" ]]; then
  PREV_STATE="$(cat "$STATE_FILE" 2>/dev/null || echo unknown)"
fi

echo "$CURRENT_STATE" > "$STATE_FILE"

if [[ "$CURRENT_STATE" == "fail" && "$PREV_STATE" != "fail" ]]; then
  BODY="PayCal health monitor detected server errors at $NOW_UTC on $HOSTNAME.\n\nFailures:\n"
  for failure in "${FAILURES[@]}"; do
    BODY+="- $failure\n"
  done

  BODY+="\nAll checks:\n"
  for result in "${RESULTS[@]}"; do
    BODY+="- $result\n"
  done

  send_email "[PayCal Alert] Outage/500 detected on $HOSTNAME" "$BODY"
fi

if [[ "$CURRENT_STATE" == "ok" && "$PREV_STATE" == "fail" ]]; then
  BODY="PayCal health monitor recovered at $NOW_UTC on $HOSTNAME.\n\nAll checks:\n"
  for result in "${RESULTS[@]}"; do
    BODY+="- $result\n"
  done

  send_email "[PayCal Recovery] Service restored on $HOSTNAME" "$BODY"
fi
