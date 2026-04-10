<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\Enums\FormTTL;

/**
 * WorkEntryLockService.php
 *
 * Policy service for historical work-entry edit locks.
 *
 * Why this exists:
 * - Apply one consistent lock policy across calendar and archive flows.
 * - Derive lock boundaries from pay-period cadence and user grace settings.
 * - Prevent accidental edits to finalized periods while allowing controlled overrides.
 */

/**
 * Evaluates and exposes work-entry lock state.
 *
 * Internal guarantees:
 * - Boundary calculations are cached per-user and UTC day.
 * - Invalid dates are treated as locked for safety.
 * - Override path is explicit and opt-in via method parameter.
 */
final class WorkEntryLockService
{
  /**
   * Get user's editing grace period preference (in days).
   *
   * @param string $userUUID User UUID
   *
   * @return int Grace period in days (0-3)
   */
  public static function getGraceDays(string $userUUID): int
  {
    $graceDaysStr = Database::hget(Keys::USER . ':' . $userUUID, 'editing_grace_days');
    
    // If not set, use default
    if ('' === $graceDaysStr) {
      $graceDaysStr = UserPreferenceDefaults::DEFAULT_EDITING_GRACE_DAYS;
    }
    
    $graceDays = (int) $graceDaysStr;
    
    // Validate range using system limits
    $minGraceDays = (int) SystemLimits::get('editing_grace_days_min');
    $maxGraceDays = (int) SystemLimits::get('editing_grace_days_max');
    
    // Clamp to valid range
    if ($graceDays < $minGraceDays) {
      $graceDays = $minGraceDays;
    }
    if ($graceDays > $maxGraceDays) {
      $graceDays = $maxGraceDays;
    }
    
    return $graceDays;
  }

  /**
   * Calculate the lock boundary date for a user.
   *
   * The lock boundary is the first date that is locked (cannot be edited).
   * Dates before this boundary are locked; dates on or after are editable.
   *
   * @param string $userUUID User UUID
   *
   * @return string Lock boundary date in Y-m-d format
   */
  public static function getLockBoundaryDate(string $userUUID): string
  {
    // Cache boundary by user + current UTC date to prevent server-local drift.
    $today = self::utcToday();
    $cacheKey = Keys::LOCK_BOUNDARY . ":{$userUUID}:{$today}";
    $cached = Database::get($cacheKey);
    
    if ('' !== $cached) {
      return $cached;
    }
    
    // Calculate boundary
    $boundary = self::calculateLockBoundary($userUUID);
    
    // Expire at next UTC midnight so all app nodes share the same day boundary.
    Database::set($cacheKey, $boundary, self::secondsUntilMidnight());
    
    return $boundary;
  }

  /**
   * Check if a specific date is locked for editing.
   *
   * @param string $date          Date to check (Y-m-d format)
   * @param string $userUUID      User UUID
   * @param bool   $allowOverride  If true, ignore lock (for admin/manager override)
   *
   * @return bool True if locked (cannot edit), false if unlocked (can edit)
   */
  public static function isLocked(string $date, string $userUUID, bool $allowOverride = false): bool
  {
    // If override is granted, never locked
    if ($allowOverride) {
      return false;
    }

    // Validate date format
    if (!WorkEntry::validateDate($date)) {
      Log::warn("WorkEntryLockService::isLocked - invalid date format: {$date}");
      return true; // Treat invalid dates as locked
    }
    
    // Future dates are never locked
    $today = self::utcToday();
    if ($date >= $today) {
      return false;
    }
    
    // Get lock boundary
    $lockBoundary = self::getLockBoundaryDate($userUUID);
    
    // Date is locked if it's before the boundary
    return $date < $lockBoundary;
  }

