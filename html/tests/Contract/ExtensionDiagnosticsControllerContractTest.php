<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: extension diagnostics controller must remain admin-gated and
 * return runtime snapshot payload keys used by diagnostics tooling.
 */
#[Group('contract')]
final class ExtensionDiagnosticsControllerContractTest extends TestCase
{
  public function testRuntimeSnapshotRemainsAdminGated(): void
  {
    $controller = $this->readProjectFile('src/Controllers/ExtensionDiagnosticsController.php');

    $this->assertStringContainsString('if (!AdminSurface::userCanAccess()) {', $controller);
    $this->assertStringContainsString("Response::error('[Extensions] Admin access required.'", $controller);
  }

  public function testRuntimeSnapshotIncludesExpectedDiagnosticsKeys(): void
  {
    $controller = $this->readProjectFile('src/Controllers/ExtensionDiagnosticsController.php');

    $this->assertStringContainsString("'active_manifests' => ExtensionDiagnosticsBridge::activeManifests()", $controller);
    $this->assertStringContainsString("'discovered_manifests' => ExtensionDiagnosticsBridge::discoveredManifests()", $controller);
    $this->assertStringContainsString("'capabilities' => ExtensionDiagnosticsBridge::capabilityManifest()", $controller);
    $this->assertStringContainsString("'hook_listeners' => ExtensionDiagnosticsBridge::listenersSummary()", $controller);
    $this->assertStringContainsString("'generated_at' => date('c')", $controller);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
