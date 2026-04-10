<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * TaxBracket.php
 *
 * Purpose: Define the TaxBracket class for PayCal\Domain.
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
 * Immutable value object representing a single tax bracket.
 *
 * A tax bracket defines a range of income and the tax rate applied to that range.
 * Example: [$0 - $54,713) taxed at 15%
 *
 * Boundary Semantics:
 * - minIncome/minIncomeCents is INCLUSIVE (income at this value is taxed in this bracket)
 * - maxIncome/maxIncomeCents is EXCLUSIVE (income at this value is NOT taxed in this bracket)
 *
 * Rate Representation:
 * - rate: float decimal (0.15 = 15%)
 * - rateBasisPoints: integer (1500 = 15%, where 1% = 100 basis points)
 */
class TaxBracket implements \JsonSerializable
{
  // Float fields removed. Use integer cents and basis points only.

  /** @var int Minimum income in cents (inclusive) */
  public readonly int $minIncomeCents;

  /** @var int Maximum income in cents (exclusive), PHP_INT_MAX for unlimited */
  public readonly int $maxIncomeCents;

  /** @var int Tax rate in basis points (1500 = 15%, 1% = 100 basis points) */
  public readonly int $rateBasisPoints;

  /** @var bool Whether this bracket has no upper limit */
  public readonly bool $isUnlimited;

  /**
   * Create a new tax bracket.
   *
   * @param int $minIncome Minimum income in cents (inclusive)
   * @param int $maxIncome Maximum income in cents (exclusive)
   * @param int $rate      Tax rate in basis points (1500 = 15%)
   *
   * @throws InvalidArgumentException if validation fails
   */
  public function __construct(int $minIncome, int $maxIncome, int $rate)
  {
    if ($minIncome < 0) {
      throw new InvalidArgumentException("Min income cannot be negative: {$minIncome}");
    }
    if ($maxIncome <= $minIncome) {
      throw new InvalidArgumentException("Max income ({$maxIncome}) must be greater than min income ({$minIncome})");
    }
    if ($rate < 0 || $rate > 10000) {
      throw new InvalidArgumentException("Tax rate must be between 0 and 10000 basis points, got: {$rate}");
    }
    $this->minIncomeCents = $minIncome;
    $this->maxIncomeCents = $maxIncome;
    $this->rateBasisPoints = $rate;
    $this->isUnlimited = (PHP_INT_MAX === $maxIncome);
  }

  /**
   * Create from array format [min, max, rate].
   *
   * @param array<int, float|int> $array Array with exactly 3 elements
   *
   * @throws InvalidArgumentException if array is invalid
   */
  public static function fromArray(array $array): self
  {
    $itemCount = count($array);
    if (3 !== $itemCount) {
      throw new InvalidArgumentException('Array must have exactly 3 elements, got: '.$itemCount);
    }

    return new self((int) $array[0], (int) $array[1], (int) $array[2]);
  }

  /**
   * Create from integer values (cents and basis points).
   *
   * @param int $minIncomeCents  Minimum income in cents (inclusive)
   * @param int $maxIncomeCents  Maximum income in cents (exclusive)
   * @param int $rateBasisPoints Tax rate in basis points (1500 = 15%)
   *
   * @throws InvalidArgumentException if validation fails
   */
  public static function fromCents(int $minIncomeCents, int $maxIncomeCents, int $rateBasisPoints): self
  {
    if ($minIncomeCents < 0) {
      throw new InvalidArgumentException("Min income cannot be negative: {$minIncomeCents} cents");
    }
    if ($maxIncomeCents <= $minIncomeCents) {
      throw new InvalidArgumentException("Max income ({$maxIncomeCents}) must be greater than min income ({$minIncomeCents})");
    }
    if ($rateBasisPoints < 0 || $rateBasisPoints > 10000) {
      throw new InvalidArgumentException("Tax rate must be between 0 and 10000 basis points, got: {$rateBasisPoints}");
    }

    return new self($minIncomeCents, $maxIncomeCents, $rateBasisPoints);
  }

  /**
   * Convert to array format [min, max, rate] for backward compatibility.
   *
   * @return array{0: float, 1: float, 2: float}
   */
  public function toArray(): array
  {
    return [$this->minIncomeCents, $this->maxIncomeCents, $this->rateBasisPoints];
  }

  /**
   * Convert to array format with integer values [minCents, maxCents, rateBasisPoints].
   *
   * @return array{0: int, 1: int, 2: int}
   */
  public function toCentsArray(): array
  {
    return [$this->minIncomeCents, $this->maxIncomeCents, $this->rateBasisPoints];
  }

  // Float tax engine method fully removed. Use integer-only engine.

  /**
   * Calculate tax for given income in cents within this bracket (integer version).
   *
   * Uses deterministic integer math with explicit HALF_UP rounding.
   * This avoids floating-point precision issues.
   *
   * Formula: tax_cents = (taxable_cents × rate_basis_points) / 10000
   *
   * Example:
   *   Income: 123456 cents ($1,234.56)
   *   Rate: 1500 basis points (15%)
   *   Tax: (123456 × 1500) / 10000 = 185184000 / 10000 = 18518.4 → 18518 cents
   *
   * @param int $incomeCents Total income in cents
   *
   * @return int Tax amount in cents (always non-negative)
   */
  public function calculateTaxCents(int $incomeCents): int
  {
    // Income at or below minimum: no tax in this bracket
    if ($incomeCents <= $this->minIncomeCents) {
      return 0;
    }

    // Calculate taxable amount
    // For unlimited brackets, don't cap at maxIncomeCents
    // Mutation: Remove max cap logic
    $upperBound = min($incomeCents, $this->maxIncomeCents);
    $taxableCents = $upperBound - $this->minIncomeCents;

    // Ensure non-negative (should always be, but defensive)
    if ($taxableCents <= 0) {
      return 0;
    }

    // Explicit HALF_UP rounding to nearest cent
    return intdiv($taxableCents * $this->rateBasisPoints + 5000, 10000);
  }

  /**
   * JSON serialize to array format for backward compatibility.
   *
   * @return array{0: float, 1: float, 2: float}
   */
  public function jsonSerialize(): array
  {
    return $this->toArray();
  }
}
