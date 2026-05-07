<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Response;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\User;

/**
 * UserController.php
 *
 * Purpose: User-focused API layer for authenticated profile data and direct
 * user-bound operations not owned by more specialized controllers.
 *
 * Developer notes:
 * - Keep this controller narrow; specialized domains should stay in their own
 *   controllers rather than accumulating generic user endpoints here.
 * - Prefer repository/domain helpers for user reads and writes instead of
 *   direct storage access.
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
 * User API surface.
 *
 * Responsibilities:
 * - Expose authenticated user-bound endpoints with stable response contracts.
 * - Keep generic profile/user operations thin and delegated to domain helpers.
 */
class UserController
{
  /**
   * Constructor. Aborts with 401 if the request is not authenticated.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();
  }

  /**
   * Search for users by name or email.
   */
  #[Route('users/search', ['GET'])]
  public function searchUsers(): void
  {
    $query = InputSanitizer::getString('q') ?? '';

    if (strlen($query) < 2) {
      Response::success(
        '[UserC] Search query too short.',
        [],
        HttpStatus::HTTP_OK
      );

      return;
    }

    // Search for users by name or email in Redis
    $userKeys = \PayCal\Database::scanKeys(Keys::USER.':*');
    $results = [];
    $queryLower = strtolower($query);

    foreach ($userKeys as $key) {
      // Extract UUID from key (e.g., "user:uuid" -> "uuid")
      $uuid = str_replace(Keys::USER.':', '', $key);

      // Get user data
      $userData = \PayCal\Database::hgetall($key);

      if (empty($userData)) {
        continue;
      }

      $name = $userData['full_name'] ?? '';
      $email = $userData['email'] ?? '';

      // Skip the current user
      if (User::currentUUID() === $uuid) {
        continue;
      }

      // Check if query matches name or email
      if (false === stripos($name, $queryLower) && false === stripos($email, $queryLower)) {
        continue;
      }

      $results[] = [
          'uuid' => $uuid,
          'name' => $name,
          'email' => $email,
      ];

      // Limit results to 10
      if (count($results) >= SystemConfig::MAX_USER_RESULTS) {
        break;
      }
    }

    Response::success(
      '[UserC] Users search completed.',
      ['users' => $results],
      HttpStatus::HTTP_OK
    );
  }
}

