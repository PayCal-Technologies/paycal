#!/bin/bash

################################################################################
# Redis Update Script - Import i18n Strings to Redis
#
# Copyright 2026 Chris Simmons cshaiku@gmail.com
#
# Purpose:
#   Imports i18n strings from filesystem into Redis
#   using atomic swap operations (shadow import) to ensure consistency.
#
# Usage:
#   redis-update.sh           # Import from DEV_DIR (default)
#   redis-update.sh prod      # Import from PROD_DIR
#   redis-update.sh -h        # Show help
#
# Features:
#   - Atomic shadow imports (no downtime)
#   - Multi-language string support
#   - Colorized logging with timestamps
#
################################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PAYCAL_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
# In this workspace, scripts live under /.../paycal/dev/scripts and the
# environment root is /.../paycal/dev (not /.../paycal/dev/dev).
if [ -d "$PAYCAL_ROOT/html" ] && [ -d "$PAYCAL_ROOT/strings" ]; then
  DEV_DIR="$PAYCAL_ROOT"
else
  DEV_DIR="$PAYCAL_ROOT/dev"
fi
PROD_DIR="$PAYCAL_ROOT/prod"

##########################################
# COLORS
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

show_help() {
cat << 'EOF'
Usage:
  redis-update.sh           Import from development environment
  redis-update.sh prod      Import from production environment
  redis-update.sh -h        Show this help

Description:
  Atomically imports i18n strings into Redis.
  Uses shadow keys to ensure zero-downtime updates.

Examples:
  redis-update.sh           # Import from dev
  redis-update.sh prod      # Import from production
EOF
}

##########################################
# PARSE ARGUMENTS
# Determine BASE_DIR from script location (works from any cwd)
BASE_DIR="$DEV_DIR"
ENV_NAME="development"

case "${1:-}" in
  -h|--help)
    show_help
    exit 0
    ;;
  prod|production)
    BASE_DIR="$PROD_DIR"
    ENV_NAME="production"
    ;;
  dev|development|"")
    BASE_DIR="$DEV_DIR"
    ENV_NAME="development"
    ;;
  *)
    err "[✖] Unknown argument: $1"
    show_help
    exit 1
    ;;
esac

##########################################
# REDIS IMPORT
log "[→] Starting Redis import from $ENV_NAME environment..."
log "[→] Base directory: $BASE_DIR"

if [ ! -d "$BASE_DIR/html" ]; then
  err "[✖] HTML directory does not exist: $BASE_DIR/html"
  exit 1
fi

if [ ! -d "$BASE_DIR/strings" ]; then
  err "[✖] Strings directory does not exist: $BASE_DIR/strings"
  exit 1
fi

export BASE_DIR
php << 'PHP_SCRIPT'
<?php
date_default_timezone_set("America/Toronto");
ob_start();
require_once rtrim((string) getenv("BASE_DIR"), "/") . "/html/config.php";
ob_end_clean();

use PayCal\Domain\Database;
use PayCal\Domain\Environment;

$appHome = Environment::appHome();

function atomic_import($hashKey, $files) {
    $tmpKey = $hashKey . "_tmp";
    Database::del($tmpKey);
    $count = 0;
    
    foreach ($files as $file) {
        $lang = basename($file, ".txt");
        $tmpLangKey = "system:i18n:$lang" . "_tmp";
        Database::del($tmpLangKey);
        $data = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === "" || $line[0] === "#") continue;
            $parts = preg_split('/\s+/', $line, 2);
            if (count($parts) === 2) {
                [$k, $v] = $parts;
                // Remove i_ or h_ prefix if present
                if (stripos($k, 'i_') === 0 || stripos($k, 'h_') === 0) {
                    $k = substr($k, 2);
                }
                // Normalize legacy key names during import.
                if ($k === 'UPDATING_DENSITY') {
                    $k = 'UPDATING_DENSITY_TO';
                }
                $data[$k] = $v;
            }
        }
        if (!empty($data)) {
            Database::hset($tmpLangKey, $data);
        }
        Database::rename($tmpLangKey, "system:i18n:$lang");
        $count++;
    }

    return $count;
}

// i18n strings
$stringFiles = array_values(array_filter(
  glob($appHome . "strings/*.txt") ?: [],
  static fn (string $file): bool => basename($file) !== 'html.txt'
));
if ($stringFiles === false || count($stringFiles) === 0) {
    echo "[" . date("Y-m-d H:i:s") . "] [!] No string files found.\n";
} else {
    $stringCount = atomic_import("system:i18n", $stringFiles);
    echo "[" . date("Y-m-d H:i:s") . "] [✔] Imported $stringCount string files (atomic swap).\n";
}
PHP_SCRIPT

log "[✔] Redis import completed successfully."
exit 0
