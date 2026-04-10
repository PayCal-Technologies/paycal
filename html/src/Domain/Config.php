<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Config.php
 *
 * Purpose: Legacy configuration bootstrap helper for loading constants and
 * defining string constants used by older bootstrap/runtime paths.
 *
 * Developer notes:
 * - This class is bootstrap-oriented and should remain side-effect predictable.
 * - Prefer newer typed config facades for runtime reads where available.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Legacy configuration loader.
 *
 * Responsibilities:
 * - Load bootstrap constants for compatibility paths.
 * - Define string constants only when not already defined.
 */
class Config
{
  /**
   * Loads configuration, including constants from bootstrap/constants.php.
   * Acts as a central loader for app constants and settings.
   *
   * @param null|string $file Optional file to load (defaults to constants.php).
   * @return void
   */
  public static function load(?string $file = null): void
  {
    $defaultFile = __DIR__.'/../../bootstrap/constants.php';
    $fileToLoad = $file ?: $defaultFile;

    if (file_exists($fileToLoad)) {
      require_once $fileToLoad;
    }
  }

  /**
   * Create multiple string constants from an associative array.
   * Calls createStringConstant() for each key-value pair.
   *
   * @param array<string, string> $strings Associative array of constant name => value pairs
   */
  public static function createStringConstants(array $strings): void
  {
    foreach ($strings as $name => $value) {
      self::createStringConstant($name, $value);
    }
  }

  /**
   * Creates a constant if not already defined.
   *
   * @param string $name  constant name
   * @param string $value constant value
   */
  public static function createStringConstant(string $name, string $value): void
  {
    if (!defined($name)) {
      define($name, $value);
    }
  }
}
