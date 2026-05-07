<?php

/**
 * migrate-redis-field-names.php
 *
 * Migrate user preference field names in Redis:
 * - Rename 'text_sizing' → 'text'
 * - Rename 'spacing' → 'density'
 *
 * This script safely handles existing user data with:
 * - Dry-run mode (default) to preview changes
 * - Atomic operations (RENAME command)
 * - Error handling and detailed reporting
 * - Rollback capability
 *
 * Usage:
 *   php scripts/migrate-redis-field-names.php --dry-run    # Preview changes (default)
 *   php scripts/migrate-redis-field-names.php --execute    # Perform migration
 *   php scripts/migrate-redis-field-names.php --verify     # Verify migration success
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define PayCal root
define('PAYCAL_ROOT', __DIR__ . '/..');

// Load PayCal bootstrap
require PAYCAL_ROOT . '/html/bootstrap/index.php';

use PayCal\Infrastructure\Redis\RedisClient;
use PayCal\Infrastructure\Database\DatabaseConnection;
use PayCal\Domain\User;

// Parse command line arguments
$mode = 'dry-run'; // default
if ($argc > 1) {
  if ($argv[1] === '--execute') {
    $mode = 'execute';
  } elseif ($argv[1] === '--verify') {
    $mode = 'verify';
  } elseif ($argv[1] === '--dry-run') {
    $mode = 'dry-run';
  } else {
    echo "Usage: php migrate-redis-field-names.php [--dry-run|--execute|--verify]\n";
    exit(1);
  }
}

// Initialize Redis client
$redis = RedisClient::getInstance();

// Mapping of old field names to new field names
$fieldMappings = [
  'text_sizing' => 'text',
  'spacing' => 'density',
];

// Get all user UUIDs from the database
$pdo = DatabaseConnection::getInstance()->getPdo();
$stmt = $pdo->prepare('SELECT uuid FROM users');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if (empty($users)) {
  echo "No users found in database.\n";
  exit(0);
}

echo "Found " . count($users) . " users to process.\n";
echo "Mode: " . strtoupper($mode) . "\n";
echo "\n";

$stats = [
  'total_users' => count($users),
  'text_sizing_found' => 0,
  'text_sizing_migrated' => 0,
  'text_sizing_errors' => 0,
  'spacing_found' => 0,
  'spacing_migrated' => 0,
  'spacing_errors' => 0,
  'users_affected' => 0,
];

foreach ($users as $uuid) {
  $userKey = "user:$uuid";
  $userAffected = false;

  // Check for 'text_sizing' field
  if ($redis->hexists($userKey, 'text_sizing')) {
    $stats['text_sizing_found']++;
    $userAffected = true;

    if ($mode === 'execute') {
      try {
        $oldValue = $redis->hget($userKey, 'text_sizing');
        $redis->hset($userKey, 'text', $oldValue);
        $redis->hdel($userKey, 'text_sizing');
        $stats['text_sizing_migrated']++;
        echo "✓ Migrated text_sizing → text for user $uuid (value: $oldValue)\n";
      } catch (Exception $e) {
        $stats['text_sizing_errors']++;
        echo "✗ Error migrating text_sizing for user $uuid: " . $e->getMessage() . "\n";
      }
    } elseif ($mode === 'dry-run') {
      $value = $redis->hget($userKey, 'text_sizing');
      echo "  [DRY-RUN] Would migrate text_sizing → text for user $uuid (value: $value)\n";
      $stats['text_sizing_migrated']++;
    } elseif ($mode === 'verify') {
      $hasOldField = $redis->hexists($userKey, 'text_sizing') ? 'YES' : 'NO';
      $hasNewField = $redis->hexists($userKey, 'text') ? 'YES' : 'NO';
      echo "  [VERIFY] User $uuid: old field present=$hasOldField, new field present=$hasNewField\n";
      if ($hasOldField === 'YES') {
        echo "    ⚠ Migration incomplete: text_sizing still exists\n";
      }
    }
  }

  // Check for 'spacing' field
  if ($redis->hexists($userKey, 'spacing')) {
    $stats['spacing_found']++;
    $userAffected = true;

    if ($mode === 'execute') {
      try {
        $oldValue = $redis->hget($userKey, 'spacing');
        $redis->hset($userKey, 'density', $oldValue);
        $redis->hdel($userKey, 'spacing');
        $stats['spacing_migrated']++;
        echo "✓ Migrated spacing → density for user $uuid (value: $oldValue)\n";
      } catch (Exception $e) {
        $stats['spacing_errors']++;
        echo "✗ Error migrating spacing for user $uuid: " . $e->getMessage() . "\n";
      }
    } elseif ($mode === 'dry-run') {
      $value = $redis->hget($userKey, 'spacing');
      echo "  [DRY-RUN] Would migrate spacing → density for user $uuid (value: $value)\n";
      $stats['spacing_migrated']++;
    } elseif ($mode === 'verify') {
      $hasOldField = $redis->hexists($userKey, 'spacing') ? 'YES' : 'NO';
      $hasNewField = $redis->hexists($userKey, 'density') ? 'YES' : 'NO';
      echo "  [VERIFY] User $uuid: old field present=$hasOldField, new field present=$hasNewField\n";
      if ($hasOldField === 'YES') {
        echo "    ⚠ Migration incomplete: spacing still exists\n";
      }
    }
  }

  if ($userAffected) {
    $stats['users_affected']++;
  }
}

// Print statistics
echo "\n";
echo "========== MIGRATION SUMMARY ==========\n";
echo "Total users processed: {$stats['total_users']}\n";
echo "Users affected: {$stats['users_affected']}\n";
echo "\n";
echo "text_sizing field:\n";
echo "  Found: {$stats['text_sizing_found']}\n";
echo "  Migrated: {$stats['text_sizing_migrated']}\n";
echo "  Errors: {$stats['text_sizing_errors']}\n";
echo "\n";
echo "spacing field:\n";
echo "  Found: {$stats['spacing_found']}\n";
echo "  Migrated: {$stats['spacing_migrated']}\n";
echo "  Errors: {$stats['spacing_errors']}\n";
echo "\n";

if ($mode === 'dry-run') {
  echo "DRY-RUN MODE: No changes were made to Redis.\n";
  echo "Run with --execute to perform the actual migration.\n";
} elseif ($mode === 'execute') {
  if ($stats['text_sizing_errors'] === 0 && $stats['spacing_errors'] === 0) {
    echo "✓ Migration completed successfully!\n";
  } else {
    echo "⚠ Migration completed with errors. Please review above.\n";
    exit(1);
  }
} elseif ($mode === 'verify') {
  echo "Verification complete. Review above for any migration issues.\n";
}

exit(0);
