<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Config\Environment;

/**
 * Log.php
 *
 * Purpose: Internal logging utility for debug, error, trace, access, and info
 * file output with environment-aware path management.
 *
 * Developer notes:
 * - Logging is cross-cutting; format and path changes can affect diagnostics,
 *   tooling, and production troubleshooting.
 * - Keep logging helpers side-effect-light and avoid recursive logging paths in
 *   internal helper methods.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * File-based logging utility.
 *
 * Responsibilities:
 * - Route messages to the correct log sinks.
 * - Normalize log formatting and timestamp behavior.
 * - Support lightweight diagnostics without surprising call-site behavior.
 */
final class Log
{
  private const MAX_LOG_PATH_LENGTH = 255;

  // Log file path accessors (from Environment)
  /**
   * Handles debugLogPath operation.
   */
  private static function debugLogPath(): string { return \PayCal\Domain\Config\Environment::appHome() . 'logs/debug.log'; }
  /**
   * Handles errorLogPath operation.
   */
  private static function errorLogPath(): string { return \PayCal\Domain\Config\Environment::appHome() . 'logs/error.log'; }
  /**
   * Handles accessLogPath operation.
   */
  private static function accessLogPath(): string { return \PayCal\Domain\Config\Environment::appHome() . 'logs/access.log'; }
  /**
   * Handles infoLogPath operation.
   */
  private static function infoLogPath(): string { return \PayCal\Domain\Config\Environment::appHome() . 'logs/info.log'; }

  /**
   * Log a debug message.
   * Writes debug-level messages for breakpoint-style, rich variable debugging.
   *
   * @param string $message Debug message to write to debug log file
   */
  public static function debug(string $message = ''): void
  {
    self::write(self::debugLogPath(), $message);
  }

  /**
   * Log an error message.
   * Writes error-level messages for breakpoint-style, rich variable debugging.
   *
   * @param string $message Error message to write to error log file
   * @param string $context Optional additional context to append to message
   */
  public static function error(string $message = '', string $context = ''): void
  {
    $fullMessage = '' !== $context ? "{$message} {$context}" : $message;
    self::write(self::errorLogPath(), $fullMessage);
  }

  /**
   * Log an informational message.
   * Writes info-level messages for milestones and state changes.
   *
   * @param string $message Info message to write to info log file
   * @param string $context Optional additional context to append to message
   */
  public static function info(string $message = '', string $context = ''): void
  {
    $fullMessage = '' !== $context ? "{$message} {$context}" : $message;
    self::write(self::infoLogPath(), $fullMessage);
  }

  /**
   * Log a warning message.
   * Writes warning-level messages for non-fatal oddities and unexpected conditions.
   *
   * @param string $message Warning message to write to access log file
   */
  public static function warn(string $message = ''): void
  {
    self::write(self::accessLogPath(), $message);
  }

  /**
   * Log a warning message (alias for warn()).
   * Writes warning-level messages for non-fatal oddities and unexpected conditions.
   *
   * @param string $message Warning message to write to access log file
   */
  public static function warning(string $message = ''): void
  {
    self::warn($message);
  }

  /**
   * Timestamp with microseconds UTC.
   */
  private static function now(): string
  {
    $dt = new \DateTime('now', new \DateTimeZone('UTC'));

    return $dt->format('Y-m-d H:i:s');
  }

  /**
   * Internal: central write.
   */
  private static function write(string $file, string $message): void
  {
    $maxLen = SystemConfig::MAX_STRING_LENGTH;
    if (strlen($message) > $maxLen) {
      $message = substr($message, 0, $maxLen) . ' [TRUNCATED]';
    }
    if (strlen($file) > self::MAX_LOG_PATH_LENGTH) {
      throw new \RuntimeException('Log: log file path exceeds maximum allowed length of ' . self::MAX_LOG_PATH_LENGTH . ' bytes');
    }
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0o755, true) && !is_dir($dir)) {
      throw new \RuntimeException('Log: unable to create log directory: ' . $dir);
    }
    if (!is_writable($dir)) {
      throw new \RuntimeException('Log: log directory is not writable: ' . $dir);
    }
    @file_put_contents(
      $file,
      self::now()
       .' '.Security::getClientIPAddress()
       ." {$message}".PHP_EOL,
      FILE_APPEND
    );
  }
}

