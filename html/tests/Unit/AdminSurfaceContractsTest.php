<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Extensions\ExtensionRuntime;

/**
 * Contract tests for capability-driven admin surface behavior.
 */
#[Group('unit')]
final class AdminSurfaceContractsTest extends TestCase
{
  /** @var array<string, array<string, mixed>> */
  private array $originalActive;

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  protected function setUp(): void
  {
    parent::setUp();
    $this->originalActive = $this->readRuntimeActive();
  }

  protected function tearDown(): void
  {
    $this->writeRuntimeActive($this->originalActive);
    parent::tearDown();
  }

  #[Test]
  public function isEnabledDefaultsToFalseWithoutCapability(): void
  {
    $this->writeRuntimeActive([]);

    $this->assertFalse(AdminSurface::isEnabled());
  }

  #[Test]
  public function isEnabledFollowsCapabilityFlag(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => true,
        ],
      ],
    ]);

    $this->assertTrue(AdminSurface::isEnabled());
  }

  #[Test]
  public function navLinksReturnEmptyWhenCapabilityMissing(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => true,
        ],
      ],
    ]);

    $this->assertSame([], AdminSurface::navLinks());
  }

  #[Test]
  public function navLinksUseOnlyValidCapabilityItems(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => true,
          'admin.nav.links' => [
            ['href' => '/admin/', 'label_key' => 'ADMIN', 'icon' => 'admin', 'match_prefix' => '/admin'],
            ['href' => 'admin/invalid', 'label_key' => 'ADMIN_BAD'],
            ['href' => '/admin/missing-label/', 'icon' => 'admin'],
          ],
        ],
      ],
    ]);

    $links = AdminSurface::navLinks();

    $this->assertCount(1, $links);
    $this->assertSame('/admin/', $links[0]['href']);
    $this->assertSame('ADMIN', $links[0]['label_key']);
    $this->assertSame('admin', $links[0]['icon']);
    $this->assertSame('/admin', $links[0]['match_prefix']);
  }

  #[Test]
  public function navItemIsActiveMatchesExactOrChildPaths(): void
  {
    $item = [
      'href' => '/admin/metrics/',
      'label_key' => 'METRICS',
      'icon' => 'metrics',
      'match_prefix' => '/admin/metrics',
    ];

    $this->assertTrue(AdminSurface::navItemIsActive($item, '/admin/metrics'));
    $this->assertTrue(AdminSurface::navItemIsActive($item, '/admin/metrics/')); // normalized
    $this->assertTrue(AdminSurface::navItemIsActive($item, '/admin/metrics/details'));
    $this->assertFalse(AdminSurface::navItemIsActive($item, '/admin'));
    $this->assertFalse(AdminSurface::navItemIsActive($item, '/admin/metric'));
  }

  #[Test]
  public function pagePathsNormalizeAndFilterToAdminPrefixes(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => true,
          'admin.page.paths' => ['/admin/metrics/', '/admin/metrics', '/settings/', '/admin/stripe/'],
        ],
      ],
    ]);

    $paths = AdminSurface::pagePaths();

    $this->assertSame(['/admin/metrics', '/admin/stripe'], $paths);
  }

  #[Test]
  public function pagePathIsEnabledUsesExplicitPagePathsCapability(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => true,
          'admin.page.paths' => ['/admin/metrics/', '/admin/stripe/'],
          'admin.nav.links' => [
            ['href' => '/admin/', 'label_key' => 'ADMIN', 'icon' => 'admin', 'match_prefix' => '/admin'],
          ],
        ],
      ],
    ]);

    $this->assertTrue(AdminSurface::pagePathIsEnabled('/admin/metrics/weekly'));
    $this->assertFalse(AdminSurface::pagePathIsEnabled('/admin/languages/'));
  }

  #[Test]
  public function pagePathIsEnabledFallsBackToNavLinksWhenPagePathsMissing(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => true,
          'admin.nav.links' => [
            ['href' => '/admin/metrics/', 'label_key' => 'METRICS', 'icon' => 'metrics', 'match_prefix' => '/admin/metrics'],
          ],
        ],
      ],
    ]);

    $this->assertTrue(AdminSurface::pagePathIsEnabled('/admin/metrics/details'));
    $this->assertFalse(AdminSurface::pagePathIsEnabled('/admin/redis/'));
  }

  #[Test]
  public function pagePathIsEnabledReturnsFalseWhenSurfaceIsDisabled(): void
  {
    $this->writeRuntimeActive([]);

    $this->assertFalse(AdminSurface::pagePathIsEnabled('/admin/metrics/'));
  }

  /** @return array<string, array<string, mixed>> */
  private function readRuntimeActive(): array
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    /** @var array<string, array<string, mixed>> $active */
    $active = $ref->getValue();
    return $active;
  }

  /** @param array<string, array<string, mixed>> $active */
  private function writeRuntimeActive(array $active): void
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, 'active');
    $ref->setValue(null, $active);
  }
}
