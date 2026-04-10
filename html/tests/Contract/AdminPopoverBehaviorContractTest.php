<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: admin popover behavior remains keyboard-accessible and
 * synchronized with aria-expanded state.
 */
#[Group('contract')]
final class AdminPopoverBehaviorContractTest extends TestCase
{
  public function testCoreJsTargetsAdminPopoverAndToggleElements(): void
  {
    $coreJs = $this->readProjectFile('js/core/index.php');

    $this->assertStringContainsString("const adminPopoverToggle = document.querySelector('[data-admin-popover-toggle=\"admin-nav-popover\"]');", $coreJs);
    $this->assertStringContainsString("const adminPopover = document.getElementById('admin-nav-popover');", $coreJs);
  }

  public function testCoreJsMaintainsAriaExpandedSyncForAdminPopover(): void
  {
    $coreJs = $this->readProjectFile('js/core/index.php');

    $this->assertStringContainsString("adminPopoverToggle.setAttribute('aria-expanded', isAdminPopoverOpen() ? 'true' : 'false');", $coreJs);
    $this->assertStringContainsString('const syncAdminPopoverState = () => {', $coreJs);
    $this->assertStringContainsString('syncAdminPopoverState();', $coreJs);
  }

  public function testCoreJsCollectsMenuItemsByMenuitemRole(): void
  {
    $coreJs = $this->readProjectFile('js/core/index.php');

    $this->assertStringContainsString("return Array.from(adminPopover.querySelectorAll('[role=\"menuitem\"]'))", $coreJs);
    $this->assertStringContainsString('const getAdminMenuItems = () => {', $coreJs);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
