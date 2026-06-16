<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\SettingsNav;

#[Group('unit')]
final class SettingsNavTest extends TestCase
{
  #[Test]
  public function subNavTabsIncludePhaseOneSections(): void
  {
    $slugs = array_map(static fn (array $tab): string => $tab['slug'], SettingsNav::subNavTabs());

    $this->assertSame(
      ['account', 'calendar', 'appearance', 'accessibility', 'security', 'data', 'diagnostics'],
      $slugs
    );
  }

  #[Test]
  public function legacyHashRedirectsMapOldPanelsToSubPages(): void
  {
    $redirects = SettingsNav::legacyHashRedirects();

    $this->assertSame('/settings/account/', $redirects['account']);
    $this->assertSame('/settings/account/', $redirects['panel-billing']);
    $this->assertSame('/settings/calendar/', $redirects['panel-pay-period']);
    $this->assertSame('/settings/calendar/', $redirects['panel-account-work-defaults']);
    $this->assertSame('/settings/calendar/', $redirects['panel-calendar-work-defaults']);
    $this->assertSame('/settings/calendar/', $redirects['panel-calendar']);
    $this->assertSame('/settings/appearance/', $redirects['panel-style']);
    $this->assertSame('/settings/accessibility/', $redirects['panel-audio']);
    $this->assertSame('/settings/security/', $redirects['panel-passkeys']);
    $this->assertSame('/settings/data/', $redirects['panel-data-portability']);
    $this->assertSame('/settings/diagnostics/', $redirects['panel-debugging']);
  }

  #[Test]
  public function defaultSubPageIsAccount(): void
  {
    $this->assertSame('/settings/account/', SettingsNav::defaultSubPageHref());
  }

  #[Test]
  public function subNavTabI18nKeysResolveInEnglishLocaleFile(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $enMap = $this->loadLocaleMap($projectRoot . '/strings/en.txt');
    $requiredKeys = [
      'SETTINGS_NAV_ARIA',
      'SETTINGS_REDIRECT_LOADING',
      'SETTINGS_ACCESSIBILITY_TYPOGRAPHY_FORM_ARIA',
      'SETTINGS_DIAGNOSTICS_BASIC_TITLE',
      'SETTINGS_DIAGNOSTICS_BASIC_DESC',
      'SETTINGS_DIAGNOSTICS_COPY_SUPPORT_INFO',
      'SETTINGS_DIAGNOSTICS_LINK_TROUBLESHOOTING',
      'SETTINGS_DIAGNOSTICS_LINK_ISSUE_REPORT',
    ];

    foreach (SettingsNav::subNavTabs() as $tab) {
      $requiredKeys[] = (string) $tab['label_key'];
      $requiredKeys[] = (string) $tab['title_key'];
      $requiredKeys[] = (string) $tab['desc_key'];
    }

    foreach (array_unique($requiredKeys) as $key) {
      $this->assertArrayHasKey($key, $enMap, $key . ' must exist in strings/en.txt');
      $this->assertNotSame($key, $enMap[$key], $key . ' must not fall back to the raw key name');
      $this->assertNotSame('', trim($enMap[$key]), $key . ' must have a non-empty value');
    }
  }

  /**
   * @return array<string, string>
   */
  private function loadLocaleMap(string $filePath): array
  {
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
      $this->fail('Could not read locale file: ' . $filePath);
    }

    $map = [];
    foreach ($lines as $line) {
      if ($line === '' || $line[0] === '#') {
        continue;
      }

      $parts = preg_split('/\s+/', $line, 2);
      if (!is_array($parts) || count($parts) !== 2) {
        continue;
      }

      $map[$parts[0]] = $parts[1];
    }

    return $map;
  }
}
