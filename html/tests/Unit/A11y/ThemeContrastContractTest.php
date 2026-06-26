<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class ThemeContrastContractTest extends TestCase
{
  private const PAYCAL_CONTRAST_MIN = 4.75;

  #[Test]
  public function contrastMatrixDocumentsPayCalMinimum(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $matrix = (string) file_get_contents($projectRoot . '/docs/WCAG_THEME_CONTRAST_MATRIX.md');
    $matrixScript = (string) file_get_contents($projectRoot . '/scripts/generate-theme-contrast-matrix.js');

    $this->assertStringContainsString(
      sprintf('%.2f:1', self::PAYCAL_CONTRAST_MIN),
      $matrix,
      'WCAG_THEME_CONTRAST_MATRIX.md should document PayCal contrast minimum'
    );
    $this->assertStringContainsString(
      "process.env.PAYCAL_CONTRAST_MIN ?? '4.75'",
      $matrixScript,
      'Contrast matrix generator should default to PayCal minimum'
    );
  }

  #[Test]
  public function highTrafficCssAvoidsWorkEntryForeBeforeContrastColorOnBadges(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $calendarCss = (string) file_get_contents($projectRoot . '/html/css/calendar/index.php');
    $commonCss = (string) file_get_contents($projectRoot . '/html/css/common/index.php');

    $this->assertStringContainsString(
      'contrast-color(var(--public-beta-echo-bg)',
      $commonCss,
      'Public beta banner should use contrast-color on its mixed background'
    );
    $this->assertStringNotContainsString(
      'earnings-piegraphs-color-deductions',
      $calendarCss,
      'Calendar badges should not depend on earnings pie-graph scoped colors'
    );
    $this->assertDoesNotMatchRegularExpression(
      '/\.calendar_earnings_badge[\s\S]*?color:\s*var\(--work-entry-fore/s',
      $calendarCss
    );
  }

  #[Test]
  public function calendarBadgeTokensAreDeclaredInTokenScaffold(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $tokensCss = (string) file_get_contents($projectRoot . '/html/css/tokens/index.php');

    foreach ([
      '--calendar-earnings-badge-gross-bg',
      '--calendar-earnings-badge-net-bg',
      '--calendar-earnings-badge-deductions-bg',
      '--calendar-earnings-badge-entries-bg',
      '--calendar-earnings-badge-hours-bg',
    ] as $token) {
      $this->assertStringContainsString($token, $tokensCss);
    }
  }
}
