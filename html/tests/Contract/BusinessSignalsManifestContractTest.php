<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: business-signals extension keeps stable manifest and hook wiring.
 */
#[Group('contract')]
final class BusinessSignalsManifestContractTest extends TestCase
{
  public function testBusinessSignalsManifestHasStableIdHooksAndCapabilities(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/basic/business-signals/manifest.php';

    $this->assertSame('business-signals', $manifest['id'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertContains('business.audit_event', $manifest['hooks'] ?? []);
    $this->assertSame('basic', $manifest['capabilities']['business.signal.owner_inbox'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['business.audit.listener'] ?? null);
  }

  public function testBusinessSignalsBootstrapRegistersExpectedHookAndSource(): void
  {
    $bootstrap = $this->readProjectFile('extensions/basic/business-signals/bootstrap.php');

    $this->assertStringContainsString('HookBus::register(', $bootstrap);
    $this->assertStringContainsString("'business.audit_event'", $bootstrap);
    $this->assertStringContainsString("[Hooks::class, 'onBusinessAuditEvent']", $bootstrap);
    $this->assertStringContainsString("'extension:business-signals:basic'", $bootstrap);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
