<?php declare(strict_types=1);

use PayCal\Domain\ContrastColor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ContrastColorTest extends TestCase
{
  #[Test]
  public function contrastRatioIsSymmetric(): void
  {
    $ratio = ContrastColor::contrastRatio('#FFFFFF', '#111111');

    $this->assertGreaterThan(15.0, $ratio);
    $this->assertEqualsWithDelta(
      ContrastColor::contrastRatio('#111111', '#FFFFFF'),
      $ratio,
      0.0001
    );
  }

  #[Test]
  public function mixHexBlendsTwoColors(): void
  {
    $mixed = ContrastColor::mixHex('#FFFFFF', '#000000', 50.0);

    $this->assertSame('#808080', $mixed);
  }

  #[Test]
  public function foregroundForBackgroundPicksHigherContrastCandidate(): void
  {
    $this->assertSame('#FFFFFF', ContrastColor::foregroundForBackground('#1E2330'));
    $this->assertSame('#111111', ContrastColor::foregroundForBackground('#F4F8FF'));
  }
}
