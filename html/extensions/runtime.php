<?php declare(strict_types=1);

namespace PayCal\Domain\Extensions;

use PayCal\Domain\Log;

/**
 * Static in-process hook registry used by extension packages.
 *
 * Listeners are registered during extension bootstrap and executed in
 * deterministic priority/source order.
 */
final class HookBus
{
  /** @var array<string, array<int, array{priority:int, callback:callable, source:string}>> */
  private static array $listeners = [];

  /**
   * Register a listener callback for one hook name.
   */
  public static function register(string $hookName, callable $callback, int $priority = 100, string $source = 'unknown'): void
  {
    $hook = strtolower(trim($hookName));
    if ($hook === '') {
      return;
    }

    self::$listeners[$hook][] = [
      'priority' => $priority,
      'callback' => $callback,
      'source' => $source,
    ];

    usort(self::$listeners[$hook], static function (array $a, array $b): int {
      if ($a['priority'] === $b['priority']) {
        return strcmp($a['source'], $b['source']);
      }

      return $a['priority'] <=> $b['priority'];
    });
  }

  /**
   * @param array<string, mixed> $payload
   * @return array<int, mixed>
   */
  public static function dispatch(string $hookName, array $payload = []): array
  {
    $hook = strtolower(trim($hookName));
    if ($hook === '' || !isset(self::$listeners[$hook])) {
      return [];
    }

    $results = [];
    foreach (self::$listeners[$hook] as $listener) {
      try {
        $results[] = ($listener['callback'])($payload);
      } catch (\Throwable $e) {
        Log::error('[HookBus] Listener failed for ' . $hook . ' from ' . $listener['source'] . ': ' . $e->getMessage());
      }
    }

    return $results;
  }

  /** @return array<string, array<int, array{priority:int, source:string}>> */
  public static function listenersSummary(): array
  {
    $summary = [];
    foreach (self::$listeners as $hook => $entries) {
      $summary[$hook] = array_map(
        static fn(array $entry): array => [
          'priority' => $entry['priority'],
          'source' => $entry['source'],
        ],
        $entries
      );
    }

    return $summary;
  }
}

/**
 * Extension discovery, selection, and activation runtime.
 *
 * Boot flow:
 * 1) Discover manifests under basic/ and overrides/
 * 2) Apply override precedence by extension id
 * 3) Execute selected package bootstrap files
 * 4) Publish active manifest/capability globals for compatibility
 */
final class ExtensionRuntime
{
  private const BASIC_DIR = __DIR__ . '/basic';
  private const OVERRIDES_DIR = __DIR__ . '/overrides';

  private static bool $booted = false;

  /** @var array<string, array<string, mixed>> */
  private static array $discovered = [];

  /** @var array<string, array<string, mixed>> */
  private static array $active = [];

  /**
   * Initialize extension runtime once per request/process.
   */
  public static function boot(): void
  {
    if (self::$booted) {
      return;
    }

    self::$booted = true;
    self::$discovered = self::discoverExtensions();
    self::$active = self::selectActiveExtensions(self::$discovered);

    foreach (self::$active as $extension) {
      $bootstrap = self::stringValue($extension['bootstrap_file'] ?? null);
      if ($bootstrap === '' || !is_file($bootstrap)) {
        continue;
      }

      try {
        require_once $bootstrap;
      } catch (\Throwable $e) {
        $extensionId = self::stringValue($extension['id'] ?? null);
        Log::error('[ExtensionRuntime] Failed bootstrapping extension ' . $extensionId . ': ' . $e->getMessage());
      }
    }

    $GLOBALS['PAYCAL_EXTENSION_MANIFESTS'] = self::activeManifests();
    $GLOBALS['PAYCAL_EXTENSION_CAPABILITIES'] = self::capabilityManifest();
  }

  /** @return array<int, array<string, mixed>> */
  public static function activeManifests(): array
  {
    return array_values(self::$active);
  }

  /** @return array<int, array<string, mixed>> */
  public static function discoveredManifests(): array
  {
    return array_values(self::$discovered);
  }

  /**
   * Build capability-first manifest across active extensions.
   *
   * @return array<string, array<int, array{extension_id:string, version:string, source:string, value:mixed}>>
   */
  public static function capabilityManifest(): array
  {
    $manifest = [];

    foreach (self::$active as $extension) {
      $capabilities = $extension['capabilities'] ?? [];
      if (!is_array($capabilities)) {
        continue;
      }

      foreach ($capabilities as $capability => $value) {
        if (!is_string($capability) || $capability === '') {
          continue;
        }

        $manifest[$capability][] = [
          'extension_id' => self::stringValue($extension['id'] ?? null),
          'version' => self::stringValue($extension['version'] ?? null),
          'source' => self::stringValue($extension['source'] ?? null),
          'value' => $value,
        ];
      }
    }

    return $manifest;
  }

  public static function hasCapability(string $capability): bool
  {
    return isset(self::capabilityManifest()[trim($capability)]);
  }

  /** @return array<int, array{extension_id:string, version:string, source:string, value:mixed}> */
  public static function capabilityEntries(string $capability): array
  {
    $normalized = trim($capability);
    if ($normalized === '') {
      return [];
    }

    $entries = self::capabilityManifest()[$normalized] ?? [];
    return $entries;
  }

