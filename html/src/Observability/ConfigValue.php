<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Typed reads from diagnostic-policy and other mixed config arrays.
 */
final class ConfigValue
{
  /**
   * Read a scalar value as a trimmed string.
   */
  public static function string(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? trim((string) $value) : $default;
  }

  /**
   * Read a numeric value as an integer.
   */
  public static function int(mixed $value, int $default = 0): int
  {
    return is_numeric($value) ? (int) $value : $default;
  }
}
