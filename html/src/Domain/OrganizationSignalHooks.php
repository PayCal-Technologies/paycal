<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;

/**
 * OrganizationSignalHooks.php
 *
 * Purpose: Hook seam for organization audit-event fanout into extension
 * listeners through the shared hook bus.
 *
 * Developer notes:
 * - Hook names exposed here are part of the extension contract and should stay
 *   stable unless the corresponding extension surface changes deliberately.
 * - Keep the seam thin so core organization workflows remain decoupled from
 *   concrete extension listeners.
 *
 * Architectural role:
 * - Reusable domain bridge for exposing organization signals to extension
 *   listeners without hard dependencies on extension code.
 * - Encapsulates extension hook fanout outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Extensions
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Core hook seam for organization signal fanout.
 *
 * Extensions register listeners via HookBus for organization audit events.
 */
final class OrganizationSignalHooks
{
  /** @param array<string, string> $event */
  #[ExtensionHook('organization.audit_event')]
  public static function onOrganizationAuditEvent(array $event): void
  {
    ExtensionHookBridge::dispatch('organization.audit_event', ['event' => $event]);
  }
}

