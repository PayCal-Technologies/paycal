<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: organization audit fanout remains HookBus-driven.
 */
#[Group('contract')]
final class OrganizationSignalHookBusContractTest extends TestCase
{
  public function testOrganizationSignalHooksDispatchesViaHookBus(): void
  {
    $source = $this->readProjectFile('src/Domain/OrganizationSignalHooks.php');

    $this->assertStringContainsString('ExtensionHookBridge::dispatch(\'organization.audit_event\', [\'event\' => $event]);', $source);
  }

  public function testOrganizationSignalHooksNoLongerReferencesLegacyExtensionClass(): void
  {
    $source = $this->readProjectFile('src/Domain/OrganizationSignalHooks.php');

    $this->assertStringNotContainsString('PayCal\\Extensions\\OrganizationSignalHooks', $source);
    $this->assertStringNotContainsString('src/Extensions/OrganizationSignalHooks.php', $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
