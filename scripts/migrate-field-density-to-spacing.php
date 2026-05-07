<?php declare(strict_types=1);

/**
 * migrate-field-density-to-spacing.php
 *
 * Renames the 'density' Redis hash field to 'spacing' for all user records.
 *
 * Background: The user preference was originally stored under the field name
 * 'density'. In the v1.055+ rename, all code (PHP, JS, CSS, i18n) was updated
 * to use 'spacing'. This script migrates existing Redis hashes so the field
 * name matches the new convention.
 *
 * Safe to run multiple times: if the 'density' field is absent, the user record
 * is skipped silently.
 *
 * Usage:
 *   php scripts/migrate-field-density-to-spacing.php              # dry-run (default)
 *   php scripts/migrate-field-density-to-spacing.php --dry-run    # preview changes
 *   php scripts/migrate-field-density-to-spacing.php --execute    # perform migration
 *   php scripts/migrate-field-density-to-spacing.php --verify     # confirm no 'density' fields remain
 */

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

require __DIR__ . '/../html/bootstrap/index.php';

$mode = 'dry-run';
if (isset($argv[1])) {
  $arg = strtolower(trim((string) $argv[1]));
  if (in_array($arg, ['--execute', '--dry-run', '--verify'], true)) {
    $mode = ltrim($arg, '-');
  } else {
    fwrite(STDERR, "Usage: php scripts/migrate-field-density-to-spacing.php [--dry-run|--execute|--verify]\n");
    exit(1);
  }
}

$scanned  = 0;
$found    = 0;
$migrated = 0;
$errors   = 0;

foreach (Database::scanKeys(Keys::USER . ':*') as $key) {
  $redisKey = (string) $key;
  if ($redisKey === '' || strpos($redisKey, ':csrf:') !== false) {
    continue;
  }

  $scanned++;

  if ($mode === 'verify') {
    $densityValue = Database::hget($redisKey, 'density');
    if ($densityValue !== null && $densityValue !== false && $densityValue !== '') {
      echo "UNMIGRATED: $redisKey still has density=$densityValue\n";
      $found++;
    }
    continue;
  }

  $densityValue = Database::hget($redisKey, 'density');
  if ($densityValue === null || $densityValue === false || $densityValue === '') {
    continue;
  }

  $found++;
  $existingSpacing = Database::hget($redisKey, 'spacing');

  if ($mode === 'execute') {
    try {
      Database::hset($redisKey, ['spacing' => (string) $densityValue]);
      Database::hdel($redisKey, 'density');
      $migrated++;
      echo "Migrated $redisKey: density=$densityValue -> spacing=$densityValue\n";
    } catch (\Throwable $e) {
      $errors++;
      fwrite(STDERR, "ERROR migrating $redisKey: " . $e->getMessage() . "\n");
    }
  } else {
    $note = ($existingSpacing !== null && $existingSpacing !== false && $existingSpacing !== '')
      ? " (existing spacing=$existingSpacing will be overwritten)"
      : '';
    echo "[DRY-RUN] Would migrate $redisKey: density=$densityValue -> spacing$note\n";
  }
}

echo "\n";
printf("Mode:     %s\n", $mode);
printf("Scanned:  %d\n", $scanned);
if ($mode === 'verify') {
  printf("Unmigrated records with density field: %d\n", $found);
  if ($found === 0) {
    echo "Verification PASSED: no density fields remain.\n";
  } else {
    echo "Verification FAILED: density fields still present. Re-run with --execute.\n";
    exit(1);
  }
} else {
  printf("Found:    %d\n", $found);
  printf("Migrated: %d\n", $migrated);
  printf("Errors:   %d\n", $errors);
  if ($mode === 'dry-run') {
    echo "No data changed. Re-run with --execute to apply migration.\n";
  }
}
