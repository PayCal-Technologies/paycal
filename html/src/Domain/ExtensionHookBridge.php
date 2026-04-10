<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;

/**
 * ExtensionHookBridge.php
 *
 * Purpose: Bridge between core domain flows and optional extension hook-bus
 * dispatch, avoiding direct runtime coupling.
 *
 * Developer notes:
 * - Dispatch must be safe no-op when extensions are not installed.
 * - Keep payload contracts simple and array-based for compatibility.
 */
/**
 * Extension hook dispatch bridge.
 *
 * Responsibilities:
 * - Dispatch hook events to extension runtime when available.
 * - Return deterministic empty results when no hook runtime exists.
 */
final class ExtensionHookBridge
{
  /**
   * @param array<string, mixed> $payload
   * @return array<int, mixed>
   */
  #[ExtensionHook('dynamic')]
  /**
   * Handles dispatch operation.
   */
  public static function dispatch(string $hookName, array $payload = []): array
  {
    if (!class_exists(\PayCal\Domain\Extensions\HookBus::class)) {
      return [];
    }

    return \PayCal\Domain\Extensions\HookBus::dispatch($hookName, $payload);
  }
}

