<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsMonthly;

use PayCal\Domain\Earnings;
use PayCal\Domain\DataGrid;
use PayCal\Domain\Strings;
use PayCal\Domain\Taxes;
use PayCal\Domain\User;

/**
 * Private monthly earnings renderer hook.
 */
final class Hooks
{
  /**
   * @param array<string, scalar|null> $params
   */
  private static function formatI18n(string $key, array $params = []): string
  {
    $label = Strings::i18n($key);
    foreach ($params as $paramKey => $paramValue) {
      $label = str_replace('{' . $paramKey . '}', (string) $paramValue, $label);
    }

    return $label;
  }

  /**
   * @param array<string, mixed> $context
   */
  public static function render(array $context): string
  {
    $yearCandidate = $context['year'] ?? null;
    $year = (is_int($yearCandidate) || is_string($yearCandidate)) ? (int) $yearCandidate : (int) date('Y');
    if ($year < 1900 || $year > 3000) {
      $html = $context['html'] ?? '';
      return is_string($html) ? $html : '';
    }

    $tax = new Taxes('Alberta', $year);
    $rows = [];

    $previousGrossCents = 0;
    $previousFederalTax = 0;
    $previousProvincialTax = 0;
    $previousEmploymentInsurance = 0;
    $previousCanadaPensionPlan = 0;
    $previousOldAgeSecurity = 0;
    $previousRegularHours = 0.0;
    $previousOvertimeHours = 0.0;

    for ($month = 1; $month <= 12; ++$month) {
      $startDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
      $endDate = (clone $startDate)->modify('last day of this month');

      $totals = Earnings::getTotalsForRange(
        new \DateTimeImmutable("{$year}-01-01"),
        $endDate,
        User::currentUUID()
      );
      $grossCents = (int) $totals['totals']['grossCents'];

      $ytdRegularHours = (float) $totals['hours']['regular'];
      $ytdOvertimeHours = (float) $totals['hours']['overtime'];

      $taxes = $tax->calculateTaxesCents($grossCents);
      $federalTaxes = (int) $taxes['federal'];
      $provincialTaxes = (int) $taxes['provincial'];
      $employmentInsurance = (int) $taxes['employment_insurance'];
      $canadaPensionPlan = (int) $taxes['canada_pension_plan'];
      $oldAgeSecurity = (int) $taxes['old_age_security'];
      $taxTotal = (int) $taxes['totalDeductions'];
      $netCents = $grossCents - $taxTotal;

      $monthGrossCents = $grossCents - $previousGrossCents;
      $monthFederalCents = $federalTaxes - $previousFederalTax;
      $monthProvincialCents = $provincialTaxes - $previousProvincialTax;
      $monthEICents = $employmentInsurance - $previousEmploymentInsurance;
      $monthCPPCents = $canadaPensionPlan - $previousCanadaPensionPlan;
      $monthOASCents = $oldAgeSecurity - $previousOldAgeSecurity;
      $monthRegularHours = max(0.0, $ytdRegularHours - $previousRegularHours);
      $monthOvertimeHours = max(0.0, $ytdOvertimeHours - $previousOvertimeHours);

      $previousTaxTotalCents = $previousFederalTax
        + $previousProvincialTax
        + $previousEmploymentInsurance
        + $previousCanadaPensionPlan
        + $previousOldAgeSecurity;

      $monthTotalTaxCents = $taxTotal - $previousTaxTotalCents;
      $monthNetCents = $netCents - ($previousGrossCents - $previousTaxTotalCents);

      $rows[] = [
        'id' => sprintf('%04d-%02d', $year, $month),
        'month' => Strings::formatLocalizedShortMonth($year, $month),
        'regular_hours' => Strings::formatLocalizedNumber($monthRegularHours, 2, 2),
        'overtime_hours' => Strings::formatLocalizedNumber($monthOvertimeHours, 2, 2),
        'gross' => '$' . Strings::formatLocalizedNumber($monthGrossCents / 100, 2, 2),
        'federal_tax' => '$' . Strings::formatLocalizedNumber($monthFederalCents / 100, 2, 2),
        'provincial_tax' => '$' . Strings::formatLocalizedNumber($monthProvincialCents / 100, 2, 2),
        'total_tax' => '$' . Strings::formatLocalizedNumber(($monthFederalCents + $monthProvincialCents) / 100, 2, 2),
        'ei' => '$' . Strings::formatLocalizedNumber($monthEICents / 100, 2, 2),
        'cpp' => '$' . Strings::formatLocalizedNumber($monthCPPCents / 100, 2, 2),
        'oas' => '$' . Strings::formatLocalizedNumber($monthOASCents / 100, 2, 2),
        'total_deductions' => '$' . Strings::formatLocalizedNumber($monthTotalTaxCents / 100, 2, 2),
        'net' => '$' . Strings::formatLocalizedNumber($monthNetCents / 100, 2, 2),
      ];

      $previousRegularHours = $ytdRegularHours;
      $previousOvertimeHours = $ytdOvertimeHours;
      $previousGrossCents = $grossCents;
      $previousFederalTax = $federalTaxes;
      $previousProvincialTax = $provincialTaxes;
      $previousEmploymentInsurance = $employmentInsurance;
      $previousCanadaPensionPlan = $canadaPensionPlan;
      $previousOldAgeSecurity = $oldAgeSecurity;
    }

    return (new DataGrid([
      'id' => 'earnings-monthly-' . $year,
      'columns' => self::columns(),
      'rows' => $rows,
      'meta' => [
        'layout' => 'auto',
        'page' => 1,
        'totalPages' => 1,
        'title' => self::formatI18n('EARNINGS_MONTHLY_GRID_ARIA_FOR', ['year' => (string) $year]),
      ],
    ]))->table();
  }

  /**
   * @return list<array<string, scalar>>
   */
  private static function columns(): array
  {
    return [
      ['key' => 'month', 'label' => Strings::i18n('EARNINGS_MONTH')],
      ['key' => 'regular_hours', 'label' => Strings::i18n('REGULAR'), 'align' => 'right'],
      ['key' => 'overtime_hours', 'label' => Strings::i18n('OVERTIME'), 'align' => 'right'],
      ['key' => 'gross', 'label' => Strings::i18n('GROSS'), 'align' => 'right'],
      ['key' => 'federal_tax', 'label' => Strings::i18n('FEDERAL_TAX'), 'align' => 'right'],
      ['key' => 'provincial_tax', 'label' => Strings::i18n('PROVINCIAL_TAX'), 'align' => 'right'],
      ['key' => 'ei', 'label' => Strings::i18n('EARNINGS_EI'), 'align' => 'right'],
      ['key' => 'cpp', 'label' => Strings::i18n('EARNINGS_CPP'), 'align' => 'right'],
      ['key' => 'oas', 'label' => Strings::i18n('EARNINGS_OAS'), 'align' => 'right'],
      ['key' => 'total_deductions', 'label' => Strings::i18n('EARNINGS_TOTAL_DEDUCTIONS'), 'align' => 'right'],
      ['key' => 'net', 'label' => Strings::i18n('NET'), 'align' => 'right'],
    ];
  }
}
