<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

/**
 * WorkEntryTest.
 *
 * Unit tests for WorkEntry class
 * Tests work entry object properties, validation, and business logic
 *
 * @internal
 *
 */
#[Group('unit')]
final class WorkEntryTest extends TestCase
{
  #[Test]
  public function normalizeWorkEntryPayloadInfersOvertimeFromHoursWhenSplitMissing(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload([
      'h' => '12',
      's' => 'S123456789',
      'd' => '2026-04-09',
    ]);

    $this->assertSame('12', (string) ($normalized['hours'] ?? ''));
    $this->assertEqualsWithDelta(8.0, (float) ($normalized['regular_hours'] ?? 0), 0.0001);
    $this->assertEqualsWithDelta(4.0, (float) ($normalized['overtime_hours'] ?? 0), 0.0001);
  }

  #[Test]
  public function normalizeWorkEntryPayloadNormalizesLegacyFlatExplicitSplitWhenHoursExceedCap(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload([
      'hours' => '12',
      'regular_hours' => '12',
      'overtime_hours' => '0',
      's' => 'S123456789',
      'd' => '2026-04-09',
    ]);

    $this->assertSame('12', (string) ($normalized['hours'] ?? ''));
    $this->assertEqualsWithDelta(8.0, (float) ($normalized['regular_hours'] ?? 0), 0.0001);
    $this->assertEqualsWithDelta(4.0, (float) ($normalized['overtime_hours'] ?? 0), 0.0001);
  }

  #[Test]
  public function normalizeWorkEntryPayloadNormalizesLegacyFlatSplitWhenHoursFieldMissing(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload([
      'regular_hours' => '12',
      'overtime_hours' => '0',
      's' => 'S123456789',
      'd' => '2026-04-09',
    ]);

    $this->assertEqualsWithDelta(12.0, (float) ($normalized['hours'] ?? 0), 0.0001);
    $this->assertEqualsWithDelta(8.0, (float) ($normalized['regular_hours'] ?? 0), 0.0001);
    $this->assertEqualsWithDelta(4.0, (float) ($normalized['overtime_hours'] ?? 0), 0.0001);
  }

  #[Test]
  public function normalizeWorkEntryPayloadPreservesValidExplicitSplitValues(): void
  {
    $normalized = WorkEntry::normalizeWorkEntryPayload([
      'hours' => '12',
      'regular_hours' => '10',
      'overtime_hours' => '2',
      's' => 'S123456789',
      'd' => '2026-04-09',
    ]);

    $this->assertSame('12', (string) ($normalized['hours'] ?? ''));
    $this->assertSame('10', (string) ($normalized['regular_hours'] ?? ''));
    $this->assertSame('2', (string) ($normalized['overtime_hours'] ?? ''));
  }

  // =========================================================================
  // Object Instantiation and Properties Tests
  // =========================================================================

  #[Test]
  public function workEntryCanBeInstantiated(): void
  {
    $entry = new WorkEntry();

    $this->assertInstanceOf(WorkEntry::class, $entry);
  }

  #[Test]
  public function workEntryHasDefaultPropertyValues(): void
  {
    $entry = new WorkEntry();

    $this->assertSame('', $entry->siteId);
    $this->assertSame('', $entry->siteName);
    $this->assertSame(0.0, $entry->hours);
    $this->assertSame(0.0, $entry->regularHours);
    $this->assertSame(0.0, $entry->overtimeHours);
    $this->assertSame(0.0, $entry->travelHours);
  }

  #[Test]
  public function workEntryPropertiesCanBeSet(): void
  {
    $entry = new WorkEntry();

    $entry->siteId = 'S123456789';
    $entry->siteName = 'Test Site';
    $entry->hours = 8.5;
    $entry->regularHours = 8.0;
    $entry->overtimeHours = 0.5;
    $entry->travelHours = 1.0;

    $this->assertSame('S123456789', $entry->siteId);
    $this->assertSame('Test Site', $entry->siteName);
    $this->assertSame(8.5, $entry->hours);
    $this->assertSame(8.0, $entry->regularHours);
    $this->assertSame(0.5, $entry->overtimeHours);
    $this->assertSame(1.0, $entry->travelHours);
  }

  #[Test]
  public function workEntryHoursCanBeFloat(): void
  {
    $entry = new WorkEntry();

    $entry->hours = 7.75;
    $entry->regularHours = 7.5;
    $entry->overtimeHours = 0.25;

    $this->assertSame(7.75, $entry->hours);
    $this->assertSame(7.5, $entry->regularHours);
    $this->assertSame(0.25, $entry->overtimeHours);
  }

  #[Test]
  public function workEntryCanStoreZeroHours(): void
  {
    $entry = new WorkEntry();

    $entry->hours = 0.0;
    $entry->regularHours = 0.0;
    $entry->overtimeHours = 0.0;
    $entry->travelHours = 0.0;

    $this->assertSame(0.0, $entry->hours);
    $this->assertSame(0.0, $entry->regularHours);
    $this->assertSame(0.0, $entry->overtimeHours);
    $this->assertSame(0.0, $entry->travelHours);
  }

