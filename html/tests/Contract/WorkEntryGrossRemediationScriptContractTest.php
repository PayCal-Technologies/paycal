<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class WorkEntryGrossRemediationScriptContractTest extends TestCase
{
  public function testWorkGrossRemediationIsRegisteredInScriptSuite(): void
  {
    $paycal = (string) file_get_contents(__DIR__ . '/../../../scripts/paycal');

    $this->assertStringContainsString('work:gross:remediate [--fix] [--json] [--include-archived]', $paycal);
    $this->assertStringContainsString('remediate-work-entry-gross.php', $paycal);
  }

  public function testWorkGrossRemediationBackfillsSnapshotFieldsAndInvalidatesCaches(): void
  {
    $script = (string) file_get_contents(__DIR__ . '/../../../scripts/remediate-work-entry-gross.php');

    foreach ([
      'calculateEarningsSnapshot',
      'regular_amount',
      'overtime_amount',
      'travel_amount',
      'living_out_amount',
      'earnings_snapshot_version',
      'siteWageForEntry',
      'EarningsCacheService::invalidateForUser',
      'BusinessWorkspaceCache::invalidateFinancialDataForMember',
    ] as $needle) {
      $this->assertStringContainsString($needle, $script);
    }
  }
}
