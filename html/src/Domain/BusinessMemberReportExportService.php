<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Server-side export builder for protected business member reports.
 *
 * The browser supplies only export intent (business, member, scope, format,
 * year). Protected work rows are read through BusinessProtectedDataAccess and
 * report bytes are generated from the server-side authorized snapshot.
 */
final class BusinessMemberReportExportService
{
  /** @return array{success: bool, message: string, reason: string, data: array<string, mixed>} */
  public function exportMemberReport(
    string $actorUUID,
    string $businessId,
    string $memberUUID,
    string $scope,
    string $format,
    int $year,
  ): array {
    $actorUUID = trim($actorUUID);
    $businessId = trim($businessId);
    $memberUUID = trim($memberUUID);
    $scope = strtolower(trim($scope));
    $format = strtolower(trim($format));
    $year = $this->normalizeYear($year);

    if ($actorUUID === '' || $businessId === '' || $memberUUID === '') {
      return $this->fail('missing_context', 'Actor, business, and member are required.');
    }

    $this->audit($businessId, 'business.member.report.export.requested', $actorUUID, [
      'target_member_uuid' => $memberUUID,
      'report_scope' => $scope,
      'format' => $format,
      'year' => (string) $year,
      'result' => 'requested',
    ]);

    if (!in_array($scope, ['yearly', 'monthly', 'daily'], true)) {
      $this->audit($businessId, 'business.member.report.export.denied', $actorUUID, [
        'target_member_uuid' => $memberUUID,
        'report_scope' => $scope,
        'format' => $format,
        'year' => (string) $year,
        'result' => 'denied',
        'reason' => 'invalid_scope',
      ]);
      return $this->fail('invalid_scope', 'Unsupported member report export scope.');
    }

    if (!in_array($format, ['xlsx', 'pdf'], true)) {
      $this->audit($businessId, 'business.member.report.export.denied', $actorUUID, [
        'target_member_uuid' => $memberUUID,
        'report_scope' => $scope,
        'format' => $format,
        'year' => (string) $year,
        'result' => 'denied',
        'reason' => 'invalid_format',
      ]);
      return $this->fail('invalid_format', 'Unsupported member report export format.');
    }

    if ($format !== 'pdf' && !SubscriptionRepository::isPremiumActive($actorUUID)) {
      $this->audit($businessId, 'business.member.report.export.denied', $actorUUID, [
        'target_member_uuid' => $memberUUID,
        'report_scope' => $scope,
        'format' => $format,
        'year' => (string) $year,
        'result' => 'denied',
        'reason' => 'premium_required',
      ]);
      return $this->fail('premium_required', 'Premium subscription required for this export format.');
    }

    $protectedRead = (new BusinessProtectedDataAccess())->readMemberWork(
      $actorUUID,
      $businessId,
      $memberUUID,
      $year,
      null,
      true,
      'business.member.report.export',
    );

    if (!$protectedRead['success']) {
      $reason = $protectedRead['reason'] !== '' ? $protectedRead['reason'] : 'protected_read_denied';
      $this->audit($businessId, 'business.member.report.export.denied', $actorUUID, [
        'target_member_uuid' => $memberUUID,
        'report_scope' => $scope,
        'format' => $format,
        'year' => (string) $year,
        'result' => 'denied',
        'reason' => $reason,
      ]);

      return $this->fail($reason, (string) $protectedRead['message']);
    }

    $entries = $this->normalizeEntries($protectedRead['data']['entries'] ?? []);

    $memberUser = User::getByUUID($memberUUID);
    $rows = $this->buildDetailedRows($entries, $year, $memberUser);
    if ($rows === []) {
      $this->audit($businessId, 'business.member.report.export.denied', $actorUUID, [
        'target_member_uuid' => $memberUUID,
        'report_scope' => $scope,
        'format' => $format,
        'year' => (string) $year,
        'result' => 'denied',
        'reason' => 'no_export_rows',
      ]);

      return $this->fail('no_export_rows', 'No protected business work rows are available for export.');
    }

    $report = $this->buildReport($scope, $year, $memberUUID, $memberUser, $rows);

    $this->audit($businessId, 'business.member.report.export.started', $actorUUID, [
      'target_member_uuid' => $memberUUID,
      'report_scope' => $scope,
      'format' => $format,
      'year' => (string) $year,
      'entry_count' => (string) count($rows),
      'reference_code' => $this->stringValue($report['meta']['reference_code'] ?? ''),
      'result' => 'started',
    ]);

    try {
      $bytes = $format === 'xlsx'
        ? Xlsx::generate($scope, $rows, $report)
        : EarningsPdf::generate($scope, $report);
    } catch (\InvalidArgumentException $e) {
      $this->audit($businessId, 'business.member.report.export.failed', $actorUUID, [
        'target_member_uuid' => $memberUUID,
        'report_scope' => $scope,
        'format' => $format,
        'year' => (string) $year,
        'result' => 'failed',
        'reason' => 'render_failed',
      ]);

      return $this->fail('render_failed', 'Member report export could not be rendered.');
    }

    $mime = $format === 'xlsx'
      ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      : 'application/pdf';
    $filename = $this->buildFilename($scope, $format, $year, $memberUser, $memberUUID);

    $this->audit($businessId, 'business.member.report.export.completed', $actorUUID, [
      'target_member_uuid' => $memberUUID,
      'report_scope' => $scope,
      'format' => $format,
      'year' => (string) $year,
      'entry_count' => (string) count($rows),
      'byte_count' => (string) strlen($bytes),
      'filename' => $filename,
      'reference_code' => $this->stringValue($report['meta']['reference_code'] ?? ''),
      'result' => 'allowed',
    ]);

    return [
      'success' => true,
      'message' => 'Protected member report export generated.',
      'reason' => '',
      'data' => [
        'bytes' => $bytes,
        'mime' => $mime,
        'filename' => $filename,
        'scope' => $scope,
        'format' => $format,
        'year' => $year,
        'entry_count' => count($rows),
      ],
    ];
  }

