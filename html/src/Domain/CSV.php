<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * CSV.php
 *
 * Purpose: Define the CSV class for PayCal\Domain.
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

class CSV
{
  /**
   * Handles scalarString operation.
   */
  private static function scalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles numericInt operation.
   */
  private static function numericInt(mixed $value, int $default = 0): int
  {
    return is_numeric($value) ? (int) $value : $default;
  }

  /**
   * Export work entries for a given year as CSV.
   * Emits a header row followed by one row per work entry and a final totals row.
   * Columns: date, gross, regular_hours, overtime_hours, cum_gross, cum_regular_hours, cum_overtime_hours.
   *
   * @return string CSV content
   */
  public function getYear(int $year): string
  {
    $start = new \DateTimeImmutable("{$year}-01-01");
    $end = new \DateTimeImmutable("{$year}-12-31");

    // Fetch raw rows in range (data layer)
    $rows = Work::getInstance()->getWorkInRange(
			new \DateTimeImmutable($start->format('Y-m-d')),
			new \DateTimeImmutable($end->modify('+1 day')->format('Y-m-d'))
		);

    // Running totals
    $cumGross = 0.0;
    $cumReg = 0.0;
    $cumOT = 0.0;

    // Build CSV in-memory
    $fh = fopen('php://temp', 'r+');

    if (false === $fh) {
      return '';
    }

    // @var resource $fh
    fputcsv(
      $fh,
      [
            'Date',
            'Site',
            'Wage',
            'Hours',
            'Regular',
            'OT',
            'Gross',
            'Net',
            'FTax',
            'PTax',
            'EI',
            'CPP',
            'OAS',
        ],
      ',',
      '"',
      '\\',
    );

    foreach ($rows as $row) {
      // Minimal validation; earnings payload guarantees g/r/o numeric
      if (
        !isset($row['gross'], $row['regular_hours'], $row['overtime_hours'])
        || !is_numeric($row['gross'])
        || !is_numeric($row['regular_hours'])
        || !is_numeric($row['overtime_hours'])
      ) {
        // Skip bad rows; caller can inspect logs
        continue;
      }

      $date = self::scalarString($row['date'] ?? $row['id'] ?? '');
      $siteName = self::scalarString($row['site_name'] ?? '');
      $wage = self::scalarString($row['wage'] ?? '0.00');

      $h = self::numericInt($row['hours'] ?? 0);
      $g = self::numericInt($row['gross']);
      $r = self::numericInt($row['regular_hours']);
      $o = self::numericInt($row['overtime_hours']);

      // Calculate taxes using the entry year for historical bracket accuracy
      $entryYear = (int) substr($date, 0, 4);
      $tax = new Taxes('Alberta', $entryYear);
      $t = $tax->calculateTaxesCents($g);
      $net = $g - $t['totalDeductions'];

      $cumGross += $g;
      $cumReg += $r;
      $cumOT += $o;

      fputcsv($fh, [
          $date,
          $siteName,
          Money::centsToDollars((int)$wage),
          Money::centsToDollars((int)$h),
          Money::centsToDollars((int)$r),
          Money::centsToDollars((int)$o),
          number_format($g, 2, '.', ''),
          number_format($net, 2, '.', ''),
          number_format($t['federal'], 2, '.', ''),
          number_format($t['provincial'], 2, '.', ''),
          number_format($t['employment_insurance'], 2, '.', ''),
          number_format($t['canada_pension_plan'], 2, '.', ''),
          number_format($t['old_age_security'], 2, '.', ''),
      ], ',', '"', '\\');
    }

    // Final totals row
    // Columns: Date, Site, Wage, Hours, Regular, OT, Gross, Net, FTax, PTax, EI, CPP, OAS
    $totals = Earnings::getWorkTotalsForRange($start, $end);
    fputcsv($fh, [], ',', '"', '\\'); // blank line
    fputcsv(
      $fh,
      [
            'TOTALS',    // Date
            '',          // Site
            '',          // Wage
            '',          // Hours
            number_format($totals['regularHours'], 2, '.', ''),   // Regular
            number_format($totals['overtimeHours'], 2, '.', ''),  // OT
            number_format($totals['grossIncome'], 2, '.', ''),    // Gross
            '',          // Net
            '',          // FTax
            '',          // PTax
            '',          // EI
            '',          // CPP
            '',          // OAS
        ],
      ',',
      '"',
      '\\'
    );

    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);

    return (string) $csv;
  }
}