  /**
   * Calculate lock boundary date for a user.
   *
   * This calculates the earliest date that is still editable based on:
   * 1. User's pay period configuration (determines current period start)
   * 2. User's grace period preference (days back into previous period that remain editable)
   *
   * Formula: lock_boundary = current_period_start - grace_days
   *
   * Semantics: dates strictly BEFORE lock_boundary are locked.
   * - grace=0 → boundary = current period start → only current+future periods editable
   * - grace=1 → boundary = period start - 1 day → last day of previous period also editable
   * - grace=3 → boundary = period start - 3 days → last 3 days of previous period also editable
   *
   * Example: Current period starts Mon Mar 2, grace 3 days
   * - lock_boundary = Mar 2 - 3 = Feb 28
   * - Editable: Feb 28, Mar 1 (prev period grace days), Mar 2+ (current period)
   * - Locked: Feb 27 and earlier
   *
   * @param string $userUUID User UUID
   *
   * @return string Lock boundary date in Y-m-d format
   */
  private static function calculateLockBoundary(string $userUUID): string
  {
    // Get user's grace period
    $graceDays = self::getGraceDays($userUUID);

    // Get user's pay period settings
    $user = Database::hgetall(Keys::USER . ':' . $userUUID);

    if (empty($user)) {
      Log::warn("WorkEntryLockService::calculateLockBoundary - user not found: {$userUUID}");
      // Return current UTC day as boundary (lock all past dates)
      return self::utcToday();
    }

    // Resolve pay period for current date
    $payPeriod = self::resolvePayPeriod(self::utcToday(), $userUUID);

    if (null === $payPeriod) {
      Log::warn("WorkEntryLockService::calculateLockBoundary - could not resolve pay period for user: {$userUUID}");
      // Return current UTC day as boundary (lock all past dates)
      return self::utcToday();
    }

    // The lock boundary is the current period start moved back by graceDays.
    // All dates strictly before this boundary are locked; the boundary date itself
    // and everything after it is editable.
    $currentPeriodStart = $payPeriod->start();
    $lockBoundary = $currentPeriodStart->modify('-' . $graceDays . ' days');

    return $lockBoundary->format('Y-m-d');
  }

  /**
   * Resolve pay period containing a specific date for a user.
   *
   * @param string $date     Date to resolve (Y-m-d format)
   * @param string $userUUID User UUID
   *
   * @return null|PayPeriods Pay period object or null if cannot resolve
   */
  private static function resolvePayPeriod(string $date, string $userUUID): ?PayPeriods
  {
    try {
      $user = UserRepository::getByUUID($userUUID);
      if (null === $user) {
        return null;
      }

      $tz = $user->timezone ?: 'America/Edmonton';
      $zone = new \DateTimeZone($tz);
      $target = new \DateTimeImmutable($date, $zone);
      $scheduled = PayPeriodGenerator::resolveForDate($user, $target);
      if (null !== $scheduled) {
        return $scheduled;
      }

      $frequency = PayPeriodGenerator::resolveFrequency($user);
      $anchor = $user->pay_anchor ?? 'Monday';
      $epoch = null;
      if ($frequency === PayFrequency::BIWEEKLY && !empty($user->pay_epoch)) {
        $epoch = new \DateTimeImmutable($user->pay_epoch, $zone);
      }

      return PayPeriods::fromDate($date, $frequency, $anchor, $epoch, $tz);
    } catch (\Exception $e) {
      Log::error("WorkEntryLockService::resolvePayPeriod - exception: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Clear lock boundary cache for a user.
   *
   * Should be called when:
   * - User changes their grace period preference
   * - User changes their pay period settings
   *
   * @param string $userUUID User UUID
   *
   * @return bool True if cache was cleared
   */
  public static function clearCache(string $userUUID): bool
  {
    $deleted = 0;

    // Remove legacy key from earlier fixed-TTL implementation.
    $legacyKey = Keys::LOCK_BOUNDARY . ":{$userUUID}";
    $deleted += Database::del($legacyKey);

    // Remove current date-scoped cache entries.
    $pattern = Keys::LOCK_BOUNDARY . ":{$userUUID}:*";
    foreach (Database::scanKeys($pattern) as $cacheKey) {
      $deleted += Database::unlink($cacheKey);
    }

    return $deleted > 0;
  }

  /**
   * Get seconds remaining until next UTC midnight.
   */
  private static function secondsUntilMidnight(): int
  {
    $zone = new \DateTimeZone('UTC');
    $now = new \DateTimeImmutable('now', $zone);
    $midnight = new \DateTimeImmutable('tomorrow', $zone);
    $ttl = $midnight->getTimestamp() - $now->getTimestamp();

    // Guard against zero/negative TTL edge cases.
    return max(FormTTL::ONE_MIN->value, $ttl);
  }

  /**
   * Current UTC day in Y-m-d format.
   */
  private static function utcToday(): string
  {
    return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');
  }
}
