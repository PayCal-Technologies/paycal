<?php declare(strict_types=1);

namespace PayCal\Domain;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * Xlsx.php
 *
 * Purpose: Generate XLSX spreadsheets from earnings report data for download.
 *
 * Developer notes:
 * - This class mirrors the column structure of the client-side CSV generators
 *   in earnings-export.js so both outputs stay in sync.
 * - PhpSpreadsheet is used for Excel-compatible binary output; keep sheet
 *   logic scope-specific and do not add general-purpose spreadsheet helpers.
 * - Input arrives as decoded JSON from the browser (same report objects that
 *   drive CSV/TXT export) — validate types carefully before use.
 *
 * Architectural role:
 * - Domain helper consumed by EarningsController::exportXlsx().
 * - Stateless: every public method returns a string of binary XLSX content.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */
final class Xlsx
{
  private const HEADER_BG    = 'FF2D6A4F';
  private const HEADER_FG    = 'FFFFFFFF';
  private const TOTAL_BG     = 'FFD8F3DC';
  private const MONEY_FORMAT = '#,##0.00';
  private const HOURS_FORMAT = '0.00';

  /**
   * Generate an XLSX file binary for the given scope and report data.
   *
   * Supported scopes: yearly, monthly, daily, payperiod.
   * Returns the raw XLSX byte string (write to response directly).
   *
   * @param string               $scope       Export scope (yearly|monthly|daily|payperiod)
   * @param array<int,mixed>     $detailedRows Day-level rows from buildDetailedRows() in JS
   * @param array<string,mixed>  $report       Aggregated report from buildXxxReportJson() in JS
   * @return string Binary XLSX content
   */
  public static function generate(string $scope, array $detailedRows, array $report): string
  {
    return match ($scope) {
      'yearly'    => self::buildYearly($detailedRows, $report),
      'monthly'   => self::buildMonthly($detailedRows, $report),
      'daily',
      'payperiod' => self::buildDaily($detailedRows, $report),
      default     => throw new \InvalidArgumentException("Unsupported XLSX scope: {$scope}"),
    };
  }

  // -------------------------------------------------------------------------
  // Scope-specific builders
  // -------------------------------------------------------------------------

  /**
   * Build yearly XLSX: one row per day with wage, hours, earnings, and tax breakdown.
   * Mirrors generateYearlyCsv() column layout.
   *
   * @param array<int,mixed>    $detailedRows
   * @param array<string,mixed> $report
   */
  private static function buildYearly(array $detailedRows, array $report): string
  {
    $meta      = is_array($report['meta'] ?? null) ? $report['meta'] : [];
    $firstRow  = isset($detailedRows[0]) && is_array($detailedRows[0]) ? $detailedRows[0] : [];
    $year      = self::s($meta['year'] ?? ($firstRow !== [] ? substr(self::s($firstRow['date'] ?? ''), 0, 4) : '') ?: (string) date('Y'));
    $title     = self::buildTitle($meta, "PayCal.app - {$year} Yearly Earnings Report");

    $headers = ['Date', 'Site', 'Wage', 'Hours', 'Regular Pay', 'OT Pay', 'Gross', 'Net', 'Fed Tax', 'Prov Tax', 'EI', 'CPP', 'OAS'];
    $colTypes = ['string', 'string', 'money', 'hours', 'money', 'money', 'money', 'money', 'money', 'money', 'money', 'money', 'money'];

    $dataRows = [];
    $totRegular = 0.0;
    $totOt      = 0.0;
    $totGross   = 0.0;

    foreach ($detailedRows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $reg = self::f($row['regular_pay'] ?? 0);
      $ot  = self::f($row['overtime_pay'] ?? 0);
      $gr  = self::f($row['gross'] ?? 0);
      $totRegular += $reg;
      $totOt      += $ot;
      $totGross   += $gr;

      $dataRows[] = [
        self::s($row['date'] ?? ''),
        self::s($row['site_name'] ?? ''),
        self::f($row['wage'] ?? 0),
        self::f($row['hours'] ?? 0),
        $reg,
        $ot,
        $gr,
        self::f($row['net'] ?? 0),
        self::f($row['federal_tax'] ?? 0),
        self::f($row['provincial_tax'] ?? 0),
        self::f($row['employment_insurance'] ?? 0),
        self::f($row['canada_pension_plan'] ?? 0),
        self::f($row['old_age_security'] ?? 0),
      ];
    }

    $totalRow = ['TOTALS', '', '', '', $totRegular, $totOt, $totGross, '', '', '', '', '', ''];

    $footerLines = self::buildFooterLines($meta);

    return self::renderSpreadsheet($title, $headers, $colTypes, $dataRows, $totalRow, $footerLines);
  }

