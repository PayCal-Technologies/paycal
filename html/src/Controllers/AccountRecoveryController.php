<?php declare(strict_types=1);

namespace PayCal\Controllers;

use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use PayCal\Infrastructure\RateControl\AccountRecoveryAbuseGuard;
use PayCal\Infrastructure\Transaction\AccountRecoveryTransaction;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\Encryption\EnvelopeFormat;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\RecoveryKey;
use PayCal\Infrastructure\RateControl\RateLimiter;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Domain\User;
use PayCal\Domain\UserFields;
use PayCal\Domain\UserRepository;

/**
 * AccountRecoveryController.php
 *
 * Purpose: Multi-step account recovery flow: email verification, recovery key
 * proof, passkey re-registration, and session bootstrapping after a lost credential.
 *
 * Developer notes:
 * - Recovery is intentionally stateful and adversarial. Keep anti-abuse,
 *   ambiguity, and challenge-expiry behavior centralized and explicit.
 * - Any change here should be reviewed with recovery-key proof semantics,
 *   passkey bootstrap rules, and transaction persistence together.
 * - Do not simplify ambiguity responses in ways that reintroduce account
 *   enumeration or credential-state leakage.
 * - This controller coordinates security workflows; helper extraction is fine,
 *   but alternate bypass paths are not.
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
 * Account recovery API surface.
 *
 * Responsibilities:
 * - Start and advance the recovery transaction lifecycle.
 * - Enforce proof, challenge, and abuse-guard checks between steps.
 * - Bootstrap replacement credentials only after recovery proof succeeds.
 */
final class AccountRecoveryController
{
  private const CHALLENGE_TTL_SECONDS = 300;
  private const WEBAUTHN_TIMEOUT_SECONDS = 60;
  private const CHALLENGE_ID_BYTES = 16;
  private const SECRET_BYTES = 32;

