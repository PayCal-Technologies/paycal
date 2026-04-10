<?php declare(strict_types=1);

namespace PayCal\Domain;
use PayCal\Domain\Enums\FormTTL;

/**
 * Clock.php
 *
 * Purpose: Define the Clock class for PayCal\Domain.
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
class Clock
{
  private static ?int $frozenTime = null;

  /**
   * Get current Unix timestamp.
   *
   * @return int Current time or frozen time if set
   */
  public static function now(): int
  {
    return self::$frozenTime ?? time();
  }

  /**
   * Get current time as float (with microseconds).
   *
   * @return float Current microtime
   */
  public static function microtime(): float
  {
    if (null !== self::$frozenTime) {
      return (float) self::$frozenTime;
    }

    return microtime(true);
  }

  /**
   * Get formatted date string.
   *
   * @param string   $format Date format (PHP date() format)
   * @param null|int $timestamp Optional timestamp (uses now() if null)
   * @return string Formatted date
   */
  public static function date(string $format, ?int $timestamp = null): string
  {
    $time = $timestamp ?? self::now();

    return date($format, $time);
  }

  /**
   * Freeze time at a specific point (for testing).
   *
   * @param string|int $time Unix timestamp or strtotime-compatible string
   */
  public static function freeze(string|int $time): void
  {
    if (is_string($time)) {
      $timestamp = strtotime($time);
      if (false === $timestamp) {
        throw new \InvalidArgumentException("Invalid time format: {$time}");
      }
      self::$frozenTime = $timestamp;
    } else {
      self::$frozenTime = $time;
    }
  }

  /**
   * Unfreeze time (resume normal flow).
   */
  public static function unfreeze(): void
  {
    self::$frozenTime = null;
  }

  /**
   * Check if time is currently frozen.
   *
   * @return bool True if frozen
   */
  public static function isFrozen(): bool
  {
    return null !== self::$frozenTime;
  }

  /**
   * Travel forward in time by seconds (relative to current frozen/real time).
   *
   * @param int $seconds Number of seconds to travel forward
   */
  public static function travel(int $seconds): void
  {
    $current = self::now();
    self::freeze($current + $seconds);
  }

  /**
   * Get current date in Y-m-d format.
   *
   * @return string Date in YYYY-MM-DD format
   */
  public static function today(): string
  {
    return self::date('Y-m-d');
  }

  /**
   * Get yesterday's date in Y-m-d format.
   *
   * @return string Date in YYYY-MM-DD format
   */
  public static function yesterday(): string
  {
    return self::date('Y-m-d', self::now() - FormTTL::ONE_DAY->value);
  }

  /**
   * Get tomorrow's date in Y-m-d format.
   *
   * @return string Date in YYYY-MM-DD format
   */
  public static function tomorrow(): string
  {
    return self::date('Y-m-d', self::now() + FormTTL::ONE_DAY->value);
  }

  /**
   * Get start of day timestamp (00:00:00).
   *
   * @param null|int $timestamp Optional timestamp (uses now() if null)
   * @return int Timestamp at start of day
   */
  public static function startOfDay(?int $timestamp = null): int
  {
    $time = $timestamp ?? self::now();

    return (int) strtotime(date('Y-m-d 00:00:00', $time));
  }

  /**
   * Get end of day timestamp (23:59:59).
   *
   * @param null|int $timestamp Optional timestamp (uses now() if null)
   * @return int Timestamp at end of day
   */
  public static function endOfDay(?int $timestamp = null): int
  {
    $time = $timestamp ?? self::now();

    return (int) strtotime(date('Y-m-d 23:59:59', $time));
  }

  /**
   * Parse a date string to timestamp.
   *
   * @param string $date Date string
   * @return int Unix timestamp
   */
  public static function parse(string $date): int
  {
    $timestamp = strtotime($date);
    if (false === $timestamp) {
      throw new \InvalidArgumentException("Invalid date format: {$date}");
    }

    return $timestamp;
  }

  /**
   * Get ISO 8601 formatted datetime string.
   *
   * @param null|int $timestamp Optional timestamp (uses now() if null)
   * @return string ISO 8601 datetime
   */
  public static function iso8601(?int $timestamp = null): string
  {
    $time = $timestamp ?? self::now();

    return date('c', $time);
  }

  /**
   * Get current year.
   *
   * @return int Current year
   */
  public static function year(): int
  {
    return (int) self::date('Y');
  }

  /**
   * Get current month (1-12).
   *
   * @return int Current month
   */
  public static function month(): int
  {
    return (int) self::date('n');
  }

  /**
   * Get current day of month (1-31).
   *
   * @return int Current day
   */
  public static function day(): int
  {
    return (int) self::date('j');
  }
}
