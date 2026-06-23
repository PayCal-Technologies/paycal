<?php declare(strict_types=1);

namespace PayCal\Tests\Support;

use PayCal\Domain\BusinessMemberReportsService;
use PayCal\Domain\Money;
use PayCal\Domain\Taxes;

/**
 * Shared helpers for forensic tax, earnings, and work-entry tests.
 *
 * Mirrors production aggregation/render logic so tests can verify behavior
 * without duplicating large blocks in every test class.
 */
final class ForensicTaxSupport
{
  /**
   * Replicate collectMemberBusinessWork monthly rollup for isolated unit tests.
   *
   * @param list<array<string, mixed>> $entries
   * @return array{
   *   years: list<int>,
   *   by_year: array<int, array{
   *     reg_hours: float,
   *     ot_hours: float,
   *     gross: float,
   *     net: float,
   *     gross_by_date: array<string, float>,
   *     active_months: array<string, true>,
   *     months: list<array{month: string, label: string, reg_hours: float, ot_hours: float, gross: float, net: float}>,
   *     daily_entries: array<string, array<string, string>>
   *   }>,
   *   gross_by_year_cents: array<int, int>
   * }
   */
  public static function buildWorkSnapshotFromEntries(array $entries): array
  {
    /** @var array<int, array<string, mixed>> $byYear */
    $byYear = [];
    /** @var array<int, int> $grossByYearCents */
    $grossByYearCents = [];

    foreach ($entries as $entry) {
      $date = (string) ($entry['date'] ?? '');
      if ($date === '' || strlen($date) < 4) {
        continue;
      }

      $entryYear = (int) substr($date, 0, 4);
      if ($entryYear < 2000 || $entryYear > 2100) {
        continue;
      }

      if (!isset($byYear[$entryYear])) {
        $byYear[$entryYear] = [
          'reg_hours' => 0.0,
          'ot_hours' => 0.0,
          'gross' => 0.0,
          'net' => 0.0,
          'gross_by_date' => [],
          'active_months' => [],
          'monthly_breakdown' => [],
          'daily_entries' => [],
        ];
      }

      $month = strlen($date) >= 7 ? substr($date, 0, 7) : 'unknown';
      $entryReg = (float) ($entry['regular_hours'] ?? $entry['r'] ?? 0);
      $entryOt = (float) ($entry['overtime_hours'] ?? $entry['o'] ?? 0);
      $entryTravel = (float) ($entry['travel_hours'] ?? $entry['t'] ?? 0);
      $entryHours = (float) ($entry['hours'] ?? $entry['h'] ?? ($entryReg + $entryOt + $entryTravel));
      $entryGross = (float) ($entry['gross'] ?? $entry['g'] ?? 0);
      $entryTax = (float) ($entry['tax'] ?? $entry['tx'] ?? 0);
      $entryNet = (float) ($entry['net'] ?? ($entryGross - $entryTax));
      $entryLoa = (float) ($entry['living_out_allowance'] ?? $entry['l'] ?? 0);
      $entryWage = (float) ($entry['wage'] ?? $entry['w'] ?? 0);
      $siteName = trim((string) ($entry['site_name'] ?? ''));

      $byYear[$entryYear]['reg_hours'] += $entryReg;
      $byYear[$entryYear]['ot_hours'] += $entryOt;
      $byYear[$entryYear]['gross'] += $entryGross;
      $byYear[$entryYear]['net'] += $entryNet;

      if (!isset($byYear[$entryYear]['gross_by_date'][$date])) {
        $byYear[$entryYear]['gross_by_date'][$date] = 0.0;
      }
      $byYear[$entryYear]['gross_by_date'][$date] += $entryGross;

      if ($month !== 'unknown') {
        $byYear[$entryYear]['active_months'][$month] = true;
      }

      if (!isset($byYear[$entryYear]['monthly_breakdown'][$month])) {
        $byYear[$entryYear]['monthly_breakdown'][$month] = [
          'reg_hours' => 0.0,
          'ot_hours' => 0.0,
          'gross' => 0.0,
          'net' => 0.0,
        ];
      }

      $byYear[$entryYear]['monthly_breakdown'][$month]['reg_hours'] += $entryReg;
      $byYear[$entryYear]['monthly_breakdown'][$month]['ot_hours'] += $entryOt;
      $byYear[$entryYear]['monthly_breakdown'][$month]['gross'] += $entryGross;
      $byYear[$entryYear]['monthly_breakdown'][$month]['net'] += $entryNet;

      if (!isset($byYear[$entryYear]['daily_entries'][$date])) {
        $byYear[$entryYear]['daily_entries'][$date] = [
          'site_name' => $siteName,
          'wage' => number_format($entryWage, 2, '.', ''),
          'hours' => number_format($entryHours, 2, '.', ''),
          'regular_hours' => number_format($entryReg, 2, '.', ''),
          'overtime_hours' => number_format($entryOt, 2, '.', ''),
          'travel_hours' => number_format($entryTravel, 2, '.', ''),
          'living_out_allowance' => number_format($entryLoa, 2, '.', ''),
          'gross' => number_format($entryGross, 2, '.', ''),
          'tax' => number_format($entryTax, 2, '.', ''),
          'deductions' => number_format($entryTax, 2, '.', ''),
          'net' => number_format($entryNet, 2, '.', ''),
        ];
      } else {
        $existing = &$byYear[$entryYear]['daily_entries'][$date];
        $existing['site_name'] = $existing['site_name'] !== '' ? $existing['site_name'] . ', ' . $siteName : $siteName;
        $existing['hours'] = number_format((float) $existing['hours'] + $entryHours, 2, '.', '');
        $existing['regular_hours'] = number_format((float) $existing['regular_hours'] + $entryReg, 2, '.', '');
        $existing['overtime_hours'] = number_format((float) $existing['overtime_hours'] + $entryOt, 2, '.', '');
        $existing['travel_hours'] = number_format((float) $existing['travel_hours'] + $entryTravel, 2, '.', '');
        $existing['living_out_allowance'] = number_format((float) $existing['living_out_allowance'] + $entryLoa, 2, '.', '');
        $existing['gross'] = number_format((float) $existing['gross'] + $entryGross, 2, '.', '');
        $existing['tax'] = number_format((float) $existing['tax'] + $entryTax, 2, '.', '');
        $existing['deductions'] = $existing['tax'];
        $existing['net'] = number_format((float) $existing['net'] + $entryNet, 2, '.', '');
      }
    }

    $years = array_keys($byYear);
    sort($years);
    $years = array_values(array_map(static fn (int|string $y): int => (int) $y, $years));

    $normalizedByYear = [];
    foreach ($byYear as $entryYear => $values) {
      $months = [];
      ksort($values['monthly_breakdown']);
      foreach ($values['monthly_breakdown'] as $yearMonth => $monthValues) {
        $dateTime = \DateTimeImmutable::createFromFormat('Y-m', (string) $yearMonth);
        $months[] = [
          'month' => (string) $yearMonth,
          'label' => $dateTime !== false ? $dateTime->format('M Y') : (string) $yearMonth,
          'reg_hours' => round((float) $monthValues['reg_hours'], 2),
          'ot_hours' => round((float) $monthValues['ot_hours'], 2),
          'gross' => round((float) $monthValues['gross'], 2),
          'net' => round((float) $monthValues['net'], 2),
        ];
      }

      $grossByDate = [];
      foreach ($values['gross_by_date'] as $dateKey => $grossValue) {
        $grossByDate[(string) $dateKey] = round((float) $grossValue, 2);
      }
      ksort($grossByDate);
      ksort($values['daily_entries']);

      $normalizedByYear[(int) $entryYear] = [
        'reg_hours' => round((float) $values['reg_hours'], 2),
        'ot_hours' => round((float) $values['ot_hours'], 2),
        'gross' => round((float) $values['gross'], 2),
        'net' => round((float) $values['net'], 2),
        'gross_by_date' => $grossByDate,
        'active_months' => $values['active_months'],
        'months' => $months,
        'daily_entries' => $values['daily_entries'],
      ];

      $grossByYearCents[(int) $entryYear] = Money::dollarsToCents((string) $values['gross']);
    }

    return [
      'years' => $years,
      'by_year' => $normalizedByYear,
      'gross_by_year_cents' => $grossByYearCents,
    ];
  }

