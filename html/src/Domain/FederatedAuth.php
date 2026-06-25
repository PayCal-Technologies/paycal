<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;

/**
 * Federated sign-in feature gate and provider registry.
 */
final class FederatedAuth
{
  private const STATE_TTL_SECONDS = 600;
  private const MAX_CLOCK_SKEW_SECONDS = 120;

  private const LOCAL_ALLOWED_HOSTS = [
    'dev.paycal.local',
    'mac.paycal.app',
    'localhost',
    '127.0.0.1',
  ];

  /** @return array<int, string> */
  public static function localAllowedHosts(): array
  {
    return self::LOCAL_ALLOWED_HOSTS;
  }

  /**
   * Normalize a host name for allow-list checks.
   */
  public static function normalizeHost(string $host): string
  {
    $cleanHost = strtolower(trim($host));
    $cleanHost = preg_replace('/^[a-z][a-z0-9+\-.]*:\/\//i', '', $cleanHost) ?? $cleanHost;
    $cleanHost = trim($cleanHost);

    if ($cleanHost === '') {
      return '';
    }

    if (str_starts_with($cleanHost, '[')) {
      $closingBracket = strpos($cleanHost, ']');
      if ($closingBracket !== false) {
        return rtrim(substr($cleanHost, 1, $closingBracket - 1), '.');
      }
    }

    $slashPosition = strpos($cleanHost, '/');
    if ($slashPosition !== false) {
      $cleanHost = substr($cleanHost, 0, $slashPosition);
    }

    $colonPosition = strpos($cleanHost, ':');
    if ($colonPosition !== false) {
      $cleanHost = substr($cleanHost, 0, $colonPosition);
    }

    return rtrim($cleanHost, '.');
  }

  /**
   * Resolve the current request host.
   */
  public static function requestHost(): string
  {
    $rawHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? Environment::appDomain();
    if (!is_scalar($rawHost)) {
      return '';
    }

    return self::normalizeHost((string) $rawHost);
  }

  /**
   * Return whether a host passes the local environment gate.
   */
  public static function localGatePassesForHost(string $host): bool
  {
    return in_array(self::normalizeHost($host), self::LOCAL_ALLOWED_HOSTS, true);
  }

  /**
   * Return whether federated auth is available for a host.
   */
  public static function featureGatePassesForHost(string $host): bool
  {
    if (!Environment::authFederatedSigninEnabled()) {
      return false;
    }

    if (!Environment::authFederatedSigninLocalOnly()) {
      return true;
    }

    return self::localGatePassesForHost($host);
  }

  /**
   * Return whether a provider can be used on a host.
   */
  public static function providerIsAvailableForHost(string $providerId, string $host): bool
  {
    if (!self::featureGatePassesForHost($host)) {
      return false;
    }

    $registry = self::providerRegistry();
    if (!isset($registry[$providerId])) {
      return false;
    }

    $provider = $registry[$providerId];
    return $provider['enabled'] === true && $provider['client_id_present'] === true;
  }

