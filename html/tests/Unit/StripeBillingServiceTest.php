<?php declare(strict_types=1);

namespace PayCal\Tests\Unit;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\SubscriptionRepository;
use PayCal\Domain\StripeBillingService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StripeBillingServiceTest extends TestCase
{
  private StripeBillingService $service;

  protected function setUp(): void
  {
    parent::setUp();
    $this->service = new StripeBillingService();

    putenv('STRIPE_SECRET_KEY');
    putenv('STRIPE_WEBHOOK_SECRET');
    putenv('STRIPE_PREMIUM_PRICE_ID');
    unset($_ENV['STRIPE_SECRET_KEY'], $_ENV['STRIPE_WEBHOOK_SECRET'], $_ENV['STRIPE_PREMIUM_PRICE_ID']);
  }

  protected function tearDown(): void
  {
    putenv('STRIPE_SECRET_KEY');
    putenv('STRIPE_WEBHOOK_SECRET');
    putenv('STRIPE_PREMIUM_PRICE_ID');
    unset($_ENV['STRIPE_SECRET_KEY'], $_ENV['STRIPE_WEBHOOK_SECRET'], $_ENV['STRIPE_PREMIUM_PRICE_ID']);

    parent::tearDown();
  }

  public function testCreateCheckoutSessionFailsWithoutPriceId(): void
  {
    $result = $this->service->createCheckoutSession(
      'user-1',
      'user@example.com',
      'https://dev.paycal.local/settings/?ok=1',
      'https://dev.paycal.local/settings/?cancel=1'
    );

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('price ID is not configured', (string) $result['message']);
  }

  public function testCreatePortalSessionFailsWithoutSecretKey(): void
  {
    $result = $this->service->createPortalSession('user-1', 'https://dev.paycal.local/settings/');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('secret key is not configured', (string) $result['message']);
  }

  public function testProcessWebhookFailsWithoutWebhookSecret(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');

    $result = $this->service->processWebhook('{"id":"evt_1"}', 't=1,v1=test');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('webhook secret is not configured', (string) $result['message']);
  }

  public function testProcessWebhookFailsWhenPayloadEmptyEvenWithSecrets(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');
    putenv('STRIPE_WEBHOOK_SECRET=whsec_test_123');

    $result = $this->service->processWebhook('', 't=1,v1=test');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('payload is empty', (string) $result['message']);
  }

  public function testProcessWebhookCheckoutCompletedActivatesPremiumAndIsIdempotent(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');
    putenv('STRIPE_WEBHOOK_SECRET=whsec_test_123');

    $userUUID = 'stripe-test-user-' . bin2hex(random_bytes(6));
    $eventId = 'evt_' . bin2hex(random_bytes(8));
    $subscriptionId = 'sub_' . bin2hex(random_bytes(6));
    $customerId = 'cus_' . bin2hex(random_bytes(6));

    $payload = json_encode([
      'id' => $eventId,
      'type' => 'checkout.session.completed',
      'data' => [
        'object' => [
          'subscription' => $subscriptionId,
          'customer' => $customerId,
          'metadata' => [
            'user_uuid' => $userUUID,
          ],
        ],
      ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->assertIsString($payload);
    $signature = $this->buildStripeSignature($payload, 'whsec_test_123');

    try {
      $first = $this->service->processWebhook($payload, $signature);

      $this->assertTrue($first['success']);
      $this->assertSame('checkout.session.completed', $first['data']['event_type'] ?? null);
      $this->assertTrue(SubscriptionRepository::isPremiumActive($userUUID));

      $subscription = SubscriptionRepository::get($userUUID);
      $this->assertSame(Subscription::PREMIUM, $subscription['tier']);
      $this->assertSame($subscriptionId, (string) ($subscription['id'] ?? ''));
      $this->assertSame($customerId, (string) ($subscription['customer_id'] ?? ''));

      $second = $this->service->processWebhook($payload, $signature);
      $this->assertTrue($second['success']);
      $this->assertTrue((bool) ($second['data']['duplicate'] ?? false));
      $this->assertSame('1', Database::get($this->webhookTelemetryKey('duplicate')));
    } finally {
      Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
      Database::unlink(Keys::BILLING_WEBHOOK_EVENT . ':' . $eventId);
      Database::unlink($this->webhookTelemetryKey('processed'));
      Database::unlink($this->webhookTelemetryKey('processed:checkout_session_completed'));
      Database::unlink($this->webhookTelemetryKey('duplicate'));
      Database::unlink($this->webhookTelemetryKey('duplicate:checkout_session_completed'));
      Database::unlink('security:event:billing_webhook_processed');
      Database::unlink('security:event:billing_webhook_duplicate');
    }
  }

  public function testProcessWebhookSubscriptionCreatedActivatesPremium(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');
    putenv('STRIPE_WEBHOOK_SECRET=whsec_test_123');

    $userUUID = 'stripe-sub-created-user-' . bin2hex(random_bytes(6));
    $eventId = 'evt_' . bin2hex(random_bytes(8));
    $subscriptionId = 'sub_' . bin2hex(random_bytes(6));
    $customerId = 'cus_' . bin2hex(random_bytes(6));

    $payload = json_encode([
      'id' => $eventId,
      'type' => 'customer.subscription.created',
      'data' => [
        'object' => [
          'id' => $subscriptionId,
          'customer' => $customerId,
          'status' => 'active',
          'metadata' => [
            'user_uuid' => $userUUID,
          ],
        ],
      ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->assertIsString($payload);
    $signature = $this->buildStripeSignature($payload, 'whsec_test_123');

    try {
      $result = $this->service->processWebhook($payload, $signature);

      $this->assertTrue($result['success']);
      $this->assertSame('customer.subscription.updated', $result['data']['event_type'] ?? null);
      $this->assertTrue(SubscriptionRepository::isPremiumActive($userUUID));

      $subscription = SubscriptionRepository::get($userUUID);
      $this->assertSame(Subscription::PREMIUM, $subscription['tier']);
      $this->assertSame($subscriptionId, (string) ($subscription['id'] ?? ''));
      $this->assertSame($customerId, (string) ($subscription['customer_id'] ?? ''));
    } finally {
      Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
      Database::unlink(Keys::BILLING_WEBHOOK_EVENT . ':' . $eventId);
    }
  }

  public function testProcessWebhookRejectsInvalidSignature(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');
    putenv('STRIPE_WEBHOOK_SECRET=whsec_test_123');

    $payload = json_encode([
      'id' => 'evt_' . bin2hex(random_bytes(8)),
      'type' => 'checkout.session.completed',
      'data' => ['object' => ['metadata' => ['user_uuid' => 'bad-signature-user']]],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->assertIsString($payload);

    $result = $this->service->processWebhook($payload, 't=' . time() . ',v1=invalid');
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('verification failed', strtolower((string) $result['message']));
    $this->assertSame('1', Database::get($this->webhookTelemetryKey('verification_failed')));
    $this->assertSame('1', Database::get($this->webhookTelemetryKey('verification_failed:checkout_session_completed')));

    Database::unlink($this->webhookTelemetryKey('verification_failed'));
    Database::unlink($this->webhookTelemetryKey('verification_failed:checkout_session_completed'));
    Database::unlink('security:event:billing_webhook_verification_failed');
  }

  public function testCreatePortalSessionBackfillsCustomerIdFromSubscriptionLookup(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');

    $userUUID = 'stripe-portal-backfill-' . bin2hex(random_bytes(6));
    $subscriptionId = 'sub_' . bin2hex(random_bytes(6));
    $customerId = 'cus_' . bin2hex(random_bytes(6));
    $returnURL = 'https://dev.paycal.local/profile/?billing=portal';
    $portalURL = 'https://billing.stripe.test/session/' . bin2hex(random_bytes(6));

    SubscriptionRepository::upgradeToPremium($userUUID, $subscriptionId, null);

    $service = new StripeBillingService(
      static function (string $secretKey, string $lookupSubscriptionId) use ($subscriptionId, $customerId): string {
        TestCase::assertSame('sk_test_123', $secretKey);
        TestCase::assertSame($subscriptionId, $lookupSubscriptionId);

        return $customerId;
      },
      static function (string $secretKey, string $portalCustomerId, string $portalReturnURL) use ($customerId, $returnURL, $portalURL): string {
        TestCase::assertSame('sk_test_123', $secretKey);
        TestCase::assertSame($customerId, $portalCustomerId);
        TestCase::assertSame($returnURL, $portalReturnURL);

        return $portalURL;
      }
    );

    try {
      $result = $service->createPortalSession($userUUID, $returnURL);

      $this->assertTrue($result['success']);
      $this->assertSame($portalURL, (string) ($result['data']['portal_url'] ?? ''));

      $subscription = SubscriptionRepository::get($userUUID);
      $this->assertSame($customerId, (string) ($subscription['customer_id'] ?? ''));
      $this->assertSame($subscriptionId, (string) ($subscription['id'] ?? ''));
    } finally {
      Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
    }
  }

  public function testEnqueueAndDrainWebhookQueueProcessesValidEvent(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');
    putenv('STRIPE_WEBHOOK_SECRET=whsec_test_123');

    $userUUID = 'stripe-queue-user-' . bin2hex(random_bytes(6));
    $eventId = 'evt_' . bin2hex(random_bytes(8));
    $subscriptionId = 'sub_' . bin2hex(random_bytes(6));
    $customerId = 'cus_' . bin2hex(random_bytes(6));

    $payload = json_encode([
      'id' => $eventId,
      'type' => 'checkout.session.completed',
      'data' => [
        'object' => [
          'subscription' => $subscriptionId,
          'customer' => $customerId,
          'metadata' => [
            'user_uuid' => $userUUID,
          ],
        ],
      ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->assertIsString($payload);
    $signature = $this->buildStripeSignature($payload, 'whsec_test_123');

    try {
      $queued = $this->service->enqueueWebhook($payload, $signature);
      $this->assertTrue($queued['success']);

      $drained = $this->service->drainWebhookQueue(10);
      $this->assertTrue($drained['success']);
      $data = $drained['data'];
      $this->assertSame(1, (int) ($data['processed'] ?? 0));
      $this->assertSame(1, (int) ($data['succeeded'] ?? 0));
      $this->assertSame(0, (int) ($data['failed'] ?? 0));

      $subscription = SubscriptionRepository::get($userUUID);
      $this->assertSame(Subscription::PREMIUM, $subscription['tier']);
      $this->assertSame($subscriptionId, (string) ($subscription['id'] ?? ''));
      $this->assertSame($customerId, (string) ($subscription['customer_id'] ?? ''));
    } finally {
      Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
      Database::unlink(Keys::BILLING_WEBHOOK_EVENT . ':' . $eventId);
      Database::unlink(Keys::BILLING_WEBHOOK_QUEUE);
      Database::unlink(Keys::BILLING_WEBHOOK_DEAD_LETTER);
      Database::unlink($this->webhookTelemetryKey('queued'));
      Database::unlink($this->webhookTelemetryKey('processed'));
      Database::unlink($this->webhookTelemetryKey('processed:checkout_session_completed'));
      Database::unlink('security:event:billing_webhook_queued');
      Database::unlink('security:event:billing_webhook_processed');
    }
  }

  public function testDrainWebhookQueueDeadLettersInvalidSignatureWithoutRetry(): void
  {
    putenv('STRIPE_SECRET_KEY=sk_test_123');
    putenv('STRIPE_WEBHOOK_SECRET=whsec_test_123');

    $payload = json_encode([
      'id' => 'evt_' . bin2hex(random_bytes(8)),
      'type' => 'checkout.session.completed',
      'data' => ['object' => ['metadata' => ['user_uuid' => 'queue-invalid-signature-user']]],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->assertIsString($payload);

    try {
      $queued = $this->service->enqueueWebhook($payload, 't=' . time() . ',v1=invalid');
      $this->assertTrue($queued['success']);

      $drained = $this->service->drainWebhookQueue(10);
      $this->assertTrue($drained['success']);
      $data = $drained['data'];
      $this->assertSame(1, (int) ($data['processed'] ?? 0));
      $this->assertSame(1, (int) ($data['failed'] ?? 0));
      $this->assertSame(0, (int) ($data['requeued'] ?? 0));
      $this->assertSame(1, (int) ($data['dead_lettered'] ?? 0));
      $this->assertSame('1', Database::get($this->webhookTelemetryKey('queue_dead_lettered')));
    } finally {
      Database::unlink(Keys::BILLING_WEBHOOK_QUEUE);
      Database::unlink(Keys::BILLING_WEBHOOK_DEAD_LETTER);
      Database::unlink($this->webhookTelemetryKey('queued'));
      Database::unlink($this->webhookTelemetryKey('verification_failed'));
      Database::unlink($this->webhookTelemetryKey('verification_failed:checkout_session_completed'));
      Database::unlink($this->webhookTelemetryKey('queue_dead_lettered'));
      Database::unlink('security:event:billing_webhook_queued');
      Database::unlink('security:event:billing_webhook_verification_failed');
      Database::unlink('security:event:billing_webhook_queue_dead_lettered');
    }
  }

  private function webhookTelemetryKey(string $metric): string
  {
    return Keys::TELEMETRY . ':billing:webhook:' . $metric . ':' . date('Y-m-d');
  }

  private function buildStripeSignature(string $payload, string $secret): string
  {
    $timestamp = time();
    $signedPayload = $timestamp . '.' . $payload;
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return 't=' . $timestamp . ',v1=' . $signature;
  }
}
