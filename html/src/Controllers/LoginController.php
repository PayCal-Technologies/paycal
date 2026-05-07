<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Response;
use PayCal\Observability\Lens;

/**
 * LoginController.php
 *
 * Purpose: Minimal authentication endpoint surface retained for login-related
 * responses and migration-era compatibility behavior.
 *
 * Developer notes:
 * - Password login is intentionally disabled, so this controller primarily
 *   exists to provide a clear API contract for that state.
 * - Keep any future auth-path additions aligned with the passkey-first model.
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
 * Login API surface.
 *
 * Responsibilities:
 * - Return deterministic responses for login-related calls.
 * - Preserve passkey-first auth posture without legacy ambiguity.
 */
final class LoginController
{
    /**
     * POST auth/login
     *
     * Password-based login is permanently disabled; this endpoint exists only to
     * return a clear error directing users to passkey authentication.
     */
    #[\PayCal\Domain\Attributes\Route('auth/login', ['POST'])]
    public function login(): void
    {
        Lens::boot('auth/login');
        Lens::add('Password auth disabled', ['endpoint' => 'auth/login'], 'warning');
        Response::error('Password authentication is disabled. Use passkey login at /auth/.', [], HttpStatus::HTTP_BAD_REQUEST);
    }
}

