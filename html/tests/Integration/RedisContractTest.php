<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Redis;
use PHPUnit\Framework\Attributes\Group;

// Ensure Database and bootstrap are available
require_once __DIR__.'/../../tests/bootstrap.php';

/**
 * @internal
 *
 */
#[Group('integration')]
#[Group('redis')]
#[Group('api')]
#[Group('skip')]
final class RedisContractTest extends TestCase
{
  public function testRedisNamespaceContracts(): void
  {
    // Safety: only scan inside a test namespace. CI must set REDIS_PREFIX (e.g. "test:")
    $prefix = getenv('REDIS_PREFIX') ?: '';
    if ('' === $prefix) {
      $this->markTestSkipped('REDIS_PREFIX not set; skipping redis-contract test to avoid scanning production Redis.');

      return;
    }

    // Normalize prefix to ensure it ends with ':' if provided without one
    if (!str_ends_with($prefix, ':')) {
      $prefix .= ':';
    }

    $offending = [
        'telemetry' => [],
        'kek' => [],
        'rate' => [],
    ];

    // 1) telemetry:encryption:* keys that do NOT include :v1:
    $telemetryKeys = Database::scanKeys($prefix.'telemetry:encryption:*');
    foreach ($telemetryKeys as $k) {
      if (str_contains($k, 'telemetry:encryption:v1:')) {
        continue;
      }
      $offending['telemetry'][] = $k;
    }

    // 2) user:kek:* keys that do NOT include user:kek:v1:
    $kekKeys = Database::scanKeys($prefix.'user:kek:*');
    foreach ($kekKeys as $k) {
      if (str_contains($k, 'user:kek:v1:')) {
        continue;
      }
      $offending['kek'][] = $k;
    }

    // 3) any :rate: keys without a version segment (i.e., not containing ':v')
    $rateKeys = Database::scanKeys($prefix.'*:rate:*');
    foreach ($rateKeys as $k) {
      // Accept keys that include ":v" followed by digits (e.g. :v1:)
      if (preg_match('/:v\d+:/', $k)) {
        continue;
      }
      $offending['rate'][] = $k;
    }

    $messages = [];
    foreach ($offending as $cat => $list) {
      if (!empty($list)) {
        $messages[] = strtoupper($cat).' - unversioned keys found: '.implode(', ', $list);
      }
    }

    if (!empty($messages)) {
      $this->fail("Redis namespace contract violations:\n".implode("\n", $messages));
    }

    $this->assertTrue(true, 'No unversioned Redis keys found in test namespace.');
  }
}
