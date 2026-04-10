<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: header admin navigation remains capability-driven via AdminSurface.
 */
#[Group('contract')]
final class HeaderAdminSurfaceContractTest extends TestCase
{
  public function testHeaderUsesAdminSurfaceCapabilityForAdminNavVisibility(): void
  {
    $header = $this->readProjectFile('header.php');

    $this->assertStringContainsString('$adminSurfaceEnabled = \\PayCal\\Domain\\AdminSurface::isEnabled();', $header);
    $this->assertStringContainsString('$adminNavItems = $adminSurfaceEnabled ? \\PayCal\\Domain\\AdminSurface::navLinks() : [];', $header);
    $this->assertStringContainsString('if ($adminSurfaceEnabled && User::isAdmin() && $adminNavItems !== []) {', $header);
  }

  public function testHeaderRendersAdminPopoverItemsFromCapabilityLinks(): void
  {
    $header = $this->readProjectFile('header.php');

    $this->assertStringContainsString('<?php foreach ($adminNavItems as $adminNavItem) {', $header);
    $this->assertStringContainsString('$isActive = \\PayCal\\Domain\\AdminSurface::navItemIsActive($adminNavItem, $requestPath);', $header);
    $this->assertStringContainsString('$label = Strings::headerI18n($adminNavItem[\'label_key\']);', $header);
    $this->assertStringContainsString('href="<?php echo htmlspecialchars($adminNavItem[\'href\'], ENT_QUOTES, \'UTF-8\'); ?>"', $header);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
