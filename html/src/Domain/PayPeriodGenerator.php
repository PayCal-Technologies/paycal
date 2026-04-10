<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\PayFrequency;

/**
 * PayPeriodGenerator.php
 *
 * Purpose: Generate and maintain Redis-backed pay-period schedules used by
 * payroll views, calendar logic, and historical-lock boundaries.
 *
 * Developer notes:
 * - This class is the scheduling authority for persisted pay-period ranges.
 *   Avoid duplicating date-window generation in controllers or pages.
 * - Frequency, anchor, epoch, and timezone behavior must remain internally
 *   consistent because downstream features depend on exact period boundaries.
 * - Regeneration has side effects across earnings, locking, and organization
 *   pay-period views; treat schedule format changes as cross-system changes.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Pay-period schedule generator.
 *
 * Responsibilities:
 * - Resolve effective pay-period configuration from user settings.
 * - Generate durable period schedules and indexes.
 * - Provide shared helpers for period lookup and regeneration workflows.
 */

final class PayPeriodGenerator
{
  private const SCHEDULE_VERSION = '1';

  /**
   * Regenerate the stored pay-period schedule for a user.
   *
   * @param User $user Authenticated user whose schedule should be rebuilt
   */
  public static function regenerateForUser(User $user): void
  {
    $timezone = $user->timezone ?: 'America/Edmonton';
    $zone = new \DateTimeZone($timezone);
    $frequency = self::resolveFrequency($user);
    $anchor = self::normalizeAnchor($user->pay_anchor ?? 'Monday');
    $historicalStart = self::normalizeDate(
      $user->pay_period_start ?: '2024-01-01',
      $zone
    );
    $epoch = null;
    if ($frequency === PayFrequency::BIWEEKLY) {
      $epochSource = $user->pay_epoch ?: $historicalStart->format('Y-m-d');
      $epoch = self::normalizeDate($epochSource, $zone);
    }

    self::clearSchedule($user->user_uuid);

    $indexKey = self::indexKey($user->user_uuid);
    $cursor = PayPeriods::fromDate($historicalStart, $frequency, $anchor, $epoch, $timezone);
    while ($cursor->start() < $historicalStart) {
      $cursor = $cursor->next();
    }

    $until = (new \DateTimeImmutable('now', $zone))->modify('+3 years');
    $redis = Database::getInstance()->client;
    while ($cursor->start() <= $until) {
      $start = $cursor->start();
      $startYmd = $start->format('Y-m-d');
      $payloadKey = self::periodKey($user->user_uuid, $startYmd);
      Database::hset($payloadKey, [
        'start' => $startYmd,
        'end_exclusive' => $cursor->endExclusive()->format('Y-m-d'),
        'end_inclusive' => $cursor->endInclusive()->format('Y-m-d'),
        'frequency' => $frequency->value,
        'anchor' => $anchor,
        'epoch' => $epoch?->format('Y-m-d') ?? '',
        'timezone' => $timezone,
      ]);
      $redis->zAdd($indexKey, (float) $start->getTimestamp(), $startYmd);
      $cursor = $cursor->next();
    }

    Database::hset(self::metaKey($user->user_uuid), [
      'version' => self::SCHEDULE_VERSION,
      'frequency' => $frequency->value,
      'anchor' => $anchor,
      'timezone' => $timezone,
      'epoch' => $epoch?->format('Y-m-d') ?? '',
      'historical_start' => $historicalStart->format('Y-m-d'),
      'generated_at' => (new \DateTimeImmutable('now', $zone))->format('c'),
    ]);
  }

