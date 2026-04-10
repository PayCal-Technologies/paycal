<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\Enums\SubscriptionStatus;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

final class BillingControllerIntegrationTest extends TestCase
{
  /**
   * @return array<string, mixed>
   */
  private function runBillingCall(string $method, string $requestMethod, array $server = [], array $cookies = [], array $post = [], string $setupScript = ''): array
  {
    $bootstrap = var_export(__DIR__ . '/../../bootstrap/Classes.php', true);
    $methodExport = var_export($method, true);
    $requestMethodExport = var_export($requestMethod, true);
    $serverExport = var_export($server, true);
    $cookiesExport = var_export($cookies, true);
    $postExport = var_export($post, true);

    $script = 'require ' . $bootstrap . '; '
      . '$_SERVER["REQUEST_METHOD"] = ' . $requestMethodExport . '; '
      . 'foreach (' . $serverExport . ' as $k => $v) { $_SERVER[$k] = $v; } '
      . 'foreach (' . $cookiesExport . ' as $k => $v) { $_COOKIE[$k] = $v; } '
      . 'foreach (' . $postExport . ' as $k => $v) { $_POST[$k] = $v; } '
      . $setupScript
      . 'ob_start(); '
      . '$c = new \\PayCal\\Controllers\\BillingController(); '
      . '$m = ' . $methodExport . '; '
      . '$c->{$m}(); '
      . 'echo ob_get_clean();';

    $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script));
    $this->assertNotFalse($output);

    $decoded = json_decode((string) $output, true);
    $this->assertIsArray($decoded);

    return $decoded;
  }

  /**
   * @return array{userUUID: string, email: string, sessionHash: string}
   */
  private function createAuthenticatedBillingContext(array $subscription = []): array
  {
    $userUUID = 'billing-it-' . bin2hex(random_bytes(8));
    $email = $userUUID . '@example.test';
    $sessionHash = hash('sha256', bin2hex(random_bytes(32)));

    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => $email,
      'full_name' => 'Billing Integration User',
      'email_verified' => '1',
      'auth_level' => (string) AuthLevel::USER->value,
    ]);
    UserRepository::setUserEmail($userUUID, $email);

    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'user_uuid' => $userUUID,
      'created_at' => date('c'),
    ]);
    Database::expire(Keys::SESSION . ':' . $sessionHash, 3600);

    $defaults = [
      'tier' => Subscription::PREMIUM->value,
      'status' => SubscriptionStatus::ACTIVE->value,
      'subscription_id' => 'sub_it_' . bin2hex(random_bytes(4)),
      'subscription_start_date' => date('c', time() - 7 * 86400),
      'subscription_renewal_date' => date('c', time() + 23 * 86400),
      'subscription_cancel_date' => '',
    ];

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $userUUID, array_merge($defaults, $subscription));

    return [
      'userUUID' => $userUUID,
      'email' => $email,
      'sessionHash' => $sessionHash,
    ];
  }

  /**
   * @param array{userUUID: string, email: string, sessionHash: string} $context
   */
  private function cleanupAuthenticatedBillingContext(array $context): void
  {
    Database::unlink(Keys::SESSION . ':' . $context['sessionHash']);
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $context['userUUID']);
    Database::unlink(Keys::USER . ':' . $context['userUUID']);
    Database::unlink(Keys::EMAIL . ':' . $context['email']);
    Database::unlink(Keys::EMAIL . $context['email']);
  }

  private function seedBillingCsrfToken(string $userUUID, string $formType = 'settings'): string
  {
    $token = bin2hex(random_bytes(16));
    Database::set('user:' . $userUUID . ':csrf:' . $formType . ':' . $token, (string) time(), 3600);
    return $token;
  }

  private function publicToggleSetupScript(): string
  {
    $runtime = var_export(__DIR__ . '/../../extensions/runtime.php', true);

    return 'require_once ' . $runtime . '; '
      . '$ref = new \\ReflectionProperty(\\PayCal\\Domain\\Extensions\\ExtensionRuntime::class, "active"); '
      . '$ref->setValue(null, ['
      . '"billing-provider" => ['
      . '"id" => "billing-provider", '
      . '"version" => "1.0.0", '
      . '"source" => "basic", '
      . '"capabilities" => ["billing.provider" => "public-toggle"],'
      . '],'
      . ']); ';
  }

  public function testCreateCheckoutSessionWithoutSessionReturnsUnauthorized(): void
  {
    $decoded = $this->runBillingCall('createCheckoutSession', 'POST');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Unauthorized', (string) ($decoded['message'] ?? ''));
  }

  public function testCreatePortalSessionWithoutSessionReturnsUnauthorized(): void
  {
    $decoded = $this->runBillingCall('createPortalSession', 'POST');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Unauthorized', (string) ($decoded['message'] ?? ''));
  }

  public function testGetSubscriptionWithoutSessionReturnsUnauthorized(): void
  {
    $decoded = $this->runBillingCall('getSubscription', 'GET');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Unauthorized', (string) ($decoded['message'] ?? ''));
  }

  public function testGetWebhookTelemetryWithoutSessionReturnsUnauthorized(): void
  {
    $decoded = $this->runBillingCall('getWebhookTelemetry', 'GET');

    $this->assertSame('error', $decoded['status'] ?? null);
    $this->assertStringContainsString('Unauthorized', (string) ($decoded['message'] ?? ''));
  }

  public function testWebhookWithoutConfigurationReturnsError(): void
  {
    $decoded = $this->runBillingCall('webhook', 'POST', [
      'HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef',
    ]);

    $this->assertSame('error', $decoded['status'] ?? null);
    $message = (string) ($decoded['message'] ?? '');
    $this->assertTrue(
      str_contains($message, 'Stripe webhooks are unavailable in public toggle mode')
      ||
      str_contains($message, 'Webhook payload is empty')
      || str_contains($message, 'Stripe secret key is not configured')
      || str_contains($message, 'Stripe webhook secret is not configured'),
      'Unexpected webhook error: ' . $message
    );
  }

  public function testGetSubscriptionMarksFutureCancelDateAsPendingCancellation(): void
  {
    $context = $this->createAuthenticatedBillingContext([
      'subscription_cancel_date' => date('c', time() + 2 * 86400),
    ]);

    try {
      $decoded = $this->runBillingCall('getSubscription', 'GET', [], [
        'PAYCAL_AUTH' => $context['sessionHash'],
      ]);

      $this->assertSame('success', $decoded['status'] ?? null);
      $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
      $this->assertTrue((bool) ($data['is_premium'] ?? false));
      $this->assertTrue((bool) ($data['is_pending_cancellation'] ?? false));
    } finally {
      $this->cleanupAuthenticatedBillingContext($context);
    }
  }

  public function testGetSubscriptionDoesNotMarkPastCancelDateAsPendingCancellation(): void
  {
    $context = $this->createAuthenticatedBillingContext([
      'subscription_cancel_date' => date('c', time() - 2 * 86400),
    ]);

    try {
      $decoded = $this->runBillingCall('getSubscription', 'GET', [], [
        'PAYCAL_AUTH' => $context['sessionHash'],
      ]);

      $this->assertSame('success', $decoded['status'] ?? null);
      $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
      $this->assertTrue((bool) ($data['is_premium'] ?? false));
      $this->assertFalse((bool) ($data['is_pending_cancellation'] ?? false));
    } finally {
      $this->cleanupAuthenticatedBillingContext($context);
    }
  }

  public function testCreateCheckoutSessionInPublicToggleModeEnablesPremium(): void
  {
    $context = $this->createAuthenticatedBillingContext([
      'tier' => Subscription::FREE->value,
      'status' => SubscriptionStatus::ACTIVE->value,
      'subscription_id' => '',
      'subscription_start_date' => '',
      'subscription_renewal_date' => '',
      'subscription_cancel_date' => '',
    ]);
    $csrfToken = $this->seedBillingCsrfToken($context['userUUID']);

    try {
      $decoded = $this->runBillingCall(
        'createCheckoutSession',
        'POST',
        [],
        ['PAYCAL_AUTH' => $context['sessionHash']],
        ['csrf_token' => $csrfToken],
        $this->publicToggleSetupScript()
      );

      $this->assertSame('success', $decoded['status'] ?? null);
      $this->assertSame('premium', $decoded['data']['tier'] ?? null);
      $this->assertSame('public-toggle', $decoded['data']['billing_provider'] ?? null);

      $subscription = Database::hgetall(Keys::USER_SUBSCRIPTION . ':' . $context['userUUID']);
      $this->assertSame('premium', strtolower((string) ($subscription['tier'] ?? '')));
    } finally {
      $this->cleanupAuthenticatedBillingContext($context);
    }
  }

  public function testCancelSubscriptionInPublicToggleModeDisablesPremiumWithoutPhrase(): void
  {
    $context = $this->createAuthenticatedBillingContext();
    $csrfToken = $this->seedBillingCsrfToken($context['userUUID']);

    try {
      $decoded = $this->runBillingCall(
        'cancelSubscription',
        'POST',
        [],
        ['PAYCAL_AUTH' => $context['sessionHash']],
        ['csrf_token' => $csrfToken],
        $this->publicToggleSetupScript()
      );

      $this->assertSame('success', $decoded['status'] ?? null);
      $this->assertSame('free', $decoded['data']['tier'] ?? null);
      $this->assertSame('public-toggle', $decoded['data']['billing_provider'] ?? null);

      $subscription = Database::hgetall(Keys::USER_SUBSCRIPTION . ':' . $context['userUUID']);
      $this->assertSame('free', strtolower((string) ($subscription['tier'] ?? '')));
    } finally {
      $this->cleanupAuthenticatedBillingContext($context);
    }
  }
}
