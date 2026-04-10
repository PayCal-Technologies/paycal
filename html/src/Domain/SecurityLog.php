<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Attributes\ExtensionHook;
use PayCal\Domain\Enums\FormTTL;

/**
 * SecurityLog.php
 *
 * Purpose: Define the SecurityLog class for PayCal\Domain.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Class SecurityLog
 *
 * Minimal structured security event logger for audit and abuse telemetry.
 */
final class SecurityLog
{
  /**
   * Record a security event with normalized context.
   *
   * @param string                     $event   Event name
   * @param array<string, scalar|null> $context
   */
  #[ExtensionHook('security.audit_event')]
  /**
   * Handles log operation.
   */
  public static function log(string $event, array $context = []): void
  {
    $timestamp = time();
    $eventKey = 'security:event:' . $event;

    try {
      Database::incr($eventKey);
      Database::expire($eventKey, FormTTL::ONE_DAY->value);
    } catch (\Throwable) {
    }

    $payload = [
      'ts' => $timestamp,
      'event' => $event,
      'context' => $context,
    ];

    ExtensionHookBridge::dispatch('security.audit_event', [
      'event' => $event,
      'context' => $context,
      'timestamp' => $timestamp,
    ]);

    Log::error('[SECURITY] ' . json_encode($payload));
  }

  /**
   * Record a triggered rate-limit event.
   *
   * @param string $scope      Rate-limit scope identifier
   * @param string $identifier Scope subject identifier
   * @param int    $remaining  Remaining attempts after enforcement
   */
  public static function logRateLimitTriggered(string $scope, string $identifier, int $remaining): void
  {
    $ip = Security::getClientIPAddress();

    self::log('rate_limit_triggered', [
      'scope' => $scope,
      'identifier' => $identifier,
      'remaining' => (string) $remaining,
      'ip' => $ip,
      'user_uuid' => (string) User::currentUUID(),
    ]);
  }

  /**
   * Record a rejected edit attempt on a locked historical entry.
   *
   * @param string $userUUID User UUID
   * @param string $date     Locked entry date
   */
  public static function logEntryLockedAttempt(string $userUUID, string $date): void
  {
    $ip = Security::getClientIPAddress();

    self::log('entry_locked_attempt', [
      'user_uuid' => $userUUID,
      'date' => $date,
      'ip' => $ip,
    ]);
  }

  /**
   * Record activation of password-only protected mode.
   *
   * @param string $userUUID   User UUID
   * @param string $authMethod Auth method that triggered protected mode
   */
  public static function logProtectedModeActivated(string $userUUID, string $authMethod): void
  {
    self::log('protected_mode_activated', [
      'user_uuid' => $userUUID,
      'auth_method' => $authMethod,
      'ip' => Security::getClientIPAddress(),
    ]);
  }

  /**
   * Record a blocked mutation attempt while protected mode is active.
   *
   * @param string $userUUID User UUID
   * @param string $method   HTTP method
   * @param string $route    Route path
   */
  public static function logProtectedModeMutationBlocked(string $userUUID, string $method, string $route): void
  {
    self::log('protected_mode_mutation_blocked', [
      'user_uuid' => $userUUID,
      'method' => strtoupper($method),
      'route' => $route,
      'ip' => Security::getClientIPAddress(),
    ]);
  }
}

