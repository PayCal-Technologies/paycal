<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: migrated admin pages must use centralized AdminSurface gate.
 */
#[Group('contract')]
final class AdminPageSurfaceGateContractTest extends TestCase
{
  /** @return array<int, array{path:string, gate:string}> */
  public static function adminPageProvider(): array
  {
    return [
      ['path' => 'admin/index.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/');"],
      ['path' => 'admin/metrics/index.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/metrics/');"],
      ['path' => 'admin/languages.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/languages/');"],
      ['path' => 'admin/stripe/index.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/stripe/');"],
      ['path' => 'admin/redis/index.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/redis/');"],
      ['path' => 'admin/ast/index.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/ast/');"],
      ['path' => 'admin/documentation/index.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/documentation/');"],
      ['path' => 'admin/tax-brackets.php', 'gate' => "AdminSurface::redirectHomeIfPageUnavailable('/admin/tax-brackets/');"],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('adminPageProvider')]
  public function testAdminPageUsesCentralSurfaceGate(string $path, string $gate): void
  {
    $source = $this->readProjectFile($path);

    $this->assertStringContainsString('use PayCal\\Domain\\AdminSurface;', $source);
    $this->assertStringContainsString($gate, $source);
    $this->assertStringNotContainsString('if (!User::isAdmin()) {', $source);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
