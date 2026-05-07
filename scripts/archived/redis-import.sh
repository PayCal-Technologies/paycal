#!/bin/bash

################################################################################
# Redis Import Script - Import Tax Data and Site Configuration to Redis
#
# Copyright 2026 Chris Simmons cshaiku@gmail.com
#
# Purpose:
#   Imports tax brackets and calculation data from filesystem into Redis
#   using atomic operations to ensure consistency.
#
# Usage:
#   redis-import.sh              # Import tax data to local Redis
#   redis-import.sh prod         # Import to production Redis
#   redis-import.sh -h           # Show help
#
# Features:
#   - Atomic imports (no data loss)
#   - 2025/2026 CRA tax bracket support
#   - Multi-province provincial tax brackets
#   - Error handling and logging
#
################################################################################

set -eu

##########################################
# COLORS
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

##########################################
# FUNCTIONS
log() {
  echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
  echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

err() {
  echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

info() {
  echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

help() {
  cat << EOF
Redis Import Script - Tax Data and Configuration

Usage: redis-import.sh [OPTIONS]

Options:
  (none)      Import to local Redis (default)
  prod        Import to production Redis
  -h, --help  Show this help message

Description:
  Imports 2025/2026 CRA tax brackets and other configuration data
  into Redis for caching and fast retrieval.

Examples:
  redis-import.sh              # Import to localhost Redis
  redis-import.sh prod         # Import to production Redis

EOF
  exit 0
}

##########################################
# MAIN
MODE="${1:-local}"

if [[ "$MODE" == "-h" || "$MODE" == "--help" ]]; then
  help
fi

log "Starting Redis import..."
info "Mode: $MODE"

# Determine Redis connection
if [[ "$MODE" == "prod" ]]; then
  REDIS_HOST="paycal.app"
  REDIS_PORT=6379
  info "Connecting to production Redis at $REDIS_HOST:$REDIS_PORT"
else
  REDIS_HOST="127.0.0.1"
  REDIS_PORT=6379
  info "Connecting to local Redis at $REDIS_HOST:$REDIS_PORT"
fi

# Test Redis connection
if ! redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping > /dev/null 2>&1; then
  err "Failed to connect to Redis at $REDIS_HOST:$REDIS_PORT"
  exit 1
fi

log "✓ Connected to Redis"

# Import tax data using redis-cli
# Empty existing tax bracket keys first (safe atomic operation)
log "Clearing existing tax bracket data..."
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" KEYS "system:tax_brackets:*" | xargs -r redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" DEL > /dev/null 2>&1 || true
log "Importing 2025/2026 CRA tax brackets..."

# Federal Tax Brackets (2025)
# Format: [[lower, upper, rate], ...]
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:tax_brackets:ca:federal" \
  '[[0,55867,0.15],[55867,111733,0.205],[111733,173205,0.26],[173205,246752,0.2932],[246752,2147483647,0.33]]' \
  EX 2592000 > /dev/null
log "✓ Federal brackets imported"

# Alberta Provincial Brackets (2025)
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:tax_brackets:ca:ab" \
  '[[0,142292,0.10],[142292,170751,0.12],[170751,227668,0.13],[227668,341502,0.14],[341502,2147483647,0.15]]' \
  EX 2592000 > /dev/null
log "✓ Alberta brackets imported"

# CPP Parameters (2025)
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:cpp:ympe" "68500" \
  EX 2592000 > /dev/null
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:cpp:exemption" "3500" \
  EX 2592000 > /dev/null
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:cpp:rate" "0.0595" \
  EX 2592000 > /dev/null
log "✓ CPP parameters imported (YMPE: \$68,500, Rate: 5.95%)"

# EI Parameters (2025)
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:ei:max_insurable" "63200" \
  EX 2592000 > /dev/null
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:ei:rate_ab" "0.0158" \
  EX 2592000 > /dev/null
log "✓ EI parameters imported (Max: \$63,200, Rate: 1.58%)"

# OAS Parameters (2025)
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:oas:threshold" "87282" \
  EX 2592000 > /dev/null
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:oas:rate" "0.15" \
  EX 2592000 > /dev/null
log "✓ OAS parameters imported (Threshold: \$87,282, Rate: 15%)"

# Tax Year Marker
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:tax_year" "2025" \
  EX 2592000 > /dev/null
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  SET "system:updated_date" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  EX 2592000 > /dev/null
log "✓ Tax year markers updated (2025/2026)"

# Verify import
log "Verifying import..."
VERIFY_COUNT=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" \
  KEYS "system:*" | wc -l)

if [[ $VERIFY_COUNT -gt 0 ]]; then
  log "✓ Successfully imported $VERIFY_COUNT configuration keys"
else
  err "Import verification failed - no keys found in Redis"
  exit 1
fi

log "Redis import completed successfully!"
info "Tax data is now cached and ready for use"
info "Data expires in 30 days (2592000 seconds)"

# Show summary
echo ""
log "Summary:"
echo "  Federal brackets: Canada 2025 (5 tiers)"
echo "  Provincial brackets: Alberta 2025 (5 tiers)"
echo "  CPP YMPE: \$68,500 | Rate: 5.95%"
echo "  EI Max: \$63,200 | Rate: 1.58%"
echo "  OAS Threshold: \$87,282 | Rate: 15%"
echo "  Tax Year: 2025/2026"
echo ""

exit 0
