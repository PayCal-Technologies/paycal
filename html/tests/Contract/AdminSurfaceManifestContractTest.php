<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: admin-surface override manifests keep required capability schema.
 */
#[Group('contract')]
final class AdminSurfaceManifestContractTest extends TestCase
{
  public function testPrivateAdminSurfaceManifestHasRequiredCapabilities(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/admin-surface/manifest.php';

    $this->assertSame('admin-surface', $manifest['id'] ?? null);
    $this->assertTrue((bool) ($manifest['enabled'] ?? false));
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['admin.surface.enabled'] ?? null);

    $pagePaths = $manifest['capabilities']['admin.page.paths'] ?? null;
    $this->assertIsArray($pagePaths);
    $this->assertContains('/admin/', $pagePaths);
    $this->assertContains('/admin/metrics/', $pagePaths);

    $navLinks = $manifest['capabilities']['admin.nav.links'] ?? null;
    $this->assertIsArray($navLinks);
    $this->assertNotEmpty($navLinks);
  }

  public function testAdminSurfaceManifestExampleRetainsSameContractShape(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/admin-surface/manifest.php.example';

    $this->assertSame('admin-surface', $manifest['id'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['admin.surface.enabled'] ?? null);
    $this->assertIsArray($manifest['capabilities']['admin.page.paths'] ?? null);
    $this->assertIsArray($manifest['capabilities']['admin.nav.links'] ?? null);
  }
}
