<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain\Earnings;

use PayCal\Domain\BusinessMembersFinancialSummary;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Forensic tests for BusinessMembersFinancialSummary earnings aggregation.
 */
final class BusinessMembersFinancialSummaryForensicTest extends TestCase
{
  #[Test]
  public function forensicForBusinessMembersReturnsArrayKeyedByMemberUuid(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers('biz-1', ['m1', 'm2']);
    $this->assertCount(2, $result);
    $this->assertArrayHasKey('m1', $result);
    $this->assertArrayHasKey('m2', $result);
  }

  #[Test]
  public function forensicSummaryFieldsAreNumeric(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $row = $service->forBusinessMembers('biz', ['member'])['member'];
    $this->assertIsFloat($row['ytd_gross']);
    $this->assertIsFloat($row['total_hours']);
    $this->assertIsFloat($row['trailing_baseline']);
  }

  #[Test]
  public function forensicFormatCurrencyUsesDollarPrefix(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $this->assertStringStartsWith('$', $service->formatCurrency(99.99));
  }

  #[Test]
  public function forensicFormatHoursUsesTwoDecimalPlaces(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $service->formatHours(8.0));
  }

  #[Test]
  public function forensicEmptyMemberListReturnsEmptyArray(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $this->assertSame([], $service->forBusinessMembers('biz', []));
  }

  #[Test]
  public function forensicCollectMemberBusinessWorkExistsInSource(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 4) . '/src/Domain/BusinessMembersFinancialSummary.php',
    );
    $this->assertStringContainsString('collectMemberOrgWork', $source);
    $this->assertStringContainsString('ytd_gross', $source);
  }

  #[Test]
  public function forensicFinancialSummaryReadsGrossNotTax(): void
  {
    $source = (string) file_get_contents(
      dirname(__DIR__, 4) . '/src/Domain/BusinessMembersFinancialSummary.php',
    );
    $this->assertStringContainsString("entry['gross']", $source);
    $this->assertStringNotContainsString('calculateTaxesCents', $source);
  }

  #[Test]
  public function forensicOrgMemberRollupIncludesAllWorkEntriesRegardlessOfSite(): void
  {
    $summarySource = (string) file_get_contents(
      dirname(__DIR__, 4) . '/src/Domain/BusinessMembersFinancialSummary.php',
    );
    $reportsSource = (string) file_get_contents(
      dirname(__DIR__, 4) . '/src/Domain/BusinessMemberReportsService.php',
    );
    $teamSource = (string) file_get_contents(
      dirname(__DIR__, 4) . '/reports/_partials/team_earnings_data.php',
    );

    $this->assertStringContainsString('org-visible work entries', $summarySource);
    $this->assertStringContainsString('BusinessWorkVisibilityPolicy', $summarySource);
    $this->assertStringNotContainsString("if (\$matchStrategy === 'no_match')", $summarySource);
    $this->assertStringNotContainsString("if (\$matchStrategy === 'no_match')", $reportsSource);
    $this->assertStringContainsString('included_unlinked', $teamSource);
    $this->assertStringNotContainsString("if (\$matchStrategy === 'no_match') {\n        continue;", $teamSource);
  }
}
