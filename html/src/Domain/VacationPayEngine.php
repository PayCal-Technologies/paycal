<?php declare(strict_types=1);

namespace PayCal\Domain;

use InvalidArgumentException;
use RuntimeException;

/**
 * Minimum vacation-pay percentages by jurisdiction and year.
 *
 * The caller passes the wages that belong in the official wage base.
 * Notes on each rule say what that base includes.
 */
final class VacationPayEngine
{
  /** @var array<string, mixed>|null */
  private static ?array $data = null;

  /** Prevent construction of the static vacation service. */
  private function __construct()
  {
  }

  /**
   * @return array<string, mixed>
   */
  public static function data(): array
  {
    if (self::$data === null) {
      $decoded = json_decode((string) file_get_contents(__DIR__ . '/VacationPayData.json'), true);
      if (!is_array($decoded) || !isset($decoded['versions']) || !is_array($decoded['versions'])) {
        throw new RuntimeException('Vacation pay data is missing or invalid.');
      }
      self::$data = PayRuleData::object($decoded);
    }

    return self::$data;
  }

  /**
   * @return array<string, mixed>
   */
  public static function rule(string $code, int $year): array
  {
    $normalized = self::code($code);
    $version = self::versionForYear($year);
    $jurisdictions = $version['jurisdictions'] ?? null;
    if (!is_array($jurisdictions) || !isset($jurisdictions[$normalized]) || !is_array($jurisdictions[$normalized])) {
      throw new InvalidArgumentException('Unknown vacation-pay jurisdiction: ' . $code);
    }

    $rule = PayRuleData::object($jurisdictions[$normalized]);
    $rule['code'] = $normalized;
    $rule['year'] = $year;
    $rule['version_year'] = (int) PayRuleData::number($version['version_year']);
    $rule['extrapolated'] = $year > (int) PayRuleData::number($version['through_year'] ?? $year);

    return $rule;
  }

  /**
   * @return array{label: string, numerator: int, denominator: int, min_years: float, basis_points: int}
   */
  public static function tier(string $code, float $yearsOfService, int $year): array
  {
    if ($yearsOfService < 0) {
      throw new InvalidArgumentException('Years of service cannot be negative.');
    }

    $rule = self::rule($code, $year);
    $chosen = null;
    foreach (PayRuleData::entries($rule['tiers']) as $tier) {
      if (!is_array($tier)) {
        continue;
      }
      $minYears = PayRuleData::number($tier['min_years'] ?? 0);
      if (!empty($tier['exclusive_min_years']) ? $yearsOfService > $minYears : $yearsOfService >= $minYears) {
        $chosen = $tier;
      }
    }

    if ($chosen === null) {
      throw new RuntimeException('Vacation pay rule has no tier.');
    }

    $numerator = (int) PayRuleData::number($chosen['numerator']);
    $denominator = (int) PayRuleData::number($chosen['denominator']);
    if ($denominator <= 0) {
      throw new RuntimeException('Vacation pay tier denominator must be positive.');
    }

    return [
      'label' => PayRuleData::text($chosen['label']),
      'numerator' => $numerator,
      'denominator' => $denominator,
      'min_years' => PayRuleData::number($chosen['min_years']),
      'basis_points' => (int) round(($numerator / $denominator) * 10000),
    ];
  }

  /**
   * Vacation pay in cents on wages that are already in the rule's wage base.
   * This amount is an accrual. It is not added to gross by this method.
   */
  public static function accrualCents(string $code, int $vacationableWagesCents, float $yearsOfService, int $year): int
  {
    if ($vacationableWagesCents < 0) {
      throw new InvalidArgumentException('Wages cannot be negative.');
    }

    $tier = self::tier($code, $yearsOfService, $year);

    return (int) round($vacationableWagesCents * $tier['numerator'] / $tier['denominator']);
  }

  /**
   * @return list<string>
   */
  public static function codes(): array
  {
    $version = self::versionForYear(2026);
    $jurisdictions = $version['jurisdictions'] ?? [];

    return array_keys(is_array($jurisdictions) ? $jurisdictions : []);
  }

  /**
   * @return array<string, mixed>
   */
  private static function versionForYear(int $year): array
  {
    $versions = PayRuleData::entries(self::data()['versions']);
    if ($versions === []) { throw new RuntimeException('Missing vacation rule versions.'); }
    $chosenYear = null;
    foreach (array_keys($versions) as $versionYear) {
      $versionYear = (int) $versionYear;
      if ($versionYear <= $year && ($chosenYear === null || $versionYear > $chosenYear)) {
        $chosenYear = $versionYear;
      }
    }

    if ($chosenYear === null) {
      $chosenYear = (int) min(array_map('intval', array_keys($versions)));
    }

    $version = PayRuleData::object($versions[(string) $chosenYear]);
    $version['version_year'] = $chosenYear;

    return $version;
  }

  /** Normalize and validate the vacation jurisdiction code. */
  private static function code(string $code): string
  {
    $rule = OvertimeRules::resolve($code);
    $resolved = PayRuleData::text($rule['code'] ?? '');
    if ($resolved === '' || $resolved === OvertimeRules::LEGACY) {
      throw new InvalidArgumentException('Unknown vacation-pay jurisdiction: ' . $code);
    }

    return $resolved;
  }
}
