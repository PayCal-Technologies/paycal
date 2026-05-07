<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Queue\StripeBillingQueueMonitor;

/**
 * StripeBillingQueueMonitorTest.php
 *
 * Unit tests for webhook queue monitoring and alerting.
 */
final class StripeBillingQueueMonitorTest extends TestCase
{
  protected function tearDown(): void
  {
    parent::tearDown();
    
    // Clean up queue and dead-letter data
    Database::del(Keys::BILLING_WEBHOOK_QUEUE);
    Database::del(Keys::BILLING_WEBHOOK_DEAD_LETTER);
  }

  /**
   * Test: Queue health returns healthy status when depths are low.
   */
  public function testQueueHealthReturnsHealthyWhenDepthsLow(): void
  {
    $health = StripeBillingQueueMonitor::getQueueHealth();

    $this->assertTrue($health['healthy']);
    $this->assertSame(0, $health['queue_depth']);
    $this->assertSame(0, $health['dead_letter_depth']);
    $this->assertEmpty($health['alerts']);
  }

  /**
   * Test: Queue health triggers warning alert when queue depth approaches warning threshold.
   */
  public function testQueueHealthTriggersWarningAlertOnQueueDepth(): void
  {
    // Populate queue near warning threshold (500+)
    for ($i = 0; $i < 510; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_QUEUE, json_encode([
        'payload' => 'test-' . $i,
        'attempt' => 1,
      ]));
    }

    $health = StripeBillingQueueMonitor::getQueueHealth();

    // Healthy is true because warning alerts don't make system unhealthy
    $this->assertTrue($health['healthy']);
    $this->assertGreaterThanOrEqual(500, $health['queue_depth']);
    $this->assertNotEmpty($health['alerts']);
    
    // Verify warning alert exists
    $hasWarningAlert = false;
    foreach ($health['alerts'] as $alert) {
      if ($alert['code'] === 'WEBHOOK_QUEUE_BACKLOG' && $alert['severity'] === 'warning') {
        $hasWarningAlert = true;
        break;
      }
    }
    $this->assertTrue($hasWarningAlert, 'Expected queue backlog warning alert');
  }

  /**
   * Test: Queue health triggers critical alert when queue depth exceeds critical threshold.
   */
  public function testQueueHealthTriggersCriticalAlertOnQueueOverflow(): void
  {
    // Populate queue beyond critical threshold (1000+)
    for ($i = 0; $i < 1050; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_QUEUE, json_encode([
        'payload' => 'test-' . $i,
        'attempt' => 1,
      ]));
    }

    $health = StripeBillingQueueMonitor::getQueueHealth();

    $this->assertFalse($health['healthy']); // Has critical alerts
    $this->assertGreaterThanOrEqual(1000, $health['queue_depth']);
    
    // Verify critical alert exists
    $hasCriticalAlert = false;
    foreach ($health['alerts'] as $alert) {
      if ($alert['code'] === 'WEBHOOK_QUEUE_CRITICAL' && $alert['severity'] === 'critical') {
        $hasCriticalAlert = true;
        break;
      }
    }
    $this->assertTrue($hasCriticalAlert, 'Expected critical queue alert');
  }

  /**
   * Test: Queue health triggers warning alert when dead-letter depth approaches threshold.
   */
  public function testQueueHealthTriggersWarningAlertOnDeadLetterDepth(): void
  {
    // Populate dead-letter near warning threshold (50+)
    for ($i = 0; $i < 60; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_DEAD_LETTER, json_encode([
        'payload' => 'failed-' . $i,
        'error' => 'test-error',
      ]));
    }

    $health = StripeBillingQueueMonitor::getQueueHealth();

    // Healthy is true because warning alerts don't make system unhealthy
    $this->assertTrue($health['healthy']);
    $this->assertGreaterThanOrEqual(50, $health['dead_letter_depth']);
    $this->assertNotEmpty($health['alerts']);
    
    // Verify dead-letter warning alert exists
    $hasDeadLetterAlert = false;
    foreach ($health['alerts'] as $alert) {
      if ($alert['code'] === 'WEBHOOK_DEAD_LETTER_ACCUMULATING' && $alert['severity'] === 'warning') {
        $hasDeadLetterAlert = true;
        break;
      }
    }
    $this->assertTrue($hasDeadLetterAlert, 'Expected dead-letter accumulation warning alert');
  }

  /**
   * Test: Queue health computes correct percentage metrics.
   */
  public function testQueueHealthComputesCorrectPercentages(): void
  {
    // Add 100 items to queue (max is 2000)
    for ($i = 0; $i < 100; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_QUEUE, json_encode(['test' => $i]));
    }

    // Add 50 items to dead-letter (max is 500)
    for ($i = 0; $i < 50; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_DEAD_LETTER, json_encode(['test' => $i]));
    }

    $health = StripeBillingQueueMonitor::getQueueHealth();

    $this->assertSame(100, $health['queue_depth']);
    $this->assertSame(50, $health['dead_letter_depth']);
    $this->assertEqualsWithDelta(5.0, $health['queue_percent'], 0.1);
    $this->assertEqualsWithDelta(10.0, $health['dead_letter_percent'], 0.1);
  }

  /**
   * Test: Multiple alerts can coexist (queue + dead-letter both warning).
   */
  public function testQueueHealthCanEmitMultipleAlerts(): void
  {
    // Populate queue near warning threshold
    for ($i = 0; $i < 510; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_QUEUE, json_encode(['test' => $i]));
    }

    // Populate dead-letter near warning threshold
    for ($i = 0; $i < 60; $i++) {
      Database::lpush(Keys::BILLING_WEBHOOK_DEAD_LETTER, json_encode(['test' => $i]));
    }

    $health = StripeBillingQueueMonitor::getQueueHealth();

    // Should have at least 2 alerts
    $this->assertGreaterThanOrEqual(2, count($health['alerts']));
    
    $codesSeen = array_map(fn($a) => $a['code'], $health['alerts']);
    $this->assertContains('WEBHOOK_QUEUE_BACKLOG', $codesSeen);
    $this->assertContains('WEBHOOK_DEAD_LETTER_ACCUMULATING', $codesSeen);
  }
}
