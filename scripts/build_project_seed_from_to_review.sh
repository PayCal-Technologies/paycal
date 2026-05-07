#!/usr/bin/env bash
set -euo pipefail

SRC_DIR="/private/var/www/paycal/ai-notes/#to-review"
OUT_DIR="/private/var/www/paycal/ai-notes"
TS="$(date +%Y%m%d-%H%M%S)"
OUT_TSV="$OUT_DIR/project-seed-$TS.tsv"
OUT_MD="$OUT_DIR/project-seed-$TS.md"

printf 'source_file\ttitle\tbody\n' > "$OUT_TSV"

sanitize_title() {
  local raw="$1"
  raw="${raw%.md}"
  raw="$(printf '%s' "$raw" | sed -E 's/^[0-9]{8}-[0-9]{6}-//')"
  raw="$(printf '%s' "$raw" | sed -E 's/[_-]+/ /g')"
  raw="$(printf '%s' "$raw" | sed -E 's/[[:space:]]+/ /g; s/^ //; s/ $//')"
  printf '%s' "$raw"
}

extract_actions() {
  local f="$1"
  {
    grep -E '^- \[ \]|^\* \[ \]' "$f" || true
    grep -Ei 'todo|next steps|remaining|left to do|pending|follow-up|follow up' "$f" | head -n 8 || true
  } | sed -E 's/[\t\r]+/ /g' | sed -E 's/^\s+//; s/\s+$//' | awk 'NF' | head -n 10
}

count=0
while IFS= read -r file; do
  [[ -z "$file" ]] && continue
  base="$(basename "$file")"
  title="$(sanitize_title "$base")"

  action_lines="$(extract_actions "$file")"
  if [[ -n "$action_lines" ]]; then
    body=$(printf 'Source doc: `%s`\n\nPotential actionable items found in document:\n%s\n\nPlease validate and split into executable tasks.' "$base" "$(printf '%s\n' "$action_lines" | sed 's/^/- /')")
  else
    body=$(printf 'Source doc: `%s`\n\nNo explicit unchecked checklist items detected.\nReview this document and derive concrete tasks if still relevant.' "$base")
  fi

  safe_body="$(printf '%s' "$body" | tr '\n' ' ' | sed -E 's/[[:space:]]+/ /g')"
  printf '%s\t%s\t%s\n' "$file" "$title" "$safe_body" >> "$OUT_TSV"
  count=$((count + 1))
done < <(find "$SRC_DIR" -maxdepth 1 -type f -name '*.md' | LC_ALL=C sort)

cat > "$OUT_MD" <<EOF
# Project Seed Export

- Source: $SRC_DIR
- Generated: $TS
- Cards prepared: $count
- TSV: $(basename "$OUT_TSV")

Use this file to bulk-create GitHub Project draft cards once gh project scopes are enabled.
EOF

echo "TSV=$OUT_TSV"
echo "SUMMARY=$OUT_MD"
echo "COUNT=$count"
