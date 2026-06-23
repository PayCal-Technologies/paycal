<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Domain\Config\Environment;

/**
 * Loads and exposes config/diagnostic-policy.php for TraceGate and Argus Console.
 */
final class TraceGatePolicy
{
  /** @var array<string, mixed>|null */
  private static ?array $config = null;

  /**
   * Repo root.
   */
  public static function repoRoot(): string
  {
    return dirname(__DIR__, 3);
  }

  /**
   * Config path.
   */
  public static function configPath(): string
  {
    return self::repoRoot() . '/config/diagnostic-policy.php';
  }

  /** @return array<string, mixed> */
  public static function load(): array
  {
    if (self::$config !== null) {
      return self::$config;
    }

    $path = self::configPath();
    if (!is_file($path)) {
      self::$config = [];

      return self::$config;
    }

    /** @var array<string, mixed> $loaded */
    $loaded = require $path;
    self::$config = $loaded;

    return self::$config;
  }

  /**
   * Reset for tests.
   */
  public static function resetForTests(): void
  {
    self::$config = null;
    ArgusPackageStore::resetForTests();
    ArgusRequestContext::resetForTests();
    ArgusEventBudget::resetForTests();
    TraceTimelineStore::resetForTests();
  }

  /**
   * @return array<string, mixed>
   */
  public static function captureLimits(): array
  {
    $config = self::load();
    $limits = is_array($config['capture_limits'] ?? null) ? $config['capture_limits'] : [];

    return [
      'max_events_per_request' => ConfigValue::int($limits['max_events_per_request'] ?? null, 100),
      'max_context_keys' => ConfigValue::int($limits['max_context_keys'] ?? null, 32),
      'max_string_length' => ConfigValue::int($limits['max_string_length'] ?? null, 256),
      'max_payload_bytes' => ConfigValue::int($limits['max_payload_bytes'] ?? null, 8192),
    ];
  }

  /**
   * Scope allows capture.
   */
  public static function scopeAllowsCapture(): bool
  {
    $scope = ArgusPackageStore::captureScope();
    if ($scope->isGlobal()) {
      return true;
    }

    return $scope->matches(ArgusRequestContext::captureScope());
  }

  /**
   * Is known module.
   */
  public static function isKnownModule(string $module): bool
  {
    $normalized = strtolower(trim($module));
    if ($normalized === '') {
      return false;
    }

    $config = self::load();
    $modules = is_array($config['modules'] ?? null) ? $config['modules'] : [];

    return is_array($modules[$normalized] ?? null);
  }

  /**
   * Is master enabled.
   */
  public static function isMasterEnabled(): bool
  {
    $override = ArgusPackageStore::masterEnabled();
    if ($override === false) {
      return false;
    }

    // Unset or forced-on: environment and package policy decide emission.
    return true;
  }

  /**
   * Master effective status.
   */
  public static function masterEffectiveStatus(): bool
  {
    $override = ArgusPackageStore::masterEnabled();
    if ($override !== null) {
      return $override;
    }

    $envProfile = self::environmentProfile();

    return (bool) ($envProfile['enabled'] ?? self::defaults()['enabled'] ?? false);
  }

  /**
   * Module is enabled.
   */
  public static function moduleIsEnabled(string $module): bool
  {
    if (!self::isKnownModule($module)) {
      return false;
    }

    $override = ArgusPackageStore::packageOverride($module);
    if ($override !== null) {
      return $override;
    }

    $moduleProfile = self::moduleProfile($module);
    if (array_key_exists('enabled', $moduleProfile)) {
      return (bool) $moduleProfile['enabled'];
    }

    // Legacy default: modules without explicit enabled remain on (TraceGate used ?? true).
    // Dev-only modules with production_allowed still follow environment when unset.
    if ((bool) ($moduleProfile['production_allowed'] ?? false)) {
      return true;
    }

    $envProfile = self::environmentProfile();

    return (bool) ($envProfile['enabled'] ?? self::defaults()['enabled'] ?? false);
  }

  /**
   * @return array<string, array{label: string}>
   */
  public static function packageGroups(): array
  {
    $config = self::load();
    $groups = is_array($config['package_groups'] ?? null) ? $config['package_groups'] : [];
    $out = [];

    foreach ($groups as $groupId => $groupConfig) {
      if (!is_string($groupId) || !is_array($groupConfig)) {
        continue;
      }

      $label = ConfigValue::string($groupConfig['label'] ?? $groupId, $groupId);
      if ($label === '') {
        continue;
      }

      $out[$groupId] = ['label' => $label];
    }

    return $out;
  }

  /**
   * Active environment.
   */
  public static function activeEnvironment(): string
  {
    $env = Environment::appEnv();

    return match ($env) {
      'mac' => 'mac',
      'dev' => 'dev',
      default => 'prod',
    };
  }

  /**
   * Is dev environment.
   */
  public static function isDevEnvironment(): bool
  {
    return in_array(self::activeEnvironment(), ['mac', 'dev'], true);
  }

  /** @return array<string, mixed> */
  public static function defaults(): array
  {
    $config = self::load();

    return is_array($config['defaults'] ?? null) ? $config['defaults'] : [];
  }

