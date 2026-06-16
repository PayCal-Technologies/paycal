<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class AdminNavLabelKeysExistInLocaleTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  /**
   * @return list<string>
   */
  private function adminSurfaceNavLabelKeys(): array
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require $this->projectRoot() . '/html/extensions/overrides/admin-surface/manifest.php';
    $navLinks = $manifest['capabilities']['admin.nav.links'] ?? [];
    $this->assertIsArray($navLinks);

    $keys = [];
    foreach ($navLinks as $link) {
      if (!is_array($link)) {
        continue;
      }
      $labelKey = trim((string) ($link['label_key'] ?? ''));
      if ($labelKey !== '') {
        $keys[] = $labelKey;
      }
    }

    return array_values(array_unique($keys));
  }

  /**
   * @return list<string>
   */
  private function localeKeys(string $locale): array
  {
    $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');
    preg_match_all('/^([A-Z0-9_]+) /m', $strings, $matches);

    return $matches[1] ?? [];
  }

  #[Test]
  public function adminSurfaceManifestNavLabelKeysExistInEnglishLocale(): void
  {
    $englishKeys = $this->localeKeys('en');

    foreach ($this->adminSurfaceNavLabelKeys() as $labelKey) {
      $this->assertContains(
        $labelKey,
        $englishKeys,
        sprintf('Admin nav label_key %s must exist in strings/en.txt', $labelKey),
      );
    }
  }

  #[Test]
  public function adminSurfaceManifestNavLabelKeysAreTranslatedInAllLocales(): void
  {
    $locales = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];
    $labelKeys = $this->adminSurfaceNavLabelKeys();

    foreach ($locales as $locale) {
      $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');

      foreach ($labelKeys as $labelKey) {
        $this->assertMatchesRegularExpression(
          '/^' . preg_quote($labelKey, '/') . ' .+/m',
          $strings,
          sprintf('Missing or empty %s in %s.txt', $labelKey, $locale),
        );
      }
    }
  }

  #[Test]
  public function auditedLabelKeySourcesExistInEnglishLocale(): void
  {
    $englishKeys = $this->localeKeys('en');
    $requiredKeys = array_merge(
      [
        'BUSINESS_NAV_DASHBOARD',
        'BUSINESS_NAV_DETAILS',
        'BUSINESS_NAV_MEMBERS',
        'BUSINESS_NAV_SITES',
        'BUSINESS_NAV_PAYROLL',
        'BUSINESS_NAV_AUDIT',
        'BUSINESS_NAV_REPORTS',
        'BUSINESS_DASHBOARD_METRIC_MEMBERS',
        'BUSINESS_DASHBOARD_METRIC_SITES',
        'BUSINESS_DASHBOARD_METRIC_PENDING_INVITES',
        'BUSINESS_DASHBOARD_METRIC_PENDING_REQUESTS',
        'BUSINESS_DASHBOARD_METRIC_WORK_TODAY',
        'BUSINESS_DASHBOARD_METRIC_WORK_WEEK',
        'BUSINESS_DASHBOARD_METRIC_LAST_ACTIVITY',
        'BUSINESS_DASHBOARD_METRIC_CREATED',
        'EARNINGS_FORECAST_NEXT_PAYCHECK',
        'EARNINGS_FORECAST_NEXT_30_DAYS',
        'EARNINGS_FORECAST_YEAR_PROJECTION',
        'EARNINGS_FORECAST_ASSUMP_WAGE',
        'EARNINGS_FORECAST_ASSUMP_REG_HRS',
        'EARNINGS_FORECAST_ASSUMP_OT_HRS',
        'EARNINGS_FORECAST_ASSUMP_LOA',
        'EARNINGS_FORECAST_ASSUMP_TRAVEL',
        'EARNINGS_FORECAST_ASSUMP_PROVINCE',
        'EARNINGS_FORECAST_ASSUMP_PAY_FREQ',
        'EARNINGS_FORECAST_ASSUMP_ANCHOR',
        'EARNINGS_FORECAST_ASSUMP_YTD_GROSS',
      ],
      $this->adminSurfaceNavLabelKeys(),
    );

    foreach (array_values(array_unique($requiredKeys)) as $labelKey) {
      $this->assertContains(
        $labelKey,
        $englishKeys,
        sprintf('Audited label_key %s must exist in strings/en.txt', $labelKey),
      );
    }
  }

  #[Test]
  public function publicBusinessPreviewKeysAreTranslatedInAllLocales(): void
  {
    $locales = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];
    $requiredKeys = [
      'BUSINESS_PUBLIC_PREVIEW_NAME',
      'BUSINESS_PUBLIC_PREVIEW_LEAD',
      'BUSINESS_PUBLIC_PREVIEW_DETAILS',
      'BUSINESS_PUBLIC_PREVIEW_MEMBERS',
      'BUSINESS_PUBLIC_PREVIEW_SITES',
      'BUSINESS_PUBLIC_PREVIEW_PAYROLL',
      'BUSINESS_PUBLIC_PREVIEW_REPORTS',
      'BUSINESS_PUBLIC_PREVIEW_AUDIT',
      'PUBLIC_EXTENSION_DISCLAIMER',
    ];

    foreach ($locales as $locale) {
      $strings = (string) file_get_contents($this->projectRoot() . '/strings/' . $locale . '.txt');

      foreach ($requiredKeys as $requiredKey) {
        $this->assertMatchesRegularExpression(
          '/^' . preg_quote($requiredKey, '/') . ' .+/m',
          $strings,
          sprintf('Missing or empty %s in %s.txt', $requiredKey, $locale),
        );
      }
    }
  }

  #[Test]
  public function calendarIndexMainLandmarkUsesTranslatedPageLabel(): void
  {
    $indexPage = (string) file_get_contents($this->projectRoot() . '/html/index.php');

    $this->assertStringContainsString("\$pageLabel = html_index_i18n('CALENDAR');", $indexPage);
    $this->assertStringNotContainsString("\$pageLabel = 'CALENDAR';", $indexPage);
  }
}
