<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Registration;
use PayCal\Domain\Response;

/**
 * RegistrationController.php
 *
 * Purpose: Account-registration controller for creating new user accounts and
 * validating onboarding input at the HTTP boundary.
 *
 * Developer notes:
 * - Registration behavior affects authentication, verification, and baseline
 *   user settings, so keep it aligned with the domain Registration service.
 * - This controller should validate request shape and delegate business rules.
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
 * Registration API surface.
 *
 * Responsibilities:
 * - Accept and validate account-creation requests.
 * - Delegate account creation to the registration domain flow.
 * - Return consistent onboarding-ready responses.
 */
class RegistrationController
{
  /**
   * Handles user registration.
   */
  public static function register(): void
  {
    if ('POST' !== $_SERVER['REQUEST_METHOD']) {
      \PayCal\Domain\Response::error('[RC] Method rejected', [], HttpStatus::HTTP_METHOD_NOT_ALLOWED);

      return;
    }

    $input = self::collectInput();
    self::logInput($input);

    $result = Registration::register($input);
    self::logResult($result);

    if ($result['success'] && $result['userUUID'] !== null && $result['userUUID'] !== '') {
      try {
        \PayCal\Infrastructure\Audit\SystemAuditRepository::append('user.account.created', $result['userUUID'], [
          'method' => 'registration',
        ]);
      } catch (\Throwable) {
      }
    }

    self::redirectByResult($result);
  }

  /**
   * Collects registration form data.
   *
   * @return array<string, string>
   */
  private static function collectInput(): array
  {
    $fullName = InputSanitizer::postString('full_name');
    $email = InputSanitizer::postString('email');
    if ('' === $email) {
      $email = InputSanitizer::postString('register_email');
    }

    return [
        'full_name' => $fullName,
        'email' => $email,
        'password' => InputSanitizer::postString('password'),
        'confirm_password' => InputSanitizer::postString('confirm_password'),
        'invite_code' => InputSanitizer::postString('invite_code'),
    ];
  }

  /**
   * @param array<string, string> $input
   */
  private static function logInput(array $input): void
  {
    // Input logging removed
  }

  /**
   * @param array{success: bool, message: string, userUUID: ?string} $result
   */
  private static function logResult(array $result): void
  {
    // Result logging removed
  }

  /**
   * @param array{success: bool, message: string, userUUID: ?string} $result
   */
  private static function redirectByResult(array $result): void
  {
    if ($result['success']) {
      header('Location: /');
      exit;
    }

    self::redirect(
      '/auth/?auth_tab=register',
      'signin_error',
      $result['message']
    );
  }

  /**
   * Handles redirect operation.
   */
  private static function redirect(
    string $path,
    string $param,
    string $message
  ): void {
    $separator = str_contains($path, '?') ? '&' : '?';
    header("Location: {$path}{$separator}{$param}=".urlencode($message));

    exit;
  }
}