  /**
   * Build monthly XLSX: one row per month with aggregated hours and earnings.
   * Mirrors generateMonthlyCsv() column layout.
   *
   * @param array<int,mixed>    $detailedRows
   * @param array<string,mixed> $report
   */
  private static function buildMonthly(array $detailedRows, array $report): string
  {
    $meta      = is_array($report['meta'] ?? null) ? $report['meta'] : [];
    $firstRow  = isset($detailedRows[0]) && is_array($detailedRows[0]) ? $detailedRows[0] : [];
    $year      = self::s($meta['year'] ?? ($firstRow !== [] ? substr(self::s($firstRow['date'] ?? ''), 0, 4) : '') ?: (string) date('Y'));
    $title     = self::buildTitle($meta, "PayCal.app - {$year} Monthly Earnings Report");

    $headers  = ['Month', 'Regular Hrs', 'OT Hrs', 'Gross', 'Tax', 'Net'];
    $colTypes = ['string', 'hours', 'hours', 'money', 'money', 'money'];

    $monthly = [];
    foreach ($detailedRows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $monthKey = substr(self::s($row['date'] ?? ''), 0, 7);
      if ($monthKey === '') {
        continue;
      }
      if (!isset($monthly[$monthKey])) {
        $monthly[$monthKey] = ['regular' => 0.0, 'ot' => 0.0, 'gross' => 0.0, 'tax' => 0.0, 'net' => 0.0];
      }
      $monthly[$monthKey]['regular'] += self::f($row['regular_hours'] ?? 0);
      $monthly[$monthKey]['ot']      += self::f($row['overtime_hours'] ?? 0);
      $monthly[$monthKey]['gross']   += self::f($row['gross'] ?? 0);
      $monthly[$monthKey]['tax']     += self::f($row['tax'] ?? 0);
      $monthly[$monthKey]['net']     += self::f($row['net'] ?? 0);
    }
    ksort($monthly);

    $dataRows = [];
    foreach ($monthly as $monthKey => $m) {
      $dataRows[] = [$monthKey, $m['regular'], $m['ot'], $m['gross'], $m['tax'], $m['net']];
    }

    $footerLines = self::buildFooterLines($meta);

    return self::renderSpreadsheet($title, $headers, $colTypes, $dataRows, null, $footerLines);
  }

