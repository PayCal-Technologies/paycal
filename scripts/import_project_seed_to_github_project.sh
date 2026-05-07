#!/usr/bin/env bash
set -euo pipefail

OWNER="cshaiku"
PROJECT_NUMBER="2"
PROJECT_ID="PVT_kwHOAV7DQM4BSPjO"
SEED_TSV="/private/var/www/paycal/ai-notes/project-seed-20260319-120726.tsv"
LOG="/private/var/www/paycal/ai-notes/project-import-$(date +%Y%m%d-%H%M%S).tsv"

if [[ ! -f "$SEED_TSV" ]]; then
  echo "Seed file not found: $SEED_TSV" >&2
  exit 1
fi

FIELDS_JSON="$(gh project field-list "$PROJECT_NUMBER" --owner "$OWNER" --format json)"

get_field_id() {
  local name="$1"
  jq -r --arg n "$name" '.fields[] | select(.name==$n) | .id' <<< "$FIELDS_JSON"
}

get_option_id() {
  local field="$1"
  local option="$2"
  jq -r --arg f "$field" --arg o "$option" '.fields[] | select(.name==$f) | .options[]? | select(.name==$o) | .id' <<< "$FIELDS_JSON"
}

FLOW_FIELD_ID="$(get_field_id "Flow")"
AREA_FIELD_ID="$(get_field_id "Area")"
TYPE_FIELD_ID="$(get_field_id "Type")"
PRIORITY_FIELD_ID="$(get_field_id "Priority")"
EFFORT_FIELD_ID="$(get_field_id "Effort")"
RISK_FIELD_ID="$(get_field_id "Risk")"
SOURCE_FIELD_ID="$(get_field_id "Source")"
CONFIDENCE_FIELD_ID="$(get_field_id "Confidence")"

FLOW_INBOX_ID="$(get_option_id "Flow" "Inbox")"

area_from_text() {
  local t="$1"
  if grep -Eiq 'auth|passkey|recovery|login|session|credential' <<< "$t"; then echo "Auth"; return; fi
  if grep -Eiq 'redis|cache|freeze|breaker|tier0|evict' <<< "$t"; then echo "Redis"; return; fi
  if grep -Eiq 'payroll|tax|earning' <<< "$t"; then echo "Payroll Engine"; return; fi
  if grep -Eiq 'export|pdf|csv|renderer|report' <<< "$t"; then echo "Exports"; return; fi
  if grep -Eiq 'ui|ux|modal|sidebar|layout|css|frontend|theme|design' <<< "$t"; then echo "UI"; return; fi
  if grep -Eiq 'api|endpoint|route|controller|http' <<< "$t"; then echo "API"; return; fi
  if grep -Eiq 'security|crypto|encryption|audit|csp|rate limit|vuln|hardening' <<< "$t"; then echo "Security"; return; fi
  if grep -Eiq 'billing|invoice|subscription|payment' <<< "$t"; then echo "Billing"; return; fi
  if grep -Eiq 'analytics|metric|dashboard|observability' <<< "$t"; then echo "Analytics"; return; fi
  echo "Infra"
}

type_from_text() {
  local t="$1"
  if grep -Eiq 'security|vuln|csp|encryption|auth hardening|audit' <<< "$t"; then echo "Security"; return; fi
  if grep -Eiq 'bug|fix|regression|error|failure|broken' <<< "$t"; then echo "Bug"; return; fi
  if grep -Eiq 'refactor|cleanup|migrate|migration|rewrite|debt' <<< "$t"; then echo "Refactor"; return; fi
  if grep -Eiq 'research|analysis|investigate|spike|explore' <<< "$t"; then echo "Research"; return; fi
  if grep -Eiq 'implement|build|add|create|feature' <<< "$t"; then echo "Feature"; return; fi
  echo "Tech Debt"
}

priority_from_text() {
  local t="$1"
  if grep -Eiq 'critical|p0|blocker|sev0|sev1' <<< "$t"; then echo "P0"; return; fi
  if grep -Eiq 'security|auth|redis|data loss|incident' <<< "$t"; then echo "P1"; return; fi
  if grep -Eiq 'roadmap|enhancement|improve|optimization' <<< "$t"; then echo "P2"; return; fi
  echo "P3"
}

