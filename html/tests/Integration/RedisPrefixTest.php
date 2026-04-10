<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Redis;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[Group('integration')]
#[Group('redis')]
final class RedisPrefixTest extends TestCase
{
  private string $originalPrefix = '';

  protected function setUp(): void
  {
    parent::setUp();
    // Save original prefix
    $this->originalPrefix = getenv('REDIS_PREFIX') ?: '';
  }

  protected function tearDown(): void
  {
    // Restore original prefix
    putenv('REDIS_PREFIX='.$this->originalPrefix);

    // Reset cached instance so next test gets fresh connection
    $ref = new ReflectionClass('PayCal\Database');
    $prop = $ref->getProperty('writeInstance');
    $prop->setValue(null, null);

    parent::tearDown();
  }

  public function testDatabaseAppliesRedisPrefix(): void
  {
    // Reset any existing cached instance on Database
    $ref = new ReflectionClass('PayCal\Database');
    $prop = $ref->getProperty('writeInstance');
    $prop->setValue(null, null);

    // Set prefix and force a new connection
    putenv('REDIS_PREFIX=test:');

    $redis = Database::getWriteInstance();

    // Use the native Redis client's getOption method
    $this->assertSame('test:', $redis->client->getOption(Redis::OPT_PREFIX));
  }
}
