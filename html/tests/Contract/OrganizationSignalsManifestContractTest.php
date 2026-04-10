<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: organization-signals extension keeps stable manifest and hook wiring.
 */
#[Group('contract')]
final class OrganizationSignalsManifestContractTest extends TestCase
{
  public function testOrganizationSignalsManifestHasStableIdHooksAndCapabilities(): void
  {
    /** @var array<string, mixed> $manifest */
    $manifest = require __DIR__ . '/../../extensions/basic/organization-signals/manifest.php';

    $this->assertSame('organization-signals', $manifest['id'] ?? null);
    $this->assertSame('bootstrap.php', $manifest['bootstrap'] ?? null);
    $this->assertContains('organization.audit_event', $manifest['hooks'] ?? []);
    $this->assertSame('basic', $manifest['capabilities']['organization.signal.owner_inbox'] ?? null);
    $this->assertSame(true, $manifest['capabilities']['organization.audit.listener'] ?? null);
  }

  public function testOrganizationSignalsBootstrapRegistersExpectedHookAndSource(): void
  {
    $bootstrap = $this->readProjectFile('extensions/basic/organization-signals/bootstrap.php');

    $this->assertStringContainsString('HookBus::register(', $bootstrap);
    $this->assertStringContainsString("'organization.audit_event'", $bootstrap);
    $this->assertStringContainsString("[Hooks::class, 'onOrganizationAuditEvent']", $bootstrap);
    $this->assertStringContainsString("'extension:organization-signals:basic'", $bootstrap);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
