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
      ['accessibility', 'account', 'subscription', 'data', 'appearance', 'calendar', 'security', 'diagnostics'],
      $slugs
    );
  }

  #[Test]
  public function defaultSubPageIsAccessibility(): void
  {
    $this->assertSame('/settings/accessibility/', SettingsNav::defaultSubPageHref());
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

  #[Test]
  public function settingsShellUsesTabsAsSubpageSelectionAndLeavesHeadingsToPanels(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $shell = (string) file_get_contents($projectRoot . '/html/settings/_shell.php');
    $settingsCss = (string) file_get_contents($projectRoot . '/html/css/settings/index.php');

    $this->assertStringContainsString('settings_subnav_tab--active', $shell);
    $this->assertStringContainsString('aria-current="page"', $shell);
    $this->assertStringNotContainsString('settings_page_header', $shell);
    $this->assertStringNotContainsString('settings_page_title', $shell);
    $this->assertStringNotContainsString('settings_page_desc', $shell);
    $this->assertStringNotContainsString('settings-page-title', $shell);
    $this->assertStringNotContainsString('settings-page-desc', $shell);
    $this->assertStringNotContainsString('.settings_page_header', $settingsCss);
    $this->assertStringNotContainsString('.settings_page_title', $settingsCss);
    $this->assertStringNotContainsString('.settings_page_desc', $settingsCss);
    $this->assertStringContainsString('.settings_page_content > section.panel > h2', $settingsCss);
  }

  #[Test]
  public function settingsPanelHeadingsUseSharedCardTitleClass(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $settingsCss = (string) file_get_contents($projectRoot . '/html/css/settings/index.php');

    $this->assertStringContainsString('.settings_page_content h2.settings_card_title', $settingsCss);

    foreach ([
      'panel_calendar.php',
      'panel_security_passkeys.php',
      'panel_security_timeouts.php',
      'panel_data.php',
      'panel_diagnostics_advanced.php',
      'panel_security_federated.php',
      'panel_account_activity.php',
      'panel_account_danger.php',
    ] as $partial) {
      $contents = (string) file_get_contents($projectRoot . '/html/settings/_partials/' . $partial);
      $this->assertStringContainsString('settings_card_title', $contents, $partial . ' must use the shared settings panel heading style');
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
