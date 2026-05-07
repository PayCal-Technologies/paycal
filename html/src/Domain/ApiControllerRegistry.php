<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Controllers\AdminController;
use PayCal\Controllers\AccountController;
use PayCal\Controllers\AccountRecoveryController;
use PayCal\Controllers\BillingController;
use PayCal\Controllers\CalendarController;
use PayCal\Controllers\ChangeEmailController;
use PayCal\Controllers\DEKController;
use PayCal\Controllers\EmailVerificationController;
use PayCal\Controllers\EarningsController;
use PayCal\Controllers\EncryptionController;
use PayCal\Controllers\ExtensionDiagnosticsController;
use PayCal\Controllers\HealthController;
use PayCal\Controllers\KekController;
use PayCal\Controllers\LoginController;
use PayCal\Controllers\OrganizationDiscoveryController;
use PayCal\Controllers\PasskeyController;
use PayCal\Controllers\RecoveryEmailController;
use PayCal\Controllers\SecurityController;
use PayCal\Controllers\SettingsController;
use PayCal\Controllers\SitesController;
use PayCal\Controllers\Soc2StatusController;
use PayCal\Controllers\UserController;

/**
 * ApiControllerRegistry.php
 *
 * Purpose: Canonical registry of API controller classes used by route/attribute
 * discovery and API dispatch initialization.
 *
 * Developer notes:
 * - Keep this list explicit so API surface changes are review-visible.
 * - Admin/extension diagnostics controllers remain gated by AdminSurface.
 *
 * Architectural role:
 * - Reusable domain registry that exposes the complete API controller set to
 *   bootstrap and route-discovery code.
 * - Encapsulates controller enumeration outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */
final class ApiControllerRegistry
{
  /** @return array<int, class-string> */
  public static function controllers(): array
  {
    $controllers = [
      AccountController::class,
      AccountRecoveryController::class,
      BillingController::class,
      CalendarController::class,
      ChangeEmailController::class,
      DEKController::class,
      EmailVerificationController::class,
      EarningsController::class,
      EncryptionController::class,
      HealthController::class,
      KekController::class,
      LoginController::class,
      OrganizationDiscoveryController::class,
      PasskeyController::class,
      RecoveryEmailController::class,
      SecurityController::class,
      SettingsController::class,
      SitesController::class,
      Soc2StatusController::class,
      UserController::class,
    ];

    if (AdminSurface::isEnabled()) {
      $controllers[] = AdminController::class;
      $controllers[] = ExtensionDiagnosticsController::class;
    }

    return $controllers;
  }
}
