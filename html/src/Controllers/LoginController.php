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
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
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
    /**
     * Handles login operation.
     */
    public function login(): void
    {
        Lens::boot('auth/login');
        Lens::add('Password auth disabled', ['endpoint' => 'auth/login'], 'warning');
        Response::error('Password authentication is disabled. Use passkey login at /auth/.', [], HttpStatus::HTTP_BAD_REQUEST);
    }
}

