#!/bin/bash

# Verbose PHPStan script for PayCal Dev
# Usage: ./run_phpstan.sh [level]

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
HTML_DIR="$PROJECT_ROOT/html"
PHPSTAN_BIN="$PROJECT_ROOT/vendor/bin/phpstan"
PHPSTAN_CONFIG="$PROJECT_ROOT/phpstan.neon"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}PHPStan Verbose Analysis${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}Project Root: ${PROJECT_ROOT}${NC}"
echo -e "${YELLOW}HTML Dir: ${HTML_DIR}${NC}"
echo -e "${YELLOW}PHPStan Binary: ${PHPSTAN_BIN}${NC}"
echo -e "${YELLOW}Config File: ${PHPSTAN_CONFIG}${NC}"
echo ""

# Check if phpstan exists
if [ ! -f "$PHPSTAN_BIN" ]; then
    echo -e "${RED}ERROR: PHPStan binary not found at $PHPSTAN_BIN${NC}"
    exit 1
fi

# Check if config exists
if [ ! -f "$PHPSTAN_CONFIG" ]; then
    echo -e "${YELLOW}WARNING: Config file not found at $PHPSTAN_CONFIG${NC}"
    echo "Creating default config..."
fi

# Change to project root
cd "$PROJECT_ROOT"

echo -e "${BLUE}Running PHPStan analysis...${NC}"
echo ""

# Run PHPStan with verbose output and increased memory
php -d memory_limit=512M "$PHPSTAN_BIN" analyse --configuration="$PHPSTAN_CONFIG" --verbose $@

exit_code=$?

echo ""
if [ $exit_code -eq 0 ]; then
    echo -e "${GREEN}✓ PHPStan analysis completed successfully!${NC}"
else
    echo -e "${RED}✗ PHPStan found errors (exit code: $exit_code)${NC}"
fi
echo ""

exit $exit_code
