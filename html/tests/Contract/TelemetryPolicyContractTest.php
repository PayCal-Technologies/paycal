<?php declare(strict_types=1);

use PayCal\Domain\TelemetryPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class TelemetryPolicyContractTest extends TestCase
{
  public function testStreamDescriptionsExposeRetentionAndBoundaries(): void
  {
    $product = TelemetryPolicy::describeStream(TelemetryPolicy::STREAM_PRODUCT);
    $security = TelemetryPolicy::describeStream(TelemetryPolicy::STREAM_SECURITY);

    $this->assertSame(30, $product['retention_days']);
    $this->assertSame('product-observability-only', $product['access_boundary']);

    $this->assertSame(90, $security['retention_days']);
    $this->assertSame('security-operations-only', $security['access_boundary']);
  }

  public function testAccessControlSeparatesSecurityAndProductRoles(): void
  {
    $this->assertTrue(TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_PRODUCT, 'product'));
    $this->assertTrue(TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_PRODUCT, 'security'));

    $this->assertTrue(TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_SECURITY, 'security'));
    $this->assertFalse(TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_SECURITY, 'product'));
  }
}
