<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\RateLimiter;

/**
 * @internal
 */
#[Group('unit')]
final class RateLimiterIpTest extends TestCase
{
  #[Test]
  public function ipCalendarLimitReturnsExpectedShape(): void
  {
    $result = RateLimiter::checkIPCalendarLimit('127.0.0.1');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('allowed', $result);
    $this->assertArrayHasKey('remaining', $result);
    $this->assertIsBool($result['allowed']);
    $this->assertIsInt($result['remaining']);
  }
}