  /** @return array<string, array<string, bool|string|array<int, string>>> */
  public static function providerRegistry(): array
  {
    return [
      'google' => [
        'id' => 'google',
        'label' => 'Google',
        'button_label' => 'Continue with Google',
        'button_label_key' => 'AUTH_FEDERATED_CONTINUE_GOOGLE',
        'icon_key' => 'google',
        'enabled' => Environment::authProviderGoogleEnabled(),
        'client_id_present' => Environment::authGoogleClientId() !== '',
        'callback_path' => '/api/v1/auth/federated/callback/google',
        'issuer' => 'https://accounts.google.com',
        'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_endpoint' => 'https://oauth2.googleapis.com/token',
        'jwks_uri' => 'https://www.googleapis.com/oauth2/v3/certs',
        'scopes' => ['openid', 'email', 'profile'],
        'supports_fedcm' => true,
        'fedcm_enabled' => Environment::authProviderFedcmEnabled(),
      ],
      'apple' => [
        'id' => 'apple',
        'label' => 'Apple',
        'button_label' => 'Continue with Apple',
        'button_label_key' => 'AUTH_FEDERATED_CONTINUE_APPLE',
        'icon_key' => 'apple',
        'enabled' => Environment::authProviderAppleEnabled(),
        'client_id_present' => self::appleCredentialsPresent(),
        'callback_path' => '/api/v1/auth/federated/callback/apple',
        'issuer' => 'https://appleid.apple.com',
        'authorization_endpoint' => 'https://appleid.apple.com/auth/authorize',
        'token_endpoint' => 'https://appleid.apple.com/auth/token',
        'jwks_uri' => 'https://appleid.apple.com/auth/keys',
        'scopes' => ['name', 'email'],
        'response_mode' => 'form_post',
        'callback_methods' => ['POST'],
        'supports_fedcm' => false,
        'fedcm_enabled' => false,
      ],
      'microsoft' => [
        'id' => 'microsoft',
        'label' => 'Microsoft',
        'button_label' => 'Continue with Microsoft',
        'button_label_key' => 'AUTH_FEDERATED_CONTINUE_MICROSOFT',
        'icon_key' => 'microsoft',
        'enabled' => Environment::authProviderMicrosoftEnabled(),
        'client_id_present' => Environment::authMicrosoftClientId() !== '',
        'callback_path' => '/api/v1/auth/federated/callback/microsoft',
        'issuer' => 'https://login.microsoftonline.com/common/v2.0',
        'authorization_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        'token_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
        'jwks_uri' => 'https://login.microsoftonline.com/common/discovery/v2.0/keys',
        'scopes' => ['openid', 'email', 'profile'],
        'supports_fedcm' => false,
        'fedcm_enabled' => false,
      ],
    ];
  }

  /** @return array<string, bool|string|array<int, string>> */
  public static function provider(string $providerId): array
  {
    $registry = self::providerRegistry();
    return $registry[$providerId] ?? [];
  }

  /**
   * Build the provider callback URL.
   */
  public static function callbackUrl(string $providerId): string
  {
    $provider = self::provider($providerId);
    $path = is_string($provider['callback_path'] ?? null) ? $provider['callback_path'] : '';
    return Environment::appURL($path);
  }

  /**
   * Return whether Apple federated auth credentials are configured.
   */
  public static function appleCredentialsPresent(): bool
  {
    return Environment::authAppleCredentialsPresent();
  }

  /**
   * Resolve the OAuth client ID for a provider.
   */
  public static function providerClientId(string $providerId): string
  {
    return match ($providerId) {
      'google' => Environment::authGoogleClientId(),
      'apple' => Environment::authAppleClientId(),
      'microsoft' => Environment::authMicrosoftClientId(),
      default => '',
    };
  }

  /**
   * Build the provider authorization URL.
   */
  public static function authorizationUrl(string $providerId, string $state, string $nonce): string
  {
    $provider = self::provider($providerId);
    $endpoint = is_string($provider['authorization_endpoint'] ?? null) ? $provider['authorization_endpoint'] : '';
    $scopes = is_array($provider['scopes'] ?? null) ? $provider['scopes'] : ['openid', 'email', 'profile'];
    $clientId = self::providerClientId($providerId);
    if ($endpoint === '' || $clientId === '') {
      return '';
    }

    $params = [
      'client_id' => $clientId,
      'redirect_uri' => self::callbackUrl($providerId),
      'response_type' => 'code',
      'state' => $state,
      'nonce' => $nonce,
    ];

    if ($providerId === 'apple') {
      $params['response_mode'] = 'form_post';
      $params['scope'] = implode(' ', array_map('strval', $scopes));
    } else {
      $params['scope'] = implode(' ', array_map('strval', $scopes));
      $params['prompt'] = 'select_account';
    }

    return $endpoint . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
  }