  /** @return array<string, mixed> */
  public static function environmentProfile(): array
  {
    $config = self::load();
    $env = self::activeEnvironment();
    $profiles = is_array($config['environments'] ?? null) ? $config['environments'] : [];

    return is_array($profiles[$env] ?? null) ? $profiles[$env] : [];
  }

  /** @return array<string, mixed> */
  public static function moduleProfile(string $module): array
  {
    $config = self::load();
    $modules = is_array($config['modules'] ?? null) ? $config['modules'] : [];

    return is_array($modules[$module] ?? null) ? $modules[$module] : [];
  }

  /** @return array<string, mixed> */
  public static function eventProfile(string $eventName): array
  {
    $config = self::load();
    $events = is_array($config['events'] ?? null) ? $config['events'] : [];

    return is_array($events[$eventName] ?? null) ? $events[$eventName] : [];
  }

  /**
   * Package matrix for Argus Console (config + runtime effective state).
   *
   * @return array<int, array<string, mixed>>
   */
  public static function consoleMatrix(): array
  {
    $config = self::load();
    $env = self::activeEnvironment();
    $envProfile = self::environmentProfile();
    $modules = is_array($config['modules'] ?? null) ? $config['modules'] : [];
    $events = is_array($config['events'] ?? null) ? $config['events'] : [];

    $rows = [];
    foreach ($modules as $moduleId => $moduleConfig) {
      if (!is_string($moduleId) || !is_array($moduleConfig)) {
        continue;
      }

      $runtimeOverride = ArgusPackageStore::packageOverride($moduleId);
      $expiresAt = ArgusPackageStore::packageExpiresAt($moduleId);
      $scope = ArgusPackageStore::captureScope();
      $configEnabled = (bool) ($moduleConfig['enabled'] ?? $envProfile['enabled'] ?? self::defaults()['enabled'] ?? false);
      $rows[] = [
        'module' => $moduleId,
        'label' => ConfigValue::string($moduleConfig['label'] ?? $moduleId, $moduleId),
        'group' => ConfigValue::string($moduleConfig['group'] ?? 'other', 'other'),
        'environment' => $env,
        'enabled' => self::moduleIsEnabled($moduleId),
        'config_enabled' => $configEnabled,
        'runtime_override' => $runtimeOverride,
        'expires_at' => $expiresAt,
        'expires_label' => ArgusExpiryPolicy::formatRemaining($expiresAt),
        'scoped' => $scope->isActive(),
        'severity_floor' => ConfigValue::string($moduleConfig['severity_floor'] ?? $envProfile['severity_floor'] ?? 'error', 'error'),
        'sink' => ConfigValue::string($moduleConfig['sink'] ?? $envProfile['sink'] ?? 'none', 'none'),
        'production_allowed' => (bool) ($moduleConfig['production_allowed'] ?? false),
        'production_allowed_events' => self::productionAllowedEventsForModule($moduleId, $events),
      ];
    }

    usort($rows, static function (array $a, array $b): int {
      $groupCmp = strcmp((string) $a['group'], (string) $b['group']);
      if ($groupCmp !== 0) {
        return $groupCmp;
      }

      return strcmp((string) $a['label'], (string) $b['label']);
    });

    return $rows;
  }

  /**
   * Packages grouped for admin pill UI.
   *
   * @return array<int, array{group: string, label: string, packages: array<int, array<string, mixed>>}>
   */
  public static function consolePackageGroups(): array
  {
    $groups = self::packageGroups();
    $matrix = self::consoleMatrix();
    $buckets = [];

    foreach ($matrix as $row) {
      $groupId = ConfigValue::string($row['group'] ?? 'other', 'other');
      if (!isset($buckets[$groupId])) {
        $buckets[$groupId] = [];
      }
      $buckets[$groupId][] = $row;
    }

    $orderedGroupIds = array_keys($groups);
    foreach (array_keys($buckets) as $groupId) {
      if (!in_array($groupId, $orderedGroupIds, true)) {
        $orderedGroupIds[] = $groupId;
      }
    }

    $out = [];
    foreach ($orderedGroupIds as $groupId) {
      $packages = $buckets[$groupId] ?? [];
      if ($packages === []) {
        continue;
      }

      $out[] = [
        'group' => $groupId,
        'label' => ConfigValue::string($groups[$groupId]['label'] ?? ucfirst(str_replace('_', ' ', $groupId)), ucfirst(str_replace('_', ' ', $groupId))),
        'packages' => $packages,
      ];
    }

    return $out;
  }

  /**
   * @param array<string, mixed> $events
   * @return array<int, string>
   */
  private static function productionAllowedEventsForModule(string $moduleId, array $events): array
  {
    $out = [];
    foreach ($events as $eventName => $eventConfig) {
      if (!is_array($eventConfig)) {
        continue;
      }
      if (ConfigValue::string($eventConfig['module'] ?? '') !== $moduleId) {
        continue;
      }
      if (!($eventConfig['production_allowed'] ?? false)) {
        continue;
      }
      $out[] = $eventName;
    }

    sort($out);

    return $out;
  }
}
