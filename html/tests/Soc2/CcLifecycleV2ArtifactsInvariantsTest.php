<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Lifecycle / V2 SOC2 Artifact Invariants
 *
 * Purpose:
 *   Verify continuity artifacts and strict schema contracts for SOC2 v2
 *   lifecycle evidence are present and structurally valid.
 *
 * Why here:
 *   This suite provides deterministic, test-backed assurance that v2 index,
 *   lifecycle, and schema files required by the monthly bundle pipeline exist.
 *
 * @internal
 */
#[Group('unit')]
#[Group('soc2')]
final class CcLifecycleV2ArtifactsInvariantsTest extends TestCase
{
  private string $repoRoot;

  protected function setUp(): void
  {
    $this->repoRoot = dirname(__DIR__, 3);
  }

  private function repoFile(string $relativePath): string
  {
    return $this->repoRoot . '/' . $relativePath;
  }

  /** @return array<string, mixed> */
  private function decodeObject(string $relativePath): array
  {
    $path = $this->repoFile($relativePath);
    $this->assertFileExists($path, "Expected artifact missing: {$relativePath}");
    $decoded = json_decode((string) file_get_contents($path), true);
    $this->assertIsArray($decoded, "Expected valid JSON object in {$relativePath}");
    return $decoded;
  }

  #[Test]
  public function v2SchemasExistWithRequiredKeys(): void
  {
    $bundleSchema = $this->decodeObject('soc2/schemas/bundle-index.schema.json');
    $controlSchema = $this->decodeObject('soc2/schemas/control-status.schema.json');
    $exceptionSchema = $this->decodeObject('soc2/schemas/exception-log.schema.json');

    $this->assertArrayHasKey('required', $bundleSchema);
    $this->assertArrayHasKey('bundle_required', $bundleSchema);
    $this->assertArrayHasKey('status_enum', $bundleSchema);

    $this->assertArrayHasKey('required', $controlSchema);
    $this->assertArrayHasKey('entry_required', $controlSchema);
    $this->assertArrayHasKey('status_enum', $controlSchema);

    $this->assertArrayHasKey('required', $exceptionSchema);
    $this->assertArrayHasKey('exception_required', $exceptionSchema);
    $this->assertArrayHasKey('severity_enum', $exceptionSchema);
  }

  #[Test]
  public function v2IndexesExistAndContainExpectedArrays(): void
  {
    $bundleIndex = $this->decodeObject('soc2/index/bundle-index.json');
    $controlHistory = $this->decodeObject('soc2/index/control-history.json');
    $exceptionLog = $this->decodeObject('soc2/index/exception-log.json');

    $this->assertArrayHasKey('bundles', $bundleIndex);
    $this->assertIsArray($bundleIndex['bundles']);

    $this->assertArrayHasKey('records', $controlHistory);
    $this->assertIsArray($controlHistory['records']);

    $this->assertArrayHasKey('exceptions', $exceptionLog);
    $this->assertIsArray($exceptionLog['exceptions']);
  }

  #[Test]
  public function lifecycleLatestReportsExist(): void
  {
    $this->decodeObject('soc2/reports/lifecycle/soc2-control-status-latest.json');
    $this->decodeObject('soc2/reports/lifecycle/soc2-control-regressions-latest.json');
    $this->decodeObject('soc2/reports/exceptions/soc2-exception-sla-latest.json');
    $this->decodeObject('soc2/reports/trends/soc2-trend-snapshot-latest.json');
  }

  #[Test]
  public function internalSoc2StatusControllerIsRegistered(): void
  {
    $registryPath = $this->repoFile('html/src/Domain/ApiControllerRegistry.php');
    $content = (string) file_get_contents($registryPath);

    $this->assertStringContainsString('Soc2StatusController::class', $content);
    $this->assertStringContainsString('use PayCal\\Controllers\\Soc2StatusController;', $content);
  }
}
