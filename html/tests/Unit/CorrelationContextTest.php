<?php declare(strict_types=1);

use PayCal\Domain\Security\CorrelationContext;
use PayCal\Domain\Security\CorrelationDecision;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('security')]
final class CorrelationContextTest extends TestCase
{
  public function testNormalizesAndDeduplicatesRequestedPairs(): void
  {
    $context = new CorrelationContext(
      'security-incident',
      'user-123',
      'security-admin',
      'incident-response',
      [' site_metadata:financial_payload ', 'SITE_METADATA:FINANCIAL_PAYLOAD', 'invalid-pair', 'user_profile:session_metadata'],
      'audit-rc-1'
    );

    $this->assertSame(
      ['site_metadata:financial_payload', 'user_profile:session_metadata'],
      $context->correlationPairsRequested()
    );
  }

  public function testPrivilegedContextDetection(): void
  {
    $context = new CorrelationContext(
      'fraud-investigation',
      'user-123',
      'security',
      'fraud-review',
      ['user_profile:credential_metadata'],
      'audit-rc-2'
    );

    $this->assertTrue($context->isPrivilegedContext());
  }

  public function testNonPrivilegedContextDetection(): void
  {
    $context = new CorrelationContext(
      'self-service-earnings',
      'user-123',
      'user',
      'self-service',
      ['site_metadata:financial_payload'],
      'audit-rc-3'
    );

    $this->assertFalse($context->isPrivilegedContext());
  }

  public function testCorrelationDecisionArrayShape(): void
  {
    $decision = new CorrelationDecision(false, 'metadata_correlation_denied', [' USER_PROFILE:SESSION_METADATA ']);

    $this->assertSame(
      [
        'allowed' => false,
        'reason' => 'metadata_correlation_denied',
        'denied_pairs' => ['user_profile:session_metadata'],
      ],
      $decision->toArray()
    );
  }
}