effort_from_text() {
  local t="$1"
  if grep -Eiq 'quick reference|readme|docs|copy edit' <<< "$t"; then echo "XS"; return; fi
  if grep -Eiq 'small|minor|single file' <<< "$t"; then echo "S"; return; fi
  if grep -Eiq 'plan|integration|controller|service|test' <<< "$t"; then echo "M"; return; fi
  if grep -Eiq 'architecture|system|migration|multi' <<< "$t"; then echo "L"; return; fi
  echo "M"
}

risk_from_text() {
  local t="$1"
  if grep -Eiq 'security|auth|encryption|redis|data loss|incident' <<< "$t"; then echo "High"; return; fi
  if grep -Eiq 'api|infra|deployment|migration' <<< "$t"; then echo "Medium"; return; fi
  echo "Low"
}

confidence_from_text() {
  local t="$1"
  if grep -Eiq 'todo|\[ \]|next steps|remaining|pending' <<< "$t"; then echo "92"; return; fi
  if grep -Eiq 'analysis|summary|audit|report|plan' <<< "$t"; then echo "74"; return; fi
  echo "65"
}

printf 'source\ttitle\titem_id\tarea\ttype\tpriority\teffort\trisk\tconfidence\n' > "$LOG"

# Dedupe by normalized title in this import run.
declare -A seen

# Skip titles that already exist in the project.
declare -A existing_titles
while IFS= read -r et; do
  [[ -n "$et" ]] && existing_titles["$et"]=1
done < <(gh project item-list "$PROJECT_NUMBER" --owner "$OWNER" --limit 500 --format json | jq -r '.items[].title')

while IFS=$'\t' read -r source title body; do
  [[ "$source" == "source_file" ]] && continue
  [[ -z "$title" ]] && continue

  if [[ -n "${existing_titles[$title]:-}" ]]; then
    continue
  fi

  norm_title="$(printf '%s' "$title" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/ /g; s/^ //; s/ $//')"
  if [[ -n "${seen[$norm_title]:-}" ]]; then
    continue
  fi
  seen[$norm_title]=1

  blob="$(printf '%s %s %s' "$source" "$title" "$body" | tr '[:upper:]' '[:lower:]')"
  area="$(area_from_text "$blob")"
  type="$(type_from_text "$blob")"
  priority="$(priority_from_text "$blob")"
  effort="$(effort_from_text "$blob")"
  risk="$(risk_from_text "$blob")"
  confidence="$(confidence_from_text "$blob")"

  area_opt="$(get_option_id "Area" "$area")"
  type_opt="$(get_option_id "Type" "$type")"
  pri_opt="$(get_option_id "Priority" "$priority")"
  eff_opt="$(get_option_id "Effort" "$effort")"
  risk_opt="$(get_option_id "Risk" "$risk")"

  created_json="$(gh project item-create "$PROJECT_NUMBER" --owner "$OWNER" --title "$title" --body "$body" --format json)"
  item_id="$(jq -r '.id' <<< "$created_json")"
  existing_titles["$title"]=1

  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$FLOW_FIELD_ID" --single-select-option-id "$FLOW_INBOX_ID" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$AREA_FIELD_ID" --single-select-option-id "$area_opt" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$TYPE_FIELD_ID" --single-select-option-id "$type_opt" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$PRIORITY_FIELD_ID" --single-select-option-id "$pri_opt" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$EFFORT_FIELD_ID" --single-select-option-id "$eff_opt" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$RISK_FIELD_ID" --single-select-option-id "$risk_opt" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$SOURCE_FIELD_ID" --text "$(basename "$source")" >/dev/null
  gh project item-edit --id "$item_id" --project-id "$PROJECT_ID" --field-id "$CONFIDENCE_FIELD_ID" --number "$confidence" >/dev/null

  printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n' "$source" "$title" "$item_id" "$area" "$type" "$priority" "$effort" "$risk" "$confidence" >> "$LOG"
done < "$SEED_TSV"

echo "IMPORT_LOG=$LOG"
echo "CREATED_COUNT=$(($(wc -l < "$LOG") - 1))"
