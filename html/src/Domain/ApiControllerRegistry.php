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
 */
/**
 * API controller registry.
 *
 * Responsibilities:
 * - Provide the complete set of discoverable API controller classes.
 * - Gate optional admin controllers based on runtime admin-surface policy.
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
      UserController::class,
    ];

    if (AdminSurface::isEnabled()) {
      $controllers[] = AdminController::class;
      $controllers[] = ExtensionDiagnosticsController::class;
    }

    return $controllers;
  }
}
