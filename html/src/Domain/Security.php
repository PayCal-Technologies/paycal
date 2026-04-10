<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;

/**
 * Security.php
 *
 * Purpose: Security utility helper for request-origin inspection, verification
 * code generation, and shared low-level security support behavior.
 *
 * Developer notes:
 * - This class contains low-level helpers used by multiple security-sensitive
 *   workflows, so subtle behavior changes can have wide impact.
 * - Proxy/IP trust behavior is especially sensitive and should remain explicit.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Shared security helper utility.
 *
 * Responsibilities:
 * - Resolve security-relevant request metadata safely.
 * - Provide common helper routines used by auth and verification flows.
 * - Centralize low-level security support behavior that should stay consistent.
 */
final class Security
{
  /**
   * Resolve client IP with trusted-proxy checks.
   *
   * Trusts forwarded headers only when REMOTE_ADDR is explicitly listed in
   * TRUSTED_PROXIES (comma-separated) environment variable.
   */
  public static function getClientIPAddress(): string
  {
    $remoteRaw = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $remote = InputSanitizer::sanitizeIPAddress(is_scalar($remoteRaw) ? (string) $remoteRaw : 'unknown');

    if ('unknown' === $remote) {
      return 'unknown';
    }

    if (!self::isTrustedProxy($remote)) {
      return $remote;
    }

    $forwarded = self::firstForwardedAddress();
    if ('' === $forwarded) {
      return $remote;
    }

    $sanitized = InputSanitizer::sanitizeIPAddress($forwarded);

    return 'unknown' !== $sanitized ? $sanitized : $remote;
  }

  /**
   * Determine the real IP Address of the website visitor.
   *
   * @return string $pAddress
   */
  public static function getVisitorRealIPAddress(): string
  {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      return InputSanitizer::sanitizeIPAddress($_SERVER['HTTP_CLIENT_IP']);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      return InputSanitizer::sanitizeIPAddress($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    return InputSanitizer::sanitizeIPAddress($_SERVER['REMOTE_ADDR'] ?? 'unknown');
  }

  /**
   * Handles isTrustedProxy operation.
   */
  private static function isTrustedProxy(string $remoteAddr): bool
  {
    $raw = getenv('TRUSTED_PROXIES');
    if (false === $raw || trim($raw) === '') {
      return false;
    }

    $trusted = array_filter(array_map('trim', explode(',', $raw)), static fn (string $value): bool => $value !== '');

    return in_array($remoteAddr, $trusted, true);
  }

  /**
   * Handles firstForwardedAddress operation.
   */
  private static function firstForwardedAddress(): string
  {
    $xffRaw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if (is_scalar($xffRaw) && (string) $xffRaw !== '') {
      $parts = explode(',', (string) $xffRaw);
      return trim($parts[0]);
    }

    $clientIpRaw = $_SERVER['HTTP_CLIENT_IP'] ?? '';

    return is_scalar($clientIpRaw) ? trim((string) $clientIpRaw) : '';
  }

  /**
   * Generates a random UPPERCASE verification code.
   *
   * @param int|null $length Optional. The length of the code. Defaults to SystemConfig::PC_VERIFICATION_LENGTH.
   * @return string The verification code of exact length (no separator)
   */
  public static function generateVerificationCode(?int $length = null): string
  {
    $set = \PayCal\Domain\Config\SystemConfig::PC_VERIFICATION_SET;
    $len = $length ?? \PayCal\Domain\Config\SystemConfig::PC_VERIFICATION_LENGTH;
    $maxLength = strlen($set);
    $code = '';
    for ($i = 0; $i < $len; ++$i) {
      $code .= $set[random_int(0, $maxLength - 1)];
    }
    return strtoupper($code);
  }
}

