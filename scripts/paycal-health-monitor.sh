#!/usr/bin/env bash
# PayCal health monitor
# Purpose: detect outage/500 conditions and send one-time alert/recovery emails.
# Why here: repo-managed operational script for server cron execution.
# Usage: run manually or via cron every 5 minutes.

set -euo pipefail

RECIPIENT="cshaiku@gmail.com"
FROM_ADDR="alerts@paycal.app"
STATE_FILE="/tmp/paycal-health-monitor.state"
HOSTNAME="$(hostname -f 2>/dev/null || hostname)"
NOW_UTC="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"

check_url() {
  local name="$1"
  local url="$2"
  local code

  if ! code=$(curl -ksS --connect-timeout 8 --max-time 20 -o /dev/null -w "%{http_code}" "$url" 2>/dev/null); then
    echo "$name|$url|CURL_ERROR"
    return
  fi

  echo "$name|$url|$code"
}

send_email() {
  local subject="$1"
  local body="$2"

  {
    echo "From: $FROM_ADDR"
    echo "To: $RECIPIENT"
    echo "Subject: $subject"
    echo "Date: $(date -R)"
    echo
    printf "%b\n" "$body"
  } | /usr/bin/msmtp -t
}

RESULTS=(
  "$(check_url "prod-home" "https://paycal.app/")"
  "$(check_url "prod-css" "https://paycal.app/css/")"
  "$(check_url "prod-api-root" "https://paycal.app/api/")"
  "$(check_url "dev-home" "https://dev.paycal.app/")"
)

FAILURES=()
for item in "${RESULTS[@]}"; do
  IFS='|' read -r name url code <<< "$item"

  if [[ "$code" == "CURL_ERROR" ]]; then
    FAILURES+=("$name $url -> CURL_ERROR")
    continue
  fi

  if [[ "$code" =~ ^5 ]]; then
    FAILURES+=("$name $url -> HTTP $code")
  fi
done

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
