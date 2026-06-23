<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\BusinessMemberReportCatalog;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BusinessMemberReportCatalogTest extends TestCase
{
  #[Test]
  public function reportsExposeSupportedBulkExportScopes(): void
  {
    $reports = BusinessMemberReportCatalog::reports();

    $this->assertSame(['ytd', 'monthly', 'daily'], array_column($reports, 'key'));
    $this->assertSame(['yearly', 'monthly', 'daily'], array_column($reports, 'scope'));
    $this->assertSame(['Yearly work summary', 'Monthly work summary', 'Daily work summary'], array_column($reports, 'label'));
    $this->assertSame([
      'Includes hours, sites, gross totals, allowances, and yearly totals.',
      'Groups member work by month with hours, sites, gross totals, and allowances.',
      'Lists daily work entries with sites, hours, allowances, and daily totals.',
    ], array_column($reports, 'description'));
  }

  #[Test]
  public function formatsExposeSupportedBulkExportFormats(): void
  {
    $formats = BusinessMemberReportCatalog::formats();

    $this->assertSame(['csv', 'txt', 'xlsx', 'pdf'], array_column($formats, 'key'));
    $this->assertSame(['CSV', 'TXT', 'XLSX', 'PDF'], array_column($formats, 'label'));
  }
}
