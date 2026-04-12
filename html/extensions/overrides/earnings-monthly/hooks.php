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
   * @param float|int $value
   */
  private static function formatAmount(float|int $value): string
  {
    $locale = 'en_US';
    if (defined('USER_LOCALE') && is_string(USER_LOCALE) && USER_LOCALE !== '') {
      $locale = USER_LOCALE;
    }

    if (class_exists('\\NumberFormatter')) {
      $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
      $formatter->setAttribute(\NumberFormatter::GROUPING_USED, 1);
      $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
      $formatted = $formatter->format((float) $value);
      if (is_string($formatted) && $formatted !== '') {
        return $formatted;
      }
    }

    return number_format((float) $value, 2, '.', ',');
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
        '__MONTH_NAME__' => date('M', (int) mktime(0, 0, 0, $month, 1)),
        '__REGULAR_HOURS__' => number_format($monthRegularHours, 2, '.', ''),
        '__OVERTIME_HOURS__' => number_format($monthOvertimeHours, 2, '.', ''),
        '__GROSS__' => self::formatAmount($monthGrossCents / 100),
        '__FEDERAL_TAX__' => self::formatAmount($monthFederalCents / 100),
        '__PROVINCIAL_TAX__' => self::formatAmount($monthProvincialCents / 100),
        '__TOTAL_TAX__' => self::formatAmount(($monthFederalCents + $monthProvincialCents) / 100),
        '__EI__' => self::formatAmount($monthEICents / 100),
        '__CPP__' => self::formatAmount($monthCPPCents / 100),
        '__OAS__' => self::formatAmount($monthOASCents / 100),
        '__TOTAL_DEDUCTIONS__' => self::formatAmount($monthTotalTaxCents / 100),
        '__NET__' => self::formatAmount($monthNetCents / 100),
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
      '__EARNINGS_MONTHLY_ARIA_PREFIX__' => Strings::i18n('EARNINGS_MONTHLY_ARIA_PREFIX'),
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
