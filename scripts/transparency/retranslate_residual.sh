#!/usr/bin/env bash
set -euo pipefail

# PURPOSE:
#   Re-translate transparency keys that still match English in non-English locale files.
#
# USAGE:
#   bash scripts/transparency/retranslate_residual.sh
#
# WHY THIS LIVES HERE:
#   This follow-up utility is useful after primary localization passes.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

langs=(de es fr hi it nl pt tl tr)

translate_text() {
  local text="$1"
  local target_lang="$2"
  local response

  response=$(curl -s --max-time 20 --retry 2 --retry-delay 1 --get \
    --data-urlencode "client=gtx" \
    --data-urlencode "sl=en" \
    --data-urlencode "tl=${target_lang}" \
    --data-urlencode "dt=t" \
    --data-urlencode "q=${text}" \
    "https://translate.googleapis.com/translate_a/single")

  php -r '$j=json_decode(stream_get_contents(STDIN), true); if (is_array($j) && isset($j[0]) && is_array($j[0])) { $out=""; foreach($j[0] as $seg){ if(isset($seg[0])) $out.=$seg[0]; } echo $out; }' <<< "$response"
}

for locale in "${langs[@]}"; do
  echo "[residual] locale=$locale"

  same_file="/tmp/transparency_same_as_en_${locale}.txt"
  awk 'NR==FNR{k[$1]=$0;next} ($1 in k){en=$0; sub(/^[^ ]+ /,"",en); lv=k[$1]; sub(/^[^ ]+ /,"",lv); if(lv==en && $1 ~ /^TRANSPARENCY_/) print $1}' strings/en.txt "strings/${locale}.txt" > "$same_file"

  map_file="/tmp/transparency_residual_map_${locale}.tsv"
  : > "$map_file"

  while IFS= read -r key; do
    [[ -z "$key" ]] && continue
    en_line=$(rg -N "^${key} " strings/en.txt | head -n1 || true)
    [[ -z "$en_line" ]] && continue
    en_val=${en_line#${key} }

    tr_val=$(translate_text "$en_val" "$locale" || true)
    [[ -z "$tr_val" ]] && tr_val="$en_val"
    tr_val=${tr_val//$'\n'/ }

    printf '%s\t%s\n' "$key" "$tr_val" >> "$map_file"
  done < "$same_file"

  out_file="/tmp/${locale}.txt.residual.new"
  awk -v MAP="$map_file" 'BEGIN{ while((getline line<MAP)>0){ key=line; sub(/\t.*/,"",key); val=line; sub(/^[^\t]*\t/,"",val); m[key]=val } }
  {
    if ($0 ~ /^#/ || $0 ~ /^$/) { print $0; next }
    key=$1
    if (key in m) {
      print key " " m[key]
    } else {
      print $0
    }
  }' "strings/${locale}.txt" > "$out_file"

  mv "$out_file" "strings/${locale}.txt"
  echo "[done] locale=$locale"
done

echo "[complete] residual transparency translations updated"