  /**
   * @param array<string, array<string, string>> $entries
   * @return list<array<string, mixed>>
   */
  private function buildDetailedRows(array $entries, int $year, ?User $memberUser): array
  {
    $province = $this->resolveProvinceName($memberUser);
    $taxByYear = [];
    $rows = [];

    foreach ($entries as $workKey => $entry) {
      $date = $this->resolveDate((string) $workKey, $entry);
      if ($date === '' || (int) substr($date, 0, 4) !== $year) {
        continue;
      }

      $rowYear = (int) substr($date, 0, 4);
      if (!isset($taxByYear[$rowYear])) {
        $taxByYear[$rowYear] = new Taxes($province, $rowYear);
      }
      /** @var Taxes $tax */
      $tax = $taxByYear[$rowYear];

      $siteId = $this->resolveSiteId((string) $workKey, $entry);
      $siteName = trim($this->stringValue($entry['site_name'] ?? ''));
      if ($siteName === '') {
        $siteName = $siteId;
      }

      $wage = $this->round2($this->floatValue($entry['wage'] ?? $entry['w'] ?? 0));
      $regularHours = $this->round2($this->floatValue($entry['regular_hours'] ?? $entry['r'] ?? 0));
      $overtimeHours = $this->round2($this->floatValue($entry['overtime_hours'] ?? $entry['o'] ?? 0));
      $travelHours = $this->round2($this->floatValue($entry['travel_hours'] ?? $entry['t'] ?? $entry['travel'] ?? 0));
      $hours = $this->round2($this->floatValue($entry['hours'] ?? $entry['h'] ?? ($regularHours + $overtimeHours + $travelHours)));
      $loa = $this->round2($this->floatValue($entry['living_out_allowance'] ?? $entry['loa'] ?? $entry['l'] ?? 0));

      $calculatedGross = $this->round2(($wage * $regularHours) + ($wage * $overtimeHours * 1.5) + ($wage * $travelHours) + $loa);
      $sourceGross = $this->round2($this->floatValue($entry['gross'] ?? $entry['g'] ?? 0));
      $gross = $sourceGross > 0.0 ? $sourceGross : $calculatedGross;

      $taxBreakdown = $tax->calculateTaxes($gross);
      $annualTaxBreakdown = $tax->calculateTaxes($gross * 260.0);
      $federalTax = $this->storedOrCalculated($entry, 'federal_tax', $taxBreakdown['federal']);
      $provincialTax = $this->storedOrCalculated($entry, 'provincial_tax', $taxBreakdown['provincial']);
      $ei = $this->storedOrCalculated($entry, 'employment_insurance', $taxBreakdown['employment_insurance']);
      $cpp = $this->storedOrCalculated($entry, 'canada_pension_plan', $annualTaxBreakdown['canada_pension_plan'] / 260.0);
      $oas = $this->storedOrCalculated($entry, 'old_age_security', $annualTaxBreakdown['old_age_security'] / 260.0);
      $sourceTax = $this->round2($this->floatValue($entry['tax'] ?? $entry['tx'] ?? $entry['deductions'] ?? 0));
      $totalTax = $sourceTax > 0.0 ? $sourceTax : $this->round2($federalTax + $provincialTax + $ei + $cpp + $oas);
      $sourceNet = $this->round2($this->floatValue($entry['net'] ?? 0));
      $net = $sourceNet > 0.0 ? $sourceNet : $this->round2($gross - $totalTax);

      $effectiveWage = $wage > 0.0 ? $wage : ($hours > 0.0 ? $this->round2($gross / $hours) : 0.0);
      $regularPay = $this->round2($effectiveWage * $regularHours);
      $overtimePay = $this->round2($effectiveWage * $overtimeHours * 1.5);

      $rows[] = [
        'date' => $date,
        'site_id' => $siteId,
        'site_name' => $siteName,
        'wage' => $wage,
        'hours' => $hours,
        'regular_hours' => $regularHours,
        'overtime_hours' => $overtimeHours,
        'travel' => $travelHours,
        'loa' => $loa,
        'regular_pay' => $regularPay,
        'overtime_pay' => $overtimePay,
        'gross' => $gross,
        'net' => $net,
        'federal_tax' => $federalTax,
        'provincial_tax' => $provincialTax,
        'employment_insurance' => $ei,
        'canada_pension_plan' => $cpp,
        'old_age_security' => $oas,
        'tax' => $totalTax,
      ];
    }

    usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));

    return $rows;
  }

  /**
   * @param list<array<string, mixed>> $rows
   * @return array{meta: array<string, mixed>, summary: array<string, mixed>, rows: list<array<string, mixed>>}
   */
  private function buildReport(string $scope, int $year, string $memberUUID, ?User $memberUser, array $rows): array
  {
    return match ($scope) {
      'yearly' => $this->buildYearlyReport($year, $memberUUID, $memberUser, $rows),
      'monthly' => $this->buildMonthlyReport($year, $memberUUID, $memberUser, $rows),
      'daily' => $this->buildDailyReport($year, $memberUUID, $memberUser, $rows),
      default => throw new \InvalidArgumentException("Unsupported member report scope: {$scope}"),
    };
  }

  /**
   * @param list<array<string, mixed>> $rows
   * @return array{meta: array<string, mixed>, summary: array<string, mixed>, rows: list<array<string, mixed>>}
   */
  private function buildYearlyReport(int $year, string $memberUUID, ?User $memberUser, array $rows): array
  {
    /** @var array<string, array<string, mixed>> $siteTotals */
    $siteTotals = [];
    foreach ($rows as $row) {
      $siteId = $this->stringValue($row['site_id'] ?? '');
      $siteKey = $siteId !== '' ? $siteId : ($this->stringValue($row['site_name'] ?? '') ?: 'UNKNOWN');
      if (!isset($siteTotals[$siteKey])) {
        $siteTotals[$siteKey] = [
          'site_id' => $siteKey,
          'site_name' => $this->stringValue($row['site_name'] ?? '') ?: $siteKey,
          'regular' => 0.0,
          'overtime' => 0.0,
          'gross' => 0.0,
          'net' => 0.0,
          'employment_insurance' => 0.0,
          'canada_pension_plan' => 0.0,
          'old_age_security' => 0.0,
          'tax' => 0.0,
        ];
      }
      $siteTotals[$siteKey]['regular'] = $this->floatValue($siteTotals[$siteKey]['regular'] ?? 0) + $this->floatValue($row['regular_hours'] ?? 0);
      $siteTotals[$siteKey]['overtime'] = $this->floatValue($siteTotals[$siteKey]['overtime'] ?? 0) + $this->floatValue($row['overtime_hours'] ?? 0);
      $siteTotals[$siteKey]['gross'] = $this->floatValue($siteTotals[$siteKey]['gross'] ?? 0) + $this->floatValue($row['gross'] ?? 0);
      $siteTotals[$siteKey]['net'] = $this->floatValue($siteTotals[$siteKey]['net'] ?? 0) + $this->floatValue($row['net'] ?? 0);
      $siteTotals[$siteKey]['employment_insurance'] = $this->floatValue($siteTotals[$siteKey]['employment_insurance'] ?? 0) + $this->floatValue($row['employment_insurance'] ?? 0);
      $siteTotals[$siteKey]['canada_pension_plan'] = $this->floatValue($siteTotals[$siteKey]['canada_pension_plan'] ?? 0) + $this->floatValue($row['canada_pension_plan'] ?? 0);
      $siteTotals[$siteKey]['old_age_security'] = $this->floatValue($siteTotals[$siteKey]['old_age_security'] ?? 0) + $this->floatValue($row['old_age_security'] ?? 0);
      $siteTotals[$siteKey]['tax'] = $this->floatValue($siteTotals[$siteKey]['tax'] ?? 0) + $this->floatValue($row['tax'] ?? 0);
    }

    return [
      'meta' => array_merge($this->buildCommonMeta($year, 'Yearly', $memberUUID, $memberUser), ['scope' => 'yearly']),
      'summary' => $this->buildSummary($rows),
      'rows' => array_values(array_map(fn (array $row): array => $this->normalizeReportRow($row), $siteTotals)),
    ];
  }

  /**
   * @param list<array<string, mixed>> $rows
   * @return array{meta: array<string, mixed>, summary: array<string, mixed>, rows: list<array<string, mixed>>}
   */
  private function buildMonthlyReport(int $year, string $memberUUID, ?User $memberUser, array $rows): array
  {
    /** @var array<string, array<string, mixed>> $monthly */
    $monthly = [];
    foreach ($rows as $row) {
      $month = substr($this->stringValue($row['date'] ?? ''), 0, 7);
      if ($month === '') {
        continue;
      }
      $siteName = $this->stringValue($row['site_name'] ?? '') ?: ($this->stringValue($row['site_id'] ?? '') ?: 'UNKNOWN');
      $key = $month . '::' . $siteName;
      if (!isset($monthly[$key])) {
        $monthly[$key] = [
          'month' => $month,
          'site_name' => $siteName,
          'regular' => 0.0,
          'overtime' => 0.0,
          'gross' => 0.0,
          'employment_insurance' => 0.0,
          'canada_pension_plan' => 0.0,
          'old_age_security' => 0.0,
          'tax' => 0.0,
        ];
      }
      $monthly[$key]['regular'] = $this->floatValue($monthly[$key]['regular'] ?? 0) + $this->floatValue($row['regular_hours'] ?? 0);
      $monthly[$key]['overtime'] = $this->floatValue($monthly[$key]['overtime'] ?? 0) + $this->floatValue($row['overtime_hours'] ?? 0);
      $monthly[$key]['gross'] = $this->floatValue($monthly[$key]['gross'] ?? 0) + $this->floatValue($row['gross'] ?? 0);
      $monthly[$key]['employment_insurance'] = $this->floatValue($monthly[$key]['employment_insurance'] ?? 0) + $this->floatValue($row['employment_insurance'] ?? 0);
      $monthly[$key]['canada_pension_plan'] = $this->floatValue($monthly[$key]['canada_pension_plan'] ?? 0) + $this->floatValue($row['canada_pension_plan'] ?? 0);
      $monthly[$key]['old_age_security'] = $this->floatValue($monthly[$key]['old_age_security'] ?? 0) + $this->floatValue($row['old_age_security'] ?? 0);
      $monthly[$key]['tax'] = $this->floatValue($monthly[$key]['tax'] ?? 0) + $this->floatValue($row['tax'] ?? 0);
    }

    uasort($monthly, static function (array $a, array $b): int {
      $leftMonth = isset($a['month']) && is_scalar($a['month']) ? (string) $a['month'] : '';
      $rightMonth = isset($b['month']) && is_scalar($b['month']) ? (string) $b['month'] : '';
      $monthCompare = strcmp($leftMonth, $rightMonth);
      if ($monthCompare !== 0) {
        return $monthCompare;
      }

      $leftSite = isset($a['site_name']) && is_scalar($a['site_name']) ? (string) $a['site_name'] : '';
      $rightSite = isset($b['site_name']) && is_scalar($b['site_name']) ? (string) $b['site_name'] : '';
      return strcmp($leftSite, $rightSite);
    });

    return [
      'meta' => array_merge($this->buildCommonMeta($year, 'Monthly', $memberUUID, $memberUser), ['scope' => 'monthly']),
      'summary' => $this->buildSummary($rows),
      'rows' => array_values(array_map(fn (array $row): array => $this->normalizeReportRow($row), $monthly)),
    ];
  }

  /**
   * @param list<array<string, mixed>> $rows
   * @return array{meta: array<string, mixed>, summary: array<string, mixed>, rows: list<array<string, mixed>>}
   */
  private function buildDailyReport(int $year, string $memberUUID, ?User $memberUser, array $rows): array
  {
    /** @var array<string, array<string, mixed>> $daily */
    $daily = [];
    foreach ($rows as $row) {
      $date = $this->stringValue($row['date'] ?? '');
      if ($date === '') {
        continue;
      }
      if (!isset($daily[$date])) {
        $daily[$date] = [
          'date' => $date,
          'site_name' => '',
          'regular' => 0.0,
          'overtime' => 0.0,
          'travel' => 0.0,
          'loa' => 0.0,
          'gross' => 0.0,
          'employment_insurance' => 0.0,
          'canada_pension_plan' => 0.0,
          'old_age_security' => 0.0,
          'tax' => 0.0,
          'net' => 0.0,
        ];
      }

      $siteName = $this->stringValue($row['site_name'] ?? '');
      if ($siteName !== '') {
        if ($daily[$date]['site_name'] === '') {
          $daily[$date]['site_name'] = $siteName;
        } elseif ($daily[$date]['site_name'] !== $siteName) {
          $daily[$date]['site_name'] = 'Multiple Sites';
        }
      }

      $daily[$date]['regular'] = $this->floatValue($daily[$date]['regular'] ?? 0) + $this->floatValue($row['regular_hours'] ?? 0);
      $daily[$date]['overtime'] = $this->floatValue($daily[$date]['overtime'] ?? 0) + $this->floatValue($row['overtime_hours'] ?? 0);
      $daily[$date]['travel'] = $this->floatValue($daily[$date]['travel'] ?? 0) + $this->floatValue($row['travel'] ?? 0);
      $daily[$date]['loa'] = $this->floatValue($daily[$date]['loa'] ?? 0) + $this->floatValue($row['loa'] ?? 0);
      $daily[$date]['gross'] = $this->floatValue($daily[$date]['gross'] ?? 0) + $this->floatValue($row['gross'] ?? 0);
      $daily[$date]['employment_insurance'] = $this->floatValue($daily[$date]['employment_insurance'] ?? 0) + $this->floatValue($row['employment_insurance'] ?? 0);
      $daily[$date]['canada_pension_plan'] = $this->floatValue($daily[$date]['canada_pension_plan'] ?? 0) + $this->floatValue($row['canada_pension_plan'] ?? 0);
      $daily[$date]['old_age_security'] = $this->floatValue($daily[$date]['old_age_security'] ?? 0) + $this->floatValue($row['old_age_security'] ?? 0);
      $daily[$date]['tax'] = $this->floatValue($daily[$date]['tax'] ?? 0) + $this->floatValue($row['tax'] ?? 0);
      $daily[$date]['net'] = $this->floatValue($daily[$date]['net'] ?? 0) + $this->floatValue($row['net'] ?? 0);
    }
    ksort($daily);

    return [
      'meta' => array_merge($this->buildCommonMeta($year, 'Daily', $memberUUID, $memberUser), ['scope' => 'daily']),
      'summary' => $this->buildSummary($rows),
      'rows' => array_values(array_map(fn (array $row): array => $this->normalizeReportRow($row), $daily)),
    ];
  }

  /**
   * @param list<array<string, mixed>> $rows
   * @return array<string, mixed>
   */
  private function buildSummary(array $rows): array
  {
    $summary = [
      'regular_hours' => 0.0,
      'overtime_hours' => 0.0,
      'gross' => 0.0,
      'federal_tax' => 0.0,
      'provincial_tax' => 0.0,
      'employment_insurance' => 0.0,
      'canada_pension_plan' => 0.0,
      'old_age_security' => 0.0,
      'taxes' => 0.0,
      'net' => 0.0,
    ];

    foreach ($rows as $row) {
      $summary['regular_hours'] += $this->floatValue($row['regular_hours'] ?? 0);
      $summary['overtime_hours'] += $this->floatValue($row['overtime_hours'] ?? 0);
      $summary['gross'] += $this->floatValue($row['gross'] ?? 0);
      $summary['federal_tax'] += $this->floatValue($row['federal_tax'] ?? 0);
      $summary['provincial_tax'] += $this->floatValue($row['provincial_tax'] ?? 0);
      $summary['employment_insurance'] += $this->floatValue($row['employment_insurance'] ?? 0);
      $summary['canada_pension_plan'] += $this->floatValue($row['canada_pension_plan'] ?? 0);
      $summary['old_age_security'] += $this->floatValue($row['old_age_security'] ?? 0);
      $summary['taxes'] += $this->floatValue($row['tax'] ?? 0);
      $summary['net'] += $this->floatValue($row['net'] ?? 0);
    }

    return $this->normalizeReportRow($summary);
  }

  /**
   * @param array<string, mixed> $row
   * @return array<string, mixed>
   */
  private function normalizeReportRow(array $row): array
  {
    $normalized = $row;
    foreach ($normalized as $key => $value) {
      if (is_float($value) || is_int($value)) {
        $normalized[$key] = $this->round2((float) $value);
      }
    }

    return $normalized;
  }

  /** @return array<string, mixed> */
  private function buildCommonMeta(int $year, string $scopeLabel, string $memberUUID, ?User $memberUser): array
  {
    $province = $memberUser instanceof User ? trim($memberUser->province) : '';

    return [
      'title' => "PayCal.app - {$year} {$scopeLabel} Earnings Report",
      'subtitle' => $this->formatAsAtDate(),
      'employee' => $memberUUID,
      'year' => $year,
      'email' => $memberUser instanceof User ? trim($memberUser->email) : '',
      'phone' => $memberUser instanceof User ? trim($memberUser->phone) : '',
      'ip_address' => $this->requestIpAddress(),
      'address' => $memberUser instanceof User ? trim((string) $memberUser->address_line1) : '',
      'full_name' => $memberUser instanceof User ? trim($memberUser->full_name) : '',
      'city' => $memberUser instanceof User ? trim((string) $memberUser->address_city) : '',
      'province' => $province,
      'postal' => $memberUser instanceof User ? trim((string) $memberUser->address_postal) : '',
      'created_at' => gmdate('Y-m-d H:i:s') . ' UTC',
      'as_at' => $this->formatAsAtDate(),
      'reference_code' => $this->generateReferenceCode(),
    ];
  }

  private function buildFilename(string $scope, string $format, int $year, ?User $memberUser, string $memberUUID): string
  {
    $name = $memberUser instanceof User && trim($memberUser->full_name) !== ''
      ? trim($memberUser->full_name)
      : $memberUUID;
    $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) ?: 'member';
    $safeName = trim($safeName, '-_');
    if ($safeName === '') {
      $safeName = 'member';
    }

    return "PayCal-{$year}-" . ucfirst($scope) . "-Report-{$safeName}.{$format}";
  }

  /** @param array<string, string> $entry */
  private function resolveDate(string $workKey, array $entry): string
  {
    $parts = explode(':', $workKey);
    $isArchived = isset($parts[1]) && $parts[1] === 'archived';
    $date = $isArchived ? (string) ($parts[3] ?? '') : (string) ($parts[2] ?? '');
    if (strlen($date) >= 10) {
      return substr($date, 0, 10);
    }

    $entryDate = $this->stringValue($entry['date'] ?? '');
    return strlen($entryDate) >= 10 ? substr($entryDate, 0, 10) : '';
  }

  /** @param array<string, string> $entry */
  private function resolveSiteId(string $workKey, array $entry): string
  {
    $parts = explode(':', $workKey);
    $isArchived = isset($parts[1]) && $parts[1] === 'archived';
    $siteId = $isArchived ? (string) ($parts[4] ?? '') : (string) ($parts[3] ?? '');
    if ($siteId !== '') {
      return $siteId;
    }

    return $this->stringValue($entry['site_id'] ?? '');
  }

  /** @param array<string, string> $entry */
  private function storedOrCalculated(array $entry, string $key, float $calculated): float
  {
    $stored = $this->round2($this->floatValue($entry[$key] ?? 0));
    return $stored > 0.0 ? $stored : $this->round2($calculated);
  }

  private function resolveProvinceName(?User $memberUser): string
  {
    if (!$memberUser instanceof User) {
      return 'Alberta';
    }

    $candidate = trim($memberUser->province);
    if ($candidate === '') {
      return 'Alberta';
    }

    $upper = strtoupper($candidate);
    $provinceNames = [
      'AB' => 'Alberta',
      'BC' => 'British Columbia',
      'SK' => 'Saskatchewan',
      'MB' => 'Manitoba',
      'ON' => 'Ontario',
      'QC' => 'Quebec',
      'NS' => 'Nova Scotia',
      'NB' => 'New Brunswick',
      'NL' => 'Newfoundland',
      'PE' => 'Prince Edward Island',
      'YT' => 'Yukon',
      'NT' => 'Northwest Territories',
      'NU' => 'Nunavut',
    ];

    return $provinceNames[$upper] ?? $candidate;
  }

  private function normalizeYear(int $year): int
  {
    if ($year < 2000 || $year > 2100) {
      return (int) date('Y');
    }

    return $year;
  }

  private function floatValue(mixed $value): float
  {
    return is_numeric($value) ? (float) $value : 0.0;
  }

  private function stringValue(mixed $value): string
  {
    return is_scalar($value) ? trim((string) $value) : '';
  }

  /**
   * @param mixed $entries
   * @return array<string, array<string, string>>
   */
  private function normalizeEntries(mixed $entries): array
  {
    if (!is_array($entries)) {
      return [];
    }

    $normalized = [];
    foreach ($entries as $workKey => $entry) {
      if (!is_string($workKey) || !is_array($entry)) {
        continue;
      }

      $row = [];
      foreach ($entry as $key => $value) {
        if (is_string($key) && is_scalar($value)) {
          $row[$key] = (string) $value;
        }
      }
      $normalized[$workKey] = $row;
    }

    return $normalized;
  }

  private function round2(float $value): float
  {
    return round($value, 2);
  }

  private function formatAsAtDate(): string
  {
    $timestamp = time();
    return 'as at ' . date('F', $timestamp) . ' ' . $this->ordinalDay((int) date('j', $timestamp)) . ', ' . date('Y', $timestamp);
  }

  private function ordinalDay(int $day): string
  {
    $mod100 = $day % 100;
    if ($mod100 >= 11 && $mod100 <= 13) {
      return $day . 'th';
    }

    return match ($day % 10) {
      1 => $day . 'st',
      2 => $day . 'nd',
      3 => $day . 'rd',
      default => $day . 'th',
    };
  }

  private function generateReferenceCode(int $length = 16): string
  {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
      $code .= $alphabet[random_int(0, $max)];
    }

    return $code;
  }

  private function requestIpAddress(): string
  {
    $candidate = $_SERVER['REMOTE_ADDR'] ?? '';
    return is_scalar($candidate) && trim((string) $candidate) !== '' ? trim((string) $candidate) : 'unknown';
  }

  /** @param array<string, scalar|array<mixed>> $details */
  private function audit(string $businessId, string $eventType, string $actorUUID, array $details): void
  {
    (new BusinessDiscoveryService())->appendBusinessAuditEvent($businessId, $eventType, $actorUUID, $details);
  }

  /** @return array{success: bool, message: string, reason: string, data: array<string, mixed>} */
  private function fail(string $reason, string $message): array
  {
    return [
      'success' => false,
      'message' => $message,
      'reason' => $reason,
      'data' => ['reason' => $reason],
    ];
  }
}
