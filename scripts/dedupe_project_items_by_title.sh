#!/usr/bin/env bash
set -euo pipefail
OWNER="cshaiku"
PROJECT_NUMBER="2"

TMP_JSON="/tmp/paycal_items_dedupe.json"
gh project item-list "$PROJECT_NUMBER" --owner "$OWNER" --limit 500 --format json > "$TMP_JSON"

LOG="/private/var/www/paycal/ai-notes/project-dedupe-$(date +%Y%m%d-%H%M%S).tsv"
printf 'title\tkept_item_id\tdeleted_item_id\n' > "$LOG"

# Build title->list(ids) and delete all but first.
jq -r '.items[] | [.title,.id] | @tsv' "$TMP_JSON" | awk -F '\t' '
{
  title=$1; id=$2;
  if (!(title in keep)) {
    keep[title]=id;
  } else {
    print title"\t"keep[title]"\t"id;
  }
}' | while IFS=$'\t' read -r title kept delid; do
  gh project item-delete "$PROJECT_NUMBER" --owner "$OWNER" --id "$delid" >/dev/null
  printf '%s\t%s\t%s\n' "$title" "$kept" "$delid" >> "$LOG"
done

echo "LOG=$LOG"
echo "REMAINING=$(gh project item-list "$PROJECT_NUMBER" --owner "$OWNER" --limit 500 --format json | jq '.totalCount')"
