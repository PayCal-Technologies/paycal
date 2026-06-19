<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Catalog of member report/export options supported by the business members bulk UI.
 */
final class BusinessMemberReportCatalog
{
  /**
   * @return array<int, array{key: string, label: string, scope: string}>
   */
  public static function reports(): array
  {
    return [
      ['key' => 'ytd', 'label' => 'Yearly work summary', 'scope' => 'yearly'],
      ['key' => 'monthly', 'label' => 'Monthly work summary', 'scope' => 'monthly'],
      ['key' => 'daily', 'label' => 'Daily work summary', 'scope' => 'daily'],
    ];
  }

  /**
   * @return array<int, array{key: string, label: string}>
   */
  public static function formats(): array
  {
    return [
      ['key' => 'csv', 'label' => 'CSV (browser convenience)'],
      ['key' => 'txt', 'label' => 'TXT (browser convenience)'],
      ['key' => 'xlsx', 'label' => 'XLSX'],
      ['key' => 'pdf', 'label' => 'PDF'],
    ];
  }
}
