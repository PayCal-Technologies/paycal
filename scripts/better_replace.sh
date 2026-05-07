#!/bin/bash

# Usage: better_replace.sh <search_pattern> <replace_pattern> <directory> [--dry-run] [--literal]
# Features:
# - Prints diff preview before replacing
# - Supports dry-run mode
# - Handles escaping safely
# - Works on GNU and BSD sed
# - --literal flag for exact string match (no regex)

if [ $# -lt 3 ]; then
  echo "Usage: $0 <search_pattern> <replace_pattern> <directory> [--dry-run] [--literal]"
  exit 1
fi

search_pattern="$1"
replace_pattern="$2"
directory="$3"
dry_run=false
literal=false

for arg in "$@"; do
  if [ "$arg" == "--dry-run" ]; then
    dry_run=true
  fi
  if [ "$arg" == "--literal" ]; then
    literal=true
  fi
done

# Safely iterate over matching files (handles spaces)
grep -rl -- "$search_pattern" "$directory" | while IFS= read -r file; do
  echo "--- $file ---"

  if [ "$literal" = true ]; then
    # Literal string replacement (no regex)
    diff_output=$(awk -v s="$search_pattern" -v r="$replace_pattern" '{gsub(s, r)} 1' "$file" | diff -u "$file" -)

    if [ -n "$diff_output" ]; then
      echo "$diff_output"
      if [ "$dry_run" = false ]; then
        awk -v s="$search_pattern" -v r="$replace_pattern" '{gsub(s, r)} 1' "$file" > "$file.tmp" && mv "$file.tmp" "$file"
        echo "Applied literal replacement."
      else
        echo "Dry run: no changes applied."
      fi
    else
      echo "No changes needed."
    fi

  else
    # Escape delimiter safely for sed
    safe_search=${search_pattern//|/\\|}
    safe_replace=${replace_pattern//|/\\|}

    diff_output=$(sed "s|$safe_search|$safe_replace|g" "$file" | diff -u "$file" -)

    if [ -n "$diff_output" ]; then
      echo "$diff_output"
      if [ "$dry_run" = false ]; then
        if sed --version >/dev/null 2>&1; then
          sed -i "s|$safe_search|$safe_replace|g" "$file"
        else
          sed -i '' "s|$safe_search|$safe_replace|g" "$file"
        fi
        echo "Applied regex replacement."
      else
        echo "Dry run: no changes applied."
      fi
    else
      echo "No changes needed."
    fi
  fi

  echo
done
