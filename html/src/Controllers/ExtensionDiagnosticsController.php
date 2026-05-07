<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Attributes\ExtensionDiagnostics;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Extensions\Bridges\ExtensionDiagnosticsBridge;
use PayCal\Domain\Response;
use PayCal\Domain\User;

/**
 * ExtensionDiagnosticsController.php
 *
 * Purpose: Admin-only controller for extension runtime diagnostics, manifest
 * inspection, and compatibility visibility endpoints.
 *
 * Developer notes:
 * - Keep this surface read-only and tightly gated through AdminSurface.
 * - Runtime diagnostics should reflect bridge state, not reimplement it here.
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
 * Extension diagnostics API surface.
 *
 * Responsibilities:
 * - Expose admin-only runtime and manifest diagnostics for extension infrastructure.
 * - Delegate runtime state collection to ExtensionDiagnosticsBridge.
 * - Keep diagnostics read-only and gated through admin access checks.
 */
final class ExtensionDiagnosticsController
{
  #[Route('extensions/runtime', ['GET'])]
  #[ExtensionDiagnostics('runtime-manifests')]
  public function runtimeSnapshot(): void
  {
    if (!AdminSurface::userCanAccess()) {
      Response::error('[Extensions] Admin access required.', [], HttpStatus::HTTP_FORBIDDEN);
      return;
    }

    ExtensionDiagnosticsBridge::boot();

    Response::success('[Extensions] Runtime snapshot retrieved.', [
      'active_manifests' => ExtensionDiagnosticsBridge::activeManifests(),
      'discovered_manifests' => ExtensionDiagnosticsBridge::discoveredManifests(),
      'capabilities' => ExtensionDiagnosticsBridge::capabilityManifest(),
      'hook_listeners' => ExtensionDiagnosticsBridge::listenersSummary(),
      'generated_at' => date('c'),
    ], HttpStatus::HTTP_OK);
  }
}

