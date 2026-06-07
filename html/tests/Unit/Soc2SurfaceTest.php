<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Soc2Surface;
use PayCal\Domain\Extensions\ExtensionRuntime;

/**
 * Unit tests for SOC2 capability seam defaults and override behavior.
 */
#[Group('unit')]
final class Soc2SurfaceTest extends TestCase
{
  private bool $originalBooted;

  /** @var array<string, array<string, mixed>> */
  private array $originalDiscovered;

  /** @var array<string, array<string, mixed>> */
  private array $originalActive;

  public static function setUpBeforeClass(): void
  {
    require_once __DIR__ . '/../../extensions/runtime.php';
  }

  protected function setUp(): void
  {
    parent::setUp();

    $this->originalBooted = $this->readRuntimeStatic('booted');
    $this->originalDiscovered = $this->readRuntimeStatic('discovered');
    $this->originalActive = $this->readRuntimeStatic('active');

    $this->writeRuntimeStatic('active', []);
  }

  protected function tearDown(): void
  {
    $this->writeRuntimeStatic('booted', $this->originalBooted);
    $this->writeRuntimeStatic('discovered', $this->originalDiscovered);
    $this->writeRuntimeStatic('active', $this->originalActive);

    parent::tearDown();
  }

  #[Test]
  public function defaultsAreSafeAndNonEmpty(): void
  {
    $this->assertFalse(Soc2Surface::isEnabled());
    $this->assertNotSame([], Soc2Surface::artifactPaths());
    $this->assertNotSame([], Soc2Surface::checklistPaths());
    $this->assertNotSame([], Soc2Surface::executionSteps());
  }

  #[Test]
  public function runtimeCapabilitiesOverrideDefaults(): void
  {
    $this->writeRuntimeStatic('active', [
      'soc2-surface' => [
        'id' => 'soc2-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'soc2.dashboard.enabled' => true,
          'soc2.dashboard.artifact.paths' => ['tmp/custom-a.md', 'tmp/custom-b.md'],
          'soc2.dashboard.checklist.paths' => ['tmp/checklist.md'],
          'soc2.dashboard.execution.steps' => ['Custom step 1', 'Custom step 2'],
        ],
      ],
    ]);

    $this->assertTrue(Soc2Surface::isEnabled());
    $this->assertSame(['tmp/custom-a.md', 'tmp/custom-b.md'], Soc2Surface::artifactPaths());
    $this->assertSame(['tmp/checklist.md'], Soc2Surface::checklistPaths());
    $this->assertSame(['Custom step 1', 'Custom step 2'], Soc2Surface::executionSteps());
  }

  #[Test]
  public function invalidCapabilityPayloadsFallBackToDefaults(): void
  {
    $this->writeRuntimeStatic('active', [
      'soc2-surface' => [
        'id' => 'soc2-surface',
        'version' => '1.0.0-private',
        'source' => 'override',
        'capabilities' => [
          'soc2.dashboard.artifact.paths' => 42,
          'soc2.dashboard.checklist.paths' => null,
          'soc2.dashboard.execution.steps' => ['  ', [], false],
        ],
      ],
    ]);

    $this->assertNotSame([], Soc2Surface::artifactPaths());
    $this->assertNotSame([], Soc2Surface::checklistPaths());
    $this->assertNotSame([], Soc2Surface::executionSteps());
  }

  private function readRuntimeStatic(string $property): mixed
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, $property);
    return $ref->getValue();
  }

  private function writeRuntimeStatic(string $property, mixed $value): void
  {
    $ref = new \ReflectionProperty(ExtensionRuntime::class, $property);
    $ref->setValue(null, $value);
  }
}
