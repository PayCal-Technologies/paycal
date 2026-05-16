<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * EarningsPdf.php
 *
 * Purpose: Generate PDF earnings reports from aggregated report data for download.
 *
 * Developer notes:
 * - Mirrors the column structure of the client-side CSV/XLSX generators in
 *   earnings-export.js so all export outputs stay in sync.
 * - Uses Tabularium (pure-PHP PDF 1.4) — zero external dependencies.
 * - Input arrives as decoded JSON from the browser (same report objects that
 *   drive CSV/XLSX/TXT export); validate types carefully before use.
 * - Yearly and monthly scopes use A4 portrait. Daily and payperiod use A4
 *   landscape (more columns require the extra width).
 *
 * Architectural role:
 * - Domain helper consumed by EarningsController::exportPdf().
 * - Stateless: every public method returns a raw PDF byte string.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
final class EarningsPdf
{
  // ---------------------------------------------------------------------------
  // Design constants
  // ---------------------------------------------------------------------------

  /** Table header background: PayCal green (#2D6A4F) */
  private const HDR_R = 45;
  private const HDR_G = 106;
  private const HDR_B = 79;

  /** Totals row background: light green (#D8F3DC) */
  private const TOT_R = 216;
  private const TOT_G = 243;
  private const TOT_B = 220;

  /** Body font size (pt) */
  private const FONT_BODY = 9.0;

  /** Table row height (pt) */
  private const ROW_H = 13.0;

  /** Header row height (pt) */
  private const HDR_H = 14.0;

  // ---------------------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------------------

  /**
   * Generate a PDF report byte string for the given scope and report data.
   *
   * Supported scopes: yearly, monthly, daily, payperiod.
   * Returns the raw PDF bytes (write to response directly).
   *
   * @param string              $scope  Export scope (yearly|monthly|daily|payperiod)
   * @param array<string,mixed> $report Aggregated report from buildXxxReportJson() in JS
   * @return string Raw PDF content
   * @throws \InvalidArgumentException on unsupported scope
   */
  public static function generate(string $scope, array $report): string
  {
    return match ($scope) {
      'yearly'    => self::buildYearly($report),
      'monthly'   => self::buildMonthly($report),
      'daily',
      'payperiod' => self::buildDaily($report, $scope),
      default     => throw new \InvalidArgumentException("Unsupported PDF scope: {$scope}"),
    };
  }

  // ---------------------------------------------------------------------------
  // Scope builders
  // ---------------------------------------------------------------------------

  /**
   * Yearly PDF: one row per site — regular, OT, gross, EI, CPP, OAS, tax, net.
   *
   * @param array<string,mixed> $report
   */
  private static function buildYearly(array $report): string
  {
    $meta    = is_array($report['meta']    ?? null) ? $report['meta']    : [];
    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
    $rows    = is_array($report['rows']    ?? null) ? $report['rows']    : [];

    $pdf = self::makePdf(Tabularium::PAGE_A4);
    $pdf->addPage();

    // Portrait A4 — printable width = 595.28 - 72 = 523.28 pt
    $pw = $pdf->getPrintWidth();

    self::renderHeader($pdf, $meta, $summary, 'yearly');

    // Table columns: Site | Regular | OT | Gross | EI | CPP | OAS | Tax | Net
    $cols = [
      ['Site',     $pw * 0.248, 'L'],
      ['Regular',  $pw * 0.096, 'R'],
      ['OT',       $pw * 0.086, 'R'],
      ['Gross',    $pw * 0.115, 'R'],
      ['EI',       $pw * 0.086, 'R'],
      ['CPP',      $pw * 0.086, 'R'],
      ['OAS',      $pw * 0.086, 'R'],
      ['Tax',      $pw * 0.096, 'R'],
      ['Net',      $pw * 0.101, 'R'],
    ];

    $onNewPage = function (Tabularium $t) use ($cols): void {
      self::renderTableHeader($t, $cols);
    };

    self::renderTableHeader($pdf, $cols);

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $cells = [
        [self::s($row['site_name'] ?? ''),                 $cols[0][1], 'L'],
        [self::formatNum($row['regular'] ?? ''),  $cols[1][1], 'R'],
        [self::formatNum($row['overtime'] ?? ''), $cols[2][1], 'R'],
        [self::formatNum($row['gross'] ?? ''),    $cols[3][1], 'R'],
        [self::formatNum($row['employment_insurance'] ?? ''), $cols[4][1], 'R'],
        [self::formatNum($row['canada_pension_plan'] ?? ''),  $cols[5][1], 'R'],
        [self::formatNum($row['old_age_security'] ?? ''),     $cols[6][1], 'R'],
        [self::formatNum($row['tax'] ?? ''),      $cols[7][1], 'R'],
        [self::formatNum($row['net'] ?? ''),      $cols[8][1], 'R'],
      ];
      $pdf->safeRow($cells, self::ROW_H, '1', false, $onNewPage);
    }

