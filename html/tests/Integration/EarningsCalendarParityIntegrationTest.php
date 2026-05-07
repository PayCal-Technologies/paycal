<?php declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/Support/EncryptedWorkTestUser.php';

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Earnings;
use PayCal\Domain\Work;
use PayCal\Controllers\EarningsController;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Skip;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Support\EncryptedWorkTestUser;

/**
 * Integration parity test between Calendar and Earnings retrieval paths.
 *
 * Purpose:
 * - Seed plaintext work entries for a known timeframe.
 * - Read calendar source rows directly from the canonical work store.
 * - Read earnings totals for the same timeframe.
 * - Assert date-level parity for hours/gross across the same timeframe.
 */
#[Group('integration')]
final class EarningsCalendarParityIntegrationTest extends TestCase
{
  private EncryptedWorkTestUser $fixture;
  private string $userUUID;
  private string $siteId;
  private string $siteName;

  protected function setUp(): void
  {
    parent::setUp();

    $this->fixture = EncryptedWorkTestUser::create();
    $this->userUUID = $this->fixture->userUUID;
    $this->siteId = 'S' . strtolower(substr(bin2hex(random_bytes(8)), 0, 9));
    $this->siteName = 'Strathcona Resources Ltd.';
    $this->fixture->addSite($this->siteId, $this->siteName, 23.0);
    $this->fixture->ensureCurrentDateHasWorkData($this->siteId, $this->siteName, 23.0, 8.0);
  }

  protected function tearDown(): void
  {
    $this->fixture->cleanup();
    $_GET = [];

    parent::tearDown();
  }

  public function testCalendarDecryptedRangeMatchesEarningsDailyRange(): void
  {
    $year = max((int) SystemConfig::get('year_min'), 2026);
    $start = sprintf('%04d-03-29', $year);
    $end = sprintf('%04d-04-02', $year);

    $seedRows = [
      ['date' => sprintf('%04d-03-29', $year), 'hours' => 12.0, 'regular' => 8.0, 'overtime' => 4.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 23.0],
      ['date' => sprintf('%04d-03-30', $year), 'hours' => 12.0, 'regular' => 8.0, 'overtime' => 4.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 23.0],
      ['date' => sprintf('%04d-03-31', $year), 'hours' => 12.0, 'regular' => 8.0, 'overtime' => 4.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 23.0],
      ['date' => sprintf('%04d-04-01', $year), 'hours' => 12.0, 'regular' => 8.0, 'overtime' => 4.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 23.0],
      ['date' => sprintf('%04d-04-02', $year), 'hours' => 12.0, 'regular' => 8.0, 'overtime' => 4.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 23.0],
    ];

    foreach ($seedRows as $row) {
      $this->fixture->seedWorkRow([
        'date' => $row['date'],
        'site_id' => $this->siteId,
        'site_name' => $this->siteName,
        'hours' => $row['hours'],
        'regular_hours' => $row['regular'],
        'overtime_hours' => $row['overtime'],
        'travel_hours' => $row['travel'],
        'living_out_allowance' => $row['loa'],
        'wage' => $row['wage'],
        'gross' => ($row['regular'] * $row['wage']) + (($row['overtime'] * $row['wage']) * 1.5) + ($row['travel'] * $row['wage']) + $row['loa'],
      ]);
      $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $row['date'] . ':' . $this->siteId;
      $this->assertTrue(Database::exists($workKey), 'Failed to seed work entry for ' . $row['date']);
    }

    $calendarAggregates = $this->aggregateCalendarDecryptedRange($start, $end);

    foreach ($this->dateRange($start, $end) as $dateKey) {
      $this->assertArrayHasKey($dateKey, $calendarAggregates, 'Calendar missing seeded date ' . $dateKey);

      $calendarRow = $calendarAggregates[$dateKey];
      $dayStart = new \DateTimeImmutable($dateKey . ' 00:00:00');
      $earningsRow = Earnings::getTotalsForRange($dayStart, $dayStart, $this->userUUID);

      $amounts = isset($earningsRow['amounts']) && is_array($earningsRow['amounts']) ? $earningsRow['amounts'] : [];
      $hours = isset($earningsRow['hours']) && is_array($earningsRow['hours']) ? $earningsRow['hours'] : [];
      $totals = isset($earningsRow['totals']) && is_array($earningsRow['totals']) ? $earningsRow['totals'] : [];

      $this->assertEqualsWithDelta($calendarRow['gross'], $this->toFloat($totals['gross'] ?? 0), 0.01, 'Gross mismatch on ' . $dateKey);
      $this->assertEqualsWithDelta($calendarRow['hours'], $this->toFloat($hours['total'] ?? 0), 0.01, 'Hours mismatch on ' . $dateKey);
      $this->assertEqualsWithDelta($calendarRow['regular_hours'], $this->toFloat($hours['regular'] ?? 0), 0.01, 'Regular mismatch on ' . $dateKey);
      $this->assertEqualsWithDelta($calendarRow['overtime_hours'], $this->toFloat($hours['overtime'] ?? 0), 0.01, 'OT mismatch on ' . $dateKey);
      $this->assertEqualsWithDelta($calendarRow['wage_amount'], $this->toFloat($amounts['wage'] ?? 0), 0.01, 'Wage amount mismatch on ' . $dateKey);
    }
  }

