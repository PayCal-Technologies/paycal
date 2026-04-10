<?php declare(strict_types=1);

use PayCal\Domain\Security\CorrelationBroker;
use PayCal\Domain\Security\CorrelationContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class CorrelationBrokerTest extends TestCase
{
  public function testEvaluateDeniesUnknownPairForContext(): void
  {
    $context = new CorrelationContext(
      'self-service-earnings',
      'user-123',
      'user',
      'self-service',
      ['user_profile:session_metadata'],
      'audit-rc-4'
    );

    $decision = CorrelationBroker::evaluate($context);

    $this->assertFalse($decision->allowed());
    $this->assertSame('metadata_correlation_denied', $decision->reasonCode());
    $this->assertSame(['user_profile:session_metadata'], $decision->deniedPairs());
  }

  public function testComposeReturnsSuccessForAllowedPair(): void
  {
    $context = new CorrelationContext(
      'self-service-earnings',
      'user-123',
      'user',
      'self-service',
      ['site_metadata:financial_payload'],
      'audit-rc-5'
    );

    $result = CorrelationBroker::compose(
      ['site_id' => 's1'],
      ['gross' => 100.25],
      'site_metadata',
      'financial_payload',
      $context
    );

    $this->assertSame('success', $result['status'] ?? null);
    $this->assertSame('correlation_allowed', $result['decision']['reason'] ?? null);
    $this->assertSame(['site_id' => 's1'], $result['data']['left'] ?? null);
    $this->assertSame(['gross' => 100.25], $result['data']['right'] ?? null);
  }

  public function testComposeReturnsDeniedEnvelopeForUnknownContext(): void
  {
    $context = new CorrelationContext(
      'unknown-context',
      'user-123',
      'user',
      'self-service',
      ['site_metadata:financial_payload'],
      'audit-rc-6'
    );

    $result = CorrelationBroker::compose(
      ['site_id' => 's1'],
      ['gross' => 100.25],
      'site_metadata',
      'financial_payload',
      $context
    );

    $this->assertSame('denied', $result['status'] ?? null);
    $this->assertSame('metadata_correlation_denied', $result['decision']['reason'] ?? null);
  }

  public function testEvaluateDeniesBillingMetadataCorrelationWithEncryptedWorkEntry(): void
  {
    $context = new CorrelationContext(
      'security-incident',
      'user-123',
      'admin',
      'incident-review',
      ['billing_metadata:encrypted_work_entry'],
      'audit-rc-7'
    );

    $decision = CorrelationBroker::evaluate($context);

    $this->assertFalse($decision->allowed());
    $this->assertSame('metadata_correlation_denied', $decision->reasonCode());
    $this->assertSame(['billing_metadata:encrypted_work_entry'], $decision->deniedPairs());
  }

  public function testComposeDeniesBillingMetadataCorrelationWithFinancialPayload(): void
  {
    $context = new CorrelationContext(
      'self-service-earnings',
      'user-123',
      'user',
      'self-service',
      ['billing_metadata:financial_payload'],
      'audit-rc-8'
    );

    $result = CorrelationBroker::compose(
      ['customer_id' => 'cus_123'],
      ['gross' => 100.25],
      'billing_metadata',
      'financial_payload',
      $context
    );

    $this->assertSame('denied', $result['status'] ?? null);
    $this->assertSame('metadata_correlation_denied', $result['decision']['reason'] ?? null);
  }
}
