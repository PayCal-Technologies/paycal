<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\FederatedAuth;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Response;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;

/**
 * Federated sign-in discovery endpoints.
 */
final class FederatedAuthController
{
  private const SETTINGS_CSRF_FORM_TYPE = 'settings';

  /**
   * Return available federated sign-in providers.
   */
  #[Route('auth/providers', ['GET'])]
  public function providers(): void
  {
    $host = FederatedAuth::requestHost();

    Response::success('[Auth] Federated providers.', [
      'enabled' => FederatedAuth::featureGatePassesForHost($host),
      'local_only' => Environment::authFederatedSigninLocalOnly(),
      'host_allowed' => FederatedAuth::localGatePassesForHost($host),
      'providers' => FederatedAuth::availableProvidersForHost($host),
    ], HttpStatus::HTTP_OK);
  }

  /**
   * Start a federated sign-in flow.
   */
  #[Route('auth/federated/start/{provider}', ['GET'])]
  public function start(string $provider): void
  {
    $providerId = strtolower(trim($provider));
    $host = FederatedAuth::requestHost();
    $mode = $this->queryString('mode') === 'link' ? 'link' : 'signin';
    $strongUserUUID = FederatedAuth::currentStrongUserUUID();

    if ($providerId !== 'google' || !FederatedAuth::providerIsAvailableForHost($providerId, $host)) {
      Response::error('[Auth] Federated provider unavailable.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if ($mode === 'link' && $strongUserUUID === '') {
      Response::error('[Auth] Fresh passkey session required to link provider.', [
        'step_up_required' => true,
        'recommended_method' => 'passkey',
      ], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $state = FederatedAuth::createOAuthState($providerId, $mode, $mode === 'link' ? '/settings/security/' : '/');
    $location = FederatedAuth::authorizationUrl($providerId, $state['state'], $state['nonce']);
    header('Location: ' . $location, true, 302);
  }

  /**
   * Complete a federated sign-in callback.
   */
  #[Route('auth/federated/callback/{provider}', ['GET'])]
  public function callback(string $provider): void
  {
    $providerId = strtolower(trim($provider));
    $host = FederatedAuth::requestHost();
    $hadAuthenticatedSession = Authentication::validateAndTouchSession();

    if ($providerId !== 'google' || !FederatedAuth::providerIsAvailableForHost($providerId, $host)) {
      $this->redirectOAuthFailure($hadAuthenticatedSession, 'provider_unavailable');
      return;
    }

    $code = $this->queryString('code');
    $stateId = $this->queryString('state');
    if ($code === '' || $stateId === '') {
      $this->redirectOAuthFailure($hadAuthenticatedSession, 'missing_callback_params');
      return;
    }

    $state = FederatedAuth::consumeOAuthState($stateId, $providerId);
    if ($state === []) {
      $this->redirectOAuthFailure($hadAuthenticatedSession, 'invalid_state');
      return;
    }

    $claims = $this->claimsForGoogleCode($code, $state['nonce']);
    if ($claims === [] || ($claims['sub'] ?? '') === '') {
      $this->redirectOAuthFailure($hadAuthenticatedSession, 'invalid_provider_token');
      return;
    }

    if ($state['mode'] === 'link') {
      $userUUID = $state['user_uuid'];
      if ($userUUID === '' || UserRepository::getByUUID($userUUID) === null) {
        $this->redirectOAuthResult('link_failed');
        return;
      }

      try {
        FederatedAuth::linkProviderIdentity($userUUID, $providerId, $claims);
      } catch (\RuntimeException) {
        $this->redirectOAuthResult('already_linked');
        return;
      }

      $this->redirectOAuthResult('linked');
      return;
    }

    $userUUID = FederatedAuth::resolveLinkedUserUUID($providerId, $claims);
    if ($userUUID === '' || UserRepository::getByUUID($userUUID) === null) {
      $userUUID = $this->linkVerifiedGoogleEmailAlias($claims);
      if ($userUUID === '') {
        $userUUID = $this->autoCreateGoogleUser($claims);
      }
      if ($userUUID === '') {
        $this->redirectOAuthFailure($hadAuthenticatedSession, 'provider_not_linked');
        return;
      }
    }

    $sessionHash = bin2hex(random_bytes(32));
    Authentication::setSession($sessionHash, $userUUID);
    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'auth_method' => 'federated_google',
      'auth_strength' => 'standard',
    ]);
    Authentication::setCookie($sessionHash);
    UserRepository::touchLastSignin($userUUID);
    FederatedAuth::touchProviderSignin($userUUID, $providerId, $claims);

    if ($hadAuthenticatedSession) {
      $this->redirectOAuthResult('linked');
      return;
    }

    header('Location: /', true, 302);
  }

  /** @param array<string, string> $claims */
  private function linkVerifiedGoogleEmailAlias(array $claims): string
  {
    if (($claims['email_verified'] ?? '') !== 'true') {
      return '';
    }

    $email = InputSanitizer::sanitizeEmail((string) ($claims['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return '';
    }

    $userUUID = UserRepository::getUUIDFromEmail($email);
    if ($userUUID === '' || UserRepository::getByUUID($userUUID) === null) {
      return '';
    }

    try {
      FederatedAuth::linkProviderIdentity($userUUID, 'google', $claims);
    } catch (\RuntimeException) {
      return '';
    }

    return $userUUID;
  }

  /** @param array<string, string> $claims */
  private function autoCreateGoogleUser(array $claims): string
  {
    if (!Environment::authFederatedAutoCreateLocal()) {
      return '';
    }

    if (($claims['email_verified'] ?? '') !== 'true') {
      return '';
    }

    $email = InputSanitizer::sanitizeEmail((string) ($claims['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return '';
    }

    if (UserRepository::getUUIDFromEmail($email) !== '') {
      return '';
    }

    $fullName = InputSanitizer::sanitizeString(trim((string) ($claims['name'] ?? '')));
    if ($fullName === '') {
      $fullName = $email;
    }

    $userUUID = User::generateUserUUID();
    UserRepository::setUser($userUUID, $email, AuthLevel::USER, $fullName, '', '');
    Database::hset(Keys::USER . ':' . $userUUID, [
      'email_verified' => '1',
      'email_verified_at' => (string) time(),
      'last_auth_method' => 'federated_google',
    ]);

    FederatedAuth::linkProviderIdentity($userUUID, 'google', $claims);
    $businessService = new BusinessDiscoveryService();
    $businessService->ensurePersonalBusiness($userUUID);

    return $userUUID;
  }

  /**
   * Return linked federated identities for the current user.
   */
  #[Route('auth/federated/linked', ['GET'])]
  public function linked(): void
  {
    $userUUID = User::currentUUID();
    if ($userUUID === '' || $userUUID === 'public') {
      Response::error('[Auth] Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    Response::success('[Auth] Federated linked providers.', [
      'providers' => FederatedAuth::linkedProvidersForUser($userUUID),
    ]);
  }

  /**
   * Unlink a federated identity from the current user.
   */
  #[Route('auth/federated/unlink', ['POST'])]
  public function unlink(): void
  {
    $userUUID = FederatedAuth::currentStrongUserUUID();
    if ($userUUID === '') {
      Response::error('[Auth] Fresh passkey session required to unlink provider.', [
        'step_up_required' => true,
        'recommended_method' => 'passkey',
      ], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $body = $this->jsonBody();
    if (!$this->requireSettingsCsrfToken($body)) {
      return;
    }

    $providerId = strtolower(trim($this->scalarString($body['provider'] ?? '')));
    if ($providerId !== 'google') {
      Response::error('[Auth] Federated provider unavailable.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    FederatedAuth::unlinkProviderIdentity($userUUID, $providerId);
    Response::success('[Auth] Federated provider unlinked.', [
      'providers' => FederatedAuth::linkedProvidersForUser($userUUID),
    ]);
  }

  /** @param array<string, mixed> $body */
  private function requireSettingsCsrfToken(array $body): bool
  {
    $csrfToken = $this->scalarString($body['csrf_token'] ?? '');
    if ($csrfToken === '') {
      $csrfToken = $this->scalarString($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }

    if ($csrfToken === '' || !User::current()->verifyFormNonce(self::SETTINGS_CSRF_FORM_TYPE, $csrfToken)) {
      Response::error('[Auth] CSRF token invalid or missing.', [], HttpStatus::HTTP_FORBIDDEN);
      return false;
    }

    return true;
  }

  /** @return array<string, mixed> */
  private function jsonBody(): array
  {
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Read a query string value as trimmed text.
   */
  private function queryString(string $key): string
  {
    $value = $_GET[$key] ?? '';
    return $this->scalarString($value);
  }

  /**
   * Convert a scalar value to trimmed text.
   */
  private function scalarString(mixed $value): string
  {
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /** @return array<string, string> */
  private function claimsForGoogleCode(string $code, string $nonce): array
  {
    $tokenResponse = $this->postForm('https://oauth2.googleapis.com/token', [
      'code' => $code,
      'client_id' => Environment::authGoogleClientId(),
      'client_secret' => Environment::authGoogleClientSecret(),
      'redirect_uri' => FederatedAuth::callbackUrl('google'),
      'grant_type' => 'authorization_code',
    ]);

    $idToken = FederatedAuth::idTokenFromTokenResponse($tokenResponse);
    if ($idToken === '') {
      return [];
    }

    $jwks = $this->getJson('https://www.googleapis.com/oauth2/v3/certs');
    return FederatedAuth::validateGoogleIdToken($idToken, $jwks, $nonce);
  }

  /**
   * @param array<string, string> $fields
   * @return array<string, mixed>
   */
  private function postForm(string $url, array $fields): array
  {
    $ch = curl_init($url);
    if ($ch === false) {
      return [];
    }

    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($raw) || $status < 200 || $status >= 300) {
      return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  /** @return array<string, mixed> */
  private function getJson(string $url): array
  {
    $ch = curl_init($url);
    if ($ch === false) {
      return [];
    }

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($raw) || $status < 200 || $status >= 300) {
      return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Redirect to the sign-in page with a federated auth error.
   */
  private function redirectAuthError(string $code): void
  {
    header('Location: /auth/?auth_tab=signin&federated_error=' . rawurlencode($code), true, 302);
  }

  /**
   * Redirect to security settings with a federated auth result.
   */
  private function redirectSettings(string $status): void
  {
    header('Location: /settings/security/?federated=' . rawurlencode($status), true, 302);
  }

  /**
   * Redirect an OAuth failure to the correct signed-in or signed-out surface.
   */
  private function redirectOAuthFailure(bool $hadAuthenticatedSession, string $code): void
  {
    if ($hadAuthenticatedSession) {
      $this->redirectOAuthResult($code);
      return;
    }

    $this->redirectAuthError($code);
  }

  /**
   * Redirect an OAuth result to security settings.
   */
  private function redirectOAuthResult(string $status): void
  {
    $this->redirectSettings($status);
  }
}
