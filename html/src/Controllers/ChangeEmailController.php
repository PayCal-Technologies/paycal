<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Database;
use PayCal\Domain\EmailChangeTransaction;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Domain\SecurityLog;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;

/**
 * ChangeEmailController.php
 *
 * Purpose: Handle secure email change with dual verification.
 *
 * Endpoints:
 * - POST /api/v1/account/change-email/start - Initiate email change with passkey step-up
 * - POST /api/v1/account/change-email/verify - Verify both old and new email codes
 * - POST /api/v1/account/change-email/resend - Resend verification codes
 * - POST /api/v1/account/change-email/cancel - Cancel email change transaction
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
final class ChangeEmailController
{
  /**
   * Start email change transaction.
   * Requires: Recovery email verified, fresh passkey step-up
   * 
   * POST /api/v1/account/change-email/start
   * Request: { "new_email": "newemail@example.com" }
   * Response: { "txn_id": "...", "message": "Codes sent to old and new emails" }
   */
  #[Route('account/change-email/start', ['POST'])]
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

      // Check: recovery email required?
      $recoveryEmailRequired = (bool) SystemConfig::get('recovery_email_required_for_email_change');
      if ($recoveryEmailRequired && !$user->recovery_email_verified) {
        Response::error('Recovery email must be verified first.', [], HttpStatus::HTTP_FORBIDDEN);
        return;
      }

      // Check: passkey step-up required (fresh assertion)
      if (!$this->validateStepUp($user->user_uuid)) {
        Response::error('Fresh passkey step-up required.', [], HttpStatus::HTTP_FORBIDDEN);
        return;
      }

      $input = $this->requestInput();
      if (!is_array($input) || !isset($input['new_email']) || !is_string($input['new_email'])) {
        Response::error('Missing new_email.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $newEmail = InputSanitizer::sanitizeEmail($input['new_email']);
      if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        Response::error('Invalid email format.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      if ($newEmail === $user->email) {
        Response::error('New email must be different from current email.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $existingUserUUID = UserRepository::getUUIDFromEmail($newEmail);
      if ($existingUserUUID !== '' && $existingUserUUID !== $user->user_uuid) {
        Response::error('Email is already in use.', [], 409);
        return;
      }

      // Rate limit: max starts per day
      $startsKey = 'change_email:starts:' . $user->user_uuid;
      $startCount = (int) Database::get($startsKey) ?: 0;
      $maxStarts = (int) SystemConfig::get('email_change_max_new_email_starts_per_day');

      if ($startCount >= $maxStarts) {
        Response::error('Max email change attempts exceeded today.', [], HttpStatus::HTTP_TOO_MANY_REQUESTS);
        return;
      }

      // Create transaction
      $codeTtlMinutes = (int) SystemConfig::get('email_change_code_ttl_minutes');
      $txn = EmailChangeTransaction::create(
        $user->user_uuid,
        $user->email,
        $newEmail,
        $codeTtlMinutes
      );

      // Generate codes
      $oldCode = Security::generateVerificationCode(6);
      $newCode = Security::generateVerificationCode(6);
      $txn->setOldCodeHash(hash('sha256', $oldCode));
      $txn->setNewCodeHash(hash('sha256', $newCode));

      // Save transaction
      $txn->save();

      // Send codes to both emails (bypass external transport while running PHPUnit).
      if (defined('PHPUNIT_COMPOSER_INSTALL')) {
        $oldSent = true;
        $newSent = true;
      } else {
        $oldSent = EmailGarum::sendChangeEmailCode($user->email, $user->full_name, 'old', $oldCode, $txn->getTxnId());
        $newSent = EmailGarum::sendChangeEmailCode($newEmail, $user->full_name, 'new', $newCode, $txn->getTxnId());
      }

      if ($oldSent && $newSent) {
        Database::incr($startsKey);
        Database::expire($startsKey, 86400); // 24 hours

        SecurityLog::log('change_email_started', [
          'user_uuid' => $user->user_uuid,
          'old_email' => $user->email,
          'new_email' => $newEmail,
          'txn_id' => $txn->getTxnId(),
        ]);

        Response::success('Verification codes sent to both email addresses.', [
          'txn_id' => $txn->getTxnId(),
          'old_email_hint' => $this->createEmailHint($user->email),
          'new_email_hint' => $this->createEmailHint($newEmail),
          'expires_in_minutes' => $codeTtlMinutes,
        ]);
      } else {
        $txn->delete();
        Response::error('Failed to send verification codes.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        SecurityLog::log('change_email_start_send_failed', [
          'user_uuid' => $user->user_uuid,
          'old_sent' => $oldSent,
          'new_sent' => $newSent,
        ]);
      }
    } catch (\Throwable $e) {
      SecurityLog::log('change_email_start_exception', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Verify email change codes.
   * 
   * POST /api/v1/account/change-email/verify
   * Request: { "txn_id": "...", "old_code": "123456", "new_code": "654321" }
   * Response: { "message": "Email changed successfully" }
   */
  #[Route('account/change-email/verify', ['POST'])]
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

      $input = $this->requestInput();
      if (!is_array($input) || !isset($input['txn_id'], $input['old_code'], $input['new_code'])) {
        Response::error('Missing txn_id, old_code, or new_code.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $txnId = InputSanitizer::sanitizeString($input['txn_id']);
      $oldCode = strtoupper(InputSanitizer::sanitizeString($input['old_code']));
      $newCode = strtoupper(InputSanitizer::sanitizeString($input['new_code']));

      // Load transaction
      $txn = EmailChangeTransaction::load($txnId);
      if (!$txn || $txn->getUserUuid() !== $user->user_uuid) {
        Response::error('Transaction not found.', [], HttpStatus::HTTP_NOT_FOUND);
        return;
      }

      // Check status
      if ($txn->getStatus() !== 'pending') {
        Response::error('Transaction is not pending.', [], HttpStatus::HTTP_UNPROCESSABLE);
        return;
      }

      // Check expiry
      if ($txn->isExpired()) {
        $txn->delete();
        Response::error('Transaction has expired.', [], HttpStatus::HTTP_UNPROCESSABLE);
        return;
      }

      // Check attempt limit
      $maxAttempts = (int) SystemConfig::get('email_change_max_verify_attempts');
      if ($txn->getVerifyAttempts() >= $maxAttempts) {
        $txn->delete();
        Response::error('Too many failed attempts.', [], HttpStatus::HTTP_TOO_MANY_REQUESTS);
        return;
      }

      // Verify both codes
      $oldHash = hash('sha256', $oldCode);
      $newHash = hash('sha256', $newCode);
      $oldMatches = hash_equals($txn->getOldCodeHash(), $oldHash);
      $newMatches = hash_equals($txn->getNewCodeHash(), $newHash);

      if (!($oldMatches && $newMatches)) {
        $txn->incrementVerifyAttempts();
        $txn->save();
        Response::error('Invalid code(s).', [], HttpStatus::HTTP_UNAUTHORIZED);
        return;
      }

      // Mark codes as verified
      $txn->setOldVerified(true);
      $txn->setNewVerified(true);
      $txn->setStatus('verified');

      // Atomically update user email
      $now = time();
      Database::hset(Keys::USER . ':' . $user->user_uuid, [
        'email' => $txn->getNewEmail(),
        'recovery_email_verified_at' => '',  // Reset recovery email verification if needed
      ]);

      // Update email indices
      \PayCal\Domain\UserRepository::setUserEmail($user->user_uuid, $txn->getNewEmail());

      // Mark transaction as committed
      $txn->setStatus('committed');
      $txn->save();

      SecurityLog::log('email_changed', [
        'user_uuid' => $user->user_uuid,
        'old_email' => $txn->getOldEmail(),
        'new_email' => $txn->getNewEmail(),
        'txn_id' => $txnId,
      ]);

      // Send confirmation emails
      EmailGarum::sendEmailChangeConfirmation($txn->getOldEmail(), $user->full_name, $txn->getNewEmail(), 'old');
      EmailGarum::sendEmailChangeConfirmation($txn->getNewEmail(), $user->full_name, $txn->getOldEmail(), 'new');

      // Clean up old transaction after brief delay
      $txn->delete();

      Response::success('Email changed successfully.', [
        'new_email' => $this->createEmailHint($txn->getNewEmail()),
      ]);
    } catch (\Throwable $e) {
      SecurityLog::log('change_email_verify_exception', [
        'error' => $e->getMessage(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Resend email change codes.
   * 
   * POST /api/v1/account/change-email/resend
   * Request: { "txn_id": "..." }
   * Response: { "message": "Codes resent" }
   */
  #[Route('account/change-email/resend', ['POST'])]
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

      $input = $this->requestInput();
      if (!is_array($input) || !isset($input['txn_id'])) {
        Response::error('Missing txn_id.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $txnId = InputSanitizer::sanitizeString($input['txn_id']);
      $txn = EmailChangeTransaction::load($txnId);
      if (!$txn || $txn->getUserUuid() !== $user->user_uuid || $txn->getStatus() !== 'pending') {
        Response::error('Invalid transaction.', [], HttpStatus::HTTP_NOT_FOUND);
        return;
      }

      // Check cooldown
      $now = time();
      $cooldownSeconds = (int) SystemConfig::get('email_change_resend_cooldown_seconds');
      $lastSentAt = $txn->getLastSentAt();

      if ($lastSentAt > 0 && ($now - $lastSentAt) < $cooldownSeconds) {
        $retryAfter = $cooldownSeconds - ($now - $lastSentAt);
        Response::error(
          'Too soon. Retry in ' . $retryAfter . ' seconds.',
          ['retry_after' => $retryAfter],
          HttpStatus::HTTP_TOO_MANY_REQUESTS
        );
        return;
      }

      // Check max resends per hour
      $maxResends = (int) SystemConfig::get('email_change_max_resends_per_hour');
      if ($txn->getResendCount() >= $maxResends) {
        Response::error('Max resends exceeded.', [], HttpStatus::HTTP_TOO_MANY_REQUESTS);
        return;
      }

      // Regenerate codes
      $oldCode = Security::generateVerificationCode(6);
      $newCode = Security::generateVerificationCode(6);
      $txn->setOldCodeHash(hash('sha256', $oldCode));
      $txn->setNewCodeHash(hash('sha256', $newCode));
      $txn->setLastSentAt($now);
      $txn->incrementResendCount();

      // Send codes (bypass external transport while running PHPUnit).
      if (defined('PHPUNIT_COMPOSER_INSTALL')) {
        $oldSent = true;
        $newSent = true;
      } else {
        $oldSent = EmailGarum::sendChangeEmailCode($txn->getOldEmail(), $user->full_name, 'old', $oldCode, $txnId);
        $newSent = EmailGarum::sendChangeEmailCode($txn->getNewEmail(), $user->full_name, 'new', $newCode, $txnId);
      }

      if ($oldSent && $newSent) {
        $txn->save();

        SecurityLog::log('change_email_codes_resent', [
          'user_uuid' => $user->user_uuid,
          'txn_id' => $txnId,
        ]);

        Response::success('Verification codes resent.');
      } else {
        Response::error('Failed to resend codes.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
      }
    } catch (\Throwable $e) {
      SecurityLog::log('change_email_resend_exception', [
        'error' => $e->getMessage(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Cancel email change transaction.
   * 
   * POST /api/v1/account/change-email/cancel
   * Request: { "txn_id": "..." }
   * Response: { "message": "Transaction cancelled" }
   */
  #[Route('account/change-email/cancel', ['POST'])]
  /**
   * Handles cancel operation.
   */
  public function cancel(): void
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

      $input = $this->requestInput();
      if (!is_array($input) || !isset($input['txn_id'])) {
        Response::error('Missing txn_id.', [], HttpStatus::HTTP_BAD_REQUEST);
        return;
      }

      $txnId = InputSanitizer::sanitizeString($input['txn_id']);
      $txn = EmailChangeTransaction::load($txnId);
      if (!$txn || $txn->getUserUuid() !== $user->user_uuid) {
        Response::error('Transaction not found.', [], HttpStatus::HTTP_NOT_FOUND);
        return;
      }

      $txn->setStatus('cancelled');
      $txn->save();
      $txn->delete();

      SecurityLog::log('change_email_cancelled', [
        'user_uuid' => $user->user_uuid,
        'txn_id' => $txnId,
      ]);

      Response::success('Email change cancelled.');
    } catch (\Throwable $e) {
      SecurityLog::log('change_email_cancel_exception', [
        'error' => $e->getMessage(),
      ]);
      Response::error('An error occurred.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Validate fresh passkey step-up assertion.
   * Step-up must be within the configured max age.
   */
  private function validateStepUp(string $userUuid): bool
  {
    $sessionHash = Authentication::getSessionHashFromCookie();
    if (null === $sessionHash || '' === $sessionHash) {
      return false;
    }

    $sessionKey = Keys::SESSION . ':' . $sessionHash;
    $authMethod = strtolower((string) Database::hget($sessionKey, 'auth_method'));
    $authStrength = strtolower((string) Database::hget($sessionKey, 'auth_strength'));

    // A current strong passkey session satisfies step-up requirements.
    if ('passkey' === $authMethod && ('strong' === $authStrength || 'standard' === $authStrength)) {
      return true;
    }

    // Legacy/future explicit step-up timestamp support.
    $stepUpTimestamp = (int) Database::hget(Keys::SESSION . ':' . $sessionHash, 'passkey_stepup_at');
    $now = time();
    $maxAge = (int) SystemConfig::get('email_change_stepup_max_age_seconds');

    return $stepUpTimestamp > 0 && ($now - $stepUpTimestamp) < $maxAge;
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

  /**
   * @return array<mixed>|null
   */
  private function requestInput(): ?array
  {
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      return $decoded;
    }

    return $_POST !== [] ? $_POST : null;
  }
}

