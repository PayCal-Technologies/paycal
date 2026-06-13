<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: business-surface override manifest keeps required capability schema.
 */
#[Group('contract')]
final class BusinessSurfaceManifestContractTest extends TestCase
{
  public function testPublicBusinessSurfaceManifestHasRequiredCapabilities(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/business-surface/manifest.php';

    $this->assertSame('business-surface', $manifest['id'] ?? null);
    $this->assertTrue((bool) ($manifest['enabled'] ?? false));
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['business.surface.enabled'] ?? null);

    $pagePaths = $manifest['capabilities']['business.page.paths'] ?? null;
    $this->assertIsArray($pagePaths);
    $this->assertContains('/business/', $pagePaths);
    $this->assertContains('/business/details/', $pagePaths);

    $navTabs = $manifest['capabilities']['business.nav.tabs'] ?? null;
    $this->assertIsArray($navTabs);
    $this->assertNotEmpty($navTabs);
  }

  public function testBusinessPagesIncludePublicExtensionDisclaimerPartial(): void
  {
    $projectRoot = dirname(__DIR__, 2);
    $dashboard = (string) file_get_contents($projectRoot . '/business/index.php');
    $details = (string) file_get_contents($projectRoot . '/business/details/index.php');

    $this->assertStringContainsString('_partials/extension_disclaimer.php', $dashboard);
    $this->assertStringContainsString('_partials/extension_disclaimer.php', $details);
    $this->assertStringNotContainsString('style=', $dashboard);
    $this->assertStringNotContainsString('style=', $details);
  }
}
