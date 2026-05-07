<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Infrastructure\RateControl\RateLimiter;

/**
 * @internal
 */
#[Group('unit')]
final class RateLimiterTelemetryTest extends TestCase
{
  #[Test]
  public function telemetryLimitReturnsExpectedShape(): void
  {
    $userUUID = 'Utest-telemetry-limit';

    RateLimiter::clearLimit($userUUID, 'telemetry');
    $result = RateLimiter::checkTelemetryLimit($userUUID);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('allowed', $result);
    $this->assertArrayHasKey('remaining', $result);
    $this->assertIsBool($result['allowed']);
    $this->assertIsInt($result['remaining']);
    $this->assertGreaterThanOrEqual(0, $result['remaining']);
    $this->assertLessThanOrEqual(89, $result['remaining']);
  }
}
