<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\RedisReliabilityService;
use PayCal\Domain\RecoveryKey;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Domain\SecurityLog;
use PayCal\Domain\User;
use PayCal\Observability\Lens;

/**
 * EmailVerificationController.php
 *
 * Purpose: Email-verification workflow controller for verification links,
 * verification codes, resend behavior, and recovery-material follow-up steps.
 *
 * Developer notes:
 * - Verification behavior is security-relevant and should preserve ambiguity,
 *   rate limits, and post-verification side effects.
 * - Recovery-key follow-up actions should remain coupled to successful
 *   verification rather than being callable through alternate paths.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Email-verification API surface.
 *
 * Responsibilities:
 * - Advance and confirm the email-verification lifecycle.
 * - Coordinate resend flows and related account-security side effects.
 * - Return verification outcomes suitable for auth and onboarding UIs.
 */
final class EmailVerificationController
{
  private const TOKEN_EXPIRY_HOURS = 24;
  private const RESEND_RATE_LIMIT = 120; // per hour
  private const VERIFY_RATE_LIMIT = 10; // per hour

  /**
   * Verify email address via token from magic link
   *
   * GET /auth/verify-email?token=<token>
   */
  #[Route('auth/verify-email', ['GET'])]
  /**
   * Handles verifyEmail operation.
   */
  public function verifyEmail(): void
  {
    // Rate limiting by IP
    if (!$this->enforceRateLimit('verify', self::VERIFY_RATE_LIMIT)) {
      $this->redirectWithError('Too many verification attempts. Please try again later.');

      return;
    }

    $rawToken = $_GET['token'] ?? '';
    $rawCode = $_GET['code'] ?? '';
    $tokenRawString = is_string($rawToken) ? $rawToken : '';
    $codeRawString = is_string($rawCode) ? $rawCode : '';
    $token = InputSanitizer::sanitizeString($tokenRawString);
    $code = strtoupper(InputSanitizer::sanitizeString($codeRawString));
    $lookupValue = $token !== '' ? $token : $code;
    $verificationMode = $token !== '' ? 'token' : ($code !== '' ? 'code' : 'none');
    $remoteIp = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])
      ? $_SERVER['REMOTE_ADDR']
      : 'unknown';
    $requestMethod = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
      ? $_SERVER['REQUEST_METHOD']
      : 'UNKNOWN';
    $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
      ? $_SERVER['REQUEST_URI']
      : '';
    $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
      ? $_SERVER['HTTP_HOST']
      : '';

    SecurityLog::log('email_verification_attempt', [
        'mode' => $verificationMode,
        'request_method' => $requestMethod,
        'request_uri' => $requestUri,
        'host' => $host,
        'ip' => $remoteIp,
        'token_length' => strlen($token),
        'code_length' => strlen($code),
    ]);

    if (empty($lookupValue)) {
      SecurityLog::log('email_verification_failed', [
          'reason' => 'missing_lookup_value',
          'mode' => $verificationMode,
          'request_method' => $requestMethod,
          'request_uri' => $requestUri,
          'ip' => $remoteIp,
      ]);

      $this->redirectWithError('Invalid verification link.');

      return;
    }

    // Hash token for comparison
    $tokenHash = hash('sha256', $lookupValue);

    // Find user with matching token hash first, then fallback to verification code.
    $user = $this->findUserByTokenHash($tokenHash);
    $verifiedByCode = false;
    if ($user === null) {
      $user = $this->findUserByVerificationCode($lookupValue);
      $verifiedByCode = ($user !== null);
    }

    if ($user === null) {
      $failureMessage = $verificationMode === 'code'
        ? 'Invalid or expired verification code.'
        : 'Invalid or expired verification link.';

      SecurityLog::log('email_verification_failed', [
          'reason' => $verificationMode === 'code' ? 'invalid_code' : 'invalid_token',
          'mode' => $verificationMode,
          'request_method' => $requestMethod,
          'request_uri' => $requestUri,
          'ip' => $remoteIp,
      ]);

      $this->redirectWithError($failureMessage);

      return;
    }

    // Check if already verified
    if ($user->email_verified) {
      SecurityLog::log('email_verification_already_verified', [
          'mode' => $verificationMode,
          'user_uuid' => $user->user_uuid,
          'email' => $user->email,
      ]);

      $this->redirectWithSuccess('Email already verified.');

      return;
    }

    // Check token expiry
    if ($this->isTokenExpired($user->email_verify_expiry)) {
      SecurityLog::log('email_verification_failed', [
          'reason' => 'expired_token',
          'mode' => $verificationMode,
          'user_uuid' => $user->user_uuid,
      ]);

      $this->redirectWithError('Verification link has expired. Please request a new one.');

      return;
    }

    // Mark email as verified and clear token
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'email_verified' => '1',
        'email_verify_token_hash' => '',
        'email_verify_expiry' => '',
    ]);

    if (!$verifiedByCode) {
      Database::del(Keys::EMAIL_VERIFICATION.':'.$tokenHash);
    }

    // Clear any one-time verification codes after successful verification.
    Database::unlink(Keys::VERIFICATION_CODES.':'.$user->user_uuid);

    SecurityLog::log('email_verified', [
      'mode' => $verifiedByCode ? 'code' : 'token',
        'user_uuid' => $user->user_uuid,
        'email' => $user->email,
    ]);

    // Generate and send recovery key
    $this->generateAndSendRecoveryKey($user);

    $this->redirectWithSuccess('Email verified! Your recovery key has been sent to your email.');
  }

  /**
   * Resend verification email
   *
   * POST /api/v1/account/resend-verification
   */
  #[Route('account/resend-verification', ['POST'])]
  /**
   * Handles resendVerification operation.
   */
  public function resendVerification(): void
  {
    try {
      Lens::add('Resend verification request started', [], 'verification_resend');

      if (!Authentication::validateAndTouchSession()) {
        Lens::add('Resend verification unauthorized (session)', [], 'verification_resend');
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);

        return;
      }

      $user = User::current();
      Lens::add('Resend verification user loaded', [
        'user_uuid' => $user->user_uuid,
        'email_verified' => (bool) $user->email_verified,
      ], 'verification_resend');

      if ('' === $user->user_uuid) {
        Lens::add('Resend verification unauthorized (empty user uuid)', [], 'verification_resend');
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);

        return;
      }

      $mutationGate = RedisReliabilityService::allowMutations();
      if ($mutationGate['allowed'] !== true) {
        Lens::add('Resend verification blocked by Redis guard', [
          'guard' => $mutationGate,
        ], 'verification_resend');
        Response::error(
          '[EmailVerification] Redis reliability guard blocked mutation.',
          ['redis_guard' => $mutationGate],
          HttpStatus::HTTP_SERVICE_UNAVAILABLE
        );

        return;
      }

      // Rate limiting
      $rateLimitResult = $this->checkRateLimit('resend:'.$user->user_uuid, self::RESEND_RATE_LIMIT);
      if (!$rateLimitResult['allowed']) {
        Lens::add('Resend verification rate limited', [
          'rate_limit' => $rateLimitResult,
        ], 'verification_resend');
        header('Retry-After: '.$rateLimitResult['retry_after']);
        header('X-RateLimit-Limit: '.self::RESEND_RATE_LIMIT);
        header('X-RateLimit-Remaining: 0');
        
        Response::error(
          'Too many resend requests. Please try again in '.$rateLimitResult['retry_minutes'].' minutes.',
          ['retry_after' => $rateLimitResult['retry_after'], 'retry_minutes' => $rateLimitResult['retry_minutes']],
          HttpStatus::HTTP_TOO_MANY_REQUESTS
        );

        return;
      }

      // If already verified, return success
      if ($user->email_verified) {
        Lens::add('Resend verification skipped (already verified)', [
          'user_uuid' => $user->user_uuid,
        ], 'verification_resend');
        Response::success('Email is already verified.');

        return;
      }

      // Local/dev fallback: if SMTP is not configured, auto-verify to avoid
      // blocking account flows during local testing.
      if (
        in_array(Environment::appEnv(), ['dev', 'mac'], true)
        && (Environment::smtpServer() === '' || Environment::smtpPort() === 0)
      ) {
        Lens::add('Resend verification auto-verified local env', [
          'app_env' => Environment::appEnv(),
        ], 'verification_resend');
        Database::hset(Keys::USER . ':' . $user->user_uuid, [
          'email_verified' => '1',
          'email_verify_token_hash' => '',
          'email_verify_expiry' => '0',
        ]);

        SecurityLog::log('verification_auto_verified_local', [
          'user_uuid' => $user->user_uuid,
          'email' => $user->email,
          'app_env' => Environment::appEnv(),
        ]);

        Response::success('Email auto-verified for local development (SMTP not configured).');

        return;
      }

      // Generate new verification token
      $token = bin2hex(random_bytes(32));
      $tokenHash = hash('sha256', $token);
      $expiry = time() + (self::TOKEN_EXPIRY_HOURS * FormTTL::ONE_HOUR->value);

      // Store token hash
      Database::hset(Keys::USER.':'.$user->user_uuid, [
          'email_verify_token_hash' => $tokenHash,
          'email_verify_expiry' => (string) $expiry,
      ]);

      Database::set(Keys::EMAIL_VERIFICATION.':'.$tokenHash, $user->user_uuid, self::TOKEN_EXPIRY_HOURS * FormTTL::ONE_HOUR->value);
      Lens::add('Resend verification token persisted', [
        'user_uuid' => $user->user_uuid,
        'expiry' => $expiry,
      ], 'verification_resend');

      // Generate one-time verification code (valid for 1 hour) when possible.
      $verificationCode = '';
      try {
        $verificationCode = Security::generateVerificationCode(6);
        User::addVerificationCode($verificationCode, $user->user_uuid);
      } catch (\Throwable $codeError) {
        Lens::add('Resend verification code generation failed', [
          'user_uuid' => $user->user_uuid,
          'error' => $codeError->getMessage(),
        ], 'verification_resend');
        SecurityLog::log('verification_code_generation_failed', [
          'user_uuid' => $user->user_uuid,
          'email' => $user->email,
          'error' => $codeError->getMessage(),
        ]);
      }

      // Send verification email (primary: magic link + code, fallback: code-only message).
      $sent = false;
      try {
        $sent = EmailGarum::sendVerificationEmail($token, $user->email, $user->full_name, $verificationCode);
        Lens::add('Resend verification primary send attempted', [
          'user_uuid' => $user->user_uuid,
          'sent' => $sent,
          'has_code' => $verificationCode !== '',
        ], 'verification_resend');
      } catch (\Throwable $mailError) {
        Lens::add('Resend verification primary send exception', [
          'user_uuid' => $user->user_uuid,
          'error' => $mailError->getMessage(),
        ], 'verification_resend');
        SecurityLog::log('verification_email_resent_primary_exception', [
          'user_uuid' => $user->user_uuid,
          'email' => $user->email,
          'error' => $mailError->getMessage(),
        ]);
      }

      if (!$sent) {
        if ($verificationCode !== '') {
          try {
            $fallbackResult = EmailGarum::emailVerificationCode($verificationCode, $user->email);
            $sent = $fallbackResult === 'Email Sent Successfully.';
            Lens::add('Resend verification fallback send attempted', [
              'user_uuid' => $user->user_uuid,
              'sent' => $sent,
              'result' => $fallbackResult,
            ], 'verification_resend');
          } catch (\Throwable $fallbackError) {
            Lens::add('Resend verification fallback send exception', [
              'user_uuid' => $user->user_uuid,
              'error' => $fallbackError->getMessage(),
            ], 'verification_resend');
            SecurityLog::log('verification_email_resent_fallback_exception', [
              'user_uuid' => $user->user_uuid,
              'email' => $user->email,
              'error' => $fallbackError->getMessage(),
            ]);
          }
        }

        if ($sent) {
          SecurityLog::log('verification_email_resent_fallback', [
            'user_uuid' => $user->user_uuid,
            'email' => $user->email,
            'fallback' => 'code_only',
          ]);
        }
      }

      if ($sent) {
        Lens::add('Resend verification completed successfully', [
          'user_uuid' => $user->user_uuid,
        ], 'verification_resend');
        SecurityLog::log('verification_email_resent', [
            'user_uuid' => $user->user_uuid,
            'email' => $user->email,
        ]);

        Response::success('Verification email sent. Please check your inbox.');
      } else {
        Lens::add('Resend verification failed after send attempts', [
          'user_uuid' => $user->user_uuid,
          'smtp_server' => Environment::smtpServer(),
          'smtp_port' => Environment::smtpPort(),
        ], 'verification_resend');
        // Log the failure with SMTP details
        error_log('[EmailVerificationController] Email send failed for user: ' . $user->user_uuid);
        
        SecurityLog::log('verification_email_resent_failed', [
            'user_uuid' => $user->user_uuid,
            'email' => $user->email,
            'smtp_server' => Environment::smtpServer(),
            'smtp_port' => Environment::smtpPort(),
        ]);

        Response::error('Failed to send verification email. Please try again later.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      }
    } catch (\Throwable $e) {
      $errorMessage = $e->getMessage();
      $errorFile = $e->getFile();
      $isRedisFailure = str_contains(strtolower($errorMessage), 'redis')
        || str_contains($errorFile, '/Redis.php');

      Lens::add('Resend verification uncaught exception', [
        'error' => $errorMessage,
        'file' => $errorFile,
        'line' => $e->getLine(),
        'redis_related' => $isRedisFailure,
      ], 'verification_resend');

      if ($isRedisFailure) {
        SecurityLog::log('verification_email_resend_redis_exception', [
          'error' => $errorMessage,
          'file' => $errorFile,
          'line' => $e->getLine(),
          'trace' => $e->getTraceAsString(),
        ]);
        header('Retry-After: 30');
        Response::error(
          'Verification resend is temporarily unavailable (Redis timeout). Please retry shortly.',
          ['retry_after' => 30],
          HttpStatus::HTTP_SERVICE_UNAVAILABLE
        );

        return;
      }

      SecurityLog::log('verification_email_resend_exception', [
        'error' => $errorMessage,
        'file' => $errorFile,
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      Response::error('An error occurred while resending verification email.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Generate recovery key and send via email
   *
   * @param User $user User object
   */
  private function generateAndSendRecoveryKey(User $user): void
  {
    // Check if recovery key already generated
    if ($user->recovery_key_generated) {
      SecurityLog::log('recovery_key_generation_skipped', [
          'user_uuid' => $user->user_uuid,
          'reason' => 'already_generated',
      ]);

      return;
    }

    // Generate account recovery salt if not exists
    if (empty($user->account_recovery_salt)) {
      $recoverySaltBytes = RecoveryKey::generateRecoverySalt();
      $recoverySalt = base64_encode($recoverySaltBytes);

      Database::hset(Keys::USER.':'.$user->user_uuid, [
          'account_recovery_salt' => $recoverySalt,
      ]);
    } else {
      $recoverySalt = $user->account_recovery_salt;
    }

    // Generate 256-bit recovery key
    $recoveryKeyBytes = RecoveryKey::generate();

    // Encode with Crockford Base32
    $recoveryKeyEncoded = RecoveryKey::encodeCrockford($recoveryKeyBytes);

    // Format for display (8 groups of 4 = 13 groups total for 52 chars)
    $recoveryKeyFormatted = RecoveryKey::format($recoveryKeyEncoded);

    // Derive recovery KEK/proof key from raw bytes.
    RecoveryKey::deriveKEK($recoveryKeyBytes, $recoverySalt);
    $recoveryProofKey = base64_encode(RecoveryKey::deriveProofKey($recoveryKeyBytes, $recoverySalt));

    // NOTE: In production, DEK must be retrieved from client session
    // For now, we'll set recovery_key_generated flag and store salt
    // Actual DEK wrapping will happen when user is logged in with DEK in memory

    // Mark recovery key as generated
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
        'recovery_proof_key_version' => '1',
    ]);

    // Send recovery key email
    $sent = EmailGarum::sendRecoveryKeyEmail($recoveryKeyFormatted, $user->email, $user->full_name);

    if ($sent) {
      SecurityLog::log('recovery_key_sent', [
          'user_uuid' => $user->user_uuid,
          'email' => $user->email,
      ]);
    } else {
      SecurityLog::log('recovery_key_send_failed', [
          'user_uuid' => $user->user_uuid,
          'email' => $user->email,
      ]);
    }
  }

  /**
   * Find user by token hash
   *
   * @param string $tokenHash SHA256 hash of token
   *
   * @return User|null User object or null if not found
   */
  private function findUserByTokenHash(string $tokenHash): ?User
  {
    $indexedUserUUID = Database::get(Keys::EMAIL_VERIFICATION.':'.$tokenHash);
    if ($indexedUserUUID !== '') {
      $indexedUser = User::getByUUID($indexedUserUUID);
      if ($indexedUser !== null && $indexedUser->email_verify_token_hash === $tokenHash) {
        return $indexedUser;
      }
    }

    // Scan all users for matching token hash
    // In production, consider indexing tokens in Redis for faster lookup
    $userKeys = Database::scanKeys(Keys::USER.':*');

    foreach ($userKeys as $userKey) {
      $userData = Database::hgetall($userKey);

      if (isset($userData['email_verify_token_hash']) && $userData['email_verify_token_hash'] === $tokenHash) {
        $matchedUserUUID = (string) ($userData['user_uuid'] ?? '');
        if ($matchedUserUUID !== '') {
          $expiry = (int) ($userData['email_verify_expiry'] ?? 0);
          $ttl = max(0, $expiry - time());
          if ($ttl > 0) {
            Database::set(Keys::EMAIL_VERIFICATION.':'.$tokenHash, $matchedUserUUID, $ttl);
          }
        }
        return User::getByUUID($matchedUserUUID);
      }
    }

    return null;
  }

  /**
   * Find user by one-time verification code.
   *
   * @param string $code Verification code entered by user
   *
   * @return User|null User object or null if not found
   */
  private function findUserByVerificationCode(string $code): ?User
  {
    $normalizedCode = strtoupper(trim($code));
    if ($normalizedCode === '') {
      return null;
    }

    // Fast path: if user is authenticated on /unverified, check only their code set.
    $currentUserUUID = User::currentUUID();
    if ($currentUserUUID !== '') {
      $currentUserKey = Keys::VERIFICATION_CODES.':'.$currentUserUUID;
      $createdAt = Database::hget($currentUserKey, $normalizedCode);

      if ($createdAt !== '') {
        if ((time() - (int) $createdAt) > FormTTL::ONE_HOUR->value) {
          Database::hdel($currentUserKey, $normalizedCode);
          return null;
        }

        return User::getByUUID($currentUserUUID);
      }
    }

    $codeKeys = Database::scanKeys(Keys::VERIFICATION_CODES.':*');

    foreach ($codeKeys as $codeKey) {
      $createdAt = Database::hget($codeKey, $normalizedCode);
      if ($createdAt === '') {
        continue;
      }

      // Defensive expiry guard: code is valid for 1 hour.
      if ((time() - (int) $createdAt) > FormTTL::ONE_HOUR->value) {
        Database::hdel($codeKey, $normalizedCode);
        continue;
      }

      $parts = explode(':', $codeKey, 2);
      if (count($parts) !== 2 || $parts[1] === '') {
        continue;
      }

      return User::getByUUID($parts[1]);
    }

    return null;
  }

  /**
   * Check if token is expired
   *
   * @param string|null $expiryTimestamp Expiry timestamp
   *
   * @return bool True if expired
   */
  private function isTokenExpired(?string $expiryTimestamp): bool
  {
    if (empty($expiryTimestamp)) {
      return true;
    }

    return time() > (int) $expiryTimestamp;
  }

  /**
   * Enforce rate limiting
   *
   * @param string $key  Rate limit key
   * @param int    $limit Max requests per hour
   *
   * @return bool True if under limit, false if over
   */
  /**
   * Check rate limit and return detailed status
   *
   * @param string $key   Rate limit key
   * @param int    $limit Maximum requests per hour
   *
   * @return array{allowed: bool, retry_after: int, retry_minutes: int, current_count: int}
   */
  private function checkRateLimit(string $key, int $limit): array
  {
    $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])
      ? $_SERVER['REMOTE_ADDR']
      : 'unknown';
    $rateLimitKey = "rate_limit:email_verification:{$key}:{$ip}";

    $count = Database::get($rateLimitKey);
    $ttl = Database::ttl($rateLimitKey);
    
    // Calculate retry time
    $retryAfter = $ttl > 0 ? $ttl : FormTTL::ONE_HOUR->value;
    $retryMinutes = (int) ceil($retryAfter / FormTTL::ONE_MIN->value);

    if ($count === '') {
      Database::set($rateLimitKey, '1', FormTTL::ONE_HOUR->value);

      return [
        'allowed' => true,
        'retry_after' => 0,
        'retry_minutes' => 0,
        'current_count' => 1,
      ];
    }

    $currentCount = (int) $count;

    if ($currentCount >= $limit) {
      return [
        'allowed' => false,
        'retry_after' => $retryAfter,
        'retry_minutes' => $retryMinutes,
        'current_count' => $currentCount,
      ];
    }

    Database::set($rateLimitKey, (string) ($currentCount + 1), FormTTL::ONE_HOUR->value);

    return [
      'allowed' => true,
      'retry_after' => 0,
      'retry_minutes' => 0,
      'current_count' => $currentCount + 1,
    ];
  }

  /**
   * Legacy wrapper for checkRateLimit (backwards compatibility)
   *
   * @param string $key   Rate limit key
   * @param int    $limit Maximum requests per hour
   */
  private function enforceRateLimit(string $key, int $limit): bool
  {
    return $this->checkRateLimit($key, $limit)['allowed'];
  }

  /**
   * Redirect to auth page with error message
   *
   * @param string $message Error message
   */
  private function redirectWithError(string $message): void
  {
    if (Authentication::validateAndTouchSession()) {
      header('Location: /unverified/?verification_error='.urlencode($message));
      exit;
    }

    header('Location: /auth/?verification_error='.urlencode($message));
    exit;
  }

  /**
   * Redirect to success page
   *
   * @param string $message Success message
   */
  private function redirectWithSuccess(string $message): void
  {
    header('Location: /auth/?auth_tab=signin&verification_success=1&signin_message='.urlencode($message));
    exit;
  }
}

