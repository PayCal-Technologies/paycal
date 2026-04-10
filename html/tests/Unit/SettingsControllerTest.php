<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Controllers\SettingsController;
use PHPUnit\Framework\Attributes\Group;

/**
 * SettingsControllerTest
 */
#[Group('unit')]
final class SettingsControllerTest extends TestCase
{
  /**
   * @param array<string, string> $input
   * @return array<string, string>
   */
  private function normalize(array $input): array
  {
    $method = new ReflectionMethod(SettingsController::class, 'normalizeThemeVariant');

    /** @var array<string, string> $result */
    $result = $method->invoke(null, $input);

    return $result;
  }

  /**
   * @param array<string, mixed> $input
   * @return array<string, mixed>
   */
  private function normalizePayPeriod(array $input): array
  {
    $method = new ReflectionMethod(SettingsController::class, 'normalizePayPeriodPreferences');

    /** @var array<string, mixed> $result */
    $result = $method->invoke(null, $input);

    return $result;
  }

  /**
   * @param array<string, mixed> $input
   * @return array<string, mixed>
   */
  private function normalizeNavigation(array $input): array
  {
    $method = new ReflectionMethod(SettingsController::class, 'normalizeNavigationPreferences');

    /** @var array<string, mixed> $result */
    $result = $method->invoke(null, $input);

    return $result;
  }

  #[Test]
  public function normalizeSplitsLegacyCombinedThemeValue(): void
  {
    $result = $this->normalize(['theme' => 'macos_light']);

    $this->assertSame('macos', $result['theme']);
    $this->assertSame('light', $result['variant']);
  }

  #[Test]
  public function normalizeAppliesDefaultsWhenFieldsMissing(): void
  {
    $result = $this->normalize([]);

    $this->assertArrayNotHasKey('theme', $result);
    $this->assertArrayNotHasKey('variant', $result);
  }

  #[Test]
  public function normalizeBackfillsThemeWhenOnlyVariantProvided(): void
  {
    $result = $this->normalize(['variant' => 'light']);

    $this->assertSame('paycal_black', $result['theme']);
    $this->assertSame('light', $result['variant']);
  }

  #[Test]
  public function normalizeCoercesInvalidThemeAndVariant(): void
  {
    $result = $this->normalize([
      'theme' => 'not-a-theme',
      'variant' => 'sepia',
    ]);

    $this->assertSame('paycal_blue', $result['theme']);
    $this->assertSame('dark', $result['variant']);
  }

