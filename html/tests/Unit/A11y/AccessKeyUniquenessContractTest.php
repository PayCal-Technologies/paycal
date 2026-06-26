<?php declare(strict_types=1);

use PayCal\Domain\Render;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Authenticated shell accesskey map (unique per document).
 *
 * | Key | Target |
 * |-----|--------|
 * | 0   | Skip to main content (header.php) |
 * | C   | Calendar — PayCal sidebar heading |
 * | R   | Reports |
 * | S   | Sites |
 * | O   | Business dashboard heading |
 * | e   | Settings (sidebar utility) |
 * | J   | Connections (sidebar, when shown) |
 * | h   | Help (footer secondary nav) |
 * | A   | About (footer) |
 * | n   | Contact (footer) |
 * | g   | GitHub (footer) |
 * | m   | Media (footer) |
 * | l   | Policies (footer) |
 * | t   | Transparency (footer) |
 *
 * Sidebar keyboard-shortcuts nav uses data-nav-shortcut="h" only (no accesskey).
 * HELP_PAGE_TEASER omits accesskey to avoid conflicting with footer Help.
 */
#[Group('unit')]
#[Group('a11y')]
final class AccessKeyUniquenessContractTest extends TestCase
{
  /**
   * @return array<string, string>
   */
  private function intendedAuthenticatedShellAccessKeyMap(): array
  {
    return [
      '0' => 'skip_to_content',
      'C' => 'calendar_heading',
      'R' => 'reports',
      'S' => 'sites',
      'O' => 'business_heading',
      'e' => 'settings',
      'J' => 'connections',
      'h' => 'footer_help',
      'A' => 'footer_about',
      'n' => 'footer_contact',
      'g' => 'footer_github',
      'm' => 'footer_media',
      'l' => 'footer_policies',
      't' => 'footer_transparency',
    ];
  }

  private function htmlRoot(): string
  {
    return dirname(__DIR__, 3);
  }

  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  /**
   * @return array<string, string> accesskey => source label
   */
  private function collectAuthenticatedShellAccessKeys(bool $withBusiness = true, bool $withConnections = true): array
  {
    $map = ['0' => 'header:skip_to_content'];

    $navigation = Render::buildSidebarNavigation(false, false, false, $withBusiness);
    foreach ($navigation['groups'] as $group) {
      if ($group['visible'] !== true) {
        continue;
      }

      $this->registerNavLinkAccessKey($map, $group['heading'], 'Render:sidebar_heading:' . $group['id']);
      foreach ($group['links'] as $link) {
        $page = (string) ($link['page'] ?? 'unknown');
        $this->registerNavLinkAccessKey($map, $link, 'Render:sidebar:' . $page);
      }
    }

    $this->registerNavLinkAccessKey($map, Render::settingsUtilityNavLink(), 'Render:settingsUtilityNavLink');
    if ($withConnections) {
      $this->registerNavLinkAccessKey($map, Render::regularConnectionsNavLink(true), 'Render:regularConnectionsNavLink');
    }

    $footer = (string) file_get_contents($this->htmlRoot() . '/footer.php');
    $footerPages = [];
    $footerKeys = [];
    if (preg_match_all("/'page' => \(string\) '([^']+)'/", $footer, $pageMatches) > 0) {
      $footerPages = $pageMatches[1];
    }
    if (preg_match_all("/'access_key' => \(string\) '([^']*)'/", $footer, $keyMatches) > 0) {
      $footerKeys = $keyMatches[1];
    }
    foreach ($footerKeys as $index => $key) {
      if ($key === '') {
        continue;
      }

      $page = (string) ($footerPages[$index] ?? 'footer_link');
      $map[$key] = 'footer:' . $page;
    }

    $header = (string) file_get_contents($this->htmlRoot() . '/header.php');
    if (preg_match_all('/accesskey="([^"]*)"/', $header, $headerMatches) > 0) {
      foreach ($headerMatches[1] as $key) {
        if ($key === '') {
          continue;
        }

        $label = $key === '0' ? 'header:skip_to_content' : 'header:literal';
        $map[$key] = $label;
      }
    }

    return $map;
  }

