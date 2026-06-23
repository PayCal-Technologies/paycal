<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Catalog of member report/export options supported by the business members bulk UI.
 */
final class BusinessMemberReportCatalog
{
  /**
   * @return array<int, array{key: string, label: string, scope: string, description: string}>
   */
  public static function reports(): array
  {
    return [
      [
        'key' => 'ytd',
        'label' => 'Yearly work summary',
        'scope' => 'yearly',
        'description' => 'Includes hours, sites, gross totals, allowances, and yearly totals.',
      ],
      [
        'key' => 'monthly',
        'label' => 'Monthly work summary',
        'scope' => 'monthly',
        'description' => 'Groups member work by month with hours, sites, gross totals, and allowances.',
      ],
      [
        'key' => 'daily',
        'label' => 'Daily work summary',
        'scope' => 'daily',
        'description' => 'Lists daily work entries with sites, hours, allowances, and daily totals.',
      ],
    ];
  }

  /**
   * @return array<int, array{key: string, label: string}>
   */
  public static function formats(): array
  {
    return [
      ['key' => 'csv', 'label' => 'CSV'],
      ['key' => 'txt', 'label' => 'TXT'],
      ['key' => 'xlsx', 'label' => 'XLSX'],
      ['key' => 'pdf', 'label' => 'PDF'],
    ];
  }
}
