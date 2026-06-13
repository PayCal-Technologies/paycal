<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsMonthly;

use PayCal\Domain\Earnings;
use PayCal\Domain\Render;
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
    $monthsHTML = [];

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

      $renderMonth = [
        '__MONTH_ID__' => sprintf('%04d-%02d', $year, $month),
        '__MONTH_NAME__' => Strings::formatLocalizedShortMonth($year, $month),
        '__REGULAR_HOURS__' => Strings::formatLocalizedNumber($monthRegularHours, 2, 2),
        '__OVERTIME_HOURS__' => Strings::formatLocalizedNumber($monthOvertimeHours, 2, 2),
        '__GROSS__' => Strings::formatLocalizedNumber($monthGrossCents / 100, 2, 2),
        '__FEDERAL_TAX__' => Strings::formatLocalizedNumber($monthFederalCents / 100, 2, 2),
        '__PROVINCIAL_TAX__' => Strings::formatLocalizedNumber($monthProvincialCents / 100, 2, 2),
        '__TOTAL_TAX__' => Strings::formatLocalizedNumber(($monthFederalCents + $monthProvincialCents) / 100, 2, 2),
        '__EI__' => Strings::formatLocalizedNumber($monthEICents / 100, 2, 2),
        '__CPP__' => Strings::formatLocalizedNumber($monthCPPCents / 100, 2, 2),
        '__OAS__' => Strings::formatLocalizedNumber($monthOASCents / 100, 2, 2),
        '__TOTAL_DEDUCTIONS__' => Strings::formatLocalizedNumber($monthTotalTaxCents / 100, 2, 2),
        '__NET__' => Strings::formatLocalizedNumber($monthNetCents / 100, 2, 2),
      ];

      $monthsHTML[] = Render::template('earnings-month', $renderMonth);

      $previousRegularHours = $ytdRegularHours;
      $previousOvertimeHours = $ytdOvertimeHours;
      $previousGrossCents = $grossCents;
      $previousFederalTax = $federalTaxes;
      $previousProvincialTax = $provincialTaxes;
      $previousEmploymentInsurance = $employmentInsurance;
      $previousCanadaPensionPlan = $canadaPensionPlan;
      $previousOldAgeSecurity = $oldAgeSecurity;
    }

    return Render::template('earnings-monthly-viewstrip', [
      '__YEAR__' => (string) $year,
      '__DATA_GRID__' => 'earnings-monthly-' . $year,
      '__EARNINGS_MONTHLY_ARIA__' => htmlspecialchars(
        self::formatI18n('EARNINGS_MONTHLY_GRID_ARIA_FOR', ['year' => (string) $year]),
        ENT_QUOTES,
        'UTF-8',
      ),
      '__EARNINGS_MONTH__' => Strings::i18n('EARNINGS_MONTH'),
      '__REGULAR_LABEL__' => Strings::i18n('REGULAR'),
      '__OT_LABEL__' => Strings::i18n('OVERTIME'),
      '__GROSS_LABEL__' => Strings::i18n('GROSS'),
      '__FEDERAL_TAX_LABEL__' => Strings::i18n('FEDERAL_TAX'),
      '__PROVINCIAL_TAX_LABEL__' => Strings::i18n('PROVINCIAL_TAX'),
      '__EARNINGS_EI__' => Strings::i18n('EARNINGS_EI'),
      '__EARNINGS_CPP__' => Strings::i18n('EARNINGS_CPP'),
      '__EARNINGS_OAS__' => Strings::i18n('EARNINGS_OAS'),
      '__EARNINGS_TOTAL_DEDUCTIONS__' => Strings::i18n('EARNINGS_TOTAL_DEDUCTIONS'),
      '__NET_LABEL__' => Strings::i18n('NET'),
      '__MONTHS__' => implode('', $monthsHTML),
    ]);
  }
}
