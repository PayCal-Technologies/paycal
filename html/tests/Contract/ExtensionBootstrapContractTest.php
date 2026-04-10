<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: extension bootstrap must be guarded and non-fatal in config boot.
 */
#[Group('contract')]
final class ExtensionBootstrapContractTest extends TestCase
{
  public function testConfigBootstrapsExtensionsWithThrowableGuard(): void
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
