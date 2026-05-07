<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Telemetry;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * ContactSupportTelemetry.php
 *
 * Purpose: Support-form telemetry recorder for aggregate metrics, bounded
 * event logging, and operational diagnostics around support delivery outcomes.
 *
 * Developer notes:
 * - Telemetry writes here should remain best-effort so support submission paths
 *   do not fail closed on diagnostics storage issues.
 * - Keep log shaping sanitized and size-bounded because these events may carry
 *   operationally sensitive context.
 *
 * Architectural role:
 * - Reusable domain telemetry service for contact-support observability and
 *   post-delivery diagnostics.
 * - Encapsulates support telemetry persistence outside the HTTP layer.
 *
 * @category   Infrastructure
 * @package    PayCal\Infrastructure\Telemetry
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Contact support telemetry recorder.
 *
 * Why this exists:
 * - Capture support-form delivery outcomes without coupling to controller code.
 * - Maintain both aggregate counters (Redis) and auditable event logs (JSONL).
 * - Keep contact UX resilient even when telemetry writes fail.
 *
 * Internal guarantees:
 * - Metric writes are best-effort and never block request completion.
 * - Log lines are sanitized, bounded, and appended with file locking.
 * - Rotation keeps disk usage bounded while preserving historical diagnostics.
 */
final class ContactSupportTelemetry
{
  private const DEFAULT_LOG_PATH = '/var/www/paycal/logs/contact-email.log';
  private const DEFAULT_MAX_LOG_BYTES = 10_485_760; // 10 MB
  private const DEFAULT_ROTATE_KEEP = 1095; // 3 years of rotated files
  private const METRIC_PREFIX = Keys::TELEMETRY . ':contact';

  /**
   * @param array<string, scalar|null> $event
   */
  public static function recordSubmission(array $event): void
  {
    $timestamp = time();
    $isSuccess = ($event['is_success'] ?? 0) === 1;
    $outcome = self::sanitizeSegment((string) ($event['outcome'] ?? 'unknown'));

    self::incrementMetrics($timestamp, $outcome, $isSuccess, $event);
    self::writeEventLog($timestamp, $event, $outcome);
  }

  /**
   * @return array<string, mixed>
   */
  public static function getMetricsSnapshot(): array
  {
    $prefix = self::METRIC_PREFIX;
    $lastFailureOutcomeText = Database::get($prefix . ':last_failure_outcome');

    return [
      'total_submissions' => self::getInt($prefix . ':submissions:total'),
      'successful_submissions' => self::getInt($prefix . ':submissions:success'),
      'failed_submissions' => self::getInt($prefix . ':submissions:failure'),
      'today_total' => self::getInt($prefix . ':day:' . date('Y-m-d') . ':total'),
      'today_success' => self::getInt($prefix . ':day:' . date('Y-m-d') . ':success'),
      'today_failure' => self::getInt($prefix . ':day:' . date('Y-m-d') . ':failure'),
      'week_total' => self::getInt($prefix . ':week:' . date('o-\\WW') . ':total'),
      'month_total' => self::getInt($prefix . ':month:' . date('Y-m') . ':total'),
      'last_submission_at' => self::getInt($prefix . ':last_submission_at'),
      'last_success_at' => self::getInt($prefix . ':last_success_at'),
      'last_failure_at' => self::getInt($prefix . ':last_failure_at'),
      'last_failure_outcome' => $lastFailureOutcomeText,
      'include_ip_count' => self::getInt($prefix . ':context:include_ip'),
      'include_browser_count' => self::getInt($prefix . ':context:include_browser_device'),
      'include_language_count' => self::getInt($prefix . ':context:include_language_region'),
      'log_path' => self::resolveLogPath(),
      'log_size_bytes' => self::logSizeBytes(self::resolveLogPath()),
      'rotation_keep_files' => self::rotationKeep(),
      'rotation_max_bytes' => self::maxLogBytes(),
      'log_write_failures' => self::getInt($prefix . ':log_write_failures'),
    ];
  }

  /**
   * @param array<string, scalar|null> $event
   */
  private static function incrementMetrics(int $timestamp, string $outcome, bool $isSuccess, array $event): void
  {
    $prefix = self::METRIC_PREFIX;
    $day = date('Y-m-d', $timestamp);
    $week = date('o-\\WW', $timestamp);
    $month = date('Y-m', $timestamp);
    $year = date('Y', $timestamp);

    try {
      Database::incr($prefix . ':submissions:total');
      Database::incr($prefix . ':outcome:' . $outcome);

      Database::incr($prefix . ':day:' . $day . ':total');
      Database::incr($prefix . ':week:' . $week . ':total');
      Database::incr($prefix . ':month:' . $month . ':total');
      Database::incr($prefix . ':year:' . $year . ':total');

      if ($isSuccess) {
        Database::incr($prefix . ':submissions:success');
        Database::incr($prefix . ':day:' . $day . ':success');
        Database::set($prefix . ':last_success_at', (string) $timestamp);
      } else {
        Database::incr($prefix . ':submissions:failure');
        Database::incr($prefix . ':day:' . $day . ':failure');
        Database::set($prefix . ':last_failure_at', (string) $timestamp);
        Database::set($prefix . ':last_failure_outcome', $outcome);
      }

      if ((int) ($event['include_ip_address'] ?? 0) === 1) {
        Database::incr($prefix . ':context:include_ip');
      }
      if ((int) ($event['include_browser_device'] ?? 0) === 1) {
        Database::incr($prefix . ':context:include_browser_device');
      }
      if ((int) ($event['include_language_region'] ?? 0) === 1) {
        Database::incr($prefix . ':context:include_language_region');
      }

      Database::set($prefix . ':last_submission_at', (string) $timestamp);
    } catch (\Throwable) {
      // Keep contact UX resilient if metrics writes fail.
    }
  }