  /** @return array{state: string, nonce: string, mode: string, user_uuid: string} */
  public static function createOAuthState(string $providerId, string $mode, string $redirectTarget = '/'): array
  {
    $normalizedMode = $mode === 'link' ? 'link' : 'signin';
    $state = bin2hex(random_bytes(32));
    $nonce = bin2hex(random_bytes(32));
    $userUUID = '';

    if ($normalizedMode === 'link') {
      $userUUID = self::currentStrongUserUUID();
    }

    $payload = [
      'provider' => $providerId,
      'mode' => $normalizedMode,
      'nonce' => $nonce,
      'redirect' => self::safeRedirectTarget($redirectTarget),
      'user_uuid' => $userUUID,
      'created_at' => (string) time(),
    ];

    Database::set(self::oauthStateKey($state), self::jsonEncode($payload), self::STATE_TTL_SECONDS);

    return [
      'state' => $state,
      'nonce' => $nonce,
      'mode' => $normalizedMode,
      'user_uuid' => $userUUID,
    ];
  }

  /** @return array<string, string> */
  public static function consumeOAuthState(string $state, string $providerId, string $mode = ''): array
  {
    $stateId = trim($state);
    if ($stateId === '') {
      return [];
    }

    $json = Database::getdel(self::oauthStateKey($stateId));
    if ($json === '') {
      return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
      return [];
    }

    $payload = [];
    foreach (['provider', 'mode', 'nonce', 'redirect', 'user_uuid', 'created_at'] as $field) {
      $value = $decoded[$field] ?? '';
      $payload[$field] = is_scalar($value) ? (string) $value : '';
    }

    if ($payload['provider'] !== $providerId || ($mode !== '' && $payload['mode'] !== $mode) || $payload['nonce'] === '') {
      return [];
    }

    return $payload;
  }

  /**
   * Hash a provider subject identifier for storage.
   */
  public static function subjectHash(string $providerId, string $subject): string
  {
    return hash('sha256', $providerId . '|' . $subject);
  }

  /** @param array<string, string> $claims */
  public static function linkProviderIdentity(string $userUUID, string $providerId, array $claims): void
  {
    $subject = trim($claims['sub'] ?? '');
    if ($userUUID === '' || $providerId === '' || $subject === '') {
      return;
    }

    $subjectHash = self::subjectHash($providerId, $subject);
    $now = (string) time();
    $existing = Database::get(self::identityLookupKey($providerId, $subjectHash));
    if ($existing !== '' && $existing !== $userUUID) {
      throw new \RuntimeException('Provider identity is already linked.');
    }

    Database::set(self::identityLookupKey($providerId, $subjectHash), $userUUID);
    Database::set(self::userProviderKey($userUUID, $providerId), self::jsonEncode([
      'provider' => $providerId,
      'subject_hash' => $subjectHash,
      'email' => $claims['email'] ?? '',
      'email_verified' => $claims['email_verified'] ?? '',
      'linked_at' => $now,
      'last_signin_at' => '',
    ]));
  }

  /** @param array<string, string> $claims */
  public static function resolveLinkedUserUUID(string $providerId, array $claims): string
  {
    $subject = trim($claims['sub'] ?? '');
    if ($providerId === '' || $subject === '') {
      return '';
    }

    $subjectHash = self::subjectHash($providerId, $subject);
    return Database::get(self::identityLookupKey($providerId, $subjectHash));
  }

  /** @param array<string, string> $claims */
  public static function touchProviderSignin(string $userUUID, string $providerId, array $claims): void
  {
    $linked = self::linkedProvider($userUUID, $providerId);
    if ($linked === []) {
      return;
    }

    $linked['last_signin_at'] = (string) time();
    if (($claims['email'] ?? '') !== '') {
      $linked['email'] = $claims['email'];
    }
    Database::set(self::userProviderKey($userUUID, $providerId), self::jsonEncode($linked));
  }