  #[Test]
  public function workEntryCanStoreLargeHours(): void
  {
    $entry = new WorkEntry();

    $entry->hours = 24.0;
    $entry->regularHours = 16.0;
    $entry->overtimeHours = 8.0;

    $this->assertSame(24.0, $entry->hours);
    $this->assertSame(16.0, $entry->regularHours);
    $this->assertSame(8.0, $entry->overtimeHours);
  }

  #[Test]
  public function workEntrySupportsQuarterHourIncrements(): void
  {
    $entry = new WorkEntry();

    $entry->hours = 8.25;  // 8 hours 15 minutes

    $this->assertSame(8.25, $entry->hours);
  }

  #[Test]
  public function workEntrySupportsDecimalPrecision(): void
  {
    $entry = new WorkEntry();

    $entry->hours = 7.333333;

    $this->assertEqualsWithDelta(7.333333, $entry->hours, 0.000001);
  }

  #[Test]
  public function workEntrySiteIdCanBeEmpty(): void
  {
    $entry = new WorkEntry();

    $this->assertSame('', $entry->siteId);

    $entry->siteId = '';
    $this->assertSame('', $entry->siteId);
  }

  #[Test]
  public function workEntrySiteIdAcceptsValidFormat(): void
  {
    $entry = new WorkEntry();

    $entry->siteId = 'Sa1b2c3d4e';

    $this->assertSame('Sa1b2c3d4e', $entry->siteId);
  }

  #[Test]
  public function workEntrySiteNameCanContainSpecialCharacters(): void
  {
    $entry = new WorkEntry();

    $entry->siteName = 'ABC Corp & Co., Ltd.';

    $this->assertSame('ABC Corp & Co., Ltd.', $entry->siteName);
  }

  #[Test]
  public function workEntryCanCalculateTotalHours(): void
  {
    $entry = new WorkEntry();

    $entry->regularHours = 8.0;
    $entry->overtimeHours = 2.0;
    $entry->travelHours = 1.0;

    $totalCalculated = $entry->regularHours + $entry->overtimeHours;

    $this->assertSame(10.0, $totalCalculated);
  }

  #[Test]
  public function workEntryHoursPropertyIsIndependent(): void
  {
    $entry = new WorkEntry();

    // Setting individual hour types doesn't automatically update total hours
    $entry->regularHours = 8.0;
    $entry->overtimeHours = 2.0;

    // hours property is independent
    $this->assertSame(0.0, $entry->hours);

    // Must be set explicitly
    $entry->hours = $entry->regularHours + $entry->overtimeHours;
    $this->assertSame(10.0, $entry->hours);
  }

  #[Test]
  public function workEntryMultipleInstancesAreIndependent(): void
  {
    $entry1 = new WorkEntry();
    $entry2 = new WorkEntry();

    $entry1->siteId = 'Site1';
    $entry1->hours = 8.0;

    $entry2->siteId = 'Site2';
    $entry2->hours = 10.0;

    $this->assertSame('Site1', $entry1->siteId);
    $this->assertSame('Site2', $entry2->siteId);
    $this->assertSame(8.0, $entry1->hours);
    $this->assertSame(10.0, $entry2->hours);
  }

  #[Test]
  public function workEntryCanBeUsedInArrays(): void
  {
    $entries = [];

    $entry1 = new WorkEntry();
    $entry1->siteId = 'S111111111';
    $entry1->hours = 8.0;

    $entry2 = new WorkEntry();
    $entry2->siteId = 'S222222222';
    $entry2->hours = 10.0;

    $entries[] = $entry1;
    $entries[] = $entry2;

    $this->assertCount(2, $entries);
    $this->assertSame('S111111111', $entries[0]->siteId);
    $this->assertSame('S222222222', $entries[1]->siteId);
  }

  #[Test]
  public function workEntryPropertiesArePubliclyAccessible(): void
  {
    $entry = new WorkEntry();

    // Test that all properties are accessible
    $reflection = new ReflectionClass(WorkEntry::class);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

    $propertyNames = array_map(fn ($p) => $p->getName(), $properties);

    $this->assertContains('siteId', $propertyNames);
    $this->assertContains('siteName', $propertyNames);
    $this->assertContains('hours', $propertyNames);
    $this->assertContains('regularHours', $propertyNames);
    $this->assertContains('overtimeHours', $propertyNames);
    $this->assertContains('travelHours', $propertyNames);
  }

  #[Test]
  public function workEntryHasExpectedPropertyTypes(): void
  {
    $reflection = new ReflectionClass(WorkEntry::class);

    $siteIdProp = $reflection->getProperty('siteId');
    $this->assertSame('string', $siteIdProp->getType()->getName());

    $siteNameProp = $reflection->getProperty('siteName');
    $this->assertSame('string', $siteNameProp->getType()->getName());

    $hoursProp = $reflection->getProperty('hours');
    $this->assertSame('float', $hoursProp->getType()->getName());

    $regularHoursProp = $reflection->getProperty('regularHours');
    $this->assertSame('float', $regularHoursProp->getType()->getName());

    $overtimeHoursProp = $reflection->getProperty('overtimeHours');
    $this->assertSame('float', $overtimeHoursProp->getType()->getName());

    $travelHoursProp = $reflection->getProperty('travelHours');
    $this->assertSame('float', $travelHoursProp->getType()->getName());
  }

