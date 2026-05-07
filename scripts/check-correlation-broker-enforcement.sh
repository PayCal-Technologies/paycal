#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_GLOB="$ROOT_DIR/html/src/Controllers"

if ! command -v rg >/dev/null 2>&1; then
  echo "[broker-enforcement] ripgrep (rg) is required." >&2
  exit 2
fi

matches="$(rg -n "MetadataCorrelationPolicy" "$TARGET_GLOB" --glob '*.php' || true)"
if [[ -n "$matches" ]]; then
  echo "[broker-enforcement] Direct MetadataCorrelationPolicy usage found in controllers." >&2
  echo "$matches" >&2
  echo "[broker-enforcement] Use CorrelationBroker + CorrelationContext in controllers instead." >&2
  exit 1
fi

echo "[broker-enforcement] OK: controllers rely on broker-based correlation enforcement."
