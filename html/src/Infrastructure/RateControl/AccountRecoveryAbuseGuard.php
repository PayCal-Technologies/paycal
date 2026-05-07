<?php declare(strict_types=1);

namespace PayCal\Infrastructure\RateControl;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * AccountRecoveryAbuseGuard.php
 *
 * Purpose: Abuse-prevention guard for account-recovery flows using Redis-backed
 * counters, replay signals, and temporary IP blocking decisions.
 *
 * Developer notes:
 * - Recovery abuse thresholds are security controls and should remain aligned
 *   with the configured anti-abuse policy.
 * - Keep tracking and blocking semantics explicit so controller behavior stays
 *   deterministic under repeated hostile traffic.
 *
 * Architectural role:
 * - Infrastructure guard consumed by recovery workflows that need consistent
 *   rate and replay-abuse enforcement.
 * - Encapsulates recovery abuse policy outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\RateControl
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Class AccountRecoveryAbuseGuard
 *
 * Detects and blocks abusive use of the account-recovery flow by tracking
 * per-IP replay-attack counters and supersede events in Redis.  Auto-blocks
 * an IP once the configurable replay-count threshold is exceeded.
 */
final class AccountRecoveryAbuseGuard
{
  private const METRIC_TTL_SECONDS = 2592000;

  /**
   * Handles isBlocked operation.
   */
  public function isBlocked(string $remoteIp): bool
  {
    if (!$this->isEnabled() || $remoteIp === '' || $remoteIp === 'unknown') {
      return false;
    }

    return Database::exists(Keys::accountRecoveryBlockedIp($this->ipHash($remoteIp)));
  }

  /**
   * Handles recordReplayEvent operation.
   */
  public function recordReplayEvent(string $remoteIp, string $reason = 'replay'): int
  {
    $this->incrementTelemetry('replay_detected');
    $windowSeconds = max(60, (int) SystemConfig::get('account_recovery_replay_block_window_seconds'));
    $window = (string) floor(time() / $windowSeconds);
    $counterKey = Keys::accountRecoveryReplayCounter($this->ipHash($remoteIp), $window);
    $count = Database::incr($counterKey);
    Database::expire($counterKey, $windowSeconds + 60);

    if ($reason === 'proof') {
      $this->incrementTelemetry('proof_fail');
    }

    if ($this->shouldAutoBlock($count)) {
      Database::set(
        Keys::accountRecoveryBlockedIp($this->ipHash($remoteIp)),
        (string) time(),
        max(300, (int) SystemConfig::get('account_recovery_replay_ip_block_ttl_minutes') * 60)
      );
      $this->incrementTelemetry('ip_blocked');
    }

    return $count;
  }

  /**
   * Handles recordSupersedeEvent operation.
   */
  public function recordSupersedeEvent(): void
  {
    $this->incrementTelemetry('txn_superseded');
  }

  /**
   * Handles shouldAutoBlock operation.
   */
  private function shouldAutoBlock(int $count): bool
  {
    if (!filter_var(SystemConfig::get('account_recovery_auto_block_enabled'), FILTER_VALIDATE_BOOLEAN)) {
      return false;
    }

    return $count >= max(1, (int) SystemConfig::get('account_recovery_replay_block_threshold'));
  }

  /**
   * Handles incrementTelemetry operation.
   */
  private function incrementTelemetry(string $metric): void
  {
    if (!$this->isEnabled()) {
      return;
    }

    $key = Keys::accountRecoveryTelemetry($metric, date('Y-m-d'));
    if (!Database::exists($key)) {
      Database::set($key, '0', self::METRIC_TTL_SECONDS);
    }
    Database::incr($key);
  }

  /**
   * Handles isEnabled operation.
   */
  private function isEnabled(): bool
  {
    return filter_var(SystemConfig::get('account_recovery_enabled'), FILTER_VALIDATE_BOOLEAN);
  }

  /**
   * Handles ipHash operation.
   */
  private function ipHash(string $remoteIp): string
  {
    return substr(hash('sha256', $remoteIp), 0, 32);
  }
}
