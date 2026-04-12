<?php declare(strict_types=1);

namespace PayCal\Controllers;

use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Domain\SecurityLog;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;
use PayCal\Domain\RecoveryKey;
use PayCal\Observability\Lens;

/**
 * PasskeyController.php
 *
 * Purpose: WebAuthn passkey lifecycle controller for registration, login,
 * listing, deletion, and account-recovery-adjacent credential flows.
 *
 * Developer notes:
 * - This controller sits on top of WebAuthn flows and should preserve strict
 *   challenge, origin, and recovery/security checks.
 * - Credential registration and deletion affect encryption bootstrap and org
 *   wrap flows, so changes here can have broader security implications.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Passkey API surface.
 *
 * Responsibilities:
 * - Orchestrate WebAuthn challenge/response flows.
 * - Manage persisted passkey metadata for authenticated users.
 * - Coordinate related security and recovery side effects safely.
 */
final class PasskeyController
{
  private const CHALLENGE_TTL_SECONDS = 300;
  private const LIMIT_PER_MINUTE_IP = 5;
  private const RATE_WINDOW_SECONDS = 70;
  private const MAX_PASSKEY_NAME_LENGTH = 100;
  private const WEBAUTHN_TIMEOUT_SECONDS = 60;
  private const CHALLENGE_ID_BYTES = 16;
  private const SECRET_BYTES = 32;

