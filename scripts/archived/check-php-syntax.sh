#!/bin/bash
# PHP Syntax Checker
# Validates PHP syntax for all .php files before git operations
# Used as a pre-commit hook and standalone validation tool

set -e

WORKSPACE_ROOT="${1:-.}"
ERRORS=0
ERROR_FILES=()

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}[→] Checking PHP syntax for all files in ${WORKSPACE_ROOT}...${NC}"

# Find all PHP files and check syntax
# Exclude vendor and node_modules directories for speed
while IFS= read -r -d '' file; do
  if php -l "$file" > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} $file"
  else
    echo -e "${RED}✗${NC} $file"
    ERRORS=$((ERRORS + 1))
    ERROR_FILES+=("$file")
    php -l "$file" 2>&1 | sed 's/^/  /'
  fi
done < <(find "$WORKSPACE_ROOT" -type f -name "*.php" ! -path "*/vendor/*" ! -path "*/node_modules/*" -print0)

echo ""

if [ $ERRORS -eq 0 ]; then
  echo -e "${GREEN}[✓] All PHP files passed syntax validation${NC}"
  exit 0
else
  echo -e "${RED}[✗] Found $ERRORS PHP file(s) with syntax errors:${NC}"
  for file in "${ERROR_FILES[@]}"; do
    echo "  - $file"
  done
  exit 1
fi
