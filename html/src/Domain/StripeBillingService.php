<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Infrastructure\Telemetry\SecurityLog;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * StripeBillingService.php
 *
 * Purpose: Encapsulate Stripe checkout, billing-portal, subscription, and
 * webhook flows behind a single provider integration boundary.
 *
 * Developer notes:
 * - Webhook deduplication, retry semantics, and queue limits are part of the
 *   integration contract.
 * - Keep Stripe transport details here so controllers stay provider-agnostic.
 *
 * Architectural role:
 * - Reusable domain integration service for Stripe checkout, portal, and
 *   webhook-driven billing operations.
 * - Encapsulates external billing provider behavior outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * StripeBillingService
 *
 * Handles Stripe Checkout, Billing Portal sessions, and webhook event processing.
 */
final class StripeBillingService
{
  private const WEBHOOK_EVENT_TTL_SECONDS = 604800; // 7 days
  private const WEBHOOK_TELEMETRY_PREFIX = 'billing:webhook';
  // Queue capacity intentionally small: signature is pre-validated before enqueue
  // so only cryptographically verified Stripe events reach this list.
  private const WEBHOOK_QUEUE_MAX_ITEMS = 25;
  private const WEBHOOK_QUEUE_MAX_RETRIES = 3;
  private const WEBHOOK_DEAD_LETTER_MAX_ITEMS = 50;
  // Stripe signs with HMAC-SHA256 using STRIPE_WEBHOOK_SECRET. A valid Stripe-Signature
  // header contains a Unix timestamp (t=) and at least one v1= HMAC value.
  // Reject anything more than 5 minutes old (Stripe's own recommended tolerance is 5 min).
  private const WEBHOOK_TIMESTAMP_TOLERANCE_SECONDS = 300;

  private readonly ?\Closure $portalCustomerResolver;
  private readonly ?\Closure $portalSessionCreator;
  private readonly ?\Closure $subscriptionCanceler;

