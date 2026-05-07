<?php declare(strict_types=1);

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

require __DIR__ . '/../html/bootstrap/index.php';

$mode = 'dry-run';
if (isset($argv[1])) {
  $arg = strtolower(trim((string) $argv[1]));
  if (in_array($arg, ['--execute', '--dry-run'], true)) {
    $mode = ltrim($arg, '-');
  } else {
    fwrite(STDERR, "Usage: php scripts/migrate-density-compact-to-tight.php [--dry-run|--execute]\n");
    exit(1);
  }
}

$scanned = 0;
$found = 0;
$updated = 0;

foreach (Database::scanKeys(Keys::USER . ':*') as $key) {
  $redisKey = (string) $key;
  if ($redisKey === '' || strpos($redisKey, ':csrf:') !== false) {
    continue;
  }

  $scanned++;
  $spacing = (string) Database::hget($redisKey, 'spacing');
  if ($spacing !== 'compact') {
    continue;
  }

  $found++;
  if ($mode === 'execute') {
    Database::hset($redisKey, ['spacing' => 'tight']);
    $updated++;
  }
}

printf("Mode: %s\n", $mode);
printf("Keys scanned: %d\n", $scanned);
printf("Compact spacings found: %d\n", $found);
printf("Spacings updated to tight: %d\n", $updated);

if ($mode === 'dry-run') {
  echo "No data changed. Re-run with --execute to apply updates.\n";
}
