<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Ordered diagnostic severities for TraceGate threshold checks.
 */
enum DiagnosticSeverity: string
{
  case Debug = 'debug';
  case Info = 'info';
  case Warn = 'warn';
  case Error = 'error';

  /**
   * Return the numeric ordering used for severity threshold checks.
   */
  public function rank(): int
  {
    return match ($this) {
      self::Debug => 10,
      self::Info => 20,
      self::Warn => 30,
      self::Error => 40,
    };
  }

  /**
   * Return whether this severity meets or exceeds the configured floor.
   */
  public function meetsFloor(self $floor): bool
  {
    return $this->rank() >= $floor->rank();
  }

  /**
   * Parse a user/config supplied severity string.
   */
  public static function fromString(string $value): ?self
  {
    $normalized = strtolower(trim($value));

    return match ($normalized) {
      'debug' => self::Debug,
      'info' => self::Info,
      'warn', 'warning' => self::Warn,
      'error' => self::Error,
      default => null,
    };
  }
}