  #[Skip('renderSections("lazy") stalls in PHPUnit process (confirmed rendering-infra issue unrelated to gross omission). The gross-absent aggregation path is exercised by EarningsWorkTotalsIntegrationTest::getWorkTotalsForRange_returnsGrossIncomeCents. Fix stall separately before enabling this test.')]
  #[Group('skip')]
  public function testRenderSectionsLazyDoesNotFatalWhenEncryptedWorkRowOmitsGross(): void
  {
    $this->markTestSkipped('renderSections("lazy") stalls in PHPUnit process — see skip reason above');

    $year = max((int) SystemConfig::get('year_min'), 2026);
    $workDate = sprintf('%04d-06-15', $year);
    $this->fixture->seedWorkRow([
      'date' => $workDate,
      'site_id' => $this->siteId,
      'site_name' => $this->siteName,
      'hours' => 12.0,
      'regular_hours' => 8.0,
      'overtime_hours' => 4.0,
      'travel_hours' => 0.0,
      'living_out_allowance' => 0.0,
      'wage' => 23.0,
    ]);

    $html = Earnings::getInstance()->renderSections('lazy');

    $this->assertNotSame('', trim($html));
    $this->assertStringContainsString('earnings_year_tablist', $html);
    $this->assertStringContainsString((string) $year, $html);
  }

