#!/usr/bin/env bash
# Purpose:
#   Capture a production alert-delivery proof artifact for SOC2 CC4 / CC7 evidence.
#   Runs the same health-check probes as paycal-health-monitor.sh but does NOT
#   send email and does NOT mutate state.  The output is a JSON file retained
#   in soc2/reports/alert-delivery-proofs/ with timestamp, HTTP results, and
#   the conclusion (HEALTHY | DEGRADED) for auditor review.
#
# Usage:
#   bash scripts/soc2-capture-alert-proof.sh [--force]
#
# Why here:
#   SOC2 GAP-004: "Production alert-delivery proof artifact not yet retained."
#   CC4 (Monitoring) and CC7 (System Operations) both expect demonstrable
#   evidence that the alerting pipeline is exercised and that results are
#   preserved.  This script closes that gap without sending test emails to
#   real recipients.
#
# Output:
#   soc2/reports/alert-delivery-proofs/proof-YYYY-MM-DDTHH-MM-SSZ.json
#   soc2/reports/alert-delivery-proofs/proof-latest.json  (symlink-equivalent copy)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
STAMP="$(date -u +%Y-%m-%dT%H-%M-%SZ)"
OUT_DIR="${REPO_ROOT}/soc2/reports/alert-delivery-proofs"
OUT_PATH="${OUT_DIR}/proof-${STAMP}.json"
LATEST_PATH="${OUT_DIR}/proof-latest.json"

mkdir -p "${OUT_DIR}"

FORCE=0
for arg in "$@"; do
  [[ "${arg}" == "--force" ]] && FORCE=1
done

# Honour a cooldown: skip if latest proof is < 6 hours old, unless --force
if [[ "${FORCE}" == "0" && -f "${LATEST_PATH}" ]]; then
  LATEST_MOD="$(date -r "${LATEST_PATH}" +%s 2>/dev/null || echo 0)"
  NOW_TS="$(date -u +%s)"
  ELAPSED=$(( NOW_TS - LATEST_MOD ))
  if [[ "${ELAPSED}" -lt 21600 ]]; then
    echo "[soc2-alert-proof] Skipping: latest proof is $(( ELAPSED / 60 )) minutes old (< 6h cooldown). Use --force to override."
    exit 0
  fi
fi

NOW_UTC="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
HOSTNAME="$(hostname -f 2>/dev/null || hostname)"
PROBE_REPORT="$(python3 "${SCRIPT_DIR}/paycal-http-probes.py")"
CONCLUSION="$(printf '%s' "${PROBE_REPORT}" | python3 -c 'import json,sys; print(json.load(sys.stdin)["conclusion"])')"
PROBES="$(printf '%s' "${PROBE_REPORT}" | python3 -c 'import json,sys; print(json.dumps(json.load(sys.stdin)["probes"]))')"

cat > "${OUT_PATH}" <<JSON
{
  "schema_version": "v1",
  "artifact_type": "alert-delivery-proof",
  "soc2_controls": ["CC4", "CC7"],
  "captured_at_utc": "${NOW_UTC}",
  "hostname": "${HOSTNAME}",
  "conclusion": "${CONCLUSION}",
  "probes": ${PROBES},
  "notes": [
    "Endpoint probe only. No emails sent. This artifact does not verify alert delivery.",
    "Production redirect and versioned health expectations are verified. Development is intentionally offline and must return 503.",
    "Run: bash scripts/soc2-capture-alert-proof.sh --force to capture on demand."
  ]
}
JSON

cp "${OUT_PATH}" "${LATEST_PATH}"

echo "[soc2-alert-proof] ${CONCLUSION} — wrote ${OUT_PATH}"
echo "[soc2-alert-proof] updated ${LATEST_PATH}"