  #[Test]
  public function normalizeKeepsValidExplicitValues(): void
  {
    $result = $this->normalize([
      'theme' => 'win95',
      'variant' => 'dark',
    ]);

    $this->assertSame('win95', $result['theme']);
    $this->assertSame('dark', $result['variant']);

    $paycalBlueResult = $this->normalize([
      'theme' => 'paycal_blue',
      'variant' => 'light',
    ]);

    $this->assertSame('paycal_blue', $paycalBlueResult['theme']);
    $this->assertSame('light', $paycalBlueResult['variant']);

    $paycalBlackResult = $this->normalize([
      'theme' => 'paycal_black',
      'variant' => 'dark',
    ]);

    $this->assertSame('paycal_black', $paycalBlackResult['theme']);
    $this->assertSame('dark', $paycalBlackResult['variant']);

    $paycalRedResult = $this->normalize([
      'theme' => 'paycal_red',
      'variant' => 'light',
    ]);

    $this->assertSame('paycal_red', $paycalRedResult['theme']);
    $this->assertSame('light', $paycalRedResult['variant']);

    $paycalGreenResult = $this->normalize([
      'theme' => 'paycal_green',
      'variant' => 'dark',
    ]);

    $this->assertSame('paycal_green', $paycalGreenResult['theme']);
    $this->assertSame('dark', $paycalGreenResult['variant']);

    $paycalWhiteResult = $this->normalize([
      'theme' => 'paycal_white',
      'variant' => 'light',
    ]);

    $this->assertSame('paycal_white', $paycalWhiteResult['theme']);
    $this->assertSame('light', $paycalWhiteResult['variant']);

    $xpResult = $this->normalize([
      'theme' => 'winxp',
      'variant' => 'light',
    ]);

    $this->assertSame('winxp', $xpResult['theme']);
    $this->assertSame('light', $xpResult['variant']);

    $system7Result = $this->normalize([
      'theme' => 'system7',
      'variant' => 'dark',
    ]);

    $this->assertSame('system7', $system7Result['theme']);
    $this->assertSame('dark', $system7Result['variant']);

    $macos9Result = $this->normalize([
      'theme' => 'macos9',
      'variant' => 'light',
    ]);

    $this->assertSame('macos9', $macos9Result['theme']);
    $this->assertSame('light', $macos9Result['variant']);

    $system8Result = $this->normalize([
      'theme' => 'system8',
      'variant' => 'dark',
    ]);

    $this->assertSame('system8', $system8Result['theme']);
    $this->assertSame('dark', $system8Result['variant']);

    $mintResult = $this->normalize([
      'theme' => 'mint',
      'variant' => 'light',
    ]);

    $this->assertSame('mint', $mintResult['theme']);
    $this->assertSame('light', $mintResult['variant']);

    $fedoraResult = $this->normalize([
      'theme' => 'fedora',
      'variant' => 'dark',
    ]);

    $this->assertSame('fedora', $fedoraResult['theme']);
    $this->assertSame('dark', $fedoraResult['variant']);

    $debianResult = $this->normalize([
      'theme' => 'debian',
      'variant' => 'light',
    ]);

    $this->assertSame('debian', $debianResult['theme']);
    $this->assertSame('light', $debianResult['variant']);

    $beosResult = $this->normalize([
      'theme' => 'beos',
      'variant' => 'dark',
    ]);

    $this->assertSame('beos', $beosResult['theme']);
    $this->assertSame('dark', $beosResult['variant']);

    $zetaResult = $this->normalize([
      'theme' => 'zeta',
      'variant' => 'light',
    ]);

    $this->assertSame('zeta', $zetaResult['theme']);
    $this->assertSame('light', $zetaResult['variant']);

    $haikuResult = $this->normalize([
      'theme' => 'haiku',
      'variant' => 'dark',
    ]);

    $this->assertSame('haiku', $haikuResult['theme']);
    $this->assertSame('dark', $haikuResult['variant']);

    $bladeRunnerResult = $this->normalize([
      'theme' => 'blade_runner',
      'variant' => 'dark',
    ]);

    $this->assertSame('blade_runner', $bladeRunnerResult['theme']);
    $this->assertSame('dark', $bladeRunnerResult['variant']);

    $spaceOdysseyResult = $this->normalize([
      'theme' => 'space_odyssey',
      'variant' => 'light',
    ]);

    $this->assertSame('space_odyssey', $spaceOdysseyResult['theme']);
    $this->assertSame('light', $spaceOdysseyResult['variant']);

    $tronResult = $this->normalize([
      'theme' => 'tron',
      'variant' => 'dark',
    ]);

    $this->assertSame('tron', $tronResult['theme']);
    $this->assertSame('dark', $tronResult['variant']);

    $fifthElementResult = $this->normalize([
      'theme' => 'fifth_element',
      'variant' => 'light',
    ]);

    $this->assertSame('fifth_element', $fifthElementResult['theme']);
    $this->assertSame('light', $fifthElementResult['variant']);

    $duneResult = $this->normalize([
      'theme' => 'dune',
      'variant' => 'dark',
    ]);

    $this->assertSame('dune', $duneResult['theme']);
    $this->assertSame('dark', $duneResult['variant']);

    $matrixResult = $this->normalize([
      'theme' => 'matrix',
      'variant' => 'light',
    ]);

    $this->assertSame('matrix', $matrixResult['theme']);
    $this->assertSame('light', $matrixResult['variant']);

    $alienResult = $this->normalize([
      'theme' => 'alien',
      'variant' => 'dark',
    ]);

    $this->assertSame('alien', $alienResult['theme']);
    $this->assertSame('dark', $alienResult['variant']);

    $akiraResult = $this->normalize([
      'theme' => 'akira',
      'variant' => 'light',
    ]);

    $this->assertSame('akira', $akiraResult['theme']);
    $this->assertSame('light', $akiraResult['variant']);
  }

  #[Test]
  public function normalizePayPeriodKeepsValidStartAndSetsEpoch(): void
  {
    $result = $this->normalizePayPeriod([
      'pay_period_start' => '2026-03-16',
    ]);

    $this->assertSame('2026-03-16', $result['pay_period_start']);
    $this->assertSame('2026-03-16', $result['pay_epoch']);
  }

  #[Test]
  public function normalizePayPeriodInvalidStartDoesNotOverwrite(): void
  {
    $result = $this->normalizePayPeriod([
      'pay_period_start' => '03/16/2026',
    ]);

    $this->assertArrayNotHasKey('pay_period_start', $result);
    $this->assertArrayNotHasKey('pay_epoch', $result);
  }

  #[Test]
  public function normalizeNavigationKeepsValidPositions(): void
  {
    $result = $this->normalizeNavigation([
      'nav_position_primary' => 'left',
    ]);

    $this->assertSame('left', $result['nav_position_primary']);
  }

  #[Test]
  public function normalizeNavigationLowercasesValidPositions(): void
  {
    $result = $this->normalizeNavigation([
      'nav_position_primary' => 'RIGHT',
    ]);

    $this->assertSame('right', $result['nav_position_primary']);
  }

  #[Test]
  public function normalizeNavigationFallsBackForInvalidPositions(): void
  {
    $result = $this->normalizeNavigation([
      'nav_position_primary' => 'ceiling',
    ]);

    $this->assertSame('left', $result['nav_position_primary']);
  }
}
