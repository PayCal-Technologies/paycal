<?php declare(strict_types=1);

use PayCal\Domain\Security\MetadataCorrelationPolicy;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class MetadataCorrelationPolicyTest extends TestCase
{
  public function testDeniesUnknownContextByDefault(): void
  {
    $this->assertFalse(MetadataCorrelationPolicy::allows('unknown-context', 'site_metadata', 'financial_payload'));
  }

  public function testAllowsSelfServiceEarningsCorrelation(): void
  {
    $this->assertTrue(MetadataCorrelationPolicy::allows('self-service-earnings', 'site_metadata', 'financial_payload'));
  }

  public function testAllowsSelfServiceCalendarCorrelation(): void
  {
    $this->assertTrue(MetadataCorrelationPolicy::allows('self-service-calendar', 'site_metadata', 'financial_payload'));
  }

  public function testAllowsBothPairDirections(): void
  {
    $this->assertTrue(MetadataCorrelationPolicy::allows('self-service-earnings', 'financial_payload', 'site_metadata'));
  }

  public function testAllowsSecurityIncidentUserSessionCorrelation(): void
  {
    $this->assertTrue(MetadataCorrelationPolicy::allows('security-incident', 'user_profile', 'session_metadata'));
  }

  public function testAllowsSecurityIncidentUserCredentialCorrelation(): void
  {
    $this->assertTrue(MetadataCorrelationPolicy::allows('security-incident', 'credential_metadata', 'user_profile'));
  }

  public function testDeniesSelfServiceUserSessionCorrelation(): void
  {
    $this->assertFalse(MetadataCorrelationPolicy::allows('self-service-earnings', 'user_profile', 'session_metadata'));
  }

  public function testDeniesBillingMetadataCorrelationWithFinancialPayload(): void
  {
    $this->assertFalse(MetadataCorrelationPolicy::allows('self-service-earnings', 'billing_metadata', 'financial_payload'));
  }

  public function testDeniesBillingMetadataCorrelationWithEncryptedWorkEntryInPrivilegedContext(): void
  {
    $this->assertFalse(MetadataCorrelationPolicy::allows('security-incident', 'billing_metadata', 'encrypted_work_entry'));
  }
}
