<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * TelemetryPolicy
 *
 * Defines retention and access boundaries for telemetry streams.
 */
final class TelemetryPolicy
{
  public const STREAM_PRODUCT = 'product';
  public const STREAM_SECURITY = 'security';

  public const RETENTION_DAYS_PRODUCT = 30;
  public const RETENTION_DAYS_SECURITY = 90;

  /** @var array<string, true> */
  private const SECURITY_ACCESS_ROLE_SET = [
    'security' => true,
    'security-admin' => true,
    'forensics' => true,
  ];

  /** @var array<string, true> */
  private const PRODUCT_ACCESS_ROLE_SET = [
    'product' => true,
    'product-admin' => true,
    'security' => true,
    'security-admin' => true,
  ];

  /**
   * @return array{retention_days: int, access_boundary: string}
   */
  public static function describeStream(string $stream): array
  {
    $normalized = strtolower(trim($stream));

    if ($normalized === self::STREAM_SECURITY) {
      return [
        'retention_days' => self::RETENTION_DAYS_SECURITY,
        'access_boundary' => 'security-operations-only',
      ];
    }

    return [
      'retention_days' => self::RETENTION_DAYS_PRODUCT,
      'access_boundary' => 'product-observability-only',
    ];
  }

  /**
   * Handles canAccess operation.
   */
  public static function canAccess(string $stream, string $role): bool
  {
    $streamMeta = self::describeStream($stream);
    $normalizedRole = strtolower(trim($role));

    if ($streamMeta['access_boundary'] === 'security-operations-only') {
      return isset(self::SECURITY_ACCESS_ROLE_SET[$normalizedRole]);
    }

    return isset(self::PRODUCT_ACCESS_ROLE_SET[$normalizedRole]);
  }
}

