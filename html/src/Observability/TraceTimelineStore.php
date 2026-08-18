<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * Redis ring buffer of recent diagnostic events keyed by trace_id.
 */
final class TraceTimelineStore
{
  private const KEY_PREFIX = Keys::SYSTEM . ':argus:trace:';
  private const MAX_EVENTS = 200;
  private const TTL_SECONDS = 3600;

  /**
   * Clear trace timeline entries for isolated tests.
   */
  public static function resetForTests(): void
  {
    Database::del(self::KEY_PREFIX . '*');
  }

  /**
   * Append a diagnostic event to the bounded Redis trace timeline.
   */
  public static function append(DiagnosticEvent $event): void
  {
    $traceId = $event->traceId;
    if ($traceId === '') {
      return;
    }

    try {
      $key = self::KEY_PREFIX . $traceId;
      $line = json_encode($event->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      if ($line === false) {
        return;
      }

      Database::lpush($key, $line);
      Database::ltrim($key, 0, self::MAX_EVENTS - 1);
      Database::expire($key, self::TTL_SECONDS);
    } catch (\Throwable) {
      // Never break app flow for timeline storage.
    }
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public static function eventsForTrace(string $traceId): array
  {
    $normalized = trim($traceId);
    if ($normalized === '') {
      return [];
    }

    try {
      $raw = Database::lrange(self::KEY_PREFIX . $normalized, 0, self::MAX_EVENTS - 1);

      $out = [];
      foreach (array_reverse($raw) as $line) {
        if ($line === '') {
          continue;
        }
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
          $event = [];
          foreach ($decoded as $field => $value) {
            if (is_string($field)) {
              $event[$field] = $value;
            }
          }

          if ($event !== []) {
            $out[] = $event;
          }
        }
      }

      return $out;
    } catch (\Throwable) {
      return [];
    }
  }
}
