<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessDashboardMetrics;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessDashboardMetricsTest extends TestCase
{
  private string $businessId;

  protected function setUp(): void
  {
    $this->businessId = 'test-dashboard-metrics-' . bin2hex(random_bytes(6));
  }

  protected function tearDown(): void
  {
    Database::unlink(Keys::BUSINESS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_SETTINGS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $this->businessId);
    Database::unlink(Keys::BUSINESS_SITE . ':' . $this->businessId);
    Database::unlink(Keys::businessMetricsPendingInvites($this->businessId));
    Database::unlink(Keys::businessMetricsPendingRequests($this->businessId));
    Database::unlink(Keys::businessMetricsWorkDay($this->businessId, '2026-06-10'));
  }

  #[Test]
  public function forBusinessReturnsSetSizesAndMetadata(): void
  {
    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'created_at' => '2026-01-15T10:00:00+00:00',
      'last_activity_at' => '2026-06-09T18:30:00+00:00',
    ]);
    Database::hset(Keys::BUSINESS_SETTINGS . ':' . $this->businessId, [
      'timezone' => 'UTC',
    ]);
    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, 'member-a', 'member-b');
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, 'owner:site-1');

    $metrics = BusinessDashboardMetrics::forBusiness($this->businessId, true);

    $this->assertSame(2, $metrics['members']);
    $this->assertSame(1, $metrics['sites']);
    $this->assertSame(0, $metrics['pending_invites']);
    $this->assertSame(0, $metrics['pending_requests']);
    $this->assertSame('2026-06-09T18:30:00+00:00', $metrics['last_activity_at']);
    $this->assertSame('2026-01-15T10:00:00+00:00', $metrics['created_at']);
  }

  #[Test]
  public function pendingCountersIncrementAndDecrement(): void
  {
    BusinessDashboardMetrics::recordPendingInviteCreated($this->businessId);
    BusinessDashboardMetrics::recordPendingInviteCreated($this->businessId);
    BusinessDashboardMetrics::recordPendingInviteResolved($this->businessId);

    BusinessDashboardMetrics::recordPendingRequestCreated($this->businessId);
    BusinessDashboardMetrics::recordPendingRequestResolved($this->businessId);

    $metrics = BusinessDashboardMetrics::forBusiness($this->businessId, true);

    $this->assertSame(1, $metrics['pending_invites']);
    $this->assertSame(0, $metrics['pending_requests']);
  }

  #[Test]
  public function accessMetricsHiddenWhenNotAuthorized(): void
  {
    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'created_at' => '2026-01-15T10:00:00+00:00',
    ]);

    BusinessDashboardMetrics::recordPendingInviteCreated($this->businessId);

    $metrics = BusinessDashboardMetrics::forBusiness($this->businessId, false);

    $this->assertNull($metrics['pending_invites']);
    $this->assertNull($metrics['pending_requests']);
  }
}
