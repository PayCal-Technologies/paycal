<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Redis;

/**
 * Contract: Redis wrapper exposes compatibility constants and expected API surface.
 */
#[Group('contract')]
#[Group('redis')]
final class RedisWrapperContractTest extends TestCase
{
  public function testRedisWrapperExposesPrefixOptionConstant(): void
  {
    $this->assertTrue(defined('Redis::OPT_PREFIX'));
    $this->assertSame(\Redis::OPT_PREFIX, Redis::OPT_PREFIX);
  }
}
