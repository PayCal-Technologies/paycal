<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * TaxBracketCollection.php
 *
 * Purpose: Define the TaxBracketCollection class for PayCal\Domain.
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
 * @implements \IteratorAggregate<int, TaxBracket>
 */
class TaxBracketCollection implements \JsonSerializable, \IteratorAggregate
{
  /** @var array<int, TaxBracket> */
  private array $brackets;

  /**
   * @param array<int, TaxBracket> $brackets
   *
   * @throws InvalidArgumentException
   */
  public function __construct(array $brackets)
  {
    if ($brackets === []) {
      throw new InvalidArgumentException("Brackets cannot be empty");
    }

    usort(
      $brackets,
      fn (TaxBracket $a, TaxBracket $b): int =>
        $a->minIncomeCents <=> $b->minIncomeCents
    );

    $count = count($brackets);

    for ($i = 0; $i < $count - 1; ++$i) {
      if ($brackets[$i]->maxIncomeCents !== $brackets[$i + 1]->minIncomeCents) {
        throw new InvalidArgumentException(
          "Brackets must be contiguous: bracket ending at {$brackets[$i]->maxIncomeCents} "
          . "does not connect to bracket starting at {$brackets[$i + 1]->minIncomeCents}"
        );
      }
    }

    $this->brackets = $brackets;
  }

  /**
   * Calculate total tax across all brackets.
   *
   * @param int $incomeCents
   *
   * @return int
   */
  public function calculateTaxCents(int $incomeCents): int
  {
    $totalTax = 0;

    foreach ($this->brackets as $bracket) {
      $totalTax += $bracket->calculateTaxCents($incomeCents);
    }

    return $totalTax;
  }

  /**
   * Calculate total tax across all brackets (in dollars).
   *
   * @param float $income Income in dollars
   *
   * @return float Tax amount in dollars
   */
  public function calculateTax(float $income): float
  {
    $incomeCents = (int) round($income * 100);
    $taxCents = $this->calculateTaxCents($incomeCents);

    return $taxCents / 100;
  }

  /**
   * Create from integer arrays:
   * [[minCents, maxCents, rateBasisPoints], ...]
   *
   * @param array<int, array{0:int,1:int,2:int}> $arrays
   *
   * @throws InvalidArgumentException
   */
  /**
   * Create from arrays (alias for fromCentsArrays for compatibility).
   * @param array<int, array{0:int,1:int,2:int}> $arrays
   * @return self
   */
  public static function fromArrays(array $arrays): self
  {
    return self::fromCentsArrays($arrays);
  }

  /**
   * @param array<int, array{0:int,1:int,2:int}> $arrays
   */
  public static function fromCentsArrays(array $arrays): self
  {
    $brackets = array_map(
      fn (array $arr): TaxBracket =>
        TaxBracket::fromCents($arr[0], $arr[1], $arr[2]),
      $arrays
    );

    return new self($brackets);
  }

  /**
   * Convert to integer arrays:
   * [[minCents, maxCents, rateBasisPoints], ...]
   *
   * @return array<int, array{0:int,1:int,2:int}>
   */
  /**
   * Convert to arrays (alias for toCentsArrays for compatibility).
   * @return array<int, array{0:int,1:int,2:int}>
   */
  public function toArrays(): array
  {
    return $this->toCentsArrays();
  }

  /**
   * @return array<int, array{0:int,1:int,2:int}>
   */
  public function toCentsArrays(): array
  {
    return array_map(
      fn (TaxBracket $bracket): array =>
        $bracket->toCentsArray(),
      $this->brackets
    );
  }

  /**
   * @return \ArrayIterator<int, TaxBracket>
   */
  public function getIterator(): \ArrayIterator
  {
    return new \ArrayIterator($this->brackets);
  }

  /**
   * JSON serialize using integer representation only.
   *
   * @return array<int, array{0:int,1:int,2:int}>
   */
  public function jsonSerialize(): array
  {
    return $this->toCentsArrays();
  }

  /**
   * Handles count operation.
   */
  public function count(): int
  {
    return count($this->brackets);
  }
}
