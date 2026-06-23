<?php declare(strict_types=1);

namespace PayCal\Observability\Sink;

use PayCal\Observability\DiagnosticEvent;
use PayCal\Observability\TraceGateDecision;

/**
 * Diagnostic sink contract. Implementations must be side-effect safe when disabled.
 */
interface DiagnosticSinkInterface
{
  /**
   * Return the registry identifier for this sink.
   */
  public function id(): string;

  /**
   * Write a diagnostic event accepted by TraceGate.
   */
  public function write(DiagnosticEvent $event, TraceGateDecision $decision): void;
}