  /**
   * @param array<string, scalar|null> $event
   */
  private static function writeEventLog(int $timestamp, array $event, string $outcome): void
  {
    $path = self::resolveLogPath();

    try {
      self::rotateIfNeeded($path);

      $payload = [
        'timestamp' => gmdate('c', $timestamp),
        'outcome' => $outcome,
        'is_success' => ((int) ($event['is_success'] ?? 0) === 1),
        'sender_name' => self::cleanField((string) ($event['sender_name'] ?? '')),
        'sender_email' => self::cleanField((string) ($event['sender_email'] ?? '')),
        'subject' => self::cleanField((string) ($event['subject'] ?? '')),
        'reason' => self::cleanField((string) ($event['reason'] ?? '')),
        'message_preview' => self::truncate(self::cleanField((string) ($event['message_preview'] ?? '')), 500),
        'ip_address' => self::cleanField((string) ($event['ip_address'] ?? 'unknown')),
        'browser_user_agent' => self::cleanField((string) ($event['browser_user_agent'] ?? 'unknown')),
        'session_id' => self::cleanField((string) ($event['session_id'] ?? '')),
        'fingerprint' => self::cleanField((string) ($event['fingerprint'] ?? '')),
        'include_browser_device' => ((int) ($event['include_browser_device'] ?? 0) === 1),
        'include_ip_address' => ((int) ($event['include_ip_address'] ?? 0) === 1),
        'include_language_region' => ((int) ($event['include_language_region'] ?? 0) === 1),
      ];

      $line = json_encode($payload, JSON_UNESCAPED_SLASHES);
      if ($line === false) {
        $line = '{"timestamp":"' . gmdate('c', $timestamp) . '","outcome":"json_encode_failed"}';
      }

      @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (\Throwable) {
      try {
        Database::incr(self::METRIC_PREFIX . ':log_write_failures');
      } catch (\Throwable) {
        // No-op: never block request flow on telemetry failures.
      }
    }
  }

  /**
   * Handles resolveLogPath operation.
   */
  private static function resolveLogPath(): string
  {
    $configured = trim(self::envString('CONTACT_LOG_PATH', ''));
    $primary = $configured !== '' ? $configured : self::DEFAULT_LOG_PATH;

    $primaryDir = dirname($primary);
    if (is_dir($primaryDir) || @mkdir($primaryDir, 0o755, true)) {
      return $primary;
    }

    return rtrim(Environment::appHome(), '/') . '/logs/contact-email.log';
  }

  /**
   * Handles rotateIfNeeded operation.
   */
  private static function rotateIfNeeded(string $path): void
  {
    $maxBytes = self::maxLogBytes();
    if (!is_file($path)) {
      return;
    }

    $size = filesize($path);
    if (!is_int($size) || $size < $maxBytes) {
      return;
    }

    $keep = self::rotationKeep();
    if ($keep < 1) {
      return;
    }

    $oldest = $path . '.' . $keep;
    if (is_file($oldest)) {
      @unlink($oldest);
    }

    for ($index = $keep - 1; $index >= 1; $index--) {
      $src = $path . '.' . $index;
      $dst = $path . '.' . ($index + 1);
      if (is_file($src)) {
        @rename($src, $dst);
      }
    }

    @rename($path, $path . '.1');
  }

  /**
   * Handles maxLogBytes operation.
   */
  private static function maxLogBytes(): int
  {
    $value = self::envInt('CONTACT_LOG_ROTATE_MAX_BYTES', self::DEFAULT_MAX_LOG_BYTES);

    return max(131072, $value);
  }

  /**
   * Handles rotationKeep operation.
   */
  private static function rotationKeep(): int
  {
    $value = self::envInt('CONTACT_LOG_ROTATE_KEEP', self::DEFAULT_ROTATE_KEEP);

    return max(1, $value);
  }

  /**
   * Handles getInt operation.
   */
  private static function getInt(string $key): int
  {
    $raw = Database::get($key);
    if ($raw === '') {
      return 0;
    }

    return (int) $raw;
  }

  /**
   * Handles logSizeBytes operation.
   */
  private static function logSizeBytes(string $path): int
  {
    if (!is_file($path)) {
      return 0;
    }

    $size = filesize($path);
    return is_int($size) ? $size : 0;
  }

  /**
   * Handles sanitizeSegment operation.
   */
  private static function sanitizeSegment(string $value): string
  {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9_\-]/', '_', $value) ?? '';

    return $value === '' ? 'unknown' : $value;
  }

  /**
   * Handles cleanField operation.
   */
  private static function cleanField(string $value): string
  {
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);

    return trim($value);
  }

  /**
   * Handles truncate operation.
   */
  private static function truncate(string $value, int $max): string
  {
    if (strlen($value) <= $max) {
      return $value;
    }

    return substr($value, 0, $max);
  }

  /**
   * Handles envString operation.
   */
  private static function envString(string $key, string $default = ''): string
  {
    $value = $_ENV[$key] ?? $default;

    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles envInt operation.
   */
  private static function envInt(string $key, int $default): int
  {
    $value = $_ENV[$key] ?? $default;

    if (is_int($value)) {
      return $value;
    }

    if (is_numeric($value)) {
      return (int) $value;
    }

    return $default;
  }
}