  /**
   * Mirror renderMemberMonthlyViewStrip deduction derivation (gross − net).
   *
   * @param array<string, mixed> $workSnapshot
   * @return array<string, array{gross: float, net: float, deductions: float}>
   */
  public static function deriveMemberMonthlyDeductionsGrossMinusNet(array $workSnapshot, int $year): array
  {
    $yearData = is_array($workSnapshot['by_year'][$year] ?? null) ? $workSnapshot['by_year'][$year] : [];
    $monthlyMap = [];
    foreach ($yearData['months'] ?? [] as $monthRow) {
      if (!is_array($monthRow)) {
        continue;
      }
      $monthlyMap[(string) ($monthRow['month'] ?? '')] = $monthRow;
    }

    $result = [];
    for ($month = 1; $month <= 12; ++$month) {
      $monthKey = sprintf('%04d-%02d', $year, $month);
      $monthValues = is_array($monthlyMap[$monthKey] ?? null) ? $monthlyMap[$monthKey] : null;
      $gross = $monthValues !== null ? (float) ($monthValues['gross'] ?? 0) : 0.0;
      $net = $monthValues !== null ? (float) ($monthValues['net'] ?? 0) : 0.0;
      $deductions = max(0.0, $gross - $net);
      $result[$monthKey] = ['gross' => $gross, 'net' => $net, 'deductions' => $deductions];
    }

    return $result;
  }

