<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Queue;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Infrastructure\Telemetry\SecurityLog;

/**
 * StripeBillingQueueMonitor.php
 *
 * Webhook queue depth monitoring and alerting service.
 *
 * Why this exists:
 * - Track Stripe webhook queue health (depth, dead-letter accumulation).
 * - Emit alerts when queue backlog or dead-letter buffers exceed thresholds.
 * - Provide consolidated queue status for health dashboards.
 *
 * Internal guarantees:
 * - Alert thresholds are centralized and type-safe.
 * - Alerts are throttled to prevent operator spam (one per 5 minutes per alert type).
 * - Queue depth is sampled during health checks, not retrieved on every request.
 */
final class StripeBillingQueueMonitor
{
  private const ALERT_THROTTLE_PREFIX = Keys::SYSTEM . ':billing:queue:alert:last:';
  private const ALERT_THROTTLE_SECONDS = 300; // 5 minutes

  /**
   * Alert threshold configuration.
   * 
   * @return array{
   *   queue_depth_warning: int,
   *   queue_depth_critical: int,
   *   dead_letter_warning: int,
   *   dead_letter_critical: int,
   *   queue_max_items: int,
   *   dead_letter_max_items: int
   * }
   */
  private static function policy(): array
  {
    return [
      'queue_depth_warning' => 500,      // Alert if queue has 500+ unprocessed items
      'queue_depth_critical' => 1000,    // Alert if queue has 1000+ unprocessed items
      'dead_letter_warning' => 50,       // Alert if dead-letter has 50+ failed items
      'dead_letter_critical' => 200,     // Alert if dead-letter has 200+ failed items
      'queue_max_items' => 2000,         // Configured max queue size
      'dead_letter_max_items' => 500,    // Configured max dead-letter size
    ];
  }

  /**
   * Sample current queue and dead-letter depths, evaluate health status.
   *
   * @return array{
   *   healthy: bool,
   *   queue_depth: int,
   *   queue_percent: float,
   *   dead_letter_depth: int,
   *   dead_letter_percent: float,
   *   alerts: array<int, array{id: string, code: string, severity: string, value: int, threshold: int, message: string}>
   * }
   */
  public static function getQueueHealth(): array
  {
    $policy = self::policy();
    
    // Sample queue and dead-letter depths
    $queueDepth = Database::llen(Keys::BILLING_WEBHOOK_QUEUE);
    $deadLetterDepth = Database::llen(Keys::BILLING_WEBHOOK_DEAD_LETTER);

    $queuePercent = $policy['queue_max_items'] > 0 
      ? round(($queueDepth / $policy['queue_max_items']) * 100, 2) 
      : 0.0;
    $deadLetterPercent = $policy['dead_letter_max_items'] > 0 
      ? round(($deadLetterDepth / $policy['dead_letter_max_items']) * 100, 2) 
      : 0.0;

    // Evaluate alerts
    $alerts = self::evaluateAlerts(
      $policy,
      $queueDepth,
      $deadLetterDepth
    );

    $healthy = empty($alerts) || !self::hasAnyAlert($alerts, 'critical');
    
    // Emit alert hooks (throttled)
    self::emitAlertHooks($alerts);

    return [
      'healthy' => $healthy,
      'queue_depth' => $queueDepth,
      'queue_percent' => $queuePercent,
      'dead_letter_depth' => $deadLetterDepth,
      'dead_letter_percent' => $deadLetterPercent,
      'alerts' => $alerts,
    ];
  }

  /**
   * @param array{
   *   queue_depth_warning: int,
   *   queue_depth_critical: int,
   *   dead_letter_warning: int,
   *   dead_letter_critical: int,
   *   queue_max_items: int,
   *   dead_letter_max_items: int
   * } $policy
   * @return array<int, array{id: string, code: string, severity: string, value: int, threshold: int, message: string}>
   */
  private static function evaluateAlerts(
    array $policy,
    int $queueDepth,
    int $deadLetterDepth
  ): array {
    $alerts = [];

    // Evaluate queue depth alerts
    if ($queueDepth >= $policy['queue_depth_critical']) {
      $alerts[] = [
        'id' => 'queue:depth:critical',
        'code' => 'WEBHOOK_QUEUE_CRITICAL',
        'severity' => 'critical',
        'value' => $queueDepth,
        'threshold' => $policy['queue_depth_critical'],
        'message' => 'Stripe webhook queue critical: ' . $queueDepth . ' unprocessed events.',
      ];
    } elseif ($queueDepth >= $policy['queue_depth_warning']) {
      $alerts[] = [
        'id' => 'queue:depth:warning',
        'code' => 'WEBHOOK_QUEUE_BACKLOG',
        'severity' => 'warning',
        'value' => $queueDepth,
        'threshold' => $policy['queue_depth_warning'],
        'message' => 'Stripe webhook queue backlog: ' . $queueDepth . ' unprocessed events.',
      ];
    }

    // Evaluate dead-letter alerts
    if ($deadLetterDepth >= $policy['dead_letter_critical']) {
      $alerts[] = [
        'id' => 'dead_letter:depth:critical',
        'code' => 'WEBHOOK_DEAD_LETTER_CRITICAL',
        'severity' => 'critical',
        'value' => $deadLetterDepth,
        'threshold' => $policy['dead_letter_critical'],
        'message' => 'Stripe webhook dead-letter critical: ' . $deadLetterDepth . ' failed events.',
      ];
    } elseif ($deadLetterDepth >= $policy['dead_letter_warning']) {
      $alerts[] = [
        'id' => 'dead_letter:depth:warning',
        'code' => 'WEBHOOK_DEAD_LETTER_ACCUMULATING',
        'severity' => 'warning',
        'value' => $deadLetterDepth,
        'threshold' => $policy['dead_letter_warning'],
        'message' => 'Stripe webhook dead-letter accumulating: ' . $deadLetterDepth . ' failed events.',
      ];
    }

    return $alerts;
  }

  /**
   * @param array<int, array{id: string, code: string, severity: string, value: int, threshold: int, message: string}> $alerts
   */
  private static function emitAlertHooks(array $alerts): void
  {
    foreach ($alerts as $alert) {
      $id = $alert['id'];
      $severity = $alert['severity'];
      $message = $alert['message'];
      $code = $alert['code'];

      // Throttle duplicate alerts (prevent spam)
      $throttleKey = self::ALERT_THROTTLE_PREFIX . $severity . ':' . $id;
      $alreadySent = Database::get($throttleKey) !== '';
      if ($alreadySent) {
        continue;
      }

      // Log to security audit trail
      SecurityLog::log('stripe_webhook_queue_alert', [
        'code' => $code,
        'severity' => $severity,
        'message' => $message,
        'queue_depth' => (string) $alert['value'],
        'threshold' => (string) $alert['threshold'],
      ]);

      // Set throttle flag to prevent re-alerting during TTL window
      Database::set($throttleKey, (string) time(), self::ALERT_THROTTLE_SECONDS);
    }
  }

  /**
   * @param array<int, array{id: string, code: string, severity: string, value: int, threshold: int, message: string}> $alerts
   */
  private static function hasAnyAlert(array $alerts, string $severity): bool
  {
    foreach ($alerts as $alert) {
      if ($alert['severity'] === $severity) {
        return true;
      }
    }

    return false;
  }
}