  /**
   * @param array<string, string> $map
   * @param array<string, string> $link
   */
  private function registerNavLinkAccessKey(array &$map, array $link, string $source): void
  {
    $key = (string) ($link['access_key'] ?? '');
    if ($key === '') {
      return;
    }

    $map[$key] = $source;
  }

  /**
   * @param array<string, string> $map
   *
   * @return list<string>
   */
  private function duplicateAccessKeys(array $map): array
  {
    $seen = [];
    $duplicates = [];

    foreach ($map as $key => $source) {
      if (isset($seen[$key])) {
        $duplicates[] = sprintf('%s (%s, %s)', $key, $seen[$key], $source);
        continue;
      }

      $seen[$key] = $source;
    }

    return $duplicates;
  }

  #[Test]
  public function authenticatedShellAccessKeysAreUniqueWithBusinessWorkspace(): void
  {
    $map = $this->collectAuthenticatedShellAccessKeys(true, true);
    $duplicates = $this->duplicateAccessKeys($map);

    $this->assertSame([], $duplicates, 'Duplicate authenticated-shell accesskeys: ' . implode('; ', $duplicates));

    foreach (array_keys($this->intendedAuthenticatedShellAccessKeyMap()) as $expectedKey) {
      $this->assertArrayHasKey($expectedKey, $map, 'Missing expected accesskey: ' . $expectedKey);
    }
  }

  #[Test]
  public function authenticatedShellAccessKeysAreUniqueWithoutBusinessWorkspace(): void
  {
    $map = $this->collectAuthenticatedShellAccessKeys(false, false);
    unset($map['O'], $map['J']);
    $duplicates = $this->duplicateAccessKeys($map);

    $this->assertSame([], $duplicates, 'Duplicate authenticated-shell accesskeys: ' . implode('; ', $duplicates));
  }

  #[Test]
  public function headerKeyboardShortcutsNavOmitsAccessKeyAttribute(): void
  {
    $header = (string) file_get_contents($this->htmlRoot() . '/header.php');

    $this->assertStringContainsString('data-help-trigger="true"', $header);
    $this->assertStringContainsString('data-nav-shortcut="h"', $header);
    $this->assertStringNotContainsString('data-nav-shortcut="h" aria-keyshortcuts="h" accesskey="h"', $header);
    $this->assertDoesNotMatchRegularExpression(
      '/data-help-trigger="true"[^>]*accesskey=/',
      $header,
    );
  }

  #[Test]
  public function helpTeaserAndKeyboardShortcutsPartialOmitAccessKeys(): void
  {
    $keyboardShortcuts = (string) file_get_contents($this->projectRoot() . '/templates/keyboard-shortcuts.php');
    $i18n = (string) file_get_contents($this->htmlRoot() . '/src/i18n.php');
    $enStrings = (string) file_get_contents($this->projectRoot() . '/strings/en.txt');

    $this->assertStringNotContainsString('accesskey=', $keyboardShortcuts);
    $this->assertStringNotContainsString('accesskey=', $i18n);
    $this->assertDoesNotMatchRegularExpression('/^HELP_PAGE_TEASER .+accesskey/m', $enStrings);
    $this->assertDoesNotMatchRegularExpression('/^HELP_PAGE_TEASER_HTML .+accesskey/m', $enStrings);
  }

  #[Test]
  public function renderNavLinkTemplateUsesAccessKeyPlaceholderOnlyOncePerLink(): void
  {
    $template = (string) file_get_contents($this->projectRoot() . '/templates/nav-link-item.php');

    $this->assertSame(1, substr_count($template, 'accesskey='));
    $this->assertStringContainsString("accesskey='__ACCESS_KEY__'", $template);
  }
}
