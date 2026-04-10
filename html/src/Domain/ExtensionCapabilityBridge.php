<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionCapability;

/**
 * ExtensionCapabilityBridge.php
 *
 * Purpose: Bridge between core domain code and optional extension runtime
 * capability resolution.
 *
 * Developer notes:
 * - Core must continue to run safely when extension runtime is absent.
 * - Capability reads here should always return deterministic fallback values.
 */
/**
 * Extension capability runtime bridge.
 *
 * Responsibilities:
 * - Detect extension runtime availability.
 * - Resolve boolean capability flags with safe defaults.
 * - Resolve capability payload values with guarded fallbacks.
 */
final class ExtensionCapabilityBridge
{
  private const RUNTIME_CLASS = 'PayCal\\Domain\\Extensions\\ExtensionRuntime';

  /**
   * Handles runtimeAvailable operation.
   */
  public static function runtimeAvailable(): bool
  {
    return class_exists(self::RUNTIME_CLASS);
  }

  #[ExtensionCapability('dynamic')]
  /**
   * Handles enabled operation.
   */
  public static function enabled(string $capability, bool $default = false): bool
  {
    if (!self::runtimeAvailable()) {
      return $default;
    }

    try {
      $class = self::RUNTIME_CLASS;
      return $class::capabilityEnabled($capability, $default);
    } catch (\Throwable) {
      return $default;
    }
  }

  #[ExtensionCapability('dynamic')]
  /**
   * Handles value operation.
   */
  public static function value(string $capability, mixed $default = null): mixed
  {
    if (!self::runtimeAvailable()) {
      return $default;
    }

    try {
      $class = self::RUNTIME_CLASS;
      return $class::capabilityValue($capability, $default);
    } catch (\Throwable) {
      return $default;
    }
  }
}

