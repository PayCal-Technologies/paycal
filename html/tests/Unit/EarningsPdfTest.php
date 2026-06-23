<?php declare(strict_types=1);

use PayCal\Domain\EarningsPdf;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EarningsPdfTest extends TestCase
{
  #[Test]
  public function dailyPdfSupportsMultipagePrintModes(): void
  {
    foreach (['bw', 'grayscale', 'color'] as $printMode) {
      $pdf = EarningsPdf::generate('daily', $this->dailyReport(90), $printMode);

      $this->assertStringStartsWith('%PDF-1.4', $pdf);
      $this->assertStringContainsString('/Type /Page', $pdf);
      $this->assertGreaterThan(1000, strlen($pdf));
    }
  }

  /**
   * @return array<string, mixed>
   */
  private function dailyReport(int $rowCount): array
  {
    $rows = [];
    for ($i = 1; $i <= $rowCount; $i++) {
      $rows[] = [
        'date' => sprintf('2026-01-%02d', (($i - 1) % 28) + 1),
        'site_name' => 'Rio Tinto',
        'regular' => 8.0,
        'overtime' => ($i % 3) + 0.5,
        'travel' => 0.0,
        'loa' => 0.0,
        'gross' => 356.50,
        'employment_insurance' => 5.88,
        'canada_pension_plan' => 18.40,
        'old_age_security' => 0.0,
        'tax' => 84.00,
        'net' => 248.22,
      ];
    }

    return [
      'meta' => [
        'title' => 'PayCal Earnings Report',
        'employee' => 'test-user',
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'year' => 2026,
        'as_at' => '2026-06-23',
        'reference_code' => 'TEST-REF',
      ],
      'summary' => [
        'regular_hours' => $rowCount * 8.0,
        'overtime_hours' => $rowCount * 1.5,
        'gross' => $rowCount * 356.50,
        'net' => $rowCount * 248.22,
        'federal_tax' => $rowCount * 50.00,
        'provincial_tax' => $rowCount * 34.00,
        'employment_insurance' => $rowCount * 5.88,
        'canada_pension_plan' => $rowCount * 18.40,
        'old_age_security' => 0.0,
        'taxes' => $rowCount * 84.00,
      ],
      'rows' => $rows,
    ];
  }
}
