<?php declare(strict_types=1);

namespace PayCal\Observability\Sink;

/**
 * Resolves sink implementations by TraceGate sink id.
 */
final class DiagnosticSinkRegistry
{
  /** @var array<string, DiagnosticSinkInterface> */
  private static array $sinks = [];

  /**
   * Return a sink implementation by identifier.
   */
  public static function get(string $sinkId): DiagnosticSinkInterface
  {
    self::boot();

    return self::$sinks[$sinkId] ?? self::$sinks['none'];
  }

  /**
   * Initialize the default sink registry.
   */
  public static function boot(): void
  {
    if (self::$sinks !== []) {
      return;
    }

    $null = new NullSink();
    self::$sinks = [
      'none' => $null,
      'file' => new FileSink(),
      'security' => new SecurityLogSink(),
    ];
  }

  /**
   * Reset sink registry state for isolated tests.
   */
  public static function resetForTests(): void
  {
    self::$sinks = [];
    NullSink::resetSuppressedCountForTests();
  }
}
