<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Observability\Sink\DiagnosticSinkRegistry;

/**
 * Argus — request-time diagnostic observer (superhero facade).
 *
 * Not a background daemon. Emits structured events through TraceGate policy
 * into configured sinks. Defaults to no-op in production unless explicitly allowed.
 */
final class Argus
{
  private static string $correlationId = '';

  /**
   * Set the correlation identifier used for emitted events.
   */
  public static function setCorrelationId(string $correlationId): void
  {
    self::$correlationId = trim($correlationId);
  }

  /**
   * Return the current correlation identifier.
   */
  public static function correlationId(): string
  {
    return self::$correlationId;
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function emit(
    string $name,
    DiagnosticSeverity $severity,
    array $context = [],
  ): bool {
    try {
      if (!ArgusEventBudget::allow()) {
        return false;
      }

      $redacted = DiagnosticRedactor::redact($context);
      if ($redacted === null) {
        return false;
      }

      $traceId = ArgusRequestContext::traceId();
      $spanId = ArgusRequestContext::spanId();
      $correlationId = self::$correlationId !== '' ? self::$correlationId : $traceId;

      $event = DiagnosticEvent::create(
        $name,
        $severity,
        $redacted,
        $correlationId,
        $traceId,
        $spanId,
      );

      if ($event === null) {
        return false;
      }

      $decision = TraceGate::evaluate($event);
      if (!$decision->allowed) {
        DiagnosticSinkRegistry::get('none')->write($event, $decision);

        return false;
      }

      DiagnosticSinkRegistry::get($decision->sink)->write($event, $decision);
      TraceTimelineStore::append($event);
      ArgusEventBudget::recordEmission();

      return true;
    } catch (\Throwable) {
      return false;
    }
  }

  /**
   * @return array<string, mixed>
   */
  public static function status(): array
  {
    return ArgusConsole::snapshot();
  }
}
