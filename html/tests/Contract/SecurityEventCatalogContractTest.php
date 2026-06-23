<?php declare(strict_types=1);

use PayCal\Observability\SecurityEventCatalog;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
#[Group('security')]
final class SecurityEventCatalogContractTest extends TestCase
{
  public function testCoreMigrationEventsResolveToArgusNames(): void
  {
    $cases = [
      'rate_limit_triggered' => 'request_guard.rate_limit_triggered',
      'entry_locked_attempt' => 'lock_boundary.mutation_blocked',
      'redis_tier0_alert' => 'redis.tier0_alert',
      'passkey_login_success' => 'auth.passkey_login_success',
      'billing_webhook_processed' => 'stripe.webhook_verified',
      'billing_webhook_verification_failed' => 'stripe.webhook_failed',
    ];

    foreach ($cases as $legacy => $expected) {
      $resolved = SecurityEventCatalog::resolve($legacy);
      $this->assertNotNull($resolved, 'Missing catalog mapping for ' . $legacy);
      $this->assertSame($expected, $resolved['name']);
    }
  }
}
