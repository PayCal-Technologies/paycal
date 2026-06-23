<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Argus Console — admin control panel snapshot and mutation helpers.
 */
final class ArgusConsole
{
  /**
   * @return array<string, mixed>
   */
  public static function snapshot(): array
  {
    $config = TraceGatePolicy::load();
    $runtime = ArgusPackageStore::snapshot();
    $envProfile = TraceGatePolicy::environmentProfile();

    return [
      'title' => 'Argus Console',
      'observer' => 'Argus',
      'policy_engine' => 'TraceGate',
      'mode' => 'capture_control_panel',
      'policy_version' => ConfigValue::string($config['version'] ?? '0', '0'),
      'policy_path' => TraceGatePolicy::configPath(),
      'active_environment' => TraceGatePolicy::activeEnvironment(),
      'environment_profile' => $envProfile,
      'defaults' => TraceGatePolicy::defaults(),
      'capture_limits' => TraceGatePolicy::captureLimits(),
      'master_enabled' => TraceGatePolicy::masterEffectiveStatus(),
      'master_gate_open' => TraceGatePolicy::isMasterEnabled(),
      'master_override' => $runtime['master_override'] ?? null,
      'master_expires_at' => $runtime['master_expires_at'] ?? null,
      'runtime' => $runtime,
      'presets' => ArgusPresetCatalog::all(),
      'duration_options' => ArgusExpiryPolicy::durationOptions(),
      'requires_expiry' => ArgusExpiryPolicy::requiresExpiry(),
      'package_groups' => TraceGatePolicy::consolePackageGroups(),
      'module_matrix' => TraceGatePolicy::consoleMatrix(),
      'current_trace_id' => ArgusRequestContext::traceId(),
      'production_allowed_events' => self::productionAllowedEvents(),
      'migration_status' => ConfigValue::string($config['migration_status'] ?? 'pending', 'pending'),
      'migration_notes' => is_array($config['migration_notes'] ?? null) ? $config['migration_notes'] : [],
      'related_systems' => [
        'lens' => 'dev-only request diagnostics (unchanged)',
        'shadow_talon' => 'fault/crash capture only',
        'the_ledger' => 'immutable audit chain only (not a diagnostics sink)',
        'security_log' => 'security sink adapter target',
      ],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function setMasterEnabled(
    bool $enabled,
    string $adminUuid,
    int $durationMinutes = 0,
    bool $adminOverride = false,
  ): array {
    $expiresAt = $enabled
      ? ArgusExpiryPolicy::resolveExpiresAt($durationMinutes, $adminOverride)
      : null;
    ArgusPackageStore::setMasterEnabled($enabled, $adminUuid, $expiresAt);

    return self::snapshot();
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function setPackageEnabled(
    string $moduleId,
    bool $enabled,
    string $adminUuid,
    int $durationMinutes = 0,
    bool $adminOverride = false,
  ): ?array {
    if (!TraceGatePolicy::isKnownModule($moduleId)) {
      return null;
    }

    if ($enabled && ArgusExpiryPolicy::requiresExpiry() && $durationMinutes <= 0 && !$adminOverride) {
      $durationMinutes = 60;
    }

    $expiresAt = $enabled
      ? ArgusExpiryPolicy::resolveExpiresAt($durationMinutes, $adminOverride)
      : null;

    ArgusPackageStore::setPackageEnabled($moduleId, $enabled, $adminUuid, $expiresAt);

    return self::snapshot();
  }

  /**
   * @param array<string, mixed> $scopeRaw
   * @return array<string, mixed>
   */
  public static function setCaptureScope(array $scopeRaw, string $adminUuid): array
  {
    ArgusPackageStore::setCaptureScope(ArgusCaptureScope::fromArray($scopeRaw), $adminUuid);

    return self::snapshot();
  }

  /**
   * @return array<string, mixed>|null
   */
  public static function applyPreset(
    string $presetId,
    string $adminUuid,
    int $durationMinutes = 0,
    bool $adminOverride = false,
  ): ?array {
    $preset = ArgusPresetCatalog::get($presetId);
    if ($preset === null) {
      return null;
    }

    if (ArgusExpiryPolicy::requiresExpiry() && $durationMinutes <= 0 && !$adminOverride) {
      $durationMinutes = 60;
    }

    $expiresAt = ArgusExpiryPolicy::resolveExpiresAt($durationMinutes, $adminOverride);
    ArgusPackageStore::enablePreset($preset['modules'], $adminUuid, $expiresAt);
    ArgusPackageStore::setMasterEnabled(true, $adminUuid, $expiresAt);

    return self::snapshot();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public static function timelineForTrace(string $traceId): array
  {
    return TraceTimelineStore::eventsForTrace($traceId);
  }

  /**
   * @return array<int, string>
   */
  private static function productionAllowedEvents(): array
  {
    $config = TraceGatePolicy::load();
    $events = is_array($config['events'] ?? null) ? $config['events'] : [];
    $out = [];

    foreach ($events as $eventName => $eventConfig) {
      if (!is_string($eventName) || !is_array($eventConfig)) {
        continue;
      }
      if ($eventConfig['production_allowed'] ?? false) {
        $out[] = $eventName;
      }
    }

    sort($out);

    return $out;
  }
}
