#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WIN10_FILE="$ROOT_DIR/html/css/win10_dark/index.php"
TOKENS_FILE="$ROOT_DIR/html/css/tokens/index.php"

TOKEN_KEYS=()
TOKEN_VALS=()
SPLIT_LEFT=""
SPLIT_RIGHT=""

set_token() {
  local key="$1"
  local value="$2"
  local i
  for ((i = 0; i < ${#TOKEN_KEYS[@]}; i++)); do
    if [[ "${TOKEN_KEYS[$i]}" == "$key" ]]; then
      TOKEN_VALS[$i]="$value"
      return 0
    fi
  done
  TOKEN_KEYS+=("$key")
  TOKEN_VALS+=("$value")
}

get_token() {
  local key="$1"
  local i
  for ((i = 0; i < ${#TOKEN_KEYS[@]}; i++)); do
    if [[ "${TOKEN_KEYS[$i]}" == "$key" ]]; then
      printf '%s' "${TOKEN_VALS[$i]}"
      return 0
    fi
  done
  return 1
}

trim() {
  local s="$1"
  s="${s#"${s%%[![:space:]]*}"}"
  s="${s%"${s##*[![:space:]]}"}"
  printf '%s' "$s"
}

load_tokens() {
  local file="$1"
  local line key value
  local token_re='^[[:space:]]*(--[a-z0-9-]+)[[:space:]]*:[[:space:]]*([^;]+);'
  while IFS= read -r line; do
    if [[ $line =~ $token_re ]]; then
      key="${BASH_REMATCH[1]}"
      value="$(trim "${BASH_REMATCH[2]}")"
      set_token "$key" "$value"
    fi
  done < "$file"
}

split_top_level_once() {
  local input="$1"
  local left=""
  local right=""
  local depth=0
  local i ch
  local found=0

  for ((i = 0; i < ${#input}; i++)); do
    ch="${input:i:1}"
    [[ $ch == "(" ]] && ((depth++))
    [[ $ch == ")" ]] && ((depth--))

    if [[ $ch == "," && $depth -eq 0 ]]; then
      left="${input:0:i}"
      right="${input:i+1}"
      found=1
      break
    fi
  done

  if [[ $found -eq 0 ]]; then
    left="$input"
    right=""
  fi

  SPLIT_LEFT="$(trim "$left")"
  SPLIT_RIGHT="$(trim "$right")"
}

resolve_color() {
  local expr="$(trim "$1")"
  local depth="${2:-0}"
  local inner ref fallback ref_val hex rr gg bb
  local mix p1part p2part p1expr p2expr p1pct p2pct p1 p2
  local c1 c2 r1 g1 b1 r2 g2 b2
  local expr_lc

  (( depth > 24 )) && return 1
  [[ -z "$expr" ]] && return 1

  if [[ $expr == var\(* ]]; then
    inner="${expr#var(}"
    inner="${inner%)}"
    split_top_level_once "$inner"
    ref="$SPLIT_LEFT"
    fallback="$SPLIT_RIGHT"

    if ref_val="$(get_token "$ref")"; then
      if resolve_color "$ref_val" "$((depth + 1))"; then
        return 0
      fi
    fi

    if [[ -n "$fallback" ]]; then
      resolve_color "$fallback" "$((depth + 1))"
      return $?
    fi

    return 1
  fi

  if [[ $expr =~ ^#[0-9A-Fa-f]{3}$ ]]; then
    hex="${expr#\#}"
    rr=$((16#${hex:0:1}${hex:0:1}))
    gg=$((16#${hex:1:1}${hex:1:1}))
    bb=$((16#${hex:2:1}${hex:2:1}))
    printf '%s %s %s\n' "$rr" "$gg" "$bb"
    return 0
  fi

  if [[ $expr =~ ^#[0-9A-Fa-f]{6}$ ]]; then
    hex="${expr#\#}"
    rr=$((16#${hex:0:2}))
    gg=$((16#${hex:2:2}))
    bb=$((16#${hex:4:2}))
    printf '%s %s %s\n' "$rr" "$gg" "$bb"
    return 0
  fi

  expr_lc="$(printf '%s' "$expr" | tr '[:upper:]' '[:lower:]')"

  if [[ $expr_lc == "white" ]]; then
    printf '255 255 255\n'
    return 0
  fi

  if [[ $expr_lc == "black" ]]; then
    printf '0 0 0\n'
    return 0
  fi

  if [[ $expr == color-mix\(* ]]; then
    mix="${expr#color-mix(}"
    mix="${mix%)}"
    mix="${mix#in srgb,}"
    split_top_level_once "$mix"
    p1part="$SPLIT_LEFT"
    p2part="$SPLIT_RIGHT"
    [[ -z "$p1part" || -z "$p2part" ]] && return 1

    p1expr="$p1part"
    p2expr="$p2part"
    p1pct=""
    p2pct=""

    if [[ $p1part =~ ^(.*)[[:space:]]+([0-9]+(\.[0-9]+)?)%[[:space:]]*$ ]]; then
      p1expr="$(trim "${BASH_REMATCH[1]}")"
      p1pct="${BASH_REMATCH[2]}"
    fi

    if [[ $p2part =~ ^(.*)[[:space:]]+([0-9]+(\.[0-9]+)?)%[[:space:]]*$ ]]; then
      p2expr="$(trim "${BASH_REMATCH[1]}")"
      p2pct="${BASH_REMATCH[2]}"
    fi

    if [[ -z "$p1pct" && -z "$p2pct" ]]; then
      p1="0.5"
      p2="0.5"
    elif [[ -z "$p1pct" ]]; then
      p2="$(awk -v p="$p2pct" 'BEGIN{printf "%.12f", p/100}')"
      p1="$(awk -v p="$p2" 'BEGIN{printf "%.12f", 1-p}')"
    elif [[ -z "$p2pct" ]]; then
      p1="$(awk -v p="$p1pct" 'BEGIN{printf "%.12f", p/100}')"
      p2="$(awk -v p="$p1" 'BEGIN{printf "%.12f", 1-p}')"
    else
      p1="$(awk -v p="$p1pct" 'BEGIN{printf "%.12f", p/100}')"
      p2="$(awk -v p="$p2pct" 'BEGIN{printf "%.12f", p/100}')"
    fi

    c1="$(resolve_color "$p1expr" "$((depth + 1))")" || return 1
    c2="$(resolve_color "$p2expr" "$((depth + 1))")" || return 1
    read -r r1 g1 b1 <<< "$c1"
    read -r r2 g2 b2 <<< "$c2"

    awk -v r1="$r1" -v g1="$g1" -v b1="$b1" -v r2="$r2" -v g2="$g2" -v b2="$b2" -v p1="$p1" -v p2="$p2" 'BEGIN {
      r = int((r1 * p1) + (r2 * p2) + 0.5)
      g = int((g1 * p1) + (g2 * p2) + 0.5)
      b = int((b1 * p1) + (b2 * p2) + 0.5)
      printf "%d %d %d\n", r, g, b
    }'
    return 0
  fi

  return 1
}

contrast_white() {
  local r="$1" g="$2" b="$3"
  awk -v r="$r" -v g="$g" -v b="$b" 'BEGIN {
    cr = r / 255
    cg = g / 255
    cb = b / 255
    lr = (cr <= 0.03928) ? (cr / 12.92) : (((cr + 0.055) / 1.055) ^ 2.4)
    lg = (cg <= 0.03928) ? (cg / 12.92) : (((cg + 0.055) / 1.055) ^ 2.4)
    lb = (cb <= 0.03928) ? (cb / 12.92) : (((cb + 0.055) / 1.055) ^ 2.4)
    lum_bg = (0.2126 * lr) + (0.7152 * lg) + (0.0722 * lb)
    ratio = (1.0 + 0.05) / (lum_bg + 0.05)
    printf "%.2f", ratio
  }'
}

if [[ ! -f "$WIN10_FILE" || ! -f "$TOKENS_FILE" ]]; then
  echo "Required token files are missing." >&2
  exit 1
fi

load_tokens "$TOKENS_FILE"
load_tokens "$WIN10_FILE"

labels=("Btn" "BtnHover" "BtnActive" "BtnPrimary" "BtnPrimaryHover" "BtnPrimaryActive")
tokens=("--button-bg" "--button-bg-hover" "--button-bg-active" "--button-primary-bg" "--button-primary-bg-hover" "--button-primary-bg-active")

for i in "${!labels[@]}"; do
  label="${labels[$i]}"
  token_name="${tokens[$i]}"
  if ! raw="$(get_token "$token_name")"; then
    printf '%s white ratio: unresolved (missing token %s)\n' "$label" "$token_name"
    continue
  fi

  if rgb_line="$(resolve_color "$raw" 0)"; then
    read -r r g b <<< "$rgb_line"
    ratio="$(contrast_white "$r" "$g" "$b")"
    printf '%s white ratio: %s:1\n' "$label" "$ratio"
  else
    printf '%s white ratio: unresolved (cannot resolve %s)\n' "$label" "$token_name"
  fi
done