  /**
   * POST auth/recovery/start
   *
   * Initiates an account recovery transaction. Sends a verification code to the
   * registered email address if the account exists.  Response is deliberately
   * ambiguous to prevent account-enumeration.
   */
  #[Route('auth/recovery/start', ['POST'])]
  /**
   * Handles start operation.
   */
  public function start(): void
  {
    if (!$this->featureEnabled()) {
      $this->fail('Recovery is unavailable.', HttpStatus::HTTP_SERVICE_UNAVAILABLE);
    }

    $this->enforceRecoveryRateLimit('start');

    $guard = new AccountRecoveryAbuseGuard();
    if ($guard->isBlocked(Security::getClientIPAddress())) {
      $this->fail('Recovery is temporarily unavailable.', HttpStatus::HTTP_TOO_MANY_REQUESTS);
    }

    $body = $this->jsonBody();
    $email = InputSanitizer::sanitizeEmail($this->scalarString($body['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $this->fail('Valid email is required.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $userUuid = UserRepository::getUUIDFromEmail($email);
    $user = $userUuid !== '' ? User::getByUUID($userUuid) : null;
    $created = AccountRecoveryTransaction::create(
      $email,
      $user !== null ? $user->user_uuid : '',
      $user !== null ? $user->full_name : ''
    );
    /** @var AccountRecoveryTransaction $transaction */
    $transaction = $created['transaction'];

    if ($created['superseded']) {
      $guard->recordSupersedeEvent();
    }

    if ($user !== null && $user->email_verified) {
      $code = Security::generateVerificationCode(6);
      $transaction->storeEmailCode($code);
      EmailGarum::sendAccountRecoveryCode($user->email, $user->full_name, $code);
    }

    // Equalize response time to prevent account enumeration
    $this->equalizeResponseTime(100, 500);

    Response::success('If the account exists, recovery instructions have been sent.', [
      'txnId' => $transaction->id(),
      'txnSecret' => $created['txnSecret'],
      'cooldownSeconds' => (int) SystemConfig::get('account_recovery_resend_cooldown_seconds'),
    ]);
  }

  /**
   * POST auth/recovery/magic-link/consume
   *
   * Consumes a one-time recovery magic link token and advances the recovery
   * transaction to email-verified state without requiring manual code entry.
   */
  #[Route('auth/recovery/magic-link/consume', ['POST'])]
  /**
   * Handles consumeMagicLink operation.
   */
  public function consumeMagicLink(): void
  {
    if (!$this->featureEnabled()) {
      $this->fail('Recovery is unavailable.', HttpStatus::HTTP_SERVICE_UNAVAILABLE);
    }

    $body = $this->jsonBody();
    $token = InputSanitizer::sanitizeString($this->scalarString($body['token'] ?? ''));
    if ($token === '') {
      $this->fail('Recovery link is invalid or expired.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $this->enforceRecoveryRateLimit('magic-link-consume', $token);

    $linkKey = Keys::accountRecoveryMagicLink($token);
    $linkData = Database::hgetall($linkKey);
    Database::unlink($linkKey);
    if ($linkData === []) {
      $this->fail('Recovery link is invalid or expired.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $txnId = $this->scalarString($linkData['txn_id'] ?? '');
    $txnSecret = $this->scalarString($linkData['txn_secret'] ?? '');
    $transaction = AccountRecoveryTransaction::load($txnId);
    if ($transaction === null || !$transaction->verifySecret($txnSecret) || $transaction->isExpired()) {
      $this->fail('Recovery link is invalid or expired.', HttpStatus::HTTP_BAD_REQUEST);
    }

    if ($transaction->status() === AccountRecoveryTransaction::STATUS_PENDING) {
      if (!$transaction->markEmailVerifiedByMagicLink($this->clientFingerprintHash(), $this->clientIpClass())) {
        $this->fail('Recovery link is invalid or expired.', HttpStatus::HTTP_BAD_REQUEST);
      }
    } elseif (!in_array($transaction->status(), [
      AccountRecoveryTransaction::STATUS_EMAIL_VERIFIED,
      AccountRecoveryTransaction::STATUS_PROOF_VERIFIED,
      AccountRecoveryTransaction::STATUS_BOOTSTRAP_ISSUED,
    ], true)) {
      $this->fail('Recovery link is invalid or expired.', HttpStatus::HTTP_BAD_REQUEST);
    }

    if (!$transaction->elevateForMagicLinkPasskey($this->clientFingerprintHash(), $this->clientIpClass())) {
      $this->fail('Recovery link is invalid or expired.', HttpStatus::HTTP_BAD_REQUEST);
    }

    Response::success('Recovery link confirmed.', [
      'txnId' => $transaction->id(),
      'txnSecret' => $txnSecret,
      'status' => $transaction->status(),
      'passkeyReady' => true,
    ]);
  }

  /**
   * POST auth/recovery/resend
   *
   * Resends the recovery verification code for an existing, non-expired transaction.
   * Subject to a per-transaction cooldown enforced by SystemConfig.
   */
  #[Route('auth/recovery/resend', ['POST'])]
  /**
   * Handles resend operation.
   */
  public function resend(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('resend', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    if ($transaction === null || !$transaction->canResend()) {
      $this->fail('Recovery request invalid.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $user = $transaction->userUuid() !== '' ? User::getByUUID($transaction->userUuid()) : null;
    if ($user !== null) {
      $code = Security::generateVerificationCode(6);
      $transaction->storeEmailCode($code);
      EmailGarum::sendAccountRecoveryCode($user->email, $user->full_name, $code);
    }

    // Equalize response time to prevent account enumeration
    $this->equalizeResponseTime(100, 500);

    Response::success('If the account exists, recovery instructions have been re-sent.', [
      'cooldownSeconds' => (int) SystemConfig::get('account_recovery_resend_cooldown_seconds'),
    ]);
  }

  /**
   * POST auth/recovery/verify-email
   *
   * Validates the one-time email code supplied by the user.  Marks the transaction
   * as email-verified so the proof step becomes available.
   */
  #[Route('auth/recovery/verify-email', ['POST'])]
  /**
   * Handles verifyEmail operation.
   */
  public function verifyEmail(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('verify-email', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    $code = strtoupper(InputSanitizer::sanitizeString($this->scalarString($body['code'] ?? '')));
    if ($transaction === null || $code === '') {
      $this->fail('Recovery verification failed.', HttpStatus::HTTP_UNAUTHORIZED);
    }

    if (!$transaction->verifyEmailCode($code, $this->clientFingerprintHash(), $this->clientIpClass())) {
      $this->fail('Recovery verification failed.', HttpStatus::HTTP_UNAUTHORIZED);
    }

    Response::success('Recovery email verified.', ['txnId' => $transaction->id()]);
  }

  /**
   * POST auth/recovery/proof-payload
   *
   * Returns the encrypted DEK and a short-lived proof nonce so the client can
   * construct and sign the recovery-key proof offline.
   */
  #[Route('auth/recovery/proof-payload', ['POST'])]
  /**
   * Handles proofPayload operation.
   */
  public function proofPayload(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('proof-payload', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    if ($transaction === null) {
      $this->fail('Recovery request invalid.', HttpStatus::HTTP_UNAUTHORIZED);
    }

    $user = $transaction->userUuid() !== '' ? User::getByUUID($transaction->userUuid()) : null;
    if ($user === null || $user->wrapped_dek_recovery === null || $user->account_recovery_salt === null || $user->recovery_proof_key === null) {
      $this->fail('Recovery is not available for this account yet.', HttpStatus::HTTP_UNPROCESSABLE);
    }

    $challenge = $transaction->issueProofNonce($this->clientFingerprintHash(), $this->clientIpClass());
    if ($challenge === null) {
      $this->fail('Recovery request invalid.', HttpStatus::HTTP_BAD_REQUEST);
    }

    Response::success('Recovery proof payload ready.', [
      'wrappedDekRecovery' => $user->wrapped_dek_recovery,
      'accountRecoverySalt' => $user->account_recovery_salt,
      'proofNonce' => $challenge['proofNonce'],
      'proofNonceExpiresAt' => $challenge['expiresAt'],
      'proofLabel' => RecoveryKey::PROOF_LABEL,
      'proofVersion' => $user->recovery_proof_key_version > 0 ? $user->recovery_proof_key_version : 1,
      'dekVersion' => $user->dek_version,
      'cryptoVersion' => $user->crypto_version > 0 ? $user->crypto_version : 1,
      'clientFingerprintHash' => $this->clientFingerprintHash(),
    ]);
  }

  /**
   * POST auth/recovery/prove-key
   *
   * Verifies the client-submitted recovery-key proof and—on success—marks all
   * existing user sessions as recovery-pending so a fresh passkey can be created.
   */
  #[Route('auth/recovery/prove-key', ['POST'])]
  /**
   * Handles proveKey operation.
   */
  public function proveKey(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('prove-key', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    $proof = $this->scalarString($body['proof'] ?? '');
    $proofNonce = $this->scalarString($body['proofNonce'] ?? '');
    $guard = new AccountRecoveryAbuseGuard();
    if ($transaction === null || $proof === '' || $proofNonce === '') {
      $guard->recordReplayEvent(Security::getClientIPAddress(), 'proof');
      $this->fail('Recovery proof failed.', HttpStatus::HTTP_UNAUTHORIZED);
    }

    $user = $transaction->userUuid() !== '' ? User::getByUUID($transaction->userUuid()) : null;
    if ($user === null || $user->recovery_proof_key === null) {
      $guard->recordReplayEvent(Security::getClientIPAddress(), 'proof');
      $this->fail('Recovery proof failed.', HttpStatus::HTTP_UNAUTHORIZED);
    }

    $expectedProof = RecoveryKey::generateProof($user->recovery_proof_key, $transaction->id(), $proofNonce, $this->clientFingerprintHash());
    if (!$transaction->verifyProof($proof, $proofNonce, $expectedProof, $this->clientFingerprintHash(), $this->clientIpClass())) {
      $guard->recordReplayEvent(Security::getClientIPAddress(), 'proof');
      $this->fail('Recovery proof failed.', HttpStatus::HTTP_UNAUTHORIZED);
    }

    Authentication::markUserSessionsRecoveryPending($transaction->userUuid(), $transaction->id());
    Response::success('Recovery key verified.', ['txnId' => $transaction->id()]);
  }

  /**
   * POST auth/recovery/bootstrap
   *
   * Issues a single-use bootstrap token once the proof is verified.  Returns the
   * user's encryption metadata needed for re-wrapping the DEK with a new passkey.
   */
  #[Route('auth/recovery/bootstrap', ['POST'])]
  /**
   * Handles bootstrap operation.
   */
  public function bootstrap(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('bootstrap', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    if ($transaction === null || !$transaction->issueBootstrap()) {
      $this->fail('Recovery bootstrap unavailable.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $user = User::getByUUID($transaction->userUuid());
    if ($user === null) {
      $this->fail('Recovery bootstrap unavailable.', HttpStatus::HTTP_BAD_REQUEST);
    }

    Response::success('Recovery bootstrap ready.', [
      'userId' => $user->user_uuid,
      'email' => $user->email,
      'fullName' => $user->full_name,
      'encryptionSalt' => $user->encryption_salt,
      'dekVersion' => $user->dek_version,
      'cryptoVersion' => $user->crypto_version > 0 ? $user->crypto_version : 1,
      'bootstrapTtlSeconds' => (int) SystemConfig::get('account_recovery_bootstrap_ttl_seconds'),
    ]);
  }

  /**
   * POST auth/recovery/register-passkey/start
   *
   * Begins the WebAuthn registration ceremony for the replacement passkey during
   * recovery.  Existing credential IDs are intentionally not excluded to avoid
   * browser InvalidStateError during the recovery flow.
   */
  #[Route('auth/recovery/register-passkey/start', ['POST'])]
  /**
   * Handles registerPasskeyStart operation.
   */
  public function registerPasskeyStart(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('register-passkey-start', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    if ($transaction === null || !$transaction->bootstrapIsUsable($this->clientFingerprintHash(), $this->clientIpClass())) {
      $this->fail('Recovery bootstrap unavailable.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $user = User::getByUUID($transaction->userUuid());
    if ($user === null) {
      $this->fail('Recovery bootstrap unavailable.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $deviceName = InputSanitizer::sanitizeString($this->scalarString($body['deviceName'] ?? 'Recovered Passkey'));
    $webauthn = $this->createWebAuthn();
    // Recovery can intentionally register on an authenticator that may already hold this RP's credential.
    // Excluding existing IDs here can trigger browser InvalidStateError and stall recovery UX.
    $createArgs = $webauthn->getCreateArgs($user->user_uuid, $user->email, $user->full_name !== '' ? $user->full_name : $user->email, self::WEBAUTHN_TIMEOUT_SECONDS, 'required', 'required', null, []);

    $challengeId = bin2hex(random_bytes(self::CHALLENGE_ID_BYTES));
    $challenge = $this->encodeB64Url($webauthn->getChallenge()->getBinaryString());
    Database::hsetex(Keys::webauthnChallenge('recovery-register', $challengeId), [
      'challenge' => $challenge,
      'txn_id' => $transaction->id(),
      'user_uuid' => $user->user_uuid,
      'device_name' => $deviceName !== '' ? $deviceName : 'Recovered Passkey',
    ], self::CHALLENGE_TTL_SECONDS);

    Response::success('Recovery passkey challenge created.', [
      'challengeId' => $challengeId,
      'publicKey' => $createArgs->publicKey,
    ]);
  }

  /**
   * POST auth/recovery/register-passkey/finish
   *
   * Completes the WebAuthn registration ceremony, persists the new credential, and
   * marks the replacement passkey as registered on the recovery transaction.
   */
  #[Route('auth/recovery/register-passkey/finish', ['POST'])]
  /**
   * Handles registerPasskeyFinish operation.
   */
  public function registerPasskeyFinish(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('register-passkey-finish', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    $challengeId = $this->scalarString($body['challengeId'] ?? '');
    if ($transaction === null || $challengeId === '' || !$transaction->bootstrapIsUsable($this->clientFingerprintHash(), $this->clientIpClass())) {
      $this->fail('Recovery registration failed.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $challengeKey = Keys::webauthnChallenge('recovery-register', $challengeId);
    $challengeData = Database::hgetall($challengeKey);
    Database::unlink($challengeKey);
    if (($challengeData['txn_id'] ?? '') !== $transaction->id()) {
      $this->fail('Recovery registration failed.', HttpStatus::HTTP_FORBIDDEN);
    }

    $credential = is_array($body['credential'] ?? null) ? $body['credential'] : $body;
    $responseData = is_array($credential['response'] ?? null) ? $credential['response'] : [];
    $clientDataJSON = $this->decodeB64Url($this->scalarString($responseData['clientDataJSON'] ?? ''));
    $attestationObject = $this->decodeB64Url($this->scalarString($responseData['attestationObject'] ?? ''));
    $challengeBinary = $this->decodeB64Url($this->scalarString($challengeData['challenge'] ?? ''));
    if ($clientDataJSON === null || $attestationObject === null || $challengeBinary === null) {
      $this->fail('Recovery registration failed.', HttpStatus::HTTP_BAD_REQUEST);
    }

    try {
      $webauthn = $this->createWebAuthn();
      // UV must be enforced: the challenge was issued with userVerification='required'.
      // Passing false here would let a modified client clear the UV flag and bypass biometrics.
      $result = $webauthn->processCreate($clientDataJSON, $attestationObject, $challengeBinary, true, true);
      $credentialId = $this->encodeB64Url($result->credentialId);
      $publicKeyPem = (string) $result->credentialPublicKey;
      $signCount = (int) ($result->signatureCounter ?? 0);
    } catch (WebAuthnException) {
      $this->fail('Recovery registration failed.', HttpStatus::HTTP_FORBIDDEN);
    }

    $transportsJson = json_encode($responseData['transports'] ?? []);
    if (!is_string($transportsJson)) {
      $transportsJson = '[]';
    }

    Database::hset(Keys::webauthnCredential($credentialId), [
      'credential_id' => $credentialId,
      'user_uuid' => $transaction->userUuid(),
      'public_key_pem' => $publicKeyPem,
      'sign_count' => (string) $signCount,
      'transports' => $transportsJson,
      'device_name' => $this->scalarString($challengeData['device_name'] ?? 'Recovered Passkey'),
      'created_at' => (string) time(),
      'last_used_at' => '',
    ]);
    Database::sadd(Keys::webauthnUserCredentials($transaction->userUuid()), $credentialId);
    Database::hset(Keys::USER . ':' . $transaction->userUuid(), ['webauthn_enabled' => '1']);
    $transaction->markReplacementPasskeyRegistered($credentialId);

    Response::success('Recovery passkey registered.', ['credentialId' => $credentialId]);
  }

  /**
   * POST auth/recovery/complete
   *
   * Finalises account recovery: persists the passkey-wrapped DEK, destroys all old
   * sessions, creates a new authenticated session, and marks the transaction complete.
   */
  #[Route('auth/recovery/complete', ['POST'])]
  /**
   * Handles complete operation.
   */
  public function complete(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('complete', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    if ($transaction === null || !$transaction->bootstrapIsUsable($this->clientFingerprintHash(), $this->clientIpClass())) {
      $this->fail('Recovery completion failed.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $credentialId = $this->scalarString($body['credentialId'] ?? $transaction->replacementCredentialId());
    $wrappedDekPasskey = $this->scalarString($body['wrappedDekPasskey'] ?? '');
    if ($credentialId === '' || $wrappedDekPasskey === '') {
      $this->fail('Recovery completion failed.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $decodedEnvelopeJson = base64_decode($wrappedDekPasskey, true);
    if (!is_string($decodedEnvelopeJson) || $decodedEnvelopeJson === '') {
      $this->fail('Recovery completion failed.', HttpStatus::HTTP_BAD_REQUEST);
    }

    try {
      EnvelopeFormat::fromJson($decodedEnvelopeJson);
    } catch (\Throwable) {
      $this->fail('Recovery completion failed.', HttpStatus::HTTP_BAD_REQUEST);
    }

    Database::hset(Keys::USER . ':' . $transaction->userUuid() . ':passkey_wrapped_deks', [$credentialId => $wrappedDekPasskey]);
    Database::hset(Keys::USER . ':' . $transaction->userUuid(), [
      UserFields::DEK_VERSION->value => (string) $this->scalarInt($body['dekVersion'] ?? 1, 1),
      UserFields::CRYPTO_VERSION->value => (string) $this->scalarInt($body['cryptoVersion'] ?? 1, 1),
      UserFields::WRAPPED_DEK_PASSKEY->value => '',
      'last_auth_method' => 'passkey',
      'password_only_risk' => '0',
    ]);

    Authentication::clearUserRecoveryPending($transaction->userUuid(), $transaction->id());
    Authentication::destroyAllUserSessions($transaction->userUuid());

    $sessionHash = bin2hex(random_bytes(self::SECRET_BYTES));
    Authentication::setSession($sessionHash, $transaction->userUuid());
    Database::hset(Keys::SESSION . ':' . $sessionHash, [
      'auth_method' => 'passkey',
      'auth_strength' => 'strong',
      'credential_id' => $credentialId,
      'recovery_pending' => '0',
      'recovery_txn_id' => '',
    ]);
    Authentication::setCookie($sessionHash);
    UserRepository::touchLastSignin($transaction->userUuid());
    $transaction->complete($credentialId);

    Response::success('Account recovery complete.', ['redirectUrl' => '/']);
  }

  /**
   * POST auth/recovery/cancel
   *
   * Cancels an in-progress recovery transaction and clears any recovery-pending
   * flags from the associated user sessions.
   */
  #[Route('auth/recovery/cancel', ['POST'])]
  /**
   * Handles cancel operation.
   */
  public function cancel(): void
  {
    $body = $this->jsonBody();
    $this->enforceRecoveryRateLimit('cancel', $this->extractTxnId($body));

    $transaction = $this->loadAuthorizedTransaction($body);
    if ($transaction === null) {
      Response::success('Recovery cancelled.');
      return;
    }

    $transaction->cancel();
    Authentication::clearUserRecoveryPending($transaction->userUuid(), $transaction->id());
    Response::success('Recovery cancelled.');
  }

  /**
   * POST user/account/recovery-material
   *
   * Stores recovery material (recovery-wrapped DEK, proof key, salt) for an
   * authenticated user who completed encryption setup after account creation.
   * Requires an active authenticated session.
   */
  #[Route('user/account/recovery-material', ['POST'])]
  /**
   * Handles backfillRecoveryMaterial operation.
   */
  public function backfillRecoveryMaterial(): void
  {
    Authentication::abortIfUnauthenticated();
    $body = $this->jsonBody();
    $wrappedDekRecovery = $this->scalarString($body['wrappedDekRecovery'] ?? '');
    $recoveryProofKey = $this->scalarString($body['recoveryProofKey'] ?? '');
    $accountRecoverySalt = $this->scalarString($body['accountRecoverySalt'] ?? '');
    if ($wrappedDekRecovery === '' || $recoveryProofKey === '' || $accountRecoverySalt === '') {
      $this->fail('Recovery material payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $decodedEnvelopeJson = base64_decode($wrappedDekRecovery, true);
    if (!is_string($decodedEnvelopeJson) || $decodedEnvelopeJson === '' || base64_decode($recoveryProofKey, true) === false || base64_decode($accountRecoverySalt, true) === false) {
      $this->fail('Recovery material payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
    }

    try {
      EnvelopeFormat::fromJson($decodedEnvelopeJson);
    } catch (\Throwable) {
      $this->fail('Recovery material payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
    }

    $user = User::current();
    Database::hset(Keys::USER . ':' . $user->user_uuid, [
      UserFields::ACCOUNT_RECOVERY_SALT->value => $accountRecoverySalt,
      UserFields::WRAPPED_DEK_RECOVERY->value => $wrappedDekRecovery,
      UserFields::RECOVERY_KEY_GENERATED->value => '1',
      UserFields::RECOVERY_PROOF_KEY->value => $recoveryProofKey,
      UserFields::RECOVERY_PROOF_KEY_VERSION->value => '1',
    ]);

    Response::success('Recovery material stored.');
  }

  /**
   * Equalize response time to prevent account enumeration attacks.
   * Adds artificial delay to ensure consistent response times regardless
   * of whether an account exists or other timing-based variations.
   *
   * @param int $minMilliseconds Minimum response time in milliseconds
   * @param int $maxMilliseconds Maximum response time in milliseconds
   */
  private function equalizeResponseTime(int $minMilliseconds = 100, int $maxMilliseconds = 500): void
  {
    $elapsed = (intdiv((int) (microtime(true) * 1000), 1)); // Current time in MS
    $targetDelay = random_int($minMilliseconds, $maxMilliseconds);
    $remainingDelay = max(0, $targetDelay - $elapsed % 1000);
    
    if ($remainingDelay > 0) {
      usleep($remainingDelay * 1000);
    }
  }

  /**
   * Handles featureEnabled operation.
   */
  private function featureEnabled(): bool
  {
    return filter_var(SystemConfig::get('account_recovery_enabled'), FILTER_VALIDATE_BOOLEAN);
  }

  /** @param array<string, mixed>|null $body */
  private function loadAuthorizedTransaction(?array $body = null): ?AccountRecoveryTransaction
  {
    $payload = $body ?? $this->jsonBody();
    $txnId = $this->scalarString($payload['txnId'] ?? $payload['txn_id'] ?? '');
    $txnSecret = $this->scalarString($payload['txnSecret'] ?? $payload['txn_secret'] ?? '');
    $transaction = AccountRecoveryTransaction::load($txnId);
    if ($transaction === null || !$transaction->verifySecret($txnSecret)) {
      return null;
    }

    return $transaction;
  }

  /** @return array<string, mixed> */
  private function jsonBody(): array
  {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
      return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Handles scalarString operation.
   */
  private function scalarString(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  /**
   * Handles scalarInt operation.
   */
  private function scalarInt(mixed $value, int $default = 0): int
  {
    if (is_int($value)) {
      return $value;
    }

    if (is_string($value) && $value !== '' && is_numeric($value)) {
      return (int) $value;
    }

    return $default;
  }

  /**
   * Handles fail operation.
   */
  private function fail(string $message, int $status): never
  {
    Response::error($message, [], $status);
    throw new \RuntimeException($message);
  }

  /** @param array<string, mixed>|null $body */
  private function extractTxnId(?array $body = null): string
  {
    $payload = $body ?? $this->jsonBody();
    return $this->scalarString($payload['txnId'] ?? $payload['txn_id'] ?? '');
  }

  /**
   * Handles enforceRecoveryRateLimit operation.
   */
  private function enforceRecoveryRateLimit(string $route, string $txnId = ''): void
  {
    $rate = RateLimiter::checkRecoveryEndpointLimit($route, Security::getClientIPAddress(), $txnId);
    if ($rate['allowed']) {
      return;
    }

    Response::error(
      'Recovery rate limit exceeded.',
      [
        'route' => $route,
        'remaining_requests' => $rate['remaining'],
        'quota' => $rate['limit'],
        'window_seconds' => $rate['window_seconds'],
        'reset_at' => $rate['reset_at'],
      ],
      HttpStatus::HTTP_TOO_MANY_REQUESTS
    );

    throw new \RuntimeException('Recovery rate limit exceeded for route: ' . $route);
  }

  /**
   * Handles clientFingerprintHash operation.
   */
  private function clientFingerprintHash(): string
  {
    return hash('sha256', $this->scalarString($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . $this->scalarString($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
  }

  /**
   * Handles clientIpClass operation.
   */
  private function clientIpClass(): string
  {
    $ip = Security::getClientIPAddress();
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $parts = explode('.', $ip);
      return sprintf('%s.%s.%s.0/24', $parts[0], $parts[1], $parts[2]);
    }
    return 'unknown';
  }

  /**
   * Handles createWebAuthn operation.
   */
  private function createWebAuthn(): WebAuthn
  {
    return new WebAuthn('PayCal', $this->rpId(), ['none'], true);
  }

  /**
   * Handles rpId operation.
   */
  private function rpId(): string
  {
    $host = parse_url(Environment::appPublicURL(), PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
      return 'localhost';
    }
    return str_ends_with(strtolower($host), 'paycal.app') ? 'paycal.app' : strtolower($host);
  }

  /**
   * Handles encodeB64Url operation.
   */
  private function encodeB64Url(string $binary): string
  {
    return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
  }

  /**
   * Handles decodeB64Url operation.
   */
  private function decodeB64Url(string $value): ?string
  {
    if ($value === '') {
      return null;
    }
    $padded = strtr($value, '-_', '+/');
    $mod = strlen($padded) % 4;
    if ($mod > 0) {
      $padded .= str_repeat('=', 4 - $mod);
    }
    $decoded = base64_decode($padded, true);
    return $decoded === false ? null : $decoded;
  }

}


