<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * TraceGate — policy decision engine for Argus diagnostic emission.
 *
 * Production defaults to deny unless an event is explicitly production_allowed.
 * Dev/mac environments may emit to the file sink when module + severity allow.
 */
final class TraceGate
{
  /**
   * Evaluate whether a diagnostic event may be emitted.
   */
  public static function evaluate(DiagnosticEvent $event): TraceGateDecision
  {
    $envProfile = TraceGatePolicy::environmentProfile();
    $moduleProfile = TraceGatePolicy::moduleProfile($event->module);
    $eventProfile = TraceGatePolicy::eventProfile($event->name);

    if ($moduleProfile === [] && $eventProfile === []) {
      return TraceGateDecision::deny('unknown_module');
    }

    if (!TraceGatePolicy::isMasterEnabled()) {
      return TraceGateDecision::deny('master_disabled');
    }

    if (!TraceGatePolicy::moduleIsEnabled($event->module)) {
      return TraceGateDecision::deny('module_disabled');
    }

    if (!TraceGatePolicy::scopeAllowsCapture()) {
      return TraceGateDecision::deny('scope_mismatch');
    }

    $eventProdAllowed = (bool) ($eventProfile['production_allowed'] ?? false);
    $moduleProdAllowed = (bool) ($moduleProfile['production_allowed'] ?? false);

    if (TraceGatePolicy::isDevEnvironment()) {
      $envEnabled = (bool) ($envProfile['enabled'] ?? TraceGatePolicy::defaults()['enabled'] ?? false);
      if (!$envEnabled && $eventProfile === [] && !$moduleProdAllowed) {
        return TraceGateDecision::deny('environment_disabled');
      }
    } elseif (!$eventProdAllowed && !$moduleProdAllowed) {
      return TraceGateDecision::deny('production_not_allowed');
    }

    $floor = self::resolveSeverityFloor($envProfile, $moduleProfile, $eventProfile);

    if (!$event->severity->meetsFloor($floor)) {
      return TraceGateDecision::deny('below_severity_floor');
    }

    $sink = self::resolveSink($envProfile, $moduleProfile, $eventProfile);
    if ($sink === 'none') {
      return TraceGateDecision::deny('sink_none');
    }

    if ($sink === 'file' && !TraceGatePolicy::isDevEnvironment()) {
      return TraceGateDecision::deny('file_sink_dev_only');
    }

    $securityLogEvent = '';
    if ($sink === 'security') {
      $securityLogEvent = ConfigValue::string(
        $eventProfile['security_log_event'] ?? '',
        self::defaultSecurityLogEvent($event->name),
      );
    }

    return new TraceGateDecision(true, $sink, 'allowed', $securityLogEvent);
  }

  /**
   * @param array<string, mixed> $envProfile
   * @param array<string, mixed> $moduleProfile
   * @param array<string, mixed> $eventProfile
   */
  private static function resolveSeverityFloor(
    array $envProfile,
    array $moduleProfile,
    array $eventProfile,
  ): DiagnosticSeverity {
    $candidates = [
      ConfigValue::string($moduleProfile['severity_floor'] ?? ''),
      ConfigValue::string($envProfile['severity_floor'] ?? ''),
      ConfigValue::string(TraceGatePolicy::defaults()['severity_floor'] ?? 'error', 'error'),
    ];

    foreach ($candidates as $candidate) {
      if ($candidate === '') {
        continue;
      }
      $severity = DiagnosticSeverity::fromString($candidate);
      if ($severity !== null) {
        return $severity;
      }
    }

    return DiagnosticSeverity::Error;
  }

  /**
   * @param array<string, mixed> $envProfile
   * @param array<string, mixed> $moduleProfile
   * @param array<string, mixed> $eventProfile
   */
  private static function resolveSink(array $envProfile, array $moduleProfile, array $eventProfile): string
  {
    $candidates = [
      ConfigValue::string($eventProfile['sink'] ?? ''),
      ConfigValue::string($moduleProfile['sink'] ?? ''),
      ConfigValue::string($envProfile['sink'] ?? ''),
      ConfigValue::string(TraceGatePolicy::defaults()['sink'] ?? 'none', 'none'),
    ];

    foreach ($candidates as $candidate) {
      $normalized = strtolower(trim($candidate));
      if (in_array($normalized, ['none', 'file', 'security'], true)) {
        return $normalized;
      }
    }

    return 'none';
  }

  /**
   * Derive the default SecurityLog event name for a diagnostic event.
   */
  private static function defaultSecurityLogEvent(string $eventName): string
  {
    return str_replace('.', '_', $eventName);
  }
}