  public function testTotalsForRangeUsesSiteWageFallbackWhenEncryptedRowOmitsWageAndGross(): void
  {
    $year = max((int) SystemConfig::get('year_min'), 2026);
    $workDate = sprintf('%04d-05-03', $year);
    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $workDate . ':' . $this->siteId;
    Database::hset($workKey, [
      'date' => $workDate,
      'site_id' => $this->siteId,
      'site_name' => $this->siteName,
      'hours' => '12.00',
      'regular_hours' => '8.00',
      'overtime_hours' => '4.00',
      'travel_hours' => '0.00',
      'living_out_allowance' => '0.00',
    ]);

    $dayStart = new \DateTimeImmutable($workDate . ' 00:00:00');
    $totals = Earnings::getTotalsForRange($dayStart, $dayStart, $this->userUUID);

    $this->assertEqualsWithDelta(322.00, $this->toFloat($totals['totals']['gross'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(12.00, $this->toFloat($totals['hours']['total'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(8.00, $this->toFloat($totals['hours']['regular'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(4.00, $this->toFloat($totals['hours']['overtime'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(322.00, $this->toFloat($totals['amounts']['wage'] ?? 0), 0.01);
  }

  public function testTotalsForRangeInfersOvertimeFromHoursWhenEncryptedRowOmitsSplit(): void
  {
    $year = max((int) SystemConfig::get('year_min'), 2026);
    $workDate = sprintf('%04d-05-04', $year);
    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $workDate . ':' . $this->siteId;
    // Delete any existing key so setUp-seeded split fields (regular_hours, overtime_hours)
    // do not interfere with the "omits split" scenario this test exercises.
    Database::del($workKey);
    Database::hset($workKey, [
      'date' => $workDate,
      'site_id' => $this->siteId,
      'site_name' => $this->siteName,
      'hours' => '12.00',
      'travel_hours' => '0.00',
      'living_out_allowance' => '0.00',
      'wage' => '23.00',
    ]);

    $dayStart = new \DateTimeImmutable($workDate . ' 00:00:00');
    $totals = Earnings::getTotalsForRange($dayStart, $dayStart, $this->userUUID);

    $this->assertEqualsWithDelta(12.00, $this->toFloat($totals['hours']['total'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(8.00, $this->toFloat($totals['hours']['regular'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(4.00, $this->toFloat($totals['hours']['overtime'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(322.00, $this->toFloat($totals['totals']['gross'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(322.00, $this->toFloat($totals['amounts']['wage'] ?? 0), 0.01);
  }

  public function testTotalsForRangeNormalizesLegacySnapshotFlatSplitWithoutBlob(): void
  {
    $year = max((int) SystemConfig::get('year_min'), 2026);
    $workDate = sprintf('%04d-05-05', $year);
    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $workDate . ':' . $this->siteId;
    Database::hset($workKey, [
      'date' => $workDate,
      'site_id' => $this->siteId,
      'site_name' => $this->siteName,
      'hours' => '12.00',
      'regular_hours' => '12.00',
      'overtime_hours' => '0.00',
      'travel_hours' => '0.00',
      'living_out_allowance' => '0.00',
      'wage' => '23.00',
      'gross' => '276.00',
    ]);

    $dayStart = new \DateTimeImmutable($workDate . ' 00:00:00');
    $totals = Earnings::getTotalsForRange($dayStart, $dayStart, $this->userUUID);

    $this->assertEqualsWithDelta(12.00, $this->toFloat($totals['hours']['total'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(8.00, $this->toFloat($totals['hours']['regular'] ?? 0), 0.01);
    $this->assertEqualsWithDelta(4.00, $this->toFloat($totals['hours']['overtime'] ?? 0), 0.01);
  }

  public function testGrossAndNetRemainConsistentAcrossEarningsCompositions(): void
  {
    $year = max((int) SystemConfig::get('year_min'), 2026);

    $siteIdB = 'S' . strtolower(substr(bin2hex(random_bytes(8)), 0, 9));
    $this->fixture->addSite($siteIdB, 'Prairie Logistics', 30.0);

    $seedRows = [
      ['date' => sprintf('%04d-01-10', $year), 'site_id' => $this->siteId, 'site_name' => $this->siteName, 'regular' => 8.0, 'overtime' => 2.0, 'travel' => 1.0, 'loa' => 20.0, 'wage' => 23.0],
      ['date' => sprintf('%04d-01-10', $year), 'site_id' => $siteIdB, 'site_name' => 'Prairie Logistics', 'regular' => 4.0, 'overtime' => 0.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 30.0],
      ['date' => sprintf('%04d-02-05', $year), 'site_id' => $this->siteId, 'site_name' => $this->siteName, 'regular' => 10.0, 'overtime' => 0.0, 'travel' => 0.0, 'loa' => 0.0, 'wage' => 23.0],
      ['date' => sprintf('%04d-02-20', $year), 'site_id' => $this->siteId, 'site_name' => $this->siteName, 'regular' => 8.0, 'overtime' => 4.0, 'travel' => 0.0, 'loa' => 10.0, 'wage' => 23.0],
    ];

    foreach ($seedRows as $row) {
      $hours = $row['regular'] + $row['overtime'];
      $gross = ($row['regular'] * $row['wage'])
        + (($row['overtime'] * $row['wage']) * 1.5)
        + ($row['travel'] * $row['wage'])
        + $row['loa'];

      $this->fixture->seedWorkRow([
        'date' => $row['date'],
        'site_id' => $row['site_id'],
        'site_name' => $row['site_name'],
        'hours' => $hours,
        'regular_hours' => $row['regular'],
        'overtime_hours' => $row['overtime'],
        'travel_hours' => $row['travel'],
        'living_out_allowance' => $row['loa'],
        'wage' => $row['wage'],
        'gross' => number_format($gross, 2, '.', ''),
      ]);
    }

    $yearStart = new \DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $year));
    $yearEnd = new \DateTimeImmutable(sprintf('%04d-12-31 00:00:00', $year));
    $totals = Earnings::getTotalsForRange($yearStart, $yearEnd, $this->userUUID);
    $grossTotals = $this->toFloat($totals['totals']['gross'] ?? 0);
    $netTotals = $this->toFloat($totals['totals']['net'] ?? 0);

    $dailyPayload = $this->callYearEndpoint('getDaily', $year);
    $dailyGross = 0.0;
    $dailyNet = 0.0;
    foreach ($dailyPayload as $row) {
      if (!is_array($row)) {
        continue;
      }
      $dailyGross += $this->toFloat($row['gross'] ?? 0);
      $dailyNet += $this->toFloat($row['net'] ?? 0);
    }

    $grossPayload = $this->callYearEndpoint('getGross', $year);
    $grossPanelTotal = 0.0;
    foreach ($grossPayload as $value) {
      if (is_numeric($value)) {
        $grossPanelTotal += (float) $value;
      }
    }

    $monthlyHtml = Earnings::getInstance()->renderMonthlyViewStrip($year);
    [$monthlyGross, $monthlyNet] = $this->sumMonthlyGrossAndNetFromHtml($monthlyHtml);

    $this->assertEqualsWithDelta($grossTotals, $dailyGross, 0.01, 'Daily composition gross must equal yearly totals gross.');
    $this->assertEqualsWithDelta($grossTotals, $grossPanelTotal, 0.01, 'Gross panel composition must equal yearly totals gross.');
    $this->assertEqualsWithDelta($grossTotals, $monthlyGross, 0.01, 'Monthly composition gross must equal yearly totals gross.');

    $this->assertEqualsWithDelta($netTotals, $dailyNet, 0.01, 'Daily composition net must equal yearly totals net.');
    $this->assertEqualsWithDelta($netTotals, $monthlyNet, 0.02, 'Monthly composition net must equal yearly totals net.');
  }

  /**
   * @return array<string, array{gross: float, hours: float, regular_hours: float, overtime_hours: float, wage_amount: float}>
   */
  private function aggregateCalendarDecryptedRange(string $start, string $end): array
  {
    $aggregate = [];

    $startDate = new \DateTimeImmutable($start . ' 00:00:00');
    $endExclusive = (new \DateTimeImmutable($end . ' 00:00:00'))->modify('+1 day');

    foreach (Work::getWorkInRange($startDate, $endExclusive, $this->userUUID) as $entry) {
      if (!is_array($entry)) {
        continue;
      }
      $dateKey = is_string($entry['date'] ?? null) ? (string) $entry['date'] : '';
      if ($dateKey === '' || $dateKey < $start || $dateKey > $end) {
        continue;
      }

      if (!isset($aggregate[$dateKey])) {
        $aggregate[$dateKey] = [
          'gross' => 0.0,
          'hours' => 0.0,
          'regular_hours' => 0.0,
          'overtime_hours' => 0.0,
          'wage_amount' => 0.0,
        ];
      }

      $aggregate[$dateKey]['gross'] += $this->toFloat($entry['gross'] ?? 0);
      $aggregate[$dateKey]['hours'] += $this->toFloat($entry['hours'] ?? 0);
      $aggregate[$dateKey]['regular_hours'] += $this->toFloat($entry['regular_hours'] ?? 0);
      $aggregate[$dateKey]['overtime_hours'] += $this->toFloat($entry['overtime_hours'] ?? 0);
      $wage = $this->toFloat($entry['wage'] ?? 0);
      $regular = $this->toFloat($entry['regular_hours'] ?? 0);
      $overtime = $this->toFloat($entry['overtime_hours'] ?? 0);
      $travel = $this->toFloat($entry['travel_hours'] ?? 0);
      $aggregate[$dateKey]['wage_amount'] += ($regular * $wage) + (($overtime * $wage) * 1.5) + ($travel * $wage);
    }

    return $aggregate;
  }

  /** @return array<int, string> */
  private function dateRange(string $start, string $end): array
  {
    $dates = [];
    $cursor = new \DateTimeImmutable($start);
    $endDate = new \DateTimeImmutable($end);
    while ($cursor <= $endDate) {
      $dates[] = $cursor->format('Y-m-d');
      $cursor = $cursor->modify('+1 day');
    }

    return $dates;
  }

  private function toFloat(mixed $value): float
  {
    if (is_int($value) || is_float($value)) {
      return (float) $value;
    }

    if (is_string($value) && is_numeric($value)) {
      return (float) $value;
    }

    return 0.0;
  }

  /** @return array<string, mixed> */
  private function callYearEndpoint(string $method, int $year): array
  {
    $_GET = [];
    ob_start();
    EarningsController::{$method}((string) $year);
    $raw = ob_get_clean();

    $this->assertIsString($raw);
    $decoded = json_decode($raw, true);
    $this->assertIsArray($decoded);

    $payload = $decoded['data'] ?? $decoded;
    $this->assertIsArray($payload);

    return $payload;
  }

  /** @return array{0: float, 1: float} */
  private function sumMonthlyGrossAndNetFromHtml(string $html): array
  {
    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new \DOMXPath($doc);

    $gross = 0.0;
    $net = 0.0;
    $seenRowKeys = [];
    $rows = $xpath->query('//div[contains(@class,"datagrid_row")]');
    if ($rows === false) {
      return [0.0, 0.0];
    }

    foreach ($rows as $row) {
      $cells = $xpath->query('.//div[contains(@class,"datagrid_item")]', $row);
      if ($cells === false || $cells->length < 11) {
        continue;
      }

      $rowKey = trim((string) $cells->item(0)?->textContent);
      if ($rowKey === '') {
        continue;
      }
      if (isset($seenRowKeys[$rowKey])) {
        continue;
      }
      $seenRowKeys[$rowKey] = true;

      $gross += $this->moneyTextToFloat((string) $cells->item(3)?->textContent);
      $net += $this->moneyTextToFloat((string) $cells->item(10)?->textContent);
    }

    return [$gross, $net];
  }

  private function moneyTextToFloat(string $text): float
  {
    if (preg_match('/-?\$?\s*([0-9,]+\.[0-9]{2})/', $text, $m) !== 1) {
      return 0.0;
    }

    return (float) str_replace(',', '', (string) $m[1]);
  }
}
