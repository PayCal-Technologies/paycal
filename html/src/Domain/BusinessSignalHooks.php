<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;

/**
 * BusinessSignalHooks.php
 *
 * Purpose: Hook seam for business audit-event fanout into extension
 * listeners through the shared hook bus.
 *
 * Developer notes:
 * - Hook names exposed here are part of the extension contract and should stay
 *   stable unless the corresponding extension surface changes deliberately.
 * - Keep the seam thin so core business workflows remain decoupled from
 *   concrete extension listeners.
 *
 * Architectural role:
 * - Reusable domain bridge for exposing business signals to extension
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
 * Core hook seam for business signal fanout.
 *
 * Extensions register listeners via HookBus for business audit events.
 */
final class BusinessSignalHooks
{
  /** @param array<string, string> $event */
  #[ExtensionHook('business.audit_event')]
  public static function onBusinessAuditEvent(array $event): void
  {
    ExtensionHookBridge::dispatch('business.audit_event', ['event' => $event]);
  }
}

