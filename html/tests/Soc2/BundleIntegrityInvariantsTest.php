<?php declare(strict_types=1);

namespace PayCal\Tests\Soc2;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SOC2 Bundle Integrity Invariants
 *
 * Purpose:
 *   Validate cross-artifact integrity for SOC2 evidence packaging: control-map
 *   completeness, test-control trace completeness, and bundle manifest sanity.
 *
 * Why here:
 *   This closes an evidence-quality gap where individual control artifacts may
 *   pass in isolation but drift across generated bundle surfaces.
 *
 * @internal
 */
#[Group('unit')]
#[Group('soc2')]
final class BundleIntegrityInvariantsTest extends TestCase
{
  private string $repoRoot;

  protected function setUp(): void
  {
    $this->repoRoot = dirname(__DIR__, 3);
  }

  /**
   * @return array<string, mixed>
   */
  private function readJson(string $relativePath): array
  {
    $path = $this->repoRoot . '/' . $relativePath;
    $this->assertFileExists($path, "Required JSON artifact missing: {$relativePath}");

    $raw = file_get_contents($path);
    $this->assertIsString($raw);

    $decoded = json_decode($raw, true);
    $this->assertIsArray($decoded, "Artifact is not valid JSON: {$relativePath}");

    return $decoded;
  }

  #[Test]
  public function controlMapContainsExactlyCc1ThroughCc9(): void
  {
    $map = $this->readJson('soc2/reports/soc2-control-map.json');

    $controls = $map['controls'] ?? null;
    $this->assertIsArray($controls);

    $ids = [];
    foreach ($controls as $control) {
      $this->assertIsArray($control);
      $id = strtoupper(trim((string) ($control['control_id'] ?? '')));
      $this->assertNotEmpty($id, 'Each control map row must define control_id.');
      $ids[] = $id;

      $artifacts = $control['artifacts'] ?? null;
      $this->assertIsArray($artifacts, "{$id} artifacts must be an array.");
      $this->assertNotEmpty($artifacts, "{$id} must map at least one artifact.");

      $unique = array_values(array_unique(array_map(static fn($v): string => trim((string) $v), $artifacts)));
      $this->assertCount(count($artifacts), $unique, "{$id} contains duplicate artifact paths.");
    }

    sort($ids);
    $this->assertSame(['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9'], $ids);
  }

  #[Test]
  public function testControlTraceCoversAllCcControlsAndLogsExist(): void
  {
    $trace = $this->readJson('soc2/reports/test-controls/soc2-test-control-trace-latest.json');

    $this->assertIsBool($trace['all_passed'] ?? null, 'Latest test-control trace must include boolean all_passed field.');

    $suites = $trace['suites'] ?? null;
    $this->assertIsArray($suites);
    $this->assertGreaterThanOrEqual(9, count($suites), 'Trace should include at least one suite per CC control.');

    $covered = [];
    foreach ($suites as $suite) {
      $this->assertIsArray($suite);
      $controlIds = $suite['control_ids'] ?? null;
      $this->assertIsArray($controlIds);
      foreach ($controlIds as $cid) {
        $covered[strtoupper((string) $cid)] = true;
      }

      $logPath = (string) ($suite['log_path'] ?? '');
      $this->assertNotEmpty($logPath, 'Each suite must include log_path.');
      $this->assertFileExists($this->repoRoot . '/' . $logPath, "Suite log missing: {$logPath}");

      $testFiles = $suite['test_files'] ?? null;
      $this->assertIsArray($testFiles);
      $this->assertNotEmpty($testFiles);
      foreach ($testFiles as $testFile) {
        $relative = trim((string) $testFile);
        $this->assertNotEmpty($relative);
        $this->assertFileExists($this->repoRoot . '/' . $relative, "Test file listed in trace is missing: {$relative}");
      }
    }

    $expected = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9'];
    foreach ($expected as $cid) {
      $this->assertArrayHasKey($cid, $covered, "Trace must include coverage for {$cid}.");
    }
  }

  #[Test]
  public function latestBundleManifestHasExpectedShapeAndCoreArtifacts(): void
  {
    $bundleGlobs = glob($this->repoRoot . '/soc2/bundles/*/bundle.json') ?: [];
    $this->assertNotEmpty($bundleGlobs, 'At least one SOC2 bundle manifest must exist under soc2/bundles/*/bundle.json.');

    usort(
      $bundleGlobs,
      static fn(string $a, string $b): int => (int) (filemtime($b) <=> filemtime($a))
    );

    $latest = $bundleGlobs[0];
    $raw = file_get_contents($latest);
    $this->assertIsString($raw);

    $manifest = json_decode($raw, true);
    $this->assertIsArray($manifest, 'Latest bundle manifest must be valid JSON.');
    $this->assertSame('v1', $manifest['schema_version'] ?? null);

    $fileCount = (int) ($manifest['file_count'] ?? 0);
    $files = $manifest['files'] ?? null;
    $this->assertIsArray($files);
    $this->assertGreaterThan(20, $fileCount, 'Bundle manifest should contain a meaningful evidence inventory (>20 files).');
    $this->assertGreaterThan(20, count($files), 'Bundle files list should contain >20 entries.');

    $paths = [];
    foreach ($files as $entry) {
      $this->assertIsArray($entry);
      $path = (string) ($entry['path'] ?? '');
      if ($path !== '') {
        $paths[$path] = true;
      }
    }

    $this->assertArrayHasKey('reports/soc2-control-map.json', $paths, 'Bundle must include control map artifact.');
    $hasLegacyTracePath = isset($paths['reports/soc2-test-control-trace-latest.json']);
    $hasNestedTracePath = isset($paths['reports/test-controls/soc2-test-control-trace-latest.json']);
    $this->assertTrue(
      $hasLegacyTracePath || $hasNestedTracePath,
      'Bundle must include test-control trace artifact (legacy or nested path).'
    );
  }
}