  /** @return array<string, string> */
  public static function linkedProvider(string $userUUID, string $providerId): array
  {
    $json = Database::get(self::userProviderKey($userUUID, $providerId));
    if ($json === '') {
      return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
      return [];
    }

    $linked = [];
    foreach (['provider', 'subject_hash', 'email', 'email_verified', 'linked_at', 'last_signin_at'] as $field) {
      $value = $decoded[$field] ?? '';
      $linked[$field] = is_scalar($value) ? (string) $value : '';
    }

    return $linked;
  }

  /** @return array<int, array<string, bool|string>> */
  public static function linkedProvidersForUser(string $userUUID): array
  {
    $providers = [];
    foreach (['google', 'apple', 'microsoft'] as $providerId) {
      $linked = self::linkedProvider($userUUID, $providerId);
      $provider = self::provider($providerId);
      if (!is_string($provider['label'] ?? null)) {
        continue;
      }

      $providers[] = [
        'id' => $providerId,
        'label' => $provider['label'],
        'linked' => $linked !== [],
        'email' => $linked['email'] ?? '',
        'linked_at' => $linked['linked_at'] ?? '',
        'last_signin_at' => $linked['last_signin_at'] ?? '',
        'enabled' => self::providerIsAvailableForHost($providerId, self::requestHost()),
      ];
    }

    return $providers;
  }

  /**
   * Unlink provider identity.
   */
  public static function unlinkProviderIdentity(string $userUUID, string $providerId): bool
  {
    $linked = self::linkedProvider($userUUID, $providerId);
    if ($linked === []) {
      return false;
    }

    $subjectHash = $linked['subject_hash'] ?? '';
    if ($subjectHash !== '') {
      Database::unlink(self::identityLookupKey($providerId, $subjectHash));
    }
    Database::unlink(self::userProviderKey($userUUID, $providerId));
    return true;
  }

  /**
   * Return the current strong-authenticated user UUID.
   */
  public static function currentStrongUserUUID(): string
  {
    $sessionHash = Authentication::getSessionHashFromCookie();
    if ($sessionHash === null || $sessionHash === '' || !Authentication::sessionExists($sessionHash)) {
      return '';
    }

    $sessionKey = Keys::SESSION . ':' . $sessionHash;
    $strength = strtolower((string) Database::hget($sessionKey, 'auth_strength'));
    if ($strength === 'strong') {
      return (string) Database::hget($sessionKey, 'user_uuid');
    }

    $stepUpTimestamp = (int) Database::hget($sessionKey, 'passkey_stepup_at');
    $maxAge = (int) SystemConfig::get('email_change_stepup_max_age_seconds');
    if ($maxAge <= 0) {
      $maxAge = 900;
    }

    if ($stepUpTimestamp > 0 && (time() - $stepUpTimestamp) <= $maxAge) {
      return (string) Database::hget($sessionKey, 'user_uuid');
    }

    return '';
  }

  /** @param array<string, mixed> $tokenResponse */
  public static function idTokenFromTokenResponse(array $tokenResponse): string
  {
    $idToken = $tokenResponse['id_token'] ?? '';
    return is_scalar($idToken) ? trim((string) $idToken) : '';
  }

  /**
   * @param array<string, mixed> $jwks
   * @return array<string, string>
   */
  public static function validateGoogleIdToken(string $idToken, array $jwks, string $expectedNonce): array
  {
    return self::validateJwt($idToken, $jwks, Environment::authGoogleClientId(), ['https://accounts.google.com', 'accounts.google.com'], $expectedNonce);
  }

  /**
   * @param array<string, mixed> $jwks
   * @return array<string, string>
   */
  public static function validateAppleIdToken(string $idToken, array $jwks, string $expectedNonce): array
  {
    return self::validateJwt($idToken, $jwks, Environment::authAppleClientId(), 'https://appleid.apple.com', $expectedNonce);
  }

