<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class Soc2SurfaceManifestContractTest extends TestCase
{
  public function testSoc2SurfaceManifestHasRequiredCapabilities(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/soc2-surface/manifest.php';

    $this->assertSame('soc2-surface', $manifest['id'] ?? null);
    $this->assertTrue((bool) ($manifest['enabled'] ?? false));
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['soc2.dashboard.enabled'] ?? null);

    $this->assertIsArray($manifest['capabilities']['soc2.dashboard.artifact.paths'] ?? null);
    $this->assertIsArray($manifest['capabilities']['soc2.dashboard.checklist.paths'] ?? null);
    $this->assertIsArray($manifest['capabilities']['soc2.dashboard.execution.steps'] ?? null);
  }

  public function testSoc2SurfaceManifestExampleRetainsSameContractShape(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/soc2-surface/manifest.php.example';

    $this->assertSame('soc2-surface', $manifest['id'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['soc2.dashboard.enabled'] ?? null);
    $this->assertIsArray($manifest['capabilities']['soc2.dashboard.artifact.paths'] ?? null);
    $this->assertIsArray($manifest['capabilities']['soc2.dashboard.checklist.paths'] ?? null);
    $this->assertIsArray($manifest['capabilities']['soc2.dashboard.execution.steps'] ?? null);
  }
}