  /**
   * Resolve the stored pay period that contains the supplied date.
   *
   * @param User               $user User whose schedule should be queried
   * @param \DateTimeImmutable $date Target date in any timezone
   *
   * @return null|PayPeriods Matching pay period when one exists
   */
  public static function resolveForDate(User $user, \DateTimeImmutable $date): ?PayPeriods
  {
    $timezone = $user->timezone ?: 'America/Edmonton';
    $zone = new \DateTimeZone($timezone);
    $target = $date->setTimezone($zone)->setTime(0, 0, 0);
    $indexKey = self::indexKey($user->user_uuid);

    if (!Database::exists($indexKey)) {
      self::regenerateForUser($user);
    }

    $redis = Database::getInstance()->client;
    $members = $redis->zRevRangeByScore(
      $indexKey,
      (string) $target->getTimestamp(),
      '-inf',
      ['limit' => [0, 1]]
    );
    if (!is_array($members) || [] === $members) {
      return null;
    }

    $startMember = $members[0] ?? '';
    $startYmd = is_scalar($startMember) ? (string) $startMember : '';
    $payload = Database::hgetall(self::periodKey($user->user_uuid, $startYmd));
    if ([] === $payload) {
      return null;
    }

    $startRaw = $payload['start'] ?? '';
    $endExclusiveRaw = $payload['end_exclusive'] ?? '';
    $frequencyRaw = $payload['frequency'] ?? '';
    $start = new \DateTimeImmutable($startRaw, $zone);
    $endExclusive = new \DateTimeImmutable($endExclusiveRaw, $zone);
    $frequency = PayFrequency::from($frequencyRaw);
    $anchor = $payload['anchor'] ?? 'Monday';
    $epoch = null;
    if ('' !== (string) ($payload['epoch'] ?? '')) {
      $epoch = new \DateTimeImmutable((string) $payload['epoch'], $zone);
    }

    if ($target < $start || $target >= $endExclusive) {
      return null;
    }

    return PayPeriods::fromRange($start, $endExclusive, $frequency, $anchor, $epoch);
  }

  /**
   * Remove all persisted schedule data for a user.
   *
   * @param string $userUUID User UUID
   */
  public static function clearSchedule(string $userUUID): void
  {
    Database::unlink(self::indexKey($userUUID));
    Database::unlink(self::metaKey($userUUID));
    $keys = Database::scanKeys(self::baseKey($userUUID).':*');
    foreach ($keys as $key) {
      if ($key === self::indexKey($userUUID) || $key === self::metaKey($userUUID)) {
        continue;
      }
      Database::unlink($key);
    }
  }

  /**
   * Resolve the canonical pay frequency from user preferences.
   *
   * @param User $user User whose schedule settings are being interpreted
   *
   * @return PayFrequency Normalized pay frequency value
   */
  public static function resolveFrequency(User $user): PayFrequency
  {
    $value = $user->pay_frequency;
    if (null !== $value && '' !== $value) {
      $frequency = PayFrequency::tryFrom($value);
      if (null !== $frequency) {
        return $frequency;
      }
    }

    return match ((int) $user->pay_period_length) {
      7 => PayFrequency::WEEKLY,
      14 => PayFrequency::BIWEEKLY,
      default => PayFrequency::BIWEEKLY,
    };
  }

  /**
   * Normalize a weekday anchor into the expected title-cased value.
   *
   * @param string $anchor Raw weekday string
   *
   * @return string Normalized weekday anchor
   */
  private static function normalizeAnchor(string $anchor): string
  {
    return match (strtoupper($anchor)) {
      'MONDAY' => 'Monday',
      'TUESDAY' => 'Tuesday',
      'WEDNESDAY' => 'Wednesday',
      'THURSDAY' => 'Thursday',
      'FRIDAY' => 'Friday',
      'SATURDAY' => 'Saturday',
      'SUNDAY' => 'Sunday',
      default => 'Monday',
    };
  }

  /**
   * Normalize a date string into a midnight immutable date for the timezone.
   *
   * @param string        $date Date string to normalize
   * @param \DateTimeZone $zone Timezone for normalization
   *
   * @return \DateTimeImmutable Normalized immutable date
   */
  private static function normalizeDate(string $date, \DateTimeZone $zone): \DateTimeImmutable
  {
    return (new \DateTimeImmutable($date, $zone))->setTime(0, 0, 0);
  }

  /**
   * Build the root Redis key for a user's persisted schedule.
   *
   * @param string $userUUID User UUID
   *
   * @return string Schedule key prefix
   */
  private static function baseKey(string $userUUID): string
  {
    return Keys::PAY_PERIOD.':schedule:'.$userUUID;
  }

  /**
   * Build the Redis sorted-set key for schedule indexing.
   *
   * @param string $userUUID User UUID
   *
   * @return string Schedule index key
   */
  private static function indexKey(string $userUUID): string
  {
    return self::baseKey($userUUID).':index';
  }

  /**
   * Build the Redis metadata key for schedule generation state.
   *
   * @param string $userUUID User UUID
   *
   * @return string Schedule metadata key
   */
  private static function metaKey(string $userUUID): string
  {
    return self::baseKey($userUUID).':meta';
  }

  /**
   * Build the Redis key for one persisted pay period payload.
   *
   * @param string $userUUID User UUID
   * @param string $startYmd Period start date in Y-m-d format
   *
   * @return string Period payload key
   */
  private static function periodKey(string $userUUID, string $startYmd): string
  {
    return self::baseKey($userUUID).':'.$startYmd;
  }
}
