<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;

/**
 * Core hook seam for organization signal fanout.
 *
 * Extensions register listeners via HookBus for organization audit events.
 */
final class OrganizationSignalHooks
{
  /** @param array<string, string> $event */
  #[ExtensionHook('organization.audit_event')]
  /**
   * Handles onOrganizationAuditEvent operation.
   */
  public static function onOrganizationAuditEvent(array $event): void
  {
    ExtensionHookBridge::dispatch('organization.audit_event', ['event' => $event]);
  }
}

