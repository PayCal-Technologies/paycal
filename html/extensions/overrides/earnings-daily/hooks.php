<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsDaily;

use PayCal\Domain\Earnings;
use PayCal\Domain\Money;
use PayCal\Domain\Taxes;
use PayCal\Domain\User;

/**
 * Private daily earnings payload hook.
 */
final class Hooks
{
  /**
   * @param array<string, mixed> $context
   * @return array<string, array<string, string>>
   */
  public static function render(array $context): array
  {
    $yearCandidate = $context['year'] ?? null;
    $year = (is_int($yearCandidate) || is_string($yearCandidate)) ? (int) $yearCandidate : (int) date('Y');
    $payload = $context['payload'] ?? [];

    if (!is_array($payload)) {
      return [];
    }

    /** @var array<string, array<string, string>> $rows */
    $rows = [];

    foreach ($payload as $date => $row) {
      if (!is_array($row)) {
        continue;
      }

      $dateKey = (string) $date;
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateKey)) {
        continue;
      }

      $day = new \DateTimeImmutable($dateKey);
      $totals = Earnings::getTotalsForRange($day, $day, User::currentUUID());

      $grossCents = (int) $totals['totals']['grossCents'];
      $taxCents = (int) $totals['totals']['taxCents'];
      $netCents = (int) $totals['totals']['netCents'];

      $taxYear = (int) substr($dateKey, 0, 4);
      if ($taxYear < 1900 || $taxYear > 3000) {
        $taxYear = $year;
      }

      $tax = new Taxes('Alberta', $taxYear);
      $taxBreakdown = $tax->calculateTaxesCents($grossCents);

      $sites = $totals['sites'];
      $siteName = '';
      if (count($sites) > 1) {
        $siteName = 'Multiple Sites';
      } elseif (isset($sites[0])) {
        $siteName = $sites[0];
      }

      $regularHours = (float) $totals['hours']['regular'];
      $overtimeHours = (float) $totals['hours']['overtime'];
      $travelHours = (float) $totals['hours']['travel'];
      $totalHours = (float) $totals['hours']['total'];

      $wage = '';
      $avgWage = $totals['payRate']['avg'];
      if (is_string($avgWage)) {
        $wage = $avgWage;
      }

      $rows[$dateKey] = [
        'date' => $dateKey,
        'site_id' => '',
        'site_name' => $siteName,
        'wage' => $wage,
        'hours' => number_format($totalHours, 2, '.', ''),
        'regular_hours' => number_format($regularHours, 2, '.', ''),
        'overtime_hours' => number_format($overtimeHours, 2, '.', ''),
        'travel_hours' => number_format($travelHours, 2, '.', ''),
        'living_out_allowance' => (string) $totals['amounts']['loa'],
        'gross' => Money::centsToDollars($grossCents),
        'federal_tax' => Money::centsToDollars((int) $taxBreakdown['federal']),
        'provincial_tax' => Money::centsToDollars((int) $taxBreakdown['provincial']),
        'employment_insurance' => Money::centsToDollars((int) $taxBreakdown['employment_insurance']),
        'canada_pension_plan' => Money::centsToDollars((int) $taxBreakdown['canada_pension_plan']),
        'old_age_security' => Money::centsToDollars((int) $taxBreakdown['old_age_security']),
        'tax' => Money::centsToDollars($taxCents),
        'deductions' => Money::centsToDollars($taxCents),
        'net' => Money::centsToDollars($netCents),
      ];
    }

    return $rows;
  }
}
