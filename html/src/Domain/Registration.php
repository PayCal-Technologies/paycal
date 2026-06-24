<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Registration.php
 *
 * Purpose: Domain-level account-registration workflow entry point and policy
 * surface for account-creation behavior.
 *
 * Developer notes:
 * - Registration policy is passkey-only.
 * - Keep this class as the single place that explains and enforces current
 *   registration availability/state.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
/**
 * Registration domain flow.
 *
 * Responsibilities:
 * - Define current registration behavior and availability.
 * - Validate input/contracts for account-creation attempts.
 * - Return a stable result shape to controller callers.
 */
final class Registration
{
  /**
   * @param array<string, string> $inputData
   *
   * @return array{success: bool, message: string, userUUID: ?string}
   */
  public static function register(array $inputData): array
  {
    return [
      'success' => false,
      'message' => 'Use passkey signup at /auth/.',
      'userUUID' => null,
    ];
  }
}
