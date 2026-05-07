<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PayCal\Infrastructure\Resilience\RedisReliabilityService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RedisReliabilityServiceTest extends TestCase
{
  public function testEvaluateAlertsEmitsQuotaWarningElevatedAndCritical(): void
  {
    $alerts = RedisReliabilityService::evaluateAlerts(
      [
        'quota_warning_percent' => 80.0,
        'quota_elevated_percent' => 90.0,
        'quota_critical_percent' => 95.0,
        'memory_warning_percent' => 80.0,
        'memory_critical_percent' => 90.0,
        'tier0_churn_warning_per_minute' => 500.0,
        'tier0_churn_critical_per_minute' => 1000.0,
        'eviction_critical_per_minute' => 0.0,
      ],
      [
        'session' => ['current' => 810, 'quota' => 1000, 'percent' => 81.0],
        'user' => ['current' => 910, 'quota' => 1000, 'percent' => 91.0],
        'user:kek' => ['current' => 960, 'quota' => 1000, 'percent' => 96.0],
      ],
      [],
      [],
      10.0,
      0.0
    );

    $byNamespace = [];
    foreach ($alerts as $alert) {
      if (($alert['code'] ?? '') === 'QUOTA_PRESSURE') {
        $byNamespace[(string) ($alert['namespace'] ?? '')] = (string) ($alert['severity'] ?? '');
      }
    }

    $this->assertSame('warning', $byNamespace['session'] ?? null);
    $this->assertSame('elevated', $byNamespace['user'] ?? null);
    $this->assertSame('critical', $byNamespace['user:kek'] ?? null);
  }

  public function testEvaluateAlertsEmitsEvictionAndMemoryCriticalAlerts(): void
  {
    $alerts = RedisReliabilityService::evaluateAlerts(
      [
        'quota_warning_percent' => 80.0,
        'quota_elevated_percent' => 90.0,
        'quota_critical_percent' => 95.0,
        'memory_warning_percent' => 80.0,
        'memory_critical_percent' => 90.0,
        'tier0_churn_warning_per_minute' => 500.0,
        'tier0_churn_critical_per_minute' => 1000.0,
        'eviction_critical_per_minute' => 0.0,
      ],
      [],
      [],
      [],
      92.1,
      0.5
    );

    $codes = array_map(static fn (array $a): string => (string) ($a['code'] ?? ''), $alerts);
    $this->assertContains('MEMORY_PRESSURE', $codes);
    $this->assertContains('EVICTION_DETECTED', $codes);
  }

  public function testEvaluateAlertsEmitsTier0ChurnSpikeAlerts(): void
  {
    $alerts = RedisReliabilityService::evaluateAlerts(
      [
        'quota_warning_percent' => 80.0,
        'quota_elevated_percent' => 90.0,
        'quota_critical_percent' => 95.0,
        'memory_warning_percent' => 80.0,
        'memory_critical_percent' => 90.0,
        'tier0_churn_warning_per_minute' => 500.0,
        'tier0_churn_critical_per_minute' => 1000.0,
        'eviction_critical_per_minute' => 0.0,
      ],
      [],
      [
        'session' => 540.0,
        'ratelimit' => 1100.0,
        'work' => 9999.0,
      ],
      ['session', 'ratelimit'],
      0.0,
      0.0
    );

    $session = null;
    $ratelimit = null;
    $work = null;

    foreach ($alerts as $alert) {
      if (($alert['code'] ?? '') !== 'TIER0_CHURN_SPIKE') {
        continue;
      }
      $ns = (string) ($alert['namespace'] ?? '');
      if ($ns === 'session') {
        $session = (string) ($alert['severity'] ?? '');
      }
      if ($ns === 'ratelimit') {
        $ratelimit = (string) ($alert['severity'] ?? '');
      }
      if ($ns === 'work') {
        $work = (string) ($alert['severity'] ?? '');
      }
    }

    $this->assertSame('warning', $session);
    $this->assertSame('critical', $ratelimit);
    $this->assertNull($work);
  }
}
