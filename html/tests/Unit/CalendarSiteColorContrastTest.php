<?php declare(strict_types=1);

use PayCal\Domain\Config\SiteColorPalette;
use PayCal\Domain\ContrastColor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CalendarSiteColorContrastTest extends TestCase
{
  private const SITE_TINT_PERCENT = 22.0;
  private const WCAG_AA_NORMAL = 4.5;

  #[Test]
  public function calendarCssUsesRuntimeContrastForSiteTintedWorkEntries(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $calendarCss = (string) file_get_contents($projectRoot . '/html/css/calendar/index.php');

    $this->assertStringContainsString('contrast-color(var(--work-site-tint)', $calendarCss);
    $this->assertStringContainsString('var(--work-tint-mix-base', $calendarCss);
    $this->assertStringNotContainsString('$luma >= 155.0', $calendarCss);
  }

  #[Test]
  public function calendarCssUsesRuntimeContrastForEarningsBadges(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $calendarCss = (string) file_get_contents($projectRoot . '/html/css/calendar/index.php');

    $this->assertStringContainsString('contrast-color(var(--earnings-badge-bg)', $calendarCss);
    $this->assertStringContainsString('contrast-color(var(--entries-badge-bg)', $calendarCss);
    $this->assertStringContainsString('contrast-color(var(--hours-badge-bg)', $calendarCss);
  }

  #[Test]
  public function siteTintedWorkEntriesMeetWcagAaAcrossThemeWorkEntryBacks(): void
  {
    $failures = [];

    foreach ($this->themeWorkEntryBackHexes() as $theme => $workEntryBack) {
      foreach (SiteColorPalette::pickerPalette() as $entry) {
        $siteColor = ContrastColor::normalizeHex($entry['hex']);
        $tintedBack = ContrastColor::mixHex($siteColor, $workEntryBack, self::SITE_TINT_PERCENT);
        $foreground = ContrastColor::foregroundForBackground($tintedBack);
        $ratio = ContrastColor::contrastRatio($foreground, $tintedBack);

        if ($ratio < self::WCAG_AA_NORMAL) {
          $failures[] = sprintf(
            '%s × %s tinted %s → %s on %s (%.2f:1)',
            $theme,
            $siteColor,
            $tintedBack,
            $foreground,
            $workEntryBack,
            $ratio
          );
        }
      }
    }

    $this->assertSame([], $failures, "Site-tinted work entries below WCAG AA:\n".implode("\n", $failures));
  }

  /**
   * @return array<string, string>
   */
  private function themeWorkEntryBackHexes(): array
  {
    $projectRoot = dirname(__DIR__, 3);
    $cssRoot = $projectRoot . '/html/css';
    $themes = [];

    foreach (scandir($cssRoot) ?: [] as $entry) {
      if (!preg_match('/_(dark|light)$/', $entry)) {
        continue;
      }

      $file = $cssRoot . '/' . $entry . '/index.php';
      if (!is_file($file)) {
        continue;
      }

      $hex = $this->resolveWorkEntryBackHex((string) file_get_contents($file), $cssRoot);
      if ($hex !== null) {
        $themes[$entry] = $hex;
      }
    }

    $this->assertNotEmpty($themes);

    return $themes;
  }

  private function resolveWorkEntryBackHex(string $themeSource, string $cssRoot): ?string
  {
    if (preg_match('/^\s*--work-entry-back\s*:\s*([^;]+);/m', $themeSource, $match) === 1) {
      return $this->resolveTokenToHex(trim($match[1]), $themeSource, $cssRoot);
    }

    $tokens = (string) file_get_contents($cssRoot . '/tokens/index.php');
    if (preg_match('/^\s*--work-entry-back\s*:\s*([^;]+);/m', $tokens, $match) === 1) {
      return $this->resolveTokenToHex(trim($match[1]), $tokens, $cssRoot);
    }

    return null;
  }

  private function resolveTokenToHex(string $raw, string $source, string $cssRoot): ?string
  {
    if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $raw) === 1) {
      return ContrastColor::normalizeHex($raw);
    }

    if (preg_match('/^var\((--[a-z0-9-]+)(?:,\s*([^)]+))?\)$/i', $raw, $match) === 1) {
      $token = $match[1];
      $fallback = $match[2] ?? null;

      if (preg_match('/^\s*'.$token.'\s*:\s*([^;]+);/m', $source, $tokenMatch) === 1) {
        return $this->resolveTokenToHex(trim($tokenMatch[1]), $source, $cssRoot);
      }

      if ($fallback !== null) {
        return $this->resolveTokenToHex(trim($fallback), $source, $cssRoot);
      }

      $tokens = (string) file_get_contents($cssRoot . '/tokens/index.php');
      if (preg_match('/^\s*'.preg_quote($token, '/').'\s*:\s*([^;]+);/m', $tokens, $tokenMatch) === 1) {
        return $this->resolveTokenToHex(trim($tokenMatch[1]), $tokens, $cssRoot);
      }
    }

    return null;
  }
}