  /**
   * Build the Apple OAuth client secret JWT.
   */
  public static function buildAppleClientSecret(): string
  {
    if (!self::appleCredentialsPresent()) {
      return '';
    }

    $now = time();
    return self::signEs256Jwt(
      ['alg' => 'ES256', 'kid' => Environment::authAppleKeyId()],
      [
        'iss' => Environment::authAppleTeamId(),
        'iat' => $now,
        'exp' => $now + 300,
        'aud' => 'https://appleid.apple.com',
        'sub' => Environment::authAppleClientId(),
      ],
      Environment::authApplePrivateKey(),
    );
  }

  /**
   * @param array<string, string> $claims
   */
  public static function emailVerifiedForProvider(string $providerId, array $claims): bool
  {
    $email = trim($claims['email'] ?? '');
    if ($email === '') {
      return false;
    }

    if ($providerId === 'apple') {
      $verified = strtolower(trim($claims['email_verified'] ?? ''));
      return $verified !== 'false';
    }

    return ($claims['email_verified'] ?? '') === 'true';
  }

  /**
   * @param array<string, mixed> $jwks
   * @param string|array<int, string> $issuer
   * @return array<string, string>
   */
  public static function validateJwt(string $jwt, array $jwks, string $audience, string|array $issuer, string $nonce): array
  {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
      return [];
    }

    $headerJson = self::base64UrlDecode($parts[0]);
    $payloadJson = self::base64UrlDecode($parts[1]);
    $signature = self::base64UrlDecode($parts[2]);
    if ($headerJson === '' || $payloadJson === '' || $signature === '') {
      return [];
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);
    if (!is_array($header) || !is_array($payload)) {
      return [];
    }

    if (($header['alg'] ?? '') !== 'RS256') {
      return [];
    }

    $kid = is_scalar($header['kid'] ?? null) ? (string) $header['kid'] : '';
    $publicKey = self::publicKeyForKid($jwks, $kid);
    if ($publicKey === '') {
      return [];
    }

    $verified = openssl_verify($parts[0] . '.' . $parts[1], $signature, $publicKey, OPENSSL_ALGO_SHA256);
    if ($verified !== 1) {
      return [];
    }

    $now = time();
    $expRaw = $payload['exp'] ?? 0;
    $iatRaw = $payload['iat'] ?? 0;
    $exp = is_numeric($expRaw) ? (int) $expRaw : 0;
    $iat = is_numeric($iatRaw) ? (int) $iatRaw : 0;
    if ($exp < ($now - self::MAX_CLOCK_SKEW_SECONDS) || $iat > ($now + self::MAX_CLOCK_SKEW_SECONDS)) {
      return [];
    }

    $aud = $payload['aud'] ?? '';
    $audienceMatches = false;
    if (is_array($aud)) {
      foreach ($aud as $audEntry) {
        if (is_scalar($audEntry) && (string) $audEntry === $audience) {
          $audienceMatches = true;
          break;
        }
      }
    } elseif (is_scalar($aud)) {
      $audienceMatches = (string) $aud === $audience;
    }

    $issuerClaim = $payload['iss'] ?? '';
    $nonceClaim = $payload['nonce'] ?? '';
    $allowedIssuers = is_array($issuer)
      ? array_values(array_filter(array_map('strval', $issuer), static fn (string $value): bool => $value !== ''))
      : [(string) $issuer];
    if (
      !$audienceMatches
      || !is_scalar($issuerClaim)
      || !in_array((string) $issuerClaim, $allowedIssuers, true)
      || !is_scalar($nonceClaim)
      || (string) $nonceClaim !== $nonce
    ) {
      return [];
    }

    $claims = [];
    foreach (['sub', 'email', 'email_verified', 'name', 'picture'] as $field) {
      $value = $payload[$field] ?? '';
      if (is_bool($value)) {
        $claims[$field] = $value ? 'true' : 'false';
      } elseif (is_scalar($value)) {
        $claims[$field] = (string) $value;
      } else {
        $claims[$field] = '';
      }
    }