  /**
   * Start passkey-first account registration.
   */
  #[Route('auth/passkey/signup/start', ['POST'])]
  /**
   * Handles signupStart operation.
   */
  public function signupStart(): void
  {
    if (!$this->enforceEndpointRateLimit('signup:start')) {
      return;
    }

    $body = $this->jsonBody();
    $fullName = InputSanitizer::sanitizeString($this->scalarString($body['fullName'] ?? ''));
    $email = InputSanitizer::sanitizeEmail($this->scalarString($body['email'] ?? ''));
    $inviteCode = $this->scalarString($body['inviteCode'] ?? '');
    $deviceName = InputSanitizer::sanitizeString($this->scalarString($body['deviceName'] ?? 'Passkey'));

    if ($fullName === '' || mb_strlen($fullName) < 2) {
      Response::error('Full name is required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Response::error('Valid email is required.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $expectedInviteCode = trim(Environment::inviteCode());
    if ($expectedInviteCode !== '' && trim($inviteCode) !== $expectedInviteCode) {
      Response::error('Invalid invite code.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (UserRepository::emailExists($email)) {
      Response::error('Email is already registered.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if ($deviceName === '') {
      $deviceName = 'Passkey';
    }

    $tempUserUUID = User::generateUserUUID();
    $webauthn = $this->createWebAuthn();
    $createArgs = $webauthn->getCreateArgs(
      $tempUserUUID,
      $email,
      $fullName,
      self::WEBAUTHN_TIMEOUT_SECONDS,
      'required',
      'required'
    );

    $challenge = $this->encodeB64Url($webauthn->getChallenge()->getBinaryString());
    $challengeId = bin2hex(random_bytes(self::CHALLENGE_ID_BYTES));
    $challengeKey = $this->signupChallengeKey($challengeId);

    Database::hset($challengeKey, [
      'challenge' => $challenge,
      'email' => $email,
      'full_name' => $fullName,
      'device_name' => $deviceName,
      'created_at' => (string) time(),
    ]);
    Database::expire($challengeKey, self::CHALLENGE_TTL_SECONDS);

    Response::success('[PASSKEY] Signup challenge created.', [
      'challengeId' => $challengeId,
      'publicKey' => $createArgs->publicKey,
    ]);
  }

  /**
   * Finish passkey-first account registration.
   */
  #[Route('auth/passkey/signup/finish', ['POST'])]
  /**
   * Handles signupFinish operation.
   */
  public function signupFinish(): void
  {
    if (!$this->enforceEndpointRateLimit('signup:finish')) {
      return;
    }

    $body = $this->jsonBody();
    $challengeId = $this->scalarString($body['challengeId'] ?? '');
    if ($challengeId === '') {
      Response::error('Signup failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $challengeKey = $this->signupChallengeKey($challengeId);
    if (!Database::exists($challengeKey)) {
      Response::error('Signup failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $challengeData = Database::hgetall($challengeKey);
    Database::unlink($challengeKey);

    $email = InputSanitizer::sanitizeEmail($this->scalarString($challengeData['email'] ?? ''));
    $fullName = InputSanitizer::sanitizeString($this->scalarString($challengeData['full_name'] ?? ''));
    $deviceName = InputSanitizer::sanitizeString($this->scalarString($challengeData['device_name'] ?? 'Passkey'));
    $challengeBinary = $this->decodeB64Url($this->scalarString($challengeData['challenge'] ?? ''));

    if ($email === '' || $fullName === '' || $challengeBinary === null) {
      Response::error('Signup failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (UserRepository::emailExists($email)) {
      Response::error('Email is already registered.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $credential = is_array($body['credential'] ?? null) ? $body['credential'] : $body;
    $responseData = is_array($credential['response'] ?? null) ? $credential['response'] : [];
    $clientDataJSON = $this->decodeB64Url($this->scalarString($responseData['clientDataJSON'] ?? ''));
    $attestationObject = $this->decodeB64Url($this->scalarString($responseData['attestationObject'] ?? ''));

    if ($clientDataJSON === null || $attestationObject === null) {
      Response::error('Signup failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    try {
      $webauthn = $this->createWebAuthn();
      $result = $webauthn->processCreate($clientDataJSON, $attestationObject, $challengeBinary, true, true);

      $credentialId = $this->encodeB64Url($result->credentialId);
      $publicKeyPem = (string) $result->credentialPublicKey;
      $signCount = (int) ($result->signatureCounter ?? 0);
    } catch (WebAuthnException) {
      Response::error('Signup failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if (Database::exists($this->credentialKey($credentialId))) {
      Response::error('Signup failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $userUUID = User::generateUserUUID();
    $placeholderPasswordHash = password_hash(bin2hex(random_bytes(self::SECRET_BYTES)), PASSWORD_DEFAULT);

    UserRepository::setUser(
      $userUUID,
      $placeholderPasswordHash,
      $email,
      AuthLevel::USER,
      $fullName,
      '',
      ''
    );

    $credentialKey = $this->credentialKey($credentialId);
    $now = (string) time();
    Database::hset($credentialKey, [
      'credential_id' => $credentialId,
      'user_uuid' => $userUUID,
      'public_key_pem' => $publicKeyPem,
      'sign_count' => (string) $signCount,
      'transports' => $this->jsonEncodeArray($responseData['transports'] ?? []),
      'device_name' => $deviceName === '' ? 'Passkey' : $deviceName,
      'created_at' => $now,
      'last_used_at' => $now,
    ]);
    Database::sadd($this->userCredentialsKey($userUUID), $credentialId);

    $salt = base64_encode(random_bytes(self::SECRET_BYTES));
    Database::hset(Keys::USER . ':' . $userUUID, [
      'webauthn_enabled' => '1',
      'encryption_salt' => $salt,
      'crypto_version' => '1',
      'last_auth_method' => 'passkey',
    ]);

    $organizationService = new OrganizationDiscoveryService();
    $organizationService->ensurePersonalOrganization($userUUID);

    $sessionHash = bin2hex(random_bytes(self::SECRET_BYTES));
    Authentication::setSession($sessionHash, $userUUID);
    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'auth_method' => 'passkey',
      'auth_strength' => 'strong',
    ]);
    Authentication::setCookie($sessionHash);
    UserRepository::touchLastSignin($userUUID);

    SecurityLog::log('passkey_signup_success', [
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
    ]);

    $verificationStatus = $this->sendVerificationEmailIfNeeded($userUUID);

    Response::success('[PASSKEY] Signup successful.', [
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
      'crypto_version' => 1,
      'verification_email_attempted' => $verificationStatus['attempted'],
      'verification_email_sent' => $verificationStatus['sent'],
      'verification_email_reason' => $verificationStatus['reason'],
    ]);
  }

  /**
   * Start passkey enrollment for an authenticated user.
   */
  #[Route('auth/passkey/register/start', ['POST'])]
  /**
   * Handles registerStart operation.
   */
  public function registerStart(): void
  {
    if (!$this->enforceEndpointRateLimit('register:start')) {
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $user = User::current();
    if (!$this->isValidUserUUID($user->user_uuid)) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $body = $this->jsonBody();
    $deviceName = $this->scalarString($body['deviceName'] ?? '');
    if ('' === $deviceName) {
      $deviceName = 'Passkey';
    }

    $webauthn = $this->createWebAuthn();
    $createArgs = $webauthn->getCreateArgs(
      $user->user_uuid,
      $user->email,
      $user->full_name !== '' ? $user->full_name : $user->email,
      self::WEBAUTHN_TIMEOUT_SECONDS,
      'required',
      'required',
      null,
      $this->credentialIdBinaries($user->user_uuid)
    );

    $challenge = $this->encodeB64Url($webauthn->getChallenge()->getBinaryString());
    $challengeId = bin2hex(random_bytes(self::CHALLENGE_ID_BYTES));
    $challengeKey = $this->registerChallengeKey($challengeId);

    Database::hset($challengeKey, [
      'challenge' => $challenge,
      'user_uuid' => $user->user_uuid,
      'device_name' => $deviceName,
      'created_at' => (string) time(),
    ]);
    Database::expire($challengeKey, self::CHALLENGE_TTL_SECONDS);

    Response::success('[PASSKEY] Registration challenge created.', [
      'challengeId' => $challengeId,
      'publicKey' => $createArgs->publicKey,
    ]);
  }

  /**
   * Finish passkey enrollment for an authenticated user.
   */
  #[Route('auth/passkey/register/finish', ['POST'])]
  /**
   * Handles registerFinish operation.
   */
  public function registerFinish(): void
  {
    if (!$this->enforceEndpointRateLimit('register:finish')) {
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $body = $this->jsonBody();
    $challengeId = $this->scalarString($body['challengeId'] ?? '');
    if ('' === $challengeId) {
      Response::error('Registration failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $challengeKey = $this->registerChallengeKey($challengeId);
    if (!Database::exists($challengeKey)) {
      Response::error('Registration failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $challengeData = Database::hgetall($challengeKey);
    Database::unlink($challengeKey);

    $sessionUser = User::current();
    $expectedUserUUID = $this->scalarString($challengeData['user_uuid'] ?? '');
    if (
      !$this->isValidUserUUID($sessionUser->user_uuid)
      || !$this->isValidUserUUID($expectedUserUUID)
      || $expectedUserUUID !== $sessionUser->user_uuid
    ) {
      Response::error('Registration failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $credential = is_array($body['credential'] ?? null) ? $body['credential'] : $body;
    $responseData = is_array($credential['response'] ?? null) ? $credential['response'] : [];

    $clientDataJSON = $this->decodeB64Url($this->scalarString($responseData['clientDataJSON'] ?? ''));
    $attestationObject = $this->decodeB64Url($this->scalarString($responseData['attestationObject'] ?? ''));
    $challengeBinary = $this->decodeB64Url($this->scalarString($challengeData['challenge'] ?? ''));

    if ($clientDataJSON === null || $attestationObject === null || $challengeBinary === null) {
      Response::error('Registration failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    try {
      $webauthn = $this->createWebAuthn();
      $result = $webauthn->processCreate($clientDataJSON, $attestationObject, $challengeBinary, true, true);

      $credentialId = $this->encodeB64Url($result->credentialId);
      $publicKeyPem = (string) $result->credentialPublicKey;
      $signCount = (int) ($result->signatureCounter ?? 0);
    } catch (WebAuthnException) {
      Response::error('Registration failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $credentialKey = $this->credentialKey($credentialId);
    if (Database::exists($credentialKey)) {
      $existing = Database::hgetall($credentialKey);
      if (($existing['user_uuid'] ?? '') !== $expectedUserUUID) {
        Response::error('Registration failed.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }
    }

    $deviceName = $this->scalarString($challengeData['device_name'] ?? 'Passkey');
    $transports = $this->jsonEncodeArray($responseData['transports'] ?? []);
    $now = (string) time();

    Database::hset($credentialKey, [
      'credential_id' => $credentialId,
      'user_uuid' => $expectedUserUUID,
      'public_key_pem' => $publicKeyPem,
      'sign_count' => (string) $signCount,
      'transports' => $transports,
      'device_name' => $deviceName,
      'created_at' => $now,
      'last_used_at' => '',
    ]);
    Database::sadd($this->userCredentialsKey($expectedUserUUID), $credentialId);
    Database::hset(Keys::USER . ':' . $expectedUserUUID, ['webauthn_enabled' => '1']);

    SecurityLog::log('passkey_registered', [
      'user_uuid' => $expectedUserUUID,
      'credential_id' => $credentialId,
      'device_name' => $deviceName,
    ]);

      $verificationStatus = $this->sendVerificationEmailIfNeeded($expectedUserUUID);

    Response::success('[PASSKEY] Registration complete.', [
      'credentialId' => $credentialId,
      'deviceName' => $deviceName,
        'verification_email_attempted' => $verificationStatus['attempted'],
        'verification_email_sent' => $verificationStatus['sent'],
        'verification_email_reason' => $verificationStatus['reason'],
    ]);
  }

  /**
   * Start a passkey authentication challenge.
   */
  #[Route('auth/passkey/login/start', ['POST'])]
  /**
   * Handles loginStart operation.
   */
  public function loginStart(): void
  {
    if (!$this->enforceEndpointRateLimit('login:start')) {
      return;
    }

    $body = $this->jsonBody();
    $email = InputSanitizer::sanitizeEmail($this->scalarString($body['email'] ?? ''));
    $discoverable = filter_var($body['discoverable'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $userUUID = '';
    $credentialIds = [];

    if (!$discoverable) {
      if ('' === $email) {
        Response::error('Email is required.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $userUUID = UserRepository::getUUIDFromEmail($email);
      if ('' === $userUUID || !$this->isValidUserUUID($userUUID)) {
        \PayCal\Observability\Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
        Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $credentialIds = $this->credentialIdBinaries($userUUID);
      if ([] === $credentialIds) {
        \PayCal\Observability\Lens::add('[PASSKEY] User has no credentials', ['user_uuid' => $userUUID]);
        Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }
    }

    $webauthn = $this->createWebAuthn();
    $getArgs = $webauthn->getGetArgs($credentialIds, self::WEBAUTHN_TIMEOUT_SECONDS, true, true, true, true, true, 'required');
    $challenge = $this->encodeB64Url($webauthn->getChallenge()->getBinaryString());

    $challengeId = bin2hex(random_bytes(self::CHALLENGE_ID_BYTES));
    $challengeKey = $this->loginChallengeKey($challengeId);

    Database::hset($challengeKey, [
      'challenge' => $challenge,
      'user_uuid' => $userUUID,
      'discoverable' => $discoverable ? '1' : '0',
      'created_at' => (string) time(),
    ]);
    Database::expire($challengeKey, self::CHALLENGE_TTL_SECONDS);

    Response::success('[PASSKEY] Login challenge created.', [
      'challengeId' => $challengeId,
      'publicKey' => $getArgs->publicKey,
    ]);
  }

  /**
   * Finish a passkey authentication challenge.
   */
  #[Route('auth/passkey/login/finish', ['POST'])]
  /**
   * Handles loginFinish operation.
   */
  public function loginFinish(): void
  {
    if (!$this->enforceEndpointRateLimit('login:finish')) {
      return;
    }

    $body = $this->jsonBody();
    $challengeId = $this->scalarString($body['challengeId'] ?? '');
    if ('' === $challengeId) {
      Response::error('Authentication failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $challengeKey = $this->loginChallengeKey($challengeId);
    if (!Database::exists($challengeKey)) {
      Response::error('Authentication failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $challengeData = Database::hgetall($challengeKey);
    Database::unlink($challengeKey);

    $assertion = is_array($body['assertion'] ?? null) ? $body['assertion'] : $body;
    $credentialId = $this->extractCredentialId($assertion);
    $responseData = is_array($assertion['response'] ?? null) ? $assertion['response'] : [];

    $clientDataJSON = $this->decodeB64Url($this->scalarString($responseData['clientDataJSON'] ?? ''));
    $authenticatorData = $this->decodeB64Url($this->scalarString($responseData['authenticatorData'] ?? ''));
    $signature = $this->decodeB64Url($this->scalarString($responseData['signature'] ?? ''));
    $userHandle = $this->decodeB64Url($this->scalarString($responseData['userHandle'] ?? ''));

    if ($credentialId === '' || $clientDataJSON === null || $authenticatorData === null || $signature === null) {
      Response::error('Authentication failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $credentialKey = $this->credentialKey($credentialId);
    if (!Database::exists($credentialKey)) {
      \PayCal\Observability\Lens::add('[PASSKEY] Credential not found', [
        'credentialId' => $credentialId,
        'credentialKey' => $credentialKey,
      ]);
      Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $credentialData = Database::hgetall($credentialKey);
    $expectedUserUUID = $this->scalarString($challengeData['user_uuid'] ?? '');
    $credentialUserUUID = $this->scalarString($credentialData['user_uuid'] ?? '');

    if (!$this->isValidUserUUID($credentialUserUUID)) {
      SecurityLog::log('passkey_malformed_credential_rejected', [
        'credential_id' => $credentialId,
        'credential_user_uuid' => $credentialUserUUID,
      ]);
      Response::error('Authentication failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if ('' === $expectedUserUUID && $userHandle !== null && '' !== $userHandle) {
      $expectedUserUUID = $userHandle;
    }

    if (!$this->isValidUserUUID($expectedUserUUID)) {
      SecurityLog::log('passkey_malformed_challenge_rejected', [
        'credential_id' => $credentialId,
        'challenge_user_uuid' => $expectedUserUUID,
      ]);
      Response::error('Authentication failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    if ('' === $expectedUserUUID || $expectedUserUUID !== $credentialUserUUID) {
      \PayCal\Observability\Lens::add('[PASSKEY] Login UUID mismatch', [
        'expectedUserUUID' => $expectedUserUUID,
        'credentialUserUUID' => $credentialUserUUID,
        'credentialId' => $credentialId,
      ]);
      Response::error('Authentication failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $publicKeyPem = $this->scalarString($credentialData['public_key_pem'] ?? '');
    $challengeBinary = $this->decodeB64Url($this->scalarString($challengeData['challenge'] ?? ''));
    if ('' === $publicKeyPem || $challengeBinary === null) {
      Response::error('Authentication failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    try {
      $webauthn = $this->createWebAuthn();
      $webauthn->processGet(
        $clientDataJSON,
        $authenticatorData,
        $signature,
        $publicKeyPem,
        $challengeBinary,
        (int) $this->scalarString($credentialData['sign_count'] ?? '0'),
        true,
        true
      );

      $newSignCount = (int) ($webauthn->getSignatureCounter() ?? 0);
    } catch (WebAuthnException) {
      Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $oldSignCount = (int) $this->scalarString($credentialData['sign_count'] ?? '0');
    $suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount < $oldSignCount;
    if ($suspectedClone) {
      SecurityLog::log('passkey_clone_suspected', [
        'user_uuid' => $expectedUserUUID,
        'credential_id' => $credentialId,
        'old_sign_count' => $oldSignCount,
        'new_sign_count' => $newSignCount,
      ]);
    }

    $sessionHash = bin2hex(random_bytes(self::SECRET_BYTES));
    Authentication::setSession($sessionHash, $expectedUserUUID);
    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'auth_method' => 'passkey',
      'auth_strength' => 'strong',
      'credential_id' => $credentialId,
    ]);
    Authentication::setCookie($sessionHash);
    UserRepository::touchLastSignin($expectedUserUUID);

    $updateFields = [
      'last_used_at' => (string) time(),
    ];
    if ($newSignCount > 0) {
      $updateFields['sign_count'] = (string) $newSignCount;
    }
    Database::hset($credentialKey, $updateFields);

    Database::hset(Keys::USER . ':' . $expectedUserUUID, [
      'webauthn_enabled' => '1',
      'password_only_risk' => '0',
      'last_auth_method' => 'passkey',
    ]);

    SecurityLog::log('passkey_login_success', [
      'user_uuid' => $expectedUserUUID,
      'credential_id' => $credentialId,
    ]);

    // Log post-login encryption state for debugging
    $loginUser = UserRepository::getByUUID($expectedUserUUID);
    if ($loginUser) {
      \PayCal\Observability\Lens::add('[PASSKEY] Post-login encryption state', [
        'user_uuid' => $expectedUserUUID,
        'hasWrappedDek' => !empty($loginUser->wrapped_dek),
        'hasEncryptionSalt' => !empty($loginUser->encryption_salt),
        'dekVersion' => $loginUser->dek_version
      ]);
    }

    Response::success('[PASSKEY] Login successful.', [
      'user_uuid' => $expectedUserUUID,
      'auth_strength' => 'strong',
      'password_only_warning' => false,
      'mutation_allowed' => true,
      'credential_id' => $credentialId,
      'suspected_clone' => $suspectedClone,
    ]);
  }

  /**
   * List passkeys registered to the current user.
   */
  #[Route('auth/passkey/list', ['GET'])]
  /**
   * Handles list operation.
   */
  public function list(): void
  {
    if (!Authentication::validateAndTouchSession()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $userUUID = User::currentUUID();
    if ('' === $userUUID) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $records = [];
    foreach (Database::smembers($this->userCredentialsKey($userUUID)) as $credentialIdRaw) {
      $credentialId = $this->scalarString($credentialIdRaw);
      if ('' === $credentialId) {
        continue;
      }

      $data = Database::hgetall($this->credentialKey($credentialId));
      if (($data['user_uuid'] ?? '') !== $userUUID) {
        continue;
      }

      $records[] = [
        'credentialId' => $credentialId,
        'deviceName' => $this->scalarString($data['device_name'] ?? 'Passkey'),
        'createdAt' => (int) $this->scalarString($data['created_at'] ?? '0'),
        'lastUsedAt' => (int) $this->scalarString($data['last_used_at'] ?? '0'),
      ];
    }

    $staleThreshold = time() - (90 * 86400);
    $staleCount = 0;
    foreach ($records as $record) {
      if ($record['lastUsedAt'] > 0 && $record['lastUsedAt'] < $staleThreshold) {
        $staleCount++;
      }
    }

    Response::success('[PASSKEY] Passkey list.', [
      'credentials' => $records,
      'health' => [
        'total' => count($records),
        'staleCount' => $staleCount,
        'atRisk' => count($records) <= 1 || $staleCount >= count($records) - 1,
      ],
    ]);
  }

  /**
   * Rename a passkey owned by the current user.
   */
  #[Route('auth/passkey/update', ['POST'])]
  /**
   * Handles update operation.
   */
  public function update(): void
  {
    if (!Authentication::validateAndTouchSession()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $body = $this->jsonBody();
    $credentialId = $this->scalarString($body['credentialId'] ?? '');
    $newName = InputSanitizer::sanitizeString($this->scalarString($body['newName'] ?? ''));

    if ('' === $credentialId || '' === $newName) {
      Response::error('Update failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    if (mb_strlen($newName) > self::MAX_PASSKEY_NAME_LENGTH) {
      Response::error('Passkey name too long.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $userUUID = User::currentUUID();
    $credentialKey = $this->credentialKey($credentialId);
    if (!Database::exists($credentialKey)) {
      Response::error('Update failed.', [], HttpStatus::HTTP_NOT_FOUND);
      return;
    }

    $data = Database::hgetall($credentialKey);
    if (($data['user_uuid'] ?? '') !== $userUUID) {
      Response::error('Update failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    Database::hset($credentialKey, ['device_name' => $newName]);

    SecurityLog::log('passkey_renamed', [
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
      'new_name' => $newName,
    ]);

    Response::success('[PASSKEY] Passkey updated.', [
      'credentialId' => $credentialId,
      'deviceName' => $newName,
    ]);
  }

  /**
   * Delete a passkey owned by the current user.
   */
  #[Route('auth/passkey/delete', ['POST'])]
  /**
   * Handles delete operation.
   */
  public function delete(): void
  {
    if (!Authentication::validateAndTouchSession()) {
      Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    $body = $this->jsonBody();
    $credentialId = $this->scalarString($body['credentialId'] ?? '');
    if ('' === $credentialId) {
      Response::error('Delete failed.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    $userUUID = User::currentUUID();
    $credentialKey = $this->credentialKey($credentialId);
    if (!Database::exists($credentialKey)) {
      Response::error('Delete failed.', [], HttpStatus::HTTP_NOT_FOUND);
      return;
    }

    $data = Database::hgetall($credentialKey);
    if (($data['user_uuid'] ?? '') !== $userUUID) {
      Response::error('Delete failed.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    $existingCount = (int) (Database::scard($this->userCredentialsKey($userUUID)) ?? 0);
    if ($existingCount <= 1) {
      Response::error('At least one passkey is required on your account.', [], HttpStatus::HTTP_BAD_REQUEST);
      return;
    }

    Database::unlink($credentialKey);
    Database::srem($this->userCredentialsKey($userUUID), $credentialId);
    Database::hdel(Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks', $credentialId);

    $remaining = Database::scard($this->userCredentialsKey($userUUID)) ?? 0;
    Database::hset(Keys::USER . ':' . $userUUID, [
      'webauthn_enabled' => $remaining > 0 ? '1' : '0',
    ]);

    SecurityLog::log('passkey_deleted', [
      'user_uuid' => $userUUID,
      'credential_id' => $credentialId,
    ]);

    Response::success('[PASSKEY] Passkey deleted.', [
      'remaining' => $remaining,
    ]);
  }

  /**
   * Send a recovery email from the sign-in page when passkey login fails.
   */
  #[Route('auth/passkey/send-recovery-email', ['POST'])]
  public function sendRecoveryEmail(): void
  {
    if (!$this->enforceEndpointRateLimit('send-recovery-email')) {
      return;
    }

    $body = $this->jsonBody();
    $email = InputSanitizer::sanitizeEmail($this->scalarString($body['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Response::success('[PASSKEY] Recovery instructions sent if account exists.');
      return;
    }

    $userUUID = UserRepository::getUUIDFromEmail($email);
    if ($userUUID === '' || !$this->isValidUserUUID($userUUID)) {
      Response::success('[PASSKEY] Recovery instructions sent if account exists.');
      return;
    }

    try {
      $user = User::getByUUID($userUUID);
      if ($user !== null && $user->email_verified) {
        $code = Security::generateVerificationCode(6);
        // TODO: Integrate with AccountRecoveryTransaction for full recovery flow
        EmailGarum::sendAccountRecoveryCode($user->email, $user->full_name, $code);
      }
    } catch (\Throwable) {
      // Silently fail to prevent email enumeration
    }

    SecurityLog::log('passkey_recovery_email_requested', [
      'email_hash' => hash('sha256', $email),
    ]);

    Response::success('[PASSKEY] Recovery instructions sent if account exists.');
  }

  /**
   * Decode the JSON request body into an associative array.
   *
   * @return array<string, mixed>
   */
  private function jsonBody(): array
  {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || '' === trim($raw)) {
      return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Normalize scalar input into a string.
   *
   * @param mixed $value Raw input value
   *
   * @return string Normalized string value
   */
  private function scalarString(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * Build the configured WebAuthn client.
   *
   * @return WebAuthn Configured WebAuthn helper
   */
  private function createWebAuthn(): WebAuthn
  {
    return new WebAuthn('PayCal', $this->rpId(), ['none'], true);
  }

  /**
   * Resolve the relying-party identifier for WebAuthn ceremonies.
   *
   * @return string Relying-party identifier
   */
  private function rpId(): string
  {
    $host = parse_url(Environment::appPublicURL(), PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
      return 'localhost';
    }

    $trimmed = trim(strtolower($host));
    if (str_ends_with($trimmed, 'paycal.app')) {
      return 'paycal.app';
    }

    return $trimmed;
  }

  /**
   * Build the Redis key for registration challenges.
   *
   * @param string $challengeId Challenge identifier
   *
   * @return string Registration challenge key
   */
  private function registerChallengeKey(string $challengeId): string
  {
    return Keys::webauthnChallenge('register', $challengeId);
  }

  /**
   * Build the Redis key for login challenges.
   *
   * @param string $challengeId Challenge identifier
   *
   * @return string Login challenge key
   */
  private function loginChallengeKey(string $challengeId): string
  {
    return Keys::webauthnChallenge('login', $challengeId);
  }

  /**
   * Build the Redis key for signup challenges.
   *
   * @param string $challengeId Challenge identifier
   *
   * @return string Signup challenge key
   */
  private function signupChallengeKey(string $challengeId): string
  {
    return Keys::webauthnChallenge('signup', $challengeId);
  }

  /**
   * Build the Redis set key for a user's registered credentials.
   *
   * @param string $userUUID User UUID
   *
   * @return string Credential set key
   */
  private function userCredentialsKey(string $userUUID): string
  {
    return Keys::webauthnUserCredentials($userUUID);
  }

  /**
   * Build the Redis hash key for a credential record.
   *
   * @param string $credentialId Credential identifier
   *
   * @return string Credential hash key
   */
  private function credentialKey(string $credentialId): string
  {
    return Keys::webauthnCredential($credentialId);
  }

  /**
   * Encode binary data as URL-safe base64.
   *
   * @param string $binary Raw binary payload
   *
   * @return string URL-safe base64 string without padding
   */
  private function encodeB64Url(string $binary): string
  {
    return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
  }

  /**
   * Decode URL-safe base64 data.
   *
   * @param string $value URL-safe base64 value
   *
   * @return null|string Decoded binary string when valid
   */
  private function decodeB64Url(string $value): ?string
  {
    if ('' === $value) {
      return null;
    }

    $padded = strtr($value, '-_', '+/');
    $mod = strlen($padded) % 4;
    if ($mod > 0) {
      $padded .= str_repeat('=', 4 - $mod);
    }

    $decoded = base64_decode($padded, true);
    return false === $decoded ? null : $decoded;
  }

  /**
   * Return the binary credential identifiers for a user.
   *
   * @param string $userUUID User UUID
   *
   * @return array<int, string> Binary credential identifiers
   */
  private function credentialIdBinaries(string $userUUID): array
  {
    $binaries = [];

    if (!$this->isValidUserUUID($userUUID)) {
      return [];
    }

    foreach (Database::smembers($this->userCredentialsKey($userUUID)) as $credentialIdRaw) {
      $credentialId = $this->scalarString($credentialIdRaw);
      if ('' === $credentialId) {
        continue;
      }

      $record = Database::hgetall($this->credentialKey($credentialId));
      $recordUserUUID = $this->scalarString($record['user_uuid'] ?? '');
      if (!$this->isValidUserUUID($recordUserUUID) || $recordUserUUID !== $userUUID) {
        continue;
      }

      $decoded = $this->decodeB64Url($credentialId);
      if ($decoded === null) {
        continue;
      }

      $binaries[] = $decoded;
    }

    return $binaries;
  }

  /**
   * Validate that the user UUID refers to a first-party user record.
   *
   * @param string $userUUID User UUID candidate
   *
   * @return bool True when the UUID is safe and exists
   */
  private function isValidUserUUID(string $userUUID): bool
  {
    if ($userUUID === '' || str_contains($userUUID, ':')) {
      return false;
    }

    return Database::exists(Keys::USER . ':' . $userUUID);
  }

  /**
   * Encode an array of scalar values as JSON.
   *
   * @param mixed $value Candidate array value
   *
   * @return string JSON array string
   */
  private function jsonEncodeArray(mixed $value): string
  {
    if (!is_array($value)) {
      return '[]';
    }

    $normalized = [];
    foreach ($value as $item) {
      if (is_scalar($item)) {
        $normalized[] = (string) $item;
      }
    }

    return (string) json_encode($normalized);
  }

  /**
   * Enforce a simple per-minute IP rate limit for passkey endpoints.
   *
   * @param string $endpoint Endpoint discriminator
   *
   * @return bool True when the request may proceed
   */
  private function enforceEndpointRateLimit(string $endpoint): bool
  {
    $ip = Security::getClientIPAddress();
    $clientIP = $ip !== '' ? $ip : '0.0.0.0';
    $currentMinute = (int) floor(time() / FormTTL::ONE_MIN->value);
    $key = 'ratelimit:passkey:' . $endpoint . ':' . md5($clientIP) . ':' . $currentMinute;
    $count = Database::incr($key);
    if (1 === $count) {
      Database::expire($key, self::RATE_WINDOW_SECONDS);
    }

    if ($count > self::LIMIT_PER_MINUTE_IP) {
      SecurityLog::logRateLimitTriggered('ip:passkey', $clientIP . ':' . $endpoint, max(0, self::LIMIT_PER_MINUTE_IP - $count));
      Response::error('Too many attempts. Please retry shortly.', [], HttpStatus::HTTP_TOO_MANY_REQUESTS);
      return false;
    }

    return true;
  }

  /**
    * Extract and normalize a credential identifier from the request payload.
    *
   * @param array<string, mixed> $credential
    *
    * @return string Normalized credential identifier
   */
  private function extractCredentialId(array $credential): string
  {
    $rawId = $this->scalarString($credential['rawId'] ?? '');
    if ('' !== $rawId) {
      $decoded = $this->decodeB64Url($rawId);
      if ($decoded !== null) {
        return $this->encodeB64Url($decoded);
      }
    }

    $id = $this->scalarString($credential['id'] ?? '');
    if ('' !== $id) {
      $decoded = $this->decodeB64Url($id);
      if ($decoded !== null) {
        return $this->encodeB64Url($decoded);
      }
    }

    return '';
  }

  /**
    * Send an email verification message when the passkey user remains unverified.
   *
    * @param string $userUUID User UUID
    *
   * @return array{attempted: bool, sent: bool, reason: string}
   */
  private function sendVerificationEmailIfNeeded(string $userUUID): array
  {
    Lens::add('Passkey verification send check started', [
      'user_uuid' => $userUUID,
    ], 'passkey_verification');

    $user = User::getByUUID($userUUID);
    if ($user === null) {
      Lens::add('Passkey verification send skipped: user not found', [
        'user_uuid' => $userUUID,
      ], 'passkey_verification');
      return ['attempted' => false, 'sent' => false, 'reason' => 'user_not_found'];
    }

    // Skip if already verified
    if ($user->email_verified) {
      Lens::add('Passkey verification send skipped: already verified', [
        'user_uuid' => $userUUID,
      ], 'passkey_verification');
      return ['attempted' => false, 'sent' => false, 'reason' => 'already_verified'];
    }

    // Skip if verification email already sent recently (check for existing token)
    if (!empty($user->email_verify_token_hash) && !empty($user->email_verify_expiry)) {
      $expiry = (int) $user->email_verify_expiry;
      // Only resend if token expired
      if (time() < $expiry) {
        Lens::add('Passkey verification send skipped: token still valid', [
          'user_uuid' => $userUUID,
          'expiry' => $expiry,
        ], 'passkey_verification');
        return ['attempted' => false, 'sent' => false, 'reason' => 'token_still_valid'];
      }
    }

    // Generate verification token
    $token = bin2hex(random_bytes(self::SECRET_BYTES));
    $tokenHash = hash('sha256', $token);
    $expiry = time() + FormTTL::ONE_DAY->value;

    // Store token hash
    Database::hset(Keys::USER . ':' . $userUUID, [
      'email_verified' => '0',
      'email_verify_token_hash' => $tokenHash,
      'email_verify_expiry' => (string) $expiry,
    ]);

    // Keep a token-hash index with a strict 24-hour TTL for fast verification lookup.
    Database::set(Keys::EMAIL_VERIFICATION . ':' . $tokenHash, $userUUID, FormTTL::ONE_DAY->value);
    Lens::add('Passkey verification token persisted', [
      'user_uuid' => $userUUID,
      'expiry' => $expiry,
    ], 'passkey_verification');

    // Generate one-time verification code (valid for 1 hour) for manual entry.
    $verificationCode = Security::generateVerificationCode(6);
    User::addVerificationCode($verificationCode, $userUUID);
    Lens::add('Passkey verification code generated', [
      'user_uuid' => $userUUID,
    ], 'passkey_verification');

    // Send verification email
    $sent = EmailGarum::sendVerificationEmail($token, $user->email, $user->full_name, $verificationCode);
    Lens::add('Passkey verification email send attempted', [
      'user_uuid' => $userUUID,
      'sent' => $sent,
    ], 'passkey_verification');

    if ($sent) {
      Lens::add('Passkey verification email send succeeded', [
        'user_uuid' => $userUUID,
      ], 'passkey_verification');
      SecurityLog::log('verification_email_sent', [
        'user_uuid' => $userUUID,
        'email' => $user->email,
      ]);
      return ['attempted' => true, 'sent' => true, 'reason' => 'sent'];
    }

    Lens::add('Passkey verification email send failed', [
      'user_uuid' => $userUUID,
    ], 'passkey_verification');
    SecurityLog::log('verification_email_failed', [
      'user_uuid' => $userUUID,
      'email' => $user->email,
    ]);

    return ['attempted' => true, 'sent' => false, 'reason' => 'send_failed'];
  }
}


