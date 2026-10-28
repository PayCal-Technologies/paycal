<?php declare(strict_types=1);

namespace PayCal\Domain;

/** Validates decoded rule objects and scalar calculator inputs. */
final class PayRuleData
{
  /** Return only named object properties, rejecting malformed JSON objects. */
  public static function text(mixed $value): string
  {
    return is_scalar($value) ? (string) $value : '';
  }

  /** Read a finite numeric input without casting arrays or objects. */
  public static function number(mixed $value): float
  {
    if (!is_numeric($value)) {
      return 0.0;
    }
    $number = (float) $value;
    return is_finite($number) ? $number : 0.0;
  }

  /** @return array<string, mixed> */
  public static function object(mixed $value): array
  {
    if (!is_array($value)) {
      throw new \InvalidArgumentException('Expected a pay rule object.');
    }
    $result = [];
    foreach ($value as $key => $item) {
      if (!is_string($key)) {
        throw new \InvalidArgumentException('Expected named pay rule properties.');
      }
      $result[$key] = $item;
    }
    return $result;
  }

  /** @return array<int|string, mixed> */
  public static function entries(mixed $value): array
  {
    if (!is_array($value)) {
      throw new \InvalidArgumentException('Expected a pay rule collection.');
    }
    return $value;
  }
}