    return $claims;
  }

  /** @return array<int, array<string, bool|string>> */
  public static function availableProviders(): array
  {
    return self::availableProvidersForHost(self::requestHost());
  }

  /** @return array<int, array<string, bool|string>> */
  public static function availableProvidersForHost(string $host): array
  {
    if (!self::featureGatePassesForHost($host)) {
      return [];
    }

    $providers = [];
    foreach (self::providerRegistry() as $provider) {
      if ($provider['enabled'] !== true || $provider['client_id_present'] !== true) {
        continue;
      }

      if (
        !is_string($provider['id'])
        || !is_string($provider['label'])
        || !is_string($provider['button_label'])
        || !is_string($provider['icon_key'])
      ) {
        continue;
      }

      $buttonLabelKey = $provider['button_label_key'] ?? '';
      $providers[] = [
        'id' => $provider['id'],
        'label' => $provider['label'],
        'button_label' => $provider['button_label'],
        'button_label_key' => is_string($buttonLabelKey) ? $buttonLabelKey : '',
        'icon_key' => $provider['icon_key'],
        'supports_fedcm' => $provider['supports_fedcm'] === true,
        'fedcm_enabled' => $provider['fedcm_enabled'] === true,
      ];
    }

    return $providers;
  }

  /**
   * Build the OAuth state Redis key.
   */
  private static function oauthStateKey(string $state): string
  {
    return 'federated_auth:state:' . $state;
  }

  /**
   * Identity lookup key.
   */
  public static function identityLookupKey(string $providerId, string $subjectHash): string
  {
    return 'federated_auth:identity:' . $providerId . ':' . $subjectHash;
  }

  /**
   * User provider key.
   */
  public static function userProviderKey(string $userUUID, string $providerId): string
  {
    return 'federated_auth:user:' . $userUUID . ':' . $providerId;
  }

  /**
   * Safe redirect target.
   */
  private static function safeRedirectTarget(string $redirectTarget): string
  {
    $target = trim($redirectTarget);
    if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
      return '/';
    }
    return $target;
  }

  /** @param array<string, string> $payload */
  private static function jsonEncode(array $payload): string
  {
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
  }

  /**
   * Base64 url decode.
   */
  private static function base64UrlDecode(string $input): string
  {
    $remainder = strlen($input) % 4;
    if ($remainder > 0) {
      $input .= str_repeat('=', 4 - $remainder);
    }
    $decoded = base64_decode(strtr($input, '-_', '+/'), true);
    return is_string($decoded) ? $decoded : '';
  }

  /**
   * @param array<string, string> $header
   * @param array<string, int|string> $payload
   */
  private static function signEs256Jwt(array $header, array $payload, string $privateKeyPem): string
  {
    $segments = [
      self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES) ?: '{}'),
      self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}'),
    ];
    $signingInput = implode('.', $segments);

    $privateKey = openssl_pkey_get_private($privateKeyPem);
    if ($privateKey === false) {
      return '';
    }

    $derSignature = '';
    if (!openssl_sign($signingInput, $derSignature, $privateKey, OPENSSL_ALGO_SHA256)) {
      return '';
    }

    $rawSignature = self::ecdsaDerSignatureToJose($derSignature);
    if ($rawSignature === '') {
      return '';
    }

    $segments[] = self::base64UrlEncode($rawSignature);
    return implode('.', $segments);
  }

  private static function base64UrlEncode(string $input): string
  {
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
  }

  private static function ecdsaDerSignatureToJose(string $der): string
  {
    if ($der === '' || ord($der[0]) !== 0x30) {
      return '';
    }

    $offset = 2;
    if (ord($der[1]) > 0x80) {
      $offset = 2 + (ord($der[1]) & 0x7f);
    }

    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
      return '';
    }

    $rLength = ord($der[$offset + 1]);
    $r = substr($der, $offset + 2, $rLength);
    $sOffset = $offset + 2 + $rLength;
    if (!isset($der[$sOffset]) || ord($der[$sOffset]) !== 0x02) {
      return '';
    }

    $sLength = ord($der[$sOffset + 1]);
    $s = substr($der, $sOffset + 2, $sLength);
    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");
    if ($r === '' || $s === '') {
      return '';
    }

    $partLength = max(strlen($r), strlen($s));
    return str_pad($r, $partLength, "\x00", STR_PAD_LEFT)
      . str_pad($s, $partLength, "\x00", STR_PAD_LEFT);
  }

  /** @param array<string, mixed> $jwks */
  private static function publicKeyForKid(array $jwks, string $kid): string
  {
    $keys = $jwks['keys'] ?? [];
    if (!is_array($keys)) {
      return '';
    }

    foreach ($keys as $jwk) {
      if (!is_array($jwk)) {
        continue;
      }
      $jwkKid = $jwk['kid'] ?? '';
      if (!is_scalar($jwkKid) || (string) $jwkKid !== $kid) {
        continue;
      }
      $certs = $jwk['x5c'] ?? [];
      if (is_array($certs) && isset($certs[0]) && is_scalar($certs[0])) {
        $body = chunk_split((string) $certs[0], 64, "\n");
        return "-----BEGIN CERTIFICATE-----\n" . $body . "-----END CERTIFICATE-----\n";
      }

      $modulus = is_scalar($jwk['n'] ?? null) ? self::base64UrlDecode((string) $jwk['n']) : '';
      $exponent = is_scalar($jwk['e'] ?? null) ? self::base64UrlDecode((string) $jwk['e']) : '';
      if ($modulus !== '' && $exponent !== '') {
        return self::rsaPublicKeyPem($modulus, $exponent);
      }
    }

    return '';
  }

  /**
   * Convert RSA public key components into PEM format.
   */
  private static function rsaPublicKeyPem(string $modulus, string $exponent): string
  {
    $rsaPublicKey = self::derSequence(
      self::derInteger($modulus) . self::derInteger($exponent)
    );
    $algorithmIdentifier = self::derSequence(
      self::derObjectIdentifier("\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01") . "\x05\x00"
    );
    $subjectPublicKeyInfo = self::derSequence(
      $algorithmIdentifier . self::derBitString($rsaPublicKey)
    );

    return "-----BEGIN PUBLIC KEY-----\n"
      . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
      . "-----END PUBLIC KEY-----\n";
  }

  /**
   * Encode a DER sequence.
   */
  private static function derSequence(string $payload): string
  {
    return "\x30" . self::derLength(strlen($payload)) . $payload;
  }

  /**
   * Encode a DER integer.
   */
  private static function derInteger(string $value): string
  {
    $value = ltrim($value, "\x00");
    if ($value === '') {
      $value = "\x00";
    }

    if ((ord($value[0]) & 0x80) !== 0) {
      $value = "\x00" . $value;
    }

    return "\x02" . self::derLength(strlen($value)) . $value;
  }

  /**
   * Encode a DER bit string.
   */
  private static function derBitString(string $payload): string
  {
    return "\x03" . self::derLength(strlen($payload) + 1) . "\x00" . $payload;
  }

  /**
   * Encode a DER object identifier.
   */
  private static function derObjectIdentifier(string $payload): string
  {
    return "\x06" . self::derLength(strlen($payload)) . $payload;
  }

  /**
   * Encode a DER length.
   */
  private static function derLength(int $length): string
  {
    if ($length < 0x80) {
      return chr($length);
    }

    $bytes = '';
    while ($length > 0) {
      $bytes = chr($length & 0xff) . $bytes;
      $length >>= 8;
    }

    return chr(0x80 | strlen($bytes)) . $bytes;
  }
}
