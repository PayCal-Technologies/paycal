<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsIndexRedirectTest extends TestCase
{
  #[Test]
  public function settingsRootRendersDashboardNotRedirect(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $indexPhp = (string) file_get_contents($projectRoot . '/html/settings/index.php');

    $this->assertStringContainsString("\$currentPage = 'PAGE_SETTINGS'", $indexPhp);
    $this->assertStringContainsString("require __DIR__ . '/_partials/panel_dashboard.php'", $indexPhp);
    $this->assertStringNotContainsString("header('Location: ", $indexPhp);
    $this->assertStringNotContainsString('SettingsNav::defaultSubPageHref()', $indexPhp);
    $this->assertStringNotContainsString('<script', $indexPhp);
  }

  #[Test]
  public function settingsFooterDoesNotEmitLegacyHashRedirectPayload(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $footerShared = (string) file_get_contents($projectRoot . '/html/settings/_partials/footer_shared.php');
    $settingsJs = (string) file_get_contents($projectRoot . '/html/js/settings/index.php');

    $this->assertStringNotContainsString('SettingsNav::legacyHashRedirects()', $footerShared);
    $this->assertStringNotContainsString('settings-legacy-hash-redirects', $footerShared);
    $this->assertStringNotContainsString('settings-legacy-hash-redirects', $settingsJs);
    $this->assertStringNotContainsString('window.location.replace', $footerShared);
    $this->assertStringNotContainsString('window.location.replace', $settingsJs);
  }
}
