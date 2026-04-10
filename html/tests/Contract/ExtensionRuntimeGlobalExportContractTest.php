<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: ExtensionRuntime boot exports manifest/capability snapshots
 * into expected global variables.
 */
#[Group('contract')]
final class ExtensionRuntimeGlobalExportContractTest extends TestCase
{
  public function testRuntimeBootExportsManifestAndCapabilityGlobals(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString('$GLOBALS[\'PAYCAL_EXTENSION_MANIFESTS\'] = self::activeManifests();', $runtime);
    $this->assertStringContainsString('$GLOBALS[\'PAYCAL_EXTENSION_CAPABILITIES\'] = self::capabilityManifest();', $runtime);
  }

  public function testRuntimeBootKeepsDiscoverSelectBootstrapSequence(): void
  {
    $runtime = $this->readProjectFile('extensions/runtime.php');

    $this->assertStringContainsString('self::$discovered = self::discoverExtensions();', $runtime);
    $this->assertStringContainsString('self::$active = self::selectActiveExtensions(self::$discovered);', $runtime);
    $this->assertStringContainsString('require_once $bootstrap;', $runtime);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
