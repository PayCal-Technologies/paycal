<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: earnings-ytd extension manifests keep stable precedence-compatible shape.
 */
#[Group('contract')]
final class EarningsYtdManifestContractTest extends TestCase
{
  public function testBasicEarningsYtdManifestHasExpectedHookAndCapabilities(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/basic/earnings-ytd/manifest.php';

    $this->assertSame('earnings-ytd', $manifest['id'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertContains('earnings.ytd.render', $manifest['hooks'] ?? []);
    $this->assertSame('basic', $manifest['capabilities']['earnings.ytd.render'] ?? null);
  }

  public function testOverrideEarningsYtdManifestExampleMatchesIdAndHookContract(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/overrides/earnings-ytd/manifest.php.example';

    $this->assertSame('earnings-ytd', $manifest['id'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertContains('earnings.ytd.render', $manifest['hooks'] ?? []);
    $this->assertSame('rich', $manifest['capabilities']['earnings.ytd.render'] ?? null);
  }

  public function testEarningsYtdBootstrapsRegisterExpectedHookName(): void
  {
    $basicBootstrap = $this->readProjectFile('extensions/basic/earnings-ytd/bootstrap.php');
    $overrideBootstrap = $this->readProjectFile('extensions/overrides/earnings-ytd/bootstrap.php');

    $this->assertStringContainsString("HookBus::register(", $basicBootstrap);
    $this->assertStringContainsString("'earnings.ytd.render'", $basicBootstrap);
    $this->assertStringContainsString("HookBus::register(", $overrideBootstrap);
    $this->assertStringContainsString("'earnings.ytd.render'", $overrideBootstrap);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