  /**
   * YTD cumulative delta monthly deductions (reference implementation from earnings-monthly hooks).
   *
   * @param array<string, mixed> $workSnapshot
   * @return array<string, array{gross: int, deductions: int, net: int}>
   */
  public static function computeYtdDeltaMonthlyDeductions(
    array $workSnapshot,
    int $year,
    string $province = 'Alberta',
  ): array {
    $tax = new Taxes($province, $year);
    $yearData = is_array($workSnapshot['by_year'][$year] ?? null) ? $workSnapshot['by_year'][$year] : [];
    $grossByDate = is_array($yearData['gross_by_date'] ?? null) ? $yearData['gross_by_date'] : [];

    $prevGrossCents = 0;
    $prevTaxCents = 0;
    $result = [];

    for ($month = 1; $month <= 12; ++$month) {
      $monthKey = sprintf('%04d-%02d', $year, $month);
      $ytdGrossCents = 0;
      foreach ($grossByDate as $date => $gross) {
        if (strncmp((string) $date, $monthKey, 7) <= 0) {
          $ytdGrossCents += Money::dollarsToCents((string) $gross);
        }
      }

      $ytdTaxCents = (int) $tax->calculateTaxesCents($ytdGrossCents)['totalDeductions'];
      $monthGrossCents = $ytdGrossCents - $prevGrossCents;
      $monthTaxCents = $ytdTaxCents - $prevTaxCents;
      $monthNetCents = $monthGrossCents - $monthTaxCents;

      $result[$monthKey] = [
        'gross' => $monthGrossCents,
        'deductions' => $monthTaxCents,
        'net' => $monthNetCents,
      ];

      $prevGrossCents = $ytdGrossCents;
      $prevTaxCents = $ytdTaxCents;
    }

    return $result;
  }

  public static function formatDataGridCurrency(float $dollars): string
  {
    return '$' . number_format($dollars, 2, '.', ',');
  }

  /**
   * @param list<mixed> $args
   */
  public static function invokeBusinessMemberReportsPrivate(
    string $methodName,
    mixed ...$args,
  ): mixed {
    $service = new BusinessMemberReportsService();
    $method = new \ReflectionMethod(BusinessMemberReportsService::class, $methodName);

    return $method->invoke($service, ...$args);
  }

  public static function redisWorkKey(string $memberUuid, string $date, string $siteId): string
  {
    return 'work:' . $memberUuid . ':' . $date . ':' . $siteId;
  }

  public static function redisArchivedWorkKey(string $memberUuid, string $date, string $siteId): string
  {
    return 'work:archived:' . $memberUuid . ':' . $date . ':' . $siteId;
  }
}
