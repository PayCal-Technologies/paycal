<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Enums\PayFrequency;
use PayCal\Domain\PayPeriods;
use PHPUnit\Framework\Attributes\Group;

/**
 * PayPeriods Test Suite.
 *
 * Comprehensive testing of the PayPeriods class covering:
 * - Factory methods (fromDate, fromRange)
 * - All frequency types (WEEKLY, BIWEEKLY, SEMIMONTHLY, MONTHLY)
 * - Period calculation and containment
 * - Navigation (next/previous)
 * - Date operations and serialization
 * - Timezone-aware operations with midnight normalization
 * - Pay period number computation for fiscal year alignment
 *
 * @internal
 *
 */
#[Group('unit')]
#[Group('pay-periods')]
class PayPeriodsTest extends TestCase
{
  private DateTimeZone $tz;

  protected function setUp(): void
  {
    $this->tz = new DateTimeZone('America/Edmonton');
  }

  // ========== Factory Methods ==========

  #[Test]
  public function testFromDateCreatesInstanceForWeekly(): void
  {
    $date = new DateTimeImmutable('2025-01-15', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $this->assertNotNull($period);
    $this->assertInstanceOf(PayPeriods::class, $period);
    $this->assertEquals('2025-01-13', $period->start()->format('Y-m-d'));
  }

  #[Test]
  public function testFromDateCreatesInstanceForBiweekly(): void
  {
    $date = new DateTimeImmutable('2025-01-15', $this->tz);
    $epoch = new DateTimeImmutable('2025-01-13', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $epoch, 'America/Edmonton');

    $this->assertNotNull($period);
    $this->assertInstanceOf(PayPeriods::class, $period);
  }

  #[Test]
  public function testFromDateCreatesInstanceForSemiMonthly(): void
  {
    $date = new DateTimeImmutable('2025-01-20', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::SEMIMONTHLY, 'Friday', null, 'America/Edmonton');

    $this->assertNotNull($period);
    $this->assertInstanceOf(PayPeriods::class, $period);
  }

  #[Test]
  public function testFromDateCreatesInstanceForMonthly(): void
  {
    $date = new DateTimeImmutable('2025-02-15', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::MONTHLY, 'Friday', null, 'America/Edmonton');

    $this->assertNotNull($period);
    $this->assertInstanceOf(PayPeriods::class, $period);
  }

  #[Test]
  public function testFromDateStringParameter(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $this->assertNotNull($period);
    $this->assertInstanceOf(PayPeriods::class, $period);
  }

  #[Test]
  public function testFromRangeCreatesInstanceWithExplicitDates(): void
  {
    $start = new DateTimeImmutable('2025-01-13', $this->tz);
    $end = new DateTimeImmutable('2025-01-26', $this->tz);

    $period = PayPeriods::fromRange($start, $end, PayFrequency::BIWEEKLY, 'Monday', null);

    $this->assertNotNull($period);
    $this->assertInstanceOf(PayPeriods::class, $period);
    $this->assertEquals('2025-01-13', $period->start()->format('Y-m-d'));
    $this->assertEquals('2025-01-26', $period->endExclusive()->format('Y-m-d'));
  }

  // ========== Accessor Methods ==========

  #[Test]
  public function testStartReturnsInclusiveStartDate(): void
  {
    $date = new DateTimeImmutable('2025-01-15', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $start = $period->start();
    $this->assertInstanceOf(DateTimeImmutable::class, $start);
    $this->assertEquals('2025-01-13', $start->format('Y-m-d'));
  }

  #[Test]
  public function testEndExclusiveReturnsExclusiveEnd(): void
  {
    $date = new DateTimeImmutable('2025-01-15', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $end = $period->endExclusive();
    $this->assertInstanceOf(DateTimeImmutable::class, $end);
    $this->assertEquals('2025-01-20', $end->format('Y-m-d'));
  }

  #[Test]
  public function testEndInclusiveReturnsLastDayOfPeriod(): void
  {
    $date = new DateTimeImmutable('2025-01-15', $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $end = $period->endInclusive();
    $this->assertInstanceOf(DateTimeImmutable::class, $end);
    $this->assertEquals('2025-01-19', $end->format('Y-m-d'));
  }

  // ========== Frequency-Specific Calculations ==========

  #[Test]
  #[DataProvider('weeklyPeriodProvider')]
  public function testWeeklyPeriodCalculation(string $inputDate, string $expectedStart, int $expectedLength): void
  {
    $date = new DateTimeImmutable($inputDate, $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $this->assertEquals($expectedStart, $period->start()->format('Y-m-d'));
    $this->assertEquals($expectedLength, $period->lengthDays());
  }

  public static function weeklyPeriodProvider(): array
  {
    return [
        'Monday start' => ['2025-01-13', '2025-01-13', 7],
        'Wednesday in period' => ['2025-01-15', '2025-01-13', 7],
        'Sunday end' => ['2025-01-19', '2025-01-13', 7],
        'Next Monday' => ['2025-01-20', '2025-01-20', 7],
    ];
  }

  #[Test]
  #[DataProvider('biweeklyPeriodProvider')]
  public function testBiweeklyPeriodCalculation(string $inputDate, string $expectedStart, int $expectedLength): void
  {
    $epoch = new DateTimeImmutable('2025-01-13', $this->tz);
    $date = new DateTimeImmutable($inputDate, $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $epoch, 'America/Edmonton');

    $this->assertEquals($expectedStart, $period->start()->format('Y-m-d'));
    $this->assertEquals($expectedLength, $period->lengthDays());
  }

  public static function biweeklyPeriodProvider(): array
  {
    return [
        'Start period' => ['2025-01-13', '2025-01-13', 14],
        'Mid first period' => ['2025-01-15', '2025-01-13', 14],
        'End of first period' => ['2025-01-26', '2025-01-13', 14],
        'Start of second period' => ['2025-01-27', '2025-01-27', 14],
    ];
  }

  // ========== Timezone Canonicalization (UTC -> User TZ) ==========

  #[Test]
  #[Group('timezone')]
  #[DataProvider('utcCanonicalWeeklyProvider')]
  public function testWeeklyPeriodFromUtcCanonicalInstantAcrossTimezones(
    string $canonicalUtc,
    string $timezone,
    string $expectedStart
  ): void {
    $date = new DateTimeImmutable($canonicalUtc, new DateTimeZone('UTC'));

    $period = PayPeriods::fromDate($date, PayFrequency::WEEKLY, 'Monday', null, $timezone);

    $this->assertEquals($expectedStart, $period->start()->format('Y-m-d'));
    $this->assertEquals($timezone, $period->toArray()['timezone']);
  }

  public static function utcCanonicalWeeklyProvider(): array
  {
    return [
        'UTC baseline' => ['2025-01-06 00:30:00', 'UTC', '2025-01-06'],
        'US Pacific previous local day' => ['2025-01-06 00:30:00', 'America/Los_Angeles', '2024-12-30'],
        'US Eastern previous local day' => ['2025-01-06 00:30:00', 'America/New_York', '2024-12-30'],
        'London same local day' => ['2025-01-06 00:30:00', 'Europe/London', '2025-01-06'],
        'Berlin same local day' => ['2025-01-06 00:30:00', 'Europe/Berlin', '2025-01-06'],
        'India half-hour offset' => ['2025-01-06 00:30:00', 'Asia/Kolkata', '2025-01-06'],
        'Tokyo same local day' => ['2025-01-06 00:30:00', 'Asia/Tokyo', '2025-01-06'],
        'Auckland same local day' => ['2025-01-06 00:30:00', 'Pacific/Auckland', '2025-01-06'],
        'Honolulu previous local day' => ['2025-01-06 00:30:00', 'Pacific/Honolulu', '2024-12-30'],
          'Adak previous local day' => ['2025-01-06 00:30:00', 'America/Adak', '2024-12-30'],
          'Nepal quarter-hour offset' => ['2025-01-06 00:30:00', 'Asia/Kathmandu', '2025-01-06'],
          'Eucla quarter-hour offset' => ['2025-01-06 00:30:00', 'Australia/Eucla', '2025-01-06'],
          'Chatham three-quarter offset' => ['2025-01-06 00:30:00', 'Pacific/Chatham', '2025-01-06'],
          'Apia dateline region' => ['2025-01-06 00:30:00', 'Pacific/Apia', '2025-01-06'],
    ];
  }

  #[Test]
        #[Group('timezone')]
  public function testContainsUsesPeriodTimezoneAtUtcDayBoundary(): void
  {
    $period = PayPeriods::fromDate('2025-03-12', PayFrequency::WEEKLY, 'Monday', null, 'America/New_York');

    $justBeforeLocalMidnight = new DateTimeImmutable('2025-03-10 03:59:59', new DateTimeZone('UTC'));
    $atLocalMidnight = new DateTimeImmutable('2025-03-10 04:00:00', new DateTimeZone('UTC'));

    $this->assertFalse($period->contains($justBeforeLocalMidnight));
    $this->assertTrue($period->contains($atLocalMidnight));
  }

  #[Test]
  #[Group('timezone')]
  public function testContainsUsesPeriodTimezoneAtUtcDayBoundaryAcrossDstFallBack(): void
  {
    $period = PayPeriods::fromDate('2025-11-06', PayFrequency::WEEKLY, 'Monday', null, 'America/New_York');

    $justBeforeLocalMidnight = new DateTimeImmutable('2025-11-03 04:59:59', new DateTimeZone('UTC'));
    $atLocalMidnight = new DateTimeImmutable('2025-11-03 05:00:00', new DateTimeZone('UTC'));

    $this->assertFalse($period->contains($justBeforeLocalMidnight));
    $this->assertTrue($period->contains($atLocalMidnight));
  }

  #[Test]
  #[Group('timezone')]
  public function testContainsAmbiguousRepeatedLocalHourDuringDstFallBack(): void
  {
    $period = PayPeriods::fromDate('2025-11-02', PayFrequency::WEEKLY, 'Monday', null, 'America/New_York');

    $first0130 = new DateTimeImmutable('2025-11-02 05:30:00', new DateTimeZone('UTC'));  // 01:30 EDT
    $second0130 = new DateTimeImmutable('2025-11-02 06:30:00', new DateTimeZone('UTC')); // 01:30 EST

    $this->assertTrue($period->contains($first0130));
    $this->assertTrue($period->contains($second0130));
  }

  #[Test]
  #[Group('timezone')]
  public function testDaysStayAtMidnightAcrossDstSpringTransition(): void
  {
    $period = PayPeriods::fromDate('2025-03-09', PayFrequency::WEEKLY, 'Monday', null, 'America/New_York');

    $days = $period->days();
    $this->assertCount(7, $days);
    foreach ($days as $day) {
      $this->assertEquals('00:00:00', $day->format('H:i:s'));
      $this->assertEquals('America/New_York', $day->getTimezone()->getName());
    }
  }

  #[Test]
  #[Group('timezone')]
  public function testDaysStayAtMidnightAcrossDstFallTransition(): void
  {
    $period = PayPeriods::fromDate('2025-11-06', PayFrequency::WEEKLY, 'Monday', null, 'America/New_York');

    $days = $period->days();
    $this->assertCount(7, $days);
    foreach ($days as $day) {
      $this->assertEquals('00:00:00', $day->format('H:i:s'));
      $this->assertEquals('America/New_York', $day->getTimezone()->getName());
    }
  }

  #[Test]
  #[Group('timezone')]
  public function testFromDateIsoUtcStringCanonicalizesToUserTimezone(): void
  {
    $period = PayPeriods::fromDate('2025-01-06T00:30:00Z', PayFrequency::WEEKLY, 'Monday', null, 'America/Los_Angeles');

    $this->assertEquals('2024-12-30', $period->start()->format('Y-m-d'));
  }

  #[Test]
  #[Group('timezone')]
  public function testBiweeklyUsesUtcEpochTranslatedIntoUserTimezone(): void
  {
    $date = new DateTimeImmutable('2025-01-19 15:00:00', new DateTimeZone('UTC')); // 2025-01-20 local Tokyo
    $epoch = new DateTimeImmutable('2025-01-12 15:00:00', new DateTimeZone('UTC')); // 2025-01-13 local Tokyo

    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $epoch, 'Asia/Tokyo');

    $this->assertEquals('2025-01-13', $period->start()->format('Y-m-d'));
    $this->assertEquals(14, $period->lengthDays());
  }

  #[Test]
  #[Group('timezone')]
  public function testBiweeklyRealignsEpochAfterTimezoneTranslation(): void
  {
    $date = new DateTimeImmutable('2025-01-15 12:00:00', new DateTimeZone('UTC'));
    $misalignedEpoch = new DateTimeImmutable('2025-01-14 00:30:00', new DateTimeZone('UTC')); // Tuesday local in Auckland

    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $misalignedEpoch, 'Pacific/Auckland');

    $this->assertEquals('2025-01-13', $period->start()->format('Y-m-d'));
    $this->assertEquals(14, $period->lengthDays());
  }

  #[Test]
  #[Group('timezone')]
  public function testBiweeklyWithQuarterHourTimezoneOffsetRemainsStable(): void
  {
    $date = new DateTimeImmutable('2025-01-20 00:10:00', new DateTimeZone('UTC'));
    $epoch = new DateTimeImmutable('2025-01-13 00:10:00', new DateTimeZone('UTC'));

    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $epoch, 'Pacific/Chatham');

    $this->assertEquals('2025-01-13', $period->start()->format('Y-m-d'));
    $this->assertEquals(14, $period->lengthDays());
  }

  #[Test]
  public function testBiweeklyRealignsMisalignedEpochToAnchorWeekday(): void
  {
    $date = new DateTimeImmutable('2025-01-15', $this->tz);
    $misalignedEpoch = new DateTimeImmutable('2025-01-14', $this->tz); // Tuesday

    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $misalignedEpoch, 'America/Edmonton');

    $this->assertEquals('2025-01-13', $period->start()->format('Y-m-d'));
    $this->assertEquals(14, $period->lengthDays());
  }

  #[Test]
  public function testBiweeklyRealignsMisalignedEpochWhenDateBeforeEpoch(): void
  {
    $date = new DateTimeImmutable('2025-01-10', $this->tz);
    $misalignedEpoch = new DateTimeImmutable('2025-01-14', $this->tz); // Tuesday

    $period = PayPeriods::fromDate($date, PayFrequency::BIWEEKLY, 'Monday', $misalignedEpoch, 'America/Edmonton');

    $this->assertEquals('2024-12-30', $period->start()->format('Y-m-d'));
    $this->assertEquals(14, $period->lengthDays());
  }

  #[Test]
  #[DataProvider('semiMonthlyPeriodProvider')]
  public function testSemiMonthlyPeriodCalculation(string $inputDate, string $expectedStart, string $expectedEnd): void
  {
    $date = new DateTimeImmutable($inputDate, $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::SEMIMONTHLY, 'Friday', null, 'America/Edmonton');

    $this->assertEquals($expectedStart, $period->start()->format('Y-m-d'));
    $this->assertEquals($expectedEnd, $period->endInclusive()->format('Y-m-d'));
  }

  public static function semiMonthlyPeriodProvider(): array
  {
    return [
        'First half' => ['2025-01-10', '2025-01-01', '2025-01-15'],
        'Day 15' => ['2025-01-15', '2025-01-01', '2025-01-15'],
        'Second half' => ['2025-01-20', '2025-01-16', '2025-01-31'],
        'Day 16' => ['2025-01-16', '2025-01-16', '2025-01-31'],
        'End of month' => ['2025-01-31', '2025-01-16', '2025-01-31'],
    ];
  }

  #[Test]
  #[DataProvider('monthlyPeriodProvider')]
  public function testMonthlyPeriodCalculation(string $inputDate, int $expectedMonth, int $expectedDays): void
  {
    $date = new DateTimeImmutable($inputDate, $this->tz);
    $period = PayPeriods::fromDate($date, PayFrequency::MONTHLY, 'Friday', null, 'America/Edmonton');

    $this->assertEquals($expectedMonth, (int) $period->start()->format('m'));
    $this->assertEquals($expectedDays, $period->lengthDays());
  }

  public static function monthlyPeriodProvider(): array
  {
    return [
        'January' => ['2025-01-15', 1, 31],
        'February leap' => ['2024-02-15', 2, 29],
        'February' => ['2025-02-15', 2, 28],
        'April' => ['2025-04-15', 4, 30],
    ];
  }

  // ========== Period Containment ==========

  #[Test]
  #[DataProvider('containmentProvider')]
  public function testContainsDateChecksPeriodBoundaries(string $periodDate, string $checkDate, bool $expected): void
  {
    $period = PayPeriods::fromDate($periodDate, PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $date = new DateTimeImmutable($checkDate, $this->tz);

    $this->assertEquals($expected, $period->contains($date));
  }

  public static function containmentProvider(): array
  {
    return [
        'Start included' => ['2025-01-15', '2025-01-13', true],
        'Mid included' => ['2025-01-15', '2025-01-16', true],
        'End included' => ['2025-01-15', '2025-01-19', true],
        'After excluded' => ['2025-01-15', '2025-01-20', false],
        'Before excluded' => ['2025-01-15', '2025-01-12', false],
    ];
  }

  #[Test]
  public function testContainsUsesExclusiveEndDate(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');

    $lastDayIncluded = new DateTimeImmutable('2025-01-19', $this->tz);
    $firstDayExcluded = new DateTimeImmutable('2025-01-20', $this->tz);

    $this->assertTrue($period->contains($lastDayIncluded));
    $this->assertFalse($period->contains($firstDayExcluded));
  }

  // ========== Days Array Generation ==========

  #[Test]
  public function testDaysReturnsArrayOfDateTimeImmutable(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $days = $period->days();

    $this->assertIsArray($days);
    $this->assertCount(7, $days);

    foreach ($days as $day) {
      $this->assertInstanceOf(DateTimeImmutable::class, $day);
    }
  }

  #[Test]
  public function testDaysStartsAtPeriodStartDate(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $days = $period->days();

    $this->assertEquals('2025-01-13', reset($days)->format('Y-m-d'));
  }

  #[Test]
  public function testDaysEndsAtLastDayOfPeriod(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $days = $period->days();

    $this->assertEquals('2025-01-19', end($days)->format('Y-m-d'));
  }

  #[Test]
  public function testDaysAreConsecutive(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $days = $period->days();

    $prev = null;
    foreach ($days as $day) {
      if (null !== $prev) {
        $diff = $day->diff($prev)->days;
        $this->assertEquals(1, $diff);
      }
      $prev = $day;
    }
  }

  // ========== Navigation ==========

  #[Test]
  public function testNextReturnsFollowingPeriod(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $next = $period->next();

    $this->assertInstanceOf(PayPeriods::class, $next);
    $this->assertEquals('2025-01-20', $next->start()->format('Y-m-d'));
    $this->assertEquals('2025-01-26', $next->endInclusive()->format('Y-m-d'));
  }

  #[Test]
  public function testPreviousReturnsPrecedingPeriod(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $prev = $period->previous();

    $this->assertInstanceOf(PayPeriods::class, $prev);
    $this->assertEquals('2025-01-06', $prev->start()->format('Y-m-d'));
    $this->assertEquals('2025-01-12', $prev->endInclusive()->format('Y-m-d'));
  }

  #[Test]
  public function testNavigationRoundTrip(): void
  {
    $original = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $next = $original->next();
    $back = $next->previous();

    $this->assertEquals($original->start()->format('Y-m-d'), $back->start()->format('Y-m-d'));
    $this->assertEquals($original->endInclusive()->format('Y-m-d'), $back->endInclusive()->format('Y-m-d'));
  }

  #[Test]
  public function testNavigationPreservesBiweeklyAlignment(): void
  {
    $epoch = new DateTimeImmutable('2025-01-13', $this->tz);
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::BIWEEKLY, 'Monday', $epoch, 'America/Edmonton');
    $next = $period->next();

    $this->assertEquals('2025-01-27', $next->start()->format('Y-m-d'));
    $this->assertEquals(14, $next->lengthDays());
  }

  // ========== Label Generation ==========

  #[Test]
  public function testLabelGeneratesReadableString(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $label = $period->label();

    $this->assertIsString($label);
    $this->assertStringContainsString('2025-01-13', $label);
    $this->assertStringContainsString('2025-01-19', $label);
  }

  #[Test]
  public function testLabelWithCustomFormat(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $label = $period->label('M/d');

    $this->assertIsString($label);
  }

  // ========== Serialization ==========

  #[Test]
  public function testToArrayReturnsCompleteStructure(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $arr = $period->toArray();

    $this->assertIsArray($arr);
    $this->assertArrayHasKey('frequency', $arr);
    $this->assertArrayHasKey('timezone', $arr);
    $this->assertArrayHasKey('anchor', $arr);
    $this->assertArrayHasKey('start', $arr);
    $this->assertArrayHasKey('end_exclusive', $arr);
    $this->assertArrayHasKey('end_inclusive', $arr);
    $this->assertArrayHasKey('length_days', $arr);
  }

  #[Test]
  public function testToArrayContainsAllRequiredFields(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $arr = $period->toArray();

    $this->assertArrayHasKey('start', $arr);
    $this->assertArrayHasKey('end_exclusive', $arr);
    $this->assertArrayHasKey('end_inclusive', $arr);
    $this->assertArrayHasKey('length_days', $arr);
    $this->assertEquals(7, $arr['length_days']);
  }

  #[Test]
  public function testToArrayContainsFrequencyValue(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $arr = $period->toArray();

    $this->assertEquals('weekly', $arr['frequency']);
  }

  // ========== Type Checking ==========

  #[Test]
  public function testStartReturnTypeIsDateTimeImmutableclass(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->start();

    $this->assertInstanceOf(DateTimeImmutable::class, $result);
  }

  #[Test]
  public function testEndExclusiveReturnType(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->endExclusive();

    $this->assertInstanceOf(DateTimeImmutable::class, $result);
  }

  #[Test]
  public function testLengthDaysReturnType(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->lengthDays();

    $this->assertIsInt($result);
  }

  #[Test]
  public function testContainsReturnType(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->contains(new DateTimeImmutable('2025-01-15', $this->tz));

    $this->assertIsBool($result);
  }

  #[Test]
  public function testDaysReturnType(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->days();

    $this->assertIsArray($result);
  }

  #[Test]
  public function testLabelReturnType(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->label();

    $this->assertIsString($result);
  }

  #[Test]
  public function testToArrayReturnType(): void
  {
    $period = PayPeriods::fromDate('2025-01-15', PayFrequency::WEEKLY, 'Monday', null, 'America/Edmonton');
    $result = $period->toArray();

    $this->assertIsArray($result);
  }
}