  /**
   * Build daily XLSX: one row per day with travel, LOA, and earnings.
   * Mirrors generateDailyCsv() column layout. Used for both daily and payperiod scopes.
   *
   * @param array<int,mixed>    $detailedRows
   * @param array<string,mixed> $report
   */
  private static function buildDaily(array $detailedRows, array $report): string
  {
    $meta      = is_array($report['meta'] ?? null) ? $report['meta'] : [];
    $scope     = self::s($meta['scope'] ?? 'daily');
    $firstRow  = isset($detailedRows[0]) && is_array($detailedRows[0]) ? $detailedRows[0] : [];
    $year      = self::s($meta['year'] ?? ($firstRow !== [] ? substr(self::s($firstRow['date'] ?? ''), 0, 4) : '') ?: (string) date('Y'));
    $label     = $scope === 'payperiod' ? 'Pay Period' : 'Daily';
    $title  = self::buildTitle($meta, "PayCal.app - {$year} {$label} Earnings Report");

    $headers  = ['Date', 'Site', 'Regular Hrs', 'OT Hrs', 'Travel Hrs', 'LOA', 'Gross', 'Net'];
    $colTypes = ['string', 'string', 'hours', 'hours', 'hours', 'money', 'money', 'money'];

    // Aggregate report rows (daily totals per date) or fall back to detailedRows.
    $reportRows = [];
    if (isset($report['rows']) && is_array($report['rows']) && count($report['rows']) > 0) {
      foreach ($report['rows'] as $row) {
        if (!is_array($row)) {
          continue;
        }
        $reportRows[self::s($row['date'] ?? '')] = $row;
      }
      ksort($reportRows);
    }

    $dataRows = [];
    if (count($reportRows) > 0) {
      foreach ($reportRows as $row) {
        $dataRows[] = [
          self::s($row['date'] ?? ''),
          self::s($row['site_name'] ?? ''),
          self::f($row['regular'] ?? 0),
          self::f($row['overtime'] ?? 0),
          self::f($row['travel'] ?? 0),
          self::f($row['loa'] ?? 0),
          self::f($row['gross'] ?? 0),
          self::f($row['net'] ?? 0),
        ];
      }
    } else {
      // Fall back to day-level aggregation from detailedRows.
      $daily = [];
      foreach ($detailedRows as $row) {
        if (!is_array($row)) {
          continue;
        }
        $dayKey = self::s($row['date'] ?? '');
        if ($dayKey === '') {
          continue;
        }
        if (!isset($daily[$dayKey])) {
          $daily[$dayKey] = ['site_name' => '', 'regular' => 0.0, 'overtime' => 0.0, 'travel' => 0.0, 'loa' => 0.0, 'gross' => 0.0, 'net' => 0.0];
        }
        $site = trim(self::s($row['site_name'] ?? ''));
        if ($site !== '') {
          if ($daily[$dayKey]['site_name'] === '') {
            $daily[$dayKey]['site_name'] = $site;
          } elseif ($daily[$dayKey]['site_name'] !== $site) {
            $daily[$dayKey]['site_name'] = 'Multiple Sites';
          }
        }
        $daily[$dayKey]['regular']  += self::f($row['regular_hours'] ?? 0);
        $daily[$dayKey]['overtime'] += self::f($row['overtime_hours'] ?? 0);
        $daily[$dayKey]['travel']   += self::f($row['travel'] ?? 0);
        $daily[$dayKey]['loa']      += self::f($row['loa'] ?? 0);
        $daily[$dayKey]['gross']    += self::f($row['gross'] ?? 0);
        $daily[$dayKey]['net']      += self::f($row['net'] ?? 0);
      }
      ksort($daily);
      foreach ($daily as $dayKey => $d) {
        $dataRows[] = [$dayKey, $d['site_name'], $d['regular'], $d['overtime'], $d['travel'], $d['loa'], $d['gross'], $d['net']];
      }
    }

    $footerLines = self::buildFooterLines($meta);

    return self::renderSpreadsheet($title, $headers, $colTypes, $dataRows, null, $footerLines);
  }

  // -------------------------------------------------------------------------
  // Spreadsheet renderer
  // -------------------------------------------------------------------------

