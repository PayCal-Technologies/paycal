<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Controllers\TestsPageController;

/**
 * TestsPageControllerTest
 *
 * Purpose: Guard the markdown parser for tests dashboard status rows.
 * Usage context: Runs in unit suite and validates parse resilience for symbols and bullets.
 * Why here: parser behavior is controller-local and regression-prone when status formats vary.
 */
#[Group('unit')]
final class TestsPageControllerTest extends TestCase
{
  #[Test]
  public function parseTestPlanHandlesUnicodeAndAsciiStatusRows(): void
  {
    $plan = <<<MD
Total: 45 tests, 120 assertions
Phase 1: COMPLETE (2/2 classes)

- ✅ InputSanitizer (33 tests, 88 assertions)
* X PayrollContractFreezeTest (12 tests, 32 assertions)
• PASS PayCal\\Domain\\Telemetry\\TelemetryRepositoryTest (0 tests, 0 assertions)
MD;

    $tmp = tempnam(sys_get_temp_dir(), 'paycal-test-plan-');
    $this->assertNotFalse($tmp);
    file_put_contents((string) $tmp, $plan);

    try {
      $method = new ReflectionMethod(TestsPageController::class, 'parseTestPlan');
      /** @var array<string, mixed> $parsed */
      $parsed = $method->invoke(null, $tmp);
    } finally {
      @unlink((string) $tmp);
    }

    $this->assertSame(45, $parsed['totalTests']);
    $this->assertSame(120, $parsed['totalAssertions']);

    $classes = $parsed['classes'] ?? [];
    $this->assertIsArray($classes);
    $this->assertCount(3, $classes);

    $this->assertSame('InputSanitizer', $classes[0]['name']);
    $this->assertSame('✅', $classes[0]['status']);
    $this->assertSame(33, $classes[0]['tests']);
    $this->assertSame(88, $classes[0]['assertions']);

    $this->assertSame('PayrollContractFreezeTest', $classes[1]['name']);
    $this->assertSame('X', $classes[1]['status']);

    $this->assertSame('PayCal\\Domain\\Telemetry\\TelemetryRepositoryTest', $classes[2]['name']);
    $this->assertSame('PASS', $classes[2]['status']);
  }
}
