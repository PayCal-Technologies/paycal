<?php declare(strict_types=1);

use PayCal\Domain\Telemetry\TelemetryAccessToken;
use PayCal\Domain\Telemetry\TelemetryRepository;
use PayCal\Domain\TelemetryPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class TelemetryAccessGovernanceContractTest extends TestCase
{
  public function testProductStreamRoleMatrixContract(): void
  {
    $allowedRoles = ['product', 'product-admin', 'security', 'security-admin'];
    foreach ($allowedRoles as $role) {
      $this->assertTrue(
        TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_PRODUCT, $role),
        sprintf('Expected role %s to access product stream.', $role)
      );
    }

    $deniedRoles = ['forensics', 'readonly', 'guest'];
    foreach ($deniedRoles as $role) {
      $this->assertFalse(
        TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_PRODUCT, $role),
        sprintf('Expected role %s to be denied product stream.', $role)
      );
    }
  }

  public function testSecurityStreamRoleMatrixContract(): void
  {
    $allowedRoles = ['security', 'security-admin', 'forensics'];
    foreach ($allowedRoles as $role) {
      $this->assertTrue(
        TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_SECURITY, $role),
        sprintf('Expected role %s to access security stream.', $role)
      );
    }

    $deniedRoles = ['product', 'product-admin', 'readonly', 'guest'];
    foreach ($deniedRoles as $role) {
      $this->assertFalse(
        TelemetryPolicy::canAccess(TelemetryPolicy::STREAM_SECURITY, $role),
        sprintf('Expected role %s to be denied security stream.', $role)
      );
    }
  }

  public function testCrossStreamJoinGuardAlwaysDeniesMixedStreams(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_SECURITY, 'security-admin', 'forensics', 'bucket');

    $result = TelemetryRepository::guardCrossStreamJoin($token, TelemetryPolicy::STREAM_SECURITY, TelemetryPolicy::STREAM_PRODUCT);

    $this->assertFalse($result['allowed']);
    $this->assertSame('cross_stream_join_denied', $result['reason']);
  }

  public function testSameStreamJoinStillRequiresStreamAuthorization(): void
  {
    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');

    $result = TelemetryRepository::guardCrossStreamJoin($token, TelemetryPolicy::STREAM_SECURITY, TelemetryPolicy::STREAM_SECURITY);

    $this->assertFalse($result['allowed']);
    $this->assertSame('telemetry_access_denied', $result['reason']);
  }
}
