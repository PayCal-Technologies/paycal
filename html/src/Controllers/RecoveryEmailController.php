<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Database;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Domain\SecurityLog;
use PayCal\Domain\User;

/**
 * RecoveryEmailController.php
 *
 * Purpose: Handle recovery email verification and management.
 *
 * Endpoints:
 * - POST /api/v1/account/recovery-email/start - Start recovery email verification
 * - POST /api/v1/account/recovery-email/verify - Verify recovery email code
 * - POST /api/v1/account/recovery-email/resend - Resend recovery email code
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class RecoveryEmailController
{

  /**
   * Start recovery email verification by sending a code.
   * POST /api/v1/account/recovery-email/start
   * 
   * Request: { "recovery_email": "recovery@example.com" }
   * Response: { "txn_id": "...", "message": "Verification code sent" }
   */
  #[Route('account/recovery-email/start', ['POST'])]
  /**
   * Handles start operation.
   */
  public function start(): void
  {
    try {
      if (!Authentication::validateAndTouchSession()) {
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $user = User::current();
      if ('' === $user->user_uuid) {
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $input = json_decode(file_get_contents('php://input') ?: '', true);
      if (!is_array($input) || !isset($input['recovery_email']) || !is_string($input['recovery_email'])) {
        Response::error('Missing recovery_email.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $csrfToken = isset($input['csrf_token']) && is_string($input['csrf_token'])
        ? InputSanitizer::sanitizeString($input['csrf_token'])
        : '';
      if ('' === $csrfToken || !$user->verifyFormNonce('settings', $csrfToken)) {
        Response::error('Invalid CSRF token.', [], HttpStatus::HTTP_FORBIDDEN);
        return;
      }

      $recoveryEmail = InputSanitizer::sanitizeEmail($input['recovery_email']);
      if (!filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL)) {
        Response::error('Invalid email format.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      // Rate limit: check resend cooldown
      $cooldownKey = 'recovery_email:resend:' . $user->user_uuid;
      $lastSentAt = (int) Database::get($cooldownKey);
      $now = time();
      $cooldownSeconds = (int) SystemConfig::get('recovery_email_resend_cooldown_seconds');

      if ($lastSentAt > 0 && ($now - $lastSentAt) < $cooldownSeconds) {
        $retryAfter = $cooldownSeconds - ($now - $lastSentAt);
        Response::error(
          'Too many requests. Retry in ' . $retryAfter . ' seconds.',
          ['retry_after' => $retryAfter],
          HttpStatus::HTTP_TOO_MANY_REQUESTS
        );
        SecurityLog::log('recovery_email_start_cooldown_triggered', [
          'user_uuid' => $user->user_uuid,
          'retry_after' => $retryAfter,
        ]);
        return;
      }

      // Rate limit: resends per hour
      $resendCountKey = 'recovery_email:resends:' . $user->user_uuid;
      $resendCount = (int) Database::get($resendCountKey) ?: 0;
      $maxResends = (int) SystemConfig::get('recovery_email_max_resends_per_hour');

      if ($resendCount >= $maxResends) {
        Response::error(
          'Too many resend attempts. Try again in 1 hour.',
          [],
          HttpStatus::HTTP_TOO_MANY_REQUESTS
        );
        SecurityLog::log('recovery_email_start_max_resends_exceeded', [
          'user_uuid' => $user->user_uuid,
          'max_resends' => $maxResends,
        ]);
        return;
      }

      // Generate verification code (6 characters)
      $code = Security::generateVerificationCode(6);
      $codeHash = hash('sha256', $code);
      $ttlMinutes = (int) SystemConfig::get('recovery_email_code_ttl_minutes');
      $expiresAt = $now + ($ttlMinutes * 60);

      // Store code hash with expiry
      $codeKey = Keys::recoveryEmailCode($user->user_uuid);
      Database::hset($codeKey, [
        'code_hash' => $codeHash,
        'expires_at' => (string) $expiresAt,
        'created_at' => (string) $now,
      ]);
      Database::expire($codeKey, $ttlMinutes * 60 + 300);

      // Send code to recovery email
      $sent = EmailGarum::sendRecoveryEmailVerificationCode($recoveryEmail, $user->full_name, $code);

      if ($sent) {
        // Update cooldown and resend counter
        Database::set($cooldownKey, (string) $now, $cooldownSeconds);
        Database::incr($resendCountKey);
        Database::expire($resendCountKey, 3600); // 1 hour

        // Store recovery email in user session
        Database::hset(Keys::SESSION . ':' . Authentication::getSessionHashFromCookie(), [
          'recovery_email_pending' => $recoveryEmail,
        ]);

        SecurityLog::log('recovery_email_verification_started', [
          'user_uuid' => $user->user_uuid,
          'recovery_email' => $recoveryEmail,
        ]);

        Response::success('Verification code sent to recovery email.', [
          'recovery_email' => $this->createEmailHint($recoveryEmail),
          'expires_in_minutes' => $ttlMinutes,
          'resend_cooldown_seconds' => $cooldownSeconds,
        ]);
      } else {
        Response::error('Failed to send verification code. Please try again.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        SecurityLog::log('recovery_email_verification_send_failed', [
          'user_uuid' => $user->user_uuid,
          'recovery_email' => $recoveryEmail,
        ]);
      }
    } catch (\Throwable $e) {
      SecurityLog::log('recovery_email_start_exception', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Verify recovery email code.
   * POST /api/v1/account/recovery-email/verify
   * 
   * Request: { "code": "123456" }
   * Response: { "message": "Recovery email verified" }
   */
  #[Route('account/recovery-email/verify', ['POST'])]
  /**
   * Handles verify operation.
   */
  public function verify(): void
  {
    try {
      if (!Authentication::validateAndTouchSession()) {
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $user = User::current();
      if ('' === $user->user_uuid) {
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $input = json_decode(file_get_contents('php://input') ?: '', true);
      if (!is_array($input) || !isset($input['code'])) {
        Response::error('Missing code.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $csrfToken = isset($input['csrf_token']) && is_string($input['csrf_token'])
        ? InputSanitizer::sanitizeString($input['csrf_token'])
        : '';
      if ('' === $csrfToken || !$user->verifyFormNonce('settings', $csrfToken)) {
        Response::error('Invalid CSRF token.', [], HttpStatus::HTTP_FORBIDDEN);
        return;
      }

      $code = strtoupper(InputSanitizer::sanitizeString($input['code']));
      if (strlen($code) !== 6) {
        Response::error('Verification code must be exactly 6 characters.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      // Check attempt limit
      $codeKey = Keys::recoveryEmailCode($user->user_uuid);
      $attemptData = Database::hgetall($codeKey);
      if (empty($attemptData)) {
        Response::error('No verification code found. Start verification again.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      // Check expiry
      $expiresAt = (int) ($attemptData['expires_at'] ?? 0);
      if (time() > $expiresAt) {
        Database::del($codeKey);
        Response::error('Verification code has expired.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      // Check verify attempts
      $attempts = (int) ($attemptData['verify_attempts'] ?? '0');
      $maxAttempts = (int) SystemConfig::get('recovery_email_max_verify_attempts');

      if ($attempts >= $maxAttempts) {
        Database::del($codeKey);
        Response::error('Too many failed attempts. Start verification again.', [], HttpStatus::HTTP_TOO_MANY_REQUESTS);
        SecurityLog::log('recovery_email_verify_max_attempts', [
          'user_uuid' => $user->user_uuid,
        ]);
        return;
      }

      // Verify code
      $storedHash = (string) ($attemptData['code_hash'] ?? '');
      $providedHash = hash('sha256', $code);

      if (!hash_equals($storedHash, $providedHash)) {
        Database::hset($codeKey, ['verify_attempts' => (string) ($attempts + 1)]);
        Response::error('Invalid code.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      // Get recovery email from session
      $sessionHash = Authentication::getSessionHashFromCookie();
      $recoveryEmail = (string) Database::hget(Keys::SESSION . ':' . $sessionHash, 'recovery_email_pending');

      if (!$recoveryEmail) {
        Response::error('Recovery email not found in session.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      // Mark recovery email as verified
      $now = time();
      Database::hset(Keys::USER . ':' . $user->user_uuid, [
        'recovery_email' => $recoveryEmail,
        'recovery_email_verified' => '1',
        'recovery_email_verified_at' => (string) $now,
      ]);

      // Clean up
      Database::del($codeKey);
      Database::hset(Keys::SESSION . ':' . $sessionHash, ['recovery_email_pending' => '']);

      SecurityLog::log('recovery_email_verified', [
        'user_uuid' => $user->user_uuid,
        'recovery_email' => $recoveryEmail,
      ]);

      Response::success('Recovery email verified successfully.', [
        'recovery_email' => $this->createEmailHint($recoveryEmail),
      ]);
    } catch (\Throwable $e) {
      SecurityLog::log('recovery_email_verify_exception', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Resend recovery email verification code.
   * POST /api/v1/account/recovery-email/resend
   * 
   * Request: {}
   * Response: { "message": "Code resent" }
   */
  #[Route('account/recovery-email/resend', ['POST'])]
  /**
   * Handles resend operation.
   */
  public function resend(): void
  {
    try {
      if (!Authentication::validateAndTouchSession()) {
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $user = User::current();
      if ('' === $user->user_uuid) {
        Response::error('Unauthorized.', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      $input = json_decode(file_get_contents('php://input') ?: '', true);
      if (!is_array($input)) {
        $input = [];
      }

      $csrfToken = isset($input['csrf_token']) && is_string($input['csrf_token'])
        ? InputSanitizer::sanitizeString($input['csrf_token'])
        : '';
      if ('' === $csrfToken || !$user->verifyFormNonce('settings', $csrfToken)) {
        Response::error('Invalid CSRF token.', [], HttpStatus::HTTP_FORBIDDEN);
        return;
      }

      // Check cooldown
      $cooldownKey = 'recovery_email:resend:' . $user->user_uuid;
      $lastSentAt = (int) Database::get($cooldownKey);
      $now = time();
      $cooldownSeconds = (int) SystemConfig::get('recovery_email_resend_cooldown_seconds');

      if ($lastSentAt > 0 && ($now - $lastSentAt) < $cooldownSeconds) {
        $retryAfter = $cooldownSeconds - ($now - $lastSentAt);
        Response::error(
          'Too soon. Retry in ' . $retryAfter . ' seconds.',
          ['retry_after' => $retryAfter],
          HttpStatus::HTTP_TOO_MANY_REQUESTS
        );
        return;
      }

      // Check max resends
      $resendCountKey = 'recovery_email:resends:' . $user->user_uuid;
      $resendCount = (int) Database::get($resendCountKey) ?: 0;
      $maxResends = (int) SystemConfig::get('recovery_email_max_resends_per_hour');

      if ($resendCount >= $maxResends) {
        Response::error('Max resends exceeded. Try again in 1 hour.', [], HttpStatus::HTTP_TOO_MANY_REQUESTS);
        return;
      }

      // Get recovery email from session
      $sessionHash = Authentication::getSessionHashFromCookie();
      $recoveryEmail = (string) Database::hget(Keys::SESSION . ':' . $sessionHash, 'recovery_email_pending');

      if (!$recoveryEmail) {
        Response::error('No pending recovery email.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      // Generate new code (6 characters)
      $code = Security::generateVerificationCode(6);
      $codeHash = hash('sha256', $code);
      $ttlMinutes = (int) SystemConfig::get('recovery_email_code_ttl_minutes');
      $expiresAt = $now + ($ttlMinutes * 60);

      $codeKey = Keys::recoveryEmailCode($user->user_uuid);
      Database::hset($codeKey, [
        'code_hash' => $codeHash,
        'expires_at' => (string) $expiresAt,
        'created_at' => (string) $now,
        'verify_attempts' => '0',
      ]);
      Database::expire($codeKey, $ttlMinutes * 60 + 300);

      // Send code
      $sent = EmailGarum::sendRecoveryEmailVerificationCode($recoveryEmail, $user->full_name, $code);

      if ($sent) {
        Database::set($cooldownKey, (string) $now, $cooldownSeconds);
        Database::incr($resendCountKey);
        Database::expire($resendCountKey, 3600);

        SecurityLog::log('recovery_email_resent', [
          'user_uuid' => $user->user_uuid,
        ]);

        Response::success('Verification code resent.', [
          'expires_in_minutes' => $ttlMinutes,
          'resend_cooldown_seconds' => $cooldownSeconds,
        ]);
      } else {
        Response::error('Failed to resend code.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      }
    } catch (\Throwable $e) {
      SecurityLog::log('recovery_email_resend_exception', [
        'error' => $e->getMessage(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Create email hint by masking most of the address.
   * Example: user@example.com -> us***@example.com
   */
  private function createEmailHint(string $email): string
  {
    $atPos = strrpos($email, '@');
    if (false === $atPos || $atPos < 2) {
      return '***@***';
    }
    
    return substr($email, 0, 2) . '***' . substr($email, $atPos);
  }
}

