<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Named Argus capture presets (one-click module bundles).
 */
final class ArgusPresetCatalog
{
  /**
   * @return array<int, array{id: string, label: string, modules: array<int, string>}>
   */
  public static function all(): array
  {
    $config = TraceGatePolicy::load();
    $presets = is_array($config['presets'] ?? null) ? $config['presets'] : [];
    $out = [];

    foreach ($presets as $id => $preset) {
      if (!is_string($id) || !is_array($preset)) {
        continue;
      }

      $modules = is_array($preset['modules'] ?? null) ? $preset['modules'] : [];
      $normalizedModules = [];
      foreach ($modules as $module) {
        if (!is_string($module)) {
          continue;
        }
        $moduleId = strtolower(trim($module));
        if ($moduleId !== '' && TraceGatePolicy::isKnownModule($moduleId)) {
          $normalizedModules[] = $moduleId;
        }
      }

      if ($normalizedModules === []) {
        continue;
      }

      $out[] = [
        'id' => $id,
        'label' => ConfigValue::string($preset['label'] ?? $id, $id),
        'modules' => $normalizedModules,
      ];
    }

    return $out;
  }

  /**
   * @return array{id: string, label: string, modules: array<int, string>}|null
   */
  public static function get(string $presetId): ?array
  {
    $normalized = strtolower(trim($presetId));
    foreach (self::all() as $preset) {
      if ($preset['id'] === $normalized) {
        return $preset;
      }
    }

    return null;
  }
}
