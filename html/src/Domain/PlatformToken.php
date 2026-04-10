<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Class PlatformToken
 *
 * Detects and normalises the client's operating-system platform token from
 * Sec-CH-UA-Platform header hints or User-Agent string fallback.
 * Returns a canonical token from the MAC / WIN / LINUX / IOS / ANDROID / UNKNOWN set.
 */
final class PlatformToken
{
  public const MAC = 'mac';
  public const WIN = 'win';
  public const LINUX = 'linux';
  public const IOS = 'ios';
  public const ANDROID = 'android';
  public const UNKNOWN = 'unknown';

  /** @var array<string, true> */
  private const VALID_TOKEN_SET = [
    self::MAC => true,
    self::WIN => true,
    self::LINUX => true,
    self::IOS => true,
    self::ANDROID => true,
    self::UNKNOWN => true,
  ];

  /**
   * Handles detect operation.
   */
  public static function detect(): string
  {
    $clientHint = self::fromClientHint(self::serverString('HTTP_SEC_CH_UA_PLATFORM'));
    if ($clientHint !== self::UNKNOWN) {
      return $clientHint;
    }

    $userAgent = strtolower(self::serverString('HTTP_USER_AGENT'));
    if ($userAgent === '') {
      return self::UNKNOWN;
    }

    return self::fromSource($userAgent);
  }

  /**
   * Handles normalize operation.
   */
  public static function normalize(string $value): string
  {
    $normalized = strtolower(trim($value, " \t\n\r\0\x0B\"'"));

    return isset(self::VALID_TOKEN_SET[$normalized])
      ? $normalized
      : self::UNKNOWN;
  }

  /**
   * Handles fromClientHint operation.
   */
  private static function fromClientHint(string $value): string
  {
    $normalized = strtolower(trim($value, " \t\n\r\0\x0B\"'"));
    if ($normalized === '') {
      return self::UNKNOWN;
    }

    return self::fromSource($normalized);
  }

  /**
   * Handles fromSource operation.
   */
  private static function fromSource(string $value): string
  {
    $haystack = strtolower($value);

    if (str_contains($haystack, 'iphone') || str_contains($haystack, 'ipad')) {
      return self::IOS;
    }

    if (str_contains($haystack, 'android')) {
      return self::ANDROID;
    }

    if (str_contains($haystack, 'macintosh') || str_contains($haystack, 'mac os x') || str_contains($haystack, 'macos')) {
      return self::MAC;
    }

    if (str_contains($haystack, 'windows') || str_contains($haystack, 'win32') || str_contains($haystack, 'win64')) {
      return self::WIN;
    }

    if (str_contains($haystack, 'linux') || str_contains($haystack, 'x11')) {
      return self::LINUX;
    }

    return self::UNKNOWN;
  }

  /**
   * Handles serverString operation.
   */
  private static function serverString(string $key): string
  {
    $value = $_SERVER[$key] ?? '';

    return is_scalar($value) ? (string) $value : '';
  }
}