  /**
   * Render a spreadsheet with title, headers, data rows, optional totals row, and footer.
   *
   * @param string                     $title
   * @param array<int,string>          $headers
   * @param array<int,string>          $colTypes  'string' | 'money' | 'hours' per column
   * @param array<int,array<int,mixed>> $dataRows
   * @param array<int,mixed>|null      $totalRow  null = no totals row
   * @param array<int,string>          $footerLines
   */
  private static function renderSpreadsheet(
    string $title,
    array $headers,
    array $colTypes,
    array $dataRows,
    ?array $totalRow,
    array $footerLines
  ): string {
    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Report');

    $colCount = count($headers);
    $row      = 1;

    // Title row
    $sheet->setCellValue([1, $row], $title);
    $sheet->mergeCells([1, $row, $colCount, $row]);
    $sheet->getStyle([1, $row, $colCount, $row])->applyFromArray([
      'font' => ['bold' => true, 'size' => 13],
    ]);
    $row++;

    // Header row
    foreach ($headers as $ci => $header) {
      $sheet->setCellValue([$ci + 1, $row], $header);
    }
    $sheet->getStyle([1, $row, $colCount, $row])->applyFromArray([
      'font' => ['bold' => true, 'color' => ['argb' => self::HEADER_FG]],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $headerRow = $row;
    $row++;

    // Data rows
    $firstDataRow = $row;
    foreach ($dataRows as $dataRow) {
      foreach ($dataRow as $ci => $value) {
        $sheet->setCellValue([$ci + 1, $row], $value);
      }
      // Apply number formats to non-string columns
      foreach ($colTypes as $ci => $type) {
        if ($type === 'money') {
          $sheet->getStyle([$ci + 1, $row])->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        } elseif ($type === 'hours') {
          $sheet->getStyle([$ci + 1, $row])->getNumberFormat()->setFormatCode(self::HOURS_FORMAT);
        }
      }
      $row++;
    }
    $lastDataRow = $row - 1;

    // Totals row (optional)
    if ($totalRow !== null && count($dataRows) > 0) {
      $sheet->getStyle([1, $row, $colCount, $row])->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_BG]],
      ]);
      foreach ($totalRow as $ci => $value) {
        $sheet->setCellValue([$ci + 1, $row], $value);
      }
      // Apply money format to total rows that correspond to money columns
      foreach ($colTypes as $ci => $type) {
        if ($type === 'money' && $totalRow[$ci] !== '') {
          $sheet->getStyle([$ci + 1, $row])->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        }
      }
      $row++;
    }

    // Spacer
    $row++;

    // Footer lines
    foreach ($footerLines as $line) {
      $sheet->setCellValue([1, $row], $line);
      $sheet->mergeCells([1, $row, $colCount, $row]);
      $sheet->getStyle([1, $row])->applyFromArray([
        'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF666666']],
      ]);
      $row++;
    }

    // Auto-size all columns
    foreach (range(1, $colCount) as $ci) {
      $sheet->getColumnDimensionByColumn($ci)->setAutoSize(true);
    }

    // Thin border around header + data range
    if ($lastDataRow >= $firstDataRow) {
      $sheet->getStyle([1, $headerRow, $colCount, $lastDataRow])->applyFromArray([
        'borders' => [
          'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FFCCCCCC'],
          ],
        ],
      ]);
    }

    return self::writeToString($spreadsheet);
  }

  // -------------------------------------------------------------------------
  // Internal helpers
  // -------------------------------------------------------------------------

  /**
   * Cast a mixed value to float safely.
   */
  private static function f(mixed $value): float
  {
    return is_numeric($value) ? (float) $value : 0.0;
  }

  /**
   * Cast a mixed value to string safely.
   */
  private static function s(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Build the report title line, matching the JS buildTitleLine() logic.
   *
   * @param array<string,mixed> $meta
   */
  private static function buildTitle(array $meta, string $fallback): string
  {
    $title = trim(self::s($meta['title'] ?? '') ?: $fallback);
    $asAt  = trim(self::s($meta['as_at'] ?? $meta['subtitle'] ?? ''));

    if ($asAt === '' || stripos($title, ' as at ') !== false) {
      return $title;
    }

    return "{$title} {$asAt}";
  }

  /**
   * Build the footer lines (created timestamp + reference code).
   *
   * @param array<string,mixed> $meta
   * @return array<int,string>
   */
  private static function buildFooterLines(array $meta): array
  {
    $createdAt = self::s($meta['created_at'] ?? '');
    $ipAddress = self::s($meta['ip_address'] ?? 'unknown');
    $refCode   = self::s($meta['reference_code'] ?? '');

    $lines = [];

    if ($createdAt !== '') {
      $lines[] = "Created: {$createdAt} from IP Address {$ipAddress}";
    }

    if ($refCode !== '') {
      $lines[] = "REF: {$refCode}";
    }

    return $lines;
  }

  /**
   * Write the Spreadsheet object to an in-memory string and return it.
   */
  private static function writeToString(Spreadsheet $spreadsheet): string
  {
    $writer = new XlsxWriter($spreadsheet);

    ob_start();
    $writer->save('php://output');
    $content = ob_get_clean();

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    return (string) $content;
  }
}
