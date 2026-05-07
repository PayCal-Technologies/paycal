#!/usr/bin/env bash
set -euo pipefail

# PURPOSE:
#   Measure how many transparency-related keys in each locale differ from English.
#
# USAGE:
#   bash scripts/transparency/check_non_en_coverage.sh
#
# WHY THIS LIVES HERE:
#   This is a reusable localization audit utility for transparency work and should
#   live with other maintained repo scripts, not under tmp.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

keys_file=/tmp/transparency_i18n_keys_used_uniq.txt

if [[ ! -f "$keys_file" ]]; then
  for f in html/transparency/en.php html/transparency/*/en.php; do
    awk '/\$i18nKeys[[:space:]]*=[[:space:]]*\[/, /\];/ { while (match($0, /\x27[A-Z0-9_]+\x27/)) { key=substr($0, RSTART+1, RLENGTH-2); print key; $0=substr($0, RSTART+RLENGTH) } }' "$f"
    rg -No "Strings::i18n\('([A-Z0-9_]+)'\)" "$f" --replace '$1' || true
  done | sort -u > "$keys_file"
fi

for locale in de es fr hi it nl pt tl tr; do
  total=0
  diff=0

  while IFS= read -r key; do
    en_val=$(rg -N "^${key} " strings/en.txt | head -n1 | sed "s/^${key} //")
    loc_val=$(rg -N "^${key} " "strings/${locale}.txt" | head -n1 | sed "s/^${key} //")

    [[ -z "$en_val" ]] && continue
    [[ -z "$loc_val" ]] && loc_val="$en_val"

    total=$((total + 1))
    [[ "$loc_val" != "$en_val" ]] && diff=$((diff + 1))
  done < "$keys_file"

  pct=$(awk -v d="$diff" -v t="$total" 'BEGIN{ if (t == 0) print 0; else printf "%.2f", (d/t)*100 }')
  echo "$locale diff=$diff total=$total pct_non_en=$pct"
done
