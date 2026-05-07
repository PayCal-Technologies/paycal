<?php declare(strict_types=1);

namespace PayCal\Domain\Extensions\Bridges;

use PayCal\Domain\Attributes\ExtensionBootstrap;

/**
 * ExtensionBootstrapBridge
 *
 * Isolates configuration bootstrap from direct extension system coupling.
 * Provides safe extension initialization with error handling.
 */
final class ExtensionBootstrapBridge
{
  /**
   * Initialize extension runtime if bootstrap file exists
   *
   * Safely loads the extension bootstrap file with error handling.
   * Failures are logged but do not halt application startup.
   */
  #[ExtensionBootstrap('runtime')]
  public static function initialize(): void
  {
    $bootstrapPath = __DIR__ . '/../../../../extensions/bootstrap.php';
    if (!is_file($bootstrapPath)) {
      return;
    }

    try {
      require_once $bootstrapPath;
    } catch (\Throwable $error) {
      error_log('[PayCal Extensions] Bootstrap failed: ' . $error->getMessage());
    }
  }
}