    $totals = [
      ['Totals',                                                                            $cols[0][1], 'L'],
      [self::formatNum($summary['regular_hours']        ?? ''),                 $cols[1][1], 'R'],
      [self::formatNum($summary['overtime_hours']       ?? ''),                 $cols[2][1], 'R'],
      [self::prefixDollar($summary['gross']             ?? ''),                 $cols[3][1], 'R'],
      [self::prefixDollar($summary['employment_insurance'] ?? ''),              $cols[4][1], 'R'],
      [self::prefixDollar($summary['canada_pension_plan'] ?? ''),               $cols[5][1], 'R'],
      [self::prefixDollar($summary['old_age_security']  ?? ''),                 $cols[6][1], 'R'],
      [self::prefixDollar($summary['taxes']             ?? ''),                 $cols[7][1], 'R'],
      [self::prefixDollar($summary['net']               ?? ''),                 $cols[8][1], 'R'],
    ];
    self::renderTotalsRow($pdf, $totals);

    return $pdf->output('S');
  }

  /**
   * Monthly PDF: one row per month — regular, OT, gross, EI, CPP, OAS, tax.
   *
   * @param array<string,mixed> $report
   */
  private static function buildMonthly(array $report): string
  {
    $meta    = is_array($report['meta']    ?? null) ? $report['meta']    : [];
    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
    $rows    = is_array($report['rows']    ?? null) ? $report['rows']    : [];

    $pdf = self::makePdf(Tabularium::PAGE_A4);
    $pdf->addPage();

    $pw = $pdf->getPrintWidth();

    self::renderHeader($pdf, $meta, $summary, 'monthly');

    // Columns: Month | Site | Regular | OT | Gross | EI | CPP | OAS | Tax
    $cols = [
      ['Month',    $pw * 0.134, 'L'],
      ['Site',     $pw * 0.229, 'L'],
      ['Regular',  $pw * 0.096, 'R'],
      ['OT',       $pw * 0.086, 'R'],
      ['Gross',    $pw * 0.115, 'R'],
      ['EI',       $pw * 0.086, 'R'],
      ['CPP',      $pw * 0.086, 'R'],
      ['OAS',      $pw * 0.086, 'R'],
      ['Tax',      $pw * 0.082, 'R'],
    ];

    $onNewPage = function (Tabularium $t) use ($cols): void {
      self::renderTableHeader($t, $cols);
    };

    self::renderTableHeader($pdf, $cols);

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $cells = [
        [self::formatMonth($row['month'] ?? ''), $cols[0][1], 'L'],
        [self::s($row['site_name'] ?? ''),                $cols[1][1], 'L'],
        [self::formatNum($row['regular'] ?? ''),  $cols[2][1], 'R'],
        [self::formatNum($row['overtime'] ?? ''), $cols[3][1], 'R'],
        [self::formatNum($row['gross'] ?? ''),    $cols[4][1], 'R'],
        [self::formatNum($row['employment_insurance'] ?? ''), $cols[5][1], 'R'],
        [self::formatNum($row['canada_pension_plan'] ?? ''),  $cols[6][1], 'R'],
        [self::formatNum($row['old_age_security'] ?? ''),     $cols[7][1], 'R'],
        [self::formatNum($row['tax'] ?? ''),      $cols[8][1], 'R'],
      ];
      $pdf->safeRow($cells, self::ROW_H, '1', false, $onNewPage);
    }

    $totals = [
      ['Totals',                                                                        $cols[0][1], 'L'],
      ['',                                                                              $cols[1][1], 'L'],
      [self::formatNum($summary['regular_hours']     ?? ''),                $cols[2][1], 'R'],
      [self::formatNum($summary['overtime_hours']    ?? ''),                $cols[3][1], 'R'],
      [self::prefixDollar($summary['gross']          ?? ''),                $cols[4][1], 'R'],
      [self::prefixDollar($summary['employment_insurance'] ?? ''),          $cols[5][1], 'R'],
      [self::prefixDollar($summary['canada_pension_plan'] ?? ''),           $cols[6][1], 'R'],
      [self::prefixDollar($summary['old_age_security'] ?? ''),              $cols[7][1], 'R'],
      [self::prefixDollar($summary['taxes']          ?? ''),                $cols[8][1], 'R'],
    ];
    self::renderTotalsRow($pdf, $totals);

    return $pdf->output('S');
  }

  /**
   * Daily / pay-period PDF (A4 landscape): one row per day — date, site, hours, deductions, net.
   *
   * @param array<string,mixed> $report
   */
  private static function buildDaily(array $report, string $scope): string
  {
    $meta    = is_array($report['meta']    ?? null) ? $report['meta']    : [];
    $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
    $rows    = is_array($report['rows']    ?? null) ? $report['rows']    : [];

    // Landscape: swap A4 dimensions
    $pdf = self::makePdf([Tabularium::PAGE_A4[1], Tabularium::PAGE_A4[0]]);
    $pdf->addPage();

    // Landscape A4 — printable width = 841.89 - 72 = 769.89 pt
    $pw = $pdf->getPrintWidth();

    self::renderHeader($pdf, $meta, $summary, $scope);

    // Columns: Date | Site | Regular | OT | Travel | LOA | Gross | EI | CPP | OAS | Tax | Net
    $cols = [
      ['Date',     $pw * 0.091, 'L'],
      ['Site',     $pw * 0.117, 'L'],
      ['Regular',  $pw * 0.071, 'R'],
      ['OT',       $pw * 0.059, 'R'],
      ['Travel',   $pw * 0.059, 'R'],
      ['LOA',      $pw * 0.059, 'R'],
      ['Gross',    $pw * 0.085, 'R'],
      ['EI',       $pw * 0.062, 'R'],
      ['CPP',      $pw * 0.062, 'R'],
      ['OAS',      $pw * 0.062, 'R'],
      ['Tax',      $pw * 0.075, 'R'],
      ['Net',      $pw * 0.098, 'R'],
    ];

    $onNewPage = function (Tabularium $t) use ($cols): void {
      self::renderTableHeader($t, $cols);
    };

    self::renderTableHeader($pdf, $cols);

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $cells = [
        [self::s($row['date'] ?? ''),                      $cols[0][1], 'L'],
        [self::s($row['site_name'] ?? ''),                 $cols[1][1], 'L'],
        [self::formatNum($row['regular'] ?? ''),  $cols[2][1], 'R'],
        [self::formatNum($row['overtime'] ?? ''), $cols[3][1], 'R'],
        [self::formatNum($row['travel'] ?? ''),   $cols[4][1], 'R'],
        [self::formatNum($row['loa'] ?? ''),      $cols[5][1], 'R'],
        [self::formatNum($row['gross'] ?? ''),    $cols[6][1], 'R'],
        [self::formatNum($row['employment_insurance'] ?? ''), $cols[7][1], 'R'],
        [self::formatNum($row['canada_pension_plan'] ?? ''),  $cols[8][1], 'R'],
        [self::formatNum($row['old_age_security'] ?? ''),     $cols[9][1], 'R'],
        [self::formatNum($row['tax'] ?? ''),      $cols[10][1], 'R'],
        [self::formatNum($row['net'] ?? ''),      $cols[11][1], 'R'],
      ];
      $pdf->safeRow($cells, self::ROW_H, '1', false, $onNewPage);
    }

    $totals = [
      ['Totals',                                                                        $cols[0][1], 'L'],
      ['',                                                                              $cols[1][1], 'L'],
      [self::formatNum($summary['regular_hours']     ?? ''),                $cols[2][1], 'R'],
      [self::formatNum($summary['overtime_hours']    ?? ''),                $cols[3][1], 'R'],
      ['',                                                                              $cols[4][1], 'R'],
      ['',                                                                              $cols[5][1], 'R'],
      [self::prefixDollar($summary['gross']          ?? ''),                $cols[6][1], 'R'],
      [self::prefixDollar($summary['employment_insurance'] ?? ''),          $cols[7][1], 'R'],
      [self::prefixDollar($summary['canada_pension_plan'] ?? ''),           $cols[8][1], 'R'],
      [self::prefixDollar($summary['old_age_security'] ?? ''),              $cols[9][1], 'R'],
      [self::prefixDollar($summary['taxes']          ?? ''),                $cols[10][1], 'R'],
      [self::prefixDollar($summary['net']            ?? ''),                $cols[11][1], 'R'],
    ];
    self::renderTotalsRow($pdf, $totals);

    return $pdf->output('S');
  }

  // ---------------------------------------------------------------------------
  // Layout helpers
  // ---------------------------------------------------------------------------

  /**
   * Construct and configure a Tabularium instance for an earnings report.
   *
   * @param array{0: float, 1: float} $pageSize
   */
  private static function makePdf(array $pageSize): Tabularium
  {
    $pdf = new Tabularium($pageSize, 36.0, 36.0, 36.0, 28.0);
    $pdf->setFont('', self::FONT_BODY);
    $pdf->setTextColor(30, 30, 30);
    $pdf->setDrawColor(180, 180, 180);
    return $pdf;
  }

  /**
   * Render the report title block and summary key-value pairs.
   * Advances cursor to just above the table.
   *
   * @param array<string,mixed> $meta
   * @param array<string,mixed> $summary
   */
  private static function renderHeader(Tabularium $pdf, array $meta, array $summary, string $scope): void
  {
    $title     = self::s($meta['title']    ?? '', 'PayCal Earnings Report');
    $employee  = self::s($meta['full_name'] ?? null) ?: self::s($meta['employee'] ?? '');
    $asAt      = self::s($meta['as_at']    ?? '');
    $refCode   = self::s($meta['reference_code'] ?? '');
    $email     = self::s($meta['email']    ?? '');
    $year      = self::s($meta['year']     ?? '');

    $subtitle = match ($scope) {
      'yearly'    => "Yearly Earnings Summary — {$year}",
      'monthly'   => "Monthly Earnings Summary — {$year}",
      'daily'     => "Daily Earnings — {$year}",
      'payperiod' => 'Pay Period Earnings',
      default     => 'Earnings Report',
    };

    $pw = $pdf->getPrintWidth();
    $marginLeft = 36.0;

    // Title (large, bold)
    $pdf->setFont('B', 16.0);
    $pdf->setTextColor(30, 30, 30);
    $pdf->text($marginLeft, $pdf->getY(), $title);
    $pdf->ln(20.0);

    // Subtitle
    $pdf->setFont('', 11.0);
    $pdf->text($marginLeft, $pdf->getY(), $subtitle);
    $pdf->ln(16.0);

    // Rule beneath subtitle
    $pdf->setDrawColor(45, 106, 79);
    $pdf->setLineWidth(1.0);
    $pdf->rule(1.0);
    $pdf->setDrawColor(180, 180, 180);
    $pdf->setLineWidth(0.5);
    $pdf->ln(10.0);

    // Employee info row (left) + ref code (right)
    $pdf->setFont('B', self::FONT_BODY);
    $pdf->text($marginLeft, $pdf->getY(), 'Employee:');
    $pdf->setFont('', self::FONT_BODY);
    $pdf->text($marginLeft + 55.0, $pdf->getY(), $employee ?: '—');

    if ($refCode !== '') {
      $refLabel = 'Ref: ' . $refCode;
      $refW = $pdf->textWidth($refLabel);
      $pdf->text($marginLeft + $pw - $refW, $pdf->getY(), $refLabel);
    }
    $pdf->ln(13.0);

    // Email row + as-at
    if ($email !== '') {
      $pdf->setFont('B', self::FONT_BODY);
      $pdf->text($marginLeft, $pdf->getY(), 'Email:');
      $pdf->setFont('', self::FONT_BODY);
      $pdf->text($marginLeft + 55.0, $pdf->getY(), $email);
    }
    if ($asAt !== '') {
      $asAtW = $pdf->textWidth($asAt);
      $pdf->setFont('I', self::FONT_BODY);
      $pdf->text($marginLeft + $pw - $asAtW, $pdf->getY(), $asAt);
      $pdf->setFont('', self::FONT_BODY);
    }
    $pdf->ln(16.0);

    // Summary block — two-column key-value layout
    $pdf->setFont('B', self::FONT_BODY);
    $pdf->text($marginLeft, $pdf->getY(), 'Summary');
    $pdf->ln(13.0);

    $col1 = $pw * 0.50;
    $summaryItems = [
      ['Regular Hours',       self::formatNum($summary['regular_hours'] ?? '')],
      ['Overtime Hours',      self::formatNum($summary['overtime_hours'] ?? '')],

      ['Gross Pay',           self::prefixDollar($summary['gross'] ?? '')],
      ['Net Pay',             self::prefixDollar($summary['net'] ?? '')],
      ['Federal Tax',         self::prefixDollar($summary['federal_tax'] ?? '')],
      ['Provincial Tax',      self::prefixDollar($summary['provincial_tax'] ?? '')],
      ['EI',                  self::prefixDollar($summary['employment_insurance'] ?? '')],
      ['CPP',                 self::prefixDollar($summary['canada_pension_plan'] ?? '')],
      ['OAS',                 self::prefixDollar($summary['old_age_security'] ?? '')],
      ['Total Deductions',    self::prefixDollar($summary['taxes'] ?? '')],
    ];

    $count = count($summaryItems);
    $halfCount = (int) ceil($count / 2);

    for ($i = 0; $i < $halfCount; $i++) {
      $left  = $summaryItems[$i];
      $right = $summaryItems[$i + $halfCount] ?? null;
      $y = $pdf->getY();

      $pdf->setFont('B', self::FONT_BODY);
      $pdf->text($marginLeft, $y, $left[0] . ':');
      $pdf->setFont('', self::FONT_BODY);
      $pdf->text($marginLeft + 90.0, $y, $left[1]);

      if ($right !== null) {
        $pdf->setFont('B', self::FONT_BODY);
        $pdf->text($marginLeft + $col1, $y, $right[0] . ':');
        $pdf->setFont('', self::FONT_BODY);
        $pdf->text($marginLeft + $col1 + 90.0, $y, $right[1]);
      }

      $pdf->ln(12.0);
    }

    $pdf->ln(4.0);
    $pdf->setDrawColor(45, 106, 79);
    $pdf->setLineWidth(0.5);
    $pdf->rule(0.5);
    $pdf->setDrawColor(180, 180, 180);
    $pdf->ln(2.0);
  }

  /**
   * Render a styled table header row.
   *
   * @param array<int, array{0: string, 1: float, 2: string}> $cols
   */
  private static function renderTableHeader(Tabularium $pdf, array $cols): void
  {
    $pdf->setFont('B', self::FONT_BODY);
    $pdf->setFillColor(self::HDR_R, self::HDR_G, self::HDR_B);
    $pdf->setTextColor(255, 255, 255);
    $pdf->row($cols, self::HDR_H, '1', true);
    $pdf->setFont('', self::FONT_BODY);
    $pdf->setTextColor(30, 30, 30);
    $pdf->setFillColor(255, 255, 255);
  }

  /**
   * Render a styled totals row using pre-built cell data.
   *
   * @param array<int, array{0: string, 1: float, 2: string}> $cells  Pre-built cells (same format as row())
   */
  private static function renderTotalsRow(
    Tabularium $pdf,
    array $cells,
  ): void {
    $pdf->setFont('B', self::FONT_BODY);
    $pdf->setFillColor(self::TOT_R, self::TOT_G, self::TOT_B);
    $pdf->setTextColor(30, 30, 30);
    $pdf->row($cells, self::HDR_H, '1', true);
    $pdf->setFont('', self::FONT_BODY);
    $pdf->setFillColor(255, 255, 255);
  }

  // ---------------------------------------------------------------------------
  // Format helpers
  // ---------------------------------------------------------------------------

  /**
   * Safely convert a mixed value from the report JSON to string.
   * Returns $default if the value is null, an array, or an object.
   */
  private static function s(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Normalize a mixed numeric value from the report JSON to a display string.
   * Returns '0.00' for empty/zero values.
   */
  private static function formatNum(mixed $value): string
  {
    $str = self::s($value);
    if ($str === '' || $str === '—') {
      return '—';
    }
    return number_format((float) $str, 2, '.', ',');
  }

  /**
   * Prepend '$' to a mixed numeric value, or return '—' for blank/null.
   */
  private static function prefixDollar(mixed $value): string
  {
    $str = self::s($value);
    if ($str === '' || $str === '—') {
      return '—';
    }
    return '$' . number_format((float) $str, 2, '.', ',');
  }

  /**
   * Format a mixed "YYYY-MM" month value to "MMM YYYY" (e.g. "2026-01" → "Jan 2026").
   */
  private static function formatMonth(mixed $value): string
  {
    $ym = self::s($value);
    if (preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
      $dt = \DateTimeImmutable::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-01");
      if ($dt !== false) {
        return $dt->format('M Y');
      }
    }
    return $ym;
  }
}
