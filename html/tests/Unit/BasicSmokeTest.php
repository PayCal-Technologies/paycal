<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BasicSmokeTest extends TestCase
{
  #[Test]
  public function basicSanityCheckPasses(): void
  {
    $this->assertTrue(true);
  }
}