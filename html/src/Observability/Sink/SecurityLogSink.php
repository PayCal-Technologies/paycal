<?php declare(strict_types=1);

namespace PayCal\Observability\Sink;

use PayCal\Infrastructure\Telemetry\SecurityLog;
use PayCal\Observability\DiagnosticEvent;
use PayCal\Observability\TraceGateDecision;

/**
 * Adapter into the existing SecurityLog dual-path (Redis counter + file line + hook).
 *
 * Does not write to TheLedger. High-trust immutable audit remains SystemAuditRepository.
 */
final class SecurityLogSink implements DiagnosticSinkInterface
{
  /**
   * Return the registry identifier for the security-log sink.
   */
  public function id(): string
  {
    return 'security';
  }

  /**
   * Forward an accepted diagnostic event to SecurityLog.
   */
  public function write(DiagnosticEvent $event, TraceGateDecision $decision): void
  {
    $securityEvent = $decision->securityLogEvent !== ''
      ? $decision->securityLogEvent
      : str_replace('.', '_', $event->name);

    /** @var array<string, scalar|null> $context */
    $context = [
      'diagnostic_name' => $event->name,
      'diagnostic_module' => $event->module,
      'diagnostic_severity' => $event->severity->value,
      ...$event->context,
    ];

    if ($event->correlationId !== '') {
      $context['correlation_id'] = $event->correlationId;
    }

    SecurityLog::writeRecord($securityEvent, $context);
  }
}