  /**
   * Initializes a new instance.
   */
  public function __construct(?\Closure $portalCustomerResolver = null, ?\Closure $portalSessionCreator = null, ?\Closure $subscriptionCanceler = null)
  {
    $this->portalCustomerResolver = $portalCustomerResolver;
    $this->portalSessionCreator = $portalSessionCreator;
    $this->subscriptionCanceler = $subscriptionCanceler;
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function createCheckoutSession(string $userUUID, string $userEmail, string $successURL, string $cancelURL): array
  {
    $priceId = $this->requireEnv('STRIPE_PREMIUM_PRICE_ID');
    if ($priceId === '') {
      return $this->fail('Stripe Premium price ID is not configured.');
    }

    $secretKey = $this->requireEnv('STRIPE_SECRET_KEY');
    if ($secretKey === '') {
      return $this->fail('Stripe secret key is not configured.');
    }

    try {
      $client = new StripeClient($secretKey);
      $subscription = SubscriptionRepository::get($userUUID);
      $storedCustomerId = is_scalar($subscription['customer_id'] ?? null)
        ? trim((string) $subscription['customer_id'])
        : '';

      // Email-first canonicalization prevents Stripe customer drift across repeated upgrades.
      $canonicalCustomerId = $this->resolveCanonicalCustomerIdByEmail($secretKey, $userEmail);
      if ($canonicalCustomerId === '' && $storedCustomerId !== '') {
        $canonicalCustomerId = $storedCustomerId;
      }

      if ($canonicalCustomerId !== '') {
        SubscriptionRepository::storeStripeCustomerId($userUUID, $canonicalCustomerId);
        $this->cleanupLegacyCustomersByEmail($secretKey, $userEmail, $canonicalCustomerId, $userUUID);
      }

      $checkoutParams = [
        'mode' => 'subscription',
        'line_items' => [[
          'price' => $priceId,
          'quantity' => 1,
        ]],
        'success_url' => $this->appendSessionIdPlaceholder($successURL),
        'cancel_url' => $cancelURL,
        'metadata' => [
          'user_uuid' => $userUUID,
          'paycal_plan' => 'premium_flat',
        ],
        'subscription_data' => [
          'metadata' => [
            'user_uuid' => $userUUID,
            'paycal_plan' => 'premium_flat',
          ],
        ],
      ];

      if ($canonicalCustomerId !== '') {
        $checkoutParams['customer'] = $canonicalCustomerId;
      } else {
        $checkoutParams['customer_email'] = $userEmail;
      }

      $session = $client->checkout->sessions->create($checkoutParams);

      return $this->ok('Stripe checkout session created.', [
        'checkout_url' => (string) ($session->url ?? ''),
        'session_id' => (string) ($session->id ?? ''),
      ]);
    } catch (\Throwable $e) {
      return $this->fail('Failed to create Stripe checkout session.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function createPortalSession(string $userUUID, string $returnURL): array
  {
    $secretKey = $this->requireEnv('STRIPE_SECRET_KEY');
    if ($secretKey === '') {
      return $this->fail('Stripe secret key is not configured.');
    }

    $subscription = SubscriptionRepository::get($userUUID);
    $subscriptionId = is_scalar($subscription['id'] ?? null) ? (string) $subscription['id'] : '';
    $customerId = is_scalar($subscription['customer_id'] ?? null) ? trim((string) $subscription['customer_id']) : '';

    if ($subscriptionId === '' && $customerId === '') {
      return $this->fail('No active Stripe subscription found for this user.');
    }

    try {
      $client = new StripeClient($secretKey);
      if ($customerId === '') {
        $customerId = $this->resolvePortalCustomerId($secretKey, $subscriptionId);

        if ($customerId !== '') {
          SubscriptionRepository::storeStripeCustomerId($userUUID, $customerId);
        }
      }

      if ($customerId === '') {
        return $this->fail('Unable to resolve Stripe customer for billing portal.');
      }

      return $this->ok('Stripe billing portal session created.', [
        'portal_url' => $this->createPortalUrl($secretKey, $customerId, $returnURL),
      ]);
    } catch (\Throwable $e) {
      return $this->fail('Failed to create Stripe billing portal session.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function confirmCheckoutSession(string $userUUID, string $sessionId): array
  {
    $secretKey = $this->requireEnv('STRIPE_SECRET_KEY');
    if ($secretKey === '') {
      return $this->fail('Stripe secret key is not configured.');
    }

    $normalizedSessionId = trim($sessionId);
    if ($normalizedSessionId === '') {
      return $this->fail('Stripe checkout session ID is required.');
    }

    try {
      $client = new StripeClient($secretKey);
      $session = $client->checkout->sessions->retrieve($normalizedSessionId, []);

      $sessionStatus = strtolower((string) ($session->status ?? ''));
      if ($sessionStatus !== 'complete') {
        return $this->fail('Stripe checkout session is not complete.', [
          'session_status' => $sessionStatus,
        ]);
      }

      $sessionUserUUID = '';
      if (isset($session->metadata)) {
        $metadataUserUUID = $session->metadata->user_uuid ?? '';
        if (is_scalar($metadataUserUUID)) {
          $sessionUserUUID = trim((string) $metadataUserUUID);
        }
      }

      if ($sessionUserUUID === '' && is_scalar($session->client_reference_id ?? null)) {
        $sessionUserUUID = trim((string) $session->client_reference_id);
      }

      if ($sessionUserUUID === '') {
        return $this->fail('Stripe checkout session is missing user metadata.');
      }

      if (!hash_equals($sessionUserUUID, $userUUID)) {
        return $this->fail('Checkout session does not belong to this authenticated user.');
      }

      $subscriptionId = is_scalar($session->subscription ?? null) ? trim((string) $session->subscription) : null;
      $customerId = $this->extractCustomerId($session);
      SubscriptionRepository::upgradeToPremium($userUUID, $subscriptionId, $customerId);

      return $this->ok('Subscription activated from checkout confirmation.', [
        'user_uuid' => $userUUID,
        'subscription_id' => (string) ($subscriptionId ?? ''),
        'customer_id' => (string) ($customerId ?? ''),
      ]);
    } catch (\Throwable $e) {
      return $this->fail('Failed to confirm Stripe checkout session.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function cancelSubscription(string $userUUID): array
  {
    $secretKey = $this->requireEnv('STRIPE_SECRET_KEY');
    if ($secretKey === '') {
      return $this->fail('Stripe secret key is not configured.');
    }

    $subscription = SubscriptionRepository::get($userUUID);
    $subscriptionId = is_scalar($subscription['id'] ?? null) ? trim((string) $subscription['id']) : '';
    $customerId = null;
    if (is_scalar($subscription['customer_id'] ?? null)) {
      $customerId = trim((string) $subscription['customer_id']);
      if ($customerId === '') {
        $customerId = null;
      }
    }

    if ($subscriptionId === '' && $customerId === null) {
      return $this->fail('No Stripe subscription found for this user.');
    }

    try {
      // @phpstan-ignore-next-line Guard clause above ensures customerId exists when subscriptionId is empty.
      if ($subscriptionId === '' && $customerId !== null) {
        $subscriptionId = $this->resolveActiveSubscriptionId($secretKey, $customerId);
      }

      if ($subscriptionId === '') {
        return $this->fail('Unable to resolve Stripe subscription for cancellation.');
      }

      $canceled = $this->cancelStripeSubscription($secretKey, $subscriptionId);
      $stripeStatus = $this->extractStripeStatus($canceled);
      SubscriptionRepository::downgradeToFree($userUUID);

      return $this->ok('Subscription canceled successfully.', [
        'subscription_id' => $subscriptionId,
        'stripe_status' => $stripeStatus,
      ]);
    } catch (\Throwable $e) {
      return $this->fail('Failed to cancel Stripe subscription.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /** @return array{success: bool, message: string, data: array<string, mixed>} */
  public function reconcileSubscriptionState(string $userUUID): array
  {
    $secretKey = $this->requireEnv('STRIPE_SECRET_KEY');
    if ($secretKey === '') {
      return $this->fail('Stripe secret key is not configured.');
    }

    $subscription = SubscriptionRepository::get($userUUID);
    $subscriptionId = is_scalar($subscription['id'] ?? null) ? trim((string) $subscription['id']) : '';
    $customerId = null;
    if (is_scalar($subscription['customer_id'] ?? null)) {
      $customerId = trim((string) $subscription['customer_id']);
      if ($customerId === '') {
        $customerId = null;
      }
    }

    if ($subscriptionId === '' && $customerId === null) {
      return $this->ok('No Stripe identifiers available for reconciliation.', [
        'user_uuid' => $userUUID,
      ]);
    }

    try {
      $client = new StripeClient($secretKey);
      $stripeSubscription = null;

      if ($subscriptionId !== '') {
        $stripeSubscription = $client->subscriptions->retrieve($subscriptionId, []);
      }

      // @phpstan-ignore-next-line Guard clause above ensures at least one Stripe identifier is available.
      if ($stripeSubscription === null && $customerId !== null) {
        $resolvedId = $this->resolveActiveSubscriptionId($secretKey, $customerId);
        if ($resolvedId !== '') {
          $stripeSubscription = $client->subscriptions->retrieve($resolvedId, []);
          $subscriptionId = $resolvedId;
        }
      }

      if ($stripeSubscription === null) {
        return $this->ok('No Stripe subscription found during reconciliation.', [
          'user_uuid' => $userUUID,
        ]);
      }

      $status = strtolower(trim((string) ($stripeSubscription->status ?? '')));
      $cancelAtPeriodEnd = (bool) ($stripeSubscription->cancel_at_period_end ?? false);
      $stripeCustomerId = is_scalar($stripeSubscription->customer ?? null)
        ? trim((string) $stripeSubscription->customer)
        : '';
      $resolvedSubscriptionId = is_scalar($stripeSubscription->id ?? null)
        ? trim((string) $stripeSubscription->id)
        : $subscriptionId;
      $cancelAt = is_scalar($stripeSubscription->cancel_at ?? null)
        ? (int) $stripeSubscription->cancel_at
        : 0;
      $periodEnd = $this->extractPeriodEndUnixTime($stripeSubscription);
      $cancelAtUnixTime = $cancelAt > 0 ? $cancelAt : ($periodEnd > 0 ? $periodEnd : null);
      $hasScheduledCancellation = $cancelAtPeriodEnd || $cancelAt > time();

      if ($status === 'active' || $status === 'trialing') {
        if ($hasScheduledCancellation) {
          // User canceled in Stripe portal—keep Premium access until period end,
          // but mark as pending cancellation so UI can show end date.
          SubscriptionRepository::markPendingCancellation(
            $userUUID,
            $cancelAtUnixTime,
            $resolvedSubscriptionId !== '' ? $resolvedSubscriptionId : null,
            $stripeCustomerId !== '' ? $stripeCustomerId : null
          );
        } else {
          SubscriptionRepository::upgradeToPremium(
            $userUUID,
            $resolvedSubscriptionId !== '' ? $resolvedSubscriptionId : null,
            $stripeCustomerId !== '' ? $stripeCustomerId : null,
            $periodEnd > 0 ? $periodEnd : null
          );
        }
      } elseif ($status === 'past_due' || $status === 'unpaid' || $status === 'incomplete_expired') {
        SubscriptionRepository::markPastDue($userUUID);
        if ($stripeCustomerId !== '') {
          SubscriptionRepository::storeStripeCustomerId($userUUID, $stripeCustomerId);
        }
      } else {
        SubscriptionRepository::downgradeToFree($userUUID);
      }

      return $this->ok('Stripe subscription reconciled.', [
        'user_uuid' => $userUUID,
        'stripe_status' => $status,
        'cancel_at_period_end' => $cancelAtPeriodEnd ? 'true' : 'false',
        'subscription_id' => $resolvedSubscriptionId,
      ]);
    } catch (\Throwable $e) {
      return $this->fail('Failed to reconcile Stripe subscription state.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Process Stripe webhook payload and synchronize subscription state.
   *
   * @param string $payload Raw JSON body
   * @param string $signatureHeader Stripe-Signature header
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function processWebhook(string $payload, string $signatureHeader): array
  {
    $secretKey = $this->requireEnv('STRIPE_SECRET_KEY');
    $webhookSecret = $this->requireEnv('STRIPE_WEBHOOK_SECRET');
    $payloadContext = $this->extractWebhookPayloadContext($payload);
    $signatureContext = $this->extractSignatureContext($signatureHeader);

    if ($secretKey === '') {
      $this->recordWebhookFailure('secret_key_missing', $payloadContext + $signatureContext);
      return $this->fail('Stripe secret key is not configured.');
    }

    if ($webhookSecret === '') {
      $this->recordWebhookFailure('webhook_secret_missing', $payloadContext + $signatureContext);
      return $this->fail('Stripe webhook secret is not configured.');
    }

    if ($payload === '') {
      $this->recordWebhookFailure('payload_empty', $signatureContext);
      return $this->fail('Webhook payload is empty.');
    }

    if ($signatureHeader === '') {
      $this->recordWebhookFailure('signature_missing', $payloadContext + [
        'signature_timestamp_missing' => 'true',
      ]);
      return $this->fail('Missing Stripe-Signature header.');
    }

    try {
      $event = Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
      $eventContext = $this->extractWebhookEventContext($event);
      $eventId = $this->stringOrNull($eventContext['event_id'] ?? null) ?? '';
      $eventType = $this->stringOrNull($eventContext['event_type'] ?? null) ?? '';
      if ($eventId === '') {
        $this->recordWebhookFailure('event_id_missing', $eventContext + $signatureContext);
        return $this->fail('Webhook event ID missing.');
      }

      if ($this->isWebhookEventProcessed($eventId)) {
        $this->recordWebhookOutcome('duplicate', $eventContext + [
          'event_id' => $eventId,
          'event_type' => $eventType,
        ] + $signatureContext);
        return $this->ok('Webhook event already processed.', [
          'event_id' => $eventId,
          'event_type' => $eventType,
          'duplicate' => true,
        ]);
      }

      $result = $this->applyWebhookEvent($event);
      if ($result['success']) {
        $this->markWebhookEventProcessed($eventId);
        $this->recordWebhookOutcome('processed', $eventContext + [
          'event_id' => $eventId,
          'event_type' => $eventType,
          'message' => $result['message'],
          'user_uuid' => $this->stringOrNull($result['data']['user_uuid'] ?? null),
        ] + $signatureContext);
      } else {
        $this->recordWebhookFailure('event_rejected', $eventContext + [
          'event_id' => $eventId,
          'event_type' => $eventType,
          'message' => $result['message'],
          'user_uuid' => $this->stringOrNull($result['data']['user_uuid'] ?? null),
        ] + $signatureContext);
      }

      return $result;
    } catch (\Throwable $e) {
      $this->recordWebhookFailure('verification_failed', $payloadContext + [
        'error' => $e->getMessage(),
        'error_type' => get_class($e),
        'stripe_error_code' => $this->extractStripeExceptionValue($e, 'getStripeCode'),
        'stripe_http_status' => $this->extractStripeExceptionValue($e, 'getHttpStatus'),
        'stripe_request_id' => $this->extractStripeExceptionValue($e, 'getRequestId'),
      ] + $signatureContext);
      return $this->fail('Stripe webhook verification failed.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Perform a fast pre-queue Stripe signature check.
   *
   * This is NOT a full cryptographic verification (that happens in processWebhook).
   * It checks two inexpensive conditions that reject the vast majority of garbage:
   *   1. The t= timestamp in the header is within WEBHOOK_TIMESTAMP_TOLERANCE_SECONDS.
   *   2. The header contains at least one v1= HMAC value that is 64 hex chars.
   *
   * Reject at the door so forgery and flood traffic never occupy queue slots.
   * Full Webhook::constructEvent() verification still runs during drain.
   *
   * @return array{valid: bool, reason: string}
   */
  private function preValidateSignatureHeader(string $signatureHeader, int $now): array
  {
    if ($signatureHeader === '') {
      return ['valid' => false, 'reason' => 'signature_header_empty'];
    }

    $timestamp = 0;
    $hasV1 = false;

    foreach (explode(',', $signatureHeader) as $part) {
      $part = trim($part);
      if (str_starts_with($part, 't=')) {
        $ts = substr($part, 2);
        if (ctype_digit($ts)) {
          $timestamp = (int) $ts;
        }
      } elseif (str_starts_with($part, 'v1=')) {
        $hmac = substr($part, 3);
        // SHA-256 HMAC in hex = exactly 64 lowercase hex characters.
        if (strlen($hmac) === 64 && ctype_xdigit($hmac)) {
          $hasV1 = true;
        }
      }
    }

    if ($timestamp === 0) {
      return ['valid' => false, 'reason' => 'signature_timestamp_missing'];
    }

    if (abs($now - $timestamp) > self::WEBHOOK_TIMESTAMP_TOLERANCE_SECONDS) {
      return ['valid' => false, 'reason' => 'signature_timestamp_stale'];
    }

    if (!$hasV1) {
      return ['valid' => false, 'reason' => 'signature_v1_missing_or_malformed'];
    }

    return ['valid' => true, 'reason' => 'ok'];
  }

  /**
   * Queue a Stripe webhook payload for asynchronous processing.
   *
   * Signature is pre-validated (timestamp freshness + HMAC format) before the
   * payload is queued. Full cryptographic verification happens during drain.
   * This rejects garbage at the door and keeps the queue small.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function enqueueWebhook(string $payload, string $signatureHeader): array
  {
    $payloadContext = $this->extractWebhookPayloadContext($payload);
    $signatureContext = $this->extractSignatureContext($signatureHeader);

    if ($payload === '') {
      $this->recordWebhookFailure('payload_empty', $signatureContext);
      return $this->fail('Webhook payload is empty.');
    }

    if ($signatureHeader === '') {
      $this->recordWebhookFailure('signature_missing', $payloadContext + [
        'signature_timestamp_missing' => 'true',
      ]);
      return $this->fail('Missing Stripe-Signature header.');
    }

    // Pre-validate before touching the queue: reject stale timestamps and
    // malformed HMAC values without performing a full signature computation.
    $preCheck = $this->preValidateSignatureHeader($signatureHeader, time());
    if (!$preCheck['valid']) {
      $this->recordWebhookFailure('signature_precheck_failed', $payloadContext + $signatureContext + [
        'precheck_reason' => $preCheck['reason'],
      ]);
      // Return HTTP 400 semantics — Stripe will retry; attackers receive no useful signal.
      return $this->fail('Stripe-Signature header failed pre-validation.');
    }

    $envelope = [
      'payload' => $payload,
      'signature' => $signatureHeader,
      'attempt' => 0,
      'queued_at' => time(),
      'queue_id' => bin2hex(random_bytes(8)),
    ];

    $encoded = json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
      $this->recordWebhookFailure('queue_encode_failed', $payloadContext + $signatureContext);
      return $this->fail('Failed to encode webhook queue payload.');
    }

    try {
      Database::multi(function ($redis) use ($encoded): void {
        $redis->lPush(Keys::BILLING_WEBHOOK_QUEUE, $encoded);
        $redis->lTrim(Keys::BILLING_WEBHOOK_QUEUE, 0, self::WEBHOOK_QUEUE_MAX_ITEMS - 1);
      });

      $queueDepth = Database::llen(Keys::BILLING_WEBHOOK_QUEUE);
      $this->recordWebhookOutcome('queued', $payloadContext + $signatureContext + [
        'queue_depth' => (string) $queueDepth,
      ]);

      return $this->ok('Webhook queued for asynchronous processing.', [
        'queued' => true,
        'queue_depth' => $queueDepth,
      ]);
    } catch (\Throwable $e) {
      $this->recordWebhookFailure('queue_enqueue_failed', $payloadContext + $signatureContext + [
        'error' => $e->getMessage(),
      ]);
      return $this->fail('Failed to enqueue webhook for asynchronous processing.', [
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Drain a bounded number of queued webhook deliveries.
   *
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  public function drainWebhookQueue(int $maxItems = 25): array
  {
    $limit = max(1, min(500, $maxItems));
    $processed = 0;
    $succeeded = 0;
    $failed = 0;
    $requeued = 0;
    $deadLettered = 0;

    for ($i = 0; $i < $limit; $i++) {
      $rawEnvelope = Database::rpop(Keys::BILLING_WEBHOOK_QUEUE);
      if ($rawEnvelope === null) {
        break;
      }

      $processed++;
      $decoded = json_decode($rawEnvelope, true);
      if (!is_array($decoded)) {
        $failed++;
        $deadLettered++;
        $this->recordWebhookFailure('queue_payload_invalid');
        $this->pushDeadLetter($rawEnvelope);
        continue;
      }

      $payload = is_scalar($decoded['payload'] ?? null) ? (string) $decoded['payload'] : '';
      $signature = is_scalar($decoded['signature'] ?? null) ? (string) $decoded['signature'] : '';
      $attempt = is_scalar($decoded['attempt'] ?? null) ? (int) $decoded['attempt'] : 0;

      $result = $this->processWebhook($payload, $signature);
      if ($result['success']) {
        $succeeded++;
        continue;
      }

      $failed++;
      $nextAttempt = $attempt + 1;
      if ($this->isRetryableQueueFailure($result['message']) && $nextAttempt < self::WEBHOOK_QUEUE_MAX_RETRIES) {
        $decoded['attempt'] = $nextAttempt;
        $decoded['last_error'] = $result['message'];
        $retryEncoded = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($retryEncoded !== false) {
          Database::lpush(Keys::BILLING_WEBHOOK_QUEUE, $retryEncoded);
          $requeued++;
          $this->recordWebhookOutcome('queue_requeued', [
            'attempt' => (string) $nextAttempt,
            'message' => $result['message'],
          ]);
          continue;
        }
      }

      $deadLettered++;
      $this->recordWebhookFailure('queue_dead_lettered', [
        'attempt' => (string) $nextAttempt,
        'message' => $result['message'],
      ]);
      $this->pushDeadLetter($rawEnvelope);
    }

    return $this->ok('Webhook queue drain completed.', [
      'processed' => $processed,
      'succeeded' => $succeeded,
      'failed' => $failed,
      'requeued' => $requeued,
      'dead_lettered' => $deadLettered,
      'remaining' => Database::llen(Keys::BILLING_WEBHOOK_QUEUE),
    ]);
  }

  /**
   * Handles pushDeadLetter operation.
   */
  private function pushDeadLetter(string $rawEnvelope): void
  {
    try {
      Database::multi(function ($redis) use ($rawEnvelope): void {
        $redis->lPush(Keys::BILLING_WEBHOOK_DEAD_LETTER, $rawEnvelope);
        $redis->lTrim(Keys::BILLING_WEBHOOK_DEAD_LETTER, 0, self::WEBHOOK_DEAD_LETTER_MAX_ITEMS - 1);
      });
    } catch (\Throwable) {
    }
  }

  /**
   * Handles isRetryableQueueFailure operation.
   */
  private function isRetryableQueueFailure(string $message): bool
  {
    $normalized = strtolower(trim($message));
    if ($normalized === '') {
      return true;
    }

    $nonRetryableFragments = [
      'missing stripe-signature header',
      'webhook payload is empty',
      'webhook event id missing',
      'verification failed',
      'missing user_uuid metadata',
      'data object is invalid',
      'does not belong to this authenticated user',
    ];

    foreach ($nonRetryableFragments as $fragment) {
      if (str_contains($normalized, $fragment)) {
        return false;
      }
    }

    return true;
  }

  /** @return array<string, mixed> */
  private function extractWebhookPayloadContext(string $payload): array
  {
    if ($payload === '') {
      return [];
    }

    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
      return [
        'payload_parseable_json' => 'false',
      ];
    }

    $eventId = $this->stringOrNull($decoded['id'] ?? null);
    $eventType = $this->stringOrNull($decoded['type'] ?? null);
    $apiVersion = $this->stringOrNull($decoded['api_version'] ?? null);
    $created = $this->stringOrNull($decoded['created'] ?? null);
    $pendingWebhooks = $this->stringOrNull($decoded['pending_webhooks'] ?? null);
    $livemode = $this->stringOrNull($decoded['livemode'] ?? null);
    $objectId = null;
    if (isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data']['object']) && is_array($decoded['data']['object'])) {
      $objectId = $this->stringOrNull($decoded['data']['object']['id'] ?? null);
    }

    return array_filter([
      'payload_parseable_json' => 'true',
      'event_id' => $eventId,
      'event_type' => $eventType,
      'event_api_version' => $apiVersion,
      'event_created_unix' => $created,
      'event_pending_webhooks' => $pendingWebhooks,
      'event_livemode' => $livemode,
      'event_object_id' => $objectId,
    ], static fn (mixed $value): bool => $value !== null && $value !== '');
  }

  /** @return array<string, mixed> */
  private function extractWebhookEventContext(object $event): array
  {
    $eventId = $this->stringOrNull($event->id ?? null);
    $eventType = $this->stringOrNull($event->type ?? null);
    $apiVersion = $this->stringOrNull($event->api_version ?? null);
    $created = $this->stringOrNull($event->created ?? null);
    $pendingWebhooks = $this->stringOrNull($event->pending_webhooks ?? null);
    $livemode = $this->stringOrNull($event->livemode ?? null);

    $objectId = null;
    if (isset($event->data) && is_object($event->data)
      && isset($event->data->object) && is_object($event->data->object)
    ) {
      $objectId = $this->stringOrNull($event->data->object->id ?? null);
    }

    return array_filter([
      'event_id' => $eventId,
      'event_type' => $eventType,
      'event_api_version' => $apiVersion,
      'event_created_unix' => $created,
      'event_pending_webhooks' => $pendingWebhooks,
      'event_livemode' => $livemode,
      'event_object_id' => $objectId,
    ], static fn (mixed $value): bool => $value !== null && $value !== '');
  }

  /** @return array<string, mixed> */
  private function extractSignatureContext(string $signatureHeader): array
  {
    $signature = trim($signatureHeader);
    if ($signature === '') {
      return [
        'signature_timestamp_missing' => 'true',
      ];
    }

    $parts = explode(',', $signature);
    foreach ($parts as $part) {
      $part = trim($part);
      if (!str_starts_with($part, 't=')) {
        continue;
      }

      $timestamp = trim(substr($part, 2));
      if ($timestamp !== '') {
        return [
          'signature_timestamp' => $timestamp,
          'signature_timestamp_missing' => 'false',
        ];
      }
    }

    return [
      'signature_timestamp_missing' => 'true',
    ];
  }

  /**
   * Handles extractStripeExceptionValue operation.
   */
  private function extractStripeExceptionValue(\Throwable $error, string $method): ?string
  {
    if (!method_exists($error, $method)) {
      return null;
    }

    try {
      $value = $error->{$method}();
    } catch (\Throwable) {
      return null;
    }

    return $this->stringOrNull($value);
  }

  /**
   * @param object $event
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function applyWebhookEvent(object $event): array
  {
    $eventType = is_scalar($event->type ?? null) ? (string) $event->type : '';

    if (!isset($event->data) || !is_object($event->data) || !isset($event->data->object) || !is_object($event->data->object)) {
      return $this->fail('Webhook event data object is invalid.', [
        'event_type' => $eventType,
      ]);
    }

    $object = $event->data->object;

    return match ($eventType) {
      'checkout.session.completed' => $this->handleCheckoutSessionCompleted($object),
      'customer.subscription.created' => $this->handleSubscriptionUpdated($object),
      'customer.subscription.updated' => $this->handleSubscriptionUpdated($object),
      'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
      'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
      'invoice.paid' => $this->handleInvoicePaid($object),
      default => $this->ok('Unhandled Stripe webhook event ignored.', [
        'event_type' => $eventType,
      ]),
    };
  }

  /**
   * @param object $object
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function handleCheckoutSessionCompleted(object $object): array
  {
    $userUUID = $this->extractUserUUID($object);
    if ($userUUID === '') {
      return $this->fail('checkout.session.completed missing user_uuid metadata.');
    }

    $subscriptionId = is_scalar($object->subscription ?? null) ? (string) $object->subscription : null;
    $customerId = $this->extractCustomerId($object);
    SubscriptionRepository::upgradeToPremium($userUUID, $subscriptionId, $customerId);

    return $this->ok('Subscription activated from checkout.session.completed.', [
      'user_uuid' => $userUUID,
      'subscription_id' => (string) ($subscriptionId ?? ''),
      'customer_id' => (string) ($customerId ?? ''),
      'event_type' => 'checkout.session.completed',
    ]);
  }

  /**
   * @param object $object
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function handleSubscriptionUpdated(object $object): array
  {
    $userUUID = $this->extractUserUUID($object);
    if ($userUUID === '') {
      return $this->ok('Subscription update ignored: no user_uuid metadata.', [
        'event_type' => 'customer.subscription.updated',
      ]);
    }

    $status = strtolower((string) ($object->status ?? ''));
    $customerId = $this->extractCustomerId($object);
    if ($status === 'active' || $status === 'trialing') {
      $subscriptionId = is_scalar($object->id ?? null) ? (string) $object->id : null;
      $periodEnd = $this->extractPeriodEndUnixTime($object);
      SubscriptionRepository::upgradeToPremium($userUUID, $subscriptionId, $customerId, $periodEnd);
    } elseif ($status === 'past_due' || $status === 'unpaid' || $status === 'incomplete_expired') {
      SubscriptionRepository::markPastDue($userUUID);
      if ($customerId !== null) {
        SubscriptionRepository::storeStripeCustomerId($userUUID, $customerId);
      }
    } elseif ($status === 'canceled') {
      SubscriptionRepository::downgradeToFree($userUUID);
    }

    return $this->ok('Subscription updated from Stripe.', [
      'user_uuid' => $userUUID,
      'customer_id' => (string) ($customerId ?? ''),
      'stripe_status' => $status,
      'event_type' => 'customer.subscription.updated',
    ]);
  }

  /**
   * @param object $object
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function handleSubscriptionDeleted(object $object): array
  {
    $userUUID = $this->extractUserUUID($object);
    if ($userUUID === '') {
      return $this->ok('Subscription delete ignored: no user_uuid metadata.', [
        'event_type' => 'customer.subscription.deleted',
      ]);
    }

    SubscriptionRepository::downgradeToFree($userUUID);

    return $this->ok('Subscription canceled from Stripe.', [
      'user_uuid' => $userUUID,
      'event_type' => 'customer.subscription.deleted',
    ]);
  }

  /**
   * @param object $object
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function handleInvoicePaymentFailed(object $object): array
  {
    $userUUID = $this->extractUserUUID($object);
    if ($userUUID === '') {
      return $this->ok('invoice.payment_failed ignored: no user_uuid metadata.', [
        'event_type' => 'invoice.payment_failed',
      ]);
    }

    $customerId = $this->extractCustomerId($object);
    SubscriptionRepository::markPastDue($userUUID);
    if ($customerId !== null) {
      SubscriptionRepository::storeStripeCustomerId($userUUID, $customerId);
    }

    return $this->ok('Subscription marked past_due from failed invoice.', [
      'user_uuid' => $userUUID,
      'customer_id' => (string) ($customerId ?? ''),
      'event_type' => 'invoice.payment_failed',
    ]);
  }

  /**
   * @param object $object
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function handleInvoicePaid(object $object): array
  {
    $userUUID = $this->extractUserUUID($object);
    if ($userUUID === '') {
      return $this->ok('invoice.paid ignored: no user_uuid metadata.', [
        'event_type' => 'invoice.paid',
      ]);
    }

    $subscription = SubscriptionRepository::get($userUUID);
    $subscriptionId = is_scalar($subscription['id'] ?? null) ? (string) $subscription['id'] : null;
    $customerId = $this->extractCustomerId($object);
    SubscriptionRepository::upgradeToPremium($userUUID, $subscriptionId, $customerId);

    return $this->ok('Subscription restored active from paid invoice.', [
      'user_uuid' => $userUUID,
      'customer_id' => (string) ($customerId ?? ''),
      'event_type' => 'invoice.paid',
    ]);
  }

  /** @param object $object */
  private function extractUserUUID(object $object): string
  {
    if (isset($object->metadata) && is_object($object->metadata)) {
      $metadataUserUUID = $object->metadata->user_uuid ?? '';
      if (is_scalar($metadataUserUUID)) {
        $uuid = trim((string) $metadataUserUUID);
        if ($uuid !== '') {
          return $uuid;
        }
      }
    }

    // invoice events often nest subscription details in parent.subscription_details.metadata
    if (isset($object->parent) && is_object($object->parent)
      && isset($object->parent->subscription_details) && is_object($object->parent->subscription_details)
      && isset($object->parent->subscription_details->metadata) && is_object($object->parent->subscription_details->metadata)
    ) {
      $nested = $object->parent->subscription_details->metadata->user_uuid ?? '';
      if (is_scalar($nested)) {
        return trim((string) $nested);
      }
    }

    // checkout.session can include client_reference_id fallback
    $clientReference = $object->client_reference_id ?? '';
    if (is_scalar($clientReference)) {
      $candidate = trim((string) $clientReference);
      if ($candidate !== '') {
        return $candidate;
      }
    }

    return '';
  }

  /** @param object $object */
  private function extractCustomerId(object $object): ?string
  {
    $customer = $object->customer ?? null;
    if (is_scalar($customer)) {
      $normalized = trim((string) $customer);
      return $normalized !== '' ? $normalized : null;
    }

    if (isset($object->parent) && is_object($object->parent)) {
      $nestedCustomer = $object->parent->customer ?? null;
      if (is_scalar($nestedCustomer)) {
        $normalized = trim((string) $nestedCustomer);
        return $normalized !== '' ? $normalized : null;
      }
    }

    return null;
  }

  /**
   * Handles isWebhookEventProcessed operation.
   */
  private function isWebhookEventProcessed(string $eventId): bool
  {
    return Database::exists($this->webhookEventKey($eventId));
  }

  /**
   * Handles markWebhookEventProcessed operation.
   */
  private function markWebhookEventProcessed(string $eventId): void
  {
    Database::set($this->webhookEventKey($eventId), '1', self::WEBHOOK_EVENT_TTL_SECONDS);
  }

  /**
   * Handles webhookEventKey operation.
   */
  private function webhookEventKey(string $eventId): string
  {
    return Keys::BILLING_WEBHOOK_EVENT . ':' . $eventId;
  }

  /**
   * Handles appendSessionIdPlaceholder operation.
   */
  private function appendSessionIdPlaceholder(string $url): string
  {
    if (str_contains($url, '{CHECKOUT_SESSION_ID}')) {
      return $url;
    }

    $separator = str_contains($url, '?') ? '&' : '?';
    return $url . $separator . 'session_id={CHECKOUT_SESSION_ID}';
  }

  /**
   * Handles resolvePortalCustomerId operation.
   */
  private function resolvePortalCustomerId(string $secretKey, string $subscriptionId): string
  {
    if ($this->portalCustomerResolver instanceof \Closure) {
      return trim((string) (($this->portalCustomerResolver)($secretKey, $subscriptionId) ?? ''));
    }

    $client = new StripeClient($secretKey);
    $stripeSubscription = $client->subscriptions->retrieve($subscriptionId, []);

    return trim((string) ($stripeSubscription->customer ?? ''));
  }

  /**
   * Handles createPortalUrl operation.
   */
  private function createPortalUrl(string $secretKey, string $customerId, string $returnURL): string
  {
    if ($this->portalSessionCreator instanceof \Closure) {
      return trim((string) (($this->portalSessionCreator)($secretKey, $customerId, $returnURL) ?? ''));
    }

    $client = new StripeClient($secretKey);
    $portalSession = $client->billingPortal->sessions->create([
      'customer' => $customerId,
      'return_url' => $returnURL,
    ]);

    return trim((string) ($portalSession->url ?? ''));
  }

  /**
   * Handles cancelStripeSubscription operation.
   */
  private function cancelStripeSubscription(string $secretKey, string $subscriptionId): mixed
  {
    if ($this->subscriptionCanceler instanceof \Closure) {
      return ($this->subscriptionCanceler)($secretKey, $subscriptionId);
    }

    $client = new StripeClient($secretKey);
    return $client->subscriptions->cancel($subscriptionId, []);
  }

  /**
   * Handles resolveActiveSubscriptionId operation.
   */
  private function resolveActiveSubscriptionId(string $secretKey, string $customerId): string
  {
    $client = new StripeClient($secretKey);
    $subscriptions = $client->subscriptions->all([
      'customer' => $customerId,
      'status' => 'all',
      'limit' => 10,
    ]);

    $preferredStatuses = ['active', 'trialing', 'past_due', 'unpaid', 'incomplete'];
    $firstSeenId = '';

    foreach ($subscriptions->data as $subscription) {
      $id = is_scalar($subscription->id ?? null) ? trim((string) $subscription->id) : '';
      if ($id === '') {
        continue;
      }

      if ($firstSeenId === '') {
        $firstSeenId = $id;
      }

      $status = is_scalar($subscription->status ?? null) ? strtolower(trim((string) $subscription->status)) : '';
      if (in_array($status, $preferredStatuses, true)) {
        return $id;
      }
    }

    return $firstSeenId;
  }

  /**
   * Handles resolveCanonicalCustomerIdByEmail operation.
   */
  private function resolveCanonicalCustomerIdByEmail(string $secretKey, string $email): string
  {
    $normalizedEmail = strtolower(trim($email));
    if ($normalizedEmail === '') {
      return '';
    }

    $client = new StripeClient($secretKey);
    $customers = $client->customers->all([
      'email' => $normalizedEmail,
      'limit' => 20,
    ]);

    if (!isset($customers->data)) {
      return '';
    }

    $bestCustomerId = '';
    $bestScore = -1;
    $bestCreated = -1;

    foreach ($customers->data as $customer) {
      $customerId = is_scalar($customer->id ?? null) ? trim((string) $customer->id) : '';
      if ($customerId === '') {
        continue;
      }

      $score = 0;
      $subscriptions = $client->subscriptions->all([
        'customer' => $customerId,
        'status' => 'all',
        'limit' => 10,
      ]);

      foreach ($subscriptions->data as $subscription) {
        $status = is_scalar($subscription->status ?? null) ? strtolower(trim((string) $subscription->status)) : '';
        if (in_array($status, ['active', 'trialing'], true)) {
          $score = max($score, 3);
        } elseif (in_array($status, ['past_due', 'unpaid', 'incomplete'], true)) {
          $score = max($score, 2);
        } elseif ($status !== '') {
          $score = max($score, 1);
        }
      }

      $created = is_scalar($customer->created ?? null) ? (int) $customer->created : 0;
      if ($score > $bestScore || ($score === $bestScore && $created > $bestCreated)) {
        $bestCustomerId = $customerId;
        $bestScore = $score;
        $bestCreated = $created;
      }
    }

    return $bestCustomerId;
  }

  /**
   * Handles cleanupLegacyCustomersByEmail operation.
   */
  private function cleanupLegacyCustomersByEmail(string $secretKey, string $email, string $canonicalCustomerId, string $userUUID): void
  {
    $normalizedEmail = strtolower(trim($email));
    $canonical = trim($canonicalCustomerId);
    if ($normalizedEmail === '' || $canonical === '') {
      return;
    }

    try {
      $client = new StripeClient($secretKey);
      $customers = $client->customers->all([
        'email' => $normalizedEmail,
        'limit' => 30,
      ]);

      foreach ($customers->data as $customer) {
        $customerId = is_scalar($customer->id ?? null) ? trim((string) $customer->id) : '';
        if ($customerId === '' || hash_equals($customerId, $canonical)) {
          continue;
        }

        $subscriptions = $client->subscriptions->all([
          'customer' => $customerId,
          'status' => 'all',
          'limit' => 20,
        ]);

        foreach ($subscriptions->data as $subscription) {
          $subscriptionId = is_scalar($subscription->id ?? null) ? trim((string) $subscription->id) : '';
          if ($subscriptionId === '') {
            continue;
          }

          $status = is_scalar($subscription->status ?? null) ? strtolower(trim((string) $subscription->status)) : '';
          if (!in_array($status, ['active', 'trialing', 'past_due', 'unpaid', 'incomplete'], true)) {
            continue;
          }

          try {
            $client->subscriptions->cancel($subscriptionId, []);
            SecurityLog::log('billing_customer_cleanup_subscription_canceled', [
              'user_uuid' => $userUUID,
              'email' => $normalizedEmail,
              'canonical_customer_id' => $canonical,
              'legacy_customer_id' => $customerId,
              'legacy_subscription_id' => $subscriptionId,
              'legacy_subscription_status' => $status,
            ]);
          } catch (\Throwable $e) {
            SecurityLog::log('billing_customer_cleanup_subscription_cancel_failed', [
              'user_uuid' => $userUUID,
              'email' => $normalizedEmail,
              'canonical_customer_id' => $canonical,
              'legacy_customer_id' => $customerId,
              'legacy_subscription_id' => $subscriptionId,
              'error' => $e->getMessage(),
            ]);
          }
        }
      }
    } catch (\Throwable $e) {
      SecurityLog::log('billing_customer_cleanup_failed', [
        'user_uuid' => $userUUID,
        'email' => $normalizedEmail,
        'canonical_customer_id' => $canonical,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Handles extractStripeStatus operation.
   */
  private function extractStripeStatus(mixed $subscription): string
  {
    if (is_object($subscription) && is_scalar($subscription->status ?? null)) {
      $status = trim((string) $subscription->status);
      if ($status !== '') {
        return $status;
      }
    }

    if (is_array($subscription) && is_scalar($subscription['status'] ?? null)) {
      $status = trim((string) $subscription['status']);
      if ($status !== '') {
        return $status;
      }
    }

    return 'canceled';
  }

  /**
   * Handles requireEnv operation.
   */
  private function requireEnv(string $key): string
  {
    $envValue = $_ENV[$key] ?? null;
    if (is_scalar($envValue)) {
      $normalized = trim((string) $envValue);
      if ($normalized !== '') {
        return $normalized;
      }
    }

    $systemValue = getenv($key);
    if (is_string($systemValue)) {
      return trim($systemValue);
    }

    return '';
  }

  /**
   * Handles extractPeriodEndUnixTime operation.
   */
  private function extractPeriodEndUnixTime(object $subscription): ?int
  {
    $topLevel = $subscription->current_period_end ?? null;
    if (is_scalar($topLevel)) {
      $value = (int) $topLevel;
      if ($value > 0) {
        return $value;
      }
    }

    if (isset($subscription->items) && is_object($subscription->items)
      && isset($subscription->items->data) && is_array($subscription->items->data)
      && isset($subscription->items->data[0]) && is_object($subscription->items->data[0])
    ) {
      $itemPeriodEnd = $subscription->items->data[0]->current_period_end ?? null;
      if (is_scalar($itemPeriodEnd)) {
        $value = (int) $itemPeriodEnd;
        if ($value > 0) {
          return $value;
        }
      }
    }

    $anchor = $subscription->billing_cycle_anchor ?? null;
    if (is_scalar($anchor)) {
      $value = (int) $anchor;
      if ($value > 0) {
        return $value;
      }
    }

    return null;
  }

  /** @param array<string, mixed> $context */
  private function recordWebhookOutcome(string $metric, array $context = []): void
  {
    $this->incrementWebhookTelemetry($metric);
    $eventType = $this->stringOrNull($context['event_type'] ?? null);
    if ($eventType !== null) {
      $this->incrementWebhookTelemetry($metric . ':' . $this->normalizeMetricLabel($eventType));
    }

    SecurityLog::log('billing_webhook_' . $metric, $this->normalizeLogContext($context));
  }

  /** @param array<string, mixed> $context */
  private function recordWebhookFailure(string $metric, array $context = []): void
  {
    $this->recordWebhookOutcome($metric, $context);
  }

  /**
   * Handles incrementWebhookTelemetry operation.
   */
  private function incrementWebhookTelemetry(string $metric): void
  {
    $key = Keys::TELEMETRY . ':' . self::WEBHOOK_TELEMETRY_PREFIX . ':' . $metric . ':' . date('Y-m-d');

    try {
      Database::incr($key);
    } catch (\Throwable) {
    }
  }

  /**
   * Handles normalizeMetricLabel operation.
   */
  private function normalizeMetricLabel(string $value): string
  {
    $normalized = strtolower(trim($value));
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;
    $normalized = trim($normalized, '_');

    return $normalized === '' ? 'unknown' : $normalized;
  }

  /** @param array<string, mixed> $context
   *  @return array<string, scalar|null>
   */
  private function normalizeLogContext(array $context): array
  {
    $normalized = [];

    foreach ($context as $key => $value) {
      if ($key === '') {
        continue;
      }

      $normalized[$key] = $this->stringOrNull($value);
    }

    return $normalized;
  }

  /**
   * Handles stringOrNull operation.
   */
  private function stringOrNull(mixed $value): ?string
  {
    if ($value === null) {
      return null;
    }

    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }

    if (is_scalar($value)) {
      return trim((string) $value);
    }

    return null;
  }

  /**
   * @param array<string, mixed> $data
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function ok(string $message, array $data = []): array
  {
    return ['success' => true, 'message' => $message, 'data' => $data];
  }

  /**
   * @param array<string, mixed> $data
   * @return array{success: bool, message: string, data: array<string, mixed>}
   */
  private function fail(string $message, array $data = []): array
  {
    return ['success' => false, 'message' => $message, 'data' => $data];
  }
}