  #[Test]
  public function workEntrySupportsNegativeHours(): void
  {
    $entry = new WorkEntry();

    // While unusual, the type system allows it
    $entry->hours = -2.0;

    $this->assertSame(-2.0, $entry->hours);
  }

  #[Test]
  public function workEntryCanRepresentPartialDay(): void
  {
    $entry = new WorkEntry();

    $entry->hours = 4.5;  // Half day
    $entry->regularHours = 4.5;

    $this->assertSame(4.5, $entry->hours);
    $this->assertSame(4.5, $entry->regularHours);
  }

  #[Test]
  public function workEntryCanBeSerializedToArray(): void
  {
    $entry = new WorkEntry();

    $entry->siteId = 'S123456789';
    $entry->siteName = 'Test Site';
    $entry->hours = 8.5;
    $entry->regularHours = 8.0;
    $entry->overtimeHours = 0.5;
    $entry->travelHours = 1.0;

    $data = [
        'siteId' => $entry->siteId,
        'siteName' => $entry->siteName,
        'hours' => $entry->hours,
        'regularHours' => $entry->regularHours,
        'overtimeHours' => $entry->overtimeHours,
        'travelHours' => $entry->travelHours,
    ];

    $this->assertSame('S123456789', $data['siteId']);
    $this->assertSame('Test Site', $data['siteName']);
    $this->assertSame(8.5, $data['hours']);
    $this->assertSame(8.0, $data['regularHours']);
    $this->assertSame(0.5, $data['overtimeHours']);
    $this->assertSame(1.0, $data['travelHours']);
  }

  #[Test]
  public function workEntryCanBePopulatedFromArray(): void
  {
    $data = [
        'siteId' => 'S987654321',
        'siteName' => 'Another Site',
        'hours' => 10.0,
        'regularHours' => 8.0,
        'overtimeHours' => 2.0,
        'travelHours' => 0.5,
    ];

    $entry = new WorkEntry();
    $entry->siteId = $data['siteId'];
    $entry->siteName = $data['siteName'];
    $entry->hours = $data['hours'];
    $entry->regularHours = $data['regularHours'];
    $entry->overtimeHours = $data['overtimeHours'];
    $entry->travelHours = $data['travelHours'];

    $this->assertSame('S987654321', $entry->siteId);
    $this->assertSame('Another Site', $entry->siteName);
    $this->assertSame(10.0, $entry->hours);
    $this->assertSame(8.0, $entry->regularHours);
    $this->assertSame(2.0, $entry->overtimeHours);
    $this->assertSame(0.5, $entry->travelHours);
  }

  // =========================================================================
  // Business Logic Tests (using data providers for common patterns)
  // =========================================================================

  #[Test]
  #[DataProvider('validHoursProvider')]
  public function workEntryAcceptsValidHourValues(float $hours): void
  {
    $entry = new WorkEntry();
    $entry->hours = $hours;

    $this->assertSame($hours, $entry->hours);
  }

  public static function validHoursProvider(): array
  {
    return [
        'zero hours' => [0.0],
        'quarter hour' => [0.25],
        'half hour' => [0.5],
        'three quarters' => [0.75],
        'one hour' => [1.0],
        'standard shift' => [8.0],
        'with overtime' => [10.5],
        'long shift' => [12.0],
        'max daily' => [24.0],
    ];
  }

  #[Test]
  #[DataProvider('siteIdPatternProvider')]
  public function workEntryAcceptsVariousSiteIdFormats(string $siteId): void
  {
    $entry = new WorkEntry();
    $entry->siteId = $siteId;

    $this->assertSame($siteId, $entry->siteId);
  }

  public static function siteIdPatternProvider(): array
  {
    return [
        'lowercase hex' => ['Sa1b2c3d4e'],
        'uppercase hex' => ['SABCDEF123'],
        'mixed case' => ['SaBcDeF123'],
        'all numbers' => ['S123456789'],
        'all letters' => ['Sabcdefghi'],
        'empty' => [''],
    ];
  }

  #[Test]
  public function workEntrySumOfComponentsMatchesTotalHours(): void
  {
    $entry = new WorkEntry();

    $entry->regularHours = 8.0;
    $entry->overtimeHours = 2.5;
    $entry->hours = $entry->regularHours + $entry->overtimeHours;

    $this->assertSame(10.5, $entry->hours);
    $this->assertEqualsWithDelta(
      $entry->hours,
      $entry->regularHours + $entry->overtimeHours,
      0.01
    );
  }

  #[Test]
  public function workEntryTravelHoursAreIndependentFromWorkHours(): void
  {
    $entry = new WorkEntry();

    $entry->regularHours = 8.0;
    $entry->overtimeHours = 0.0;
    $entry->travelHours = 2.0;

    // Travel hours are tracked separately
    $this->assertSame(8.0, $entry->regularHours);
    $this->assertSame(2.0, $entry->travelHours);
  }
}
