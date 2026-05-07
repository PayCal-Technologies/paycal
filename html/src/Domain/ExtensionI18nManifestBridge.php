<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * ExtensionI18nManifestBridge.php
 *
 * Purpose: Bridge between core i18n/string resolution and optional extension
 * manifest metadata.
 *
 * Developer notes:
 * - Strings must remain stable when extension runtime is absent.
 * - Manifest ordering affects override precedence and should remain explicit.
 */
final class ExtensionI18nManifestBridge
{
  private const RUNTIME_CLASS = 'PayCal\\Domain\\Extensions\\ExtensionRuntime';

  public static function runtimeAvailable(): bool
  {
    return class_exists(self::RUNTIME_CLASS);
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public static function activeManifestsForI18n(): array
  {
    if (!self::runtimeAvailable()) {
      return [];
    }

    try {
      $class = self::RUNTIME_CLASS;
      $result = $class::activeManifests();
    } catch (\Throwable) {
      return [];
    }

    $manifests = [];
    foreach ($result as $manifest) {
      /** @var array<string, mixed> $manifest */
      $manifests[] = $manifest;
    }

    usort($manifests, static function (array $a, array $b): int {
      $weightA = self::arrayString($a, 'source') === 'override' ? 0 : 1;
      $weightB = self::arrayString($b, 'source') === 'override' ? 0 : 1;
      if ($weightA !== $weightB) {
        return $weightA <=> $weightB;
      }

      return strcmp(self::arrayString($a, 'id'), self::arrayString($b, 'id'));
    });

    return $manifests;
  }

  /**
   * @param array<string, mixed> $input
   */
  private static function arrayString(array $input, string $key, string $default = ''): string
  {
    $value = $input[$key] ?? $default;

    if (!is_scalar($value)) {
      return $default;
    }

    return trim((string) $value);
  }
}

