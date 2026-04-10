<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Controllers\AdminController;
use PayCal\Controllers\ExtensionDiagnosticsController;
use PayCal\Domain\ApiControllerRegistry;
use PayCal\Domain\Extensions\ExtensionRuntime;

/**
 * Contract tests for capability-driven API controller registration.
 */
#[Group('unit')]
final class ApiControllerRegistryContractsTest extends TestCase
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
  public function registryExcludesAdminControllersWhenSurfaceDisabled(): void
  {
    $this->writeRuntimeActive([]);

    $controllers = ApiControllerRegistry::controllers();

    $this->assertNotContains(AdminController::class, $controllers);
    $this->assertNotContains(ExtensionDiagnosticsController::class, $controllers);
  }

  #[Test]
  public function registryIncludesAdminControllersWhenSurfaceEnabled(): void
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

    $controllers = ApiControllerRegistry::controllers();

    $this->assertContains(AdminController::class, $controllers);
    $this->assertContains(ExtensionDiagnosticsController::class, $controllers);
  }

  #[Test]
  public function registryExcludesAdminControllersWhenCapabilityExplicitlyFalse(): void
  {
    $this->writeRuntimeActive([
      'admin-surface' => [
        'id' => 'admin-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'admin.surface.enabled' => false,
        ],
      ],
    ]);

    $controllers = ApiControllerRegistry::controllers();

    $this->assertNotContains(AdminController::class, $controllers);
    $this->assertNotContains(ExtensionDiagnosticsController::class, $controllers);
  }

  #[Test]
  public function registryContainsNoDuplicateEntries(): void
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

    $controllers = ApiControllerRegistry::controllers();

    $this->assertSame(array_unique($controllers), $controllers, 'No controller class should appear more than once.');
  }

  #[Test]
  public function registryCoreControllersUnchangedRegardlessOfAdminSurface(): void
  {
    $this->writeRuntimeActive([]);
    $withoutAdmin = ApiControllerRegistry::controllers();

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
    $withAdmin = ApiControllerRegistry::controllers();

    $coreControllers = array_values(array_filter(
      $withAdmin,
      static fn(string $c): bool => $c !== AdminController::class && $c !== ExtensionDiagnosticsController::class
    ));

    $this->assertSame($withoutAdmin, $coreControllers, 'Core controller list must not change when admin surface is toggled.');
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
