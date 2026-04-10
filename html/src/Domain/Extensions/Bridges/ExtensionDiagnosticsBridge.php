<?php declare(strict_types=1);

namespace PayCal\Domain\Extensions\Bridges;

use PayCal\Domain\Extensions\HookBus;

/**
 * ExtensionDiagnosticsBridge
 *
 * Isolates Controllers from direct ExtensionRuntime and HookBus calls.
 * Provides safe access to extension runtime diagnostics data.
 */
final class ExtensionDiagnosticsBridge
{
  private const RUNTIME_CLASS = 'PayCal\\Domain\\Extensions\\ExtensionRuntime';

  /**
   * Check if extension runtime is available
   */
  public static function runtimeAvailable(): bool
  {
    return class_exists(self::RUNTIME_CLASS);
  }

  /**
   * Boot the extension runtime if available
   */
  public static function boot(): void
  {
    if (!self::runtimeAvailable()) {
      return;
    }

    try {
      $class = self::RUNTIME_CLASS;
      $class::boot();
    } catch (\Throwable) {
      return;
    }
  }

  /**
   * Get active manifests or empty array if runtime unavailable
   *
   * @return array<string|int, mixed>
   */
  public static function activeManifests(): array
  {
    if (!self::runtimeAvailable()) {
      return [];
    }

    try {
      $class = self::RUNTIME_CLASS;
      return $class::activeManifests();
    } catch (\Throwable) {
      return [];
    }
  }

  /**
   * Get discovered manifests or empty array if runtime unavailable
   *
   * @return array<string|int, mixed>
   */
  public static function discoveredManifests(): array
  {
    if (!self::runtimeAvailable()) {
      return [];
    }

    try {
      $class = self::RUNTIME_CLASS;
      return $class::discoveredManifests();
    } catch (\Throwable) {
      return [];
    }
  }

  /**
   * Get capability manifest or empty array if runtime unavailable
   *
   * @return array<string|int, mixed>
   */
  public static function capabilityManifest(): array
  {
    if (!self::runtimeAvailable()) {
      return [];
    }

    try {
      $class = self::RUNTIME_CLASS;
      return $class::capabilityManifest();
    } catch (\Throwable) {
      return [];
    }
  }

  /**
   * Get hook listeners summary or empty array if HookBus unavailable
   *
   * @return array<string|int, mixed>
   */
  public static function listenersSummary(): array
  {
    if (class_exists(HookBus::class)) {
      return HookBus::listenersSummary();
    }
    return [];
  }
}
