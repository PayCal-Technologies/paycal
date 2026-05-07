#!/usr/bin/env bash
set -euo pipefail

# PURPOSE:
#   Fast-batch translate transparency i18n keys from English into supported locales.
#
# USAGE:
#   bash scripts/transparency/translate_keys_fast.sh
#   TRANSLATE_LANGS="de fr" BATCH_SIZE=60 bash scripts/transparency/translate_keys_fast.sh
#
# WHY THIS LIVES HERE:
#   This script is repeatedly useful for transparency localization maintenance.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if [[ -n "${TRANSLATE_LANGS:-}" ]]; then
  # shellcheck disable=SC2206
  langs=(${TRANSLATE_LANGS})
else
  langs=(de es fr hi it nl pt tl tr)
fi

keys_file=/tmp/transparency_i18n_keys_used_uniq.txt

if [[ ! -f "$keys_file" ]]; then
  for f in html/transparency/en.php html/transparency/*/en.php; do
    awk '/\$i18nKeys[[:space:]]*=[[:space:]]*\[/, /\];/ { while (match($0, /\x27[A-Z0-9_]+\x27/)) { key=substr($0, RSTART+1, RLENGTH-2); print key; $0=substr($0, RSTART+RLENGTH) } }' "$f"
    rg -No "Strings::i18n\('([A-Z0-9_]+)'\)" "$f" --replace '$1' || true
  done | sort -u > "$keys_file"
fi

get_en_value() {
  local key="$1"
  local line
  line=$(rg -N "^${key} " strings/en.txt | head -n 1 || true)
  if [[ -z "$line" ]]; then
    echo ""
    return
  fi
  echo "${line#${key} }"
}

translate_block() {
  local text_block="$1"
  local target_lang="$2"
  local response

  response=$(curl -s --max-time 20 --retry 2 --retry-delay 1 --get \
    --data-urlencode "client=gtx" \
    --data-urlencode "sl=en" \
    --data-urlencode "tl=${target_lang}" \
    --data-urlencode "dt=t" \
    --data-urlencode "q=${text_block}" \
    "https://translate.googleapis.com/translate_a/single")

  php -r '$j=json_decode(stream_get_contents(STDIN), true); if (is_array($j) && isset($j[0]) && is_array($j[0])) { $out=""; foreach($j[0] as $seg){ if(isset($seg[0])) $out.=$seg[0]; } echo $out; }' <<< "$response"
}

batch_size=${BATCH_SIZE:-80}

for lang in "${langs[@]}"; do
  echo "[fast-translate] locale=${lang}"

  map_file="/tmp/transparency_fast_map_${lang}.tsv"
  : > "$map_file"

  keys=()
  vals=()

  while IFS= read -r key; do
    [[ -z "$key" ]] && continue
    val=$(get_en_value "$key")
    [[ -z "$val" ]] && continue
    keys+=("$key")
    vals+=("$val")
  done < "$keys_file"

  total=${#keys[@]}
  i=0
  while (( i < total )); do
    end=$((i + batch_size))
    (( end > total )) && end=$total

    block=""
    for ((j=i; j<end; j++)); do
      block+="${vals[$j]}"
      if (( j < end - 1 )); then
        block+=$'\n'
      fi
    done

    translated=$(translate_block "$block" "$lang" || true)
    if [[ -z "$translated" ]]; then
      for ((j=i; j<end; j++)); do
        printf '%s\t%s\n' "${keys[$j]}" "${vals[$j]}" >> "$map_file"
      done
    else
      tr_lines=()
      while IFS= read -r line; do
        tr_lines+=("$line")
      done <<< "$translated"

      expected=$((end - i))
      got=${#tr_lines[@]}

      if (( got != expected )); then
        for ((j=i; j<end; j++)); do
          printf '%s\t%s\n' "${keys[$j]}" "${vals[$j]}" >> "$map_file"
        done
      else
        for ((j=0; j<expected; j++)); do
          line=${tr_lines[$j]//$'\n'/ }
          printf '%s\t%s\n' "${keys[$((i+j))]}" "$line" >> "$map_file"
        done
      fi
    fi

    i=$end
    echo "[progress] locale=${lang} translated=${i}/${total}"
  done

  out_file="/tmp/${lang}.txt.transparency.fast.new"
  awk -v MAP="$map_file" 'BEGIN{ while((getline line<MAP)>0){ key=line; sub(/\t.*/,"",key); val=line; sub(/^[^\t]*\t/,"",val); m[key]=val } }
  {
    if ($0 ~ /^#/ || $0 ~ /^$/) { print $0; next }
    key=$1
    if (key in m) {
      print key " " m[key]
    } else {
      print $0
    }
  }' "strings/${lang}.txt" > "$out_file"

  mv "$out_file" "strings/${lang}.txt"
  echo "[done] locale=${lang}"
done

echo "[complete] fast transparency key translations applied"