  /**
   * Return the first capability value and coerce to bool when possible.
   */
  public static function capabilityEnabled(string $capability, bool $default = false): bool
  {
    $entries = self::capabilityEntries($capability);
    if ($entries === []) {
      return $default;
    }

    $value = $entries[0]['value'] ?? $default;
    if (is_bool($value)) {
      return $value;
    }

    if (is_int($value) || is_float($value)) {
      return (bool) $value;
    }

    if (is_string($value)) {
      $normalized = strtolower(trim($value));
      if (in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
        return true;
      }

      if (in_array($normalized, ['0', 'false', 'no', 'off', 'disabled'], true)) {
        return false;
      }
    }

    return $default;
  }

  /**
   * Return the first capability value or fallback.
   */
  public static function capabilityValue(string $capability, mixed $default = null): mixed
  {
    $entries = self::capabilityEntries($capability);
    if ($entries === []) {
      return $default;
    }

    return $entries[0]['value'] ?? $default;
  }

  /** @return array<string, array<string, mixed>> */
  private static function discoverExtensions(): array
  {
    $manifests = [];

    foreach (['basic' => self::BASIC_DIR, 'override' => self::OVERRIDES_DIR] as $source => $root) {
      if (!is_dir($root)) {
        continue;
      }

      $entries = scandir($root);
      if (!is_array($entries)) {
        continue;
      }

      foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
          continue;
        }

        $extensionDir = $root . '/' . $entry;
        if (!is_dir($extensionDir)) {
          continue;
        }

        $manifest = self::loadManifest($extensionDir, $source);
        if ($manifest === null) {
          continue;
        }

          $manifestKey = self::stringValue($manifest['source'] ?? null) . ':' . self::stringValue($manifest['id'] ?? null);
        $manifests[$manifestKey] = $manifest;
      }
    }

    return $manifests;
  }

  /**
   * Load and normalize one extension manifest from a package directory.
   *
   * @return array<string, mixed>|null
   */
  private static function loadManifest(string $extensionDir, string $source): ?array
  {
    $manifestFile = $extensionDir . '/manifest.php';
    if (!is_file($manifestFile)) {
      return null;
    }

    try {
      $manifest = require $manifestFile;
    } catch (\Throwable $e) {
      Log::error('[ExtensionRuntime] Failed reading manifest: ' . $manifestFile . ' -> ' . $e->getMessage());
      return null;
    }

    if (!is_array($manifest)) {
      return null;
    }

    $id = trim(self::stringValue($manifest['id'] ?? null));
    if ($id === '') {
      return null;
    }

    $bootstrapRaw = $manifest['bootstrap'] ?? 'bootstrap.php';
    $bootstrapRelative = trim(self::stringValue($bootstrapRaw, 'bootstrap.php'));
    $bootstrapFile = $extensionDir . '/' . $bootstrapRelative;

    $i18nRaw = is_array($manifest['i18n'] ?? null) ? $manifest['i18n'] : [];
    $i18nPath = trim(self::stringValue($i18nRaw['path'] ?? null));
    $i18nDefaultLang = strtolower(trim(self::stringValue($i18nRaw['default_lang'] ?? null, 'en')));

    $i18n = [];
    if ($i18nPath !== '') {
      $i18n = [
        'path' => $i18nPath,
        'default_lang' => $i18nDefaultLang !== '' ? $i18nDefaultLang : 'en',
      ];
    }

    return [
      'id' => $id,
      'name' => trim(self::stringValue($manifest['name'] ?? null, $id)),
      'version' => trim(self::stringValue($manifest['version'] ?? null, '0.0.0')),
      'description' => trim(self::stringValue($manifest['description'] ?? null)),
      'author' => trim(self::stringValue($manifest['author'] ?? null)),
      'license' => trim(self::stringValue($manifest['license'] ?? null)),
      'core_compat' => trim(self::stringValue($manifest['core_compat'] ?? null)),
      'capabilities' => is_array($manifest['capabilities'] ?? null) ? $manifest['capabilities'] : [],
      'hooks' => is_array($manifest['hooks'] ?? null) ? array_values(array_filter($manifest['hooks'], 'is_string')) : [],
      'enabled' => (bool) ($manifest['enabled'] ?? true),
      'i18n' => $i18n,
      'source' => $source,
      'directory' => $extensionDir,
      'manifest_file' => $manifestFile,
      'bootstrap_file' => $bootstrapFile,
    ];
  }

  /**
   * Override precedence rule:
   * - If any override exists for an extension id, never load the basic version.
   * - Then only load enabled manifests.
   *
   * @param array<string, array<string, mixed>> $discovered
   * @return array<string, array<string, mixed>>
   */
  private static function selectActiveExtensions(array $discovered): array
  {
    $byId = [];
    foreach ($discovered as $manifest) {
      $id = self::stringValue($manifest['id'] ?? null);
      if ($id === '') {
        continue;
      }

      if (!isset($byId[$id])) {
        $byId[$id] = [];
      }

      $byId[$id][] = $manifest;
    }

    $active = [];

    foreach ($byId as $id => $candidates) {
      $override = null;
      $basic = null;

      foreach ($candidates as $candidate) {
        if (($candidate['source'] ?? '') === 'override') {
          $override = $candidate;
          break;
        }
      }

      if ($override !== null) {
        if (($override['enabled'] ?? false) === true) {
          $active[$id] = $override;
        }
        continue;
      }

      foreach ($candidates as $candidate) {
        if (($candidate['source'] ?? '') === 'basic') {
          $basic = $candidate;
          break;
        }
      }

      if ($basic !== null && ($basic['enabled'] ?? false) === true) {
        $active[$id] = $basic;
      }
    }

    return $active;
  }

  /**
   * Coerce manifest/runtime values to string only when scalar.
   */
  private static function stringValue(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }
}
