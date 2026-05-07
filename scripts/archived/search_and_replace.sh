#!/bin/bash

if [ $# -lt 2 ] || [ $# -gt 3 ]; then
    echo "Usage: $0 <search_term> <replace_term> [directory_path]"
    exit 1
fi

search_term="$1"
replace_term="$2"
directory_path="${3:-.}"

# Escape backslashes and &
replace_term_escaped=$(printf '%s' "$replace_term" | sed 's/[&/\]/\\&/g')
search_term_escaped=$(printf '%s' "$search_term" | sed 's/[\/&]/\\&/g')

if sed --version >/dev/null 2>&1; then
    # GNU sed
	  find "$directory_path" -type f -exec sed -i "s|$search_term_escaped|$replace_term_escaped|g" {} +
else
    # BSD sed (macOS)
    LC_ALL=C find "$directory_path" -type f -exec sed -i '' "s|$search_term_escaped|$replace_term_escaped|g" {} +
fi
