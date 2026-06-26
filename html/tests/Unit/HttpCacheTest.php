<?php declare(strict_types=1);

use PayCal\Domain\HttpCache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class HttpCacheTest extends TestCase
{
  protected function tearDown(): void
  {
    $_GET = [];
    parent::tearDown();
  }

  #[Test]
  public function hasVersionQueryRequiresNonEmptyVParam(): void
  {
    $_GET = [];
    $this->assertFalse(HttpCache::hasVersionQuery());

    $_GET['v'] = '1.059.009';
    $this->assertTrue(HttpCache::hasVersionQuery());

    $_GET['v'] = '   ';
    $this->assertFalse(HttpCache::hasVersionQuery());
  }
}
