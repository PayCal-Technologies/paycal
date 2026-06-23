<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Observability\TraceGatePolicy;

#[Group('contract')]
#[Group('security')]
final class DiagnosticPolicyConfigContractTest extends TestCase
{
  protected function tearDown(): void
  {
    TraceGatePolicy::resetForTests();
    parent::tearDown();
  }

  public function testDiagnosticPolicyFileExistsAndDeclaresCoreSections(): void
  {
    $path = TraceGatePolicy::configPath();
    $this->assertFileExists($path);

    /** @var array<string, mixed> $config */
    $config = require $path;

    foreach (['version', 'defaults', 'environments', 'modules', 'events', 'migration_status', 'migration_notes'] as $section) {
      $this->assertArrayHasKey($section, $config, 'Missing policy section: ' . $section);
    }

    $this->assertArrayHasKey('package_groups', $config);
    $this->assertArrayHasKey('capture_limits', $config);
    $this->assertArrayHasKey('presets', $config);
  }

  public function testProductionDefaultsToDenyUnlessEventAllowlisted(): void
  {
    /** @var array<string, mixed> $config */
    $config = require TraceGatePolicy::configPath();

    $prod = $config['environments']['prod'] ?? null;
    $this->assertIsArray($prod);
    $this->assertFalse((bool) ($prod['enabled'] ?? true));
    $this->assertSame('none', $prod['sink'] ?? null);

    $events = $config['events'] ?? [];
    $this->assertIsArray($events);
    $this->assertNotEmpty($events);

    foreach ($events as $eventName => $eventConfig) {
      $this->assertIsString($eventName);
      $this->assertIsArray($eventConfig);
      $this->assertTrue(
        (bool) ($eventConfig['production_allowed'] ?? false),
        'Explicit event override must set production_allowed: ' . $eventName
      );
    }

    $modules = $config['modules'] ?? [];
    $this->assertTrue((bool) ($modules['auth']['production_allowed'] ?? false));
    $this->assertTrue((bool) ($modules['request_guard']['production_allowed'] ?? false));
  }

  public function testPayrollAndCalendarModulesStayDisabledByDefault(): void
  {
    /** @var array<string, mixed> $config */
    $config = require TraceGatePolicy::configPath();
    $modules = $config['modules'] ?? [];
    $this->assertIsArray($modules);

    $this->assertFalse((bool) ($modules['payroll']['enabled'] ?? true));
    $this->assertFalse((bool) ($modules['calendar_mutation']['enabled'] ?? true));
    $this->assertSame('none', $modules['payroll']['sink'] ?? null);
    $this->assertArrayHasKey('sites', $modules);
    $this->assertArrayHasKey('reports', $modules);
    $this->assertArrayHasKey('business_sites', $modules);
    $this->assertArrayHasKey('business_reports', $modules);
  }
}
