<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsIndexRedirectTest extends TestCase
{
  #[Test]
  public function settingsRootUsesHttpRedirectNotInlineScript(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $indexPhp = (string) file_get_contents($projectRoot . '/html/settings/index.php');

    $this->assertStringContainsString("header('Location: ", $indexPhp);
    $this->assertStringContainsString('SettingsNav::defaultSubPageHref()', $indexPhp);
    $this->assertStringNotContainsString('<script', $indexPhp);
    $this->assertStringNotContainsString("require_once Environment::appHome() . 'html/header.php'", $indexPhp);
  }

  #[Test]
  public function legacyHashRedirectsRunFromSettingsFooterWithCspNonce(): void
  {
    $projectRoot = dirname(__DIR__, 3);
    $footerShared = (string) file_get_contents($projectRoot . '/html/settings/_partials/footer_shared.php');

    $this->assertStringContainsString('SettingsNav::legacyHashRedirects()', $footerShared);
    $this->assertStringContainsString('type="application/json"', $footerShared);
    $this->assertStringContainsString('id="settings-legacy-hash-redirects"', $footerShared);
    $this->assertStringNotContainsString('window.location.replace', $footerShared);
  }
}
