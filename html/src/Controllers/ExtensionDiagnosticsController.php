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
 * ExtensionDiagnosticsController
 *
 * Admin diagnostics surface for extension runtime visibility.
 */
final class ExtensionDiagnosticsController
{
  #[Route('extensions/runtime', ['GET'])]
  #[ExtensionDiagnostics('runtime-manifests')]
  /**
   * Handles runtimeSnapshot operation.
   */
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

