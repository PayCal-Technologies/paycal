<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\BillingProviderMode;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\BillingProvider;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\Subscription;
use PayCal\Domain\SubscriptionRepository;
use PayCal\Domain\Authentication;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\MetricsService;
use PayCal\Domain\Response;
use PayCal\Domain\StripeBillingService;
use PayCal\Domain\User;
use PayCal\Infrastructure\Telemetry\SecurityLog;

/**
 * BillingController.php
 *
 * Purpose: Controller boundary for billing checkout, portal access, webhook
 * handling, and provider-mode-aware subscription actions.
 *
 * Developer notes:
 * - Billing flows are externally integrated and financially sensitive.
 * - Keep request validation and response contracts stable at this layer while
 *   delegating provider-specific behavior to domain services.
 *
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Billing API surface.
 *
 * Responsibilities:
 * - Accept billing-related requests for checkout, portal access, and webhook intake.
 * - Delegate provider-specific behavior to StripeBillingService and related domain helpers.
 * - Preserve deterministic subscription and provider-mode response contracts.
 */
final class BillingController
{
  /**
   * POST billing/checkout-session
   *
   * Creates a Stripe Checkout Session (or upgrades directly for non-Stripe providers)
   * and returns the session URL so the client can redirect the user to the payment page.
   */
  #[Route('billing/checkout-session', ['POST'])]
  /**
   * Handles createCheckoutSession operation.
   */
  public function createCheckoutSession(): void
  {
    Authentication::abortIfUnauthenticated();

    if (!$this->validateBillingCsrf()) {
      return;
    }

    $successRaw = $this->requestString('success_url', '/profile/?billing=success');
    $cancelRaw = $this->requestString('cancel_url', '/profile/?billing=cancel');
    $successTarget = $this->normalizeAppURL($successRaw, '/profile/?billing=success');
    // Sign the ?next= value so handleCheckoutReturn can reject attacker-crafted URLs.
    $sessionHash = Authentication::getSessionHashFromCookie() ?? '';
    $returnPath  = '/api/v1/billing/checkout-return?next=' . rawurlencode($successTarget);
    if ($sessionHash !== '') {
      $returnPath .= '&nxt_sig=' . hash_hmac('sha256', $successTarget, $sessionHash);
    }
    $successURL = $this->normalizeAppURL($returnPath, '/api/v1/billing/checkout-return');
    $cancelURL = $this->normalizeAppURL($cancelRaw, '/profile/?billing=cancel');

    $user = User::current();
    $userUUID = User::currentUUID();
    $email = trim($user->email);

    if ($userUUID === '' || $email === '') {
      Response::error('[Billing] Missing authenticated user context.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    if (!BillingProvider::isStripe()) {
      SubscriptionRepository::upgradeToPremium($userUUID);
      Response::success('[Billing] Premium enabled.', [
        'billing_provider' => BillingProvider::current(),
        'tier' => Subscription::PREMIUM->value,
      ], HttpStatus::HTTP_CREATED);
      return;
    }

    $service = new StripeBillingService();
    $result = $service->createCheckoutSession($userUUID, $email, $successURL, $cancelURL);

    if ($result['success']) {
      Response::success('[Billing] Checkout session created.', $result['data'], HttpStatus::HTTP_CREATED);
      return;
    }

    Response::error('[Billing] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
  }

  /**
   * GET billing/checkout-return
   *
   * Stripe redirect target after checkout. Confirms the checkout session, then
   * redirects the user to the appropriate profile billing URL.
   */
  #[Route('billing/checkout-return', ['GET'])]
  #[BillingProviderMode([BillingProvider::STRIPE])]
  /**
   * Handles handleCheckoutReturn operation.
   */
  public function handleCheckoutReturn(): void
  {
    Authentication::redirectHomeIfUnauthenticated();

    if (!$this->billingProviderAllows(__FUNCTION__)) {
      header('Location: /profile/?billing=success', true, 302);
      exit;
    }

    $userUUID = User::currentUUID();
    if ($userUUID === '') {
      header('Location: /profile/?billing=delayed', true, 302);
      exit;
    }

    $sessionRaw = $_GET['session_id'] ?? '';
    $sessionId = is_scalar($sessionRaw) ? trim((string) $sessionRaw) : '';

    $nextRaw = $_GET['next'] ?? '';
    $sigRaw  = $_GET['nxt_sig'] ?? '';
    $nextUrl = $this->verifiedRedirectTarget($nextRaw, $sigRaw);

    if ($sessionId === '') {
      $nextUrl = $this->appendQueryParam($nextUrl, 'billing', 'delayed');
    } else {
      $result = (new StripeBillingService())->confirmCheckoutSession($userUUID, $sessionId);
      if (!$result['success']) {
        $nextUrl = $this->appendQueryParam($nextUrl, 'billing', 'delayed');
      }
    }

    header('Location: ' . $nextUrl, true, 302);
    exit;
  }

  /**
   * POST billing/portal-session
   *
   * Creates a Stripe Customer Portal Session and returns the redirect URL so the
   * user can manage their subscription directly in the Stripe portal.
   */
  #[Route('billing/portal-session', ['POST'])]
  #[BillingProviderMode([BillingProvider::STRIPE])]
  /**
   * Handles createPortalSession operation.
   */
  public function createPortalSession(): void
  {
    Authentication::abortIfUnauthenticated();

    if (!$this->billingProviderAllows(__FUNCTION__)) {
      Response::error('[Billing] Billing portal is unavailable in public toggle mode.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (!$this->validateBillingCsrf()) {
      return;
    }

    $returnRaw = $this->requestString('return_url', '/profile/?billing=portal');
    $returnURL = $this->normalizeAppURL($returnRaw, '/profile/?billing=portal');

    $userUUID = User::currentUUID();
    if ($userUUID === '') {
      Response::error('[Billing] Missing authenticated user context.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $service = new StripeBillingService();
    $result = $service->createPortalSession($userUUID, $returnURL);

    if ($result['success']) {
      Response::success('[Billing] Billing portal session created.', $result['data'], HttpStatus::HTTP_CREATED);
      return;
    }

    Response::error('[Billing] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
  }

  /**
   * POST billing/confirm-checkout
   *
   * Confirms a completed Stripe Checkout Session and upgrades the user to Premium.
   * Used as a fallback server-side confirmation when the webhook has not yet arrived.
   */
  #[Route('billing/confirm-checkout', ['POST'])]
  #[BillingProviderMode([BillingProvider::STRIPE])]
  /**
   * Handles confirmCheckoutSession operation.
   */
  public function confirmCheckoutSession(): void
  {
    Authentication::abortIfUnauthenticated();

    if (!$this->billingProviderAllows(__FUNCTION__)) {
      Response::success('[Billing] Premium already enabled.', [
        'billing_provider' => BillingProvider::current(),
      ], HttpStatus::HTTP_OK);
      return;
    }

    if (!$this->validateBillingCsrf()) {
      return;
    }

    $sessionId = trim($this->requestString('session_id', ''));
    if ($sessionId === '') {
      Response::error('[Billing] Missing Stripe checkout session ID.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $userUUID = User::currentUUID();
    if ($userUUID === '') {
      Response::error('[Billing] Missing authenticated user context.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $service = new StripeBillingService();
    $result = $service->confirmCheckoutSession($userUUID, $sessionId);

    if ($result['success']) {
      Response::success('[Billing] Checkout session confirmed.', $result['data'], HttpStatus::HTTP_OK);
      return;
    }

    Response::error('[Billing] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
  }

  /**
   * POST billing/cancel-subscription
   *
   * Cancels the user's active subscription.  Requires explicit confirmation phrase.
   * For Stripe, schedules cancellation via the billing service; for other providers
   * downgrades immediately.
   */
  #[Route('billing/cancel-subscription', ['POST'])]
  /**
   * Handles cancelSubscription operation.
   */
  public function cancelSubscription(): void
  {
    Authentication::abortIfUnauthenticated();

    if (!$this->validateBillingCsrf()) {
      return;
    }

    $userUUID = User::currentUUID();
    if ($userUUID === '') {
      Response::error('[Billing] Missing authenticated user context.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    if (!BillingProvider::isStripe()) {
      SubscriptionRepository::downgradeToFree($userUUID);
      Response::success('[Billing] Premium disabled.', [
        'billing_provider' => BillingProvider::current(),
        'tier' => Subscription::FREE->value,
      ], HttpStatus::HTTP_OK);
      return;
    }

    $confirmPhrase = strtoupper(trim($this->requestString('confirm_phrase', '')));
    if ($confirmPhrase !== 'DOWNGRADE ME') {
      Response::error('[Billing] Type DOWNGRADE ME exactly to confirm subscription cancellation.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $service = new StripeBillingService();
    $result = $service->cancelSubscription($userUUID);

    if ($result['success']) {
      Response::success('[Billing] Subscription canceled.', $result['data'], HttpStatus::HTTP_OK);
      return;
    }

    Response::error('[Billing] ' . $result['message'], $result['data'], HttpStatus::HTTP_BAD_REQUEST);
  }

  /**
   * POST billing/webhook
   *
   * Receives a signed Stripe webhook event and enqueues it for asynchronous
   * processing.
   *
   * Security layers applied in order before the body is read:
   *   1. URL token gate — ?wt=<STRIPE_WEBHOOK_URL_TOKEN> must match.
   *      This is a shared secret embedded in the Stripe webhook URL
   *      configured in the Stripe dashboard. An attacker who does not
   *      know the URL cannot reach the signature or queueing logic at all.
   *   2. Pre-validation in enqueueWebhook() — timestamp freshness and
   *      HMAC format check reject structurally invalid Stripe-Signature
   *      headers before a queue slot is consumed.
   *   3. Full Webhook::constructEvent() HMAC verification during drain.
   */
  #[Route('billing/webhook', ['POST'])]
  #[BillingProviderMode([BillingProvider::STRIPE])]
  /**
   * Handles webhook operation.
   */
  public function webhook(): void
  {
    if (!$this->billingProviderAllows(__FUNCTION__)) {
      Response::error('[Billing] Stripe webhooks are unavailable in public toggle mode.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    // Layer 1: URL token gate.
    // STRIPE_WEBHOOK_URL_TOKEN is a secret path component embedded in the
    // webhook endpoint URL registered in the Stripe dashboard.
    // Stripe sends it on every delivery; we verify it before reading the body.
    if (!$this->verifyWebhookUrlToken()) {
      // Return 403 rather than 401 — do not hint that an auth upgrade exists.
      Response::error('[Billing] Forbidden.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $payload = file_get_contents('php://input');
    $payload = is_string($payload) ? $payload : '';
    $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $signature = is_scalar($signature) ? (string) $signature : '';

    $service = new StripeBillingService();
    $result = $service->enqueueWebhook($payload, $signature);

    if ($result['success']) {
      Response::success('[Billing] Webhook accepted for asynchronous processing.', $result['data'], HttpStatus::HTTP_OK);
      return;
    }

    $message = strtolower((string) $result['message']);
    $status = (str_contains($message, 'missing') || str_contains($message, 'empty') || str_contains($message, 'pre-validation'))
      ? HttpStatus::HTTP_BAD_REQUEST
      : HttpStatus::HTTP_INTERNAL_SERVER_ERROR;

    Response::error('[Billing] ' . $result['message'], $result['data'], $status);
  }

  /**
   * Verify the URL-embedded webhook token supplied as ?wt=<token>.
   *
   * STRIPE_WEBHOOK_URL_TOKEN must be set as an environment variable.
   * The value is embedded in the Stripe webhook endpoint URL (not the body)
   * and is unknown to anyone who has not been explicitly given the URL.
   *
   * Returns true when the token is absent from env (unconfigured),
   * allowing deployment without the feature while avoiding a hard failure.
   * An operator SHOULD configure this token; if unconfigured a SecurityLog
   * warning is emitted so the gap is visible.
   */
  private function verifyWebhookUrlToken(): bool
  {
    $expected = (string) (getenv('STRIPE_WEBHOOK_URL_TOKEN') ?: '');

    if ($expected === '') {
      // Token not configured: allow through but emit a warning so ops can see the gap.
      SecurityLog::log('billing_webhook_url_token_unconfigured', [
        'note' => 'STRIPE_WEBHOOK_URL_TOKEN env var is unset; URL-token gate is inactive',
      ]);
      return true;
    }

    $supplied = isset($_GET['wt']) && is_scalar($_GET['wt']) ? trim((string) $_GET['wt']) : '';

    // hash_equals prevents timing-oracle attacks.
    return $supplied !== '' && hash_equals($expected, $supplied);
  }

  /**
   * GET billing/subscription
   *
   * Returns the current user's subscription tier, status, and key dates.
   * Triggers a lightweight Stripe reconciliation to keep local state current
   * when webhook delivery may be delayed.
   */
  #[Route('billing/subscription', ['GET'])]
  /**
   * Handles getSubscription operation.
   */
  public function getSubscription(): void
  {
    Authentication::abortIfUnauthenticated();

    $userUUID = User::currentUUID();
    if ($userUUID === '') {
      Response::error('[Billing] Missing authenticated user context.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    // Keep local state aligned when webhook delivery is delayed or unavailable.
    if (BillingProvider::isStripe()) {
      $service = new StripeBillingService();
      $service->reconcileSubscriptionState($userUUID);
    }

    $sub = SubscriptionRepository::get($userUUID);
    $isPremium = $sub['tier'] === Subscription::PREMIUM && $sub['status']->grantsAccess();

    // Check if subscription is pending cancellation (has future cancel_date and is still ACTIVE/PREMIUM).
    $cancelDateStr = is_scalar($sub['cancel_date'] ?? null) ? trim((string) $sub['cancel_date']) : '';
    $isPendingCancellation = $sub['tier'] === Subscription::PREMIUM
      && $cancelDateStr !== ''
      && ($cancelUnixTime = strtotime($cancelDateStr)) !== false
      && $cancelUnixTime > time();

    Response::success('[Billing] Subscription status retrieved.', [
      'billing_provider'        => BillingProvider::current(),
      'tier'                    => $sub['tier']->value,
      'subscription_status'     => $sub['status']->value,
      'is_premium'              => $isPremium,
      'is_pending_cancellation' => $isPendingCancellation,
      'subscription_id'         => $sub['id'],
      'start_date'              => $sub['start_date'],
      'renewal_date'            => $sub['renewal_date'],
      'cancel_date'             => $sub['cancel_date'],
    ], HttpStatus::HTTP_OK);
  }

  /**
   * GET billing/telemetry
   *
   * Returns Stripe webhook queue health and telemetry data. Admin-only.
   */
  #[Route('billing/telemetry', ['GET'])]
  /**
   * Handles getWebhookTelemetry operation.
   */
  public function getWebhookTelemetry(): void
  {
    Authentication::abortIfUnauthenticated();

    if (!User::isAdmin()) {
      Response::error('[Billing] Admin access required.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Response::success('[Billing] Webhook telemetry summary.', MetricsService::getBillingWebhookMetrics(), HttpStatus::HTTP_OK);
  }

  /**
   * Normalise a caller-supplied URL to a safe same-origin destination.
   *
   * Relative paths are attached to the resolved application origin.
   * Absolute URLs are validated by comparing scheme, host, and port via
   * parse_url() — a string-prefix check is NOT sufficient because
   * "https://app.example.com.evil.com" starts with "https://app.example.com".
   */
  private function normalizeAppURL(mixed $value, string $fallbackPath): string
  {
    $requestOrigin = $this->requestOrigin();
    $appOrigin = $requestOrigin ?? $this->defaultAppOrigin();
    $defaultOrigin = $this->defaultAppOrigin();

    $candidate = is_scalar($value) ? trim((string) $value) : '';
    if ($candidate === '') {
      return $appOrigin . $fallbackPath;
    }

    // Relative path: safe to prepend the application origin.
    if (str_starts_with($candidate, '/')) {
      return $appOrigin . $candidate;
    }

    // Absolute URL: compare scheme + host + port exactly, not as a string prefix.
    if ($this->isSameOrigin($candidate, $appOrigin)) {
      return $candidate;
    }

    if ($defaultOrigin !== $appOrigin && $this->isSameOrigin($candidate, $defaultOrigin)) {
      return $candidate;
    }

    return $appOrigin . $fallbackPath;
  }

  /**
   * Returns true only when $candidate's scheme, host, and port all match $origin exactly.
   *
   * parse_url() is used so that "https://app.example.com.evil.com" never satisfies
   * an "app.example.com" origin — str_starts_with is insufficient for this check.
   */
  private function isSameOrigin(string $candidate, string $origin): bool
  {
    $pc = parse_url($candidate);
    $po = parse_url($origin);

    if (!is_array($pc) || !is_array($po)) {
      return false;
    }

    $schemeMatch = isset($pc['scheme'], $po['scheme'])
      && strtolower($pc['scheme']) === strtolower($po['scheme']);

    $hostMatch = isset($pc['host'], $po['host'])
      && strtolower($pc['host']) === strtolower($po['host']);

    // Both sides default to null (no explicit port) which is correct:
    // "https://example.com" and "https://example.com:443" would differ, intentionally.
    $portMatch = ($pc['port'] ?? null) === ($po['port'] ?? null);

    return $schemeMatch && $hostMatch && $portMatch;
  }

  /**
   * Verify the HMAC signature on an incoming ?next= redirect parameter and
   * return a safe redirect destination.
   *
   * The signature was produced in createCheckoutSession() using the user's
   * session hash as the key. If the signature is absent, wrong, or the session
   * hash is unavailable, the caller is sent to the default billing success page.
   * This prevents an attacker from crafting a ?next= link independently of a
   * real checkout flow.
   */
  private function verifiedRedirectTarget(mixed $nextRaw, mixed $sigRaw): string
  {
    $fallback = $this->defaultAppOrigin() . '/profile/?billing=success';

    $next = is_scalar($nextRaw) ? trim((string) $nextRaw) : '';
    $sig  = is_scalar($sigRaw)  ? trim((string) $sigRaw)  : '';

    // sha256 HMAC = exactly 64 lowercase hex chars.
    if ($next === '' || strlen($sig) !== 64) {
      return $fallback;
    }

    $sessionHash = Authentication::getSessionHashFromCookie() ?? '';
    if ($sessionHash === '') {
      return $fallback;
    }

    $expected = hash_hmac('sha256', $next, $sessionHash);
    if (!hash_equals($expected, $sig)) {
      return $fallback;
    }

    return $this->normalizeAppURL($next, '/profile/?billing=success');
  }

  /**
   * Handles billingProviderAllows operation.
   */
  private function billingProviderAllows(string $method): bool
  {
    $ref = new \ReflectionMethod($this, $method);
    $attributes = $ref->getAttributes(BillingProviderMode::class);
    if ($attributes === []) {
      return true;
    }

    /** @var BillingProviderMode $mode */
    $mode = $attributes[0]->newInstance();
    return in_array(BillingProvider::current(), $mode->providers, true);
  }

  /**
   * Handles requestOrigin operation.
   */
  private function requestOrigin(): ?string
  {
    $remoteRaw = $_SERVER['REMOTE_ADDR'] ?? null;
    if (!is_scalar($remoteRaw)) {
      return null;
    }

    $remote = trim((string) $remoteRaw);
    if ($remote === '' || !in_array($remote, $this->trustedProxyList(), true)) {
      return null;
    }

    $hostRaw = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null;
    if (!is_scalar($hostRaw)) {
      return null;
    }

    $host = trim((string) $hostRaw);
    if ($host === '') {
      return null;
    }

    if (str_contains($host, ',')) {
      $hostParts = explode(',', $host);
      $host = trim($hostParts[0]);
    }

    if ($host === '' || preg_match('/^[a-z0-9.\-:\[\]]+$/i', $host) !== 1) {
      return null;
    }

    $forwardedProtoRaw = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
    $forwardedProto = is_scalar($forwardedProtoRaw) ? strtolower(trim((string) $forwardedProtoRaw)) : '';
    if (str_contains($forwardedProto, ',')) {
      $protoParts = explode(',', $forwardedProto);
      $forwardedProto = trim($protoParts[0]);
    }

    if ($forwardedProto === 'http' || $forwardedProto === 'https') {
      $scheme = $forwardedProto;
    } else {
      $httpsRaw = $_SERVER['HTTPS'] ?? null;
      $isHttps = is_scalar($httpsRaw) && in_array(strtolower((string) $httpsRaw), ['on', '1', 'true'], true);
      $scheme = $isHttps ? 'https' : 'http';
    }

    return $scheme . '://' . rtrim($host, '/');
  }

  /** @return array<int, string> */
  private function trustedProxyList(): array
  {
    $raw = getenv('TRUSTED_PROXIES');
    if (!is_string($raw) || trim($raw) === '') {
      return [];
    }

    $proxies = array_filter(array_map('trim', explode(',', $raw)), static fn (string $value): bool => $value !== '');
    return array_values($proxies);
  }

  /**
   * Handles validateBillingCsrf operation.
   */
  private function validateBillingCsrf(): bool
  {
    $csrfToken = trim($this->requestString('csrf_token', ''));
    if ($csrfToken === '') {
      Response::error('[Billing] Missing CSRF token.', [], HttpStatus::HTTP_FORBIDDEN);
      return false;
    }

    $user = User::current();
    if ($user->verifyFormNonce('settings', $csrfToken) || $user->verifyFormNonce('organizations', $csrfToken)) {
      return true;
    }

    Response::error('[Billing] Invalid CSRF token.', [], HttpStatus::HTTP_FORBIDDEN);
    return false;
  }

  /**
   * Handles defaultAppOrigin operation.
   */
  private function defaultAppOrigin(): string
  {
    return Environment::appScheme() . '://' . rtrim(Environment::appDomain(), '/');
  }

  /**
   * Handles requestString operation.
   */
  private function requestString(string $key, string $fallback = ''): string
  {
    $postValue = $_POST[$key] ?? null;
    if (is_scalar($postValue)) {
      $value = trim((string) $postValue);
      if ($value !== '') {
        return $value;
      }
    }

    $json = $this->requestJsonPayload();
    $jsonValue = $json[$key] ?? null;
    if (is_scalar($jsonValue)) {
      $value = trim((string) $jsonValue);
      if ($value !== '') {
        return $value;
      }
    }

    return $fallback;
  }

  /**
   * Handles appendQueryParam operation.
   */
  private function appendQueryParam(string $url, string $key, string $value): string
  {
    if ($url === '') {
      return $url;
    }

    $fragment = '';
    $hashPos = strpos($url, '#');
    if ($hashPos !== false) {
      $fragment = substr($url, $hashPos);
      $url = substr($url, 0, $hashPos);
    }

    $separator = str_contains($url, '?') ? '&' : '?';
    return $url . $separator . rawurlencode($key) . '=' . rawurlencode($value) . $fragment;
  }

  /** @return array<string, mixed> */
  private function requestJsonPayload(): array
  {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
      return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return [];
    }

    $payload = [];
    foreach ($decoded as $key => $value) {
      if (is_string($key)) {
        $payload[$key] = $value;
      }
    }

    return $payload;
  }
}


