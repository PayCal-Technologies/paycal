<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\WorkEntryLockService;

/**
 * @internal
 */
#[Group('unit')]
final class WorkEntryLockServiceCacheTest extends TestCase
{
  #[Test]
  public function utcTodayReturnsIsoDate(): void
  {
    $method = new \ReflectionMethod(WorkEntryLockService::class, 'utcToday');

    $date = $method->invoke(null);

    $this->assertIsString($date);
    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
  }

  #[Test]
  public function secondsUntilMidnightReturnsBoundedPositiveTtl(): void
  {
    $method = new \ReflectionMethod(WorkEntryLockService::class, 'secondsUntilMidnight');

    $ttl = $method->invoke(null);

    $this->assertIsInt($ttl);
    $this->assertGreaterThanOrEqual(60, $ttl);
    $this->assertLessThanOrEqual(86400, $ttl);
  }
}
