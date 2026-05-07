<?php declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/Support/EncryptedWorkTestUser.php';

use DateTimeImmutable;
use PayCal\Domain\Earnings;
use PayCal\Domain\Money;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Support\EncryptedWorkTestUser;

/**
 * Integration tests for Earnings::getWorkTotalsForRange() under a real authenticated
 * user with encrypted work rows.
 *
 * Why this file exists:
 * - getWorkTotalsForRange() reads from Redis and calls resolveWorkRow() which needs
 *   a live session + credential for DEK decryption. These tests cannot run without
 *   that context, so they live here rather than in EarningsTest (unit).
 * - The five tests below were previously stubs in EarningsTest.php tagged
 *   #[Group('skip')]; they are now real, passing integration tests.
 *
 * Fixture: EncryptedWorkTestUser — provisions a user+session+DEK+site, seeds work
 * rows with encrypted_blob, and tears everything down in tearDown().
 */
#[Group('integration')]
final class EarningsWorkTotalsIntegrationTest extends TestCase
{
  private EncryptedWorkTestUser $fixture;
  private string $siteId;

  protected function setUp(): void
  {
    parent::setUp();

    $this->fixture = EncryptedWorkTestUser::create();
    $this->siteId  = 'S' . strtolower(substr(bin2hex(random_bytes(8)), 0, 9));
    $this->fixture->addSite($this->siteId, 'Integration Test Site', 25.0);

    // Seed two known work rows so totals are deterministic.
    // Row A: 8 regular + 2 OT @ $25 → gross = (8×25) + (2×25×1.5) = 200 + 75 = 275.00
    // Row B: 8 regular + 0 OT @ $25 → gross = (8×25) = 200.00
    // Combined → grossIncome = 475.00, grossIncomeCents = 47500, regularHours = 16, overtimeHours = 2
    $this->fixture->seedWorkRow([
      'date'             => '2026-03-10',
      'site_id'          => $this->siteId,
      'site_name'        => 'Integration Test Site',
      'regular_hours'    => 8.0,
      'overtime_hours'   => 2.0,
      'travel_hours'     => 0.0,
      'living_out_allowance' => 0.0,
      'wage'             => 25.0,
    ]);
    $this->fixture->seedWorkRow([
      'date'             => '2026-03-11',
      'site_id'          => $this->siteId,
      'site_name'        => 'Integration Test Site',
      'regular_hours'    => 8.0,
      'overtime_hours'   => 0.0,
      'travel_hours'     => 0.0,
      'living_out_allowance' => 0.0,
      'wage'             => 25.0,
    ]);
  }

  protected function tearDown(): void
  {
    $this->fixture->cleanup();
    parent::tearDown();
  }

  #[Test]
  public function getWorkTotalsForRange_returnsGrossIncomeCents(): void
  {
    $start = new DateTimeImmutable('2026-03-10');
    $end   = new DateTimeImmutable('2026-03-11');

    $totals = Earnings::getWorkTotalsForRange($start, $end);

    $this->assertArrayHasKey('grossIncomeCents', $totals);
    $this->assertIsInt($totals['grossIncomeCents']);
    $this->assertSame(47500, $totals['grossIncomeCents']);
  }

  #[Test]
  public function getWorkTotalsForRange_grossIncomeCents_matchesGrossIncome(): void
  {
    $start = new DateTimeImmutable('2026-03-10');
    $end   = new DateTimeImmutable('2026-03-11');

    $totals = Earnings::getWorkTotalsForRange($start, $end);

    $expectedCents = Money::dollarsToCents((string) $totals['grossIncome']);
    $this->assertSame(
      $expectedCents,
      $totals['grossIncomeCents'],
      "grossIncomeCents ({$totals['grossIncomeCents']}) must match dollarsToCents(grossIncome) ({$expectedCents})"
    );
  }

  #[Test]
  public function getWorkTotalsForRange_grossIncome_isFloat(): void
  {
    $start = new DateTimeImmutable('2026-03-10');
    $end   = new DateTimeImmutable('2026-03-11');

    $totals = Earnings::getWorkTotalsForRange($start, $end);

    $this->assertIsFloat($totals['grossIncome']);
    $this->assertEqualsWithDelta(475.0, $totals['grossIncome'], 0.01);
  }

  #[Test]
  public function getWorkTotalsForRange_additivityInvariant(): void
  {
    // Full range should equal sum of individual days.
    $dayA = new DateTimeImmutable('2026-03-10');
    $dayB = new DateTimeImmutable('2026-03-11');

    $totalsA    = Earnings::getWorkTotalsForRange($dayA, $dayA);
    $totalsB    = Earnings::getWorkTotalsForRange($dayB, $dayB);
    $totalsFull = Earnings::getWorkTotalsForRange($dayA, $dayB);

    $this->assertSame(
      $totalsA['grossIncomeCents'] + $totalsB['grossIncomeCents'],
      $totalsFull['grossIncomeCents'],
      'Gross cents additivity: sum of parts must equal full range total'
    );
    $this->assertEqualsWithDelta(
      $totalsA['regularHours'] + $totalsB['regularHours'],
      $totalsFull['regularHours'],
      0.001,
      'Regular hours additivity failed'
    );
    $this->assertEqualsWithDelta(
      $totalsA['overtimeHours'] + $totalsB['overtimeHours'],
      $totalsFull['overtimeHours'],
      0.001,
      'Overtime hours additivity failed'
    );
  }

  #[Test]
  public function getWorkTotalsForRange_nonNegativeGuarantees(): void
  {
    $start = new DateTimeImmutable('2026-03-10');
    $end   = new DateTimeImmutable('2026-03-11');

    $totals = Earnings::getWorkTotalsForRange($start, $end);

    $this->assertGreaterThanOrEqual(0, $totals['grossIncomeCents'], 'grossIncomeCents must be non-negative');
    $this->assertGreaterThanOrEqual(0.0, $totals['grossIncome'],    'grossIncome must be non-negative');
    $this->assertGreaterThanOrEqual(0.0, $totals['regularHours'],   'regularHours must be non-negative');
    $this->assertGreaterThanOrEqual(0.0, $totals['overtimeHours'],  'overtimeHours must be non-negative');
  }
}
