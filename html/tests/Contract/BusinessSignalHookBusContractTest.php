<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: business audit fanout remains HookBus-driven.
 */
#[Group('contract')]
final class BusinessSignalHookBusContractTest extends TestCase
{
  public function testBusinessSignalHooksDispatchesViaHookBus(): void
  {
    $source = $this->readProjectFile('src/Domain/BusinessSignalHooks.php');

    $this->assertStringContainsString('ExtensionHookBridge::dispatch(\'business.audit_event\', [\'event\' => $event]);', $source);
  }

  public function testBusinessSignalHooksNoLongerReferencesLegacyExtensionClass(): void
  {
    $source = $this->readProjectFile('src/Domain/BusinessSignalHooks.php');

    $this->assertStringNotContainsString('PayCal\\Extensions\\BusinessSignalHooks', $source);
    $this->assertStringNotContainsString('src/Extensions/BusinessSignalHooks.php', $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
