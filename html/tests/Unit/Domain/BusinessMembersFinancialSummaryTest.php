<?php declare(strict_types=1);

namespace PayCal\Tests\Unit\Domain;

use PayCal\Domain\BusinessMembersFinancialSummary;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BusinessMembersFinancialSummaryTest extends TestCase
{
  #[Test]
  public function forBusinessMembersReturnsEmptySummaryForUnknownMembers(): void
  {
    $service = new BusinessMembersFinancialSummary();
    $result = $service->forBusinessMembers('business-123', ['member-a', 'member-b']);

    $this->assertArrayHasKey('member-a', $result);
    $this->assertArrayHasKey('member-b', $result);
    $this->assertSame(0.0, $result['member-a']['ytd_gross']);
    $this->assertSame(0.0, $result['member-a']['total_hours']);
    $this->assertSame(0.0, $result['member-a']['trailing_baseline']);
  }

  #[Test]
  public function formatCurrencyAndHoursMatchReportsStyle(): void
  {
    $service = new BusinessMembersFinancialSummary();

    $this->assertSame('$1,234.50', $service->formatCurrency(1234.5));
    $this->assertSame('12.50', $service->formatHours(12.5));
  }
}
