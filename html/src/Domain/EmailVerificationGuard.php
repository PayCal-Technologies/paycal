<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Enums\HttpStatus;

/**
 * EmailVerificationGuard.php
 *
 * Purpose: Define the EmailVerificationGuard class for PayCal\Domain.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Class EmailVerificationGuard
 *
 * Provides middleware to block actions that require email verification.
 * Used to prevent unverified users from writing data to the system.
 */
final class EmailVerificationGuard
{
  /**
   * Require email verification before proceeding
   *
   * Checks if the current user has verified their email.
   * If not verified, sends 403 Forbidden response and exits.
   *
   * @return void Exits if user not verified
   */
  public static function requireVerified(): void
  {
    $user = User::current();

    if (!$user->email_verified) {
      Response::error(
        'Email verification required before using PayCal. Please check your email for the verification link.',
        [
            'email_verified' => false,
        ],
        HttpStatus::HTTP_FORBIDDEN
      );

      // Response::error calls exit, but explicit for clarity
      exit;
    }
  }

  /**
   * Check if current user is verified (non-blocking)
   *
   * @return bool True if user is verified
   */
  public static function isVerified(): bool
  {
    $user = User::current();

    return $user->email_verified;
  }
}
