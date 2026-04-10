<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: extension bootstrap entrypoint must load runtime and boot it.
 */
#[Group('contract')]
final class ExtensionBootstrapEntrypointContractTest extends TestCase
{
  public function testBootstrapEntrypointRequiresRuntimeAndCallsBoot(): void
  {
    $bootstrap = $this->readProjectFile('extensions/bootstrap.php');

    $this->assertStringContainsString('use PayCal\\Domain\\Extensions\\ExtensionRuntime;', $bootstrap);
    $this->assertStringContainsString("require_once __DIR__ . '/runtime.php';", $bootstrap);
    $this->assertStringContainsString('ExtensionRuntime::boot();', $bootstrap);
  }

  public function testConfigStillPointsAtExtensionsBootstrapEntrypoint(): void
  {
    $config = $this->readProjectFile('config.php');

    $this->assertStringContainsString('use PayCal\\Domain\\Extensions\\Bridges\\ExtensionBootstrapBridge;', $config);
    $this->assertStringContainsString('ExtensionBootstrapBridge::initialize();', $config);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
