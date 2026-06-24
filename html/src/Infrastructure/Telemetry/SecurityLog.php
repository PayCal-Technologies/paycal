<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Telemetry;

use PayCal\Domain\Attributes\ExtensionHook;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\FormTTL;
use PayCal\Domain\ExtensionHookBridge;
use PayCal\Domain\Log;
use PayCal\Observability\Argus;
use PayCal\Observability\DiagnosticSeverity;
use PayCal\Observability\SecurityEventCatalog;
use PayCal\Observability\TraceGatePolicy;

/**
 * SecurityLog.php
 *
 * Low-level security event writer. Prefer Argus::emit() for new instrumentation.
 * SecurityLogSink calls writeRecord() directly to avoid policy recursion.
 */
final class SecurityLog
{
  /**
   * @param array<string, scalar|null> $context
   */
  #[ExtensionHook('security.audit_event')]
  public static function log(string $event, array $context = []): void
  {
    $mapped = SecurityEventCatalog::resolve($event);
    if ($mapped !== null) {
      if (Argus::emit($mapped['name'], $mapped['severity'], $context)) {
        return;
      }

      if (!TraceGatePolicy::isDevEnvironment()) {
        return;
      }
    } elseif (!TraceGatePolicy::isDevEnvironment()) {
      return;
    }

    self::writeRecord($event, $context);
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function writeRecord(string $event, array $context = []): void
  {
    $timestamp = time();
    $eventKey = 'security:event:' . $event;

    try {
      Database::incr($eventKey);
      Database::expire($eventKey, FormTTL::ONE_DAY->value);
    } catch (\Throwable) {
    }

    $payload = [
      'ts' => $timestamp,
      'event' => $event,
      'context' => $context,
    ];

    ExtensionHookBridge::dispatch('security.audit_event', [
      'event' => $event,
      'context' => $context,
      'timestamp' => $timestamp,
    ]);

    Log::error('[SECURITY] ' . json_encode($payload));
  }

  /**
   * Log rate limit triggered.
   */
  public static function logRateLimitTriggered(string $scope, string $identifier, int $remaining): void
  {
    Argus::emit('request_guard.rate_limit_triggered', DiagnosticSeverity::Warn, [
      'scope' => $scope,
      'remaining' => (string) $remaining,
    ]);
  }

  /**
   * Log entry locked attempt.
   */
  public static function logEntryLockedAttempt(string $userUUID, string $date): void
  {
    Argus::emit('lock_boundary.mutation_blocked', DiagnosticSeverity::Warn, [
      'date' => $date,
    ]);
  }

}
