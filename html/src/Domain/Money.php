<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Money.php
 *
 * Purpose: Money and wage arithmetic helper for cents/dollar conversion,
 * rounding-safe calculations, and payroll-oriented numeric normalization.
 *
 * Developer notes:
 * - Monetary math should remain deterministic and integer-centric wherever
 *   possible to avoid drift across reporting and persistence paths.
 * - Changes here affect wages, earnings, exports, and tax-related outputs.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Money arithmetic helper.
 *
 * Responsibilities:
 * - Convert between user-facing currency strings and integer cents.
 * - Provide shared payroll-safe arithmetic helpers.
 * - Centralize monetary normalization used throughout the app.
 */
final class Money
{
  /**
   * Convert a dollar string to integer cents.
   *
   * @param string $dollars Dollar amount as string (e.g., "25.50", "19.99", "100")
   *
   * @return int Amount in cents (e.g., 2550, 1999, 10000)
   */
  public static function dollarsToCents(string $dollars): int
  {
    // Handle empty string
    if ('' === $dollars || '0' === $dollars) {
      return 0;
    }

    // Remove any whitespace
    $dollars = trim($dollars);

    // Handle negative amounts
    $negative = false;
    if (str_starts_with($dollars, '-')) {
      $negative = true;
      $dollars = substr($dollars, 1);
    }

    // Split on decimal point
    $parts = explode('.', $dollars);

    $wholeDollars = (int) $parts[0];
    $cents = 0;

    if (isset($parts[1])) {
      $decimalStr = $parts[1];
      $decimalLen = strlen($decimalStr);

      if (1 === $decimalLen) {
        // "25.5" means 50 cents
        $cents = (int) $decimalStr * 10;
      } elseif (2 === $decimalLen) {
        // "25.50" means 50 cents
        $cents = (int) $decimalStr;
      } else {
        // More than 2 decimal places - round to nearest cent
        // Pad or truncate to 3 digits for proper rounding
        $decimalStr = str_pad(substr($decimalStr, 0, 3), 3, '0');
        $cents = (int) round((int) $decimalStr / 10);
      }
    }

    $totalCents = ($wholeDollars * 100) + $cents;

    return $negative ? -$totalCents : $totalCents;
  }

  /**
   * Convert integer cents to a dollar string.
   *
   * @param int $cents Amount in cents
   *
   * @return string Dollar amount as string (e.g., "25.50", "19.99", "100.00")
   */
  public static function centsToDollars(int $cents): string
  {
    $negative = $cents < 0;
    if ($negative) {
      $cents = abs($cents);
    }

    $dollars = intdiv($cents, 100);
    $remainingCents = $cents % 100;

    $result = sprintf('%d.%02d', $dollars, $remainingCents);

    return $negative ? '-'.$result : $result;
  }

  /**
   * Calculate gross pay in cents from hours and wage.
   *
   * Uses per-component rounding:
   * - Regular hours × wage rounded separately
   * - Overtime hours × wage × 1.5 rounded separately
   * - Then summed
   *
   * This matches standard payroll practice.
   *
   * @param float  $regularHours  Regular hours worked
   * @param float  $overtimeHours Overtime hours worked
   * @param string $wageDollars   Hourly wage as dollar string (e.g., "25.50")
   *
   * @return int Gross pay in cents
   */
  public static function calculateGross(float $regularHours, float $overtimeHours, string $wageDollars): int
  {
    $wageCents = self::dollarsToCents($wageDollars);

    // Calculate regular pay (rounded to nearest cent)
    $regularCents = (int) round($regularHours * $wageCents);

    // Calculate overtime pay (time and a half, rounded to nearest cent)
    $overtimeCents = (int) round($overtimeHours * $wageCents * 1.5);

    return $regularCents + $overtimeCents;
  }

  /**
   * Calculate gross pay from a single hours value (no overtime).
   *
   * @param float  $hours       Hours worked
   * @param string $wageDollars Hourly wage as dollar string
   *
   * @return int Gross pay in cents
   */
  public static function calculateGrossSimple(float $hours, string $wageDollars): int
  {
    return self::calculateGross($hours, 0.0, $wageDollars);
  }

  /**
   * Multiply hours by wage and return cents.
   *
   * This is the core multiplication that must never drift.
   *
   * @param float  $hours       Hours worked
   * @param string $wageDollars Hourly wage as dollar string
   *
   * @return int Amount in cents
   */
  public static function multiply(float $hours, string $wageDollars): int
  {
    $wageCents = self::dollarsToCents($wageDollars);

    return (int) round($hours * $wageCents);
  }

  /**
   * Add two cent amounts safely.
   *
   * @param int $centsA First amount in cents
   * @param int $centsB Second amount in cents
   *
   * @return int Sum in cents
   */
  public static function add(int $centsA, int $centsB): int
  {
    return $centsA + $centsB;
  }

  /**
   * Subtract two cent amounts safely.
   *
   * @param int $centsA First amount in cents
   * @param int $centsB Second amount in cents
   *
   * @return int Difference in cents
   */
  public static function subtract(int $centsA, int $centsB): int
  {
    return $centsA - $centsB;
  }

  /**
   * Multiply cents by a factor (e.g., tax rate).
   *
   * @param int   $cents  Amount in cents
   * @param float $factor Multiplication factor
   *
   * @return int Result in cents (rounded)
   */
  public static function multiplyByFactor(int $cents, float $factor): int
  {
    return (int) round($cents * $factor);
  }

  /**
   * Divide cents by a divisor.
   *
   * @param int   $cents   Amount in cents
   * @param float $divisor Divisor
   *
   * @return int Result in cents (rounded)
   */
  public static function divide(int $cents, float $divisor): int
  {
    if (0.0 === $divisor) {
      return 0;
    }

    return (int) round($cents / $divisor);
  }
}
