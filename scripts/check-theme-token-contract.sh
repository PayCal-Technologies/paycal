#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

mode="all"
if [[ "${1:-}" == "--mode=pilot" ]]; then
  mode="pilot"
elif [[ "${1:-}" == "--mode=converted" ]]; then
  mode="converted"
fi

required_tokens=(
  --color-bg
  --color-bg-soft
  --color-bg-elevated
  --color-surface
  --color-surface-muted
  --color-surface-strong
  --color-border
  --color-text
  --color-text-muted
  --color-primary
  --color-primary-hover
  --color-primary-active
  --color-on-primary
  --color-danger
  --color-focus-ring
  --overlay-backdrop
  --shadow-md
)

theme_files=()
if [[ "$mode" == "pilot" ]]; then
  theme_files=(
    "html/css/paycal_dark/index.php"
    "html/css/paycal_light/index.php"
  )
else
  if [[ "$mode" == "converted" ]]; then
    manifest="docs/data-shapes/theme-token-converted-themes.txt"
    if [[ ! -f "$manifest" ]]; then
      echo "Converted mode requires manifest: $manifest"
      exit 1
    fi
    while IFS= read -r f; do
      [[ -z "$f" ]] && continue
      [[ "$f" =~ ^# ]] && continue
      theme_files+=("$f")
    done < "$manifest"
  else
    while IFS= read -r f; do
      theme_files+=("$f")
    done < <(find html/css -maxdepth 2 -type f -path 'html/css/*_*/index.php' | sort)
  fi
fi

if [[ ${#theme_files[@]} -eq 0 ]]; then
  echo "No theme files found."
  exit 1
fi

echo "Theme token contract check (mode: $mode)"
echo "Required tokens: ${#required_tokens[@]}"

total_failures=0
for file in "${theme_files[@]}"; do
  missing=()
  for token in "${required_tokens[@]}"; do
    if ! rg -q -- "${token}[[:space:]]*:" "$file"; then
      missing+=("$token")
    fi
  done

  if [[ ${#missing[@]} -eq 0 ]]; then
    echo "PASS $file"
  else
    total_failures=$((total_failures + 1))
    echo "FAIL $file"
    for token in "${missing[@]}"; do
      echo "  - missing $token"
    done
  fi

done

echo
echo "Summary: ${#theme_files[@]} files checked, $total_failures files failing contract."
if [[ $total_failures -gt 0 ]]; then
  exit 2
fi
